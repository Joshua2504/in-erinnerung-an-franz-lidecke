<?php require_once __DIR__ . '/lang.php'; ?>
<!DOCTYPE html>
<html lang="<?= $LANG ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(t('meta_title_tales'), ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars(t('meta_desc_tales'), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://franz-lidecke.de/maerchen-und-tanz">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= htmlspecialchars(t('og_site_name'), ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="https://franz-lidecke.de/maerchen-und-tanz">
    <meta property="og:title" content="<?= htmlspecialchars(t('meta_title_tales'), ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars(t('meta_desc_tales'), ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="https://franz-lidecke.de/images/franz-lidecke-traueranzeige.jpeg">
    <meta property="og:locale" content="<?= $ogLocale ?>">
    <link rel="stylesheet" href="style.css">
</head>
<body class="body-iframe">

<header class="site-header">
    <a href="/" class="header-home-link">
        <div class="header-inner">
            <div class="header-text">
                <p class="in-erinnerung"><?= t('header_in_loving_memory') ?></p>
                <h1 class="name">Franz Lidecke</h1>
                <p class="dates"><span class="date-symbol">*</span> 14. November 1937 &nbsp;&nbsp; <span class="date-symbol">&#8224;</span> 3. April 2026</p>
                <p class="subtitle"><?= t('header_subtitle') ?></p>
            </div>
            <div class="header-photo">
                <img src="/images/franz-lidecke-ausgeschnitten-removebg.png" alt="Franz Lidecke">
            </div>
        </div>
    </a>
    <nav class="site-nav">
        <a href="/" class="site-nav-link"><?= t('nav_guestbook') ?></a>
        <a href="/maerchenstunde" class="site-nav-link"><?= t('nav_story_hour') ?></a>
        <a href="/maerchen-und-tanz" class="site-nav-link active"><?= t('nav_tales_dance') ?></a>
        <?= lang_switcher() ?>
    </nav>
</header>

<main class="main-iframe">
    <iframe src="/maerchen-und-tanz/" title="<?= htmlspecialchars(t('tales_iframe_title'), ENT_QUOTES, 'UTF-8') ?>" allowfullscreen></iframe>
</main>

<footer>
    <p><a href="/impressum"><?= htmlspecialchars(t('footer_legal'), ENT_QUOTES, 'UTF-8') ?></a> &nbsp;&middot;&nbsp; <a href="https://github.com/Joshua2504/in-erinnerung-an-franz-lidecke/" target="_blank" rel="noopener">GitHub</a></p>
</footer>

</body>
</html>
