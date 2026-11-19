<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';

$adminCount = (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($adminCount > 0) {
    header('Location: /BootUp/admin/login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Your form expired. Please reload and try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,32}$/', $username)) {
            $error = 'Use 3–32 letters, numbers, dots, dashes, or underscores for the username.';
        } elseif (strlen($password) < 12) {
            $error = 'Choose a password with at least 12 characters.';
        } else {
            $statement = db()->prepare(
                'INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)'
            );
            $statement->execute([
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            header('Location: /BootUp/admin/login.php?created=1');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create host account — BootUp</title>
    <link rel="stylesheet" href="/BootUp/assets/css/app.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <a class="brand" href="/BootUp/">BOOTUP<span>.</span></a>
        <p class="eyebrow">FIRST-TIME SETUP</p>
        <h1>Create your host account</h1>
        <p class="muted">This setup page is available only until the first account is created.</p>
        <?php if ($error !== ''): ?><div class="notice notice-error"><?= h($error) ?></div><?php endif; ?>
        <form method="post" class="stack-form">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <label>Username
                <input name="username" required minlength="3" maxlength="32" autocomplete="username">
            </label>
            <label>Password
                <input type="password" name="password" required minlength="12" autocomplete="new-password">
                <small>Use at least 12 characters.</small>
            </label>
            <button class="button button-primary button-full" type="submit">Create account</button>
        </form>
    </main>
</body>
</html>
