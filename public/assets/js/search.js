/**
 * KaamMitra - Live Search & Auto-Suggestions Controller
 * Instant catalog matching, dropdown recommendations, and clear actions
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initLiveSearch();
    });

    function getServicesData() {
        if (window.KM_SERVICES && Array.isArray(window.KM_SERVICES)) {
            return window.KM_SERVICES;
        }
        var dataScript = document.getElementById('servicesDataJson');
        if (dataScript && dataScript.textContent) {
            try {
                window.KM_SERVICES = JSON.parse(dataScript.textContent);
                return window.KM_SERVICES;
            } catch (e) {
                return [];
            }
        }
        return [];
    }

    function initLiveSearch() {
        var desktopInput = document.getElementById('headerSearchInput');
        var desktopClearBtn = document.getElementById('clearSearchBtn');
        var desktopDropdown = document.getElementById('headerSearchDropdown');

        var services = getServicesData();

        function setupSearchField(input, clearBtn, dropdown) {
            if (!input || !dropdown) return;

            input.addEventListener('input', function() {
                var query = input.value.trim().toLowerCase();

                if (clearBtn) {
                    clearBtn.style.display = query.length > 0 ? 'block' : 'none';
                }

                if (query.length < 2) {
                    dropdown.style.display = 'none';
                    dropdown.innerHTML = '';
                    if (query.length === 0 && window.filterServicesByCategory) {
                        window.filterServicesByCategory('all');
                    }
                    return;
                }

                // Find matching services & sub-services
                var matches = [];

                services.forEach(function(service) {
                    if (service.title.toLowerCase().includes(query) || (service.subtitle && service.subtitle.toLowerCase().includes(query))) {
                        matches.push({
                            title: service.title,
                            category: service.category,
                            subName: service.sub_services && service.sub_services[0] ? service.sub_services[0].name : service.title,
                            price: service.starting_price,
                            isSub: false,
                            icon: service.icon || 'wrench'
                        });
                    }

                    if (Array.isArray(service.sub_services)) {
                        service.sub_services.forEach(function(sub) {
                            if (sub.name.toLowerCase().includes(query)) {
                                matches.push({
                                    title: sub.name,
                                    category: service.category,
                                    subName: sub.name,
                                    price: sub.price,
                                    time: sub.time,
                                    isSub: true,
                                    icon: service.icon || 'wrench'
                                });
                            }
                        });
                    }
                });

                filterCatalogCards(query);
                renderDropdownResults(dropdown, matches, query);
            });

            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    input.value = '';
                    clearBtn.style.display = 'none';
                    dropdown.style.display = 'none';
                    if (window.filterServicesByCategory) {
                        window.filterServicesByCategory('all');
                    }
                    input.focus();
                });
            }
        }

        function renderDropdownResults(dropdown, matches, query) {
            if (!matches.length) {
                dropdown.innerHTML = 
                    '<div style="padding: 1.25rem; text-align: center; color: #64748B; font-size: 0.88rem;">' +
                        '<p style="margin-bottom: 0.5rem;">No exact service matches for <strong>"' + window.escapeHtml(query) + '"</strong></p>' +
                        '<a href="#services" style="color: #FF5500; font-weight: 600;" onclick="this.closest(\'.search-dropdown-menu\').style.display=\'none\';">Browse All Rate Cards &rarr;</a>' +
                    '</div>';
                dropdown.style.display = 'block';
                return;
            }

            var topMatches = matches.slice(0, 6);
            var html = '<div class="search-dropdown-head">Recommended Services (' + matches.length + ')</div>';

            topMatches.forEach(function(item) {
                html += 
                    '<div class="search-suggestion-item" data-cat="' + item.category + '" data-sub="' + window.escapeHtml(item.subName) + '" data-price="' + item.price + '">' +
                        '<div class="search-sug-left">' +
                            '<div class="search-sug-icon">' +
                                '<i data-lucide="' + window.getLucideName(item.icon) + '" style="width: 17px; height: 17px;"></i>' +
                            '</div>' +
                            '<div>' +
                                '<div class="search-sug-title">' + window.highlightMatch(item.title, query) + '</div>' +
                                '<div class="search-sug-sub">' + window.capitalize(item.category) + ' • ⚡ Quick Dispatch</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="search-sug-right">' +
                            '<span class="search-sug-price">₹' + item.price + '</span>' +
                            '<span class="search-sug-book-btn">View</span>' +
                        '</div>' +
                    '</div>';
            });

            dropdown.innerHTML = html;
            dropdown.style.display = 'block';

            if (window.lucide) {
                lucide.createIcons({ root: dropdown });
            }

            // Navigate to category & catalog section on click
            dropdown.querySelectorAll('.search-suggestion-item').forEach(function(el) {
                el.addEventListener('click', function() {
                    var cat = el.getAttribute('data-cat');
                    dropdown.style.display = 'none';
                    if (window.filterServicesByCategory) {
                        window.filterServicesByCategory(cat);
                    }
                    var servicesSection = document.getElementById('services');
                    if (servicesSection) {
                        servicesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        }

        function filterCatalogCards(query) {
            var catalogCards = document.querySelectorAll('.catalog-card');
            catalogCards.forEach(function(card) {
                var text = card.textContent.toLowerCase();
                if (text.includes(query)) {
                    card.style.display = 'flex';
                    card.style.opacity = '1';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        setupSearchField(desktopInput, desktopClearBtn, desktopDropdown);

        document.addEventListener('click', function(e) {
            if (desktopDropdown && !desktopDropdown.contains(e.target) && e.target !== desktopInput) {
                desktopDropdown.style.display = 'none';
            }
        });
    }
})();
