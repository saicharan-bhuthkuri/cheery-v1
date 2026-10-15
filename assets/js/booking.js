/**
 * KaamMitra - Booking Flow & Modal Manager
 */

document.addEventListener('DOMContentLoaded', () => {
    initBookingModal();
});

let currentBookingStep = 1;

function initBookingModal() {
    const modalOverlay = document.getElementById('bookingModalOverlay');
    const closeBtn = document.getElementById('closeBookingModalBtn');
    const triggerButtons = document.querySelectorAll('.trigger-booking-modal');
    
    // Step navigation buttons
    const nextToStep2Btn = document.getElementById('btnNextToStep2');
    const backToStep1Btn = document.getElementById('btnBackToStep1');
    const nextToStep3Btn = document.getElementById('btnNextToStep3');
    const backToStep2Btn = document.getElementById('btnBackToStep2');
    const submitBookingBtn = document.getElementById('btnSubmitBooking');
    const bookingDoneBtn = document.getElementById('btnBookingDone');

    // Form inputs
    const serviceSelect = document.getElementById('bookServiceSelect');
    const subServiceSelect = document.getElementById('bookSubServiceSelect');
    const dateInput = document.getElementById('bookDateInput');

    // Set minimum date to today
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
        dateInput.value = today;
    }

    // Populate subservices when category changes
    if (serviceSelect && subServiceSelect) {
        serviceSelect.addEventListener('change', () => {
            populateSubservices(serviceSelect.value);
            updatePriceSummary();
        });

        subServiceSelect.addEventListener('change', () => {
            updatePriceSummary();
        });
    }

    // Open modal triggers
    triggerButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const serviceCat = btn.getAttribute('data-service-cat') || 'plumbing';
            const subName = btn.getAttribute('data-sub-name') || '';
            const subPrice = btn.getAttribute('data-sub-price') || '';
            window.openBookingModal(serviceCat, subName, subPrice);
        });
    });

    // Close modal
    if (closeBtn) closeBtn.addEventListener('click', closeBookingModal);
    if (modalOverlay) {
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeBookingModal();
        });
    }

    // Wizard Step Navigation
    if (nextToStep2Btn) {
        nextToStep2Btn.addEventListener('click', () => {
            if (!serviceSelect.value) {
                window.showToast('Please select a service category', 'error');
                return;
            }
            goToStep(2);
        });
    }

    if (backToStep1Btn) {
        backToStep1Btn.addEventListener('click', () => goToStep(1));
    }

    if (nextToStep3Btn) {
        nextToStep3Btn.addEventListener('click', () => {
            const date = document.getElementById('bookDateInput').value;
            const time = document.getElementById('bookTimeSelect').value;
            const city = document.getElementById('bookCitySelect').value;
            const address = document.getElementById('bookAddressInput').value.trim();

            if (!date) {
                window.showToast('Please select a service date', 'error');
                return;
            }
            if (!time) {
                window.showToast('Please select a preferred time slot', 'error');
                return;
            }
            if (!city) {
                window.showToast('Please select your city', 'error');
                return;
            }
            if (!address || address.length < 5) {
                window.showToast('Please enter your complete address (at least 5 characters)', 'error');
                return;
            }

            // Update Review Summary on Step 3
            updateStep3Review();
            goToStep(3);
        });
    }

    if (backToStep2Btn) {
        backToStep2Btn.addEventListener('click', () => goToStep(2));
    }

    // Final Booking Submit
    if (submitBookingBtn) {
        submitBookingBtn.addEventListener('click', handleBookingSubmit);
    }

    if (bookingDoneBtn) {
        bookingDoneBtn.addEventListener('click', () => {
            closeBookingModal();
            resetBookingForm();
        });
    }
}

window.openBookingModal = function(serviceCat = 'plumbing', preselectedSub = '', preselectedPrice = '') {
    const modalOverlay = document.getElementById('bookingModalOverlay');
    if (!modalOverlay) return;

    modalOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';

    const serviceSelect = document.getElementById('bookServiceSelect');
    if (serviceSelect) {
        serviceSelect.value = serviceCat;
        populateSubservices(serviceCat, preselectedSub);
    }

    updatePriceSummary();
    goToStep(1);
};

function closeBookingModal() {
    const modalOverlay = document.getElementById('bookingModalOverlay');
    if (modalOverlay) {
        modalOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function populateSubservices(categoryKey, preselectedName = '') {
    const subServiceSelect = document.getElementById('bookSubServiceSelect');
    if (!subServiceSelect) return;

    subServiceSelect.innerHTML = '';
    const servicesData = window.KM_SERVICES || [];
    const found = servicesData.find(s => s.category === categoryKey) || servicesData[0];

    if (found && found.sub_services) {
        found.sub_services.forEach(sub => {
            const opt = document.createElement('option');
            opt.value = sub.name;
            opt.textContent = `${sub.name} (₹${sub.price})`;
            opt.setAttribute('data-price', sub.price);
            opt.setAttribute('data-time', sub.time);
            if (preselectedName && sub.name.toLowerCase() === preselectedName.toLowerCase()) {
                opt.selected = true;
            }
            subServiceSelect.appendChild(opt);
        });
    }
}

function updatePriceSummary() {
    const subServiceSelect = document.getElementById('bookSubServiceSelect');
    const priceDisplay = document.getElementById('modalPriceValue');
    if (!subServiceSelect || !priceDisplay) return;

    const selectedOption = subServiceSelect.options[subServiceSelect.selectedIndex];
    if (selectedOption) {
        const price = selectedOption.getAttribute('data-price') || '199';
        priceDisplay.textContent = `₹${price}`;
    }
}

function updateStep3Review() {
    const serviceSelect = document.getElementById('bookServiceSelect');
    const subServiceSelect = document.getElementById('bookSubServiceSelect');
    const dateInput = document.getElementById('bookDateInput');
    const timeSelect = document.getElementById('bookTimeSelect');
    const addressInput = document.getElementById('bookAddressInput');
    const citySelect = document.getElementById('bookCitySelect');

    const revService = document.getElementById('reviewServiceSummary');
    const revSchedule = document.getElementById('reviewScheduleSummary');
    const revLocation = document.getElementById('reviewLocationSummary');
    const revTotal = document.getElementById('reviewTotalEstimate');

    const selectedSub = subServiceSelect.options[subServiceSelect.selectedIndex];
    const price = selectedSub ? selectedSub.getAttribute('data-price') : '199';

    if (revService) revService.textContent = `${serviceSelect.options[serviceSelect.selectedIndex].text} - ${subServiceSelect.value}`;
    if (revSchedule) revSchedule.textContent = `${dateInput.value} • ${timeSelect.value}`;
    if (revLocation) revLocation.textContent = `${addressInput.value}, ${citySelect.value}`;
    if (revTotal) revTotal.textContent = `₹${price}`;
}

function goToStep(step) {
    currentBookingStep = step;
    
    // Update step panels
    document.querySelectorAll('.modal-step-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    const targetPanel = document.getElementById(`modalStep${step}`);
    if (targetPanel) targetPanel.classList.add('active');

    // Update stepper circles
    for (let i = 1; i <= 3; i++) {
        const node = document.getElementById(`stepperNode${i}`);
        if (!node) continue;
        node.classList.remove('active', 'completed');
        if (i === step) {
            node.classList.add('active');
        } else if (i < step) {
            node.classList.add('completed');
        }
    }

    if (window.lucide) {
        lucide.createIcons();
    }
}

async function handleBookingSubmit() {
    const submitBtn = document.getElementById('btnSubmitBooking');
    const nameInput = document.getElementById('bookCustomerName');
    const phoneInput = document.getElementById('bookCustomerPhone');
    const notesInput = document.getElementById('bookCustomerNotes');

    const name = nameInput.value.trim();
    const phone = phoneInput.value.trim();

    if (!name || name.length < 2) {
        window.showToast('Please enter your full name', 'error');
        nameInput.focus();
        return;
    }

    const cleanPhone = phone.replace(/\D/g, '');
    if (cleanPhone.length !== 10 || !/^[6-9]/.test(cleanPhone)) {
        window.showToast('Please enter a valid 10-digit Indian mobile number', 'error');
        phoneInput.focus();
        return;
    }

    const subOption = document.getElementById('bookSubServiceSelect').options[document.getElementById('bookSubServiceSelect').selectedIndex];
    const price = subOption ? parseFloat(subOption.getAttribute('data-price')) : 199;

    const payload = {
        service: document.getElementById('bookServiceSelect').value,
        sub_service: document.getElementById('bookSubServiceSelect').value,
        customer_name: name,
        phone: cleanPhone,
        city: document.getElementById('bookCitySelect').value,
        address: document.getElementById('bookAddressInput').value.trim(),
        pincode: document.getElementById('bookPincodeInput').value.trim(),
        date: document.getElementById('bookDateInput').value,
        time_slot: document.getElementById('bookTimeSelect').value,
        notes: notesInput ? notesInput.value.trim() : '',
        estimated_price: price
    };

    // Set loading state
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <svg class="spin-anim" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="2" x2="12" y2="6"></line>
            <line x1="12" y1="18" x2="12" y2="22"></line>
            <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
            <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
            <line x1="2" y1="12" x2="6" y2="12"></line>
            <line x1="18" y1="12" x2="22" y2="12"></line>
            <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
            <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
        </svg>
        Assigning Verified Mitra...
    `;

    try {
        const response = await fetch('api/submit-booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            // Render confirmation screen
            document.getElementById('confBookingId').textContent = data.booking_id;
            document.getElementById('confCustomerName').textContent = data.booking_details.customer_name;
            document.getElementById('confMitraName').textContent = data.assigned_mitra.name;
            document.getElementById('confMitraRating').textContent = `${data.assigned_mitra.rating} ★ (${data.assigned_mitra.completed_jobs} jobs)`;
            document.getElementById('confSlot').textContent = `${data.booking_details.date} at ${data.booking_details.time_slot}`;
            document.getElementById('confAmount').textContent = data.booking_details.estimated_price;

            // Hide stepper header and step panels, show confirmation
            document.querySelector('.stepper-header').style.display = 'none';
            document.querySelectorAll('.modal-step-panel').forEach(p => p.classList.remove('active'));
            document.getElementById('modalConfirmationPanel').classList.add('active');

            window.showToast('Booking Confirmed! Assigned verified Mitra.', 'success');
        } else {
            window.showToast(data.message || 'Something went wrong. Please check your details.', 'error');
        }
    } catch (err) {
        window.showToast('Network error while booking. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
}

function resetBookingForm() {
    const stepperHeader = document.querySelector('.stepper-header');
    if (stepperHeader) stepperHeader.style.display = 'flex';
    document.querySelectorAll('.modal-step-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('modalStep1').classList.add('active');
    goToStep(1);
}
