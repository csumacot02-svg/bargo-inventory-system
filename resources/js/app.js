import { initAiCharts } from './ai-charts';
import { initDashboard } from './dashboard';

const SIDEBAR_STORAGE_KEY = 'bargo.sidebar.collapsed';

function initPasswordToggles() {
    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-password-toggle]');

        if (! toggle) {
            return;
        }

        const input = document.getElementById(toggle.getAttribute('data-password-toggle'));

        if (! input) {
            return;
        }

        const showing = input.type === 'password';
        input.type = showing ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', showing ? 'true' : 'false');
        toggle.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');

        toggle.querySelector('.password-field__icon--show')?.classList.toggle('hidden', showing);
        toggle.querySelector('.password-field__icon--hide')?.classList.toggle('hidden', ! showing);
    });
}

function initNavAccordions() {
    document.querySelectorAll('[data-nav-accordion]').forEach((accordion) => {
        const trigger = accordion.querySelector('[data-nav-accordion-trigger]');

        if (! trigger) {
            return;
        }

        trigger.addEventListener('click', () => {
            const shell = document.querySelector('[data-app-shell]');
            const isDesktop = window.matchMedia('(min-width: 1024px)').matches;

            if (isDesktop && shell?.classList.contains('is-sidebar-collapsed')) {
                shell.classList.remove('is-sidebar-collapsed');
                localStorage.setItem(SIDEBAR_STORAGE_KEY, '0');
                syncSidebarToggleState(shell);
                accordion.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');

                return;
            }

            const open = accordion.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
}

function syncSidebarToggleState(shell) {
    const isDesktop = window.matchMedia('(min-width: 1024px)').matches;
    const expanded = isDesktop
        ? ! shell.classList.contains('is-sidebar-collapsed')
        : shell.classList.contains('is-sidebar-open');

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');

        if (button.classList.contains('sidebar-toggle--collapse')) {
            button.setAttribute('aria-label', expanded ? 'Collapse navigation' : 'Expand navigation');
            button.setAttribute('title', expanded ? 'Collapse navigation' : 'Expand navigation');
        } else {
            button.setAttribute('aria-label', expanded ? 'Hide navigation' : 'Show navigation');
            button.setAttribute('title', expanded ? 'Hide navigation' : 'Show navigation');
        }
    });

    const backdrop = shell.querySelector('[data-sidebar-backdrop]');

    if (backdrop) {
        backdrop.hidden = isDesktop || ! shell.classList.contains('is-sidebar-open');
    }
}

function initSidebar() {
    const shell = document.querySelector('[data-app-shell]');

    if (! shell) {
        return;
    }

    const isDesktop = () => window.matchMedia('(min-width: 1024px)').matches;

    if (localStorage.getItem(SIDEBAR_STORAGE_KEY) === '1' && isDesktop()) {
        shell.classList.add('is-sidebar-collapsed');
    }

    syncSidebarToggleState(shell);

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            if (isDesktop()) {
                const collapsed = shell.classList.toggle('is-sidebar-collapsed');
                localStorage.setItem(SIDEBAR_STORAGE_KEY, collapsed ? '1' : '0');
                shell.classList.remove('is-sidebar-open');
            } else {
                shell.classList.toggle('is-sidebar-open');
            }

            syncSidebarToggleState(shell);
        });
    });

    shell.querySelector('[data-sidebar-backdrop]')?.addEventListener('click', () => {
        shell.classList.remove('is-sidebar-open');
        syncSidebarToggleState(shell);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && shell.classList.contains('is-sidebar-open')) {
            shell.classList.remove('is-sidebar-open');
            syncSidebarToggleState(shell);
        }
    });

    window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
        if (event.matches) {
            shell.classList.remove('is-sidebar-open');

            if (localStorage.getItem(SIDEBAR_STORAGE_KEY) === '1') {
                shell.classList.add('is-sidebar-collapsed');
            }
        } else {
            shell.classList.remove('is-sidebar-collapsed');
            shell.classList.remove('is-sidebar-open');
        }

        syncSidebarToggleState(shell);
    });
}

initPasswordToggles();
initNavAccordions();
initSidebar();
void initAiCharts();
void initDashboard();

