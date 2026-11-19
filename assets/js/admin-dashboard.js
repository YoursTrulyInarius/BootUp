(() => {
    const buttons = document.querySelectorAll('.host-game-button');
    const message = document.getElementById('host-game-message');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    buttons.forEach(button => {
        button.addEventListener('click', async () => {
            buttons.forEach(item => { item.disabled = true; });
            button.textContent = 'Opening lobby…';
            message.hidden = true;
            try {
                const response = await fetch('/BootUp/api/create-game.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                    body: JSON.stringify({ quiz_id: Number(button.dataset.quizId) }),
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.error || 'Could not open a game lobby.');
                location.href = result.redirect;
            } catch (error) {
                message.textContent = error.message || 'Could not open a game lobby. Please try again.';
                message.hidden = false;
                buttons.forEach(item => { item.disabled = false; });
                button.innerHTML = 'Host game <span aria-hidden="true">→</span>';
            }
        });
    });
})();
