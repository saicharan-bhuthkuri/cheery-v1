/**
 * KaamMitra - Navigation & Mobile Drawer Controller
 * Handles sticky island navbar, mobile side drawer, and mobile bottom app bar
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initStickyHeader();
        initMobileDrawer();
        initMobileBottomBar();
    });

    // 1. Sticky Header Scroll Effect
    function initStickyHeader() {
        var header = document.getElementById('siteHeader');
        if (!header) return;

        window.addEventListener('scroll', function() {
            if (window.scrollY > 15) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // 2. Mobile Drawer Navigation Overlay
    function initMobileDrawer() {
        var hamburgerBtn = document.getElementById('mobileMenuBtn');
        var drawerOverlay = document.getElementById('mobileDrawerOverlay');
        var closeBtn = document.getElementById('closeMobileDrawerBtn');
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

        drawerOverlay.addEventListener('click', function(e) {
            if (e.target === drawerOverlay) closeDrawer();
        });

        document.querySelectorAll('.mobile-nav-link').forEach(function(link) {
            link.addEventListener('click', closeDrawer);
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && drawerOverlay.classList.contains('active')) {
                closeDrawer();
            }
        });
    }

    // 3. Mobile App Bottom Navigation Bar Controller
    function initMobileBottomBar() {
        var tabs = document.querySelectorAll('.mobile-app-bottom-bar .app-tab');
        if (!tabs.length) return;

        var servicesSec = document.getElementById('services');
        var pledgeSec = document.getElementById('pledge');
        var reviewsSec = document.getElementById('reviews');

        window.addEventListener('scroll', function() {
            var scrollPos = window.scrollY + 200;

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
            tabs.forEach(function(tab) {
                if (tab.getAttribute('data-tab') === tabName) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
        }
    }
})();
