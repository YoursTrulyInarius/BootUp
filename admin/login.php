<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: /BootUp/admin/quizzes.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Your form expired. Please reload and try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $statement = db()->prepare('SELECT id, password_hash FROM admins WHERE username = :username');
        $statement->execute(['username' => $username]);
        $admin = $statement->fetch();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = $username;
            unset($_SESSION['csrf_token']);
            header('Location: /BootUp/admin/quizzes.php');
            exit;
        }
        $error = 'The username or password was not recognized.';
    }
}
$adminCount = (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Host sign in — BootUp</title>
    <link rel="stylesheet" href="/BootUp/assets/css/app.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <a class="brand" href="/BootUp/">BOOTUP<span>.</span></a>
        <p class="eyebrow">HOST CONSOLE</p>
        <h1>Welcome back.</h1>
        <p class="muted">Sign in to create and customize your quizzes.</p>
        <?php if (isset($_GET['created'])): ?><div class="notice notice-success">Account created. You can sign in now.</div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="notice notice-error"><?= h($error) ?></div><?php endif; ?>
        <form method="post" class="stack-form">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <label>Username
                <input name="username" required autocomplete="username">
            </label>
            <label>Password
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button class="button button-primary button-full" type="submit">Sign in</button>
        </form>
        <?php if ($adminCount === 0): ?>
            <p class="auth-footnote">New to BootUp? <a href="/BootUp/admin/create-admin.php">Set up the first host account</a>.</p>
        <?php endif; ?>
    </main>
</body>
</html>
