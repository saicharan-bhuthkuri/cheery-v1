<?php
/**
 * KaamMitra - Partner Registration Handler
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Please use POST.'
    ]);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

$name = trim($data['partner_name'] ?? '');
$phone = trim($data['phone'] ?? '');
$trade = trim($data['trade'] ?? '');
$experience = trim($data['experience'] ?? '');
$city = trim($data['city'] ?? '');
$aadhaar = trim($data['aadhaar'] ?? '');

$errors = [];

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Please enter your full legal name.';
}
if (empty($phone) || !preg_match('/^[6-9]\d{9}$/', preg_replace('/[^\d]/', '', $phone))) {
    $errors[] = 'Please enter a valid 10-digit mobile number for OTP.';
}
if (empty($trade)) {
    $errors[] = 'Please select your primary skill or trade.';
}
if (empty($experience)) {
    $errors[] = 'Please select your years of experience.';
}
if (empty($city)) {
    $errors[] = 'Please select your operating city.';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'errors' => $errors,
        'message' => implode(' ', $errors)
    ]);
    exit;
}

$partnerId = 'MITRA-' . rand(10000, 99999);

echo json_encode([
    'status' => 'success',
    'partner_id' => $partnerId,
    'message' => 'Congratulations! Your partner registration application has been received.',
    'partner_details' => [
        'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        'trade' => htmlspecialchars($trade, ENT_QUOTES, 'UTF-8'),
        'city' => htmlspecialchars($city, ENT_QUOTES, 'UTF-8'),
        'experience' => htmlspecialchars($experience, ENT_QUOTES, 'UTF-8'),
        'phone' => htmlspecialchars($phone, ENT_QUOTES, 'UTF-8')
    ],
    'next_steps' => [
        'Step 1: Document & Aadhaar verification within 4 business hours',
        'Step 2: Collect your free KaamMitra toolkit and uniform from nearest hub',
        'Step 3: Start receiving customer orders with 0% commission!'
    ]
]);
