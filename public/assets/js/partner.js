/**
 * KaamMitra - Partner Portal Navigation & Utilities
 */

document.addEventListener('DOMContentLoaded', () => {
    // Graceful fallback for any legacy partner trigger elements
    const legacyTriggers = document.querySelectorAll('.trigger-partner-modal');
    legacyTriggers.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const isInsidePartner = window.location.pathname.includes('/partner/');
            window.location.href = isInsidePartner ? 'login.php' : 'partner/login.php';
        });
    });
});
