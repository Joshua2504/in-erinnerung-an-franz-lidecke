<?php
function get_footer_stats(): array {
    try {
        $db     = new PDO('sqlite:/var/www/data/guestbook.db');
        $hits   = (int) $db->query('SELECT COUNT(*) FROM page_views')->fetchColumn();
        $unique = (int) $db->query('SELECT COUNT(DISTINCT visitor_token) FROM page_views WHERE visitor_token IS NOT NULL')->fetchColumn();
        return ['hits' => $hits, 'unique' => $unique];
    } catch (Exception $e) {
        return ['hits' => 0, 'unique' => 0];
    }
}
