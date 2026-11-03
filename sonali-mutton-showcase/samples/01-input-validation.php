<?php
/**
 * ============================================================================
 * Portfolio Code Sample: Input Validation & Request Sanitization
 * Project: Sonali Mutton Shop
 * Author:  Aawez Irfan Sayyed
 * Notice:  Sanitized portfolio extract demonstrating defensive coding standards.
 * ============================================================================
 */

declare(strict_types=1);

/**
 * Validates and sanitizes a customer inquiry or review submission.
 *
 * Demonstrates:
 * 1. Safe HTTP method validation
 * 2. Trimming and UTF-8 string length checks
 * 3. Email and phone number regex validation
 * 4. Structured error collection without leaking internal system state
 *
 * @param array $input Raw $_POST data
 * @return array{isValid: bool, cleanData: array, errors: array}
 */
function validateCustomerSubmission(array $input): array
{
    $errors = [];
    $cleanData = [];

    // 1. Full Name Validation
    $rawName = trim((string)($input['name'] ?? ''));
    if ($rawName === '') {
        $errors['name'] = 'Full name is required.';
    } elseif (mb_strlen($rawName, 'UTF-8') < 2 || mb_strlen($rawName, 'UTF-8') > 80) {
        $errors['name'] = 'Name must be between 2 and 80 characters.';
    } else {
        // Strip out control characters and normalize spaces
        $cleanData['name'] = preg_replace('/\s+/', ' ', $rawName);
    }

    // 2. Email Address Validation
    $rawEmail = trim((string)($input['email'] ?? ''));
    if ($rawEmail === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please provide a valid email address.';
    } elseif (strlen($rawEmail) > 120) {
        $errors['email'] = 'Email address exceeds maximum permitted length.';
    } else {
        $cleanData['email'] = strtolower($rawEmail);
    }

    // 3. Indian Phone / Mobile Number Validation (e.g. +91 9876543210 or 9876543210)
    $rawPhone = trim((string)($input['phone'] ?? ''));
    if ($rawPhone !== '') {
        $sanitizedPhone = preg_replace('/[^0-9+]/', '', $rawPhone);
        if (!preg_match('/^(\+91|91|0)?[6-9]\d{9}$/', $sanitizedPhone)) {
            $errors['phone'] = 'Please enter a valid 10-digit mobile number.';
        } else {
            $cleanData['phone'] = $sanitizedPhone;
        }
    } else {
        $cleanData['phone'] = null;
    }

    // 4. Rating Validation (Integer range 1 to 5)
    $rawRating = $input['rating'] ?? 5;
    $rating = filter_var($rawRating, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 5]
    ]);
    if ($rating === false) {
        $errors['rating'] = 'Rating must be an integer between 1 and 5.';
    } else {
        $cleanData['rating'] = $rating;
    }

    // 5. Customer Feedback / Message Validation
    $rawMessage = trim((string)($input['message'] ?? ''));
    if ($rawMessage === '') {
        $errors['message'] = 'Message body cannot be empty.';
    } elseif (mb_strlen($rawMessage, 'UTF-8') > 1000) {
        $errors['message'] = 'Message cannot exceed 1000 characters.';
    } else {
        $cleanData['message'] = $rawMessage;
    }

    return [
        'isValid'   => empty($errors),
        'cleanData' => $cleanData,
        'errors'    => $errors,
    ];
}

// ── Usage Demonstration ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = validateCustomerSubmission($_POST);

    if ($result['isValid']) {
        $valid = $result['cleanData'];
        // Safe for database insertion via prepared statements
        // Example: $stmt->execute([$valid['name'], $valid['email'], $valid['phone'], ...]);
        $successMessage = "Thank you, " . htmlspecialchars($valid['name'], ENT_QUOTES, 'UTF-8') . "! Your message has been received.";
    } else {
        // Collect errors to present back to the user interface safely
        $errorList = $result['errors'];
    }
}
