<?php
session_start();
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/stats.php';

// ── Database ───────────────────────────────────────────────────────────────
$dbPath = '/var/www/data/guestbook.db';
if (!is_dir(dirname($dbPath))) {
    mkdir(dirname($dbPath), 0755, true);
}
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('PRAGMA journal_mode=WAL');
$db->exec('CREATE TABLE IF NOT EXISTS entries (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    name         TEXT    NOT NULL,
    email        TEXT,
    message      TEXT    NOT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    cookie_token TEXT,
    approved     INTEGER DEFAULT 0,
    anonymous    INTEGER DEFAULT 0
)');
$db->exec('CREATE TABLE IF NOT EXISTS entry_images (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    entry_id  INTEGER NOT NULL,
    filename  TEXT    NOT NULL,
    FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE CASCADE
)');

$db->exec('CREATE TABLE IF NOT EXISTS page_views (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    visitor_token TEXT,
    visited_at    DATETIME DEFAULT CURRENT_TIMESTAMP
)');

// Migration for existing databases
try { $db->exec('ALTER TABLE entries ADD COLUMN anonymous INTEGER DEFAULT 0'); } catch (PDOException $e) {}
try { $db->exec('ALTER TABLE page_views ADD COLUMN visitor_token TEXT'); } catch (PDOException $e) {}

// ── Visitor cookie ─────────────────────────────────────────────────────────
if (empty($_COOKIE['visitor_token'])) {
    $token = bin2hex(random_bytes(16));
    setcookie('visitor_token', $token, [
        'expires'  => time() + 365 * 24 * 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['visitor_token'] = $token;
}
$visitorToken = $_COOKIE['visitor_token'];

// ── Track page view ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare('INSERT INTO page_views (visitor_token) VALUES (?)');
    $stmt->execute([$visitorToken]);
}


// ── Helpers ────────────────────────────────────────────────────────────────
function send_mail(string $to, string $subject, string $body): void {
    $vendor = '/app/vendor/autoload.php';
    if (!file_exists($vendor)) return;
    require_once $vendor;

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $dsn   = getenv('MAILER') ?: '';
        $at    = strrpos($dsn, '@');
        $host  = $at !== false ? substr($dsn, $at + 1) : '';
        $creds = $at !== false ? substr($dsn, 0, $at) : ':';
        $colon = strpos($creds, ':');
        $muser = $colon !== false ? substr($creds, 0, $colon) : $creds;
        $mpass = $colon !== false ? substr($creds, $colon + 1) : '';

        if (!$host || !$muser) return;

        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $muser;
        $mail->Password   = $mpass;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($muser, 'Gedenkseite Franz Lidecke');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $body;
        $mail->send();
    } catch (Exception $e) {
        error_log('Mailer error: ' . $mail->ErrorInfo);
    }
}

function make_thumbnail(string $src, string $dest, int $maxDim = 400): bool {
    $info = @getimagesize($src);
    if (!$info) return false;

    [$w, $h, $type] = $info;
    $img = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
        IMAGETYPE_PNG  => @imagecreatefrompng($src),
        IMAGETYPE_GIF  => @imagecreatefromgif($src),
        IMAGETYPE_WEBP => @imagecreatefromwebp($src),
        default        => false,
    };
    if (!$img) return false;

    $ratio = min($maxDim / $w, $maxDim / $h, 1.0);
    $nw    = (int) round($w * $ratio);
    $nh    = (int) round($h * $ratio);

    $thumb = imagecreatetruecolor($nw, $nh);
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    imagecopyresampled($thumb, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

    return (bool) match ($type) {
        IMAGETYPE_JPEG => imagejpeg($thumb, $dest, 85),
        IMAGETYPE_PNG  => imagepng($thumb, $dest),
        IMAGETYPE_GIF  => imagegif($thumb, $dest),
        IMAGETYPE_WEBP => imagewebp($thumb, $dest, 85),
        default        => false,
    };
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ── POST handler ───────────────────────────────────────────────────────────
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting per cookie
    $stmt = $db->prepare('SELECT COUNT(*) FROM entries WHERE cookie_token = ? AND created_at > datetime("now", "-1 hour")');
    $stmt->execute([$visitorToken]);
    if ((int) $stmt->fetchColumn() >= 3) {
        $errors[] = t('err_rate_limit');
    }

    // Fields
    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $message   = trim($_POST['message'] ?? '');
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;

    if (mb_strlen($name) < 2)       $errors[] = t('err_name_short');
    if (mb_strlen($name) > 100)     $errors[] = t('err_name_long');
    if (mb_strlen($message) < 5)    $errors[] = t('err_message_short');
    if (mb_strlen($message) > 2000) $errors[] = t('err_message_long');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('err_email_invalid');
    }

    if (empty($errors)) {
        $stmt = $db->prepare('INSERT INTO entries (name, email, message, cookie_token, approved, anonymous) VALUES (?, ?, ?, ?, 1, ?)');
        $stmt->execute([$name, $email ?: null, $message, $visitorToken, $anonymous]);
        $entryId = (int) $db->lastInsertId();

        // Handle image uploads
        $uploadDir    = '/var/www/html/uploads/';
        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        $extMap       = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_GIF  => 'gif',
            IMAGETYPE_WEBP => 'webp',
        ];

        if (!empty($_FILES['images']['name'][0])) {
            $files = $_FILES['images'];
            $count = min(count($files['name']), 20);

            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

                $tmpPath = $files['tmp_name'][$i];
                $info    = @getimagesize($tmpPath);
                if (!$info || !in_array($info[2], $allowedTypes, true)) continue;

                $ext       = $extMap[$info[2]];
                $filename  = bin2hex(random_bytes(8)) . '.' . $ext;
                $destPath  = $uploadDir . $filename;
                $thumbPath = $uploadDir . 'thumb_' . $filename;

                if (!move_uploaded_file($tmpPath, $destPath)) continue;
                make_thumbnail($destPath, $thumbPath);

                $stmt = $db->prepare('INSERT INTO entry_images (entry_id, filename) VALUES (?, ?)');
                $stmt->execute([$entryId, $filename]);
            }
        }

        // Notify admin
        $adminEmail = getenv('ADMIN_EMAIL') ?: '';
        $adminToken = getenv('ADMIN_TOKEN') ?: '';
        if ($adminEmail) {
            $adminLink      = 'https://franz-lidecke.de/admin?token=' . urlencode($adminToken);
            $escapedName    = h($name) . ($anonymous ? ' <em>(anonym)</em>' : '');
            $escapedMessage = nl2br(h($message));
            send_mail(
                $adminEmail,
                'Neuer Kondolenzbucheintrag von ' . $name,
                "<p>Ein neuer Eintrag wartet auf Freigabe:</p>
                <p><strong>Name:</strong> {$escapedName}</p>
                <p><strong>Nachricht:</strong><br>{$escapedMessage}</p>
                <p><a href=\"{$adminLink}\">Zur Freigabe</a></p>"
            );
        }

        // Flash message via session, then redirect cleanly
        $_SESSION['flash_success'] = true;

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit;
        }

        header('Location: /');
        exit;
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'errors' => $errors]);
        exit;
    }
}

// ── Session flash ──────────────────────────────────────────────────────────
$flashSuccess = !empty($_SESSION['flash_success']);
if ($flashSuccess) unset($_SESSION['flash_success']);

// ── Load approved entries ──────────────────────────────────────────────────
$entries = $db->query(
    'SELECT id, name, message, created_at, anonymous FROM entries WHERE approved = 1 ORDER BY created_at DESC'
)->fetchAll(PDO::FETCH_ASSOC);

$imagesByEntry = [];
if ($entries) {
    $ids  = implode(',', array_map(fn($e) => (int) $e['id'], $entries));
    $imgs = $db->query("SELECT entry_id, filename FROM entry_images WHERE entry_id IN ({$ids}) ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($imgs as $img) {
        $imagesByEntry[$img['entry_id']][] = $img['filename'];
    }
}

?>
<!DOCTYPE html>
<html lang="<?= $LANG ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(t('meta_title_main')) ?></title>
    <meta name="description" content="<?= h(t('meta_desc_main')) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://franz-lidecke.de/">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= h(t('og_site_name')) ?>">
    <meta property="og:url" content="https://franz-lidecke.de/">
    <meta property="og:title" content="<?= h(t('meta_title_main')) ?>">
    <meta property="og:description" content="<?= h(t('meta_desc_main')) ?>">
    <meta property="og:image" content="https://franz-lidecke.de/images/franz-lidecke-traueranzeige.jpeg">
    <meta property="og:locale" content="<?= $ogLocale ?>">

    <!-- Structured data -->
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"WebSite","name":"Franz Lidecke","url":"https://franz-lidecke.de/"}
    </script>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
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
        <div class="header-verse">
            <p><?= t('header_verse') ?></p>
        </div>
    </a>
    <nav class="site-nav">
        <a href="/" class="site-nav-link active"><?= t('nav_guestbook') ?></a>
        <a href="/maerchenstunde" class="site-nav-link"><?= t('nav_story_hour') ?></a>
        <?= lang_switcher() ?>
    </nav>
</header>

<main>

    <section class="obituary">
        <p><?= t('obituary_para_1') ?></p>
        <p><?= t('obituary_para_2') ?></p>
        <p><?= t('obituary_para_3') ?></p>
        <p><?= t('obituary_para_4') ?></p>
        <p><?= t('obituary_para_5') ?></p>
        <p class="marchen-video-callout">
            <a href="/maerchenstunde" class="marchen-video-link"><?= t('obituary_video_link') ?></a>
        </p>
    </section>

    <?php if ($flashSuccess): ?>
    <div class="banner banner-success">
        <?= h(t('banner_success')) ?>
    </div>
    <?php endif; ?>

    <?php if ($errors): ?>
    <div class="banner banner-error">
        <strong><?= h(t('banner_error_heading')) ?></strong>
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= h($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Entries -->
    <section>
        <h2><?= h(t('entries_heading')) ?></h2>

        <?php if (empty($entries)): ?>
            <p class="no-entries"><?= h(t('entries_none')) ?></p>
        <?php else: ?>
            <?php foreach ($entries as $entry): ?>
            <article class="entry">
                <div class="entry-meta">
                    <span class="entry-name">
                        <?= $entry['anonymous'] ? h(t('anonymous')) : h($entry['name']) ?>
                    </span>
                    <span class="entry-date"><?= h(fmt_date($entry['created_at'])) ?></span>
                </div>
                <p class="entry-message"><?= h($entry['message']) ?></p>

                <?php if (!empty($imagesByEntry[$entry['id']])): ?>
                <div class="entry-gallery">
                    <?php foreach ($imagesByEntry[$entry['id']] as $filename): ?>
                    <a href="/uploads/<?= h($filename) ?>"
                       class="glightbox"
                       data-gallery="entry-<?= (int)$entry['id'] ?>">
                        <img src="/uploads/thumb_<?= h($filename) ?>"
                             alt="<?= h(t('photo_alt')) ?> <?= $entry['anonymous'] ? h(t('anonymous')) : h($entry['name']) ?>"
                             loading="lazy">
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- Form -->
    <section class="form-section">
        <h2><?= h(t('form_heading')) ?></h2>

        <form method="POST" enctype="multipart/form-data" action="/" id="entryForm">
            <div class="form-grid">

                <div class="form-field">
                    <label for="name"><?= h(t('form_name')) ?></label>
                    <input type="text" id="name" name="name"
                           value="<?= h($_POST['name'] ?? '') ?>"
                           maxlength="100" required autocomplete="name">
                </div>

                <div class="form-field">
                    <label class="checkbox-label">
                        <input type="checkbox" name="anonymous" value="1"
                               <?= !empty($_POST['anonymous']) ? 'checked' : '' ?>>
                        <?= h(t('form_hide_name')) ?> <span class="optional">(<?= t('form_appears_as_anon') ?>)</span>
                    </label>
                </div>

                <div class="form-field">
                    <label for="email"><?= h(t('form_email')) ?> <span class="optional">(<?= t('form_email_hint') ?>)</span></label>
                    <input type="email" id="email" name="email"
                           value="<?= h($_POST['email'] ?? '') ?>"
                           maxlength="200" autocomplete="email">
                </div>

                <div class="form-field">
                    <label for="message"><?= h(t('form_message')) ?></label>
                    <textarea id="message" name="message" maxlength="2000" required><?= h($_POST['message'] ?? '') ?></textarea>
                </div>

                <!-- Upload zone -->
                <div class="form-field">
                    <label><?= h(t('form_photos')) ?> <span class="optional">(<?= h(t('form_photos_optional')) ?>)</span></label>
                    <div class="upload-zone" id="uploadZone">
                        <p class="upload-hint"><?= h(t('form_drag_hint')) ?></p>
                        <button type="button" class="upload-btn" id="uploadBtn"><?= h(t('form_select_btn')) ?></button>
                        <p class="upload-hint upload-hint-small"><?= t('form_upload_hint') ?></p>
                    </div>
                    <input type="file" id="images" name="images[]" multiple accept="image/*" style="display:none">
                    <div class="upload-previews" id="uploadPreviews"></div>
                </div>

                <div class="form-submit">
                    <button type="submit" class="btn-submit" id="submitBtn"><?= h(t('form_submit')) ?></button>
                    <div class="upload-progress" id="uploadProgress" hidden>
                        <div class="upload-progress-bar">
                            <div class="upload-progress-fill" id="uploadProgressBar"></div>
                        </div>
                        <span class="upload-progress-label" id="uploadProgressLabel">0 %</span>
                    </div>
                </div>

            </div>
        </form>
    </section>

</main>

<?php $footerStats = get_footer_stats(); ?>
<footer>
    <p><a href="/impressum"><?= h(t('footer_legal')) ?></a> &nbsp;&middot;&nbsp; <a href="https://github.com/Joshua2504/in-erinnerung-an-franz-lidecke/" target="_blank" rel="noopener">GitHub</a></p>
    <p style="margin-top:6px;font-size:0.78rem;"><?= number_format($footerStats['hits'], 0, ',', '.') ?> <?= h(t('footer_hits')) ?> &nbsp;&middot;&nbsp; <?= number_format($footerStats['unique'], 0, ',', '.') ?> <?= h(t('footer_unique')) ?></p>
</footer>

<div id="cookieBanner" class="cookie-banner" hidden>
    <p class="cookie-text">
        <?= h(t('cookie_text')) ?>
        <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Datenschutz</a>
    </p>
    <div class="cookie-actions">
        <button class="cookie-btn cookie-btn-accept" id="cookieAccept"><?= h(t('cookie_accept')) ?></button>
        <button class="cookie-btn cookie-btn-decline" id="cookieDecline"><?= h(t('cookie_decline')) ?></button>
    </div>
</div>

<script>
const i18n = <?= json_encode([
    'errHeading'  => t('js_err_heading'),
    'errUnknown'  => t('js_err_unknown'),
    'errServer'   => t('js_err_server'),
    'errNetwork'  => t('js_err_network'),
    'removeLabel' => t('js_remove_label'),
], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<script>GLightbox({ selector: '.glightbox' });</script>
<script>
(function () {
    const COOKIE_NAME = 'ga_consent';
    const GA_ID = 'G-FD0LXG1P2B';
    const banner = document.getElementById('cookieBanner');

    function getConsent() {
        return document.cookie.split('; ').find(r => r.startsWith(COOKIE_NAME + '='))?.split('=')[1];
    }

    function setConsent(value) {
        const expires = new Date(Date.now() + 365 * 24 * 3600 * 1000).toUTCString();
        document.cookie = COOKIE_NAME + '=' + value + '; expires=' + expires + '; path=/; SameSite=Lax';
    }

    function loadGA() {
        const s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_ID;
        document.head.appendChild(s);
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        window.gtag = gtag;
        gtag('js', new Date());
        gtag('config', GA_ID);
    }

    const consent = getConsent();
    if (consent === 'yes') {
        loadGA();
    } else if (consent !== 'no') {
        banner.hidden = false;
    }

    document.getElementById('cookieAccept').addEventListener('click', function () {
        setConsent('yes');
        banner.hidden = true;
        loadGA();
    });

    document.getElementById('cookieDecline').addEventListener('click', function () {
        setConsent('no');
        banner.hidden = true;
    });
})();
</script>
<script>
(function () {
    // ── Upload preview ─────────────────────────────────
    const zone     = document.getElementById('uploadZone');
    const btn      = document.getElementById('uploadBtn');
    const input    = document.getElementById('images');
    const previews = document.getElementById('uploadPreviews');
    let files = [];

    btn.addEventListener('click', () => input.click());
    zone.addEventListener('click', (e) => { if (e.target === zone) input.click(); });
    zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.classList.remove('dragover');
        addFiles(Array.from(e.dataTransfer.files));
    });
    input.addEventListener('change', () => addFiles(Array.from(input.files)));

    function addFiles(newFiles) {
        newFiles.filter(f => f.type.startsWith('image/')).forEach(f => {
            if (files.length < 20) files.push(f);
        });
        render();
        sync();
    }

    function removeFile(i) {
        files.splice(i, 1);
        render();
        sync();
    }

    function render() {
        previews.querySelectorAll('img').forEach(img => URL.revokeObjectURL(img.src));
        previews.innerHTML = '';
        files.forEach((f, i) => {
            const url = URL.createObjectURL(f);
            const div = document.createElement('div');
            div.className = 'preview-thumb';

            const img = document.createElement('img');
            img.src = url;
            img.alt = f.name;

            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'preview-remove';
            del.innerHTML = '&times;';
            del.setAttribute('aria-label', i18n.removeLabel);
            del.addEventListener('click', () => removeFile(i));

            const name = document.createElement('span');
            name.className = 'preview-name';
            name.textContent = f.name.length > 18 ? f.name.slice(0, 15) + '…' : f.name;

            div.appendChild(img);
            div.appendChild(del);
            div.appendChild(name);
            previews.appendChild(div);
        });
        previews.style.display = files.length ? 'grid' : 'none';
    }

    function sync() {
        const dt = new DataTransfer();
        files.forEach(f => dt.items.add(f));
        input.files = dt.files;
    }

    // ── XHR submit with progress ────────────────────────
    const form        = document.getElementById('entryForm');
    const submitBtn   = document.getElementById('submitBtn');
    const progressWrap = document.getElementById('uploadProgress');
    const progressBar  = document.getElementById('uploadProgressBar');
    const progressLabel = document.getElementById('uploadProgressLabel');

    // Only intercept if there are files; plain-text submissions don't need a progress bar
    form.addEventListener('submit', function (e) {
        if (files.length === 0) return; // Let browser handle the normal submit
        e.preventDefault();
        submitWithProgress();
    });

    function submitWithProgress() {
        const fd = new FormData(form);

        submitBtn.disabled = true;
        submitBtn.classList.add('loading');
        progressWrap.hidden = false;
        setProgress(0);

        // Remove any previous error banner
        const prev = form.closest('section').querySelector('.banner-error');
        if (prev) prev.remove();

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) setProgress(Math.round(e.loaded / e.total * 100));
        });

        xhr.addEventListener('load', () => {
            setProgress(100);
            try {
                const res = JSON.parse(xhr.responseText);
                if (res.ok) {
                    window.location.href = '/';
                } else {
                    showErrors(res.errors || [i18n.errUnknown]);
                    resetBtn();
                }
            } catch {
                showErrors([i18n.errServer]);
                resetBtn();
            }
        });

        xhr.addEventListener('error', () => {
            showErrors([i18n.errNetwork]);
            resetBtn();
        });

        xhr.send(fd);
    }

    function setProgress(pct) {
        progressBar.style.width = pct + '%';
        progressLabel.textContent = pct + '\u202F%';
    }

    function resetBtn() {
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
        progressWrap.hidden = true;
        setProgress(0);
    }

    function showErrors(errs) {
        const div = document.createElement('div');
        div.className = 'banner banner-error';
        div.innerHTML = '<strong>' + i18n.errHeading + '</strong><ul>'
            + errs.map(e => '<li>' + e.replace(/</g, '&lt;') + '</li>').join('')
            + '</ul>';
        form.closest('section').insertBefore(div, form);
        div.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
})();
</script>

</body>
</html>
