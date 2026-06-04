(function () {
    const passwordToggles = document.querySelectorAll('[data-toggle-password]');

    passwordToggles.forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.querySelector(button.getAttribute('data-toggle-password'));
            const icon = button.querySelector('i');

            if (!target) {
                return;
            }

            const show = target.type === 'password';
            target.type = show ? 'text' : 'password';

            if (icon) {
                icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
        });
    });

    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('mobileBackdrop');
    const openButtons = document.querySelectorAll('[data-open-sidebar]');
    const closeButtons = document.querySelectorAll('[data-close-sidebar]');

    const openSidebar = () => {
        if (sidebar) {
            sidebar.classList.add('open');
        }
        if (backdrop) {
            backdrop.classList.add('open');
        }
    };

    const closeSidebar = () => {
        if (sidebar) {
            sidebar.classList.remove('open');
        }
        if (backdrop) {
            backdrop.classList.remove('open');
        }
    };

    openButtons.forEach((button) => button.addEventListener('click', openSidebar));
    closeButtons.forEach((button) => button.addEventListener('click', closeSidebar));

    const notifPanel = document.getElementById('notifPanel');
    const notifToggle = document.querySelector('[data-toggle-notif]');

    if (notifToggle && notifPanel) {
        notifToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            notifPanel.classList.toggle('open');
        });

        document.addEventListener('click', (event) => {
            if (!notifPanel.contains(event.target) && !notifToggle.contains(event.target)) {
                notifPanel.classList.remove('open');
            }
        });
    }

    const profilePanel = document.getElementById('profileDropdownPanel');
    const profileToggle = document.getElementById('profileDropdownBtn');
    const profileWrap = document.querySelector('.profile-dropdown-wrap');

    if (profileToggle && profilePanel) {
        profileToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            profilePanel.classList.toggle('open');
            if (profileWrap) {
                profileWrap.classList.toggle('active');
            }
        });

        document.addEventListener('click', (event) => {
            if (!profilePanel.contains(event.target) && !profileToggle.contains(event.target)) {
                profilePanel.classList.remove('open');
                if (profileWrap) {
                    profileWrap.classList.remove('active');
                }
            }
        });
    }

    const tenantSelect = document.getElementById('id_penyewa');
    const nominalInput = document.getElementById('nominal');
    const periodeInput = document.getElementById('periode');
    const jatuhTempoInput = document.getElementById('tanggal_jatuh_tempo');

    const monthNames = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    const monthIndex = {
        januari: 0,
        februari: 1,
        maret: 2,
        april: 3,
        mei: 4,
        juni: 5,
        juli: 6,
        agustus: 7,
        september: 8,
        oktober: 9,
        november: 10,
        desember: 11,
    };

    const defaultPeriode = () => {
        const now = new Date();
        return `${monthNames[now.getMonth()]} ${now.getFullYear()}`;
    };

    const parsePeriode = (value) => {
        if (!value) {
            const now = new Date();
            return { year: now.getFullYear(), month: now.getMonth() };
        }

        const parts = value.trim().split(/\s+/);
        if (parts.length < 2) {
            const now = new Date();
            return { year: now.getFullYear(), month: now.getMonth() };
        }

        const monthName = parts[0].toLowerCase();
        const year = Number(parts[1]);
        const month = monthIndex[monthName];

        if (Number.isNaN(year) || month === undefined) {
            const now = new Date();
            return { year: now.getFullYear(), month: now.getMonth() };
        }

        return { year, month };
    };

    const setJatuhTempo = () => {
        if (!tenantSelect || !jatuhTempoInput) {
            return;
        }

        const option = tenantSelect.selectedOptions[0];
        if (!option) {
            return;
        }

        const tanggalMasuk = option.getAttribute('data-tanggal-masuk');
        if (!tanggalMasuk) {
            return;
        }

        const day = Number(tanggalMasuk.split('-')[2]);
        if (!day) {
            return;
        }

        const { year, month } = parsePeriode(periodeInput ? periodeInput.value : '');
        const lastDay = new Date(year, month + 1, 0).getDate();
        const dueDay = Math.min(day, lastDay);
        const dueDate = new Date(year, month, dueDay);
        const yyyy = dueDate.getFullYear();
        const mm = String(dueDate.getMonth() + 1).padStart(2, '0');
        const dd = String(dueDate.getDate()).padStart(2, '0');

        jatuhTempoInput.value = `${yyyy}-${mm}-${dd}`;
    };

    if (tenantSelect && nominalInput && periodeInput) {
        tenantSelect.addEventListener('change', () => {
            const option = tenantSelect.selectedOptions[0];
            if (!option) {
                return;
            }

            const harga = option.getAttribute('data-harga');
            if (harga) {
                nominalInput.value = harga;
            }

            if (!periodeInput.value) {
                periodeInput.value = defaultPeriode();
            }

            setJatuhTempo();
        });

        if (periodeInput) {
            periodeInput.addEventListener('change', () => {
                setJatuhTempo();
            });
        }
    }

    /* ─── Premium Custom Select Converter ─── */
    function convertSelect(select) {
        if (select.classList.contains('custom-select-hidden')) return;
        if (select.closest('.no-custom-select')) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select-wrapper';

        // Copy classes from original select
        if (select.getAttribute('class')) {
            select.getAttribute('class').split(' ').forEach(cls => {
                const trimmed = cls.trim();
                if (trimmed) wrapper.classList.add(trimmed);
            });
        }

        // Copy inline style rules
        if (select.style.cssText) {
            wrapper.style.cssText = select.style.cssText;
        }

        const trigger = document.createElement('div');
        trigger.className = 'custom-select-trigger';
        trigger.tabIndex = 0;

        // Propagate height & font-size styles to trigger
        if (select.style.height) trigger.style.height = select.style.height;
        if (select.style.fontSize) trigger.style.fontSize = select.style.fontSize;

        const triggerValue = document.createElement('span');
        triggerValue.className = 'custom-select-value';

        const triggerIcon = document.createElement('i');
        triggerIcon.className = 'bi bi-chevron-down custom-select-arrow';

        trigger.appendChild(triggerValue);
        trigger.appendChild(triggerIcon);
        wrapper.appendChild(trigger);

        const dropdown = document.createElement('div');
        dropdown.className = 'custom-select-dropdown';

        function updateTriggerText() {
            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption) {
                triggerValue.textContent = selectedOption.textContent;
            } else {
                triggerValue.textContent = '';
            }
        }

        function createOptionItem(optionEl) {
            const item = document.createElement('div');
            item.className = 'custom-select-option';
            if (optionEl.selected) {
                item.classList.add('selected');
            }
            item.textContent = optionEl.textContent;
            item.dataset.value = optionEl.value;

            if (optionEl.disabled) {
                item.classList.add('disabled');
                item.style.opacity = '0.5';
                item.style.pointerEvents = 'none';
            }

            item.addEventListener('click', (e) => {
                e.stopPropagation();

                const prevSelected = dropdown.querySelector('.custom-select-option.selected');
                if (prevSelected) prevSelected.classList.remove('selected');

                item.classList.add('selected');

                select.value = optionEl.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));

                closeDropdown();
            });

            return item;
        }

        function buildDropdownItems() {
            dropdown.innerHTML = '';

            Array.from(select.children).forEach(child => {
                if (child.tagName === 'OPTGROUP') {
                    const group = document.createElement('div');
                    group.className = 'custom-select-group';

                    const groupTitle = document.createElement('div');
                    groupTitle.className = 'custom-select-group-title';
                    groupTitle.textContent = child.getAttribute('label');
                    group.appendChild(groupTitle);

                    Array.from(child.children).forEach(option => {
                        const item = createOptionItem(option);
                        group.appendChild(item);
                    });
                    dropdown.appendChild(group);
                } else if (child.tagName === 'OPTION') {
                    const item = createOptionItem(child);
                    dropdown.appendChild(item);
                }
            });
        }

        buildDropdownItems();
        wrapper.appendChild(dropdown);

        // Hide original select visually
        select.classList.add('custom-select-hidden');

        // Insert custom select wrapper before the original, then place original inside it
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        updateTriggerText();

        // Listen for programmatic value changes
        select.addEventListener('change', () => {
            updateTriggerText();
            const options = dropdown.querySelectorAll('.custom-select-option');
            options.forEach(opt => {
                if (opt.dataset.value === select.value) {
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });
        });

        // Watch for dynamic options insertions (e.g. AJAX or Laravel dynamic rendering)
        const optionObserver = new MutationObserver(() => {
            buildDropdownItems();
            updateTriggerText();
        });
        optionObserver.observe(select, { childList: true });

        // Event listener for opening/closing
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = wrapper.classList.contains('is-open');
            closeAllDropdowns();
            if (!isOpen) {
                openDropdown();
            }
        });

        function openDropdown() {
            wrapper.classList.add('is-open');

            // Virtual screen bounds checker
            const triggerRect = trigger.getBoundingClientRect();
            const spaceBelow = window.innerHeight - triggerRect.bottom;

            if (spaceBelow < 280 && triggerRect.top > 280) {
                wrapper.classList.add('open-upward');
            } else {
                wrapper.classList.remove('open-upward');
            }

            // Focus-scroll selected option
            const selected = dropdown.querySelector('.custom-select-option.selected');
            if (selected) {
                selected.scrollIntoView({ block: 'nearest' });
            }
        }

        function closeDropdown() {
            wrapper.classList.remove('is-open');
        }

        // Accessibility / keyboard events
        trigger.addEventListener('keydown', (e) => {
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                trigger.click();
            } else if (e.key === 'Escape') {
                closeDropdown();
            } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!wrapper.classList.contains('is-open')) {
                    openDropdown();
                } else {
                    const options = Array.from(dropdown.querySelectorAll('.custom-select-option:not(.disabled)'));
                    const activeIndex = options.findIndex(opt => opt.classList.contains('selected'));
                    let nextIndex = activeIndex;
                    if (e.key === 'ArrowDown') {
                        nextIndex = (activeIndex + 1) % options.length;
                    } else {
                        nextIndex = (activeIndex - 1 + options.length) % options.length;
                    }
                    if (options[nextIndex]) {
                        options[nextIndex].click();
                        options[nextIndex].scrollIntoView({ block: 'nearest' });
                    }
                }
            }
        });
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.custom-select-wrapper.is-open').forEach(el => {
            el.classList.remove('is-open');
        });
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.custom-select-wrapper')) {
            closeAllDropdowns();
        }
    });

    function initCustomSelects() {
        const selects = document.querySelectorAll('select:not(.custom-select-hidden)');
        selects.forEach(select => {
            convertSelect(select);
        });
    }

    /* ─── Premium Toast Notification API ─── */
    window.showToast = function (message, type = 'success') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        
        let iconClass = 'bi-info-circle-fill';
        let defaultTitle = 'Informasi';
        
        // Normalize type
        if (type === 'danger') type = 'error';
        
        if (type === 'success') {
            iconClass = 'bi-check-circle-fill';
            defaultTitle = 'Sukses';
        } else if (type === 'error') {
            iconClass = 'bi-x-circle-fill';
            defaultTitle = 'Kesalahan';
        } else if (type === 'warning') {
            iconClass = 'bi-exclamation-triangle-fill';
            defaultTitle = 'Peringatan';
        }

        toast.className = `custom-toast toast-${type} entering`;
        toast.innerHTML = `
            <div class="custom-toast-icon">
                <i class="bi ${iconClass}"></i>
            </div>
            <div class="custom-toast-content">
                <div class="custom-toast-title">${defaultTitle}</div>
                <div class="custom-toast-message">${message}</div>
            </div>
            <button type="button" class="custom-toast-close" aria-label="Tutup">
                <i class="bi bi-x"></i>
            </button>
            <div class="custom-toast-progress"></div>
        `;

        container.appendChild(toast);

        // Remove entrance animation class
        setTimeout(() => {
            toast.classList.remove('entering');
        }, 400);

        let dismissTimeout;
        const duration = 4000;
        let startTime = Date.now();
        let remainingTime = duration;

        const dismissToast = () => {
            toast.classList.add('exiting');
            setTimeout(() => {
                toast.remove();
                if (container.children.length === 0) {
                    container.remove();
                }
            }, 400);
        };

        const startDismissTimer = (time) => {
            startTime = Date.now();
            dismissTimeout = setTimeout(dismissToast, time);
        };

        startDismissTimer(remainingTime);

        // Close on button click
        const closeBtn = toast.querySelector('.custom-toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                clearTimeout(dismissTimeout);
                dismissToast();
            });
        }

        // Pause on hover
        toast.addEventListener('mouseenter', () => {
            clearTimeout(dismissTimeout);
            remainingTime -= (Date.now() - startTime);
        });

        // Resume on mouse leave
        toast.addEventListener('mouseleave', () => {
            if (remainingTime > 0) {
                startDismissTimer(remainingTime);
            } else {
                dismissToast();
            }
        });
    };

    function interceptAlert(alertEl) {
        const message = alertEl.textContent.trim();
        if (!message) return;

        let type = 'success';
        if (alertEl.classList.contains('error') || alertEl.classList.contains('danger')) {
            type = 'error';
        } else if (alertEl.classList.contains('warning')) {
            type = 'warning';
        } else if (alertEl.classList.contains('info')) {
            type = 'info';
        }

        window.showToast(message, type);
        alertEl.remove();
    }

    function initLaravelAlerts() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach((alertEl) => {
            interceptAlert(alertEl);
        });
    }

    function initAutoLoginChips() {
        const chips = document.querySelectorAll('.demo-chip');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const form = document.querySelector('.login-right form');

        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                const username = chip.getAttribute('data-username');
                const password = chip.getAttribute('data-password');

                if (usernameInput && passwordInput) {
                    usernameInput.value = username;
                    passwordInput.value = password;

                    // Add active class animation
                    chips.forEach(c => c.classList.remove('active'));
                    chip.classList.add('active');

                    // Play feedback toast
                    if (window.showToast) {
                        window.showToast(`Mengisi kredensial: ${username}`, 'info');
                    }

                    // Submit form after short dynamic delay for smooth visuals
                    setTimeout(() => {
                        if (form) form.submit();
                    }, 450);
                }
            });
        });
    }

    function initializeAll() {
        initCustomSelects();
        initLaravelAlerts();
        initAutoLoginChips();
    }

    // Initialize custom selects & alerts immediately or when DOM is fully ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAll);
    } else {
        initializeAll();
    }

    // Dynamic Mutation Observer for dynamically generated selects and alerts
    if (window.MutationObserver) {
        const docObserver = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        // 1. Handle select elements conversion
                        if (node.tagName === 'SELECT' && !node.classList.contains('custom-select-hidden')) {
                            convertSelect(node);
                        } else {
                            const nested = node.querySelectorAll('select:not(.custom-select-hidden)');
                            nested.forEach(sel => convertSelect(sel));
                        }

                        // 2. Handle alert elements interception
                        if (node.classList.contains('alert')) {
                            interceptAlert(node);
                        } else {
                            const nestedAlerts = node.querySelectorAll('.alert');
                            nestedAlerts.forEach(al => interceptAlert(al));
                        }
                    }
                });
            });
        });
        docObserver.observe(document.body, { childList: true, subtree: true });
    }
})();

