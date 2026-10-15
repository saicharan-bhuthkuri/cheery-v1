/**
 * KaamMitra - Partner Registration Flow & Modal Manager
 */

document.addEventListener('DOMContentLoaded', () => {
    initPartnerModal();
});

function initPartnerModal() {
    const modalOverlay = document.getElementById('partnerModalOverlay');
    const closeBtn = document.getElementById('closePartnerModalBtn');
    const triggerButtons = document.querySelectorAll('.trigger-partner-modal');
    const partnerForm = document.getElementById('partnerRegisterForm');
    const partnerDoneBtn = document.getElementById('btnPartnerDone');

    // Open triggers
    triggerButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openPartnerModal();
        });
    });

    // Close triggers
    if (closeBtn) closeBtn.addEventListener('click', closePartnerModal);
    if (modalOverlay) {
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closePartnerModal();
        });
    }

    // Submit handler
    if (partnerForm) {
        partnerForm.addEventListener('submit', handlePartnerSubmit);
    }

    if (partnerDoneBtn) {
        partnerDoneBtn.addEventListener('click', () => {
            closePartnerModal();
            resetPartnerModal();
        });
    }
}

function openPartnerModal() {
    const modalOverlay = document.getElementById('partnerModalOverlay');
    if (!modalOverlay) return;
    modalOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePartnerModal() {
    const modalOverlay = document.getElementById('partnerModalOverlay');
    if (modalOverlay) {
        modalOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

async function handlePartnerSubmit(e) {
    e.preventDefault();

    const nameInput = document.getElementById('partnerNameInput');
    const phoneInput = document.getElementById('partnerPhoneInput');
    const tradeSelect = document.getElementById('partnerTradeSelect');
    const expSelect = document.getElementById('partnerExpSelect');
    const citySelect = document.getElementById('partnerCitySelect');
    const aadhaarInput = document.getElementById('partnerAadhaarInput');
    const submitBtn = document.getElementById('btnSubmitPartner');

    const name = nameInput.value.trim();
    const phone = phoneInput.value.trim().replace(/\D/g, '');
    const trade = tradeSelect.value;
    const exp = expSelect.value;
    const city = citySelect.value;
    const aadhaar = aadhaarInput ? aadhaarInput.value.trim() : '';

    if (!name || name.length < 2) {
        window.showToast('Please enter your full legal name', 'error');
        nameInput.focus();
        return;
    }

    if (phone.length !== 10 || !/^[6-9]/.test(phone)) {
        window.showToast('Please enter a valid 10-digit mobile number', 'error');
        phoneInput.focus();
        return;
    }

    if (!trade) {
        window.showToast('Please select your primary skill or trade', 'error');
        tradeSelect.focus();
        return;
    }

    if (!city) {
        window.showToast('Please select your preferred working city', 'error');
        citySelect.focus();
        return;
    }

    const payload = {
        partner_name: name,
        phone: phone,
        trade: trade,
        experience: exp,
        city: city,
        aadhaar: aadhaar
    };

    const originalBtnHtml = submitBtn.innerHTML;
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
        Registering Partner...
    `;

    try {
        const response = await fetch('api/submit-partner.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            document.getElementById('confPartnerId').textContent = data.partner_id;
            document.getElementById('confPartnerName').textContent = data.partner_details.name;
            document.getElementById('confPartnerTrade').textContent = data.partner_details.trade;
            document.getElementById('confPartnerCity').textContent = data.partner_details.city;

            document.getElementById('partnerFormSection').style.display = 'none';
            document.getElementById('partnerConfirmationSection').style.display = 'block';

            window.showToast('Registration successful! Welcome to KaamMitra family.', 'success');
        } else {
            window.showToast(data.message || 'Registration failed. Please check inputs.', 'error');
        }
    } catch (err) {
        window.showToast('Network error while submitting application. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnHtml;
    }
}

function resetPartnerModal() {
    document.getElementById('partnerRegisterForm').reset();
    document.getElementById('partnerFormSection').style.display = 'block';
    document.getElementById('partnerConfirmationSection').style.display = 'none';
}
