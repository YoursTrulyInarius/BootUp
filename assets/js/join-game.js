(() => {
    const form = document.getElementById('join-form');
    const input = document.getElementById('game-code');
    const button = document.getElementById('join-button');
    const message = document.getElementById('join-message');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    input.addEventListener('input', () => {
        input.value = input.value.replace(/\D/g, '').slice(0, 6);
        button.disabled = input.value.length !== 6;
        input.removeAttribute('aria-invalid');
        message.hidden = true;
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!/^\d{6}$/.test(input.value)) {
            input.setAttribute('aria-invalid', 'true');
            message.textContent = 'Enter all six digits from your host.';
            message.className = 'join-message is-error';
            message.hidden = false;
            input.focus();
            return;
        }

        button.disabled = true;
        button.innerHTML = 'Checking PIN…';
        try {
            const response = await fetch('/BootUp/api/join-game.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify({ code: input.value }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Could not join this game.');
            location.href = result.redirect;
        } catch (error) {
            message.textContent = error.message || 'Could not connect. Please try again.';
            message.className = 'join-message is-error';
            message.hidden = false;
            button.disabled = false;
            button.innerHTML = 'Join game <span aria-hidden="true">→</span>';
            input.focus();
        }
    });
})();
