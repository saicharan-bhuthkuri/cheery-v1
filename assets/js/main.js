/**
 * KaamMitra - Modern Marketplace & App-First Architecture Controller
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Lucide Icons
    if (window.lucide) {
        lucide.createIcons();
    }

    // 2. Core Navigation & Interactions
    initStickyHeader();
    initLocationPicker();
    initLiveSearch();
    initServiceFilters();
    initCategoryLauncher();
    initFaqAccordion();
    initMobileDrawer();
    initMobileBottomBar();
});

/* ==========================================================================
   1. STICKY HEADER SCROLL EFFECT
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
   2. LOCATION SELECTOR & CITY PICKER MODAL
   ========================================================================== */
function initLocationPicker() {
    const triggerBtns = document.querySelectorAll('.trigger-location-modal, #navLocationBtn');
    const modalOverlay = document.getElementById('locationModalOverlay');
    const closeBtn = document.getElementById('closeLocationModalBtn');
    const searchInput = document.getElementById('citySearchInput');
    const cityButtons = document.querySelectorAll('.city-select-btn');
    const navCityText = document.getElementById('navCityText');

    // Restore saved city from localStorage
    const savedCity = localStorage.getItem('km_user_city');
    if (savedCity && navCityText) {
        navCityText.textContent = savedCity + (savedCity.includes(',') ? '' : ', India');
        cityButtons.forEach(btn => {
            if (btn.getAttribute('data-city') === savedCity) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    function openLocationModal() {
        if (!modalOverlay) return;
        modalOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        if (searchInput) {
            searchInput.value = '';
            filterCities('');
            setTimeout(() => searchInput.focus(), 100);
        }
    }

    function closeLocationModal() {
        if (!modalOverlay) return;
        modalOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    triggerBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openLocationModal();
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeLocationModal);

    if (modalOverlay) {
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeLocationModal();
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalOverlay && modalOverlay.classList.contains('active')) {
            closeLocationModal();
        }
    });

    // City search filter
    function filterCities(query) {
        const q = query.toLowerCase().trim();
        cityButtons.forEach(btn => {
            const cityName = (btn.getAttribute('data-city') || '').toLowerCase();
            if (!q || cityName.includes(q)) {
                btn.style.display = 'flex';
            } else {
                btn.style.display = 'none';
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            filterCities(e.target.value);
        });
    }

    // City Selection handler
    cityButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const chosenCity = btn.getAttribute('data-city');
            if (!chosenCity) return;

            cityButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            if (navCityText) {
                navCityText.textContent = chosenCity + (chosenCity.includes(',') ? '' : ', India');
            }

            localStorage.setItem('km_user_city', chosenCity);
            closeLocationModal();
            window.showToast(`Service location set to ${chosenCity}`, 'success');
        });
    });
}

/* ==========================================================================
   3. LIVE SEARCH & INSTANT AUTO-SUGGESTIONS DROPDOWN
   ========================================================================== */
function initLiveSearch() {
    const desktopInput = document.getElementById('headerSearchInput');
    const desktopClearBtn = document.getElementById('clearSearchBtn');
    const desktopDropdown = document.getElementById('headerSearchDropdown');

    const mobileInput = document.getElementById('mobileSearchInput');
    const mobileClearBtn = document.getElementById('clearMobileSearchBtn');
    const mobileDropdown = document.getElementById('mobileSearchDropdown');

    const services = window.KM_SERVICES || [];

    function setupSearchField(input, clearBtn, dropdown) {
        if (!input || !dropdown) return;

        input.addEventListener('input', () => {
            const query = input.value.trim().toLowerCase();

            if (clearBtn) {
                clearBtn.style.display = query.length > 0 ? 'block' : 'none';
            }

            if (query.length < 2) {
                dropdown.style.display = 'none';
                dropdown.innerHTML = '';
                // If emptied, reset catalog filter
                if (query.length === 0) {
                    filterServicesByCategory('all');
                }
                return;
            }

            // Find matching services & sub-services
            const matches = [];

            services.forEach(service => {
                // Match parent title/subtitle
                if (service.title.toLowerCase().includes(query) || service.subtitle.toLowerCase().includes(query)) {
                    matches.push({
                        title: service.title,
                        category: service.category,
                        subName: service.sub_services && service.sub_services[0] ? service.sub_services[0].name : service.title,
                        price: service.starting_price,
                        isSub: false,
                        icon: service.icon || 'wrench'
                    });
                }

                // Match subservices
                if (Array.isArray(service.sub_services)) {
                    service.sub_services.forEach(sub => {
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

            // Filter catalog cards in the page simultaneously
            filterCatalogCards(query);

            renderDropdownResults(dropdown, matches, query);
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                input.value = '';
                clearBtn.style.display = 'none';
                dropdown.style.display = 'none';
                filterServicesByCategory('all');
                input.focus();
            });
        }
    }

    function renderDropdownResults(dropdown, matches, query) {
        if (!matches.length) {
            dropdown.innerHTML = `
                <div style="padding: 1.25rem; text-align: center; color: #64748B; font-size: 0.88rem;">
                    <p style="margin-bottom: 0.5rem;">No exact service matches for <strong>"${escapeHtml(query)}"</strong></p>
                    <a href="#services" style="color: #FF6600; font-weight: 600;" onclick="this.closest('.search-dropdown-menu').style.display='none';">Browse All Rate Cards &rarr;</a>
                </div>
            `;
            dropdown.style.display = 'block';
            return;
        }

        const topMatches = matches.slice(0, 6);
        let html = `<div class="search-dropdown-head">Recommended Services (${matches.length})</div>`;

        topMatches.forEach(item => {
            html += `
                <div class="search-suggestion-item" data-cat="${item.category}" data-sub="${escapeHtml(item.subName)}" data-price="${item.price}">
                    <div class="search-sug-left">
                        <div class="search-sug-icon">
                            <i data-lucide="${getLucideName(item.icon)}" style="width: 17px; height: 17px;"></i>
                        </div>
                        <div>
                            <div class="search-sug-title">${highlightMatch(item.title, query)}</div>
                            <div class="search-sug-sub">${capitalize(item.category)} • ⚡ 30 min arrival</div>
                        </div>
                    </div>
                    <div class="search-sug-right">
                        <span class="search-sug-price">₹${item.price}</span>
                        <button type="button" class="search-sug-book-btn">Book</button>
                    </div>
                </div>
            `;
        });

        dropdown.innerHTML = html;
        dropdown.style.display = 'block';

        if (window.lucide) {
            lucide.createIcons({ root: dropdown });
        }

        // Attach booking triggers
        dropdown.querySelectorAll('.search-suggestion-item').forEach(el => {
            el.addEventListener('click', () => {
                const cat = el.getAttribute('data-cat');
                const sub = el.getAttribute('data-sub');
                const price = el.getAttribute('data-price');
                dropdown.style.display = 'none';
                if (window.openBookingModal) {
                    window.openBookingModal(cat, sub, price);
                }
            });
        });
    }

    function filterCatalogCards(query) {
        const catalogCards = document.querySelectorAll('.catalog-card');
        catalogCards.forEach(card => {
            const text = card.textContent.toLowerCase();
            if (text.includes(query)) {
                card.style.display = 'flex';
                card.style.opacity = '1';
            } else {
                card.style.display = 'none';
            }
        });
    }

    setupSearchField(desktopInput, desktopClearBtn, desktopDropdown);
    setupSearchField(mobileInput, mobileClearBtn, mobileDropdown);

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (desktopDropdown && !desktopDropdown.contains(e.target) && e.target !== desktopInput) {
            desktopDropdown.style.display = 'none';
        }
        if (mobileDropdown && !mobileDropdown.contains(e.target) && e.target !== mobileInput) {
            mobileDropdown.style.display = 'none';
        }
    });
}

/* ==========================================================================
   4. SERVICE CATEGORY TABS & FILTERING
   ========================================================================= */
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
   5. HERO QUICK CATEGORY APP TILES LAUNCHER
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
   6. FAQ ACCORDION
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
   7. MOBILE DRAWER NAVIGATION
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

    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);

    drawerOverlay.addEventListener('click', (e) => {
        if (e.target === drawerOverlay) closeDrawer();
    });

    document.querySelectorAll('.mobile-nav-link').forEach(link => {
        link.addEventListener('click', closeDrawer);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && drawerOverlay.classList.contains('active')) {
            closeDrawer();
        }
    });
}

/* ==========================================================================
   8. MOBILE APP BOTTOM NAVIGATION BAR CONTROLLER
   ========================================================================== */
function initMobileBottomBar() {
    const tabs = document.querySelectorAll('.mobile-app-bottom-bar .app-tab');
    if (!tabs.length) return;

    // Track scroll to update active tab
    window.addEventListener('scroll', () => {
        const scrollPos = window.scrollY;
        const servicesSection = document.getElementById('services');

        if (servicesSection && scrollPos >= servicesSection.offsetTop - 150) {
            setActiveTab('services');
        } else {
            setActiveTab('home');
        }
    });

    function setActiveTab(tabName) {
        tabs.forEach(tab => {
            if (tab.getAttribute('data-tab') === tabName) {
                tab.classList.add('active');
            } else if (tab.getAttribute('data-tab') !== 'book') {
                tab.classList.remove('active');
            }
        });
    }
}

/* ==========================================================================
   HELPERS & GLOBAL UTILITIES
   ========================================================================== */
function getLucideName(icon) {
    const map = {
        'droplet': 'droplet',
        'zap': 'zap',
        'cpu': 'cpu',
        'hammer': 'hammer',
        'sparkles': 'sparkles',
        'paint-brush': 'palette',
        'lock': 'lock',
        'wrench': 'wrench'
    };
    return map[icon] || 'wrench';
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString().replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[m]));
}

function highlightMatch(text, query) {
    if (!query) return escapeHtml(text);
    const escapedText = escapeHtml(text);
    const escapedQuery = escapeHtml(query);
    const regex = new RegExp(`(${escapedQuery})`, 'gi');
    return escapedText.replace(regex, '<span style="color: #FF6600; font-weight: 700;">$1</span>');
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

window.showToast = function(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const iconName = type === 'success' ? 'check-circle' : 'alert-circle';
    const iconColor = type === 'success' ? '#10B981' : '#EF4444';

    toast.innerHTML = `
        <i data-lucide="${iconName}" style="color: ${iconColor}; width: 20px; height: 20px; flex-shrink: 0;"></i>
        <span>${message}</span>
    `;

    container.appendChild(toast);

    if (window.lucide) {
        lucide.createIcons({ root: toast });
    }

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
};
