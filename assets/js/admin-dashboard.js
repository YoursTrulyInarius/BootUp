(() => {
    const buttons = document.querySelectorAll('.host-game-button');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const uploadInput = document.getElementById('source-upload');
    const dashboardMessage = document.getElementById('dashboard-message');

    function showDashboardMessage(text, isError) {
        dashboardMessage.textContent = text;
        dashboardMessage.className = `notice ${isError ? 'notice-error' : 'notice-success'}`;
        dashboardMessage.hidden = false;
    }

    if (uploadInput) {
        uploadInput.addEventListener('change', async () => {
            const file = uploadInput.files[0];
            if (!file) return;
            if (file.size === 0 || file.size > 20 * 1024 * 1024) {
                showDashboardMessage(file.size === 0 ? 'The selected file is empty.' : 'Files must be 20 MB or smaller.', true);
                uploadInput.value = '';
                return;
            }

            uploadInput.disabled = true;
            showDashboardMessage(`Saving ${file.name} to your private study library…`, false);
            const formData = new FormData();
            formData.append('source_file', file);
            try {
                const response = await fetch('/BootUp/api/source-files.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrf },
                    body: formData,
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.error || 'The file could not be imported.');
                showDashboardMessage(`${result.file.name} was added to your study library.`, false);
                window.setTimeout(() => location.reload(), 500);
            } catch (error) {
                showDashboardMessage(error.message || 'The file could not be imported. Check your connection and try again.', true);
                uploadInput.disabled = false;
                uploadInput.value = '';
            }
        });
    }

    document.querySelectorAll('.delete-source-file').forEach(button => {
        button.addEventListener('click', async () => {
            const fileName = button.dataset.fileName || 'this file';
            if (!window.confirm(`Delete ${fileName} from your library? The original file will be removed; existing quizzes will not change.`)) return;
            button.disabled = true;
            showDashboardMessage(`Deleting ${fileName}…`, false);
            try {
                const response = await fetch('/BootUp/api/delete-source-file.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                    body: JSON.stringify({ id: Number(button.dataset.fileId) }),
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.error || 'The file could not be deleted.');
                showDashboardMessage(`${fileName} was deleted.`, false);
                window.setTimeout(() => location.reload(), 400);
            } catch (error) {
                showDashboardMessage(error.message || 'The file could not be deleted.', true);
                button.disabled = false;
            }
        });
    });

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
                const message = document.getElementById('host-game-message');
                message.textContent = error.message || 'Could not open a game lobby. Please try again.';
                message.hidden = false;
                buttons.forEach(item => { item.disabled = false; });
                button.innerHTML = 'Host game <span aria-hidden="true">→</span>';
            }
        });
    });
})();
