<?php
$adminToken = getenv('ADMIN_TOKEN') ?: '';
$givenToken = $_GET['token'] ?? '';

if ($adminToken === '' || !hash_equals($adminToken, $givenToken)) {
    http_response_code(403);
    exit('Zugang verweigert.');
}

$tokenParam = '?token=' . urlencode($adminToken);

// ── Database ───────────────────────────────────────────────────────────────
$dbPath = '/var/www/data/guestbook.db';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('PRAGMA journal_mode=WAL');

// ── POST actions ───────────────────────────────────────────────────────────
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_id'])) {
        $id = (int) $_POST['approve_id'];
        $db->prepare('UPDATE entries SET approved = 1 WHERE id = ?')->execute([$id]);

        // Notify submitter if they left an email
        $row = $db->prepare('SELECT name, email FROM entries WHERE id = ?');
        $row->execute([$id]);
        $entry = $row->fetch(PDO::FETCH_ASSOC);
        if ($entry && !empty($entry['email'])) {
            send_mail_admin(
                $entry['email'],
                'Ihr Eintrag im Kondolenzbuch wurde freigeschaltet',
                '<p>Liebe/r ' . htmlspecialchars($entry['name'], ENT_QUOTES, 'UTF-8') . ',</p>
                <p>Ihr Eintrag im Gedenkbuch für Franz Lidecke wurde freigeschaltet und ist nun auf
                <a href="https://franz-lidecke.de">franz-lidecke.de</a> sichtbar.</p>
                <p>Vielen Dank.</p>'
            );
        }
        $message = 'Eintrag freigegeben.';
    } elseif (isset($_POST['unapprove_id'])) {
        $id = (int) $_POST['unapprove_id'];
        $db->prepare('UPDATE entries SET approved = 0 WHERE id = ?')->execute([$id]);
        $message = 'Freigabe zurückgezogen.';
    } elseif (isset($_POST['delete_id'])) {
        $id = (int) $_POST['delete_id'];

        // Delete associated image files
        $imgs = $db->prepare('SELECT filename FROM entry_images WHERE entry_id = ?');
        $imgs->execute([$id]);
        foreach ($imgs->fetchAll(PDO::FETCH_COLUMN) as $filename) {
            $uploadDir = '/var/www/html/uploads/';
            @unlink($uploadDir . $filename);
            @unlink($uploadDir . 'thumb_' . $filename);
        }

        $db->prepare('DELETE FROM entry_images WHERE entry_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM entries WHERE id = ?')->execute([$id]);
        $message = 'Eintrag gelöscht.';
    }

    header('Location: /admin' . $tokenParam . ($message ? '&msg=' . urlencode($message) : ''));
    exit;
}

// ── Page view stats ────────────────────────────────────────────────────────
$statsTotal  = (int) $db->query('SELECT COUNT(*) FROM page_views')->fetchColumn();
$stats7days  = (int) $db->query('SELECT COUNT(*) FROM page_views WHERE visited_at > datetime("now", "-7 days")')->fetchColumn();
$statsToday  = (int) $db->query('SELECT COUNT(*) FROM page_views WHERE DATE(visited_at) = DATE("now")')->fetchColumn();

// ── Load entries ───────────────────────────────────────────────────────────
$pending  = $db->query('SELECT * FROM entries WHERE approved = 0 ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$approved = $db->query('SELECT * FROM entries WHERE approved = 1 ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

// Image counts
$imgCounts = [];
$rows = $db->query('SELECT entry_id, COUNT(*) as cnt FROM entry_images GROUP BY entry_id')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $imgCounts[$r['entry_id']] = $r['cnt'];
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function send_mail_admin(string $to, string $subject, string $body): void {
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

$flashMsg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin – Kondolenzbuch</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        body { font-family: system-ui, sans-serif; max-width: 900px; margin: 32px auto; padding: 0 16px; background: #f8f8f8; color: #222; }
        h1 { font-size: 1.3rem; margin-bottom: 8px; }
        h2 { font-size: 1rem; margin: 32px 0 12px; border-bottom: 2px solid #ddd; padding-bottom: 6px; }
        .msg { background: #e6f4e6; border: 1px solid #a8d5a8; padding: 10px 14px; border-radius: 4px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 0.9rem; vertical-align: top; }
        th { background: #f0f0f0; font-weight: 600; white-space: nowrap; }
        .msg-cell { max-width: 340px; white-space: pre-wrap; word-break: break-word; }
        .actions form { display: inline; }
        .btn { padding: 5px 12px; border: none; border-radius: 3px; cursor: pointer; font-size: 0.85rem; }
        .btn-approve   { background: #4a8c4a; color: #fff; }
        .btn-unapprove { background: #8c6a1a; color: #fff; margin-left: 6px; }
        .btn-delete    { background: #a03030; color: #fff; margin-left: 6px; }
        .badge { display: inline-block; background: #e0e0e0; border-radius: 10px; padding: 1px 8px; font-size: 0.78rem; }
        .badge-anon { background: #d0e0f0; }
        .empty { color: #888; font-style: italic; padding: 12px; }
    </style>
</head>
<body>

<h1>Kondolenzbuch &ndash; Adminbereich</h1>
<p style="color:#666;font-size:.85rem;">In Erinnerung an Franz Lidecke</p>

<?php if ($flashMsg): ?>
<div class="msg"><?= h($flashMsg) ?></div>
<?php endif; ?>

<table style="max-width:360px;margin-bottom:32px;">
    <tr><th>Seitenaufrufe gesamt</th><td><?= number_format($statsTotal, 0, ',', '.') ?></td></tr>
    <tr><th>Letzte 7 Tage</th><td><?= number_format($stats7days, 0, ',', '.') ?></td></tr>
    <tr><th>Heute</th><td><?= number_format($statsToday, 0, ',', '.') ?></td></tr>
</table>

<h2>Wartend auf Freigabe (<?= count($pending) ?>)</h2>
<?php if (empty($pending)): ?>
    <p class="empty">Keine wartenden Einträge.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>E-Mail</th>
            <th>Nachricht</th>
            <th>Datum</th>
            <th>Fotos</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($pending as $row): ?>
        <tr>
            <td><?= (int)$row['id'] ?></td>
            <td>
                <?= h($row['name']) ?>
                <?php if (!empty($row['anonymous'])): ?>
                    <span class="badge badge-anon">anonym</span>
                <?php endif; ?>
            </td>
            <td><?= h($row['email'] ?? '') ?></td>
            <td class="msg-cell"><?= h($row['message']) ?></td>
            <td style="white-space:nowrap"><?= h($row['created_at']) ?></td>
            <td><?= isset($imgCounts[$row['id']]) ? (int)$imgCounts[$row['id']] : 0 ?></td>
            <td class="actions">
                <form method="POST" action="/admin<?= h($tokenParam) ?>">
                    <input type="hidden" name="approve_id" value="<?= (int)$row['id'] ?>">
                    <button class="btn btn-approve" type="submit">Freigeben</button>
                </form>
                <form method="POST" action="/admin<?= h($tokenParam) ?>"
                      onsubmit="return confirm('Eintrag wirklich löschen?')">
                    <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
                    <button class="btn btn-delete" type="submit">Löschen</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h2>Freigegebene Einträge (<?= count($approved) ?>)</h2>
<?php if (empty($approved)): ?>
    <p class="empty">Keine freigegebenen Einträge.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>E-Mail</th>
            <th>Nachricht</th>
            <th>Datum</th>
            <th>Fotos</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($approved as $row): ?>
        <tr>
            <td><?= (int)$row['id'] ?></td>
            <td>
                <?= h($row['name']) ?>
                <?php if (!empty($row['anonymous'])): ?>
                    <span class="badge badge-anon">anonym</span>
                <?php endif; ?>
            </td>
            <td><?= h($row['email'] ?? '') ?></td>
            <td class="msg-cell"><?= h($row['message']) ?></td>
            <td style="white-space:nowrap"><?= h($row['created_at']) ?></td>
            <td><?= isset($imgCounts[$row['id']]) ? (int)$imgCounts[$row['id']] : 0 ?></td>
            <td class="actions">
                <form method="POST" action="/admin<?= h($tokenParam) ?>">
                    <input type="hidden" name="unapprove_id" value="<?= (int)$row['id'] ?>">
                    <button class="btn btn-unapprove" type="submit">Freigabe zurückziehen</button>
                </form>
                <form method="POST" action="/admin<?= h($tokenParam) ?>"
                      onsubmit="return confirm('Eintrag wirklich löschen?')">
                    <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
                    <button class="btn btn-delete" type="submit">Löschen</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

</body>
</html>
