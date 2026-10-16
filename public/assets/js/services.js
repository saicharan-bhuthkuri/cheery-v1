/**
 * KaamMitra - Services Catalog, Category Tabs & Rate Card Controller
 * Handles catalog tab switching, category quick launch, and rate card accordion drawers
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initServiceFilters();
        initRateCardToggles();
        initCategoryLauncher();
    });

    // 1. Service Category Filtering
    window.filterServicesByCategory = function(category) {
        var tabButtons = document.querySelectorAll('.category-tab-btn');
        var catalogCards = document.querySelectorAll('.catalog-card');

        tabButtons.forEach(function(btn) {
            if (btn.getAttribute('data-category') === category) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        catalogCards.forEach(function(card) {
            var cardCategory = card.getAttribute('data-category');
            if (category === 'all' || cardCategory === category) {
                card.style.display = 'flex';
                card.style.opacity = '0';
                setTimeout(function() { card.style.opacity = '1'; }, 30);
            } else {
                card.style.display = 'none';
            }
        });
    };

    function initServiceFilters() {
        var tabButtons = document.querySelectorAll('.category-tab-btn');
        if (!tabButtons.length) return;

        tabButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var cat = btn.getAttribute('data-category');
                window.filterServicesByCategory(cat);
            });
        });
    }

    // 2. Fixed ₹149 Rate Card Accordion Drawers
    function initRateCardToggles() {
        var toggleBtns = document.querySelectorAll('.rate-card-toggle-btn');
        toggleBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var drawer = btn.nextElementSibling;
                if (!drawer) return;
                var isOpen = drawer.classList.contains('open');
                if (isOpen) {
                    drawer.classList.remove('open');
                    btn.classList.remove('open');
                    btn.setAttribute('aria-expanded', 'false');
                } else {
                    drawer.classList.add('open');
                    btn.classList.add('open');
                    btn.setAttribute('aria-expanded', 'true');
                    if (window.lucide) {
                        lucide.createIcons({ root: drawer });
                    }
                }
            });
        });
    }

    // 3. Hero Quick Category App Tiles Launcher
    function initCategoryLauncher() {
        var tiles = document.querySelectorAll('.trigger-category-filter');
        if (!tiles.length) return;

        tiles.forEach(function(tile) {
            tile.addEventListener('click', function() {
                var cat = tile.getAttribute('data-cat');
                if (cat && window.filterServicesByCategory) {
                    window.filterServicesByCategory(cat);
                }

                var servicesSection = document.getElementById('services');
                if (servicesSection) {
                    servicesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }
})();
