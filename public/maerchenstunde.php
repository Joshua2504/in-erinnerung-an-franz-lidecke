<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/stats.php';

$videos = [
    ['title' => 'Liebe in Märchen',                'file' => 'Lideckes Märchenstunde - Liebe in Märchen.mkv'],
    ['title' => 'Märchen der Brüder Grimm',         'file' => 'Lideckes Märchenstunde - Märchen der Brüder Grimm.mkv'],
    ['title' => 'Märchen der Indianer Amerikas',    'file' => 'Lideckes Märchenstunde - Märchen der Indianer Amerikas.mkv'],
    ['title' => 'Märchen für Kinder',               'file' => 'Lideckes Märchenstunde - Märchen für Kinder.mkv'],
    ['title' => 'Märchen mit Tieren',               'file' => 'Lideckes Märchenstunde - Märchen mit Tieren.mkv'],
    ['title' => 'Märchen von Geistern und Dämonen', 'file' => 'Lideckes Märchenstunde - Märchen von Geistern und Dämonen.mkv'],
    ['title' => 'Märchen von mutigen Tieren',       'file' => 'Lideckes Märchenstunde - Märchen von mutigen Tieren.mkv'],
    ['title' => 'Russische Märchen',                'file' => 'Lideckes Märchenstunde - Russische Märchen.mkv'],
    ['title' => 'Türkische Märchen',                'file' => 'Lideckes Märchenstunde - Türkische Märchen.mkv'],
    ['title' => 'Märchentruhe: Reise um die Welt',  'file' => 'Lideckes Märchentruhe - Reise um die Welt.mkv'],
];

$count   = count($videos);
$active  = isset($_GET['v']) ? (int)$_GET['v'] : 0;
$active  = max(0, min($active, $count - 1));
$next    = $active + 1;
$hasNext = $next < $count;
$current = $videos[$active];
$cdnBase = 'https://cdn.treudler.net/26/lidecke/maerchenstunde/';
$src     = $cdnBase . rawurlencode($current['file']);

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="<?= $LANG ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(t('meta_title_video')) ?></title>
    <meta name="description" content="<?= h(t('meta_desc_video')) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://franz-lidecke.de/maerchenstunde">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= h(t('og_site_name')) ?>">
    <meta property="og:url" content="https://franz-lidecke.de/maerchenstunde">
    <meta property="og:title" content="<?= h(t('meta_title_video')) ?>">
    <meta property="og:description" content="<?= h(t('meta_desc_video')) ?>">
    <meta property="og:image" content="https://franz-lidecke.de/images/franz-lidecke-traueranzeige.jpeg">
    <meta property="og:locale" content="<?= $ogLocale ?>">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.css">
    <style>
        :root {
            --plyr-color-main: #3a3028;
            --plyr-video-background: #1a1410;
            --plyr-range-fill-background: #3a3028;
        }
        .plyr--video {
            border-radius: 2px;
        }
    </style>
</head>
<body>

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
        <a href="/maerchenstunde" class="site-nav-link active"><?= t('nav_story_hour') ?></a>
        <?= lang_switcher() ?>
    </nav>
</header>

<main class="main-wide">

    <section class="video-section">

        <p class="video-back-link"><a href="/"><?= t('video_back_link') ?></a></p>
        <h2><?= h(t('video_heading')) ?></h2>
        <p class="video-intro"><?= h(t('video_intro')) ?></p>

        <div class="plyr-container">
            <video id="player" playsinline controls>
                <source src="<?= h($src) ?>" type="video/x-matroska">
                <?= h(t('video_no_support')) ?>
            </video>
        </div>
        <p class="video-title"><?= h($current['title']) ?></p>
        <p class="video-meta">
            <?= sprintf('%s %d %s %d',
                $LANG === 'en' ? 'Episode' : 'Folge',
                $active + 1,
                $LANG === 'en' ? 'of' : 'von',
                $count
            ) ?>
            <?php if ($hasNext): ?>
                &nbsp;&middot;&nbsp; <?= h(t('video_next')) ?> <?= h($videos[$next]['title']) ?>
            <?php endif; ?>
        </p>
        <p class="video-compat-note"><?= h(t('video_compat_note')) ?></p>

        <aside class="video-sidebar">
            <p class="playlist-heading"><?= h(t('video_playlist_heading')) ?></p>
            <ol class="playlist">
                    <?php foreach ($videos as $i => $video): ?>
                    <li class="playlist-item<?= $i === $active ? ' active' : '' ?>">
                        <a href="/maerchenstunde?v=<?= $i ?>">
                            <span class="playlist-num"><?= $i + 1 ?></span>
                            <span class="playlist-label"><?= h($video['title']) ?></span>
                            <?php if ($i === $active): ?>
                            <span class="playlist-playing" aria-label="<?= h(t('video_playing_aria')) ?>">&#9654;</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </aside>

    </section>

</main>

<?php $footerStats = get_footer_stats(); ?>
<footer>
    <p><a href="/impressum"><?= h(t('footer_legal')) ?></a> &nbsp;&middot;&nbsp; <a href="https://github.com/Joshua2504/in-erinnerung-an-franz-lidecke/" target="_blank" rel="noopener">GitHub</a></p>
    <p style="margin-top:6px;font-size:0.78rem;"><?= number_format($footerStats['hits'], 0, ',', '.') ?> <?= h(t('footer_hits')) ?> &nbsp;&middot;&nbsp; <?= number_format($footerStats['unique'], 0, ',', '.') ?> <?= h(t('footer_unique')) ?></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.js"></script>
<script>
(function () {
    var player = new Plyr('#player', {
        controls: ['play-large', 'play', 'rewind', 'fast-forward', 'progress', 'current-time', 'duration', 'mute', 'volume', 'fullscreen'],
        keyboard: { focused: true, global: true },
        tooltips: { controls: false, seek: true },
        speed: { selected: 1, options: [0.75, 1, 1.25, 1.5] },
    });

    <?php if ($hasNext): ?>
    player.on('ended', function () {
        window.location.href = '/maerchenstunde?v=<?= $next ?>';
    });
    <?php endif; ?>
})();
</script>

</body>
</html>
