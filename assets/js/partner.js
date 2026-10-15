/**
 * KaamMitra - Partner Portal Navigation & Utilities
 */

document.addEventListener('DOMContentLoaded', () => {
    // Graceful fallback for any legacy partner trigger elements
    const legacyTriggers = document.querySelectorAll('.trigger-partner-modal');
    legacyTriggers.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = 'partner-login.php';
        });
    });
});
