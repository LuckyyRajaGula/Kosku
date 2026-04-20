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
})();
