<?php
/**
 * KaamMitra - Dedicated Customer Booking Portal
 * Authenticated booking workflow integrated with MySQL persistence
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/data/services.php';

// Prepare redirect URL preserving any service parameters
$currentUri = 'book.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $currentUri .= '?' . $_SERVER['QUERY_STRING'];
}

// Require customer to be logged in
$customer = requireCustomerAuth($currentUri);

$selectedCat = trim($_GET['service'] ?? 'plumbing');
$selectedSub = trim($_GET['sub'] ?? '');
$selectedPrice = trim($_GET['price'] ?? '');

$bookingSuccess = false;
$confirmedBooking = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security validation failed (CSRF). Please try again.';
    } else {
        $cat = trim($_POST['service_category'] ?? '');
        $sub = trim($_POST['sub_service'] ?? '');
        $date = trim($_POST['service_date'] ?? '');
        $slot = trim($_POST['time_slot'] ?? '');
        $city = trim($_POST['city'] ?? $customer['city'] ?? 'Mumbai');
        $address = trim($_POST['address'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $price = floatval($_POST['estimated_price'] ?? 199);

        if (empty($cat) || empty($date) || empty($slot) || empty($address)) {
            $error = 'Please fill in all required booking details (service, date, slot, address).';
        } else {
            // Assign verified Mitra based on category
            $technicians = [
                'plumbing' => ['name' => 'Santosh Yadav', 'rating' => 4.90],
                'electrical' => ['name' => 'Rajesh Kumar', 'rating' => 4.95],
                'appliances' => ['name' => 'Mohd. Salim', 'rating' => 4.88],
                'carpentry' => ['name' => 'Manoj Sharma', 'rating' => 4.85],
                'cleaning' => ['name' => 'Sunil & Team', 'rating' => 4.92],
                'painting' => ['name' => 'Rameshwar Lal', 'rating' => 4.80]
            ];
            $mitra = $technicians[$cat] ?? ['name' => 'Dinesh Verma', 'rating' => 4.90];

            $refId = 'KM-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 5));

            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("
                    INSERT INTO bookings 
                    (booking_reference, customer_id, customer_name, phone, service_category, sub_service, city, address, pincode, service_date, time_slot, estimated_price, status, mitra_name, mitra_rating)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed', ?, ?)
                ");
                $stmt->execute([
                    $refId,
                    $customer['id'],
                    $customer['full_name'],
                    $customer['mobile'],
                    $cat,
                    $sub ?: ucwords($cat . ' Service'),
                    $city,
                    $address,
                    $pincode ?: null,
                    $date,
                    $slot,
                    $price,
                    $mitra['name'],
                    $mitra['rating']
                ]);

                // Also update customer address/city in profile if they weren't set before
                if (empty($customer['address_line']) && !empty($address)) {
                    $upStmt = $pdo->prepare("UPDATE customers SET address_line = ?, city = ?, pincode = ? WHERE id = ?");
                    $upStmt->execute([$address, $city, $pincode ?: null, $customer['id']]);
                }

                $bookingSuccess = true;
                $confirmedBooking = [
                    'reference' => $refId,
                    'service' => $sub ?: ucwords($cat . ' Service'),
                    'date' => $date,
                    'slot' => $slot,
                    'price' => $price,
                    'address' => $address . ', ' . $city . ($pincode ? ' - ' . $pincode : ''),
                    'mitra_name' => $mitra['name'],
                    'mitra_rating' => $mitra['rating']
                ];
            } catch (Exception $e) {
                error_log("Booking error: " . $e->getMessage());
                $error = 'An error occurred while saving your booking. Please try again.';
            }
        }
    }
}

$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Service - <?= htmlspecialchars(APP_NAME); ?></title>
    
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/lucide.min.js"></script>
    <style>
        body {
            background-color: #F8FAFC;
            min-height: 100vh;
        }
        .book-header {
            background: #0A1325;
            color: #FFFFFF;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .book-container {
            max-width: 820px;
            margin: 2rem auto;
            padding: 0 1.25rem;
        }
        .book-card {
            background: #FFFFFF;
            border-radius: 24px;
            padding: 2.25rem;
            box-shadow: 0 8px 30px rgba(10, 19, 37, 0.06);
            border: 1px solid var(--border-subtle);
        }
        @media (max-width: 600px) {
            .book-card {
                padding: 1.5rem 1.25rem;
            }
        }
        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }
        @media (max-width: 600px) {
            .form-grid-2 {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
        .field-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--navy-900);
            margin-bottom: 0.4rem;
        }
        .price-badge-box {
            background: linear-gradient(135deg, #FFF5EE, #FFEADB);
            border: 1.5px solid var(--orange-200);
            border-radius: 16px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        .conf-card {
            text-align: center;
            padding: 2rem 1rem;
        }
    </style>
</head>
<body>

<header class="book-header">
    <div style="max-width: 820px; margin: 0 auto; padding: 0 1.25rem; display: flex; align-items: center; justify-content: space-between;">
        <a href="index.php" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none;">
            <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #FF6600, #E65100); display: flex; align-items: center; justify-content: center; color: #FFFFFF;">
                <i data-lucide="wrench" style="width: 18px; height: 18px;"></i>
            </div>
            <span style="font-size: 1.3rem; font-weight: 800; color: #FFFFFF;">Kaam<span style="color: #FF6600;">Mitra</span></span>
        </a>

        <div style="display: flex; align-items: center; gap: 1rem;">
            <span class="user-greeting-tag" style="font-size: 0.85rem; color: rgba(255,255,255,0.7);">
                Logged in as <strong><?= htmlspecialchars(explode(' ', $customer['full_name'])[0]); ?></strong>
            </span>
            <a href="dashboard.php" class="btn btn-outline btn-sm" style="color: #FFFFFF; border-color: rgba(255,255,255,0.3); border-radius: 8px;">
                <i data-lucide="layout-dashboard" style="width: 15px; height: 15px;"></i>
                <span>My Dashboard</span>
            </a>
        </div>
    </div>
</header>

<main class="book-container">
    <?php if ($bookingSuccess && $confirmedBooking): ?>
        <!-- SUCCESS CONFIRMATION VIEW -->
        <div class="book-card conf-card">
            <div style="width: 72px; height: 72px; border-radius: 50%; background: #ECFDF5; color: #10B981; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; box-shadow: 0 4px 14px rgba(16,185,129,0.2);">
                <i data-lucide="check-circle-2" style="width: 40px; height: 40px;"></i>
            </div>
            
            <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--navy-900); margin: 0 0 0.35rem;">
                Booking Confirmed!
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0 0 1.5rem;">
                A verified KaamMitra professional has been assigned to your service request.
            </p>

            <div style="display: inline-block; background: var(--orange-50); border: 1.5px dashed var(--orange-300); color: var(--orange-600); padding: 0.5rem 1.25rem; border-radius: 12px; font-weight: 800; font-size: 1.1rem; margin-bottom: 2rem;">
                Booking ID: <?= htmlspecialchars($confirmedBooking['reference']); ?>
            </div>

            <!-- Booking Summary Box -->
            <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 16px; padding: 1.5rem; text-align: left; max-width: 520px; margin: 0 auto 2rem; font-size: 0.9rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; border-bottom: 1px solid var(--gray-200); padding-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Service:</span>
                    <strong style="color: var(--navy-900);"><?= htmlspecialchars($confirmedBooking['service']); ?></strong>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; border-bottom: 1px solid var(--gray-200); padding-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Scheduled Date &amp; Slot:</span>
                    <strong><?= date('d M Y', strtotime($confirmedBooking['date'])); ?> • <?= htmlspecialchars($confirmedBooking['slot']); ?></strong>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; border-bottom: 1px solid var(--gray-200); padding-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Assigned Mitra:</span>
                    <strong style="color: var(--orange-600);">
                        <?= htmlspecialchars($confirmedBooking['mitra_name']); ?> ★ <?= htmlspecialchars($confirmedBooking['mitra_rating']); ?>
                    </strong>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; border-bottom: 1px solid var(--gray-200); padding-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Service Address:</span>
                    <span style="text-align: right; max-width: 250px;"><?= htmlspecialchars($confirmedBooking['address']); ?></span>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 0.25rem;">
                    <strong style="color: var(--navy-900);">Pay After Completion:</strong>
                    <strong style="color: #059669; font-size: 1.25rem;">₹<?= number_format($confirmedBooking['price']); ?></strong>
                </div>
            </div>

            <!-- Action buttons -->
            <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                <a href="dashboard.php" class="btn btn-primary" style="padding: 0.85rem 1.75rem; border-radius: 12px; font-weight: 700;">
                    <i data-lucide="layout-dashboard" style="width: 18px; height: 18px;"></i>
                    <span>View in My Dashboard</span>
                </a>
                <a href="index.php" class="btn btn-outline" style="padding: 0.85rem 1.75rem; border-radius: 12px; font-weight: 600;">
                    <span>Back to Home</span>
                </a>
            </div>
        </div>

    <?php else: ?>
        <!-- BOOKING FORM -->
        <div class="book-card">
            <div style="margin-bottom: 1.75rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                    <i data-lucide="calendar-plus" style="color: var(--orange-500); width: 22px; height: 22px;"></i>
                    <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--navy-900); margin: 0;">Schedule a Verified Mitra</h1>
                </div>
                <p style="color: var(--text-muted); font-size: 0.92rem; margin: 0;">
                    Transparent standard visiting fee, verified background check, and zero advance payment required.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #DC2626; border-radius: 10px; padding: 0.85rem 1rem; margin-bottom: 1.5rem; font-size: 0.9rem; display: flex; align-items: center; gap: 0.6rem;">
                    <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                    <span><?= htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="book.php" method="POST" id="bookingPageForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="estimated_price" id="formPriceInput" value="<?= !empty($selectedPrice) ? htmlspecialchars($selectedPrice) : '199'; ?>">

                <!-- Service Category & Sub Service -->
                <div class="form-grid-2" style="margin-bottom: 1.25rem;">
                    <div class="form-field-group">
                        <label for="bookCategorySelect" class="field-label">Service Category</label>
                        <select id="bookCategorySelect" name="service_category" class="select-control" style="height: 48px; border-radius: 12px; font-size: 0.95rem;">
                            <?php foreach ($SERVICES_DATA as $s): ?>
                                <option value="<?= htmlspecialchars($s['category']); ?>" <?= $selectedCat === $s['category'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($s['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field-group">
                        <label for="bookSubSelect" class="field-label">Specific Repair / Job</label>
                        <select id="bookSubSelect" name="sub_service" class="select-control" style="height: 48px; border-radius: 12px; font-size: 0.95rem;">
                            <!-- Populated dynamically -->
                        </select>
                    </div>
                </div>

                <!-- Price Fee Banner -->
                <div class="price-badge-box">
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--navy-700); font-weight: 700; letter-spacing: 0.05em; display: block;">
                            Transparent Standard Fee
                        </span>
                        <strong style="font-size: 1.6rem; color: var(--navy-950);" id="displayPrice">₹199</strong>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 0.85rem; font-weight: 700; color: #059669; background: #FFFFFF; padding: 0.4rem 0.85rem; border-radius: 9999px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); display: inline-block;">
                            Zero Advance • Pay After Service
                        </span>
                    </div>
                </div>

                <!-- Date & Slot -->
                <div class="form-grid-2" style="margin-bottom: 1.25rem;">
                    <div class="form-field-group">
                        <label for="serviceDateInput" class="field-label">Preferred Date</label>
                        <input 
                            type="date" 
                            id="serviceDateInput" 
                            name="service_date" 
                            class="form-control" 
                            style="height: 48px; border-radius: 12px; font-size: 0.95rem;" 
                            min="<?= date('Y-m-d'); ?>" 
                            value="<?= date('Y-m-d', strtotime('+1 day')); ?>"
                            required
                        >
                    </div>

                    <div class="form-field-group">
                        <label for="timeSlotSelect" class="field-label">Preferred Time Slot</label>
                        <select id="timeSlotSelect" name="time_slot" class="select-control" style="height: 48px; border-radius: 12px; font-size: 0.95rem;">
                            <option value="⚡ Express (Within 30 Mins)">⚡ Express (Within 30 Mins)</option>
                            <option value="09:00 AM - 11:00 AM">09:00 AM - 11:00 AM</option>
                            <option value="11:00 AM - 01:00 PM" selected>11:00 AM - 01:00 PM</option>
                            <option value="02:00 PM - 04:00 PM">02:00 PM - 04:00 PM</option>
                            <option value="04:00 PM - 06:00 PM">04:00 PM - 06:00 PM</option>
                            <option value="06:00 PM - 08:00 PM">06:00 PM - 08:00 PM</option>
                        </select>
                    </div>
                </div>

                <!-- City & PIN Code -->
                <div class="form-grid-2" style="margin-bottom: 1.25rem;">
                    <div class="form-field-group">
                        <label for="citySelect" class="field-label">Service City</label>
                        <select id="citySelect" name="city" class="select-control" style="height: 48px; border-radius: 12px; font-size: 0.95rem;">
                            <?php foreach ($AVAILABLE_CITIES as $c): ?>
                                <option value="<?= htmlspecialchars($c); ?>" <?= ($customer['city'] ?? 'Mumbai') === $c ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($c); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field-group">
                        <label for="pincodeInput" class="field-label">PIN Code</label>
                        <input 
                            type="text" 
                            id="pincodeInput" 
                            name="pincode" 
                            class="form-control" 
                            style="height: 48px; border-radius: 12px; font-size: 0.95rem;" 
                            placeholder="e.g. 400053" 
                            maxlength="6"
                            value="<?= htmlspecialchars($customer['pincode'] ?? ''); ?>"
                        >
                    </div>
                </div>

                <!-- Street Address -->
                <div class="form-field-group" style="margin-bottom: 1.5rem;">
                    <label for="addressInput" class="field-label">Complete Service Address / Flat / Floor</label>
                    <input 
                        type="text" 
                        id="addressInput" 
                        name="address" 
                        class="form-control" 
                        style="height: 48px; border-radius: 12px; font-size: 0.95rem;" 
                        placeholder="e.g. Flat 502, Orchid Heights, Link Road" 
                        value="<?= htmlspecialchars($customer['address_line'] ?? ''); ?>"
                        required
                    >
                </div>

                <!-- Customer Details Pre-filled Readout -->
                <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.75rem; font-size: 0.88rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Booking For</span>
                        <strong style="color: var(--navy-900);"><?= htmlspecialchars($customer['full_name']); ?></strong> (+91 <?= htmlspecialchars($customer['mobile']); ?>)
                    </div>
                    <div>
                        <span style="color: #10B981; font-weight: 600; display: inline-flex; align-items: center; gap: 0.35rem;">
                            <i data-lucide="shield-check" style="width: 16px; height: 16px;"></i>
                            <span>Verified Account</span>
                        </span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBookBtn" class="btn btn-primary btn-full" style="padding: 0.95rem; font-size: 1.05rem; font-weight: 700; border-radius: 12px;">
                    <span id="bookBtnText">Confirm Doorstep Mitra Booking</span>
                    <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
                </button>
            </form>
        </div>
    <?php endif; ?>
</main>

<script>
    if (window.lucide) {
        lucide.createIcons();
    }

    const servicesData = <?= json_encode($SERVICES_DATA); ?>;
    const catSelect = document.getElementById('bookCategorySelect');
    const subSelect = document.getElementById('bookSubSelect');
    const displayPrice = document.getElementById('displayPrice');
    const formPriceInput = document.getElementById('formPriceInput');

    const preSelectedSub = <?= json_encode($selectedSub); ?>;
    const preSelectedPrice = <?= json_encode($selectedPrice); ?>;

    function populateSubServices(cat) {
        if (!subSelect) return;
        subSelect.innerHTML = '';
        const found = servicesData.find(s => s.category === cat);
        if (found && found.sub_services) {
            found.sub_services.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.name;
                opt.textContent = `${sub.name} (₹${sub.price})`;
                opt.setAttribute('data-price', sub.price);
                if (preSelectedSub && sub.name.toLowerCase() === preSelectedSub.toLowerCase()) {
                    opt.selected = true;
                }
                subSelect.appendChild(opt);
            });
        }
        updatePrice();
    }

    function updatePrice() {
        if (!subSelect || !displayPrice || !formPriceInput) return;
        const selectedOpt = subSelect.options[subSelect.selectedIndex];
        if (selectedOpt) {
            const price = selectedOpt.getAttribute('data-price') || 199;
            displayPrice.textContent = '₹' + price;
            formPriceInput.value = price;
        }
    }

    if (catSelect) {
        catSelect.addEventListener('change', () => {
            populateSubServices(catSelect.value);
        });
        populateSubServices(catSelect.value);
    }

    if (subSelect) {
        subSelect.addEventListener('change', updatePrice);
    }

    // Set form loading state on submit
    const bookForm = document.getElementById('bookingPageForm');
    const bookBtn = document.getElementById('submitBookBtn');
    const bookBtnText = document.getElementById('bookBtnText');

    if (bookForm && bookBtn) {
        bookForm.addEventListener('submit', () => {
            bookBtn.style.pointerEvents = 'none';
            bookBtn.style.opacity = '0.8';
            if (bookBtnText) bookBtnText.textContent = 'Assigning Verified Mitra...';
        });
    }
</script>
</body>
</html>
