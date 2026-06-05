<?php
// Dynamic XML Sitemap Generator
header("Content-Type: application/xml; charset=utf-8");

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = $protocol . "://" . $domain;

$pages = [
    '/',
    '/about',
    '/contact',
    '/destination',
    '/trekking',
    '/tour',
    '/peak-climbing'
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Add static pages
foreach ($pages as $page) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($base_url . $page) . "</loc>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>" . ($page === '/' ? '1.0' : '0.8') . "</priority>\n";
    echo "  </url>\n";
}

// Add Packages
$packages_path = __DIR__ . '/data/packages.json';
if (file_exists($packages_path)) {
    $packages = json_decode(file_get_contents($packages_path), true);
    if (is_array($packages)) {
        foreach ($packages as $pkg) {
            $cat = strtolower($pkg['category']);
            if ($cat === 'climbing') $cat = 'peak-climbing'; // Adjust route name if needed
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($base_url . '/' . $cat . '/' . $pkg['slug']) . "</loc>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.9</priority>\n";
            echo "  </url>\n";
        }
    }
}

// Add Activities
$activities_path = __DIR__ . '/data/activities.json';
if (file_exists($activities_path)) {
    $activities = json_decode(file_get_contents($activities_path), true);
    if (is_array($activities)) {
        foreach ($activities as $act) {
            $cat = strtolower($act['category']);
            if ($cat === 'climbing') $cat = 'peak-climbing';
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($base_url . '/' . $cat . '/' . $act['slug']) . "</loc>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.7</priority>\n";
            echo "  </url>\n";
        }
    }
}

echo '</urlset>';
