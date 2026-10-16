/**
 * KaamMitra - Modern Marketplace Landing Page Controller
 * Real-time geolocation detection, service coverage evaluation, live search, catalog filtering, & FAQs
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Lucide Icons
    if (window.lucide) {
        lucide.createIcons();
    }

    // 2. Core Navigation & Interactions
    initStickyHeader();
    initRealTimeLocation();
    initLiveSearch();
    initServiceFilters();
    initRateCardToggles();
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
   2. REAL-TIME LOCATION DETECTION & SERVICE COVERAGE
   ========================================================================== */
let locationWatchId = null;
let lastEvaluatedCoords = { lat: null, lng: null, time: 0 };
let isLocationFetchPending = false;

function initRealTimeLocation() {
    const navLocationIndicator = document.getElementById('navLocationIndicator');
    const navLocationName = document.getElementById('navLocationName');

    // 1. Check browser Geolocation API support
    // If unsupported, keep location unset silently without displaying an error message or manual form
    if (!('geolocation' in navigator)) {
        return;
    }

    const geoOptions = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 60000
    };

    // 2. Start watching position continuously while page is active
    try {
        locationWatchId = navigator.geolocation.watchPosition(
            handlePositionSuccess,
            handlePositionError,
            geoOptions
        );
    } catch (e) {
        // Leave location unset silently
    }

    // Clean up watcher when leaving page
    window.addEventListener('beforeunload', () => {
        if (locationWatchId !== null && navigator.geolocation) {
            navigator.geolocation.clearWatch(locationWatchId);
            locationWatchId = null;
        }
    });

    // 3. Handle Position Success — strictly uses actual browser coordinates
    function handlePositionSuccess(position) {
        if (!position || !position.coords) return;
        const coords = position.coords;
        const lat = coords.latitude;
        const lng = coords.longitude;
        const accuracy = coords.accuracy || null;
        const timestamp = position.timestamp || Date.now();

        // Validate actual numeric coordinates
        if (typeof lat !== 'number' || typeof lng !== 'number' || isNaN(lat) || isNaN(lng)) return;

        // Check if movement is significant (> 300 meters) or first lookup to avoid redundant API calls
        if (lastEvaluatedCoords.lat !== null && lastEvaluatedCoords.lng !== null) {
            const distanceMovedKm = getDistanceKm(lat, lng, lastEvaluatedCoords.lat, lastEvaluatedCoords.lng);
            const timeSinceLastSec = (Date.now() - lastEvaluatedCoords.time) / 1000;
            if (distanceMovedKm < 0.3 && timeSinceLastSec < 300) {
                return;
            }
        }

        if (isLocationFetchPending) return;
        isLocationFetchPending = true;

        fetchLocationData({ lat, lng, accuracy, timestamp });
    }

    // 4. Handle Geolocation Permission / GPS Failure
    // Never display an error message or manual input. Keep location unset until valid coordinates are obtained.
    function handlePositionError(err) {
        // Silently leave location unset; keep navbar location indicator hidden
        if (navLocationIndicator) {
            navLocationIndicator.style.display = 'none';
        }
        if (navLocationName) {
            navLocationName.textContent = '';
        }
    }

    // 5. Fetch location & coverage details from backend
    async function fetchLocationData(params) {
        try {
            const queryParams = new URLSearchParams();
            queryParams.set('lat', params.lat);
            queryParams.set('lng', params.lng);
            if (params.accuracy !== null && params.accuracy !== undefined) {
                queryParams.set('accuracy', params.accuracy);
            }
            if (params.timestamp) {
                queryParams.set('timestamp', params.timestamp);
            }

            const apiPath = (window.KM_BASE_PATH || '') + 'api/detect-location.php?' + queryParams.toString();
            const res = await fetch(apiPath, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            const data = await res.json();
            isLocationFetchPending = false;

            if (data && data.success) {
                lastEvaluatedCoords = { lat: params.lat, lng: params.lng, time: Date.now(), verified: true };
                applyServiceCoverage(data);
            }
        } catch (error) {
            isLocationFetchPending = false;
            // Network or server error: leave location unset silently
        }
    }

    // 6. Update UI with verified real-time location and database coverage data
    function applyServiceCoverage(data) {
        // A. Show Actual Location beside Logo in Navbar
        if (data.display_name && data.display_name.trim() !== '') {
            if (navLocationName) {
                navLocationName.textContent = data.display_name;
            }
            if (navLocationIndicator) {
                navLocationIndicator.style.display = 'inline-flex';
                if (window.lucide) {
                    lucide.createIcons({ root: navLocationIndicator });
                }
            }
        } else {
            // If reverse geocoding did not return a valid locality/city, keep location unset
            if (navLocationIndicator) {
                navLocationIndicator.style.display = 'none';
            }
            if (navLocationName) {
                navLocationName.textContent = '';
            }
        }

        // B. Update Service Availability Banner using actual database records
        const coverageBanner = document.getElementById('serviceCoverageBanner');
        const coverageDot = document.getElementById('coverageDot');
        const coverageTitle = document.getElementById('coverageTitle');
        const coverageSubtitle = document.getElementById('coverageSubtitle');
        const coverageMeta = document.getElementById('coverageMeta');

        if (coverageBanner) {
            if (data.serviceable && data.active_providers > 0) {
                coverageBanner.style.display = 'block';
                coverageBanner.className = 'service-coverage-banner serviceable';
                if (coverageDot) coverageDot.className = 'coverage-dot';
                if (coverageTitle) {
                    coverageTitle.textContent = `Doorstep Services Active in ${data.display_name}`;
                }
                if (coverageSubtitle) {
                    coverageSubtitle.textContent = `${data.active_providers} verified service provider(s) registered in your area.`;
                }
                if (coverageMeta) {
                    coverageMeta.innerHTML = `<span class="coverage-badge-pill"><i data-lucide="shield-check" style="width: 14px; height: 14px;"></i> Verified Providers Active</span>`;
                    if (window.lucide) lucide.createIcons({ root: coverageMeta });
                }
            } else if (data.display_name) {
                coverageBanner.style.display = 'block';
                coverageBanner.className = 'service-coverage-banner unserviceable';
                if (coverageDot) coverageDot.className = 'coverage-dot dot-amber';
                if (coverageTitle) {
                    coverageTitle.textContent = `Service Expanding Soon to ${data.display_name}`;
                }
                if (coverageSubtitle) {
                    coverageSubtitle.textContent = `No approved service providers currently registered for this location.`;
                }
                if (coverageMeta) {
                    coverageMeta.innerHTML = `<a href="tel:18005226648" class="btn btn-outline btn-sm" style="font-size: 0.8rem; text-decoration: none;">Call Helpline</a>`;
                }
            }
        }

        // C. Update Search Placeholder if city was detected
        const headerSearchInput = document.getElementById('headerSearchInput');
        if (headerSearchInput && data.city) {
            headerSearchInput.setAttribute('placeholder', `Search repairs in ${data.city}...`);
        }
    }
}

// Distance helper
function getDistanceKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

/* ==========================================================================
   3. LIVE SEARCH & INSTANT AUTO-SUGGESTIONS DROPDOWN
   ========================================================================== */
function initLiveSearch() {
    const desktopInput = document.getElementById('headerSearchInput');
    const desktopClearBtn = document.getElementById('clearSearchBtn');
    const desktopDropdown = document.getElementById('headerSearchDropdown');

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
                if (query.length === 0) {
                    filterServicesByCategory('all');
                }
                return;
            }

            // Find matching services & sub-services
            const matches = [];

            services.forEach(service => {
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
                    <a href="#services" style="color: #FF5500; font-weight: 600;" onclick="this.closest('.search-dropdown-menu').style.display='none';">Browse All Rate Cards &rarr;</a>
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
                            <div class="search-sug-sub">${capitalize(item.category)} • ⚡ Quick Dispatch</div>
                        </div>
                    </div>
                    <div class="search-sug-right">
                        <span class="search-sug-price">₹${item.price}</span>
                        <span class="search-sug-book-btn">View</span>
                    </div>
                </div>
            `;
        });

        dropdown.innerHTML = html;
        dropdown.style.display = 'block';

        if (window.lucide) {
            lucide.createIcons({ root: dropdown });
        }

        // Navigate to category & catalog section on click
        dropdown.querySelectorAll('.search-suggestion-item').forEach(el => {
            el.addEventListener('click', () => {
                const cat = el.getAttribute('data-cat');
                dropdown.style.display = 'none';
                filterServicesByCategory(cat);
                const servicesSection = document.getElementById('services');
                if (servicesSection) {
                    servicesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

    document.addEventListener('click', (e) => {
        if (desktopDropdown && !desktopDropdown.contains(e.target) && e.target !== desktopInput) {
            desktopDropdown.style.display = 'none';
        }
    });
}

/* ==========================================================================
   4. SERVICE CATEGORY TABS & FILTERING
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

function initRateCardToggles() {
    const toggleBtns = document.querySelectorAll('.rate-card-toggle-btn');
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const drawer = btn.nextElementSibling;
            if (!drawer) return;
            const isOpen = drawer.classList.contains('open');
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

    const servicesSec = document.getElementById('services');
    const pledgeSec = document.getElementById('pledge');
    const reviewsSec = document.getElementById('reviews');

    window.addEventListener('scroll', () => {
        const scrollPos = window.scrollY + 200;

        if (reviewsSec && scrollPos >= reviewsSec.offsetTop) {
            setActiveTab('reviews');
        } else if (pledgeSec && scrollPos >= pledgeSec.offsetTop && (!servicesSec || scrollPos < servicesSec.offsetTop)) {
            setActiveTab('pledge');
        } else if (servicesSec && scrollPos >= servicesSec.offsetTop) {
            setActiveTab('services');
        } else {
            setActiveTab('home');
        }
    });

    function setActiveTab(tabName) {
        tabs.forEach(tab => {
            if (tab.getAttribute('data-tab') === tabName) {
                tab.classList.add('active');
            } else {
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
    return escapedText.replace(regex, '<span style="color: #FF5500; font-weight: 700;">$1</span>');
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
