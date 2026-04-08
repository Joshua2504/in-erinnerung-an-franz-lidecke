<?php require_once __DIR__ . '/lang.php'; require_once __DIR__ . '/stats.php'; ?>
<!DOCTYPE html>
<html lang="<?= $LANG ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(t('meta_title_impressum'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="style.css">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>

<header class="site-header">
    <a href="/" class="header-home-link">
        <div class="header-inner">
            <div class="header-text">
                <p class="in-erinnerung"><?= t('header_in_loving_memory') ?></p>
                <h1 class="name">Franz Lidecke</h1>
                <p class="dates"><span class="date-symbol">*</span> 14. November 1937 &nbsp;&nbsp; <span class="date-symbol">&#8224;</span> 3. April 2026</p>
            </div>
            <div class="header-photo">
                <img src="/images/franz-lidecke-ausgeschnitten-removebg.png" alt="Franz Lidecke">
            </div>
        </div>
    </a>
    <nav class="site-nav">
        <a href="/" class="site-nav-link"><?= t('nav_guestbook') ?></a>
        <a href="/maerchenstunde" class="site-nav-link"><?= t('nav_story_hour') ?></a>
        <?= lang_switcher() ?>
    </nav>
</header>

<main>
    <section>
        <h2><?= htmlspecialchars(t('impressum_heading'), ENT_QUOTES, 'UTF-8') ?></h2>
        <p>
            Joshua Tobias Treudler<br>
            Hinter der Schönen Aussicht 10<br>
            DE &ndash; 60311 Frankfurt am Main<br>
            <span class="email-obf" data-u="joshua" data-d="treudler.net"></span>
            <noscript><em>E-Mail: joshua [at] treudler.net</em></noscript>
        </p>
        <p style="margin-top: 24px;">
            <a href="/" style="color: var(--color-accent);"><?= t('impressum_back_link') ?></a>
        </p>
    </section>

    <section>
        <h2><?= htmlspecialchars(t('privacy_heading'), ENT_QUOTES, 'UTF-8') ?></h2>
        <h3><?= htmlspecialchars(t('privacy_controller_heading'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p>
            Joshua Tobias Treudler<br>
            Hinter der Schönen Aussicht 10<br>
            DE &ndash; 60311 Frankfurt am Main
        </p>
        <h3><?= htmlspecialchars(t('privacy_data_collection_heading'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p><?= htmlspecialchars(t('privacy_data_collection_text'), ENT_QUOTES, 'UTF-8') ?></p>
        <h3><?= htmlspecialchars(t('privacy_guestbook_heading'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p><?= htmlspecialchars(t('privacy_guestbook_text'), ENT_QUOTES, 'UTF-8') ?></p>
        <h3><?= htmlspecialchars(t('privacy_no_sharing_heading'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p><?= htmlspecialchars(t('privacy_no_sharing_text'), ENT_QUOTES, 'UTF-8') ?></p>
        <h3><?= t('privacy_ga_heading') ?></h3>
        <p><?= t('privacy_ga_text') ?></p>
        <h3><?= htmlspecialchars(t('privacy_rights_heading'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p><?= htmlspecialchars(t('privacy_rights_text'), ENT_QUOTES, 'UTF-8') ?></p>
    </section>
</main>

<?php $footerStats = get_footer_stats(); ?>
<footer>
    <p><a href="/impressum"><?= htmlspecialchars(t('footer_legal'), ENT_QUOTES, 'UTF-8') ?></a> &nbsp;&middot;&nbsp; <a href="https://github.com/Joshua2504/in-erinnerung-an-franz-lidecke/" target="_blank" rel="noopener">GitHub</a></p>
    <p style="margin-top:6px;font-size:0.78rem;"><?= number_format($footerStats['hits'], 0, ',', '.') ?> <?= htmlspecialchars(t('footer_hits'), ENT_QUOTES, 'UTF-8') ?> &nbsp;&middot;&nbsp; <?= number_format($footerStats['unique'], 0, ',', '.') ?> <?= htmlspecialchars(t('footer_unique'), ENT_QUOTES, 'UTF-8') ?></p>
</footer>

<script>
document.querySelectorAll('.email-obf').forEach(function(el) {
    var e = el.dataset.u + '\u0040' + el.dataset.d;
    el.innerHTML = '<a href="mailto:' + e + '">' + e + '</a>';
});
</script>
</body>
</html>
