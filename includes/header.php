<?php
function expire_legacy_cookie_variants($name) {
    if (headers_sent() || !isset($_COOKIE[$name])) {
        return;
    }

    $secure_cookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
    $domains = [''];
    if ($host !== '' && strpos($host, '.') !== false) {
        $domains[] = $host;
        $domains[] = '.' . ltrim($host, '.');
    }
    $paths = ['/', '/admin'];

    foreach (array_unique($domains) as $domain) {
        foreach ($paths as $path) {
            if (PHP_VERSION_ID >= 70300) {
                setcookie($name, '', [
                    'expires' => time() - 42000,
                    'path' => $path,
                    'domain' => $domain,
                    'secure' => $secure_cookie,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            } else {
                setcookie($name, '', time() - 42000, $path . '; samesite=Lax', $domain, $secure_cookie, true);
            }
        }
    }

    unset($_COOKIE[$name]);
}

expire_legacy_cookie_variants('PHPSESSID');

// Load dynamic global settings
$settings_path = __DIR__ . '/../data/settings.json';
$main_css_path = __DIR__ . '/../assets/css/style.css';
$site_name = "Top Mountain Treks Pvt. Ltd.";
$site_logo = "assets/images/logo.png";
$site_whatsapp = "+977 985-1123456";
$owner_photo = "";
$brand_line_1 = "TOP MOUNTAIN";
$brand_line_2 = "TREKS PVT. LTD.";
$site_nav = [
    ["title" => "Home", "link" => "/"],
    ["title" => "Destination", "link" => "/destination"],
    ["title" => "Trekking", "link" => "/trekking"],
    ["title" => "Peak Climbing", "link" => "/peak-climbing"],
    ["title" => "Tours", "link" => "/tour"],
    ["title" => "Company Info", "link" => "#"],
    ["title" => "Contact Us", "link" => "/contact"]
];
$header_custom_html = "";

if (file_exists($settings_path)) {
    $set_data = json_decode(file_get_contents($settings_path), true);
    if (is_array($set_data)) {
        if (!empty($set_data['company_name'])) $site_name = $set_data['company_name'];
        if (!empty($set_data['logo_path'])) $site_logo = $set_data['logo_path'];
        if (!empty($set_data['whatsapp_number'])) $site_whatsapp = $set_data['whatsapp_number'];
        if (!empty($set_data['owner_photo_path'])) $owner_photo = $set_data['owner_photo_path'];
        if (!empty($set_data['brand_line_1'])) $brand_line_1 = strtoupper($set_data['brand_line_1']);
        if (!empty($set_data['brand_line_2'])) $brand_line_2 = strtoupper($set_data['brand_line_2']);
        if (!empty($set_data['navigation'])) $site_nav = $set_data['navigation'];
        if (!empty($set_data['header_custom_html'])) $header_custom_html = $set_data['header_custom_html'];
    }
}
if ($owner_photo === '') {
    $owner_photo = $site_logo;
}

// Load dynamic theme config
$theme_path = __DIR__ . '/../data/theme.json';
$theme_settings = [
    'primary' => '#1e3d2f',
    'secondary' => '#ff7a00',
    'accent' => '#0077b6',
    'dropdown_icon' => 'triangle',
    'header_bg' => '#ffffff',
    'header_text' => '#1f2937',
    'title_color' => '#1e3d2f',
    'page_bg' => '#ffffff',
    'footer_bg' => '#0f172a',
    'footer_text' => '#9ca3af',
    'menu_bg' => '#ffffff',
    'menu_text_size' => '15px',
    'menu_padding' => '8px',
    'submenu_link_color' => '#4b5563',
    'submenu_hover_color' => '#0077b6',
    'submenu_prefix_color' => '#111827',
    'mega_menu_width' => '1240px',
    'slider_title_color' => '#ffffff',
    'slider_title_size' => '50px',
    'slider_desc_color' => '#ffffff',
    'slider_desc_size' => '18px',
    'slider_text_align' => 'left'
];
if (file_exists($theme_path)) {
    $theme_data = json_decode(file_get_contents($theme_path), true);
    if (is_array($theme_data)) {
        $theme_settings = array_merge($theme_settings, $theme_data);
    }
}

// Load activities and packages
$activities_path = __DIR__ . '/../data/activities.json';
$packages_path = __DIR__ . '/../data/packages.json';

$activities = [];
if (file_exists($activities_path)) {
    $activities = json_decode(file_get_contents($activities_path), true);
}
if (!is_array($activities)) $activities = [];

$packages = [];
if (file_exists($packages_path)) {
    $packages = json_decode(file_get_contents($packages_path), true);
}
if (!is_array($packages)) $packages = [];

if (!function_exists('header_activity_package_match')) {
    function header_activity_package_keywords($activity_id) {
        $map = [
            'everest-region' => ['everest', 'khumbu', 'ebc', 'gokyo', 'lukla', 'namche', 'pikey', 'three passes', 'high passes'],
            'annapurna-region' => ['annapurna', 'abc', 'poon', 'ghorepani', 'mardi', 'tilicho', 'khopra', 'nar phu', 'sikles', 'kapuche', 'kori', 'mohare', 'dhampus'],
            'langtang-region' => ['langtang', 'gosaikunda', 'gosainkunda', 'helambu', 'tamang', 'ama yangri', 'ganja la'],
            'manaslu-region' => ['manaslu', 'tsum', 'larkya', 'sama gaon', 'samdo'],
            'mustang-region' => ['mustang', 'tiji', 'lo manthang', 'jomsom', 'muktinath', 'kagbeni'],
            'restricted-areas' => ['restricted', 'remote', 'kanchenjunga', 'makalu', 'dolpo', 'rara', 'phoksundo'],
            'peak-climbing-nepal' => ['peak', 'climbing', 'island', 'lobuche', 'mera', 'yala'],
            'nepal-tours' => ['tour', 'kathmandu', 'pokhara', 'chitwan', 'helicopter', 'safari']
        ];
        return $map[$activity_id] ?? [];
    }

    function header_activity_package_match($activity, $package) {
        if (($package['category'] ?? '') !== ($activity['category'] ?? '')) {
            return false;
        }
        if (!empty($package['activity_id']) && $package['activity_id'] === ($activity['id'] ?? '')) {
            return true;
        }
        $haystack = strtolower(
            ($package['region'] ?? '') . ' ' .
            ($package['title'] ?? '') . ' ' .
            ($package['slug'] ?? '')
        );
        foreach (header_activity_package_keywords($activity['id'] ?? '') as $keyword) {
            if ($keyword !== '' && strpos($haystack, strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? $page_title : htmlspecialchars($site_name) . " | Nepal Trekking Specialists"; ?></title>
  <meta name="description" content="<?php echo isset($page_desc) ? htmlspecialchars(substr($page_desc, 0, 160)) : "Discover high-altitude adventures with " . htmlspecialchars($site_name) . ". Book professional treks and custom tours."; ?>">
  
  <!-- Base Stylesheets -->
  <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo file_exists($main_css_path) ? filemtime($main_css_path) : time(); ?>">
  <!-- Dynamic Custom Stylesheet (Overwrites HSL variables) -->
  <link rel="stylesheet" href="/assets/css/theme-custom.css?v=<?php echo file_exists(__DIR__ . '/../assets/css/theme-custom.css') ? filemtime(__DIR__ . '/../assets/css/theme-custom.css') : time(); ?>">
  
  <!-- Schema.org JSON-LD Structured Data for TravelAgency -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "TravelAgency",
    "name": "<?php echo htmlspecialchars($site_name); ?>",
    "image": "/assets/images/packages/hero_home.jpg",
    "telephone": "<?php echo htmlspecialchars($site_whatsapp); ?>",
    "priceRange": "$$$",
    "url": "http://127.0.0.1:8080"
  }
  </script>

  <style>
    /* Mega Menu and Header Styling Upgrades */
    #site-header {
      position: relative;
      background: var(--color-header-bg, rgba(255, 255, 255, 0.95)) !important;
      backdrop-filter: blur(10px);
      border-bottom: 1px solid var(--border-color);
      z-index: 1000;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }
    .header-top-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 2rem;
      max-width: 1400px;
      margin: 0 auto;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    .header-bottom-bar {
      background: var(--color-menu-bg, #ffffff) !important;
      width: 100%;
      border-top: 1px solid var(--border-color);
    }
    .header-bottom-row {
      max-width: 1400px;
      margin: 0 auto;
      padding: var(--menu-padding, 8px) 2rem !important;
      display: flex;
      justify-content: center;
    }
    .site-nav-centered {
      flex-grow: 1;
      display: flex;
      justify-content: center;
    }
    .nav-links {
      display: flex;
      gap: 2.25rem;
      list-style: none;
      margin: 0;
      padding: 0;
      align-items: center;
    }
    .nav-item {
      position: relative;
    }
    .nav-link {
      font-family: var(--font-heading);
      font-weight: 700;
      font-size: var(--menu-text-size, 15px) !important;
      color: var(--color-header-text, var(--text-dark)) !important;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.5rem 0;
      transition: color 0.3s;
    }
    .nav-link:hover {
      color: var(--color-secondary);
    }
    
    /* Mega Menu Dropdowns */
    .dropdown-menu {
      position: absolute;
      top: 100%;
      left: 50%;
      transform: translateX(-50%) translateY(10px);
      background: white;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-lg);
      padding: 1.5rem;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
      min-width: 280px;
      z-index: 1010;
    }
    .nav-item:hover .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateX(-50%) translateY(0);
    }

    /* Trekking Mega Menu (Multi-Column) */
    .mega-menu-wide {
      min-width: min(var(--mega-menu-width, 1240px), calc(100vw - 2rem)) !important;
      max-height: calc(100vh - 120px);
      overflow-y: auto;
      padding: 2rem !important;
    }
    .mega-menu-grid {
      display: grid;
      grid-template-columns: repeat(6, minmax(145px, 1fr));
      gap: 1rem;
    }
    .mega-col-title {
      display: block;
      font-family: var(--font-heading);
      font-size: 0.85rem;
      font-weight: 800;
      text-transform: uppercase;
      color: var(--color-secondary);
      border-bottom: 2px solid var(--border-color);
      padding-bottom: 0.5rem;
      margin-bottom: 0.75rem;
      letter-spacing: 0.5px;
    }
    .mega-col-links {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 0.45rem;
      max-height: min(58vh, 520px);
      overflow-y: auto;
      padding-right: 0.25rem;
    }
    .mega-col-link {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.82rem;
      color: var(--submenu-link-color, var(--text-medium));
      text-decoration: none;
      font-weight: 600;
      transition: all 0.2s;
    }
    .package-link-prefix {
      color: var(--submenu-prefix-color, #111827);
      font-size: 0.72rem;
      line-height: 1;
      flex: 0 0 auto;
    }
    .mega-col-link:hover {
      color: var(--submenu-hover-color, var(--color-accent));
      padding-left: 4px;
    }
    .mobile-region-arrow {
      display: none;
    }
    .standard-region-link {
      display: block;
      font-family: var(--font-heading);
      font-size: 0.75rem;
      font-weight: 800;
      color: #000000;
      text-transform: uppercase;
      text-decoration: none;
      margin-top: 0.5rem;
      margin-bottom: 0.25rem;
      border-bottom: 1px solid var(--border-color);
      padding-bottom: 2px;
      transition: color 0.2s;
    }
    .standard-region-link:hover {
      color: var(--color-secondary);
    }
    .standard-package-list {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }

    /* Standard dropdown link */
    .dropdown-link {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.5rem 0.75rem;
      font-size: 0.88rem;
      font-weight: 600;
      color: var(--submenu-link-color, var(--text-medium));
      text-decoration: none;
      border-radius: var(--radius-sm);
      transition: all 0.2s;
    }
    .dropdown-link:hover {
      background: var(--bg-light);
      color: var(--submenu-hover-color, var(--color-accent));
    }

    /* Header Right (WhatsApp) */
    .header-whatsapp-right {
      display: flex;
      align-items: center;
    }
    .header-owner-photo {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid rgba(255, 122, 0, 0.35);
      box-shadow: var(--shadow-sm);
      background: #ffffff;
    }
    .header-whatsapp-link {
      color: var(--color-secondary);
      text-decoration: none;
      font-size: 1rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }
    .header-whatsapp-dot {
      width: 0.6rem;
      height: 0.6rem;
      border-radius: 50%;
      background: #22c55e;
      box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
      flex: 0 0 auto;
    }
    .header-actions-right {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    @media (max-width: 992px) {
      #site-header {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 5000 !important;
      }
      body {
        padding-top: 60px !important;
      }
      .header-top-row {
        position: relative;
        min-height: 58px;
        padding-left: 3.75rem !important;
        padding-right: 0.75rem !important;
      }
      .header-actions-right {
        position: absolute !important;
        top: 50% !important;
        left: 1rem !important;
        right: auto !important;
        transform: translateY(-50%) !important;
        margin-left: 0 !important;
        flex-shrink: 0 !important;
      }
      .header-whatsapp-right {
        display: none !important;
      }
      #site-header .menu-btn {
        display: flex !important;
        position: fixed !important;
        top: 0.7rem !important;
        left: 0.75rem !important;
        z-index: 6000 !important;
        width: 34px;
        height: 34px;
        padding: 8px;
        border-radius: var(--radius-sm);
        background: var(--color-primary) !important;
        align-items: center;
        justify-content: space-between;
      }
      #site-header .menu-btn span {
        width: 100%;
        height: 2px;
        background-color: #ffffff !important;
      }
      body.mobile-nav-open {
        overflow: hidden !important;
        touch-action: none;
      }
      body.mobile-nav-open #site-header {
        position: fixed !important;
        inset: 0 0 auto 0 !important;
        height: 100vh !important;
        height: 100dvh !important;
        overflow: visible !important;
        z-index: 5000 !important;
      }
      body.mobile-nav-open #site-nav {
        z-index: 5002 !important;
      }
      body.mobile-nav-open #mobile-menu-btn {
        z-index: 5003 !important;
      }
      #site-nav {
        position: fixed !important;
        top: 0 !important;
        bottom: 0 !important;
        left: -100% !important;
        right: auto !important;
        height: 100vh !important;
        height: 100dvh !important;
        min-height: 100vh !important;
        max-height: 100vh !important;
        max-height: 100dvh !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        justify-content: flex-start !important;
        gap: 0 !important;
        width: min(96vw, 980px) !important;
        max-width: 100vw !important;
        padding: 5.75rem 1rem 1.25rem !important;
        background: #ffffff !important;
        border-right: 1px solid var(--border-color) !important;
        box-shadow: var(--shadow-lg) !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        scroll-behavior: smooth !important;
        overscroll-behavior: contain !important;
        -webkit-overflow-scrolling: touch;
        visibility: hidden !important;
        pointer-events: none !important;
        transform: none !important;
        transition: left 0.28s ease, visibility 0.28s ease !important;
        z-index: 1050 !important;
      }
      #site-nav.open {
        left: 0 !important;
        right: auto !important;
        visibility: visible !important;
        pointer-events: auto !important;
      }
      #site-nav .nav-links {
        display: flex !important;
        width: 100% !important;
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 0.45rem !important;
        margin: 0 !important;
        padding: 0 !important;
      }
      #site-nav .nav-item {
        position: relative !important;
      }
      #site-nav .nav-item,
      #site-nav .nav-link {
        width: 100% !important;
      }
      #site-nav .nav-link {
        justify-content: space-between !important;
        min-height: 46px !important;
        padding: 0.65rem 0.8rem !important;
        border: 1px solid var(--border-color) !important;
        border-radius: var(--radius-sm) !important;
        background: #ffffff !important;
      }
      #site-nav .nav-link > span:last-child,
      #site-nav .nav-link > svg:last-child {
        transition: transform 0.2s ease;
      }
      #site-nav .nav-item.open-mobile > .nav-link {
        border-color: var(--color-secondary) !important;
        color: var(--color-primary) !important;
      }
      #site-nav .nav-item.open-mobile > .nav-link > span:last-child,
      #site-nav .nav-item.open-mobile > .nav-link > svg:last-child {
        transform: rotate(180deg);
      }
      #site-nav .dropdown-menu {
        position: static !important;
        transform: translateY(-6px) !important;
        display: flex !important;
        flex-direction: column !important;
        width: auto !important;
        min-width: 0 !important;
        max-width: none !important;
        max-height: 0 !important;
        overflow-x: hidden !important;
        overflow-y: hidden;
        opacity: 0;
        visibility: hidden;
        padding: 0 !important;
        margin: 0 !important;
        border: 1px solid var(--border-color) !important;
        border-radius: var(--radius-md) !important;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12) !important;
        background: #ffffff !important;
      }
      #site-nav .nav-item.open-mobile > .dropdown-menu {
        max-height: min(48dvh, 350px) !important;
        overflow-y: auto;
        scroll-behavior: smooth;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        opacity: 1;
        visibility: visible;
        transform: translateY(0) !important;
        padding: 0.55rem 0 0.8rem !important;
      }
      #site-nav .mega-menu-wide {
        width: 100% !important;
        min-width: 0 !important;
        max-height: 0 !important;
        overflow-y: auto !important;
        scroll-behavior: smooth;
        overscroll-behavior: contain;
        padding: 0 !important;
      }
      #site-nav .nav-item.open-mobile > .mega-menu-wide {
        max-height: min(48dvh, 350px) !important;
      }
      #site-nav .mega-menu-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)) !important;
        gap: 0.75rem !important;
      }
      #site-nav .mega-menu-column {
        width: 100%;
        min-width: 0;
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        padding: 0.75rem;
      }
      #site-nav .mobile-region-toggle {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 0.65rem !important;
        width: 100% !important;
        min-height: 42px !important;
        color: #111827 !important;
        text-decoration: none !important;
        border: 1px solid var(--border-color) !important;
        border-radius: var(--radius-sm) !important;
        background: #ffffff !important;
        padding: 0.62rem 0.7rem !important;
      }
      #site-nav .mobile-region-toggle .mega-col-title,
      #site-nav .standard-region-link span:first-child {
        border-bottom: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
      }
      #site-nav .mobile-region-arrow {
        display: inline-block;
        flex: 0 0 auto;
        font-size: 1rem;
        line-height: 1;
        transition: transform 0.2s ease;
      }
      #site-nav .mobile-menu-group.open-region .mobile-region-arrow {
        transform: rotate(90deg);
      }
      #site-nav .mega-col-links {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.35rem;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.28s ease, padding-top 0.28s ease;
      }
      #site-nav .mega-menu-wide .mega-col-links {
        max-height: clamp(155px, 24dvh, 210px);
        overflow-y: auto;
        scroll-behavior: smooth;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        padding-top: 0.45rem;
      }
      #site-nav .mega-menu-wide .mobile-region-arrow {
        display: none;
      }
      #site-nav .standard-package-list {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 0.35rem !important;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.28s ease, padding-top 0.28s ease;
      }
      #site-nav .mobile-menu-group.open-region > .mega-col-links,
      #site-nav .mobile-menu-group.open-region > .standard-package-list {
        max-height: 72vh;
        overflow-y: auto;
        scroll-behavior: smooth;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        padding-top: 0.45rem;
      }
      #site-nav .mega-col-link,
      #site-nav .dropdown-link {
        display: flex !important;
        width: 100%;
        min-height: 38px;
        align-items: center;
        white-space: normal !important;
        overflow-wrap: anywhere;
        border: 1px solid var(--border-color);
        background: #f8fafc;
        border-radius: var(--radius-sm);
        padding: 0.5rem 0.65rem !important;
      }
    }
    @media (max-width: 640px) {
      .header-top-row {
        position: relative;
        min-height: 58px;
        padding: 0.65rem 0.75rem;
        padding-left: 3.75rem !important;
        padding-right: 0.75rem !important;
        gap: 0.4rem;
      }
      #site-header .logo-wrapper {
        min-width: 0;
        max-width: calc(100vw - 4.6rem);
      }
      #site-header .logo-wrapper img {
        height: 32px !important;
        flex-shrink: 0;
        margin-right: 0.35rem !important;
      }
      #site-header .logo-text {
        font-size: clamp(12px, 3.6vw, 15px) !important;
        max-width: calc(100vw - 7rem);
        white-space: normal;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.05 !important;
      }
      .header-actions-right {
        position: absolute;
        top: 50%;
        left: 1rem;
        right: auto;
        transform: translateY(-50%);
        margin-left: 0;
        flex-shrink: 0;
      }
      .header-whatsapp-right {
        display: none;
      }
      .header-bottom-row {
        padding: 0 !important;
      }
    }
  </style>

  <!-- Injected Custom Headers -->
  <?php echo $header_custom_html; ?>
</head>
<body>

  <!-- Sticky Navbar Header -->
  <header id="site-header">
    <!-- Top Row: Logo & Contact details (Left / Right) -->
    <div class="header-top-row">
      <button type="button" class="menu-btn" id="mobile-menu-btn" aria-label="Open navigation menu" aria-controls="site-nav" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <!-- Section 1: Logo (Left) -->
      <a href="/index.php" class="logo-wrapper" style="display:flex; align-items:center; text-decoration:none;">
        <img src="/<?php echo htmlspecialchars($site_logo); ?>" alt="<?php echo htmlspecialchars($site_name); ?> Logo" style="height:48px; width:auto; object-fit:contain; margin-right:0.5rem;" onerror="this.style.display='none'">
        <div class="logo-text" style="line-height:1.05; font-family:var(--font-heading); font-weight:900; font-size: var(--logo-font-size, 22px); color: var(--color-title, var(--color-primary)); text-transform:uppercase; letter-spacing:0;">
          <span style="display:block;"><?php echo htmlspecialchars($brand_line_1); ?></span>
          <span style="display:block;"><?php echo htmlspecialchars($brand_line_2); ?></span>
        </div>
      </a>

      <!-- Section 3: WhatsApp hotline and mobile navigation (Right) -->
      <div class="header-actions-right">
      <div class="header-whatsapp-right">
        <?php if (!empty($owner_photo)): ?>
          <img class="header-owner-photo" src="/<?php echo htmlspecialchars($owner_photo); ?>" alt="Owner photo" onerror="this.style.display='none'">
        <?php endif; ?>
        <div style="text-align:right;">
          <span style="font-size:0.7rem; color:var(--text-muted); display:block; font-weight:700; text-transform:uppercase;">WhatsApp Hotline</span>
          <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $site_whatsapp); ?>" class="header-whatsapp-link" target="_blank">
            <span class="header-whatsapp-dot" aria-hidden="true"></span>
            <?php echo htmlspecialchars($site_whatsapp); ?>
          </a>
        </div>
      </div>
      </div>
    </div>

    <!-- Bottom Row: Navigation Menu links -->
    <div class="header-bottom-bar">
      <div class="header-bottom-row">
      <!-- Section 2: Centered Navigation -->
      <div class="site-nav-centered">
        <nav id="site-nav">
          <ul class="nav-links">
            <?php foreach ($site_nav as $nav_item): 
                $nav_title = $nav_item['title'];
                $nav_link = $nav_item['link'];
                
                $is_trekking = (strtolower($nav_title) === 'trekking');
                $is_tours = (strtolower($nav_title) === 'tours' || strtolower($nav_title) === 'tour');
                $is_climbing = (strtolower($nav_title) === 'peak climbing');
                $is_company_info = (strtolower($nav_title) === 'company info');
                
                $has_dropdown = ($is_trekking || $is_tours || $is_climbing || $is_company_info);
                $cat_filter = '';
                if ($is_trekking) $cat_filter = 'trekking';
                elseif ($is_tours) $cat_filter = 'tour';
                elseif ($is_climbing) $cat_filter = 'climbing';
                $cat_path = $cat_filter === 'climbing' ? 'peak-climbing' : $cat_filter;
            ?>
              <li class="nav-item">
                <a href="<?php echo htmlspecialchars($nav_link); ?>" class="nav-link">
                  <?php echo htmlspecialchars($nav_title); ?>
                  <?php if ($has_dropdown): 
                      $icon_type = isset($theme_settings['dropdown_icon']) ? $theme_settings['dropdown_icon'] : 'triangle';
                      if ($icon_type === 'triangle'): ?>
                        <span style="font-size:0.6rem; margin-left:3px; vertical-align:middle; display:inline-block; margin-top:2px;">▼</span>
                      <?php elseif ($icon_type === 'chevron'): ?>
                        <svg viewBox="0 0 20 20" width="10" height="10" fill="currentColor" style="margin-left:3px; vertical-align:middle; display:inline-block; margin-top:2px;"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                      <?php elseif ($icon_type === 'pointer'): ?>
                        <span style="font-size:0.85rem; margin-left:3px; vertical-align:middle; display:inline-block; margin-top:2px;">↓</span>
                      <?php elseif ($icon_type === 'double_chevron'): ?>
                        <span style="font-size:0.75rem; margin-left:3px; vertical-align:middle; display:inline-block; margin-top:2px;">»</span>
                      <?php endif; ?>
                  <?php endif; ?>
                </a>
                
                <?php if ($has_dropdown): 
                    $cat_activities = [];
                    if ($cat_filter !== '') {
                        foreach ($activities as $act) {
                            if ($act['category'] === $cat_filter) {
                                $cat_activities[] = $act;
                            }
                        }
                    }
                ?>
                  <!-- Mega Menu Dropdown for Trekking, Standard Dropdowns for climbing & tours -->
                  <div class="dropdown-menu <?php echo $is_trekking ? 'mega-menu-wide' : ''; ?>">
                    
                    <?php if ($is_trekking): ?>
                      <!-- MULTI-COLUMN MEGA MENU -->
                      <div class="mega-menu-grid">
                        <?php 
                        $col_count = 0;
                        foreach ($cat_activities as $act): 
                            $act_packages = [];
                            foreach ($packages as $pkg) {
                                if (header_activity_package_match($act, $pkg)) {
                                    $act_packages[] = $pkg;
                                }
                            }
                            if (empty($act_packages)) continue;
                            $col_count++;
                        ?>
                          <div class="mega-menu-column mobile-menu-group">
                            <a href="/trekking/<?php echo htmlspecialchars($act['slug']); ?>" class="mobile-region-toggle" aria-expanded="false">
                              <span class="mega-col-title" style="color: #000000; font-weight: 800; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 0.75rem; transition: color 0.2s;" onmouseover="this.style.color='var(--color-secondary)'" onmouseout="this.style.color='#000000'">
                                <?php echo htmlspecialchars($act['name']); ?>
                              </span>
                              <span class="mobile-region-arrow" aria-hidden="true">›</span>
                            </a>
                            <ul class="mega-col-links">
                              <?php foreach ($act_packages as $pkg): ?>
                                <li>
                                  <a href="/trekking/<?php echo htmlspecialchars($pkg['slug']); ?>" class="mega-col-link package-menu-link">
                                    <span class="package-link-prefix" aria-hidden="true">&#9658;&#9658;</span>
                                    <?php echo htmlspecialchars($pkg['title']); ?>
                                  </a>
                                </li>
                              <?php endforeach; ?>
                            </ul>
                          </div>
                        <?php endforeach; ?>
                      </div>
                      
                    <?php elseif ($is_company_info): ?>
                      <!-- COMPANY INFO SUBMENUS -->
                      <div style="display:flex; flex-direction:column; gap:0.25rem;">
                        <a href="/page/why-travel-with-us" class="dropdown-link">Why Travel With Us?</a>
                        <a href="/page/about-us" class="dropdown-link">About Us</a>
                        <a href="/page/our-team" class="dropdown-link">Our Team</a>
                        <a href="/page/legal-documents" class="dropdown-link">Legal Documents</a>
                        <a href="/page/terms-and-conditions" class="dropdown-link">Terms &amp; Conditions</a>
                        <a href="/page/awards" class="dropdown-link">Awards</a>
                      </div>
                    <?php else: ?>
                      <!-- STANDARD DROPDOWN -->
                      <div style="display:flex; flex-direction:column; gap:0.25rem;">
                        <?php 
                        if (empty($cat_activities)):
                            // Fallback direct packages
                            foreach ($packages as $pkg) {
                                if ($pkg['category'] === $cat_filter) {
                                    echo '<a href="/' . $cat_path . '/' . htmlspecialchars($pkg['slug']) . '" class="dropdown-link package-menu-link"><span class="package-link-prefix" aria-hidden="true">&#9658;&#9658;</span>' . htmlspecialchars($pkg['title']) . '</a>';
                                }
                            }
                        else:
                            foreach ($cat_activities as $act):
                                $act_packages = [];
                                foreach ($packages as $pkg) {
                                    if (header_activity_package_match($act, $pkg)) {
                                        $act_packages[] = $pkg;
                                    }
                                }
                                if (empty($act_packages)) continue;
                                $act_link = '/' . $cat_path . '/' . htmlspecialchars($act['slug']);
                                echo '<div class="mobile-menu-group standard-menu-group">';
                                echo '<a href="' . $act_link . '" class="standard-region-link mobile-region-toggle" aria-expanded="false"><span>' . htmlspecialchars($act['name']) . '</span><span class="mobile-region-arrow" aria-hidden="true">›</span></a>';
                                echo '<div class="standard-package-list">';
                                foreach ($act_packages as $pkg) {
                                    echo '<a href="/' . $cat_path . '/' . htmlspecialchars($pkg['slug']) . '" class="dropdown-link package-menu-link" style="font-size:0.8rem; padding: 0.25rem 0.5rem;"><span class="package-link-prefix" aria-hidden="true">&#9658;&#9658;</span>' . htmlspecialchars($pkg['title']) . '</a>';
                                }
                                echo '</div></div>';
                            endforeach;
                        endif;
                        ?>
                      </div>
                    <?php endif; ?>

                  </div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </nav>
      </div>

    </div>
    </div>
  </header>
