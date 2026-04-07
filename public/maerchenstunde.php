<?php
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
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lideckes Märchenstunde – In Erinnerung an Franz Lidecke</title>
    <meta name="description" content="Videoaufnahmen von Franz Lideckes Märchenstunden – Märchen aus aller Welt, erzählt von Franz Lidecke, Märchenerzähler aus Bremerhaven.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://franz-lidecke.de/maerchenstunde.php">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="In Erinnerung an Franz Lidecke">
    <meta property="og:url" content="https://franz-lidecke.de/maerchenstunde.php">
    <meta property="og:title" content="Lideckes Märchenstunde – In Erinnerung an Franz Lidecke">
    <meta property="og:description" content="Videoaufnahmen von Franz Lideckes Märchenstunden – Märchen aus aller Welt, erzählt von Franz Lidecke.">
    <meta property="og:image" content="https://franz-lidecke.de/images/franz-lidecke-traueranzeige.jpeg">
    <meta property="og:locale" content="de_DE">
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
                <p class="in-erinnerung">In liebevoller Erinnerung</p>
                <h1 class="name">Franz Lidecke</h1>
                <p class="dates"><span class="date-symbol">*</span> 14. November 1937 &nbsp;&nbsp; <span class="date-symbol">&#8224;</span> 3. April 2026</p>
                <p class="subtitle">Märchenerzähler &middot; Lehrer &middot; Buchautor</p>
            </div>
            <div class="header-photo">
                <img src="/images/franz-lidecke-ausgeschnitten-removebg.png" alt="Franz Lidecke">
            </div>
        </div>
    </a>
    <nav class="site-nav">
        <a href="/" class="site-nav-link">Kondolenzbuch</a>
        <a href="/maerchenstunde.php" class="site-nav-link active">Märchenstunde</a>
        <a href="/maerchen-und-tanz.php" class="site-nav-link">Märchen &amp; Tanz</a>
    </nav>
</header>

<main>

    <section class="video-section">

        <p class="video-back-link"><a href="/">&larr; Zurück zur Gedenkseite</a></p>
        <h2>Lideckes Märchenstunde</h2>
        <p class="video-intro">Franz Lidecke erzählte Märchen aus aller Welt – für Menschen von 4 bis 90 Jahren. Diese Aufnahmen bewahren seine Stimme und sein Können als Märchenerzähler.</p>

        <div class="plyr-container">
            <video id="player" playsinline controls>
                <source src="<?= h($src) ?>" type="video/x-matroska">
                Ihr Browser unterstützt keine Videowiedergabe.
            </video>
        </div>
        <p class="video-title"><?= h($current['title']) ?></p>
        <p class="video-meta">Folge <?= $active + 1 ?> von <?= $count ?><?php if ($hasNext): ?> &nbsp;&middot;&nbsp; Weiter: <?= h($videos[$next]['title']) ?><?php endif; ?></p>
        <p class="video-compat-note">Die Videos liegen im MKV-Format vor und werden zuverlässig in Chrome und Edge abgespielt. Firefox und Safari unterstützen dieses Format in der Regel nicht.</p>

        <aside class="video-sidebar">
            <p class="playlist-heading">Alle Folgen</p>
            <ol class="playlist">
                    <?php foreach ($videos as $i => $video): ?>
                    <li class="playlist-item<?= $i === $active ? ' active' : '' ?>">
                        <a href="/maerchenstunde.php?v=<?= $i ?>">
                            <span class="playlist-num"><?= $i + 1 ?></span>
                            <span class="playlist-label"><?= h($video['title']) ?></span>
                            <?php if ($i === $active): ?>
                            <span class="playlist-playing" aria-label="wird abgespielt">&#9654;</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </aside>

    </section>

</main>

<footer>
    <p><a href="/impressum.php">Impressum</a> &nbsp;&middot;&nbsp; <a href="https://github.com/Joshua2504/in-erinnerung-an-franz-lidecke/" target="_blank" rel="noopener">GitHub</a></p>
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
        window.location.href = '/maerchenstunde.php?v=<?= $next ?>';
    });
    <?php endif; ?>
})();
</script>

</body>
</html>
