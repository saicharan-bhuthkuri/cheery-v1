/**
 * KaamMitra - Urban Company Reference Architecture JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    initStickyHeader();
    initServiceFilters();
    initCategoryLauncher();
    initHeaderSearch();
    initFaqAccordion();
    initMobileDrawer();
});

/* ==========================================================================
   STICKY HEADER SCROLL EFFECT
   ========================================================================== */
function initStickyHeader() {
    const header = document.getElementById('siteHeader');
    if (!header) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 15) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
}

/* ==========================================================================
   SERVICE CATEGORY TABS & FILTERING
   ========================================================================== */
function filterServicesByCategory(category) {
    const tabButtons = document.querySelectorAll('.category-tab-btn');
    const catalogCards = document.querySelectorAll('.catalog-card');

    tabButtons.forEach(btn => {
        if (btn.getAttribute('data-category') === category) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    catalogCards.forEach(card => {
        const cardCategory = card.getAttribute('data-category');
        if (category === 'all' || cardCategory === category) {
            card.style.display = 'flex';
            card.style.opacity = '0';
            setTimeout(() => { card.style.opacity = '1'; }, 30);
        } else {
            card.style.display = 'none';
        }
    });
}

function initServiceFilters() {
    const tabButtons = document.querySelectorAll('.category-tab-btn');
    if (!tabButtons.length) return;

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const cat = btn.getAttribute('data-category');
            filterServicesByCategory(cat);
        });
    });
}

/* ==========================================================================
   HERO QUICK CATEGORY APP TILES LAUNCHER
   ========================================================================== */
function initCategoryLauncher() {
    const tiles = document.querySelectorAll('.trigger-category-filter');
    if (!tiles.length) return;

    tiles.forEach(tile => {
        tile.addEventListener('click', () => {
            const cat = tile.getAttribute('data-cat');
            if (cat) {
                filterServicesByCategory(cat);
            }

            const servicesSection = document.getElementById('services');
            if (servicesSection) {
                servicesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
}

/* ==========================================================================
   HEADER LIVE SEARCH
   ========================================================================== */
function initHeaderSearch() {
    const searchInput = document.getElementById('headerSearchInput');
    const catalogCards = document.querySelectorAll('.catalog-card');
    if (!searchInput) return;

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        
        if (!query) {
            filterServicesByCategory('all');
            return;
        }

        // Reset category tab to All
        document.querySelectorAll('.category-tab-btn').forEach(b => b.classList.remove('active'));
        const allBtn = document.querySelector('.category-tab-btn[data-category="all"]');
        if (allBtn) allBtn.classList.add('active');

        catalogCards.forEach(card => {
            const cardText = card.textContent.toLowerCase();
            if (cardText.includes(query)) {
                card.style.display = 'flex';
                card.style.opacity = '1';
            } else {
                card.style.display = 'none';
            }
        });

        // If user is searching, scroll smoothly to the services grid
        const servicesSection = document.getElementById('services');
        if (servicesSection && window.scrollY < 300) {
            servicesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
}

/* ==========================================================================
   FAQ ACCORDION
   ========================================================================== */
function initFaqAccordion() {
    const faqCards = document.querySelectorAll('.faq-card');

    faqCards.forEach(card => {
        const trigger = card.querySelector('.faq-trigger');
        if (!trigger) return;

        trigger.addEventListener('click', () => {
            const isCurrentlyActive = card.classList.contains('active');

            faqCards.forEach(otherCard => {
                if (otherCard !== card) otherCard.classList.remove('active');
            });

            if (isCurrentlyActive) {
                card.classList.remove('active');
            } else {
                card.classList.add('active');
            }
        });
    });
}

/* ==========================================================================
   MOBILE DRAWER NAVIGATION
   ========================================================================== */
function initMobileDrawer() {
    const hamburgerBtn = document.getElementById('mobileMenuBtn');
    const drawerOverlay = document.getElementById('mobileDrawerOverlay');
    const closeBtn = document.getElementById('closeMobileDrawerBtn');
    if (!hamburgerBtn || !drawerOverlay) return;

    function openDrawer() {
        drawerOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawerOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    hamburgerBtn.addEventListener('click', openDrawer);

    if (closeBtn) {
        closeBtn.addEventListener('click', closeDrawer);
    }

    drawerOverlay.addEventListener('click', (e) => {
        if (e.target === drawerOverlay) {
            closeDrawer();
        }
    });

    document.querySelectorAll('.mobile-nav-link').forEach(link => {
        link.addEventListener('click', () => {
            closeDrawer();
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && drawerOverlay.classList.contains('active')) {
            closeDrawer();
        }
    });
}

/* ==========================================================================
   GLOBAL TOAST HELPER
   ========================================================================== */
window.showToast = function(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const iconSvg = type === 'success' 
        ? `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>`
        : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;

    toast.innerHTML = `
        ${iconSvg}
        <span>${message}</span>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
};
