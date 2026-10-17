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
        initCustomSelects();
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
            if (window.lucide) {
                window.lucide.createIcons();
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

        // Check hash or URL search param
        var params = new URLSearchParams(window.location.search);
        if (params.get('tab') === 'register' || window.location.hash === '#register') {
            setTab('register');
        } else if (params.get('tab') === 'login' || window.location.hash === '#login') {
            setTab('login');
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

    // 4. Custom Select Dropdown UI
    function initCustomSelects() {
        var selects = document.querySelectorAll('.input-icon-wrap select.select-control');
        selects.forEach(function(select) {
            if (select.dataset.customized === 'true') return;
            select.dataset.customized = 'true';

            var wrap = select.closest('.input-icon-wrap');
            if (!wrap) return;

            // Container
            var container = document.createElement('div');
            container.className = 'custom-select-container';

            // Trigger button
            var trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'custom-select-trigger';
            trigger.setAttribute('aria-haspopup', 'listbox');
            trigger.setAttribute('aria-expanded', 'false');

            var labelSpan = document.createElement('span');
            labelSpan.className = 'custom-select-label';
            
            var selectedOption = select.options[select.selectedIndex];
            labelSpan.textContent = selectedOption ? selectedOption.text : 'Select...';

            var arrowSpan = document.createElement('span');
            arrowSpan.className = 'custom-select-arrow';
            arrowSpan.innerHTML = '<i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>';

            trigger.appendChild(labelSpan);
            trigger.appendChild(arrowSpan);

            // Dropdown Menu
            var menu = document.createElement('ul');
            menu.className = 'custom-select-menu';
            menu.setAttribute('role', 'listbox');

            function populateOptions() {
                menu.innerHTML = '';
                Array.from(select.options).forEach(function(opt) {
                    var li = document.createElement('li');
                    li.className = 'custom-select-option' + (opt.selected ? ' is-selected' : '');
                    li.setAttribute('role', 'option');
                    li.setAttribute('aria-selected', opt.selected ? 'true' : 'false');
                    li.dataset.value = opt.value;

                    var optText = document.createElement('span');
                    optText.textContent = opt.text;
                    li.appendChild(optText);

                    var checkIcon = document.createElement('span');
                    checkIcon.className = 'custom-select-check';
                    checkIcon.innerHTML = '<i data-lucide="check" style="width: 15px; height: 15px;"></i>';
                    li.appendChild(checkIcon);

                    li.addEventListener('click', function(e) {
                        e.stopPropagation();
                        select.value = opt.value;
                        labelSpan.textContent = opt.text;
                        closeMenu();
                        updateSelectedClass();
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    });

                    menu.appendChild(li);
                });
            }

            function updateSelectedClass() {
                var items = menu.querySelectorAll('.custom-select-option');
                items.forEach(function(item) {
                    var isSel = item.dataset.value === select.value;
                    item.classList.toggle('is-selected', isSel);
                    item.setAttribute('aria-selected', isSel ? 'true' : 'false');
                });
            }

            populateOptions();

            function openMenu() {
                document.querySelectorAll('.custom-select-container.is-open').forEach(function(c) {
                    if (c !== container) {
                        c.classList.remove('is-open');
                        var tr = c.querySelector('.custom-select-trigger');
                        if (tr) tr.setAttribute('aria-expanded', 'false');
                    }
                });

                container.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            }

            function closeMenu() {
                container.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
            }

            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                if (container.classList.contains('is-open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });

            trigger.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    if (!container.classList.contains('is-open')) {
                        openMenu();
                    } else if (e.key === 'Enter' || e.key === ' ') {
                        closeMenu();
                    }
                }
            });

            // Hide native select visually
            select.classList.add('visually-hidden-select');
            select.setAttribute('tabindex', '-1');

            container.appendChild(trigger);
            container.appendChild(menu);
            wrap.appendChild(container);

            select.addEventListener('change', function() {
                var curOpt = select.options[select.selectedIndex];
                if (curOpt) {
                    labelSpan.textContent = curOpt.text;
                }
                updateSelectedClass();
            });
        });

        // Close on click outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.custom-select-container')) {
                document.querySelectorAll('.custom-select-container.is-open').forEach(function(c) {
                    c.classList.remove('is-open');
                    var tr = c.querySelector('.custom-select-trigger');
                    if (tr) tr.setAttribute('aria-expanded', 'false');
                });
            }
        });

        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.custom-select-container.is-open').forEach(function(c) {
                    c.classList.remove('is-open');
                    var tr = c.querySelector('.custom-select-trigger');
                    if (tr) tr.setAttribute('aria-expanded', 'false');
                });
            }
        });

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }
})();
