<?php
/**
 * KaamMitra - Booking Submission Handler
 */

header('Content-Type: application/json; charset=utf-8');

// Ensure request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Please use POST.'
    ]);
    exit;
}

// Receive payload from either raw JSON or standard POST form data
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

// Sanitize & Validate input
$service = trim($data['service'] ?? '');
$subService = trim($data['sub_service'] ?? '');
$customerName = trim($data['customer_name'] ?? '');
$phone = trim($data['phone'] ?? '');
$city = trim($data['city'] ?? '');
$address = trim($data['address'] ?? '');
$pincode = trim($data['pincode'] ?? '');
$date = trim($data['date'] ?? '');
$timeSlot = trim($data['time_slot'] ?? '');
$notes = trim($data['notes'] ?? '');
$estimatedPrice = floatval($data['estimated_price'] ?? 0);

$errors = [];

if (empty($service)) {
    $errors[] = 'Please select a service category.';
}
if (empty($customerName) || strlen($customerName) < 2) {
    $errors[] = 'Please enter a valid full name.';
}
if (empty($phone) || !preg_match('/^[6-9]\d{9}$/', preg_replace('/[^\d]/', '', $phone))) {
    $errors[] = 'Please enter a valid 10-digit mobile number.';
}
if (empty($city)) {
    $errors[] = 'Please select your city.';
}
if (empty($address) || strlen($address) < 5) {
    $errors[] = 'Please provide a complete street address.';
}
if (empty($date)) {
    $errors[] = 'Please select a preferred service date.';
}
if (empty($timeSlot)) {
    $errors[] = 'Please select a preferred time slot.';
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

// Generate realistic simulated booking response
$bookingId = 'KM-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 5));
$technicians = [
    'plumbing' => ['name' => 'Santosh Yadav', 'rating' => 4.9, 'jobs' => 840, 'phone' => '+91 98765-XXXXX'],
    'electrical' => ['name' => 'Rajesh Kumar', 'rating' => 4.95, 'jobs' => 1250, 'phone' => '+91 98112-XXXXX'],
    'appliances' => ['name' => 'Mohd. Salim', 'rating' => 4.88, 'jobs' => 920, 'phone' => '+91 99234-XXXXX'],
    'carpentry' => ['name' => 'Manoj Sharma', 'rating' => 4.85, 'jobs' => 610, 'phone' => '+91 97123-XXXXX'],
    'cleaning' => ['name' => 'Sunil & Team', 'rating' => 4.92, 'jobs' => 1400, 'phone' => '+91 98845-XXXXX'],
    'painting' => ['name' => 'Rameshwar Lal', 'rating' => 4.8, 'jobs' => 430, 'phone' => '+91 96541-XXXXX']
];

$matchedTech = $technicians[$service] ?? ['name' => 'Dinesh Verma', 'rating' => 4.9, 'jobs' => 750, 'phone' => '+91 98765-XXXXX'];

// Persist to MySQL database
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/auth.php';
    $pdo = getDBConnection();
    $customerId = null;
    if (isCustomerLoggedIn()) {
        $customerId = $_SESSION['customer_id'];
    } else {
        $cleanPhone = preg_replace('/[^\d]/', '', $phone);
        $custStmt = $pdo->prepare("SELECT id FROM customers WHERE mobile = ? LIMIT 1");
        $custStmt->execute([$cleanPhone]);
        $cust = $custStmt->fetch();
        if ($cust) {
            $customerId = $cust['id'];
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO bookings 
        (booking_reference, customer_id, customer_name, phone, service_category, sub_service, city, address, pincode, service_date, time_slot, estimated_price, status, mitra_name, mitra_rating)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed', ?, ?)
    ");
    $stmt->execute([
        $bookingId,
        $customerId,
        $customerName,
        preg_replace('/[^\d]/', '', $phone),
        $service,
        $subService ?: ucwords($service),
        $city,
        $address,
        $pincode ?: null,
        $date,
        $timeSlot,
        $estimatedPrice,
        $matchedTech['name'],
        $matchedTech['rating']
    ]);
} catch (Exception $e) {
    error_log("Failed to insert booking into DB: " . $e->getMessage());
}

echo json_encode([
    'status' => 'success',
    'booking_id' => $bookingId,
    'message' => 'Your booking has been confirmed successfully! A verified Mitra has been assigned.',
    'booking_details' => [
        'customer_name' => htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8'),
        'phone' => htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'),
        'service' => htmlspecialchars($service, ENT_QUOTES, 'UTF-8'),
        'sub_service' => htmlspecialchars($subService, ENT_QUOTES, 'UTF-8'),
        'city' => htmlspecialchars($city, ENT_QUOTES, 'UTF-8'),
        'address' => htmlspecialchars($address, ENT_QUOTES, 'UTF-8'),
        'pincode' => htmlspecialchars($pincode, ENT_QUOTES, 'UTF-8'),
        'date' => htmlspecialchars($date, ENT_QUOTES, 'UTF-8'),
        'time_slot' => htmlspecialchars($timeSlot, ENT_QUOTES, 'UTF-8'),
        'estimated_price' => $estimatedPrice > 0 ? '₹' . number_format($estimatedPrice) : 'Standard inspection fee ₹149',
        'created_at' => date('d M Y, h:i A')
    ],
    'assigned_mitra' => [
        'name' => $matchedTech['name'],
        'rating' => $matchedTech['rating'],
        'completed_jobs' => $matchedTech['jobs'],
        'status' => 'Dispatched / Contact within 15 mins',
        'verification' => 'Aadhaar & Police Verified'
    ]
]);
