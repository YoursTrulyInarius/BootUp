(() => {
    const storageKey = 'bootup-theme';
    const root = document.documentElement;
    const savedTheme = localStorage.getItem(storageKey);
    root.dataset.theme = savedTheme === 'light' ? 'light' : 'dark';
    root.dataset.bsTheme = root.dataset.theme;

    function renderToggle(button) {
        const isLight = root.dataset.theme === 'light';
        const nextTheme = isLight ? 'dark' : 'light';
        button.setAttribute('aria-label', `Switch to ${nextTheme} mode`);
        button.setAttribute('title', `Switch to ${nextTheme} mode`);
        button.setAttribute('aria-pressed', String(isLight));
        button.innerHTML = isLight
            ? `<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.2 15.4A8.5 8.5 0 0 1 8.6 3.8 8.5 8.5 0 1 0 20.2 15.4Z"/></svg><span>${nextTheme} mode</span>`
            : `<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg><span>${nextTheme} mode</span>`;
    }

    function animateToggle(button) {
        button.classList.remove('is-switching');
        void button.offsetWidth;
        button.classList.add('is-switching');
        window.setTimeout(() => button.classList.remove('is-switching'), 460);
    }

    function applyTheme(theme, persist = false) {
        root.dataset.theme = theme;
        root.dataset.bsTheme = theme;
        if (persist) localStorage.setItem(storageKey, theme);
        document.querySelectorAll('.theme-toggle').forEach(button => {
            renderToggle(button);
            animateToggle(button);
        });
    }

    function mountToggle() {
        const button = document.createElement('button');
        button.className = 'theme-toggle';
        button.type = 'button';
        button.addEventListener('click', () => {
            applyTheme(root.dataset.theme === 'light' ? 'dark' : 'light', true);
        });

        const homeActions = document.querySelector('.home-header-actions');
        const header = document.querySelector('.home-header');
        const dashboardActions = document.querySelector('.dashboard-heading-actions');
        const headerActions = document.querySelector('.header-actions');
        const authTop = document.querySelector('.auth-card-top');
        const joinHeader = document.querySelector('.join-header');
        const authCard = document.querySelector('.auth-card');

        if (homeActions) {
            homeActions.append(button);
        } else if (header) {
            const actions = document.createElement('div');
            actions.className = 'home-header-actions d-flex align-items-center';
            const link = header.querySelector('.host-link');
            link.replaceWith(actions);
            actions.append(link, button);
        } else if (dashboardActions) {
            dashboardActions.append(button);
        } else if (headerActions) {
            headerActions.append(button);
        } else if (authTop) {
            const actions = document.createElement('div');
            actions.className = 'auth-card-actions d-flex align-items-center';
            const link = authTop.querySelector('.auth-nav-link');
            link.replaceWith(actions);
            actions.append(link, button);
        } else if (joinHeader) {
            const actions = document.createElement('div');
            actions.className = 'join-header-actions d-flex align-items-center';
            const link = joinHeader.querySelector('.host-link');
            link.replaceWith(actions);
            actions.append(link, button);
        } else if (authCard) {
            authCard.querySelector('.brand').after(button);
        } else {
            document.body.append(button);
        }

        renderToggle(button);
    }

    window.addEventListener('storage', event => {
        if (event.key === storageKey && (event.newValue === 'light' || event.newValue === 'dark')) {
            applyTheme(event.newValue);
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountToggle, { once: true });
    } else {
        mountToggle();
    }
})();
