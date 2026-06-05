<?php
// PHP Built-in Server Router for Local Development
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve physical files as-is
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false; // let the built-in server handle the request
}

// Emulate rewrite rules of .htaccess
if ($uri === '/sitemap.xml') {
    include 'sitemap.php';
    exit;
}
if ($uri === '/trekking') {
    include 'trekking.php';
    exit;
}
if ($uri === '/tour') {
    include 'tour.php';
    exit;
}
if ($uri === '/peak-climbing') {
    include 'peak-climbing.php';
    exit;
}
if ($uri === '/blog') {
    include 'blog.php';
    exit;
}
if ($uri === '/destination') {
    include 'destination.php';
    exit;
}
if ($uri === '/about') {
    include 'about.php';
    exit;
}
if ($uri === '/contact') {
    include 'contact.php';
    exit;
}
if ($uri === '/admin') {
    include 'admin.php';
    exit;
}

// Matches pattern: /(trekking|tour|climbing|peak-climbing|page|blog|activity)/slug
if (preg_match('#^/(trekking|tour|climbing|peak-climbing|page|blog|activity)/([^/]+)$#', $uri, $matches)) {
    $_GET['type'] = $matches[1];
    $_GET['slug'] = $matches[2];
    include 'route.php';
    exit;
}

// Fallback to index.php
include 'index.php';
