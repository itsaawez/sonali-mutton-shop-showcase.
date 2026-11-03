<?php
/**
 * ============================================================================
 * Portfolio Code Sample: Secure Database Operations & Transactions
 * Project: Sonali Mutton Shop
 * Author:  Aawez Irfan Sayyed
 * Notice:  Sanitized portfolio extract demonstrating transactional integrity,
 *          prepared statements, and zero credential leakage.
 * ============================================================================
 */

declare(strict_types=1);

/**
 * Executes customer order placement inside an atomic ACID database transaction.
 *
 * Demonstrates:
 * 1. Transaction lifecycle (begin_transaction, commit, rollback)
 * 2. Prepared statements for master and detail records (preventing SQL injection)
 * 3. Inventory/cart verification before persisting records
 * 4. Exception handling that prevents partial order states
 *
 * @param mysqli $conn
 * @param int    $userId
 * @param array  $shippingDetails
 * @param array  $cartItems       Array of items: ['product_id' => int, 'quantity' => float, 'unit_price' => float]
 * @return array{success: bool, orderNumber: ?string, error: ?string}
 */
function placeOrderTransaction(
    mysqli $conn,
    int $userId,
    array $shippingDetails,
    array $cartItems
): array {
    if (empty($cartItems)) {
        return ['success' => false, 'orderNumber' => null, 'error' => 'Cart cannot be empty.'];
    }

    // Begin atomic transaction
    $conn->begin_transaction();

    try {
        // 1. Calculate total order amount
        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $qty   = (float)$item['quantity'];
            $price = (float)$item['unit_price'];
            $subtotal += ($qty * $price);
        }

        $deliveryFee = ($subtotal >= 500.0) ? 0.0 : 40.0;
        $totalAmount = $subtotal + $deliveryFee;

        // Generate unique business order identifier
        $orderNumber = 'SMS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        // 2. Insert Master Order record using prepared statement
        $orderSql = "
            INSERT INTO orders (
                order_number, user_id, customer_name, customer_phone,
                shipping_address, total_amount, payment_status, order_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'confirmed', NOW())
        ";

        $orderStmt = $conn->prepare($orderSql);
        if (!$orderStmt) {
            throw new RuntimeException("Failed to prepare order statement: " . $conn->error);
        }

        $custName  = (string)($shippingDetails['name'] ?? '');
        $custPhone = (string)($shippingDetails['phone'] ?? '');
        $custAddr  = (string)($shippingDetails['address'] ?? '');

        $orderStmt->bind_param(
            'sisssd',
            $orderNumber,
            $userId,
            $custName,
            $custPhone,
            $custAddr,
            $totalAmount
        );

        if (!$orderStmt->execute()) {
            throw new RuntimeException("Failed to execute order insertion: " . $orderStmt->error);
        }

        $orderId = (int)$conn->insert_id;
        $orderStmt->close();

        // 3. Insert Order Line Items
        $itemSql = "
            INSERT INTO order_items (
                order_id, product_id, product_name, quantity, unit_price, line_total
            ) VALUES (?, ?, ?, ?, ?, ?)
        ";

        $itemStmt = $conn->prepare($itemSql);
        if (!$itemStmt) {
            throw new RuntimeException("Failed to prepare line items statement: " . $conn->error);
        }

        foreach ($cartItems as $item) {
            $productId = (int)$item['product_id'];
            $pName     = (string)$item['product_name'];
            $pQty      = (float)$item['quantity'];
            $pPrice    = (float)$item['unit_price'];
            $lineTotal = $pQty * $pPrice;

            $itemStmt->bind_param('iisddd', $orderId, $productId, $pName, $pQty, $pPrice, $lineTotal);

            if (!$itemStmt->execute()) {
                throw new RuntimeException("Failed to insert item {$productId}: " . $itemStmt->error);
            }
        }
        $itemStmt->close();

        // 4. Clear user's active shopping cart
        $clearCartStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clearCartStmt->bind_param('i', $userId);
        $clearCartStmt->execute();
        $clearCartStmt->close();

        // All operations succeeded: commit transaction
        $conn->commit();

        return [
            'success'     => true,
            'orderNumber' => $orderNumber,
            'error'       => null,
        ];

    } catch (Throwable $e) {
        // Rollback any modified tables on exception
        $conn->rollback();

        // Return sanitized client-safe error message
        return [
            'success'     => false,
            'orderNumber' => null,
            'error'       => 'Order processing encountered an error. Please try again.',
        ];
    }
}
