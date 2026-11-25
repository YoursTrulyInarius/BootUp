<?php
declare(strict_types=1);
require __DIR__ . '/config/bootstrap.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
    <title>Join a game — BootUp</title>
    <script src="/BootUp/assets/js/theme.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX" crossorigin="anonymous">
    <link rel="stylesheet" href="/BootUp/assets/css/app.css">
    <script defer src="/BootUp/assets/js/join-game.js"></script>
</head>
<body class="home-page">
    <header class="site-header home-header">
        <a class="brand" href="/BootUp/">BOOTUP<span>.</span></a>
        <div class="home-header-actions d-flex align-items-center">
            <a class="host-link" href="/BootUp/admin/login.php"><span class="home-host-preface">Hosting a quiz?</span> <strong>Host sign in <span aria-hidden="true">↗</span></strong></a>
        </div>
    </header>
    <main class="home-layout container-fluid">
        <section class="home-copy">
            <div class="home-kicker"><span class="home-kicker-dot"></span> THE MULTIPLAYER QUIZ ARENA</div>
            <h1>Bring your<br>brain <span>game.</span></h1>
            <p class="home-description">Big questions. Friendly competition. Join your crew and see what you know.</p>
            <form id="join-form" class="home-pin-form" novalidate>
                <label for="game-code">ENTER YOUR GAME PIN</label>
                <div class="home-pin-row">
                    <input class="form-control" id="game-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                           pattern="[0-9]{6}" maxlength="6" placeholder="000000" aria-describedby="code-help join-message" required>
                    <button class="button button-primary home-join-button" id="join-button" type="submit" disabled>
                        Join game <span aria-hidden="true">→</span>
                    </button>
                </div>
                <p id="code-help" class="home-code-help"><span aria-hidden="true">✦</span> Six digits from your host. No account needed.</p>
                <p id="join-message" class="join-message" role="status" aria-live="polite" hidden></p>
            </form>
            <p class="home-footnote"><span class="home-footnote-icon" aria-hidden="true">↳</span> Your host will be waiting in the lobby.</p>
        </section>

        <section class="character-stage" aria-label="BootUp quiz characters">
            <div class="stage-orbit stage-orbit-one" aria-hidden="true"></div>
            <div class="stage-orbit stage-orbit-two" aria-hidden="true"></div>
            <div class="stage-spark spark-one" aria-hidden="true">✦</div>
            <div class="stage-spark spark-two" aria-hidden="true">+</div>
            <div class="stage-spark spark-three" aria-hidden="true">✳</div>
            <figure class="character-card character-robot">
                <img src="/BootUp/assets/images/char-cutout.png" width="395" height="592" alt="" fetchpriority="high">
                <figcaption><span class="character-dot"></span> READY PLAYER ONE</figcaption>
            </figure>
            <figure class="character-card character-ech">
                <img src="/BootUp/assets/images/ech-cutout.png" width="238" height="427" alt="" loading="lazy">
                <figcaption>LEVEL UP <span aria-hidden="true">↗</span></figcaption>
            </figure>
            <figure class="character-card character-nov">
                <img src="/BootUp/assets/images/nov-cutout.png" width="252" height="619" alt="" loading="lazy">
                <figcaption>GOOD LUCK, HAVE FUN</figcaption>
            </figure>
            <figure class="character-card character-one">
                <img src="/BootUp/assets/images/1-cutout.png" width="262" height="462" alt="" loading="lazy">
            </figure>
            <figure class="character-card character-two">
                <img src="/BootUp/assets/images/2-cutout.png" width="339" height="475" alt="" loading="lazy">
            </figure>
            <div class="stage-caption"><span>YOUR CREW IS HERE</span><span class="caption-line"></span><span>LET'S PLAY</span></div>
        </section>
    </main>
    <div class="home-background-glow" aria-hidden="true"></div>
</body>
</html>
