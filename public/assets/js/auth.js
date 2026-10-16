/**
 * KaamMitra - Authentication & Onboarding Controller
 * Tab switching (Customer Login / Register), Terms & Conditions gate, and password toggles
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initAuthTabs();
        initTermsCheckboxGates();
        initPasswordToggles();
    });

    // 1. Customer Login & Register Tab Switching
    function initAuthTabs() {
        var tabLoginBtn = document.getElementById('tabLoginBtn');
        var tabRegisterBtn = document.getElementById('tabRegisterBtn');
        var loginContent = document.getElementById('loginTabContent');
        var registerContent = document.getElementById('registerTabContent');
        var switchToRegisterLink = document.getElementById('switchToRegisterLink');
        var switchToLoginLink = document.getElementById('switchToLoginLink');

        if (!tabLoginBtn || !tabRegisterBtn || !loginContent || !registerContent) {
            return;
        }

        function setTab(tab) {
            if (tab === 'register') {
                tabRegisterBtn.classList.add('active');
                tabRegisterBtn.setAttribute('aria-selected', 'true');
                tabLoginBtn.classList.remove('active');
                tabLoginBtn.setAttribute('aria-selected', 'false');
                loginContent.style.display = 'none';
                registerContent.style.display = 'block';
            } else {
                tabLoginBtn.classList.add('active');
                tabLoginBtn.setAttribute('aria-selected', 'true');
                tabRegisterBtn.classList.remove('active');
                tabRegisterBtn.setAttribute('aria-selected', 'false');
                loginContent.style.display = 'block';
                registerContent.style.display = 'none';
            }
        }

        tabLoginBtn.addEventListener('click', function() { setTab('login'); });
        tabRegisterBtn.addEventListener('click', function() { setTab('register'); });
        if (switchToRegisterLink) {
            switchToRegisterLink.addEventListener('click', function() { setTab('register'); });
        }
        if (switchToLoginLink) {
            switchToLoginLink.addEventListener('click', function() { setTab('login'); });
        }
    }

    // 2. Terms and Conditions Checkbox Gate for Register Buttons
    // Required: Keep Register button disabled until checkbox is selected
    function initTermsCheckboxGates() {
        var pairs = [
            { checkboxId: 'customerTermsCheckbox', btnId: 'customerRegisterBtn' },
            { checkboxId: 'providerTermsCheckbox', btnId: 'providerRegisterBtn' }
        ];

        pairs.forEach(function(pair) {
            var checkbox = document.getElementById(pair.checkboxId);
            var btn = document.getElementById(pair.btnId);
            if (!checkbox || !btn) return;

            function syncState() {
                btn.disabled = !checkbox.checked;
            }

            checkbox.addEventListener('change', syncState);
            syncState(); // Enforce state on page load
        });
    }

    // 3. Password Visibility Toggles
    function initPasswordToggles() {
        document.querySelectorAll('.password-toggle-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var targetId = btn.getAttribute('data-target');
                var input = document.getElementById(targetId);
                if (!input) return;

                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.innerHTML = isPassword 
                    ? '<i data-lucide="eye-off" style="width: 18px; height: 18px;"></i>' 
                    : '<i data-lucide="eye" style="width: 18px; height: 18px;"></i>';
                
                if (window.lucide) {
                    lucide.createIcons();
                }
            });
        });
    }
})();
