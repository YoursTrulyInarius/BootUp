<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';

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
            try {
                $statement = db()->prepare(
                    'INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)'
                );
                $statement->execute([
                    'username' => $username,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                header('Location: /BootUp/admin/login.php?created=1');
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $error = 'That username is already taken.';
            }
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
    <script src="/BootUp/assets/js/theme.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX" crossorigin="anonymous">
    <link rel="stylesheet" href="/BootUp/assets/css/app.css">
    <script defer src="/BootUp/assets/js/password-toggle.js"></script>
</head>
<body class="auth-page">
    <main class="auth-card">
        <div class="auth-card-top">
            <a class="brand" href="/BootUp/">BOOTUP<span>.</span></a>
            <a class="auth-nav-link" href="/BootUp/admin/login.php" aria-label="Back to sign in">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M19 12H5m7 7-7-7 7-7"></path>
                </svg>
                <span>Back</span>
            </a>
        </div>
        <p class="eyebrow">HOST REGISTRATION</p>
        <h1>Create your host account</h1>
        <p class="muted">Create an account to build and host your own quizzes.</p>
        <?php if ($error !== ''): ?><div class="notice notice-error"><?= h($error) ?></div><?php endif; ?>
        <form method="post" class="stack-form">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <label>Username
                <input class="form-control" name="username" required minlength="3" maxlength="32" autocomplete="username">
            </label>
            <div class="field-group">
                <label for="new-admin-password">Password</label>
                <span class="password-field">
                    <input class="form-control" id="new-admin-password" type="password" name="password" required minlength="12" autocomplete="new-password">
                    <button class="password-toggle" type="button" data-password-toggle aria-controls="new-admin-password" aria-label="Show password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                            <path class="eye-slash" d="m3 3 18 18"></path>
                        </svg>
                    </button>
                </span>
                <small>Use at least 12 characters.</small>
            </div>
            <button class="button button-primary button-full" type="submit">Create account</button>
        </form>
    </main>
</body>
</html>
