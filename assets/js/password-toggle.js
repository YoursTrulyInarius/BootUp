(() => {
    document.querySelectorAll('[data-password-toggle]').forEach(toggle => {
        const targetId = toggle.getAttribute('aria-controls');
        const input = targetId ? document.getElementById(targetId) : null;
        if (!(input instanceof HTMLInputElement)) {
            throw new Error('Password visibility toggle is missing its input.');
        }

        toggle.addEventListener('click', () => {
            const isVisible = input.type === 'password';
            input.type = isVisible ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', String(isVisible));
            toggle.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
            toggle.classList.toggle('is-visible', isVisible);
        });
    });
})();
