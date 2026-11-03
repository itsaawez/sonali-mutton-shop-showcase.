<?php
/**
 * ============================================================================
 * Portfolio Code Sample: Search, Category Filtering & Pagination Logic
 * Project: Sonali Mutton Shop
 * Author:  Aawez Irfan Sayyed
 * Notice:  Sanitized portfolio extract demonstrating safe query parameter
 *          handling and dynamic SQL construction using prepared statements.
 * ============================================================================
 */

declare(strict_types=1);

/**
 * Builds a safe, parameterized product catalog query based on GET filters.
 *
 * Demonstrates:
 * 1. Safe query string extraction and boundary clamping
 * 2. Type casting of filter identifiers
 * 3. Dynamic prepared statement generation with parameter binding
 * 4. Pagination math preventing negative offsets
 *
 * @param mysqli $conn
 * @param array  $requestQuery Typically $_GET
 * @return array{products: array, totalRecords: int, totalPages: int, currentPage: int}
 */
function queryFilteredProducts(mysqli $conn, array $requestQuery): array
{
    // 1. Sanitize & clamp input parameters
    $categoryId = isset($requestQuery['cat']) ? (int)$requestQuery['cat'] : 0;
    $rawSearch   = trim((string)($requestQuery['q'] ?? ''));
    $page        = max(1, (int)($requestQuery['page'] ?? 1));
    $perPage     = 12;

    // Search query constraint: limit length to prevent algorithmic complexity attacks
    if (mb_strlen($rawSearch, 'UTF-8') > 80) {
        $rawSearch = mb_substr($rawSearch, 0, 80, 'UTF-8');
    }

    // 2. Build dynamic WHERE clauses and bind parameters
    $whereConditions = ['p.is_active = 1'];
    $bindTypes = '';
    $bindParams = [];

    if ($categoryId > 0) {
        $whereConditions[] = 'p.category_id = ?';
        $bindTypes .= 'i';
        $bindParams[] = $categoryId;
    }

    if ($rawSearch !== '') {
        // Search across English name, Marathi name, and description
        $whereConditions[] = '(p.name LIKE ? OR p.name_mr LIKE ? OR p.description LIKE ?)';
        $searchWildcard = '%' . $rawSearch . '%';
        $bindTypes .= 'sss';
        $bindParams[] = $searchWildcard;
        $bindParams[] = $searchWildcard;
        $bindParams[] = $searchWildcard;
    }

    $whereSql = implode(' AND ', $whereConditions);

    // 3. Count total matching rows for pagination
    $countSql = "SELECT COUNT(*) as total_count FROM products p WHERE $whereSql";
    $countStmt = $conn->prepare($countSql);

    if (!empty($bindParams)) {
        $countStmt->bind_param($bindTypes, ...$bindParams);
    }

    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRecords = (int)($countResult->fetch_assoc()['total_count'] ?? 0);
    $countStmt->close();

    $totalPages = (int)ceil($totalRecords / $perPage);
    if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    // 4. Fetch the paginated dataset
    $dataSql = "
        SELECT p.id, p.name, p.name_mr, p.price, p.unit, p.image, p.is_available,
               c.name AS cat_name, c.name_mr AS cat_name_mr
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE $whereSql
        ORDER BY p.sort_order ASC, p.id DESC
        LIMIT ? OFFSET ?
    ";

    $dataStmt = $conn->prepare($dataSql);

    // Append limit and offset to bound parameters
    $dataBindTypes = $bindTypes . 'ii';
    $dataBindParams = array_merge($bindParams, [$perPage, $offset]);

    $dataStmt->bind_param($dataBindTypes, ...$dataBindParams);
    $dataStmt->execute();
    $resultSet = $dataStmt->get_result();

    $products = [];
    while ($row = $resultSet->fetch_assoc()) {
        $products[] = $row;
    }
    $dataStmt->close();

    return [
        'products'     => $products,
        'totalRecords' => $totalRecords,
        'totalPages'   => $totalPages,
        'currentPage'  => $page,
    ];
}
