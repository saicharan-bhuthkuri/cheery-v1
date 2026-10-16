/**
 * KaamMitra - Customer Booking & Navigation Handler
 * Connects service actions to the customer booking portal
 */

document.addEventListener('DOMContentLoaded', () => {
    // Intercept any legacy booking modal triggers and redirect to customer/book.php
    const triggerButtons = document.querySelectorAll('.trigger-booking-modal');
    triggerButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            // If it's already an anchor tag with href, allow standard navigation
            if (btn.tagName === 'A' && btn.getAttribute('href')) {
                return;
            }
            e.preventDefault();
            const serviceCat = btn.getAttribute('data-service-cat') || 'plumbing';
            const subName = btn.getAttribute('data-sub-name') || '';
            const subPrice = btn.getAttribute('data-sub-price') || '';
            window.openBookingModal(serviceCat, subName, subPrice);
        });
    });
});

/**
 * Global Booking Navigation
 * Directs user to the authenticated customer booking portal
 */
window.openBookingModal = function(serviceCat = 'plumbing', preselectedSub = '', preselectedPrice = '') {
    const params = new URLSearchParams();
    if (serviceCat) params.set('service', serviceCat);
    if (preselectedSub) params.set('sub', preselectedSub);
    if (preselectedPrice) params.set('price', preselectedPrice);

    const isInsideCustomer = window.location.pathname.includes('/customer/');
    const basePath = isInsideCustomer ? 'book.php' : 'customer/book.php';
    const targetUrl = basePath + '?' + params.toString();
    window.location.href = targetUrl;
};
