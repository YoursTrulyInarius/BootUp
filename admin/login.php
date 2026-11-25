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
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Host sign in — BootUp</title>
    <script src="/BootUp/assets/js/theme.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX" crossorigin="anonymous">
    <link rel="stylesheet" href="/BootUp/assets/css/app.css">
    <script defer src="/BootUp/assets/js/password-toggle.js"></script>
</head>
<body class="auth-page login-screen">
    <main class="login-layout container-fluid">
        <section class="auth-card login-card">
            <div class="auth-card-top">
                <a class="brand" href="/BootUp/">BOOTUP<span>.</span></a>
                <a class="auth-nav-link" href="/BootUp/" aria-label="Return to homepage">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z"></path>
                    </svg>
                    <span>Home</span>
                </a>
            </div>
            <p class="eyebrow">HOST CONSOLE</p>
            <h1>Welcome back.</h1>
            <p class="muted">Sign in to create and customize your quizzes.</p>
            <?php if (isset($_GET['created'])): ?><div class="notice notice-success">Account created. You can sign in now.</div><?php endif; ?>
            <?php if ($error !== ''): ?><div class="notice notice-error"><?= h($error) ?></div><?php endif; ?>
            <form method="post" class="stack-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <label>Username
                    <input class="form-control" name="username" required autocomplete="username">
                </label>
                <div class="field-group">
                    <label for="admin-password">Password</label>
                    <span class="password-field">
                        <input class="form-control" id="admin-password" type="password" name="password" required autocomplete="current-password">
                        <button class="password-toggle" type="button" data-password-toggle aria-controls="admin-password" aria-label="Show password" aria-pressed="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                                <path class="eye-slash" d="m3 3 18 18"></path>
                            </svg>
                        </button>
                    </span>
                </div>
                <button class="button button-primary button-full" type="submit">Sign in</button>
            </form>
            <p class="auth-footnote">New to BootUp? <a href="/BootUp/admin/create-admin.php">Create a host account</a>.</p>
        </section>
        <aside class="login-world" aria-label="Build and host a BootUp quiz">
            <div class="world-topline"><span>BOOTUP WORLD</span><span>HOST MODE · 01</span></div>
            <div class="world-scene" aria-hidden="true">
                <span class="world-sun"></span>
                <span class="world-cloud world-cloud-one"></span>
                <span class="world-cloud world-cloud-two"></span>
                <span class="world-cube world-cube-one"></span>
                <span class="world-cube world-cube-two"></span>
                <img src="/BootUp/assets/images/char-cutout.png" alt="" width="395" height="592" loading="lazy">
                <div class="world-ground"></div>
            </div>
            <div class="world-copy">
                <p class="eyebrow">YOUR NEXT ADVENTURE</p>
                <h2>Build a world<br>of questions.</h2>
                <p>Create a quiz, gather your crew, and make learning a game.</p>
                <div class="world-steps"><span>CREATE</span><i></i><span>HOST</span><i></i><span>PLAY</span></div>
            </div>
        </aside>
    </main>
</body>
</html>
