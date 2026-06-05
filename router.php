<?php
// PHP Built-in Server Router for Local Development
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$route = $uri === '/' ? '/' : rtrim($uri, '/');

// Serve physical files as-is
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false; // let the built-in server handle the request
}

// Emulate rewrite rules of .htaccess
if ($route === '/sitemap.xml') {
    include 'sitemap.php';
    exit;
}
if ($route === '/trekking') {
    include 'trekking.php';
    exit;
}
if ($route === '/tour') {
    include 'tour.php';
    exit;
}
if ($route === '/peak-climbing') {
    include 'peak-climbing.php';
    exit;
}
if ($route === '/blog') {
    include 'blog.php';
    exit;
}
if ($route === '/destination') {
    include 'destination.php';
    exit;
}
if ($route === '/about') {
    include 'about.php';
    exit;
}
if ($route === '/contact') {
    include 'contact.php';
    exit;
}
if ($route === '/admin') {
    include 'admin.php';
    exit;
}

// Matches pattern: /(trekking|tour|climbing|peak-climbing|page|blog|activity)/slug
if (preg_match('#^/(trekking|tour|climbing|peak-climbing|page|blog|activity)/([^/]+)$#', $route, $matches)) {
    $_GET['type'] = $matches[1];
    $_GET['slug'] = $matches[2];
    include 'route.php';
    exit;
}

// Fallback to index.php
include 'index.php';
