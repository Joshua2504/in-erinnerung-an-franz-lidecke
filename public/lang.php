<?php
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// ── Language switching ─────────────────────────────────────────────────────
if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'], true)) {
    setcookie('lang', $_GET['lang'], [
        'expires'  => time() + 365 * 24 * 3600,
        'path'     => '/',
        'samesite' => 'Lax',
    ]);
    // Redirect to same path, preserving other query params but dropping lang=
    $params = $_GET;
    unset($params['lang']);
    $path     = strtok($_SERVER['REQUEST_URI'], '?');
    $cleanUrl = $path . ($params ? '?' . http_build_query($params) : '');
    header('Location: ' . $cleanUrl, true, 302);
    exit;
}

// ── Language detection ─────────────────────────────────────────────────────
$LANG = 'de'; // default

if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], ['de', 'en'], true)) {
    $LANG = $_COOKIE['lang'];
} else {
    $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if (preg_match('/^en\b/i', $accept)) {
        $LANG = 'en';
    }
}

$ogLocale = $LANG === 'en' ? 'en_GB' : 'de_DE';

// ── Load translations ──────────────────────────────────────────────────────
$translations = require __DIR__ . '/lang/' . $LANG . '.php';

function t(string $key): string {
    global $translations;
    return $translations[$key] ?? $key;
}

// ── Locale-aware date formatting ───────────────────────────────────────────
function fmt_date(string $dt): string {
    global $LANG;
    static $monthsDE = [
        1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
        5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
    ];
    static $monthsEN = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];
    $ts = strtotime($dt);
    if (!$ts) return $dt;
    $day   = (int) date('j', $ts);
    $month = (int) date('n', $ts);
    $year  = date('Y', $ts);
    if ($LANG === 'en') {
        return $monthsEN[$month] . ' ' . $day . ', ' . $year;
    }
    return $day . '. ' . $monthsDE[$month] . ' ' . $year;
}

// ── Language switcher HTML ─────────────────────────────────────────────────
function lang_switcher(): string {
    global $LANG;
    $de = $LANG === 'de' ? ' class="active"' : '';
    $en = $LANG === 'en' ? ' class="active"' : '';
    return '<div class="lang-switcher">'
        . '<a href="?lang=de"' . $de . '>DE</a>'
        . '<a href="?lang=en"' . $en . '>EN</a>'
        . '</div>';
}
