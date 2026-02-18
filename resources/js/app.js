import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.navbarRole = function (config) {
    return {
        role: config.role || null,
        roleColors: config.roleColors || {},
        defaultColor: config.defaultColor || '',
        navClass: '',
        init() {
            this.updateClass();

            window.addEventListener('role-changed', (event) => {
                this.role = event.detail?.role || null;
                this.updateClass();
            });
        },
        updateClass() {
            const colorClass = this.roleColors[this.role] || this.defaultColor;
            this.navClass = colorClass;
        },
    };
};

window.setUserRole = function (role) {
    window.dispatchEvent(new CustomEvent('role-changed', { detail: { role } }));
};

Alpine.start();
