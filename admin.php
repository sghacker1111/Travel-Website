<?php
function is_https_request() {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        return true;
    }
    return false;
}

function set_site_cookie($name, $value, $expires, $path = '/admin') {
    global $secure_cookie;

    if (PHP_VERSION_ID >= 70300) {
        setcookie($name, $value, [
            'expires' => $expires,
            'path' => $path,
            'domain' => '',
            'secure' => $secure_cookie,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        setcookie($name, $value, $expires, $path . '; samesite=Lax', '', $secure_cookie, true);
    }

    if ($expires > time()) {
        $_COOKIE[$name] = $value;
    } else {
        unset($_COOKIE[$name]);
    }
}

function clear_site_cookie($name, $path = '/admin') {
    set_site_cookie($name, '', time() - 42000, $path);
}

function clear_cookie_variants($name, $paths) {
    global $secure_cookie;

    $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
    $domains = [''];
    if ($host !== '' && strpos($host, '.') !== false) {
        $domains[] = $host;
        $domains[] = '.' . ltrim($host, '.');
    }

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

function admin_url($query = '') {
    $query = ltrim($query, '?');
    return '/admin' . ($query !== '' ? '?' . $query : '');
}

$request_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($request_path === '/admin.php') {
    header('Location: ' . admin_url($_SERVER['QUERY_STRING'] ?? ''), true, 302);
    exit;
}

$secure_cookie = is_https_request();

if (!headers_sent() && isset($_COOKIE['PHPSESSID'])) {
    clear_cookie_variants('PHPSESSID', ['/', '/admin']);
}
if (!headers_sent() && isset($_COOKIE['TMTADMINSESSID'])) {
    clear_cookie_variants('TMTADMINSESSID', ['/', '/admin']);
}

// Define database paths
$packages_path = __DIR__ . '/data/packages.json';
$settings_path = __DIR__ . '/data/settings.json';
$activities_path = __DIR__ . '/data/activities.json';
$blogs_path = __DIR__ . '/data/blogs.json';
$pages_path = __DIR__ . '/data/pages.json';
$theme_path = __DIR__ . '/data/theme.json';
$inquiries_path = __DIR__ . '/data/inquiries.json';

// Load databases
$packages = file_exists($packages_path) ? json_decode(file_get_contents($packages_path), true) : [];
$settings = file_exists($settings_path) ? json_decode(file_get_contents($settings_path), true) : [];
$site_name = isset($settings['company_name']) ? $settings['company_name'] : 'Top Mountain Treks Pvt. Ltd.';
$site_logo = isset($settings['logo_path']) ? $settings['logo_path'] : 'assets/images/logo.png';
$site_whatsapp = isset($settings['whatsapp_number']) ? $settings['whatsapp_number'] : '+977 985-1123456';
$owner_photo_path = isset($settings['owner_photo_path']) ? $settings['owner_photo_path'] : '';
$brand_line_1 = isset($settings['brand_line_1']) ? $settings['brand_line_1'] : 'TOP MOUNTAIN';
$brand_line_2 = isset($settings['brand_line_2']) ? $settings['brand_line_2'] : 'TREKS PVT. LTD.';
$default_nav = [
    ["title" => "Home", "link" => "/"],
    ["title" => "Destination", "link" => "/destination"],
    ["title" => "Trekking", "link" => "/trekking"],
    ["title" => "Peak Climbing", "link" => "/peak-climbing"],
    ["title" => "Tours", "link" => "/tour"],
    ["title" => "Company Info", "link" => "#"],
    ["title" => "Contact Us", "link" => "/contact"]
];
$site_nav = !empty($settings['navigation']) && is_array($settings['navigation']) ? $settings['navigation'] : $default_nav;
$contact_defaults = [
    'address' => "Top Mountain Treks Pvt. Ltd.\nSatghumti Marg, Thamel-26\nKathmandu, Nepal (Post Box: 44600)",
    'landline' => '+977 1-4700720',
    'emergency' => '+977 98511-23456',
    'email' => 'info@topmountaintreks.com',
    'map_lat' => '27.7150',
    'map_lng' => '85.3120',
    'map_zoom' => '16',
    'intro' => 'Plan your custom Himalayan trail itinerary with our expert Nepalese guides.'
];
$contact_settings = array_merge($contact_defaults, isset($settings['contact']) && is_array($settings['contact']) ? $settings['contact'] : []);
$home_offer_defaults = [
    'default_badge' => 'Hot Deal',
    'default_group_text' => 'Group Offer',
    'default_offer_days' => '30',
    'limited_text' => 'Limited time'
];
$home_offer_settings = array_merge($home_offer_defaults, isset($settings['home_offers']) && is_array($settings['home_offers']) ? $settings['home_offers'] : []);
$usd_to_npr_rate = isset($settings['usd_to_npr_rate']) ? (float)$settings['usd_to_npr_rate'] : 133;
$activities = file_exists($activities_path) ? json_decode(file_get_contents($activities_path), true) : [];
$blogs = file_exists($blogs_path) ? json_decode(file_get_contents($blogs_path), true) : [];
$pages = file_exists($pages_path) ? json_decode(file_get_contents($pages_path), true) : [];
$theme = file_exists($theme_path) ? json_decode(file_get_contents($theme_path), true) : [];
if (!is_array($theme)) $theme = [];
$theme_defaults = [
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
    'slider_text_align' => 'left',
    'logo_font_size' => '22px',
    'trek_title_color' => '#1e3d2f',
    'trek_title_size' => '40px',
    'trek_desc_color' => '#4b5563',
    'trek_desc_size' => '16px',
    'trek_align' => 'left',
    'tour_title_color' => '#1e3d2f',
    'tour_title_size' => '40px',
    'tour_desc_color' => '#4b5563',
    'tour_desc_size' => '16px',
    'tour_align' => 'left',
    'climb_title_color' => '#1e3d2f',
    'climb_title_size' => '40px',
    'climb_desc_color' => '#4b5563',
    'climb_desc_size' => '16px',
    'climb_align' => 'left',
    'page_title_color' => '#1e3d2f',
    'page_title_size' => '40px',
    'page_desc_color' => '#4b5563',
    'page_desc_size' => '16px',
    'page_align' => 'left',
    'blog_title_color' => '#1e3d2f',
    'blog_title_size' => '40px',
    'blog_desc_color' => '#4b5563',
    'blog_desc_size' => '16px',
    'blog_align' => 'left',
    'package_arrow' => ' ▸'
];
foreach ($theme_defaults as $k => $v) {
    if (!isset($theme[$k])) {
        $theme[$k] = $v;
    }
}
$inquiries = file_exists($inquiries_path) ? json_decode(file_get_contents($inquiries_path), true) : [];

if (!is_array($packages)) $packages = [];
if (!is_array($settings)) $settings = [];
if (!is_array($activities)) $activities = [];
if (!is_array($blogs)) $blogs = [];
if (!is_array($pages)) $pages = [];
if (!is_array($inquiries)) $inquiries = [];

// Resolve admin credentials
$admin_creds = isset($settings['admin']) ? $settings['admin'] : [
    'name' => 'Administrator',
    'username' => 'admin',
    'password' => 'Top@#sg@#mountain',
    'email' => 'admin@topmountaintreks.com',
    'phone' => '+977 985-1123456'
];

$login_error = "";
$action_status = "";
$action_msg = "";

function ensure_uploads_dir() {
    $upload_dir = 'assets/images/uploads';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    return $upload_dir;
}

function clean_upload_name($name) {
    return preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($name));
}

function normalize_footer_icons($icons) {
    $normalized = [];
    if (!is_array($icons)) {
        return $normalized;
    }
    foreach ($icons as $icon) {
        if (is_string($icon)) {
            $path = trim($icon);
            if ($path !== '') {
                $normalized[] = [
                    'image' => $path,
                    'url' => '',
                    'label' => 'Affiliation Badge'
                ];
            }
            continue;
        }
        if (!is_array($icon)) {
            continue;
        }
        $path = trim($icon['image'] ?? $icon['path'] ?? '');
        if ($path === '') {
            continue;
        }
        $normalized[] = [
            'image' => $path,
            'url' => trim($icon['url'] ?? ''),
            'label' => trim($icon['label'] ?? 'Affiliation Badge')
        ];
    }
    return array_slice($normalized, 0, 6);
}

function base64url_encode_value($value) {
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function base64url_decode_value($value) {
    $padding = strlen($value) % 4;
    if ($padding > 0) {
        $value .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($value, '-_', '+/'));
}

function admin_auth_secret($settings_path, $admin_creds) {
    $username = isset($admin_creds['username']) ? $admin_creds['username'] : '';
    $password = isset($admin_creds['password']) ? $admin_creds['password'] : '';
    return hash('sha256', __DIR__ . '|' . $settings_path . '|' . $username . '|' . $password . '|top-mountain-admin-cookie', true);
}

function make_signed_admin_token($purpose, $payload, $settings_path, $admin_creds, $ttl_seconds) {
    $payload['purpose'] = $purpose;
    $payload['iat'] = time();
    $payload['exp'] = time() + $ttl_seconds;

    $body = base64url_encode_value(json_encode($payload));
    $signature = base64url_encode_value(hash_hmac('sha256', $body, admin_auth_secret($settings_path, $admin_creds), true));

    return $body . '.' . $signature;
}

function read_signed_admin_token($token, $purpose, $settings_path, $admin_creds) {
    if (!is_string($token) || strpos($token, '.') === false) {
        return false;
    }

    list($body, $signature) = explode('.', $token, 2);
    $expected = base64url_encode_value(hash_hmac('sha256', $body, admin_auth_secret($settings_path, $admin_creds), true));

    if (!hash_equals($expected, $signature)) {
        return false;
    }

    $payload = json_decode(base64url_decode_value($body), true);
    if (!is_array($payload)) {
        return false;
    }
    if (($payload['purpose'] ?? '') !== $purpose) {
        return false;
    }
    if (!isset($payload['exp']) || (int)$payload['exp'] < time()) {
        return false;
    }

    return $payload;
}

function set_admin_auth_cookie($settings_path, $admin_creds) {
    $ttl = 8 * 60 * 60;
    $token = make_signed_admin_token('admin-login', [
        'u' => $admin_creds['username'] ?? '',
        'n' => $admin_creds['name'] ?? 'Administrator'
    ], $settings_path, $admin_creds, $ttl);
    set_site_cookie('TMTADMINAUTH', $token, time() + $ttl, '/admin');
}

function clear_admin_auth_cookie() {
    clear_site_cookie('TMTADMINAUTH', '/admin');
}

function is_admin_authenticated($settings_path, $admin_creds) {
    if (empty($_COOKIE['TMTADMINAUTH'])) {
        return false;
    }

    $payload = read_signed_admin_token($_COOKIE['TMTADMINAUTH'], 'admin-login', $settings_path, $admin_creds);
    if (!is_array($payload)) {
        return false;
    }

    return isset($payload['u']) && hash_equals((string)($admin_creds['username'] ?? ''), (string)$payload['u']);
}

function set_admin_otp_cookie($settings_path, $admin_creds) {
    $ttl = 10 * 60;
    $token = make_signed_admin_token('admin-otp', ['v' => 1], $settings_path, $admin_creds, $ttl);
    set_site_cookie('TMTADMINOTP', $token, time() + $ttl, '/admin');
}

function has_admin_otp_cookie($settings_path, $admin_creds) {
    if (empty($_COOKIE['TMTADMINOTP'])) {
        return false;
    }
    return is_array(read_signed_admin_token($_COOKIE['TMTADMINOTP'], 'admin-otp', $settings_path, $admin_creds));
}

function clear_admin_otp_cookie() {
    clear_site_cookie('TMTADMINOTP', '/admin');
}

function admin_delete_confirmed($admin_creds) {
    $password = isset($_POST['delete_confirm_password']) ? (string)$_POST['delete_confirm_password'] : '';
    return $password !== '' && hash_equals((string)($admin_creds['password'] ?? ''), $password);
}

function block_unconfirmed_delete(&$action_status, &$action_msg) {
    $action_status = "error";
    $action_msg = "Delete cancelled. Please enter the current admin password to confirm this action.";
}

$is_logged = is_admin_authenticated($settings_path, $admin_creds);

// 1. AJAX Media Upload Handler (Req 4, 6)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_upload_media'])) {
    header('Content-Type: application/json');
    if (!$is_logged) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
        exit;
    }
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['media_file']['tmp_name'];
        $name = clean_upload_name($_FILES['media_file']['name']);
        $upload_dir = ensure_uploads_dir();
        $target = $upload_dir . '/' . time() . '_' . $name;
        if (move_uploaded_file($tmp_name, $target)) {
            echo json_encode(['success' => true, 'url' => '/' . $target]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error.']);
    }
    exit;
}

// 2. Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    clear_admin_auth_cookie();
    clear_admin_otp_cookie();
    header('Location: ' . admin_url());
    exit;
}

// 3. Handle Login Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $user = isset($_POST['username']) ? trim($_POST['username']) : '';
    $pass = isset($_POST['password']) ? trim($_POST['password']) : '';

    if ($user === $admin_creds['username'] && $pass === $admin_creds['password']) {
        set_admin_auth_cookie($settings_path, $admin_creds);
        $is_logged = true;
    } else {
        $login_error = "Invalid username or password.";
    }
}

// 3.5 Forgot Password & OTP Recovery flow
$recovery_state = 'login';
$recovery_error = '';
$recovery_msg = '';

if (!$is_logged) {
    if (isset($_GET['action']) && $_GET['action'] === 'forgot_password') {
        $recovery_state = 'send_otp';
    }
    
    // POST request to send OTP
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_send_otp'])) {
        if (!isset($settings['admin'])) {
            $settings['admin'] = $admin_creds;
        }
        // Generate 6 digit OTP
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $expiry = time() + 600; // 10 minutes from now
        
        $settings['admin']['recovery_otp'] = $otp;
        $settings['admin']['recovery_otp_expiry'] = $expiry;
        file_put_contents($settings_path, json_encode($settings, JSON_PRETTY_PRINT));
        
        // Write to recovery log file
        $otp_log_file = 'data/recovery_otp.txt';
        file_put_contents($otp_log_file, "OTP: " . $otp . " | Generated at: " . date("Y-m-d H:i:s") . " | Expiry: " . date("Y-m-d H:i:s", $expiry) . "\n");
        
        $recovery_state = 'verify_otp';
        $recovery_msg = "A 6-digit verification code has been logged. Please check your recovery channel.";
    }
    
    // POST request to verify OTP
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_verify_otp'])) {
        $input_otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
        $stored_otp = isset($settings['admin']['recovery_otp']) ? $settings['admin']['recovery_otp'] : '';
        $expiry = isset($settings['admin']['recovery_otp_expiry']) ? (int)$settings['admin']['recovery_otp_expiry'] : 0;
        
        if (!empty($stored_otp) && $input_otp === $stored_otp && time() <= $expiry) {
            $recovery_state = 'reset_password';
            set_admin_otp_cookie($settings_path, $admin_creds);
        } else {
            $recovery_state = 'verify_otp';
            $recovery_error = "Invalid or expired OTP code.";
        }
    }
    
    // POST request to reset password
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_reset_password'])) {
        if (has_admin_otp_cookie($settings_path, $admin_creds)) {
            $new_pass = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
            $confirm_pass = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
            
            if (empty($new_pass)) {
                $recovery_state = 'reset_password';
                $recovery_error = "Password cannot be empty.";
            } elseif ($new_pass !== $confirm_pass) {
                $recovery_state = 'reset_password';
                $recovery_error = "Passwords do not match.";
            } else {
                if (!isset($settings['admin'])) {
                    $settings['admin'] = $admin_creds;
                }
                $settings['admin']['password'] = $new_pass;
                unset($settings['admin']['recovery_otp']);
                unset($settings['admin']['recovery_otp_expiry']);
                file_put_contents($settings_path, json_encode($settings, JSON_PRETTY_PRINT));
                
                clear_admin_otp_cookie();
                
                $recovery_state = 'login';
                $login_error = "Password updated successfully. Please log in with your new password.";
            }
        } else {
            $recovery_state = 'login';
            $login_error = "Session expired or unauthorized.";
        }
    }
}

// 4. Handle Admin Actions
if ($is_logged && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ACTION A: Delete Package
    if (isset($_POST['delete_package'])) {
        if (!admin_delete_confirmed($admin_creds)) {
            block_unconfirmed_delete($action_status, $action_msg);
        } else {
            $id = $_POST['package_id'];
            $filtered = [];
            $deleted_title = "";
            foreach ($packages as $pkg) {
                if ($pkg['id'] === $id) {
                    $deleted_title = $pkg['title'];
                    continue;
                }
                $filtered[] = $pkg;
            }
            $packages = $filtered;
            file_put_contents($packages_path, json_encode($packages, JSON_PRETTY_PRINT));
            $action_status = "success";
            $action_msg = "Successfully deleted package: '{$deleted_title}'";
        }
    }

    // ACTION B: Theme Colors
    if (isset($_POST['theme_submit'])) {
        $primary = $_POST['primary_color'];
        $secondary = $_POST['secondary_color'];
        $accent = $_POST['accent_color'];
        $dropdown_icon = isset($_POST['dropdown_icon']) ? $_POST['dropdown_icon'] : 'triangle';
        $header_bg = isset($_POST['header_bg']) ? $_POST['header_bg'] : '#ffffff';
        $header_text = isset($_POST['header_text']) ? $_POST['header_text'] : '#1f2937';
        $title_color = isset($_POST['title_color']) ? $_POST['title_color'] : '#1e3d2f';
        $page_bg = isset($_POST['page_bg']) ? $_POST['page_bg'] : '#ffffff';
        $footer_bg = isset($_POST['footer_bg']) ? $_POST['footer_bg'] : '#0f172a';
        $footer_text = isset($_POST['footer_text']) ? $_POST['footer_text'] : '#9ca3af';
        $menu_bg = isset($_POST['menu_bg']) ? $_POST['menu_bg'] : '#ffffff';
        $menu_text_size = isset($_POST['menu_text_size']) ? $_POST['menu_text_size'] : '15px';
        $menu_padding = isset($_POST['menu_padding']) ? $_POST['menu_padding'] : '8px';
        $submenu_link_color = isset($_POST['submenu_link_color']) ? $_POST['submenu_link_color'] : '#4b5563';
        $submenu_hover_color = isset($_POST['submenu_hover_color']) ? $_POST['submenu_hover_color'] : '#0077b6';
        $submenu_prefix_color = isset($_POST['submenu_prefix_color']) ? $_POST['submenu_prefix_color'] : '#111827';
        $mega_menu_width = isset($_POST['mega_menu_width']) ? $_POST['mega_menu_width'] : '1240px';
        $slider_title_color = isset($_POST['slider_title_color']) ? $_POST['slider_title_color'] : '#ffffff';
        $slider_title_size = isset($_POST['slider_title_size']) ? $_POST['slider_title_size'] : '50px';
        $slider_desc_color = isset($_POST['slider_desc_color']) ? $_POST['slider_desc_color'] : '#ffffff';
        $slider_desc_size = isset($_POST['slider_desc_size']) ? $_POST['slider_desc_size'] : '18px';
        $slider_text_align = isset($_POST['slider_text_align']) ? $_POST['slider_text_align'] : 'left';
        $logo_font_size = isset($_POST['logo_font_size']) ? $_POST['logo_font_size'] : '22px';
        
        $trek_title_color = isset($_POST['trek_title_color']) ? $_POST['trek_title_color'] : '#1e3d2f';
        $trek_title_size = isset($_POST['trek_title_size']) ? $_POST['trek_title_size'] : '40px';
        $trek_desc_color = isset($_POST['trek_desc_color']) ? $_POST['trek_desc_color'] : '#4b5563';
        $trek_desc_size = isset($_POST['trek_desc_size']) ? $_POST['trek_desc_size'] : '16px';
        $trek_align = isset($_POST['trek_align']) ? $_POST['trek_align'] : 'left';

        $tour_title_color = isset($_POST['tour_title_color']) ? $_POST['tour_title_color'] : '#1e3d2f';
        $tour_title_size = isset($_POST['tour_title_size']) ? $_POST['tour_title_size'] : '40px';
        $tour_desc_color = isset($_POST['tour_desc_color']) ? $_POST['tour_desc_color'] : '#4b5563';
        $tour_desc_size = isset($_POST['tour_desc_size']) ? $_POST['tour_desc_size'] : '16px';
        $tour_align = isset($_POST['tour_align']) ? $_POST['tour_align'] : 'left';

        $climb_title_color = isset($_POST['climb_title_color']) ? $_POST['climb_title_color'] : '#1e3d2f';
        $climb_title_size = isset($_POST['climb_title_size']) ? $_POST['climb_title_size'] : '40px';
        $climb_desc_color = isset($_POST['climb_desc_color']) ? $_POST['climb_desc_color'] : '#4b5563';
        $climb_desc_size = isset($_POST['climb_desc_size']) ? $_POST['climb_desc_size'] : '16px';
        $climb_align = isset($_POST['climb_align']) ? $_POST['climb_align'] : 'left';

        $page_title_color = isset($_POST['page_title_color']) ? $_POST['page_title_color'] : '#1e3d2f';
        $page_title_size = isset($_POST['page_title_size']) ? $_POST['page_title_size'] : '40px';
        $page_desc_color = isset($_POST['page_desc_color']) ? $_POST['page_desc_color'] : '#4b5563';
        $page_desc_size = isset($_POST['page_desc_size']) ? $_POST['page_desc_size'] : '16px';
        $page_align = isset($_POST['page_align']) ? $_POST['page_align'] : 'left';        $blog_title_color = isset($_POST['blog_title_color']) ? $_POST['blog_title_color'] : '#1e3d2f';
        $blog_title_size = isset($_POST['blog_title_size']) ? $_POST['blog_title_size'] : '40px';
        $blog_desc_color = isset($_POST['blog_desc_color']) ? $_POST['blog_desc_color'] : '#4b5563';
        $blog_desc_size = isset($_POST['blog_desc_size']) ? $_POST['blog_desc_size'] : '16px';
        $blog_align = isset($_POST['blog_align']) ? $_POST['blog_align'] : 'left';
        
        $package_arrow = isset($_POST['package_arrow']) ? $_POST['package_arrow'] : ' ▸';
 
        $theme = [
            'primary' => $primary,
            'secondary' => $secondary,
            'accent' => $accent,
            'dropdown_icon' => $dropdown_icon,
            'header_bg' => $header_bg,
            'header_text' => $header_text,
            'title_color' => $title_color,
            'page_bg' => $page_bg,
            'footer_bg' => $footer_bg,
            'footer_text' => $footer_text,
            'menu_bg' => $menu_bg,
            'menu_text_size' => $menu_text_size,
            'menu_padding' => $menu_padding,
            'submenu_link_color' => $submenu_link_color,
            'submenu_hover_color' => $submenu_hover_color,
            'submenu_prefix_color' => $submenu_prefix_color,
            'package_arrow' => $package_arrow,
            'mega_menu_width' => $mega_menu_width,
            'slider_title_color' => $slider_title_color,
            'slider_title_size' => $slider_title_size,
            'slider_desc_color' => $slider_desc_color,
            'slider_desc_size' => $slider_desc_size,
            'slider_text_align' => $slider_text_align,
            'logo_font_size' => $logo_font_size,
            'trek_title_color' => $trek_title_color,
            'trek_title_size' => $trek_title_size,
            'trek_desc_color' => $trek_desc_color,
            'trek_desc_size' => $trek_desc_size,
            'trek_align' => $trek_align,
            'tour_title_color' => $tour_title_color,
            'tour_title_size' => $tour_title_size,
            'tour_desc_color' => $tour_desc_color,
            'tour_desc_size' => $tour_desc_size,
            'tour_align' => $tour_align,
            'climb_title_color' => $climb_title_color,
            'climb_title_size' => $climb_title_size,
            'climb_desc_color' => $climb_desc_color,
            'climb_desc_size' => $climb_desc_size,
            'climb_align' => $climb_align,
            'page_title_color' => $page_title_color,
            'page_title_size' => $page_title_size,
            'page_desc_color' => $page_desc_color,
            'page_desc_size' => $page_desc_size,
            'page_align' => $page_align,
            'blog_title_color' => $blog_title_color,
            'blog_title_size' => $blog_title_size,
            'blog_desc_color' => $blog_desc_color,
            'blog_desc_size' => $blog_desc_size,
            'blog_align' => $blog_align
        ];
        file_put_contents($theme_path, json_encode($theme, JSON_PRETTY_PRINT));

        // Generate theme-custom.css
        $css = "/* Dynamic Custom Stylesheet - Top Mountain Treks */
:root {
  --color-primary: {$primary};
  --color-primary-hover: {$primary}ee;
  --color-secondary: {$secondary};
  --color-secondary-hover: {$secondary}ee;
  --color-accent: {$accent};
  --color-accent-hover: {$accent}ee;
  --color-header-bg: {$header_bg};
  --color-header-text: {$header_text};
  --color-title: {$title_color};
  --color-page-bg: {$page_bg};
  --color-footer-bg: {$footer_bg};
  --color-footer-text: {$footer_text};
  --color-menu-bg: {$menu_bg};
  --menu-text-size: {$menu_text_size};
  --menu-padding: {$menu_padding};
  --submenu-link-color: {$submenu_link_color};
  --submenu-hover-color: {$submenu_hover_color};
  --submenu-prefix-color: {$submenu_prefix_color};
  --mega-menu-width: {$mega_menu_width};
  --slider-title-color: {$slider_title_color};
  --slider-title-size: {$slider_title_size};
  --slider-desc-color: {$slider_desc_color};
  --slider-desc-size: {$slider_desc_size};
  --slider-text-align: {$slider_text_align};
  --logo-font-size: {$logo_font_size};
  --trek-title-color: {$trek_title_color};
  --trek-title-size: {$trek_title_size};
  --trek-desc-color: {$trek_desc_color};
  --trek-desc-size: {$trek_desc_size};
  --trek-align: {$trek_align};
  --tour-title-color: {$tour_title_color};
  --tour-title-size: {$tour_title_size};
  --tour-desc-color: {$tour_desc_color};
  --tour-desc-size: {$tour_desc_size};
  --tour-align: {$tour_align};
  --climb-title-color: {$climb_title_color};
  --climb-title-size: {$climb_title_size};
  --climb-desc-color: {$climb_desc_color};
  --climb-desc-size: {$climb_desc_size};
  --climb-align: {$climb_align};
  --page-title-color: {$page_title_color};
  --page-title-size: {$page_title_size};
  --page-desc-color: {$page_desc_color};
  --page-desc-size: {$page_desc_size};
  --page-align: {$page_align};
  --blog-title-color: {$blog_title_color};
  --blog-title-size: {$blog_title_size};
  --blog-desc-color: {$blog_desc_color};
  --blog-desc-size: {$blog_desc_size};
  --blog-align: {$blog_align};
}";
        file_put_contents(__DIR__ . '/assets/css/theme-custom.css', $css);
        $action_status = "success";
        $action_msg = "Branding theme customizer colors applied dynamically.";
    }

    // ACTION C: Save Settings (Branding, Logo, WhatsApp, Navigation, Footer Icons, Custom head/foot)
    if (isset($_POST['save_settings'])) {
        $logo_path = isset($settings['logo_path']) ? $settings['logo_path'] : 'assets/images/logo.png';
        $owner_photo_path = isset($settings['owner_photo_path']) ? $settings['owner_photo_path'] : '';
        
        // Handle logo upload
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['logo_file']['tmp_name'];
            $name = clean_upload_name($_FILES['logo_file']['name']);
            if (!file_exists('assets/images')) {
                mkdir('assets/images', 0755, true);
            }
            $target_logo = 'assets/images/' . $name;
            if (move_uploaded_file($tmp_name, $target_logo)) {
                $logo_path = $target_logo;
            }
        }

        if (isset($_FILES['owner_photo_file']) && $_FILES['owner_photo_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['owner_photo_file']['tmp_name'];
            $name = clean_upload_name($_FILES['owner_photo_file']['name']);
            $upload_dir = ensure_uploads_dir();
            $target_owner = $upload_dir . '/' . time() . '_owner_' . $name;
            if (move_uploaded_file($tmp_name, $target_owner)) {
                $owner_photo_path = $target_owner;
            }
        }

        // Handle footer icons with optional outbound links.
        $footer_icons = normalize_footer_icons($settings['footer_icons'] ?? []);
        if (isset($_POST['footer_icon_paths']) && is_array($_POST['footer_icon_paths'])) {
            $rebuilt_icons = [];
            foreach ($_POST['footer_icon_paths'] as $fidx => $path) {
                $path = trim($path);
                if ($path === '') continue;
                $rebuilt_icons[] = [
                    'image' => $path,
                    'url' => trim($_POST['footer_icon_urls'][$fidx] ?? ''),
                    'label' => trim($_POST['footer_icon_labels'][$fidx] ?? 'Affiliation Badge')
                ];
            }
            $footer_icons = $rebuilt_icons;
        }
        if (isset($_FILES['footer_logos'])) {
            $upload_dir = ensure_uploads_dir();
            foreach ($_FILES['footer_logos']['tmp_name'] as $fkey => $ftmp) {
                if (count($footer_icons) >= 6) break;
                if ($_FILES['footer_logos']['error'][$fkey] === UPLOAD_ERR_OK) {
                    $fname = clean_upload_name($_FILES['footer_logos']['name'][$fkey]);
                    $target = $upload_dir . '/' . time() . '_' . $fname;
                    if (move_uploaded_file($ftmp, $target)) {
                        $footer_icons[] = [
                            'image' => $target,
                            'url' => trim($_POST['footer_logo_links'][$fkey] ?? ''),
                            'label' => trim($_POST['footer_logo_labels'][$fkey] ?? 'Affiliation Badge')
                        ];
                    }
                }
            }
        }
        $footer_icons = array_slice(normalize_footer_icons($footer_icons), 0, 6);

        // Delete footer icon action
        if (isset($_POST['delete_footer_icon_idx'])) {
            if (!admin_delete_confirmed($admin_creds)) {
                block_unconfirmed_delete($action_status, $action_msg);
            } else {
                $del_idx = (int)$_POST['delete_footer_icon_idx'];
                if (isset($footer_icons[$del_idx])) {
                    @unlink($footer_icons[$del_idx]['image']);
                    array_splice($footer_icons, $del_idx, 1);
                }
            }
        }

        // Rebuild Navigation menu
        $nav = [];
        if (isset($_POST['nav_titles'])) {
            foreach ($_POST['nav_titles'] as $idx => $title) {
                if (empty(trim($title))) continue;
                $nav[] = [
                    'title' => trim($title),
                    'link' => trim($_POST['nav_links'][$idx])
                ];
            }
        }

        $contact_settings = isset($_POST['contact_address']) ? [
            'address' => trim($_POST['contact_address'] ?? ''),
            'landline' => trim($_POST['contact_landline'] ?? ''),
            'emergency' => trim($_POST['contact_emergency'] ?? ''),
            'email' => trim($_POST['contact_email'] ?? ''),
            'map_lat' => trim($_POST['contact_map_lat'] ?? ''),
            'map_lng' => trim($_POST['contact_map_lng'] ?? ''),
            'map_zoom' => trim($_POST['contact_map_zoom'] ?? '16'),
            'intro' => trim($_POST['contact_intro'] ?? '')
        ] : (isset($settings['contact']) && is_array($settings['contact']) ? $settings['contact'] : $contact_defaults);
        $home_offer_settings = isset($_POST['offer_default_badge']) ? [
            'default_badge' => trim($_POST['offer_default_badge'] ?? ''),
            'default_group_text' => trim($_POST['offer_default_group_text'] ?? ''),
            'default_offer_days' => trim($_POST['offer_default_days'] ?? ''),
            'limited_text' => trim($_POST['offer_limited_text'] ?? '')
        ] : (isset($settings['home_offers']) && is_array($settings['home_offers']) ? $settings['home_offers'] : $home_offer_defaults);
        $usd_to_npr_rate = isset($_POST['usd_to_npr_rate']) ? (float)$_POST['usd_to_npr_rate'] : (isset($settings['usd_to_npr_rate']) ? (float)$settings['usd_to_npr_rate'] : 133);

        $settings = [
            'company_name' => trim($_POST['company_name']),
            'brand_line_1' => strtoupper(trim($_POST['brand_line_1'] ?? 'TOP MOUNTAIN')),
            'brand_line_2' => strtoupper(trim($_POST['brand_line_2'] ?? 'TREKS PVT. LTD.')),
            'logo_path' => $logo_path,
            'owner_photo_path' => $owner_photo_path,
            'whatsapp_number' => trim($_POST['whatsapp_number']),
            'usd_to_npr_rate' => $usd_to_npr_rate,
            'contact' => $contact_settings,
            'home_offers' => $home_offer_settings,
            'navigation' => $nav,
            'footer_icons' => $footer_icons,
            'header_custom_html' => $_POST['header_custom_html'],
            'footer_custom_html' => $_POST['footer_custom_html'],
            'admin' => isset($settings['admin']) ? $settings['admin'] : $admin_creds
        ];
        file_put_contents($settings_path, json_encode($settings, JSON_PRETTY_PRINT));
        if ($action_status !== "error") {
            $action_status = "success";
            $action_msg = "Global company settings, contact details, offers, and navigation updated.";
        }
    }

    // ACTION D: Save Region / Activity Subtitle
    if (isset($_POST['save_activity'])) {
        $act_id = isset($_POST['act_id']) && !empty($_POST['act_id']) ? $_POST['act_id'] : 'act_' . uniqid();
        $name = trim($_POST['act_name']);
        $category = $_POST['act_category'];
        $slug = isset($_POST['act_slug']) && !empty($_POST['act_slug']) ? trim($_POST['act_slug']) : '';
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]+/', '-', $name));
        }
        $desc = trim($_POST['act_description']);
        $act_seo_title = trim($_POST['act_seo_title'] ?? '');
        $act_seo_description = trim($_POST['act_seo_description'] ?? '');
        $act_seo_keywords = trim($_POST['act_seo_keywords'] ?? '');

        // Handle Route Map Image (Req 5)
        $route_map_path = isset($_POST['existing_act_route_map']) ? $_POST['existing_act_route_map'] : '';
        if (isset($_FILES['route_map_file']) && $_FILES['route_map_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['route_map_file']['tmp_name'];
            $rname = clean_upload_name($_FILES['route_map_file']['name']);
            $upload_dir = ensure_uploads_dir();
            $target = $upload_dir . '/' . time() . '_' . $rname;
            if (move_uploaded_file($tmp_name, $target)) {
                $route_map_path = $target;
            }
        }

        $activity_obj = [
            'id' => $act_id,
            'name' => $name,
            'slug' => $slug,
            'category' => $category,
            'description' => $desc,
            'route_map' => $route_map_path,
            'seo_title' => $act_seo_title,
            'seo_description' => $act_seo_description,
            'seo_keywords' => $act_seo_keywords
        ];

        $found = false;
        foreach ($activities as &$act) {
            if ($act['id'] === $act_id) {
                $act = $activity_obj;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $activities[] = $activity_obj;
        }

        file_put_contents($activities_path, json_encode($activities, JSON_PRETTY_PRINT));
        $action_status = "success";
        $action_msg = "Activity/Region subtitle category saved successfully.";
    }

    // ACTION E: Delete Region / Activity
    if (isset($_POST['delete_activity'])) {
        if (!admin_delete_confirmed($admin_creds)) {
            block_unconfirmed_delete($action_status, $action_msg);
        } else {
            $id = $_POST['act_id'];
            $filtered = [];
            foreach ($activities as $act) {
                if ($act['id'] === $id) continue;
                $filtered[] = $act;
            }
            $activities = $filtered;
            file_put_contents($activities_path, json_encode($activities, JSON_PRETTY_PRINT));
            $action_status = "success";
            $action_msg = "Deleted activity subtitle category.";
        }
    }

    // ACTION F: Save Blog Post
    if (isset($_POST['save_blog'])) {
        $blog_id = isset($_POST['blog_id']) && !empty($_POST['blog_id']) ? $_POST['blog_id'] : 'blog_' . uniqid();
        $title = trim($_POST['blog_title']);
        $slug = isset($_POST['blog_slug']) && !empty($_POST['blog_slug']) ? trim($_POST['blog_slug']) : '';
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]+/', '-', $title));
        }
        $content = $_POST['blog_content'];
        $date = trim($_POST['blog_date']);
        $seo_title = trim($_POST['seo_title']);
        $seo_description = trim($_POST['seo_description']);
        $seo_keywords = trim($_POST['seo_keywords']);

        // Handle Blog Main Image
        $image_path = isset($_POST['existing_blog_image']) ? $_POST['existing_blog_image'] : 'assets/images/packages/hero_home.jpg';
        if (isset($_FILES['blog_image_file']) && $_FILES['blog_image_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['blog_image_file']['tmp_name'];
            $iname = clean_upload_name($_FILES['blog_image_file']['name']);
            $upload_dir = ensure_uploads_dir();
            $target = $upload_dir . '/' . time() . '_' . $iname;
            if (move_uploaded_file($tmp_name, $target)) {
                $image_path = $target;
            }
        }

        // Handle Blog Route Map
        $route_map_path = isset($_POST['existing_blog_route_map']) ? $_POST['existing_blog_route_map'] : '';
        if (isset($_FILES['route_map_file']) && $_FILES['route_map_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['route_map_file']['tmp_name'];
            $rname = clean_upload_name($_FILES['route_map_file']['name']);
            $upload_dir = ensure_uploads_dir();
            $target = $upload_dir . '/' . time() . '_' . $rname;
            if (move_uploaded_file($tmp_name, $target)) {
                $route_map_path = $target;
            }
        }

        $blog_obj = [
            'id' => $blog_id,
            'title' => $title,
            'slug' => $slug,
            'image' => $image_path,
            'content' => $content,
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'seo_keywords' => $seo_keywords,
            'date' => $date,
            'route_map' => $route_map_path
        ];

        $found = false;
        foreach ($blogs as &$b) {
            if ($b['id'] === $blog_id) {
                $b = $blog_obj;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $blogs[] = $blog_obj;
        }

        file_put_contents($blogs_path, json_encode($blogs, JSON_PRETTY_PRINT));
        $action_status = "success";
        $action_msg = "Blog article saved.";
    }

    // ACTION G: Delete Blog Post
    if (isset($_POST['delete_blog'])) {
        if (!admin_delete_confirmed($admin_creds)) {
            block_unconfirmed_delete($action_status, $action_msg);
        } else {
            $id = $_POST['blog_id'];
            $filtered = [];
            foreach ($blogs as $b) {
                if ($b['id'] === $id) continue;
                $filtered[] = $b;
            }
            $blogs = $filtered;
            file_put_contents($blogs_path, json_encode($blogs, JSON_PRETTY_PRINT));
            $action_status = "success";
            $action_msg = "Deleted blog article.";
        }
    }

    // ACTION H: Save Static Page
    if (isset($_POST['save_page'])) {
        $page_id = isset($_POST['page_id']) && !empty($_POST['page_id']) ? $_POST['page_id'] : 'page_' . uniqid();
        $title = trim($_POST['page_title']);
        $slug = isset($_POST['page_slug']) && !empty($_POST['page_slug']) ? trim($_POST['page_slug']) : '';
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]+/', '-', $title));
        }
        $content = $_POST['page_content'];
        $seo_title = trim($_POST['seo_title']);
        $seo_description = trim($_POST['seo_description']);
        $seo_keywords = trim($_POST['seo_keywords'] ?? '');

        // Handle Static Page Route Map
        $route_map_path = isset($_POST['existing_page_route_map']) ? $_POST['existing_page_route_map'] : '';
        if (isset($_FILES['route_map_file']) && $_FILES['route_map_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['route_map_file']['tmp_name'];
            $rname = clean_upload_name($_FILES['route_map_file']['name']);
            $upload_dir = ensure_uploads_dir();
            $target = $upload_dir . '/' . time() . '_' . $rname;
            if (move_uploaded_file($tmp_name, $target)) {
                $route_map_path = $target;
            }
        }

        // Handle Static Page Document Uploads for legal files and PDFs
        $document_file_path = isset($_POST['existing_page_document_file']) ? $_POST['existing_page_document_file'] : '';
        if (isset($_FILES['page_document_file']) && $_FILES['page_document_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['page_document_file']['tmp_name'];
            $dname = clean_upload_name($_FILES['page_document_file']['name']);
            $upload_dir = ensure_uploads_dir();
            $target = $upload_dir . '/' . time() . '_' . $dname;
            if (move_uploaded_file($tmp_name, $target)) {
                $document_file_path = $target;
            }
        }

        $page_obj = [
            'id' => $page_id,
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'seo_keywords' => $seo_keywords,
            'route_map' => $route_map_path,
            'document_file' => $document_file_path
        ];

        $found = false;
        foreach ($pages as &$p) {
            if ($p['id'] === $page_id) {
                $p = $page_obj;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $pages[] = $page_obj;
        }

        file_put_contents($pages_path, json_encode($pages, JSON_PRETTY_PRINT));
        $action_status = "success";
        $action_msg = "Static page details saved.";
    }

    // ACTION I: Delete Static Page
    if (isset($_POST['delete_page'])) {
        if (!admin_delete_confirmed($admin_creds)) {
            block_unconfirmed_delete($action_status, $action_msg);
        } else {
            $id = $_POST['page_id'];
            $filtered = [];
            foreach ($pages as $p) {
                if ($p['id'] === $id) continue;
                $filtered[] = $p;
            }
            $pages = $filtered;
            file_put_contents($pages_path, json_encode($pages, JSON_PRETTY_PRINT));
            $action_status = "success";
            $action_msg = "Deleted static page.";
        }
    }

    // ACTION J: Approve Review
    if (isset($_POST['approve_review'])) {
        $pkg_id = $_POST['rev_pkg_id'];
        $rev_idx = (int)$_POST['rev_idx'];

        foreach ($packages as &$pkg) {
            if ($pkg['id'] === $pkg_id) {
                if (isset($pkg['reviews'][$rev_idx])) {
                    $pkg['reviews'][$rev_idx]['approved'] = true;
                }
                break;
            }
        }
        file_put_contents($packages_path, json_encode($packages, JSON_PRETTY_PRINT));
        $action_status = "success";
        $action_msg = "Traveler review approved and set live.";
    }

    // ACTION K: Delete Review (Req 10)
    if (isset($_POST['delete_review'])) {
        if (!admin_delete_confirmed($admin_creds)) {
            block_unconfirmed_delete($action_status, $action_msg);
        } else {
            $pkg_id = $_POST['rev_pkg_id'];
            $rev_idx = (int)$_POST['rev_idx'];

            foreach ($packages as &$pkg) {
                if ($pkg['id'] === $pkg_id) {
                    if (isset($pkg['reviews'][$rev_idx])) {
                        array_splice($pkg['reviews'], $rev_idx, 1);
                    }
                    break;
                }
            }
            file_put_contents($packages_path, json_encode($packages, JSON_PRETTY_PRINT));
            $action_status = "success";
            $action_msg = "Traveler review deleted.";
        }
    }

    // ACTION L: Create or Edit Package
    if (isset($_POST['save_package'])) {
        $id = isset($_POST['id']) && !empty($_POST['id']) ? $_POST['id'] : 'pkg_' . uniqid();
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]+/', '-', $title));
        }

        // Highlights & Permits: support HTML blocks directly from CKEditor
        $highlights = $_POST['highlights'];
        $includes = $_POST['costIncludes'];
        $excludes = $_POST['costExcludes'];
        $permits = $_POST['requiredPermits'];

        // Fallback checks for source links
        $sourceLinks = array_filter(array_map('trim', explode("\n", $_POST['sourceLinks'])));

        $tripFacts = [];
        if (isset($_POST['fact_labels']) && is_array($_POST['fact_labels'])) {
            foreach ($_POST['fact_labels'] as $idx => $label) {
                $label = trim($label);
                $value = trim($_POST['fact_values'][$idx] ?? '');
                if ($label === '' || $value === '') continue;
                $tripFacts[] = [
                    'label' => $label,
                    'value' => $value
                ];
            }
        }

        $detailSections = [];
        if (isset($_POST['section_titles']) && is_array($_POST['section_titles'])) {
            foreach ($_POST['section_titles'] as $idx => $section_title) {
                $section_title = trim($section_title);
                $section_content = $_POST['section_contents'][$idx] ?? '';
                if ($section_title === '' || trim(strip_tags($section_content)) === '') continue;
                $detailSections[] = [
                    'title' => $section_title,
                    'content' => $section_content
                ];
            }
        }

        $packingList = array_filter(array_map('trim', explode("\n", $_POST['packing_list'] ?? '')));
        $imageAltTexts = array_filter(array_map('trim', explode("\n", $_POST['image_alt_texts'] ?? '')));
        $tags = array_filter(array_map('trim', explode("\n", $_POST['package_tags'] ?? '')));

        // Itinerary Days
        $itinerary = [];
        if (isset($_POST['itinerary_days'])) {
            foreach ($_POST['itinerary_days'] as $idx => $day_num) {
                if (empty($_POST['itinerary_titles'][$idx])) continue;
                $itinerary[] = [
                    'day' => (int)$day_num,
                    'title' => trim($_POST['itinerary_titles'][$idx]),
                    'desc' => $_POST['itinerary_descs'][$idx],
                    'accommodation' => isset($_POST['itinerary_accomm'][$idx]) ? $_POST['itinerary_accomm'][$idx] : 'Teahouse Lodge',
                    'meals' => isset($_POST['itinerary_meals'][$idx]) ? $_POST['itinerary_meals'][$idx] : 'Breakfast, Lunch, Dinner',
                    'location' => isset($_POST['itinerary_location'][$idx]) ? trim($_POST['itinerary_location'][$idx]) : '',
                    'x' => isset($_POST['itinerary_x'][$idx]) && $_POST['itinerary_x'][$idx] !== '' ? (int)$_POST['itinerary_x'][$idx] : null,
                    'y' => isset($_POST['itinerary_y'][$idx]) && $_POST['itinerary_y'][$idx] !== '' ? (int)$_POST['itinerary_y'][$idx] : null
                ];
            }
        }

        // FAQs
        $faqs = [];
        if (isset($_POST['faq_qs'])) {
            foreach ($_POST['faq_qs'] as $idx => $q) {
                if (empty(trim($q))) continue;
                $faqs[] = [
                    'q' => trim($q),
                    'a' => $_POST['faq_as'][$idx]
                ];
            }
        }

        // Handle Main Image upload
        $image_path = isset($_POST['existing_image']) ? $_POST['existing_image'] : 'assets/images/packages/hero_home.jpg';
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['image_file']['tmp_name'];
            $iname = clean_upload_name($_FILES['image_file']['name']);
            $upload_dir = ensure_uploads_dir();
            $target = $upload_dir . '/' . time() . '_' . $iname;
            if (move_uploaded_file($tmp_name, $target)) {
                $image_path = $target;
            }
        }

        // Handle Route Map upload (Req 5)
        $route_map_path = isset($_POST['existing_route_map']) ? $_POST['existing_route_map'] : '';
        if (isset($_FILES['route_map_file']) && $_FILES['route_map_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['route_map_file']['tmp_name'];
            $rname = clean_upload_name($_FILES['route_map_file']['name']);
            $upload_dir = ensure_uploads_dir();
            $target = $upload_dir . '/' . time() . '_' . $rname;
            if (move_uploaded_file($tmp_name, $target)) {
                $route_map_path = $target;
            }
        }

        // Handle Image Gallery uploads (Req 7)
        $gallery = [];
        if (isset($_POST['existing_gallery_paths'])) {
            $gallery = $_POST['existing_gallery_paths'];
        }
        if (isset($_FILES['gallery_files'])) {
            $upload_dir = ensure_uploads_dir();
            foreach ($_FILES['gallery_files']['tmp_name'] as $gkey => $gtmp) {
                if ($_FILES['gallery_files']['error'][$gkey] === UPLOAD_ERR_OK) {
                    $gname = clean_upload_name($_FILES['gallery_files']['name'][$gkey]);
                    $target = $upload_dir . '/' . time() . '_' . $gname;
                    if (move_uploaded_file($gtmp, $target)) {
                        $gallery[] = $target;
                    }
                }
            }
        }

        // Keep reviews intact
        $existing_reviews = [];
        foreach ($packages as $pkg) {
            if ($pkg['id'] === $id) {
                $existing_reviews = isset($pkg['reviews']) ? $pkg['reviews'] : [];
                break;
            }
        }

        $package_obj = [
            'id' => $id,
            'slug' => $slug,
            'category' => $_POST['category'],
            'title' => $title,
            'region' => trim($_POST['region']),
            'duration' => (int)$_POST['duration'],
            'priceUSD' => (int)$_POST['priceUSD'],
            'priceNPR' => (int)$_POST['priceNPR'],
            'priceNote' => trim($_POST['priceNote']),
            'short_overview' => trim($_POST['short_overview'] ?? ''),
            'secondary_keywords' => trim($_POST['secondary_keywords'] ?? ''),
            'card_text' => trim($_POST['card_text'] ?? ''),
            'cta_text' => trim($_POST['cta_text'] ?? ''),
            'route_map_description' => trim($_POST['route_map_description'] ?? ''),
            'offer_badge' => trim($_POST['offer_badge'] ?? ''),
            'offer_old_price_usd' => isset($_POST['offer_old_price_usd']) ? (int)$_POST['offer_old_price_usd'] : 0,
            'offer_end_date' => trim($_POST['offer_end_date'] ?? ''),
            'group_offer_text' => trim($_POST['group_offer_text'] ?? ''),
            'difficulty' => $_POST['difficulty'],
            'maxAltitude' => trim($_POST['maxAltitude']),
            'bestSeason' => trim($_POST['bestSeason']),
            'startPoint' => trim($_POST['startPoint']),
            'endPoint' => trim($_POST['endPoint']),
            'accommodation' => trim($_POST['accommodation']),
            'meals' => trim($_POST['meals']),
            'groupSize' => trim($_POST['groupSize']),
            'overview' => $_POST['overview'],
            'tripFacts' => $tripFacts,
            'detailSections' => $detailSections,
            'packingList' => array_values($packingList),
            'image_alt_texts' => array_values($imageAltTexts),
            'tags' => array_values($tags),
            'highlights' => $highlights,
            'costIncludes' => $includes,
            'costExcludes' => $excludes,
            'requiredPermits' => $permits,
            'image' => $image_path,
            'route_map' => $route_map_path,
            'gallery' => $gallery,
            'sourceLinks' => $sourceLinks,
            'lastCheckedDate' => date('Y-m-d'),
            'faqs' => $faqs,
            'itinerary' => $itinerary,
            'seo_title' => trim($_POST['seo_title']),
            'seo_description' => trim($_POST['seo_description']),
            'seo_keywords' => trim($_POST['seo_keywords']),
            'image_alt' => trim($_POST['image_alt'] ?? ''),
            'og_title' => trim($_POST['og_title']),
            'og_description' => trim($_POST['og_description']),
            'og_image' => trim($_POST['og_image']),
            'activity_id' => $_POST['activity_id'],
            'reviews' => $existing_reviews
        ];

        $found = false;
        foreach ($packages as &$pkg) {
            if ($pkg['id'] === $id) {
                $pkg = $package_obj;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $packages[] = $package_obj;
        }

        file_put_contents($packages_path, json_encode($packages, JSON_PRETTY_PRINT));
        $action_status = "success";
        $action_msg = "Trekking package saved successfully.";
    }

    // ACTION M: Delete Media File (Req 4)
    if (isset($_POST['delete_media_file'])) {
        if (!admin_delete_confirmed($admin_creds)) {
            block_unconfirmed_delete($action_status, $action_msg);
        } else {
            $del_path = $_POST['media_path'];
            // Prevent directory traversal
            if (strpos($del_path, 'assets/images/uploads/') === 0 && file_exists($del_path)) {
                @unlink($del_path);
                $action_status = "success";
                $action_msg = "Media file deleted from server.";
            }
        }
    }

    // ACTION N: Upload Media File (Req 4)
    if (isset($_POST['upload_media_file'])) {
        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['media_file']['tmp_name'];
            $name = clean_upload_name($_FILES['media_file']['name']);
            $upload_dir = ensure_uploads_dir();
            $target = $upload_dir . '/' . time() . '_' . $name;
            if (move_uploaded_file($tmp_name, $target)) {
                $action_status = "success";
                $action_msg = "Media file uploaded successfully.";
            }
        }
    }
    
    // ACTION O: Save Admin Profile & Password
    if (isset($_POST['save_profile'])) {
        $admin_name = trim($_POST['admin_name']);
        $admin_user = trim($_POST['admin_username']);
        $admin_pass = trim($_POST['admin_password']);
        $admin_email = isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '';
        $admin_phone = isset($_POST['admin_phone']) ? trim($_POST['admin_phone']) : '';
        
        if (empty($admin_user) || empty($admin_pass)) {
            $action_status = "error";
            $action_msg = "Username and Password cannot be empty.";
        } else {
            $settings['admin'] = [
                'name' => $admin_name,
                'username' => $admin_user,
                'password' => $admin_pass,
                'email' => $admin_email,
                'phone' => $admin_phone
            ];
            file_put_contents($settings_path, json_encode($settings, JSON_PRETTY_PRINT));
            $admin_creds = $settings['admin'];
            set_admin_auth_cookie($settings_path, $admin_creds);
            $action_status = "success";
            $action_msg = "Admin profile name and security details updated successfully.";
        }
    }
}

// Recalculate review counts
$pending_reviews_count = 0;
foreach ($packages as $pkg) {
    if (isset($pkg['reviews']) && is_array($pkg['reviews'])) {
        foreach ($pkg['reviews'] as $rev) {
            if (isset($rev['approved']) && $rev['approved'] === false) {
                $pending_reviews_count++;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Top Mountain Treks</title>
  
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/theme-custom.css">
  
  <!-- Classic CKEditor 5 CDN -->
  <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>

  <style>
    /* Override global header fixed layout for static admin layout flow */
    header.admin-nav {
      position: static !important;
      height: auto !important;
      background-color: var(--color-primary) !important;
      border-bottom: none !important;
      margin-bottom: 2rem !important;
      box-shadow: none !important;
      transform: none !important;
    }
    .admin-nav {
      background-color: var(--color-primary);
      color: white;
      padding: 1rem 0;
      margin-bottom: 2rem;
    }
    .admin-tabs {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-bottom: 2rem;
      border-bottom: 2px solid var(--border-color);
      padding-bottom: 0.5rem;
    }
    .admin-tab {
      padding: 0.75rem 1.25rem;
      background-color: var(--bg-light);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-sm);
      cursor: pointer;
      font-weight: 700;
      font-size: 0.9rem;
      color: var(--text-medium);
      transition: all 0.3s;
    }
    .admin-tab.active, .admin-tab:hover {
      background-color: var(--color-primary);
      color: white;
      border-color: var(--color-primary);
    }
    .admin-panel {
      display: none;
    }
    .admin-panel.active {
      display: block;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }
    th, td {
      border: 1px solid var(--border-color);
      padding: 0.75rem 1rem;
      text-align: left;
    }
    th {
      background-color: var(--bg-light);
      font-weight: 800;
    }
    .edit-fields-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }
    /* CKEditor Custom Styling */
    .ck-editor__editable {
      min-height: 250px;
    }
    /* Specific height adjustments for small rich textareas in admin */
    [id^="day-accomm-"] + .ck-editor .ck-editor__editable,
    [id^="day-meals-"] + .ck-editor .ck-editor__editable,
    [id^="faq-ans-"] + .ck-editor .ck-editor__editable {
      min-height: 80px !important;
    }
    .cookie-consent-banner {
      position: fixed;
      left: 1rem;
      right: 1rem;
      bottom: 1rem;
      z-index: 9999;
      display: none;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      max-width: 760px;
      margin: 0 auto;
      padding: 1rem;
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-lg);
    }
    .cookie-consent-banner.visible {
      display: flex;
    }
    .cookie-consent-banner p {
      margin: 0.2rem 0 0;
      color: var(--text-muted);
      font-size: 0.9rem;
    }
    @media (max-width: 640px) {
      .cookie-consent-banner {
        align-items: stretch;
        flex-direction: column;
      }
    }
  </style>
</head>
<body style="background-color:#f1f5f9; color:var(--text-dark);">

  <?php if (!$is_logged): ?>
    <!-- Login form -->
    <div style="display:flex; justify-content:center; align-items:center; min-height:100vh; padding:1.5rem;">
      <div class="glass-card" style="padding:3rem; max-width:440px; width:100%; background:white; border:1px solid var(--border-color); box-shadow:var(--shadow-lg); border-radius:var(--radius-lg);">
        
        <?php if ($recovery_state === 'login'): ?>
          <h2 style="font-family:var(--font-heading); font-size:1.8rem; font-weight:800; text-align:center; color:var(--color-primary); margin-bottom:0.5rem;">Top Mountain Treks</h2>
          <p style="text-align:center; color:var(--text-muted); font-size:0.9rem; margin-bottom:2rem;">Please authenticate to access the admin customizer.</p>
          
          <?php if (!empty($login_error)): ?>
            <div style="padding:0.75rem 1rem; background-color:#fef2f2; border:1px solid var(--color-danger); color:#991b1b; border-radius:var(--radius-sm); font-size:0.88rem; margin-bottom:1.5rem; font-weight:600;">
              <?php echo htmlspecialchars($login_error); ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="/admin">
            <div class="form-group">
              <label for="username">Username</label>
              <input type="text" id="username" name="username" class="form-input" required placeholder="Username">
            </div>
            <div class="form-group" style="margin-bottom:1.25rem;">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" class="form-input" required placeholder="Password">
            </div>
            <div style="display:flex; justify-content:flex-end; margin-bottom:1.5rem;">
              <a href="/admin?action=forgot_password" style="font-size:0.85rem; color:var(--color-primary); text-decoration:none; font-weight:600;">Forgot Password?</a>
            </div>
            <button type="submit" name="login_submit" class="btn btn-primary" style="width:100%; height:46px; font-weight:700;">Sign In</button>
          </form>

        <?php elseif ($recovery_state === 'send_otp'): ?>
          <h2 style="font-family:var(--font-heading); font-size:1.8rem; font-weight:800; text-align:center; color:var(--color-primary); margin-bottom:0.5rem;">Recovery</h2>
          <p style="text-align:center; color:var(--text-muted); font-size:0.9rem; margin-bottom:2rem;">Generate a 6-digit OTP code to verify identity.</p>
          
          <?php if (!empty($recovery_error)): ?>
            <div style="padding:0.75rem 1rem; background-color:#fef2f2; border:1px solid var(--color-danger); color:#991b1b; border-radius:var(--radius-sm); font-size:0.88rem; margin-bottom:1.5rem; font-weight:600;">
              <?php echo htmlspecialchars($recovery_error); ?>
            </div>
          <?php endif; ?>

          <div style="background-color:#f8fafc; border:1px solid var(--border-color); padding:1rem; border-radius:var(--radius-md); margin-bottom:2rem; font-size:0.9rem;">
            <div><strong>Recovery Email:</strong> <?php echo htmlspecialchars($admin_creds['email'] ?? 'Not Configured'); ?></div>
            <div style="margin-top:0.5rem;"><strong>Recovery Phone:</strong> <?php echo htmlspecialchars($admin_creds['phone'] ?? 'Not Configured'); ?></div>
          </div>

          <form method="POST" action="/admin">
            <input type="hidden" name="action_send_otp" value="1">
            <button type="submit" class="btn btn-primary" style="width:100%; height:46px; font-weight:700; margin-bottom:1rem;">Generate & Send OTP</button>
            <a href="/admin" class="btn btn-outline" style="display:flex; justify-content:center; align-items:center; width:100%; height:46px; font-weight:700; text-decoration:none; box-sizing:border-box;">Cancel</a>
          </form>

        <?php elseif ($recovery_state === 'verify_otp'): ?>
          <h2 style="font-family:var(--font-heading); font-size:1.8rem; font-weight:800; text-align:center; color:var(--color-primary); margin-bottom:0.5rem;">Verify OTP</h2>
          <p style="text-align:center; color:var(--text-muted); font-size:0.9rem; margin-bottom:2rem;">Enter the recovery code generated for this account.</p>
          
          <?php if (!empty($recovery_msg)): ?>
            <div style="padding:0.75rem 1rem; background-color:#ecfdf5; border:1px solid var(--color-success); color:#065f46; border-radius:var(--radius-sm); font-size:0.88rem; margin-bottom:1.5rem; font-weight:600;">
              <?php echo htmlspecialchars($recovery_msg); ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($recovery_error)): ?>
            <div style="padding:0.75rem 1rem; background-color:#fef2f2; border:1px solid var(--color-danger); color:#991b1b; border-radius:var(--radius-sm); font-size:0.88rem; margin-bottom:1.5rem; font-weight:600;">
              <?php echo htmlspecialchars($recovery_error); ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="/admin">
            <div class="form-group" style="margin-bottom:2rem;">
              <label for="otp">Enter 6-Digit OTP</label>
              <input type="text" id="otp" name="otp" class="form-input" required placeholder="123456" pattern="\d{6}" maxlength="6" style="text-align:center; letter-spacing:0.5rem; font-size:1.25rem; font-weight:700;">
            </div>
            <input type="hidden" name="action_verify_otp" value="1">
            <button type="submit" class="btn btn-primary" style="width:100%; height:46px; font-weight:700; margin-bottom:1rem;">Verify OTP</button>
            <a href="/admin" class="btn btn-outline" style="display:flex; justify-content:center; align-items:center; width:100%; height:46px; font-weight:700; text-decoration:none; box-sizing:border-box;">Cancel</a>
          </form>

        <?php elseif ($recovery_state === 'reset_password'): ?>
          <h2 style="font-family:var(--font-heading); font-size:1.8rem; font-weight:800; text-align:center; color:var(--color-primary); margin-bottom:0.5rem;">Reset Pass</h2>
          <p style="text-align:center; color:var(--text-muted); font-size:0.9rem; margin-bottom:2rem;">Set your new administrator password.</p>
          
          <?php if (!empty($recovery_error)): ?>
            <div style="padding:0.75rem 1rem; background-color:#fef2f2; border:1px solid var(--color-danger); color:#991b1b; border-radius:var(--radius-sm); font-size:0.88rem; margin-bottom:1.5rem; font-weight:600;">
              <?php echo htmlspecialchars($recovery_error); ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="/admin">
            <div class="form-group">
              <label for="new_password">New Password</label>
              <input type="password" id="new_password" name="new_password" class="form-input" required placeholder="New Password">
            </div>
            <div class="form-group" style="margin-bottom:2rem;">
              <label for="confirm_password">Confirm Password</label>
              <input type="password" id="confirm_password" name="confirm_password" class="form-input" required placeholder="Confirm New Password">
            </div>
            <input type="hidden" name="action_reset_password" value="1">
            <button type="submit" class="btn btn-primary" style="width:100%; height:46px; font-weight:700;">Reset Password</button>
          </form>
        <?php endif; ?>

      </div>
    </div>
  <?php else: ?>

    <!-- Header bar -->
    <header class="admin-nav">
      <div class="container" style="display:flex; justify-content:space-between; align-items:center; padding:0 1rem;">
        <h2 style="font-family:var(--font-heading); font-size:1.4rem; font-weight:800; margin:0;">Top Mountain Treks | Admin Panel <span style="font-size:0.9rem; font-weight:600; color:white; opacity:0.8; margin-left:0.5rem;">(Hello, <?php echo htmlspecialchars($admin_creds['name']); ?>)</span></h2>
        <a href="/admin?action=logout" class="btn btn-outline" style="color:white; border-color:white; height:36px; padding:0.5rem 1rem;">Sign Out</a>
      </div>
    </header>

    <div class="container" style="padding-bottom:5rem;">
      
      <!-- Notifications -->
      <?php if (!empty($action_msg)): ?>
        <div style="padding: 1rem 1.25rem; border-radius: var(--radius-md); margin-bottom: 2rem; font-weight:600; border:1px solid <?php echo $action_status === 'success' ? 'var(--color-success)' : 'var(--color-danger)'; ?>; background: <?php echo $action_status === 'success' ? '#f0fdf4' : '#fef2f2'; ?>; color: <?php echo $action_status === 'success' ? '#166534' : '#991b1b'; ?>;">
          <?php echo htmlspecialchars($action_msg); ?>
        </div>
      <?php endif; ?>

      <!-- Tabs Nav -->
      <div class="admin-tabs">
        <span class="admin-tab active" data-target="panel-list">All Packages</span>
        <span class="admin-tab" data-target="panel-edit" onclick="resetPackageForm()">Add Package</span>
        <span class="admin-tab" data-target="panel-activities">Activities & Subtitles</span>
        <span class="admin-tab" data-target="panel-blogs">Blogs & Pages</span>
        <span class="admin-tab" data-target="panel-reviews">Reviews (<?php echo $pending_reviews_count; ?> pending)</span>
        <span class="admin-tab" data-target="panel-media">Media Gallery</span>
        <span class="admin-tab" data-target="panel-settings">Settings & Logo</span>
        <span class="admin-tab" data-target="panel-theme">Theme Text & Color Setup</span>
        <span class="admin-tab" data-target="panel-inquiries">Inquiries (<?php echo count($inquiries); ?>)</span>
        <span class="admin-tab" data-target="panel-profile">Profile & Security</span>
      </div>

      <!-- Tab 1: All Packages -->
      <div class="admin-panel active" id="panel-list">
        <div class="glass-card" style="padding:2rem; background-color: white; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
          <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1.5rem;">Existing Packages</h3>
          <div style="overflow-x: auto; width: 100%;">
            <table>
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Category</th>
                  <th>Region Subtitle</th>
                  <th>Duration</th>
                  <th>Price (USD)</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($packages as $pkg): ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($pkg['title']); ?></strong></td>
                    <td style="text-transform:capitalize;"><?php echo htmlspecialchars($pkg['category']); ?></td>
                    <td>
                      <?php
                      $act_name = "None";
                      foreach ($activities as $act) {
                          if (isset($pkg['activity_id']) && $act['id'] === $pkg['activity_id']) {
                              $act_name = $act['name'];
                              break;
                          }
                      }
                      echo htmlspecialchars($act_name);
                      ?>
                    </td>
                    <td><?php echo $pkg['duration']; ?> Days</td>
                    <td>$<?php echo number_format($pkg['priceUSD']); ?></td>
                    <td style="display:flex; gap:0.5rem;">
                      <button class="btn btn-primary btn-sm" onclick="editPackage(<?php echo htmlspecialchars(json_encode($pkg)); ?>)">Edit</button>
                      <form method="POST" action="/admin" onsubmit="return confirmAdminDelete(this, 'this package')">
                        <input type="hidden" name="package_id" value="<?php echo $pkg['id']; ?>">
                        <button type="submit" name="delete_package" class="btn btn-secondary btn-sm" style="background-color:var(--color-danger);">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Tab 2: Add/Edit Package -->
      <div class="admin-panel" id="panel-edit">
        <div class="glass-card" style="padding:2.5rem; background-color: white; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
          <h3 style="font-family:var(--font-heading); font-size:1.5rem; color:var(--color-primary); margin-bottom:2rem;" id="package-form-title">Create New Package</h3>
          
          <form method="POST" action="/admin" enctype="multipart/form-data">
            <input type="hidden" id="edit-id" name="id" value="">
            <input type="hidden" id="edit-existing_image" name="existing_image" value="">
            <input type="hidden" id="edit-existing_route_map" name="existing_route_map" value="">
            
            <div class="edit-fields-grid">
              <div class="form-group">
                <label for="edit-title">Expedition Title</label>
                <input type="text" id="edit-title" name="title" class="form-input" required placeholder="e.g. Everest Base Camp Trek" oninput="generateSlug(this.value)">
              </div>
              <div class="form-group">
                <label for="edit-slug">URL Link Slug</label>
                <input type="text" id="edit-slug" name="slug" class="form-input" placeholder="e.g. everest-base-camp-trek">
              </div>
            </div>

            <div class="edit-fields-grid">
              <div class="form-group">
                <label for="edit-category">Trip Category</label>
                <select id="edit-category" name="category" class="form-input" required>
                  <option value="trekking">Trekking</option>
                  <option value="climbing">Peak Climbing</option>
                  <option value="tour">Tour / Excursion</option>
                </select>
              </div>
              <div class="form-group">
                <label for="edit-activity_id">Region Subtitle Association</label>
                <select id="edit-activity_id" name="activity_id" class="form-input">
                  <option value="">No Region/Subtitle Category</option>
                  <?php foreach ($activities as $act): ?>
                    <option value="<?php echo $act['id']; ?>"><?php echo htmlspecialchars($act['name']); ?> (<?php echo htmlspecialchars($act['category']); ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="edit-fields-grid">
              <div class="form-group">
                <label for="edit-region">Region</label>
                <input type="text" id="edit-region" name="region" class="form-input" required placeholder="e.g. Annapurna, Everest">
              </div>
              <div class="form-group">
                <label for="edit-duration">Duration (Days)</label>
                <input type="number" id="edit-duration" name="duration" class="form-input" required value="10" min="1">
              </div>
            </div>

            <div class="edit-fields-grid">
              <div class="form-group">
                <label for="edit-priceUSD">Price (USD)</label>
                <input type="number" id="edit-priceUSD" name="priceUSD" class="form-input" required value="950" min="1" data-rate="<?php echo htmlspecialchars($usd_to_npr_rate); ?>">
              </div>
              <div class="form-group">
                <label for="edit-priceNPR">Price (NPR auto)</label>
                <input type="number" id="edit-priceNPR" name="priceNPR" class="form-input" required value="126350" min="1">
              </div>
            </div>

            <div class="edit-fields-grid">
              <div class="form-group">
                <label for="edit-offer_badge">Homepage Offer Badge</label>
                <input type="text" id="edit-offer_badge" name="offer_badge" class="form-input" placeholder="e.g. Hot Deal, Group Offer" value="<?php echo htmlspecialchars($home_offer_settings['default_badge']); ?>">
              </div>
              <div class="form-group">
                <label for="edit-group_offer_text">Booking Card Group Offer Text</label>
                <input type="text" id="edit-group_offer_text" name="group_offer_text" class="form-input" placeholder="e.g. We offer group price" value="<?php echo htmlspecialchars($home_offer_settings['default_group_text']); ?>">
              </div>
            </div>

            <div class="edit-fields-grid">
              <div class="form-group">
                <label for="edit-offer_old_price_usd">Old Price USD (optional strike-through)</label>
                <input type="number" id="edit-offer_old_price_usd" name="offer_old_price_usd" class="form-input" min="0" value="0">
              </div>
              <div class="form-group">
                <label for="edit-offer_end_date">Offer End Date / Countdown</label>
                <input type="datetime-local" id="edit-offer_end_date" name="offer_end_date" class="form-input">
              </div>
            </div>

            <div class="form-group">
              <label for="edit-priceNote">Price Notes</label>
              <input type="text" id="edit-priceNote" name="priceNote" class="form-input" value="Estimated starting price per person">
            </div>

            <div class="edit-fields-grid">
              <div class="form-group">
                <label for="edit-difficulty">Difficulty Level</label>
                <select id="edit-difficulty" name="difficulty" class="form-input">
                  <option value="Easy">Easy</option>
                  <option value="Moderate">Moderate</option>
                  <option value="Challenging">Challenging</option>
                  <option value="Strenuous">Strenuous</option>
                </select>
              </div>
              <div class="form-group">
                <label for="edit-maxAltitude">Max Altitude Details</label>
                <input type="text" id="edit-maxAltitude" name="maxAltitude" class="form-input" required placeholder="e.g. 4,130 m">
              </div>
            </div>

            <div class="edit-fields-grid">
              <div class="form-group">
                <label for="edit-bestSeason">Best Seasons</label>
                <input type="text" id="edit-bestSeason" name="bestSeason" class="form-input" required placeholder="e.g. March-May & Sept-Nov">
              </div>
              <div class="form-group">
                <label for="edit-groupSize">Group Sizes</label>
                <input type="text" id="edit-groupSize" name="groupSize" class="form-input" required placeholder="e.g. 2 to 12 people">
              </div>
            </div>

            <div class="edit-fields-grid">
              <div class="form-group">
                <label for="edit-startPoint">Start Point Location</label>
                <input type="text" id="edit-startPoint" name="startPoint" class="form-input" required placeholder="e.g. Kathmandu">
              </div>
              <div class="form-group">
                <label for="edit-endPoint">End Point Location</label>
                <input type="text" id="edit-endPoint" name="endPoint" class="form-input" required placeholder="e.g. Kathmandu">
              </div>
            </div>

            <div class="form-group">
              <label for="edit-short_overview">Short Overview / Homepage Summary</label>
              <textarea id="edit-short_overview" name="short_overview" class="form-input" rows="3" placeholder="Short readable summary for cards and top of package page..."></textarea>
            </div>

            <div class="form-group">
              <label for="edit-overview">Expedition Overview (Rich Text)</label>
              <textarea id="edit-overview" name="overview" class="form-input" rows="8" placeholder="Expedition details..."></textarea>
            </div>

            <div class="edit-fields-grid">
              <div class="form-group">
                <label for="edit-card_text">Custom Package Card Text</label>
                <textarea id="edit-card_text" name="card_text" class="form-input" rows="3" placeholder="Short homepage card copy. Leave blank to use short overview."></textarea>
              </div>
              <div class="form-group">
                <label for="edit-cta_text">Package Call To Action Text</label>
                <textarea id="edit-cta_text" name="cta_text" class="form-input" rows="3" placeholder="Book this trek today..."></textarea>
              </div>
            </div>

            <div class="form-group">
              <label for="edit-route_map_description">Route Map Description</label>
              <textarea id="edit-route_map_description" name="route_map_description" class="form-input" rows="2" placeholder="Kathmandu -> Lukla -> Phakding..."></textarea>
            </div>

            <div style="border-top:1px solid var(--border-color); padding-top:1.5rem; margin-top:1.5rem;">
              <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;">
                <h4 style="font-family:var(--font-heading); color:var(--color-primary); font-weight:800; margin:0;">Trip Facts Table</h4>
                <button type="button" class="btn btn-outline btn-sm" onclick="addTripFactRow()">+ Add Trip Fact</button>
              </div>
              <div id="trip-facts-container" style="display:flex; flex-direction:column; gap:0.75rem;"></div>
            </div>

            <div style="border-top:1px solid var(--border-color); padding-top:1.5rem; margin-top:1.5rem;">
              <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;">
                <h4 style="font-family:var(--font-heading); color:var(--color-primary); font-weight:800; margin:0;">Extra Package Detail Sections</h4>
                <button type="button" class="btn btn-outline btn-sm" onclick="addDetailSectionRow()">+ Add Detail Section</button>
              </div>
              <div id="detail-sections-container" style="display:flex; flex-direction:column; gap:1rem;"></div>
            </div>

            <div class="edit-fields-grid">
              <div class="form-group">
                <label for="edit-accommodation">Global Accommodation Summary (Standard fallback)</label>
                <input type="text" id="edit-accommodation" name="accommodation" class="form-input" required value="Hotel in Kathmandu and teahouses on trek">
              </div>
              <div class="form-group">
                <label for="edit-meals">Global Meals Summary (Standard fallback)</label>
                <input type="text" id="edit-meals" name="meals" class="form-input" required value="Full board during trek; breakfast in Kathmandu">
              </div>
            </div>

            <!-- CKEditor text fields for lists (Req 2) -->
            <div class="edit-fields-grid" style="margin-top:2rem;">
              <div class="form-group">
                <label for="edit-highlights">Trip Highlights (Bullet points/lists)</label>
                <textarea id="edit-highlights" name="highlights" class="form-input" rows="5"></textarea>
              </div>
              <div class="form-group">
                <label for="edit-requiredPermits">Required Permits (Bullet points/lists)</label>
                <textarea id="edit-requiredPermits" name="requiredPermits" class="form-input" rows="5"></textarea>
              </div>
            </div>

            <div class="edit-fields-grid">
              <div class="form-group">
                <label for="edit-costIncludes">Cost Includes (Price Inclusions)</label>
                <textarea id="edit-costIncludes" name="costIncludes" class="form-input" rows="5"></textarea>
              </div>
              <div class="form-group">
                <label for="edit-costExcludes">Cost Excludes (Price Exclusions)</label>
                <textarea id="edit-costExcludes" name="costExcludes" class="form-input" rows="5"></textarea>
              </div>
            </div>

            <!-- Images & Gallery Uploads (Req 5, 7) -->
            <div style="border-top:1px solid var(--border-color); padding-top:2rem; margin-top:2rem;">
              <h4 style="font-family:var(--font-heading); color:var(--color-primary); margin-bottom:1.25rem; font-weight:800;">Package Images & Route Map</h4>
              
              <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1.5rem;">
                <div class="form-group">
                  <label for="edit-image_file">Main Featured Image (Thumbnail)</label>
                  <input type="file" id="edit-image_file" name="image_file" class="form-input">
                  <label for="edit-image_alt" style="margin-top:0.75rem;">Image SEO Alt Text</label>
                  <input type="text" id="edit-image_alt" name="image_alt" class="form-input" placeholder="Describe the image for Google and accessibility">
                  <div id="main-image-preview" style="margin-top:0.5rem;"></div>
                </div>
                <div class="form-group">
                  <label for="edit-route_map_file">Static Route Map Image File</label>
                  <input type="file" id="edit-route_map_file" name="route_map_file" class="form-input">
                  <div id="route-map-preview" style="margin-top:0.5rem;"></div>
                </div>
                <div class="form-group">
                  <label for="edit-gallery_files">Upload Photo Gallery Images (Multi)</label>
                  <input type="file" id="edit-gallery_files" name="gallery_files[]" class="form-input" multiple>
                  <div id="gallery-preview-container" style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.5rem;"></div>
                </div>
              </div>
            </div>

            <!-- Itinerary Paste Outline Parser (Req 7) -->
            <div style="border-top: 1px solid var(--border-color); padding-top: 2rem; margin-top: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: var(--radius-md);">
              <h4 style="font-family:var(--font-heading); color:var(--color-primary); margin-bottom:0.5rem; font-weight:800;">Itinerary Paste Outline Shortcut Parser</h4>
              <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:1rem;">Paste day outline text (e.g. "Day 1: Arrival\nDay 2: Trek") to automatically generate and populate day-by-day itinerary rows below.</p>
              <textarea id="itinerary-outline-paste" class="form-input" rows="4" placeholder="Day 1: Arrival in Kathmandu&#10;Day 2: Fly to Lukla and trek to Phakding&#10;Day 3: Trek to Namche Bazaar"></textarea>
              <button type="button" class="btn btn-primary btn-sm" style="margin-top:0.75rem;" onclick="parseItineraryOutline()">Parse & Generate Days</button>
            </div>

            <!-- Dynamic Itinerary Day Builder (Req 3, 6, 11) -->
            <div style="border-top:1px solid var(--border-color); padding-top:2rem; margin-top:2rem;">
              <div style="background:#fff7ed; border:1px solid #fed7aa; padding:1rem; border-radius:var(--radius-md); margin-bottom:1.25rem;">
                <h4 style="font-family:var(--font-heading); color:var(--color-primary); margin:0 0 0.5rem; font-weight:800;">Full Day Details Paste Parser</h4>
                <p style="font-size:0.82rem; color:var(--text-muted); margin:0 0 0.75rem;">Paste each day as lines: Day, Day Title, Campsite / Location, Accommodation, Meals, Description. Separate days with a blank line or start the next block with "Day 2".</p>
                <textarea id="itinerary-detail-paste" class="form-input" rows="6" placeholder="Day 1&#10;Arrival in Kathmandu&#10;Kathmandu&#10;Hotel&#10;Dinner&#10;Meet your guide and prepare permits.&#10;&#10;Day 2&#10;Fly to Lukla and trek to Phakding&#10;Phakding&#10;Teahouse Lodge&#10;Breakfast, Lunch, Dinner&#10;Trail details here..."></textarea>
                <button type="button" class="btn btn-primary btn-sm" style="margin-top:0.75rem;" onclick="parseDetailedItinerary()">Parse & Fill Day Details</button>
              </div>
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h4 style="font-family:var(--font-heading); color:var(--color-primary); font-weight:800; margin:0;">Day-by-Day Itinerary Details</h4>
                <button type="button" class="btn btn-outline btn-sm" onclick="addItineraryDayRow()">+ Add Day Row</button>
              </div>
              <div id="itinerary-rows-container"></div>
            </div>

            <!-- Dynamic FAQs Builder -->
            <div style="border-top:1px solid var(--border-color); padding-top:2rem; margin-top:2rem;">
              <div style="background:#eff6ff; border:1px solid #bfdbfe; padding:1rem; border-radius:var(--radius-md); margin-bottom:1.25rem;">
                <h4 style="font-family:var(--font-heading); color:var(--color-primary); margin:0 0 0.5rem; font-weight:800;">Bulk FAQ Paste Parser</h4>
                <p style="font-size:0.82rem; color:var(--text-muted); margin:0 0 0.75rem;">Paste Question on line 1, Answer on line 2, next Question on line 3, next Answer on line 4. The rows are created automatically.</p>
                <textarea id="faq-bulk-paste" class="form-input" rows="5" placeholder="What is the best season?&#10;Spring and autumn are usually best.&#10;Do I need travel insurance?&#10;Yes, trekking insurance with evacuation cover is recommended."></textarea>
                <button type="button" class="btn btn-primary btn-sm" style="margin-top:0.75rem;" onclick="parseBulkFaqs()">Parse & Fill FAQs</button>
              </div>
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h4 style="font-family:var(--font-heading); color:var(--color-primary); font-weight:800; margin:0;">Trip FAQs Details</h4>
                <button type="button" class="btn btn-outline btn-sm" onclick="addFaqRow()">+ Add FAQ Row</button>
              </div>
              <div id="faq-rows-container"></div>
            </div>

            <!-- Live Yoast SEO Panel -->
            <div style="border-top:1px solid var(--border-color); padding-top:2rem; margin-top:2rem; background:#fafafa; padding:2rem; border-radius:var(--radius-lg);">
              <h4 style="font-family:var(--font-heading); color:var(--color-primary); margin-bottom:1rem; font-weight:800;">Yoast-Style Live SEO Analysis Engine</h4>
              
              <div class="edit-fields-grid">
                <div class="form-group">
                  <label for="edit-seo_title">SEO Page Title</label>
                  <input type="text" id="edit-seo_title" name="seo_title" class="form-input" placeholder="Title tag..." oninput="triggerSEO()">
                </div>
                <div class="form-group">
                  <label for="edit-seo_keywords">Focus Keyphrase</label>
                  <input type="text" id="edit-seo_keywords" name="seo_keywords" class="form-input" placeholder="Focus search term..." oninput="triggerSEO()">
                </div>
              </div>

              <div class="form-group">
                <label for="edit-secondary_keywords">Secondary Keywords</label>
                <textarea id="edit-secondary_keywords" name="secondary_keywords" class="form-input" rows="2" placeholder="EBC Trek, Everest Base Camp itinerary, Everest Base Camp Trek cost..."></textarea>
              </div>

              <div class="form-group">
                <label for="edit-seo_description">SEO Meta Description</label>
                <textarea id="edit-seo_description" name="seo_description" class="form-input" rows="2" placeholder="Search preview summary..." oninput="triggerSEO()"></textarea>
              </div>

              <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:2.5rem; margin-top:1.5rem;">
                <div>
                  <h5 style="font-weight:700; margin-bottom:0.75rem;">Google SERP Snippet Preview</h5>
                  <div class="glass-card" style="padding:1.25rem; background:white; border:1px solid var(--border-color);">
                    <div id="serp-url" style="font-size:0.75rem; color:#202124; margin-bottom:2px;">http://127.0.0.1:8080/trekking/slug</div>
                    <a id="serp-title" href="#" style="font-size:1.15rem; color:#1a0dab; text-decoration:none; font-weight:600; display:block; margin-bottom:4px;">Please enter a title...</a>
                    <div id="serp-desc" style="font-size:0.85rem; color:#4d5156; line-height:1.4;">Please enter a meta description...</div>
                  </div>
                </div>
                <div>
                  <h5 style="font-weight:700; margin-bottom:0.5rem; display:flex; justify-content:space-between; align-items:center;">
                    <span>Diagnostics Checklist</span>
                    <span id="seo-score-badge" style="font-size:0.75rem; padding:0.25rem 0.5rem; border-radius:var(--radius-sm); color:white; background:#6b7280;">Gray</span>
                  </h5>
                  <div id="seo-checklist" style="font-size:0.82rem; display:flex; flex-direction:column; gap:4px; max-height:220px; overflow-y:auto; padding:0.5rem; border:1px solid var(--border-color); background:white; border-radius:var(--radius-sm);"></div>
                </div>
              </div>
            </div>

            <!-- Social Media OG Tags -->
            <div style="border-top:1px solid var(--border-color); padding-top:2rem; margin-top:2rem;">
              <h4 style="font-family:var(--font-heading); color:var(--color-primary); margin-bottom:1.25rem; font-weight:800;">Social Media Open Graph (OG) Tags</h4>
              <div class="edit-fields-grid">
                <div class="form-group">
                  <label for="edit-og_title">Facebook/Twitter Title</label>
                  <input type="text" id="edit-og_title" name="og_title" class="form-input">
                </div>
                <div class="form-group">
                  <label for="edit-og_image">Social Share Image Path</label>
                  <input type="text" id="edit-og_image" name="og_image" class="form-input">
                </div>
              </div>
              <div class="form-group">
                <label for="edit-og_description">Social Description summary</label>
                <textarea id="edit-og_description" name="og_description" class="form-input" rows="2"></textarea>
              </div>
            </div>

            <div style="border-top:1px solid var(--border-color); padding-top:2rem; margin-top:2rem;">
              <h4 style="font-family:var(--font-heading); color:var(--color-primary); margin-bottom:1rem; font-weight:800;">Tags, Source Links, and Image SEO</h4>
              <div class="edit-fields-grid">
                <div class="form-group">
                  <label for="edit-package_tags">Website Categories / Tags (one per line)</label>
                  <textarea id="edit-package_tags" name="package_tags" class="form-input" rows="5" placeholder="Everest Region Trekking&#10;Nepal Trekking Packages"></textarea>
                </div>
                <div class="form-group">
                  <label for="edit-image_alt_texts">Image SEO Alt Text Ideas (one per line)</label>
                  <textarea id="edit-image_alt_texts" name="image_alt_texts" class="form-input" rows="5" placeholder="Everest Base Camp Trek route in Nepal"></textarea>
                </div>
              </div>
              <div class="form-group">
                <label for="edit-sourceLinks">Source / Verification Links (one per line)</label>
                <textarea id="edit-sourceLinks" name="sourceLinks" class="form-input" rows="3" placeholder="https://ntb.gov.np/en/everest-base-camp"></textarea>
              </div>
            </div>

            <div style="margin-top:2.5rem; display:flex; gap:1rem; justify-content:flex-end;">
              <button type="button" class="btn btn-outline" onclick="resetPackageForm()">Reset Form</button>
              <button type="submit" name="save_package" class="btn btn-primary" style="height:46px; font-weight:700; min-width:180px;">Save Trek Package</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Tab 3: Activities & Subtitles -->
      <div class="admin-panel" id="panel-activities">
        <div class="glass-card" style="padding:2rem; background-color: white; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
          <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1.5rem;">Manage Region & Activity Subtitles</h3>
          
          <form method="POST" action="/admin" enctype="multipart/form-data" class="form-card" style="padding:1.5rem; background:#f8fafc; border-radius:var(--radius-md); margin-bottom:2.5rem;">
            <input type="hidden" id="act-id" name="act_id" value="">
            <input type="hidden" id="act-existing-route-map" name="existing_act_route_map" value="">
            
            <div class="edit-fields-grid">
              <div class="form-group">
                <label for="act-name">Subtitle Name</label>
                <input type="text" id="act-name" name="act_name" class="form-input" required placeholder="e.g. Everest Region Trekking">
              </div>
              <div class="form-group">
                <label for="act-category">Base Category</label>
                <select id="act-category" name="act_category" class="form-input">
                  <option value="trekking">Trekking</option>
                  <option value="climbing">Peak Climbing</option>
                  <option value="tour">Tours</option>
                </select>
              </div>
            </div>

            <div class="edit-fields-grid">
              <div class="form-group">
                <label for="act-slug">Slug Link</label>
                <input type="text" id="act-slug" name="act_slug" class="form-input" placeholder="e.g. everest-region-trekking">
              </div>
              <div class="form-group">
                <label for="act-route_map_file">Upload Route Map Image File</label>
                <input type="file" id="act-route_map_file" name="route_map_file" class="form-input">
              </div>
            </div>

            <div class="form-group">
              <label for="act-description">Region Description</label>
              <textarea id="act-description" name="act_description" class="form-input" rows="2" placeholder="e.g. Classic trails in the Khumbu region..." oninput="runMiniSEO('act')"></textarea>
            </div>

            <div style="border-top:1px solid var(--border-color); padding-top:1rem; margin-top:1rem;">
              <div class="edit-fields-grid">
                <div class="form-group">
                  <label for="act-seo_title">SEO Page Title</label>
                  <input type="text" id="act-seo_title" name="act_seo_title" class="form-input" oninput="runMiniSEO('act')">
                </div>
                <div class="form-group">
                  <label for="act-seo_keywords">Focus Keyphrase</label>
                  <input type="text" id="act-seo_keywords" name="act_seo_keywords" class="form-input" oninput="runMiniSEO('act')">
                </div>
              </div>
              <div class="form-group">
                <label for="act-seo_description">SEO Meta Description</label>
                <textarea id="act-seo_description" name="act_seo_description" class="form-input" rows="2" oninput="runMiniSEO('act')"></textarea>
              </div>
              <div id="act-seo-mini" style="font-size:0.82rem; background:white; border:1px solid var(--border-color); border-radius:var(--radius-sm); padding:0.75rem;"></div>
            </div>
            
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <button type="button" class="btn btn-outline btn-sm" onclick="resetActivityForm()">Clear Form</button>
              <button type="submit" name="save_activity" class="btn btn-primary btn-sm">Save Subtitle</button>
            </div>
          </form>

          <div style="overflow-x: auto; width: 100%;">
            <table>
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Category</th>
                  <th>Slug Link</th>
                  <th>Route Map File</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($activities as $act): ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($act['name']); ?></strong></td>
                    <td style="text-transform:capitalize;"><?php echo htmlspecialchars($act['category']); ?></td>
                    <td><code>/activity/<?php echo htmlspecialchars($act['slug']); ?></code></td>
                    <td>
                      <?php if (!empty($act['route_map'])): ?>
                        <a href="/<?php echo htmlspecialchars($act['route_map']); ?>" target="_blank" style="font-size:0.8rem; font-weight:700;">View File</a>
                      <?php else: ?>
                        <span style="font-size:0.8rem; color:var(--text-muted);">None</span>
                      <?php endif; ?>
                    </td>
                    <td style="display:flex; gap:0.5rem;">
                      <button class="btn btn-primary btn-sm" onclick="editActivity(<?php echo htmlspecialchars(json_encode($act)); ?>)">Edit</button>
                      <form method="POST" action="/admin" onsubmit="return confirmAdminDelete(this, 'this region category')">
                        <input type="hidden" name="act_id" value="<?php echo $act['id']; ?>">
                        <button type="submit" name="delete_activity" class="btn btn-secondary btn-sm" style="background-color:var(--color-danger);">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Tab 4: Blogs & Pages CRUD -->
      <div class="admin-panel" id="panel-blogs">
        <div class="glass-card" style="padding:1.5rem; background:#f8fafc; border:1px solid var(--border-color); border-radius:var(--radius-lg); margin-bottom:2rem;">
          <h3 style="font-family:var(--font-heading); color:var(--color-primary); font-size:1.2rem; margin:0 0 1rem; font-weight:800;">Company Info Pages Quick Edit</h3>
          <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
            <?php
              $company_page_slugs = ['why-travel-with-us', 'about-us', 'our-team', 'legal-documents', 'terms-and-conditions'];
              foreach ($company_page_slugs as $company_slug):
                $company_page = null;
                foreach ($pages as $p) {
                    if (($p['slug'] ?? '') === $company_slug) {
                        $company_page = $p;
                        break;
                    }
                }
                if ($company_page):
            ?>
              <button type="button" class="btn btn-outline btn-sm" onclick="editPage(<?php echo htmlspecialchars(json_encode($company_page)); ?>)">
                <?php echo htmlspecialchars($company_page['title']); ?>
              </button>
            <?php else: ?>
              <button type="button" class="btn btn-primary btn-sm" onclick="resetPageForm(); document.getElementById('page-title').value='<?php echo ucwords(str_replace('-', ' ', $company_slug)); ?>'; document.getElementById('page-slug').value='<?php echo $company_slug; ?>';">
                Add <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $company_slug))); ?>
              </button>
            <?php endif; endforeach; ?>
          </div>
        </div>
        <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:2.5rem;">
          
          <!-- Blogs CRUD -->
          <div class="glass-card" style="padding:2rem; background-color:white; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
            <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1.5rem;">Himalayan Blogs CRUD</h3>
            
            <form method="POST" action="/admin" enctype="multipart/form-data" class="form-card" style="padding:1.5rem; background:#f8fafc; border-radius:var(--radius-md); margin-bottom:2.5rem;">
              <input type="hidden" id="blog-id" name="blog_id" value="">
              <input type="hidden" id="blog-existing-image" name="existing_blog_image" value="">
              <input type="hidden" id="blog-existing-route-map" name="existing_blog_route_map" value="">
              
              <div class="form-group">
                <label for="blog-title">Article Title</label>
                <input type="text" id="blog-title" name="blog_title" class="form-input" required placeholder="e.g. EBC Trek Packing Lists" oninput="runMiniSEO('blog')">
              </div>
              <div class="form-group">
                <label for="blog-slug">Slug Link</label>
                <input type="text" id="blog-slug" name="blog_slug" class="form-input" placeholder="e.g. everest-trek-packing-lists">
              </div>
              <div class="edit-fields-grid">
                <div class="form-group">
                  <label for="blog-date">Date Published</label>
                  <input type="date" id="blog-date" name="blog_date" class="form-input" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                  <label for="blog_image_file">Article Cover Image</label>
                  <input type="file" id="blog_image_file" name="blog_image_file" class="form-input">
                </div>
              </div>
              <div class="form-group">
                <label for="blog_route_map_file">Article Route Map Image File</label>
                <input type="file" id="blog_route_map_file" name="route_map_file" class="form-input">
              </div>
              <div class="form-group">
                <label for="blog-content">Article Content (Rich Text)</label>
                <textarea id="blog-content" name="blog_content" class="form-input" rows="8" oninput="runMiniSEO('blog')"></textarea>
              </div>

              <!-- Blog SEO -->
              <div class="edit-fields-grid" style="border-top:1px solid var(--border-color); padding-top:1rem; margin-top:1rem;">
                <div class="form-group">
                  <label for="blog-seo_title">SEO Page Title</label>
                  <input type="text" id="blog-seo_title" name="seo_title" class="form-input" oninput="runMiniSEO('blog')">
                </div>
                <div class="form-group">
                  <label for="blog-seo_keywords">SEO Keywords</label>
                  <input type="text" id="blog-seo_keywords" name="seo_keywords" class="form-input" oninput="runMiniSEO('blog')">
                </div>
              </div>
              <div class="form-group">
                <label for="blog-seo_description">SEO Meta Description</label>
                <textarea id="blog-seo_description" name="seo_description" class="form-input" rows="2" oninput="runMiniSEO('blog')"></textarea>
              </div>
              <div id="blog-seo-mini" style="font-size:0.82rem; background:white; border:1px solid var(--border-color); border-radius:var(--radius-sm); padding:0.75rem;"></div>

              <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1rem;">
                <button type="button" class="btn btn-outline btn-sm" onclick="resetBlogForm()">Clear Form</button>
                <button type="submit" name="save_blog" class="btn btn-primary btn-sm">Save Blog Post</button>
              </div>
            </form>

            <div style="overflow-x: auto; width: 100%;">
              <table>
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($blogs as $b): ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars($b['title']); ?></strong></td>
                      <td><?php echo htmlspecialchars($b['date']); ?></td>
                      <td style="display:flex; gap:0.5rem;">
                        <button class="btn btn-primary btn-sm" onclick="editBlog(<?php echo htmlspecialchars(json_encode($b)); ?>)">Edit</button>
                        <form method="POST" action="/admin" onsubmit="return confirmAdminDelete(this, 'this blog post')">
                          <input type="hidden" name="blog_id" value="<?php echo $b['id']; ?>">
                          <button type="submit" name="delete_blog" class="btn btn-secondary btn-sm" style="background-color:var(--color-danger);">Delete</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Static Pages CRUD -->
          <div class="glass-card" style="padding:2rem; background-color:white; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
            <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1.5rem;">Static Pages CRUD</h3>
            
            <form method="POST" action="/admin" enctype="multipart/form-data" class="form-card" style="padding:1.5rem; background:#f8fafc; border-radius:var(--radius-md); margin-bottom:2.5rem;">
              <input type="hidden" id="page-id" name="page_id" value="">
              <input type="hidden" id="page-existing-route-map" name="existing_page_route_map" value="">
              <input type="hidden" id="page-existing-document-file" name="existing_page_document_file" value="">
              
              <div class="form-group">
                <label for="page-title">Page Title</label>
                <input type="text" id="page-title" name="page_title" class="form-input" required placeholder="e.g. Terms & Booking Policy" oninput="runMiniSEO('page')">
              </div>
              <div class="form-group">
                <label for="page-slug">Slug Link</label>
                <input type="text" id="page-slug" name="page_slug" class="form-input" placeholder="e.g. booking-policy">
              </div>
              <div class="form-group">
                <label for="page_route_map_file">Page Route Map Image File</label>
                <input type="file" id="page_route_map_file" name="route_map_file" class="form-input">
              </div>
              <div class="form-group">
                <label for="page_document_file">Legal Document / PDF Upload</label>
                <input type="file" id="page_document_file" name="page_document_file" class="form-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp">
              </div>
              <div class="form-group">
                <label for="page-content">Page Content (Rich Text)</label>
                <textarea id="page-content" name="page_content" class="form-input" rows="8" oninput="runMiniSEO('page')"></textarea>
              </div>

              <!-- Page SEO -->
              <div style="border-top:1px solid var(--border-color); padding-top:1rem; margin-top:1rem;">
                <div class="form-group">
                  <label for="page-seo_title">SEO Page Title</label>
                  <input type="text" id="page-seo_title" name="seo_title" class="form-input" oninput="runMiniSEO('page')">
                </div>
                <div class="form-group">
                  <label for="page-seo_keywords">Focus Keyphrase</label>
                  <input type="text" id="page-seo_keywords" name="seo_keywords" class="form-input" oninput="runMiniSEO('page')">
                </div>
                <div class="form-group">
                  <label for="page-seo_description">SEO Meta Description</label>
                  <textarea id="page-seo_description" name="seo_description" class="form-input" rows="2" oninput="runMiniSEO('page')"></textarea>
                </div>
                <div id="page-seo-mini" style="font-size:0.82rem; background:white; border:1px solid var(--border-color); border-radius:var(--radius-sm); padding:0.75rem;"></div>
              </div>

              <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1rem;">
                <button type="button" class="btn btn-outline btn-sm" onclick="resetPageForm()">Clear Form</button>
                <button type="submit" name="save_page" class="btn btn-primary btn-sm">Save Page Content</button>
              </div>
            </form>

            <div style="overflow-x: auto; width: 100%;">
              <table>
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Slug URL</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pages as $p): ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                      <td><code>/page/<?php echo htmlspecialchars($p['slug']); ?></code></td>
                      <td style="display:flex; gap:0.5rem;">
                        <button class="btn btn-primary btn-sm" onclick="editPage(<?php echo htmlspecialchars(json_encode($p)); ?>)">Edit</button>
                        <form method="POST" action="/admin" onsubmit="return confirmAdminDelete(this, 'this static page')">
                          <input type="hidden" name="page_id" value="<?php echo $p['id']; ?>">
                          <button type="submit" name="delete_page" class="btn btn-secondary btn-sm" style="background-color:var(--color-danger);">Delete</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>

      <!-- Tab 5: Traveler Reviews moderation (Req 10) -->
      <div class="admin-panel" id="panel-reviews">
        <div class="glass-card" style="padding:2rem; background-color: white; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
          <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1.5rem;">Moderate Traveler Reviews</h3>
          
          <?php
          // Get all reviews across all packages
          $all_reviews = [];
          foreach ($packages as $pkg) {
              if (isset($pkg['reviews']) && is_array($pkg['reviews'])) {
                  foreach ($pkg['reviews'] as $idx => $rev) {
                      $all_reviews[] = [
                          'pkg_id' => $pkg['id'],
                          'pkg_title' => $pkg['title'],
                          'rev_idx' => $idx,
                          'name' => $rev['name'],
                          'rating' => $rev['rating'],
                          'comment' => $rev['comment'],
                          'date' => $rev['date'],
                          'approved' => isset($rev['approved']) ? $rev['approved'] : false
                      ];
                  }
              }
          }
          ?>
          <?php if (empty($all_reviews)): ?>
            <p style="text-align:center; padding:3rem 0; color:var(--text-muted);">No traveler reviews submitted yet.</p>
          <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:1.25rem;">
              <?php foreach (array_reverse($all_reviews) as $rev): ?>
                <div class="card-wrapper" style="padding:1.5rem; border:1px solid var(--border-color); border-radius:var(--radius-md); background-color: <?php echo $rev['approved'] ? '#f8fafc' : '#fffbeb'; ?>; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.5rem;">
                  <div>
                    <span style="font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; display:block; margin-bottom:0.25rem;">
                      Trip: <strong><?php echo htmlspecialchars($rev['pkg_title']); ?></strong>
                    </span>
                    <strong style="font-size:1rem; color:var(--color-primary);"><?php echo htmlspecialchars($rev['name']); ?></strong> 
                    <span style="color:var(--color-secondary); margin-left:0.5rem;"><?php echo str_repeat('★', $rev['rating']); ?></span>
                    <p style="margin:0.5rem 0 0; font-size:0.9rem; line-height:1.5; color:var(--text-medium);"><?php echo htmlspecialchars($rev['comment']); ?></p>
                    <span style="font-size:0.75rem; color:var(--text-muted); display:block; margin-top:0.25rem;">Submitted Date: <?php echo htmlspecialchars($rev['date']); ?></span>
                  </div>
                  
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <?php if (!$rev['approved']): ?>
                      <form method="POST" action="/admin">
                        <input type="hidden" name="rev_pkg_id" value="<?php echo $rev['pkg_id']; ?>">
                        <input type="hidden" name="rev_idx" value="<?php echo $rev['rev_idx']; ?>">
                        <button type="submit" name="approve_review" class="btn btn-primary btn-sm" style="background-color:var(--color-success);">Approve Review</button>
                      </form>
                    <?php endif; ?>
                    <form method="POST" action="/admin" onsubmit="return confirmAdminDelete(this, 'this review')">
                      <input type="hidden" name="rev_pkg_id" value="<?php echo $rev['pkg_id']; ?>">
                      <input type="hidden" name="rev_idx" value="<?php echo $rev['rev_idx']; ?>">
                      <button type="submit" name="delete_review" class="btn btn-secondary btn-sm" style="background-color:var(--color-danger);">Delete</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Tab 6: General Media Gallery (Req 4) -->
      <div class="admin-panel" id="panel-media">
        <div class="glass-card" style="padding:2rem; background-color: white; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
          <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1rem;">General Media Library & Uploader</h3>
          <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1.5rem;">Upload images, videos, maps, and copy their relative links to insert them inside packages, itineraries, FAQs, blogs, or page details.</p>
          
          <form method="POST" action="/admin" enctype="multipart/form-data" class="form-card" style="padding:1.5rem; background:#f8fafc; border-radius:var(--radius-md); margin-bottom:2.5rem; display:flex; gap:1.5rem; align-items:flex-end; flex-wrap:wrap;">
            <div class="form-group" style="margin-bottom:0; flex-grow:1; min-width:250px;">
              <label for="media_file">Choose File to Upload</label>
              <input type="file" id="media_file" name="media_file" class="form-input" required>
            </div>
            <button type="submit" name="upload_media_file" class="btn btn-primary" style="height:44px; font-weight:700;">Upload File</button>
          </form>

          <h4 style="font-family:var(--font-heading); margin-bottom:1.25rem; font-weight:800;">Uploaded Media Files</h4>
          <?php
          $uploads_dir = 'assets/images/uploads';
          $media_files = [];
          if (file_exists($uploads_dir)) {
              $m_files = scandir($uploads_dir);
              foreach ($m_files as $file) {
                  if ($file !== '.' && $file !== '..') {
                      $media_files[] = $uploads_dir . '/' . $file;
                  }
              }
          }
          
          if (empty($media_files)):
          ?>
            <p style="text-align:center; padding:3rem 0; color:var(--text-muted);">No media files uploaded to server yet.</p>
          <?php else: ?>
            <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:1.5rem;" id="media-grid">
              <?php foreach (array_reverse($media_files) as $filepath): 
                  $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
                  $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                  $is_video = in_array($ext, ['mp4', 'mov', 'avi', 'webm']);
              ?>
                <div style="border:1px solid var(--border-color); border-radius:var(--radius-md); overflow:hidden; background:#f8fafc; display:flex; flex-direction:column; justify-content:space-between;">
                  <div style="height:140px; display:flex; align-items:center; justify-content:center; background:#e2e8f0; position:relative; overflow:hidden;">
                    <?php if ($is_image): ?>
                      <img src="/<?php echo htmlspecialchars($filepath); ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php elseif ($is_video): ?>
                      <span style="font-size:2.5rem;">🎥</span>
                    <?php else: ?>
                      <span style="font-size:2.5rem;">📄</span>
                    <?php endif; ?>
                    <span style="position:absolute; bottom:5px; right:5px; background:rgba(0,0,0,0.6); color:white; font-size:0.65rem; padding:2px 4px; border-radius:2px; font-weight:bold;"><?php echo strtoupper($ext); ?></span>
                  </div>
                  <div style="padding:1rem;">
                    <code style="display:block; font-size:0.75rem; word-break:break-all; margin-bottom:0.75rem; background:#fff; padding:4px; border:1px solid var(--border-color); border-radius:2px;"><?php echo htmlspecialchars($filepath); ?></code>
                    <div style="display:flex; justify-content:space-between; gap:0.5rem;">
                      <button type="button" class="btn btn-outline btn-sm" style="flex-grow:1; padding:0.4rem 0.5rem; font-size:0.72rem; font-weight:700;" onclick="copyToClipboard('<?php echo htmlspecialchars($filepath); ?>')">Copy Link</button>
                      <form method="POST" action="/admin" onsubmit="return confirmAdminDelete(this, 'this media file')" style="margin:0;">
                        <input type="hidden" name="media_path" value="<?php echo $filepath; ?>">
                        <button type="submit" name="delete_media_file" class="btn btn-secondary btn-sm" style="background-color:var(--color-danger); padding:0.4rem 0.5rem; font-size:0.72rem;">Delete</button>
                      </form>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Tab 7: Global Settings (Logo, WhatsApp, Navigation, Custom Code, badges) (Req 8, 9) -->
      <div class="admin-panel" id="panel-settings">
        <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:2.5rem;">
          
          <div class="glass-card" style="padding:2rem; background-color: white; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
            <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1.5rem;">Global Company Details</h3>
            
            <form id="settings-main-form" method="POST" action="/admin" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1.25rem;">
              <div class="form-group">
                <label for="company_name">Company Name</label>
                <input type="text" id="company_name" name="company_name" class="form-input" required value="<?php echo htmlspecialchars($site_name); ?>">
              </div>

              <div class="edit-fields-grid">
                <div class="form-group">
                  <label for="brand_line_1">Header Company Name Line 1</label>
                  <input type="text" id="brand_line_1" name="brand_line_1" class="form-input" required value="<?php echo htmlspecialchars($brand_line_1); ?>" placeholder="TOP MOUNTAIN">
                </div>
                <div class="form-group">
                  <label for="brand_line_2">Header Company Name Line 2</label>
                  <input type="text" id="brand_line_2" name="brand_line_2" class="form-input" required value="<?php echo htmlspecialchars($brand_line_2); ?>" placeholder="TREKS PVT. LTD.">
                </div>
              </div>
              
              <div class="edit-fields-grid">
                <div class="form-group">
                  <label for="whatsapp_number">WhatsApp Hotline Number</label>
                  <input type="text" id="whatsapp_number" name="whatsapp_number" class="form-input" required value="<?php echo htmlspecialchars($site_whatsapp); ?>">
                </div>
                <div class="form-group">
                  <label for="logo_file">Upload New Logo (Image)</label>
                  <input type="file" id="logo_file" name="logo_file" class="form-input">
                  <span style="font-size:0.75rem; color:var(--text-muted);">Current Logo: <code><?php echo htmlspecialchars($site_logo); ?></code></span>
                </div>
                <div class="form-group">
                  <label for="owner_photo_file">Owner Photo Before WhatsApp Number</label>
                  <input type="file" id="owner_photo_file" name="owner_photo_file" class="form-input" accept="image/*">
                  <span style="font-size:0.75rem; color:var(--text-muted);">Current Owner Photo: <code><?php echo htmlspecialchars($owner_photo_path ?: 'Not uploaded'); ?></code></span>
                </div>
              </div>

              <div class="form-group">
                <label for="usd_to_npr_rate">USD to NPR Auto Conversion Rate</label>
                <input type="number" step="0.01" min="1" id="usd_to_npr_rate" name="usd_to_npr_rate" class="form-input" value="<?php echo htmlspecialchars($usd_to_npr_rate); ?>">
              </div>

              <div style="border-top:1px solid var(--border-color); padding-top:1.5rem; margin-top:0.5rem;">
                <h4 style="font-family:var(--font-heading); color:var(--color-primary); margin-bottom:1rem; font-weight:800;">Editable Contact Us Details</h4>
                <div class="form-group">
                  <label for="contact_intro">Contact Page Intro</label>
                  <input type="text" id="contact_intro" name="contact_intro" class="form-input" value="<?php echo htmlspecialchars($contact_settings['intro']); ?>">
                </div>
                <div class="form-group">
                  <label for="contact_address">Office Address</label>
                  <textarea id="contact_address" name="contact_address" class="form-input" rows="4"><?php echo htmlspecialchars($contact_settings['address']); ?></textarea>
                </div>
                <div class="edit-fields-grid">
                  <div class="form-group">
                    <label for="contact_landline">Direct Landline</label>
                    <input type="text" id="contact_landline" name="contact_landline" class="form-input" value="<?php echo htmlspecialchars($contact_settings['landline']); ?>">
                  </div>
                  <div class="form-group">
                    <label for="contact_emergency">Emergency Hotline</label>
                    <input type="text" id="contact_emergency" name="contact_emergency" class="form-input" value="<?php echo htmlspecialchars($contact_settings['emergency']); ?>">
                  </div>
                </div>
                <div class="edit-fields-grid">
                  <div class="form-group">
                    <label for="contact_email">Direct Email</label>
                    <input type="email" id="contact_email" name="contact_email" class="form-input" value="<?php echo htmlspecialchars($contact_settings['email']); ?>">
                  </div>
                  <div class="form-group">
                    <label for="contact_map_zoom">Map Zoom</label>
                    <input type="number" id="contact_map_zoom" name="contact_map_zoom" class="form-input" min="1" max="20" value="<?php echo htmlspecialchars($contact_settings['map_zoom']); ?>">
                  </div>
                </div>
                <div class="edit-fields-grid">
                  <div class="form-group">
                    <label for="contact_map_lat">Map Latitude</label>
                    <input type="text" id="contact_map_lat" name="contact_map_lat" class="form-input" value="<?php echo htmlspecialchars($contact_settings['map_lat']); ?>">
                  </div>
                  <div class="form-group">
                    <label for="contact_map_lng">Map Longitude</label>
                    <input type="text" id="contact_map_lng" name="contact_map_lng" class="form-input" value="<?php echo htmlspecialchars($contact_settings['map_lng']); ?>">
                  </div>
                </div>
              </div>

              <div style="border-top:1px solid var(--border-color); padding-top:1.5rem; margin-top:0.5rem;">
                <h4 style="font-family:var(--font-heading); color:var(--color-primary); margin-bottom:1rem; font-weight:800;">Home Package Offer Defaults</h4>
                <div class="edit-fields-grid">
                  <div class="form-group">
                    <label for="offer_default_badge">Default Badge Text</label>
                    <input type="text" id="offer_default_badge" name="offer_default_badge" class="form-input" value="<?php echo htmlspecialchars($home_offer_settings['default_badge']); ?>">
                  </div>
                  <div class="form-group">
                    <label for="offer_default_group_text">Default Group Offer Text</label>
                    <input type="text" id="offer_default_group_text" name="offer_default_group_text" class="form-input" value="<?php echo htmlspecialchars($home_offer_settings['default_group_text']); ?>">
                  </div>
                </div>
                <div class="edit-fields-grid">
                  <div class="form-group">
                    <label for="offer_default_days">Default Offer Days</label>
                    <input type="number" id="offer_default_days" name="offer_default_days" class="form-input" min="1" value="<?php echo htmlspecialchars($home_offer_settings['default_offer_days']); ?>">
                  </div>
                  <div class="form-group">
                    <label for="offer_limited_text">Limited Time Text</label>
                    <input type="text" id="offer_limited_text" name="offer_limited_text" class="form-input" value="<?php echo htmlspecialchars($home_offer_settings['limited_text']); ?>">
                  </div>
                </div>
              </div>

              <!-- Header & Footer Code Customizers (Req 8) -->
              <div style="border-top:1px solid var(--border-color); padding-top:1.5rem; margin-top:0.5rem;">
                <h4 style="font-family:var(--font-heading); color:var(--color-primary); margin-bottom:1rem; font-weight:800;">Dynamic Head & Footer Code Customizer</h4>
                <div class="form-group">
                  <label for="header_custom_html">Custom Header HTML/JS Code (Inject inside &lt;head&gt;)</label>
                  <textarea id="header_custom_html" name="header_custom_html" class="form-input" rows="4" placeholder="e.g. Google Analytics tracking code..."><?php echo isset($settings['header_custom_html']) ? htmlspecialchars($settings['header_custom_html']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                  <label for="footer_custom_html">Custom Footer HTML/JS Code (Inject before &lt;/body&gt;)</label>
                  <textarea id="footer_custom_html" name="footer_custom_html" class="form-input" rows="4" placeholder="e.g. Live chat widgets, custom tracking scripts..."><?php echo isset($settings['footer_custom_html']) ? htmlspecialchars($settings['footer_custom_html']) : ''; ?></textarea>
                </div>
              </div>

              <!-- Footer Badges Uploads (Req 9) -->
              <div style="border-top:1px solid var(--border-color); padding-top:1.5rem; margin-top:0.5rem;">
                <h4 style="font-family:var(--font-heading); color:var(--color-primary); margin-bottom:0.5rem; font-weight:800;">Upload Footer Partner/Association Logos</h4>
                <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.75rem;">Add up to 6 small badges. Each badge can open its official website in a new tab.</p>
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                  <?php for ($slot = 1; $slot <= 6; $slot++): ?>
                    <div style="display:grid; grid-template-columns:1fr 1fr 1.25fr; gap:0.75rem; align-items:end; border:1px solid var(--border-color); border-radius:var(--radius-sm); padding:0.75rem; background:#f8fafc;">
                      <div class="form-group" style="margin:0;">
                        <label>Badge <?php echo $slot; ?> Image</label>
                        <input type="file" name="footer_logos[]" class="form-input" accept="image/*">
                      </div>
                      <div class="form-group" style="margin:0;">
                        <label>Badge <?php echo $slot; ?> Label</label>
                        <input type="text" name="footer_logo_labels[]" class="form-input" placeholder="e.g. TAAN Member">
                      </div>
                      <div class="form-group" style="margin:0;">
                        <label>Badge <?php echo $slot; ?> Link</label>
                        <input type="url" name="footer_logo_links[]" class="form-input" placeholder="https://example.com">
                      </div>
                    </div>
                  <?php endfor; ?>
                </div>
              </div>

              <!-- Rebuild Dynamic Navigation Menu Links -->
              <div style="border-top:1px solid var(--border-color); padding-top:1.5rem; margin-top:0.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                  <h4 style="font-family:var(--font-heading); color:var(--color-primary); font-weight:800; margin:0;">Edit Navigation Links</h4>
                  <button type="button" class="btn btn-outline btn-sm" onclick="addNavigationRow()">+ Add Link</button>
                </div>
                <div id="navigation-links-container" style="display:flex; flex-direction:column; gap:0.75rem;">
                  <?php foreach ($site_nav as $idx => $nav_item): ?>
                    <div class="nav-edit-row" style="display:flex; gap:0.75rem; align-items:center;">
                      <input type="text" name="nav_titles[]" class="form-input" style="flex:1;" required value="<?php echo htmlspecialchars($nav_item['title']); ?>" placeholder="Title">
                      <input type="text" name="nav_links[]" class="form-input" style="flex:1.5;" required value="<?php echo htmlspecialchars($nav_item['link']); ?>" placeholder="Link URI">
                      <button type="button" class="btn btn-outline btn-sm" onclick="moveNavRow(this, -1)">Up</button>
                      <button type="button" class="btn btn-outline btn-sm" onclick="moveNavRow(this, 1)">Down</button>
                      <button type="button" class="btn btn-secondary btn-sm" style="background-color:var(--color-danger);" onclick="this.parentElement.remove()">Delete</button>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <button type="submit" name="save_settings" class="btn btn-primary" style="height:44px; font-weight:700; margin-top:1.5rem;">Save General Settings</button>
            </form>
          </div>

          <!-- Footer Badge List (Req 9) -->
          <div class="glass-card" style="padding:2rem; background-color:white; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
            <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1.5rem;">Footer Partner Logos</h3>
            <?php 
            $footer_icons = normalize_footer_icons($settings['footer_icons'] ?? []);
            if (empty($footer_icons)): 
            ?>
              <p style="color:var(--text-muted); text-align:center; padding:3rem 0;">No partner logos uploaded yet.</p>
            <?php else: ?>
              <div style="display:flex; flex-direction:column; gap:1rem;">
                <?php foreach ($footer_icons as $idx => $icon): ?>
                  <div style="display:grid; grid-template-columns:120px 1fr auto; gap:1rem; align-items:center; border:1px solid var(--border-color); padding:0.75rem; border-radius:var(--radius-sm); background:#f8fafc;">
                    <img src="/<?php echo htmlspecialchars($icon['image']); ?>" style="height:36px; max-width:120px; object-fit:contain;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                      <input type="hidden" form="settings-main-form" name="footer_icon_paths[]" value="<?php echo htmlspecialchars($icon['image']); ?>">
                      <input type="text" form="settings-main-form" name="footer_icon_labels[]" class="form-input" value="<?php echo htmlspecialchars($icon['label']); ?>" placeholder="Badge label">
                      <input type="url" form="settings-main-form" name="footer_icon_urls[]" class="form-input" value="<?php echo htmlspecialchars($icon['url']); ?>" placeholder="https://official-site.example">
                      <code style="font-size:0.75rem; word-break:break-all; grid-column:1 / -1;"><?php echo htmlspecialchars($icon['image']); ?></code>
                    </div>
                    <form method="POST" action="/admin" style="margin:0;" onsubmit="return confirmAdminDelete(this, 'this footer logo')">
                      <input type="hidden" name="delete_footer_icon_idx" value="<?php echo $idx; ?>">
                      <!-- Reuses settings save trigger with delete variable index -->
                      <input type="hidden" name="company_name" value="<?php echo htmlspecialchars($site_name); ?>">
                      <input type="hidden" name="brand_line_1" value="<?php echo htmlspecialchars($brand_line_1); ?>">
                      <input type="hidden" name="brand_line_2" value="<?php echo htmlspecialchars($brand_line_2); ?>">
                      <input type="hidden" name="whatsapp_number" value="<?php echo htmlspecialchars($site_whatsapp); ?>">
                      <?php foreach ($site_nav as $n): ?>
                        <input type="hidden" name="nav_titles[]" value="<?php echo htmlspecialchars($n['title']); ?>">
                        <input type="hidden" name="nav_links[]" value="<?php echo htmlspecialchars($n['link']); ?>">
                      <?php endforeach; ?>
                      <input type="hidden" name="header_custom_html" value="<?php echo htmlspecialchars($settings['header_custom_html'] ?? ''); ?>">
                      <input type="hidden" name="footer_custom_html" value="<?php echo htmlspecialchars($settings['footer_custom_html'] ?? ''); ?>">
                      <button type="submit" name="save_settings" class="btn btn-secondary btn-sm" style="background-color:var(--color-danger);">Remove</button>
                    </form>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        </div>
      </div>

      <!-- Tab 8: Theme Text & Color Setup -->
      <div class="admin-panel" id="panel-theme">
        <div class="glass-card" style="padding:2.5rem; background-color: white; border:1px solid var(--border-color); border-radius:var(--radius-lg); max-width:1200px; margin:0 auto;">
          <h3 style="font-family:var(--font-heading); font-size:1.45rem; color:var(--color-primary); margin-bottom:1.5rem; border-bottom:2px solid var(--color-primary); padding-bottom:0.5rem;">Theme Text and Color Setup</h3>
          
          <form method="POST" action="/admin" style="display:flex; flex-direction:column; gap:2.5rem;">
            
            <!-- SECTION 1: Brand & Page Colors -->
            <div>
              <h4 style="font-family:var(--font-heading); font-size:1.15rem; color:var(--color-secondary); margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem;">
                🎨 Core Branding & Page Layout Settings
              </h4>
              <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; padding-left:0.5rem; border-left:3px solid var(--color-accent);">
                <div class="form-group">
                  <label for="primary_color">Primary Accent Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($theme['primary']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['primary']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="secondary_color">Secondary Highlight Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="secondary_color" name="secondary_color" value="<?php echo htmlspecialchars($theme['secondary']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['secondary']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="accent_color">Subtle Interface Highlights</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="accent_color" name="accent_color" value="<?php echo htmlspecialchars($theme['accent']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['accent']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="title_color">Title & Headings Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="title_color" name="title_color" value="<?php echo htmlspecialchars($theme['title_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['title_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="page_bg">Full Page Background Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="page_bg" name="page_bg" value="<?php echo htmlspecialchars($theme['page_bg']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['page_bg']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="logo_font_size">Company Logo Text Font Size</label>
                  <input type="text" id="logo_font_size" name="logo_font_size" value="<?php echo htmlspecialchars($theme['logo_font_size']); ?>" class="form-input" placeholder="e.g. 22px or 1.5rem">
                </div>
              </div>
            </div>

            <!-- SECTION 2: Header & Navigation settings -->
            <div>
              <h4 style="font-family:var(--font-heading); font-size:1.15rem; color:var(--color-secondary); margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem;">
                🏢 Header & Navigation Menu Settings
              </h4>
              <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; padding-left:0.5rem; border-left:3px solid var(--color-accent);">
                <div class="form-group">
                  <label for="header_bg">Header Background Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="header_bg" name="header_bg" value="<?php echo htmlspecialchars($theme['header_bg']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['header_bg']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="header_text">Header Main Text Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="header_text" name="header_text" value="<?php echo htmlspecialchars($theme['header_text']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['header_text']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="menu_bg">Navigation Bar Background</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="menu_bg" name="menu_bg" value="<?php echo htmlspecialchars($theme['menu_bg']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['menu_bg']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="menu_text_size">Menu Item Font Size</label>
                  <select id="menu_text_size" name="menu_text_size" class="form-input">
                    <option value="12px" <?php echo $theme['menu_text_size'] === '12px' ? 'selected' : ''; ?>>12px (Small)</option>
                    <option value="14px" <?php echo $theme['menu_text_size'] === '14px' ? 'selected' : ''; ?>>14px (Standard)</option>
                    <option value="15px" <?php echo $theme['menu_text_size'] === '15px' ? 'selected' : ''; ?>>15px (Default)</option>
                    <option value="16px" <?php echo $theme['menu_text_size'] === '16px' ? 'selected' : ''; ?>>16px (Medium)</option>
                    <option value="18px" <?php echo $theme['menu_text_size'] === '18px' ? 'selected' : ''; ?>>18px (Large)</option>
                    <option value="20px" <?php echo $theme['menu_text_size'] === '20px' ? 'selected' : ''; ?>>20px (XL - Doubled feel)</option>
                    <option value="24px" <?php echo $theme['menu_text_size'] === '24px' ? 'selected' : ''; ?>>24px (Double Text Size)</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="menu_padding">Navigation Menu Height / Padding</label>
                  <select id="menu_padding" name="menu_padding" class="form-input">
                    <option value="4px" <?php echo $theme['menu_padding'] === '4px' ? 'selected' : ''; ?>>4px (Very Compact)</option>
                    <option value="8px" <?php echo $theme['menu_padding'] === '8px' ? 'selected' : ''; ?>>8px (Default)</option>
                    <option value="12px" <?php echo $theme['menu_padding'] === '12px' ? 'selected' : ''; ?>>12px (Comfortable)</option>
                    <option value="16px" <?php echo $theme['menu_padding'] === '16px' ? 'selected' : ''; ?>>16px (Tall)</option>
                    <option value="20px" <?php echo $theme['menu_padding'] === '20px' ? 'selected' : ''; ?>>20px (Double Height)</option>
                    <option value="25px" <?php echo $theme['menu_padding'] === '25px' ? 'selected' : ''; ?>>25px (Super Tall)</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="dropdown_icon">Dropdown Indicator Icon</label>
                  <select id="dropdown_icon" name="dropdown_icon" class="form-input">
                    <option value="triangle" <?php echo $theme['dropdown_icon'] === 'triangle' ? 'selected' : ''; ?>>▼ Solid Triangle (Default)</option>
                    <option value="chevron" <?php echo $theme['dropdown_icon'] === 'chevron' ? 'selected' : ''; ?>>⌄ Thin Chevron</option>
                    <option value="pointer" <?php echo $theme['dropdown_icon'] === 'pointer' ? 'selected' : ''; ?>>↓ Down Arrow</option>
                    <option value="double_chevron" <?php echo $theme['dropdown_icon'] === 'double_chevron' ? 'selected' : ''; ?>>» Double Chevron</option>
                    <option value="none" <?php echo $theme['dropdown_icon'] === 'none' ? 'selected' : ''; ?>>No Indicator Icon</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="submenu_link_color">Package Link Text Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="submenu_link_color" name="submenu_link_color" value="<?php echo htmlspecialchars($theme['submenu_link_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['submenu_link_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="submenu_hover_color">Package Link Hover Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="submenu_hover_color" name="submenu_hover_color" value="<?php echo htmlspecialchars($theme['submenu_hover_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['submenu_hover_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="submenu_prefix_color">Prefix Arrow Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="submenu_prefix_color" name="submenu_prefix_color" value="<?php echo htmlspecialchars($theme['submenu_prefix_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['submenu_prefix_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="package_arrow">Package Menu Prefix Arrow</label>
                  <input type="text" id="package_arrow" name="package_arrow" value="<?php echo htmlspecialchars($theme['package_arrow'] ?? ' ▸'); ?>" class="form-input" placeholder="e.g.  ▸ or  → or »">
                </div>
                <div class="form-group">
                  <label for="mega_menu_width">Trekking Mega Menu Width</label>
                  <input type="text" id="mega_menu_width" name="mega_menu_width" value="<?php echo htmlspecialchars($theme['mega_menu_width']); ?>" class="form-input" placeholder="e.g. 1240px or 96vw">
                </div>
              </div>
            </div>

            <!-- SECTION 3: Footer Customizer -->
            <div>
              <h4 style="font-family:var(--font-heading); font-size:1.15rem; color:var(--color-secondary); margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem;">
                👣 Footer Settings
              </h4>
              <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; padding-left:0.5rem; border-left:3px solid var(--color-accent);">
                <div class="form-group">
                  <label for="footer_bg">Footer Background Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="footer_bg" name="footer_bg" value="<?php echo htmlspecialchars($theme['footer_bg']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['footer_bg']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="footer_text">Footer Text & Links Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="footer_text" name="footer_text" value="<?php echo htmlspecialchars($theme['footer_text']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['footer_text']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
              </div>
            </div>

            <!-- SECTION 4: Home Page Slider Typography Customizer -->
            <div>
              <h4 style="font-family:var(--font-heading); font-size:1.15rem; color:var(--color-secondary); margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem;">
                🌅 Home Page Hero Slider Typography & Alignment
              </h4>
              <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; padding-left:0.5rem; border-left:3px solid var(--color-accent);">
                <div class="form-group">
                  <label for="slider_title_color">Slider Package Title Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="slider_title_color" name="slider_title_color" value="<?php echo htmlspecialchars($theme['slider_title_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['slider_title_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="slider_title_size">Slider Title Text Size</label>
                  <input type="text" id="slider_title_size" name="slider_title_size" value="<?php echo htmlspecialchars($theme['slider_title_size']); ?>" class="form-input" placeholder="e.g. 50px">
                </div>
                <div class="form-group">
                  <label for="slider_desc_color">Slider Description Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="slider_desc_color" name="slider_desc_color" value="<?php echo htmlspecialchars($theme['slider_desc_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['slider_desc_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="slider_desc_size">Slider Description Text Size</label>
                  <input type="text" id="slider_desc_size" name="slider_desc_size" value="<?php echo htmlspecialchars($theme['slider_desc_size']); ?>" class="form-input" placeholder="e.g. 18px">
                </div>
                <div class="form-group">
                  <label for="slider_text_align">Slider Text Alignment</label>
                  <select id="slider_text_align" name="slider_text_align" class="form-input">
                    <option value="left" <?php echo $theme['slider_text_align'] === 'left' ? 'selected' : ''; ?>>Left Aligned</option>
                    <option value="center" <?php echo $theme['slider_text_align'] === 'center' ? 'selected' : ''; ?>>Centered</option>
                    <option value="right" <?php echo $theme['slider_text_align'] === 'right' ? 'selected' : ''; ?>>Right Aligned</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- SECTION 5: Trekking Category Typography Customizer -->
            <div>
              <h4 style="font-family:var(--font-heading); font-size:1.15rem; color:var(--color-secondary); margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem;">
                🧗 Trekking Pages Style Overrides
              </h4>
              <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; padding-left:0.5rem; border-left:3px solid var(--color-accent);">
                <div class="form-group">
                  <label for="trek_title_color">Trekking Title Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="trek_title_color" name="trek_title_color" value="<?php echo htmlspecialchars($theme['trek_title_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['trek_title_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="trek_title_size">Trekking Title Size</label>
                  <input type="text" id="trek_title_size" name="trek_title_size" value="<?php echo htmlspecialchars($theme['trek_title_size']); ?>" class="form-input" placeholder="e.g. 40px">
                </div>
                <div class="form-group">
                  <label for="trek_desc_color">Trekking Description Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="trek_desc_color" name="trek_desc_color" value="<?php echo htmlspecialchars($theme['trek_desc_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['trek_desc_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="trek_desc_size">Trekking Description Size</label>
                  <input type="text" id="trek_desc_size" name="trek_desc_size" value="<?php echo htmlspecialchars($theme['trek_desc_size']); ?>" class="form-input" placeholder="e.g. 16px">
                </div>
                <div class="form-group">
                  <label for="trek_align">Trekking Content Alignment</label>
                  <select id="trek_align" name="trek_align" class="form-input">
                    <option value="left" <?php echo $theme['trek_align'] === 'left' ? 'selected' : ''; ?>>Left Aligned</option>
                    <option value="center" <?php echo $theme['trek_align'] === 'center' ? 'selected' : ''; ?>>Centered</option>
                    <option value="right" <?php echo $theme['trek_align'] === 'right' ? 'selected' : ''; ?>>Right Aligned</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- SECTION 6: Tour Category Typography Customizer -->
            <div>
              <h4 style="font-family:var(--font-heading); font-size:1.15rem; color:var(--color-secondary); margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem;">
                🚗 Tour Pages Style Overrides
              </h4>
              <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; padding-left:0.5rem; border-left:3px solid var(--color-accent);">
                <div class="form-group">
                  <label for="tour_title_color">Tour Title Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="tour_title_color" name="tour_title_color" value="<?php echo htmlspecialchars($theme['tour_title_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['tour_title_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="tour_title_size">Tour Title Size</label>
                  <input type="text" id="tour_title_size" name="tour_title_size" value="<?php echo htmlspecialchars($theme['tour_title_size']); ?>" class="form-input" placeholder="e.g. 40px">
                </div>
                <div class="form-group">
                  <label for="tour_desc_color">Tour Description Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="tour_desc_color" name="tour_desc_color" value="<?php echo htmlspecialchars($theme['tour_desc_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['tour_desc_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="tour_desc_size">Tour Description Size</label>
                  <input type="text" id="tour_desc_size" name="tour_desc_size" value="<?php echo htmlspecialchars($theme['tour_desc_size']); ?>" class="form-input" placeholder="e.g. 16px">
                </div>
                <div class="form-group">
                  <label for="tour_align">Tour Content Alignment</label>
                  <select id="tour_align" name="tour_align" class="form-input">
                    <option value="left" <?php echo $theme['tour_align'] === 'left' ? 'selected' : ''; ?>>Left Aligned</option>
                    <option value="center" <?php echo $theme['tour_align'] === 'center' ? 'selected' : ''; ?>>Centered</option>
                    <option value="right" <?php echo $theme['tour_align'] === 'right' ? 'selected' : ''; ?>>Right Aligned</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- SECTION 7: Peak Climbing Category Typography Customizer -->
            <div>
              <h4 style="font-family:var(--font-heading); font-size:1.15rem; color:var(--color-secondary); margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem;">
                🏔️ Peak Climbing Pages Style Overrides
              </h4>
              <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; padding-left:0.5rem; border-left:3px solid var(--color-accent);">
                <div class="form-group">
                  <label for="climb_title_color">Climbing Title Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="climb_title_color" name="climb_title_color" value="<?php echo htmlspecialchars($theme['climb_title_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['climb_title_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="climb_title_size">Climbing Title Size</label>
                  <input type="text" id="climb_title_size" name="climb_title_size" value="<?php echo htmlspecialchars($theme['climb_title_size']); ?>" class="form-input" placeholder="e.g. 40px">
                </div>
                <div class="form-group">
                  <label for="climb_desc_color">Climbing Description Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="climb_desc_color" name="climb_desc_color" value="<?php echo htmlspecialchars($theme['climb_desc_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['climb_desc_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="climb_desc_size">Climbing Description Size</label>
                  <input type="text" id="climb_desc_size" name="climb_desc_size" value="<?php echo htmlspecialchars($theme['climb_desc_size']); ?>" class="form-input" placeholder="e.g. 16px">
                </div>
                <div class="form-group">
                  <label for="climb_align">Climbing Content Alignment</label>
                  <select id="climb_align" name="climb_align" class="form-input">
                    <option value="left" <?php echo $theme['climb_align'] === 'left' ? 'selected' : ''; ?>>Left Aligned</option>
                    <option value="center" <?php echo $theme['climb_align'] === 'center' ? 'selected' : ''; ?>>Centered</option>
                    <option value="right" <?php echo $theme['climb_align'] === 'right' ? 'selected' : ''; ?>>Right Aligned</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- SECTION 8: Static Pages Typography Customizer -->
            <div>
              <h4 style="font-family:var(--font-heading); font-size:1.15rem; color:var(--color-secondary); margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem;">
                📄 Static Content Pages Style Overrides
              </h4>
              <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; padding-left:0.5rem; border-left:3px solid var(--color-accent);">
                <div class="form-group">
                  <label for="page_title_color">Page Title Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="page_title_color" name="page_title_color" value="<?php echo htmlspecialchars($theme['page_title_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['page_title_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="page_title_size">Page Title Size</label>
                  <input type="text" id="page_title_size" name="page_title_size" value="<?php echo htmlspecialchars($theme['page_title_size']); ?>" class="form-input" placeholder="e.g. 40px">
                </div>
                <div class="form-group">
                  <label for="page_desc_color">Page Description Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="page_desc_color" name="page_desc_color" value="<?php echo htmlspecialchars($theme['page_desc_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['page_desc_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="page_desc_size">Page Description Size</label>
                  <input type="text" id="page_desc_size" name="page_desc_size" value="<?php echo htmlspecialchars($theme['page_desc_size']); ?>" class="form-input" placeholder="e.g. 16px">
                </div>
                <div class="form-group">
                  <label for="page_align">Page Content Alignment</label>
                  <select id="page_align" name="page_align" class="form-input">
                    <option value="left" <?php echo $theme['page_align'] === 'left' ? 'selected' : ''; ?>>Left Aligned</option>
                    <option value="center" <?php echo $theme['page_align'] === 'center' ? 'selected' : ''; ?>>Centered</option>
                    <option value="right" <?php echo $theme['page_align'] === 'right' ? 'selected' : ''; ?>>Right Aligned</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- SECTION 9: Blogs Page Typography Customizer -->
            <div>
              <h4 style="font-family:var(--font-heading); font-size:1.15rem; color:var(--color-secondary); margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem;">
                ✍️ Blogs & Article Pages Style Overrides
              </h4>
              <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; padding-left:0.5rem; border-left:3px solid var(--color-accent);">
                <div class="form-group">
                  <label for="blog_title_color">Blog Title Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="blog_title_color" name="blog_title_color" value="<?php echo htmlspecialchars($theme['blog_title_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['blog_title_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="blog_title_size">Blog Title Size</label>
                  <input type="text" id="blog_title_size" name="blog_title_size" value="<?php echo htmlspecialchars($theme['blog_title_size']); ?>" class="form-input" placeholder="e.g. 40px">
                </div>
                <div class="form-group">
                  <label for="blog_desc_color">Blog Description Color</label>
                  <div style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="color" id="blog_desc_color" name="blog_desc_color" value="<?php echo htmlspecialchars($theme['blog_desc_color']); ?>" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:4px; padding:0; cursor:pointer;" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" value="<?php echo htmlspecialchars($theme['blog_desc_color']); ?>" class="form-input" readonly style="flex:1;">
                  </div>
                </div>
                <div class="form-group">
                  <label for="blog_desc_size">Blog Description Size</label>
                  <input type="text" id="blog_desc_size" name="blog_desc_size" value="<?php echo htmlspecialchars($theme['blog_desc_size']); ?>" class="form-input" placeholder="e.g. 16px">
                </div>
                <div class="form-group">
                  <label for="blog_align">Blog Content Alignment</label>
                  <select id="blog_align" name="blog_align" class="form-input">
                    <option value="left" <?php echo $theme['blog_align'] === 'left' ? 'selected' : ''; ?>>Left Aligned</option>
                    <option value="center" <?php echo $theme['blog_align'] === 'center' ? 'selected' : ''; ?>>Centered</option>
                    <option value="right" <?php echo $theme['blog_align'] === 'right' ? 'selected' : ''; ?>>Right Aligned</option>
                  </select>
                </div>
              </div>
            </div>

            <button type="submit" name="theme_submit" class="btn btn-primary" style="height:48px; font-weight:700; width:100%; font-size:1rem; letter-spacing:0.5px;">Save Theme Setup Configuration</button>
          </form>
        </div>
      </div>

      <!-- Tab 9: Visitor Inquiries Log -->
      <div class="admin-panel" id="panel-inquiries">
        <div class="glass-card" style="padding:2rem; background-color: white; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
          <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1.5rem;">Visitor Booking Inquiries Log (<?php echo count($inquiries); ?> records)</h3>
          
          <?php if (empty($inquiries)): ?>
            <p style="text-align:center; padding:3rem 0; color:var(--text-muted);">No booking inquiries received yet.</p>
          <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:1.25rem;">
              <?php foreach (array_reverse($inquiries) as $inq): ?>
                <div style="border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.5rem; background:#f8fafc;">
                  <div style="display:flex; justify-content:space-between; flex-wrap:wrap; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem; margin-bottom:0.75rem; font-size:0.85rem; color:var(--text-muted);">
                    <span>ID: <code><?php echo htmlspecialchars($inq['id']); ?></code></span>
                    <span>Received: <?php echo htmlspecialchars($inq['submitted_at']); ?></span>
                  </div>
                  <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:1.5rem;" id="inq-details-columns">
                    <div>
                      <strong style="font-size:1.15rem; color:var(--color-primary);"><?php echo htmlspecialchars($inq['name']); ?></strong><br>
                      <span>📧 <a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>" style="font-weight:600; text-decoration:none; color:var(--color-accent);"><?php echo htmlspecialchars($inq['email']); ?></a></span><br>
                      <span>📞 Phone: <?php echo htmlspecialchars($inq['phone']); ?></span>
                    </div>
                    <div>
                      <strong>Selected Expedition:</strong> <span style="color:var(--color-secondary); font-weight:800;"><?php echo htmlspecialchars($inq['package']); ?></span><br>
                      <strong>Travel Date:</strong> <?php echo htmlspecialchars($inq['travel_date'] ?: 'Not Decided'); ?><br>
                      <strong>Travelers count:</strong> <?php echo (int)$inq['travelers']; ?>
                    </div>
                  </div>
                  <?php if (!empty($inq['message'])): ?>
                    <div style="margin-top:1rem; padding:0.75rem; background:white; border-left:3px solid var(--color-secondary); font-size:0.88rem; color:var(--text-medium); line-height:1.5;">
                      <strong>Message/Requirements:</strong><br>
                      <?php echo nl2br(htmlspecialchars($inq['message'])); ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Tab 9: Admin Profile & Security -->
      <div class="admin-panel" id="panel-profile">
        <div class="glass-card" style="padding:2.5rem; background-color: white; border:1px solid var(--border-color); border-radius:var(--radius-lg); max-width:600px;">
          <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
            <span>👤</span> Admin Profile & Security
          </h3>
          
          <form method="POST" action="/admin">
            <div class="form-group" style="margin-bottom:1.25rem;">
              <label for="admin_name" style="font-weight:700;">Profile Name</label>
              <input type="text" id="admin_name" name="admin_name" class="form-input" required value="<?php echo htmlspecialchars($admin_creds['name']); ?>" placeholder="e.g. Administrator">
            </div>
            <div class="form-group" style="margin-bottom:1.25rem;">
              <label for="admin_username" style="font-weight:700;">Username</label>
              <input type="text" id="admin_username" name="admin_username" class="form-input" required value="<?php echo htmlspecialchars($admin_creds['username']); ?>" placeholder="admin">
            </div>
            <div class="form-group" style="margin-bottom:1.25rem;">
              <label for="admin_email" style="font-weight:700;">Recovery Email</label>
              <input type="email" id="admin_email" name="admin_email" class="form-input" value="<?php echo htmlspecialchars($admin_creds['email'] ?? ''); ?>" placeholder="admin@topmountaintreks.com">
            </div>
            <div class="form-group" style="margin-bottom:1.25rem;">
              <label for="admin_phone" style="font-weight:700;">Recovery Phone</label>
              <input type="text" id="admin_phone" name="admin_phone" class="form-input" value="<?php echo htmlspecialchars($admin_creds['phone'] ?? ''); ?>" placeholder="+977 985-1123456">
            </div>
            <div class="form-group" style="margin-bottom:2rem;">
              <label for="admin_password" style="font-weight:700;">Change Password</label>
              <input type="password" id="admin_password" name="admin_password" class="form-input" required value="<?php echo htmlspecialchars($admin_creds['password']); ?>" placeholder="Password" autocomplete="new-password">
              <small style="color:var(--text-muted); display:block; margin-top:0.25rem;">Ensure you keep this safe, as it will be required to log in next time.</small>
            </div>
            
            <button type="submit" name="save_profile" class="btn btn-primary" style="height:44px; font-weight:700; width:100%;">Update Profile & Security</button>
          </form>
        </div>
      </div>

    </div>

    <script>
      // Tab switcher
      const tabs = document.querySelectorAll('.admin-tab');
      const panels = document.querySelectorAll('.admin-panel');
      
      tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          tabs.forEach(t => t.classList.remove('active'));
          panels.forEach(p => p.classList.remove('active'));
          
          tab.classList.add('active');
          const target = tab.getAttribute('data-target');
          document.getElementById(target).classList.add('active');
          
          // Re-trigger layout alignment inside panels
          triggerSEO();
        });
      });

      // Navigation Edit dynamic row uploader
      function addNavigationRow() {
        const container = document.getElementById('navigation-links-container');
        const div = document.createElement('div');
        div.className = 'nav-edit-row';
        div.style.display = 'flex';
        div.style.gap = '0.75rem';
        div.style.alignItems = 'center';
        div.innerHTML = `
          <input type="text" name="nav_titles[]" class="form-input" style="flex:1;" required placeholder="Title">
          <input type="text" name="nav_links[]" class="form-input" style="flex:1.5;" required placeholder="Link URI">
          <button type="button" class="btn btn-outline btn-sm" onclick="moveNavRow(this, -1)">Up</button>
          <button type="button" class="btn btn-outline btn-sm" onclick="moveNavRow(this, 1)">Down</button>
          <button type="button" class="btn btn-secondary btn-sm" style="background-color:var(--color-danger);" onclick="this.parentElement.remove()">Delete</button>
        `;
        container.appendChild(div);
      }

      function moveNavRow(button, direction) {
        const row = button.closest('.nav-edit-row');
        if (!row) return;
        if (direction < 0 && row.previousElementSibling) {
          row.parentElement.insertBefore(row, row.previousElementSibling);
        } else if (direction > 0 && row.nextElementSibling) {
          row.parentElement.insertBefore(row.nextElementSibling, row);
        }
      }

      function confirmAdminDelete(form, label) {
        const pass = window.prompt('Enter current admin password to delete ' + label + ':');
        if (!pass) return false;
        let field = form.querySelector('input[name="delete_confirm_password"]');
        if (!field) {
          field = document.createElement('input');
          field.type = 'hidden';
          field.name = 'delete_confirm_password';
          form.appendChild(field);
        }
        field.value = pass;
        return window.confirm('Delete ' + label + ' permanently?');
      }

      // Rich Text CKEditor Instances
      let richEditors = {};

      function initRichEditor(textareaId) {
        const el = document.getElementById(textareaId);
        if (!el) return;
        if (richEditors[textareaId]) {
          try { richEditors[textareaId].destroy(); } catch(e) {}
        }
        ClassicEditor.create(el, {
          toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo' ]
        }).then(editor => {
          richEditors[textareaId] = editor;
          
          // Sync editor updates to main textarea for SEO analyser
          editor.model.document.on('change:data', () => {
            el.value = editor.getData();
            triggerSEO();
            if (textareaId === 'blog-content') runMiniSEO('blog');
            if (textareaId === 'page-content') runMiniSEO('page');
          });
        }).catch(error => {
          console.error(error);
        });
      }

      // Lazy load CKEditor on focus/click for dynamic day rows to prevent browser freezes (Req 2/6)
      function initRichEditorOnFocus(textareaId) {
        const el = document.getElementById(textareaId);
        if (!el) return;
        
        const loadEditor = () => {
          el.removeEventListener('focus', loadEditor);
          el.removeEventListener('click', loadEditor);
          
          if (richEditors[textareaId]) return;
          
          ClassicEditor.create(el, {
            toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo' ]
          }).then(editor => {
            richEditors[textareaId] = editor;
            editor.model.document.on('change:data', () => {
              el.value = editor.getData();
              triggerSEO();
            });
            // Focus the active editor pane
            editor.editing.view.focus();
          }).catch(error => {
            console.error(error);
          });
        };
        
        el.addEventListener('focus', loadEditor);
        el.addEventListener('click', loadEditor);
      }

      // Initialize CKEditor for base textareas
      document.addEventListener('DOMContentLoaded', () => {
         initRichEditor('edit-overview');
         initRichEditor('edit-highlights');
         initRichEditor('edit-costIncludes');
         initRichEditor('edit-costExcludes');
         initRichEditor('edit-requiredPermits');
         initRichEditor('blog-content');
         initRichEditor('page-content');
         initAdminPriceSync();
         
         // Trigger default SEO
         triggerSEO();
         runMiniSEO('act');
         runMiniSEO('blog');
         runMiniSEO('page');
      });

      function initAdminPriceSync() {
        const usd = document.getElementById('edit-priceUSD');
        const npr = document.getElementById('edit-priceNPR');
        const rateInput = document.getElementById('usd_to_npr_rate');
        if (!usd || !npr) return;
        const getRate = () => {
          const rateFromSettings = rateInput ? parseFloat(rateInput.value) : NaN;
          const rateFromField = parseFloat(usd.dataset.rate || '');
          return !Number.isNaN(rateFromSettings) && rateFromSettings > 0 ? rateFromSettings : (!Number.isNaN(rateFromField) && rateFromField > 0 ? rateFromField : 133);
        };
        const sync = () => {
          const usdValue = parseFloat(usd.value);
          if (!Number.isNaN(usdValue)) {
            npr.value = Math.round(usdValue * getRate());
          }
        };
        usd.addEventListener('input', sync);
        rateInput?.addEventListener('input', sync);
        sync();
      }

      // Generating dynamic URL slugs
      function generateSlug(text) {
         const slug = text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
         document.getElementById('edit-slug').value = slug;
         triggerSEO();
      }

      function escapeInput(value) {
         return String(value || '')
           .replace(/&/g, '&amp;')
           .replace(/</g, '&lt;')
           .replace(/>/g, '&gt;')
           .replace(/"/g, '&quot;');
      }

      function addTripFactRow(label = '', value = '') {
         const container = document.getElementById('trip-facts-container');
         const row = document.createElement('div');
         row.className = 'trip-fact-row';
         row.style.display = 'grid';
         row.style.gridTemplateColumns = '1fr 1.4fr auto';
         row.style.gap = '0.75rem';
         row.style.alignItems = 'center';
         row.innerHTML = `
           <input type="text" name="fact_labels[]" class="form-input" value="${escapeInput(label)}" placeholder="Fact label, e.g. Maximum altitude">
           <input type="text" name="fact_values[]" class="form-input" value="${escapeInput(value)}" placeholder="Fact value, e.g. 5,545 m at Kala Patthar">
           <button type="button" class="btn btn-secondary btn-sm" style="background-color:var(--color-danger);" onclick="this.parentElement.remove()">Remove</button>
         `;
         container.appendChild(row);
      }

      function addDetailSectionRow(title = '', content = '') {
         const container = document.getElementById('detail-sections-container');
         const row = document.createElement('div');
         row.className = 'detail-section-row';
         row.style.border = '1px solid var(--border-color)';
         row.style.borderRadius = 'var(--radius-md)';
         row.style.padding = '1rem';
         row.style.background = '#fafafa';
         row.innerHTML = `
           <div class="form-group">
             <label>Section Title</label>
             <input type="text" name="section_titles[]" class="form-input" value="${escapeInput(title)}" placeholder="Best Time, Difficulty, Accommodation...">
           </div>
           <div class="form-group">
             <label>Section Content</label>
             <textarea name="section_contents[]" class="form-input" rows="5">${escapeInput(content)}</textarea>
           </div>
           <button type="button" class="btn btn-secondary btn-sm" style="background-color:var(--color-danger);" onclick="this.parentElement.remove()">Remove Section</button>
         `;
         container.appendChild(row);
      }

      // Rebuilding dynamic Day row elements in form (Req 3, 6, 11)
      let dayIndex = 0;
      function addItineraryDayRow(dayNum = '', title = '', desc = '', accomm = 'Teahouse Lodge', meals = 'Breakfast, Lunch, Dinner', location = '', x = '', y = '') {
         dayIndex++;
         const container = document.getElementById('itinerary-rows-container');
         const dNum = dayNum !== '' ? dayNum : container.children.length + 1;
         
         const row = document.createElement('div');
         row.className = 'itinerary-day-row';
         row.style.border = '1px solid var(--border-color)';
         row.style.padding = '1.5rem';
         row.style.borderRadius = 'var(--radius-md)';
         row.style.marginBottom = '1.5rem';
         row.style.background = '#fafafa';
         
         const uniqueId = 'day-' + dayIndex;
         
         row.innerHTML = `
            <div style="display:grid; grid-template-columns: 80px 1.5fr 1fr; gap:1.25rem; margin-bottom:1rem;">
              <div>
                <label style="font-weight:700; font-size:0.85rem;">Day</label>
                <input type="number" name="itinerary_days[]" class="form-input" value="${dNum}" required>
              </div>
              <div>
                <label style="font-weight:700; font-size:0.85rem;">Day Title</label>
                <input type="text" name="itinerary_titles[]" class="form-input" value="${title}" required placeholder="e.g. Flight to Lukla & Trek to Phakding">
              </div>
              <div>
                <label style="font-weight:700; font-size:0.85rem;">Campsite / Reached Location (Req 11)</label>
                <input type="text" name="itinerary_location[]" class="form-input" value="${location}" placeholder="e.g. Phakding">
              </div>
            </div>

            <!-- Dynamic grid columns to resolve squeezing (Req 3) -->
            <div style="margin-bottom:1rem;">
              <label style="font-weight:700; font-size:0.85rem;">Accommodation (Rich Text)</label>
              <textarea id="day-accomm-${uniqueId}" name="itinerary_accomm[]" class="form-input" rows="2">${accomm}</textarea>
            </div>
            <div style="margin-bottom:1rem;">
              <label style="font-weight:700; font-size:0.85rem;">Meals Provided (Rich Text)</label>
              <textarea id="day-meals-${uniqueId}" name="itinerary_meals[]" class="form-input" rows="2">${meals}</textarea>
            </div>

            <div style="margin-bottom:1rem;">
              <label style="font-weight:700; font-size:0.85rem;">Day Details Description (Rich Text)</label>
              <textarea id="day-desc-${uniqueId}" name="itinerary_descs[]" class="form-input" rows="4">${desc}</textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr 1.2fr; gap:1rem; align-items:center;">
              <div>
                <label style="font-size:0.75rem; color:var(--text-muted);">SVG Coordinate X (Optional)</label>
                <input type="number" name="itinerary_x[]" class="form-input" value="${x}" placeholder="e.g. 150">
              </div>
              <div>
                <label style="font-size:0.75rem; color:var(--text-muted);">SVG Coordinate Y (Optional)</label>
                <input type="number" name="itinerary_y[]" class="form-input" value="${y}" placeholder="e.g. 180">
              </div>
              
              <!-- Upload Image within Day Details (Req 6) -->
              <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1.2rem;">
                <div style="display:flex; gap:0.5rem; align-items:center;">
                  <button type="button" class="btn btn-outline btn-sm" onclick="triggerItineraryImageUpload('${uniqueId}')">📸 Insert Image</button>
                  <input type="file" id="upload-day-${uniqueId}" style="display:none;" onchange="uploadItineraryImage(this, '${uniqueId}')">
                </div>
                <button type="button" class="btn btn-secondary btn-sm" style="background-color:var(--color-danger); height:32px;" onclick="this.parentElement.parentElement.parentElement.remove()">Remove Day</button>
              </div>
            </div>
         `;
         
         container.appendChild(row);
         
          // Initialize CKEditor for Accommodation, Meals, and Descriptions dynamically on focus
          initRichEditorOnFocus('day-accomm-' + uniqueId);
          initRichEditorOnFocus('day-meals-' + uniqueId);
          initRichEditorOnFocus('day-desc-' + uniqueId);
      }

      function triggerItineraryImageUpload(dayId) {
         document.getElementById('upload-day-' + dayId).click();
      }

      function uploadItineraryImage(input, dayId) {
         if (input.files && input.files[0]) {
             let formData = new FormData();
             formData.append('media_file', input.files[0]);
             formData.append('ajax_upload_media', '1');
             
             fetch('/admin', {
                 method: 'POST',
                 body: formData
             })
             .then(res => res.json())
             .then(data => {
                 if (data.success) {
                     const editor = richEditors['day-desc-' + dayId];
                     if (editor) {
                         const viewFragment = editor.data.processor.toView('<img src="' + data.url + '" style="max-width:100%; height:auto; border-radius:8px;" alt="Trek photo">');
                         const modelFragment = editor.data.toModel(viewFragment);
                         editor.model.insertContent(modelFragment);
                     } else {
                         document.getElementById('day-desc-' + dayId).value += '<img src="' + data.url + '" style="max-width:100%; height:auto; border-radius:8px;" alt="Trek photo">';
                     }
                     alert('Image uploaded and inserted successfully!');
                 } else {
                     alert('Upload failed: ' + data.error);
                 }
             })
             .catch(err => {
                 console.error(err);
                 alert('Error uploading file.');
             });
         }
      }

      // Rebuilding FAQ day elements in form
      let faqIndex = 0;
      function addFaqRow(question = '', answer = '') {
         faqIndex++;
         const container = document.getElementById('faq-rows-container');
         const row = document.createElement('div');
         row.className = 'faq-row';
         row.style.border = '1px solid var(--border-color)';
         row.style.padding = '1.25rem';
         row.style.borderRadius = 'var(--radius-sm)';
         row.style.marginBottom = '1.25rem';
         row.style.background = '#fafafa';
         
         const uniqueId = 'faq-' + faqIndex;
         
         row.innerHTML = `
            <div style="margin-bottom:0.75rem;">
              <label style="font-weight:700; font-size:0.85rem;">Question</label>
              <input type="text" name="faq_qs[]" class="form-input" value="${question}" required placeholder="e.g. Is hot shower available on the trail?">
            </div>
            <div style="margin-bottom:0.75rem;">
              <label style="font-weight:700; font-size:0.85rem;">Answer (Rich Text)</label>
              <textarea id="faq-ans-${uniqueId}" name="faq_as[]" class="form-input" rows="3">${answer}</textarea>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <div style="display:flex; gap:0.5rem; align-items:center;">
                <button type="button" class="btn btn-outline btn-sm" onclick="triggerFaqImageUpload('${uniqueId}')">📸 Insert Image</button>
                <input type="file" id="upload-faq-${uniqueId}" style="display:none;" onchange="uploadFaqImage(this, '${uniqueId}')">
              </div>
              <button type="button" class="btn btn-secondary btn-sm" style="background-color:var(--color-danger); height:32px;" onclick="this.parentElement.parentElement.remove()">Remove FAQ</button>
            </div>
         `;
         
         container.appendChild(row);
         initRichEditorOnFocus('faq-ans-' + uniqueId);
      }

      function triggerFaqImageUpload(faqId) {
         document.getElementById('upload-faq-' + faqId).click();
      }

      function uploadFaqImage(input, faqId) {
         if (input.files && input.files[0]) {
             let formData = new FormData();
             formData.append('media_file', input.files[0]);
             formData.append('ajax_upload_media', '1');
             
             fetch('/admin', {
                 method: 'POST',
                 body: formData
             })
             .then(res => res.json())
             .then(data => {
                 if (data.success) {
                     const editor = richEditors['faq-ans-' + faqId];
                     if (editor) {
                         const viewFragment = editor.data.processor.toView('<img src="' + data.url + '" style="max-width:100%; height:auto; border-radius:8px;" alt="FAQ photo">');
                         const modelFragment = editor.data.toModel(viewFragment);
                         editor.model.insertContent(modelFragment);
                     } else {
                         document.getElementById('faq-ans-' + faqId).value += '<img src="' + data.url + '" style="max-width:100%; height:auto; border-radius:8px;" alt="FAQ photo">';
                     }
                     alert('Image uploaded and inserted successfully!');
                 } else {
                     alert('Upload failed: ' + data.error);
                 }
             })
             .catch(err => {
                 console.error(err);
                 alert('Error uploading file.');
             });
         }
      }

      // Parse pasted itinerary outlines (Req 7)
      function parseItineraryOutline() {
         const text = document.getElementById('itinerary-outline-paste').value.trim();
         if (!text) {
             alert('Please paste some itinerary outline lines first.');
             return;
         }
         
         const lines = text.split('\n');
         let dayCount = 0;
         
         lines.forEach(line => {
             line = line.trim();
             if (!line) return;
             
             let match = line.match(/^Day\s*(\d+)[\s:-]+(.*)$/i);
             let dayNum = dayCount + 1;
             let title = line;
             
             if (match) {
                 dayNum = parseInt(match[1]);
                 title = match[2].trim();
             } else {
                 let cleanTitle = line.replace(/^\d+[\s:-]+/, '');
                 title = cleanTitle.trim();
             }
             
             addItineraryDayRow(dayNum, title, '', 'Teahouse Lodge', 'Breakfast, Lunch, Dinner', '');
             dayCount++;
         });
         
         alert('Successfully parsed and generated ' + dayCount + ' itinerary day rows!');
         document.getElementById('itinerary-outline-paste').value = '';
      }

      function parseDetailedItinerary() {
         const textarea = document.getElementById('itinerary-detail-paste');
         const text = textarea.value.trim();
         if (!text) {
             alert('Please paste full itinerary details first.');
             return;
         }

         const rawLines = text.split(/\r?\n/);
         const blocks = [];
         let current = [];
         rawLines.forEach(line => {
             const trimmed = line.trim();
             const startsNewDay = /^Day\s*\d+/i.test(trimmed) && current.length > 0;
             if (!trimmed) {
                 if (current.length) {
                     blocks.push(current);
                     current = [];
                 }
             } else if (startsNewDay) {
                 blocks.push(current);
                 current = [trimmed];
             } else {
                 current.push(trimmed);
             }
         });
         if (current.length) blocks.push(current);

         if (!blocks.length) {
             alert('No day blocks found. Start each block with Day 1, Day 2, etc.');
             return;
         }

         document.getElementById('itinerary-rows-container').innerHTML = '';
         let count = 0;
         blocks.forEach((block, idx) => {
             let dayNum = idx + 1;
             let title = block[1] || block[0] || '';
             const dayMatch = (block[0] || '').match(/^Day\s*(\d+)(?:[\s:-]+(.*))?$/i);
             if (dayMatch) {
                 dayNum = parseInt(dayMatch[1], 10);
                 if (dayMatch[2]) title = dayMatch[2].trim();
             }
             const location = block[2] || '';
             const accomm = block[3] || 'Teahouse Lodge';
             const meals = block[4] || 'Breakfast, Lunch, Dinner';
             const desc = block.slice(5).join('\n') || block.slice(2).join('\n');
             addItineraryDayRow(dayNum, title, desc, accomm, meals, location);
             count++;
         });

         textarea.value = '';
         alert('Parsed and filled ' + count + ' detailed itinerary day rows.');
      }

      function parseBulkFaqs() {
         const textarea = document.getElementById('faq-bulk-paste');
         const text = textarea.value.trim();
         if (!text) {
             alert('Please paste FAQ question and answer lines first.');
             return;
         }
         const lines = text.split(/\r?\n/).map(line => line.trim()).filter(Boolean);
         if (lines.length < 2) {
             alert('Please paste at least one question line and one answer line.');
             return;
         }

         document.getElementById('faq-rows-container').innerHTML = '';
         let count = 0;
         for (let i = 0; i < lines.length; i += 2) {
             const question = lines[i] || '';
             const answer = lines[i + 1] || '';
             if (!question) continue;
             addFaqRow(question, answer);
             count++;
         }
         textarea.value = '';
         alert('Parsed and filled ' + count + ' FAQ rows.');
      }

      // Copy media path to clipboard
      function copyToClipboard(text) {
          navigator.clipboard.writeText(text).then(() => {
              alert('Media file path copied to clipboard: ' + text);
          });
      }

      // Populate forms on package edits
      function editPackage(pkg) {
         // Switch tab
         tabs.forEach(t => t.classList.remove('active'));
         panels.forEach(p => p.classList.remove('active'));
         document.querySelector('.admin-tab[data-target="panel-edit"]').classList.add('active');
         document.getElementById('panel-edit').classList.add('active');

         document.getElementById('package-form-title').textContent = "Edit Package: " + pkg.title;
         
         document.getElementById('edit-id').value = pkg.id;
         document.getElementById('edit-title').value = pkg.title;
         document.getElementById('edit-slug').value = pkg.slug;
         document.getElementById('edit-category').value = pkg.category;
         document.getElementById('edit-activity_id').value = pkg.activity_id || '';
         document.getElementById('edit-region').value = pkg.region;
         document.getElementById('edit-duration').value = pkg.duration;
         document.getElementById('edit-priceUSD').value = pkg.priceUSD;
         document.getElementById('edit-priceNPR').value = pkg.priceNPR;
         document.getElementById('edit-priceNote').value = pkg.priceNote || 'Estimated starting price per person';
         document.getElementById('edit-short_overview').value = pkg.short_overview || '';
         document.getElementById('edit-card_text').value = pkg.card_text || '';
         document.getElementById('edit-cta_text').value = pkg.cta_text || '';
         document.getElementById('edit-route_map_description').value = pkg.route_map_description || '';
         document.getElementById('edit-offer_badge').value = pkg.offer_badge || '';
         document.getElementById('edit-offer_old_price_usd').value = pkg.offer_old_price_usd || 0;
         document.getElementById('edit-offer_end_date').value = pkg.offer_end_date || '';
         document.getElementById('edit-group_offer_text').value = pkg.group_offer_text || '';
         document.getElementById('edit-difficulty').value = pkg.difficulty;
         document.getElementById('edit-maxAltitude').value = pkg.maxAltitude;
         document.getElementById('edit-bestSeason').value = pkg.bestSeason;
         document.getElementById('edit-groupSize').value = pkg.groupSize;
         document.getElementById('edit-startPoint').value = pkg.startPoint;
         document.getElementById('edit-endPoint').value = pkg.endPoint;
         document.getElementById('edit-accommodation').value = pkg.accommodation;
         document.getElementById('edit-meals').value = pkg.meals;
         
         // SEO & Socials
         document.getElementById('edit-seo_title').value = pkg.seo_title || '';
         document.getElementById('edit-seo_keywords').value = pkg.seo_keywords || '';
         document.getElementById('edit-secondary_keywords').value = pkg.secondary_keywords || '';
         document.getElementById('edit-seo_description').value = pkg.seo_description || '';
         document.getElementById('edit-image_alt').value = pkg.image_alt || '';
         document.getElementById('edit-image_alt_texts').value = Array.isArray(pkg.image_alt_texts) ? pkg.image_alt_texts.join('\n') : '';
         document.getElementById('edit-package_tags').value = Array.isArray(pkg.tags) ? pkg.tags.join('\n') : '';
         document.getElementById('edit-sourceLinks').value = Array.isArray(pkg.sourceLinks) ? pkg.sourceLinks.join('\n') : '';
         document.getElementById('edit-og_title').value = pkg.og_title || '';
         document.getElementById('edit-og_description').value = pkg.og_description || '';
         document.getElementById('edit-og_image').value = pkg.og_image || '';

         // Set main image preview
         document.getElementById('edit-existing_image').value = pkg.image || '';
         if (pkg.image) {
             document.getElementById('main-image-preview').innerHTML = `<img src="/${pkg.image}" style="height:60px; border-radius:4px; border:1px solid var(--border-color);">`;
         } else {
             document.getElementById('main-image-preview').innerHTML = '';
         }

         // Set route map preview
         document.getElementById('edit-existing_route_map').value = pkg.route_map || '';
         if (pkg.route_map) {
             document.getElementById('route-map-preview').innerHTML = `<img src="/${pkg.route_map}" style="height:60px; border-radius:4px; border:1px solid var(--border-color);">`;
         } else {
             document.getElementById('route-map-preview').innerHTML = '';
         }

         // Load Rich Text values
         if (richEditors['edit-overview']) richEditors['edit-overview'].setData(pkg.overview || '');
         
         // Highlights/Permits/Includes/Excludes check arrays vs strings
         const hlVal = Array.isArray(pkg.highlights) ? pkg.highlights.join('\n') : (pkg.highlights || '');
         if (richEditors['edit-highlights']) richEditors['edit-highlights'].setData(hlVal);

         const perVal = Array.isArray(pkg.requiredPermits) ? pkg.requiredPermits.join('\n') : (pkg.requiredPermits || '');
         if (richEditors['edit-requiredPermits']) richEditors['edit-requiredPermits'].setData(perVal);

         const incVal = Array.isArray(pkg.costIncludes) ? pkg.costIncludes.join('\n') : (pkg.costIncludes || '');
         if (richEditors['edit-costIncludes']) richEditors['edit-costIncludes'].setData(incVal);

         const excVal = Array.isArray(pkg.costExcludes) ? pkg.costExcludes.join('\n') : (pkg.costExcludes || '');
         if (richEditors['edit-costExcludes']) richEditors['edit-costExcludes'].setData(excVal);

         // Dynamic gallery previews
         const galContainer = document.getElementById('gallery-preview-container');
         galContainer.innerHTML = '';
         if (pkg.gallery && Array.isArray(pkg.gallery)) {
             pkg.gallery.forEach(gpath => {
                 const div = document.createElement('div');
                 div.style.position = 'relative';
                 div.innerHTML = `
                   <img src="/${gpath}" style="width:60px; height:60px; object-fit:cover; border-radius:4px; border:1px solid var(--border-color);">
                   <input type="hidden" name="existing_gallery_paths[]" value="${gpath}">
                   <button type="button" style="position:absolute; top:-5px; right:-5px; background:var(--color-danger); color:white; border:none; border-radius:50%; width:16px; height:16px; font-size:10px; cursor:pointer; display:flex; align-items:center; justify-content:center;" onclick="this.parentElement.remove()">&times;</button>
                 `;
                 galContainer.appendChild(div);
             });
         }

         document.getElementById('trip-facts-container').innerHTML = '';
         if (pkg.tripFacts && Array.isArray(pkg.tripFacts)) {
             pkg.tripFacts.forEach(fact => addTripFactRow(fact.label, fact.value));
         }

         document.getElementById('detail-sections-container').innerHTML = '';
         if (pkg.detailSections && Array.isArray(pkg.detailSections)) {
             pkg.detailSections.forEach(section => addDetailSectionRow(section.title, section.content));
         }

         // Clear & Rebuild dynamic Itinerary day rows
         document.getElementById('itinerary-rows-container').innerHTML = '';
         if (pkg.itinerary && Array.isArray(pkg.itinerary)) {
             pkg.itinerary.forEach(item => {
                 addItineraryDayRow(item.day, item.title, item.desc, item.accommodation || 'Teahouse Lodge', item.meals || 'Breakfast, Lunch, Dinner', item.location || '', item.x || '', item.y || '');
             });
         }

         // Clear & Rebuild FAQs
         document.getElementById('faq-rows-container').innerHTML = '';
         if (pkg.faqs && Array.isArray(pkg.faqs)) {
             pkg.faqs.forEach(faq => {
                 addFaqRow(faq.q, faq.a);
             });
         }

         triggerSEO();
      }

      function resetPackageForm() {
         document.getElementById('package-form-title').textContent = "Create New Package";
         document.getElementById('edit-id').value = '';
         document.getElementById('edit-title').value = '';
         document.getElementById('edit-slug').value = '';
         document.getElementById('edit-category').value = 'trekking';
         document.getElementById('edit-activity_id').value = '';
         document.getElementById('edit-region').value = '';
         document.getElementById('edit-duration').value = '10';
         document.getElementById('edit-priceUSD').value = '950';
         document.getElementById('edit-priceNPR').value = '126350';
         document.getElementById('edit-short_overview').value = '';
         document.getElementById('edit-card_text').value = '';
         document.getElementById('edit-cta_text').value = '';
         document.getElementById('edit-route_map_description').value = '';
         document.getElementById('edit-offer_badge').value = '<?php echo htmlspecialchars($home_offer_settings['default_badge'], ENT_QUOTES); ?>';
         document.getElementById('edit-offer_old_price_usd').value = '0';
         document.getElementById('edit-offer_end_date').value = '';
         document.getElementById('edit-group_offer_text').value = '<?php echo htmlspecialchars($home_offer_settings['default_group_text'], ENT_QUOTES); ?>';
         document.getElementById('edit-maxAltitude').value = '';
         document.getElementById('edit-bestSeason').value = '';
         document.getElementById('edit-groupSize').value = '';
         document.getElementById('edit-startPoint').value = 'Kathmandu';
         document.getElementById('edit-endPoint').value = 'Kathmandu';
         
         document.getElementById('edit-existing_image').value = '';
         document.getElementById('main-image-preview').innerHTML = '';
         document.getElementById('edit-existing_route_map').value = '';
         document.getElementById('route-map-preview').innerHTML = '';
         document.getElementById('gallery-preview-container').innerHTML = '';
         document.getElementById('trip-facts-container').innerHTML = '';
         document.getElementById('detail-sections-container').innerHTML = '';

         if (richEditors['edit-overview']) richEditors['edit-overview'].setData('');
         if (richEditors['edit-highlights']) richEditors['edit-highlights'].setData('');
         if (richEditors['edit-costIncludes']) richEditors['edit-costIncludes'].setData('');
         if (richEditors['edit-costExcludes']) richEditors['edit-costExcludes'].setData('');
         if (richEditors['edit-requiredPermits']) richEditors['edit-requiredPermits'].setData('');

         document.getElementById('itinerary-rows-container').innerHTML = '';
         document.getElementById('faq-rows-container').innerHTML = '';
         
         document.getElementById('edit-seo_title').value = '';
         document.getElementById('edit-seo_keywords').value = '';
         document.getElementById('edit-secondary_keywords').value = '';
         document.getElementById('edit-seo_description').value = '';
         document.getElementById('edit-image_alt').value = '';
         document.getElementById('edit-image_alt_texts').value = '';
         document.getElementById('edit-package_tags').value = '';
         document.getElementById('edit-sourceLinks').value = '';
         document.getElementById('edit-og_title').value = '';
         document.getElementById('edit-og_description').value = '';
         document.getElementById('edit-og_image').value = '';

         triggerSEO();
      }

      // Populate activity forms
      function editActivity(act) {
         document.getElementById('act-id').value = act.id;
         document.getElementById('act-name').value = act.name;
         document.getElementById('act-slug').value = act.slug;
         document.getElementById('act-category').value = act.category;
         document.getElementById('act-description').value = act.description || '';
         document.getElementById('act-existing-route-map').value = act.route_map || '';
         document.getElementById('act-seo_title').value = act.seo_title || '';
         document.getElementById('act-seo_keywords').value = act.seo_keywords || '';
         document.getElementById('act-seo_description').value = act.seo_description || '';
         runMiniSEO('act');
      }

      function resetActivityForm() {
         document.getElementById('act-id').value = '';
         document.getElementById('act-name').value = '';
         document.getElementById('act-slug').value = '';
         document.getElementById('act-category').value = 'trekking';
         document.getElementById('act-description').value = '';
         document.getElementById('act-existing-route-map').value = '';
         document.getElementById('act-seo_title').value = '';
         document.getElementById('act-seo_keywords').value = '';
         document.getElementById('act-seo_description').value = '';
         runMiniSEO('act');
      }

      // Populate blog forms
      function editBlog(b) {
         // Switch tab
         tabs.forEach(t => t.classList.remove('active'));
         panels.forEach(p => p.classList.remove('active'));
         document.querySelector('.admin-tab[data-target="panel-blogs"]').classList.add('active');
         document.getElementById('panel-blogs').classList.add('active');

         document.getElementById('blog-id').value = b.id;
         document.getElementById('blog-title').value = b.title;
         document.getElementById('blog-slug').value = b.slug;
         document.getElementById('blog-date').value = b.date;
         
         document.getElementById('blog-existing-image').value = b.image || '';
         document.getElementById('blog-existing-route-map').value = b.route_map || '';
         document.getElementById('blog-seo_title').value = b.seo_title || '';
         document.getElementById('blog-seo_keywords').value = b.seo_keywords || '';
         document.getElementById('blog-seo_description').value = b.seo_description || '';
         
         if (richEditors['blog-content']) richEditors['blog-content'].setData(b.content || '');
         runMiniSEO('blog');
      }

      function resetBlogForm() {
         document.getElementById('blog-id').value = '';
         document.getElementById('blog-title').value = '';
         document.getElementById('blog-slug').value = '';
         document.getElementById('blog-date').value = '<?php echo date('Y-m-d'); ?>';
         document.getElementById('blog-existing-image').value = '';
         document.getElementById('blog-existing-route-map').value = '';
         document.getElementById('blog-seo_title').value = '';
         document.getElementById('blog-seo_keywords').value = '';
         document.getElementById('blog-seo_description').value = '';
         if (richEditors['blog-content']) richEditors['blog-content'].setData('');
         runMiniSEO('blog');
      }

      // Populate static page forms
      function editPage(p) {
         tabs.forEach(t => t.classList.remove('active'));
         panels.forEach(p => p.classList.remove('active'));
         document.querySelector('.admin-tab[data-target="panel-blogs"]').classList.add('active');
         document.getElementById('panel-blogs').classList.add('active');

         document.getElementById('page-id').value = p.id;
         document.getElementById('page-title').value = p.title;
         document.getElementById('page-slug').value = p.slug;
         document.getElementById('page-existing-route-map').value = p.route_map || '';
         document.getElementById('page-existing-document-file').value = p.document_file || '';
         document.getElementById('page-seo_title').value = p.seo_title || '';
         document.getElementById('page-seo_keywords').value = p.seo_keywords || '';
         document.getElementById('page-seo_description').value = p.seo_description || '';
         
         if (richEditors['page-content']) richEditors['page-content'].setData(p.content || '');
         runMiniSEO('page');
      }

      function resetPageForm() {
         document.getElementById('page-id').value = '';
         document.getElementById('page-title').value = '';
         document.getElementById('page-slug').value = '';
         document.getElementById('page-existing-route-map').value = '';
         document.getElementById('page-existing-document-file').value = '';
         document.getElementById('page-seo_title').value = '';
         document.getElementById('page-seo_keywords').value = '';
         document.getElementById('page-seo_description').value = '';
         if (richEditors['page-content']) richEditors['page-content'].setData('');
         runMiniSEO('page');
      }

      function runMiniSEO(type) {
        const config = {
          act: {
            title: 'act-seo_title',
            keyword: 'act-seo_keywords',
            desc: 'act-seo_description',
            slug: 'act-slug',
            body: 'act-description',
            out: 'act-seo-mini'
          },
          blog: {
            title: 'blog-seo_title',
            keyword: 'blog-seo_keywords',
            desc: 'blog-seo_description',
            slug: 'blog-slug',
            body: 'blog-content',
            editor: 'blog-content',
            out: 'blog-seo-mini'
          },
          page: {
            title: 'page-seo_title',
            keyword: 'page-seo_keywords',
            desc: 'page-seo_description',
            slug: 'page-slug',
            body: 'page-content',
            editor: 'page-content',
            out: 'page-seo-mini'
          }
        }[type];
        if (!config) return;
        const out = document.getElementById(config.out);
        if (!out) return;
        const title = (document.getElementById(config.title)?.value || '').trim();
        const keyword = (document.getElementById(config.keyword)?.value || '').trim().toLowerCase();
        const desc = (document.getElementById(config.desc)?.value || '').trim();
        const slug = (document.getElementById(config.slug)?.value || '').trim().toLowerCase();
        const bodyHtml = config.editor && richEditors[config.editor]
          ? richEditors[config.editor].getData()
          : (document.getElementById(config.body)?.value || '');
        const bodyText = bodyHtml.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        const bodyLower = bodyText.toLowerCase();
        const words = bodyText ? bodyText.split(/\s+/).length : 0;
        const checks = [];
        let passed = 0;
        if (!keyword) {
          out.innerHTML = '<strong style="color:#6b7280;">SEO:</strong> Add a focus keyphrase to start live checks.';
          return;
        }
        if (title.toLowerCase().includes(keyword)) {
          checks.push('<span style="color:#15803d;">Title has keyphrase</span>');
          passed++;
        } else {
          checks.push('<span style="color:#b91c1c;">Add keyphrase to SEO title</span>');
        }
        if (desc.toLowerCase().includes(keyword)) {
          checks.push('<span style="color:#15803d;">Description has keyphrase</span>');
          passed++;
        } else {
          checks.push('<span style="color:#b91c1c;">Add keyphrase to meta description</span>');
        }
        const keywordSlug = keyword.replace(/\s+/g, '-');
        if (slug.includes(keywordSlug) || slug.includes(keyword)) {
          checks.push('<span style="color:#15803d;">Slug matches keyphrase</span>');
          passed++;
        } else {
          checks.push('<span style="color:#b45309;">Use keyphrase in slug</span>');
        }
        if (bodyLower.includes(keyword)) {
          checks.push('<span style="color:#15803d;">Content uses keyphrase</span>');
          passed++;
        } else {
          checks.push('<span style="color:#b91c1c;">Use keyphrase in content</span>');
        }
        if (words >= 300) {
          checks.push('<span style="color:#15803d;">Content length ' + words + ' words</span>');
          passed++;
        } else {
          checks.push('<span style="color:#b45309;">Content is short: ' + words + ' words</span>');
        }
        if (desc.length >= 120 && desc.length <= 160) {
          checks.push('<span style="color:#15803d;">Meta length is good</span>');
          passed++;
        } else {
          checks.push('<span style="color:#b45309;">Meta length should be 120-160 chars</span>');
        }
        const score = Math.round((passed / 6) * 100);
        const color = score >= 80 ? '#15803d' : (score >= 50 ? '#b45309' : '#b91c1c');
        out.innerHTML = '<strong style="color:' + color + ';">SEO Score ' + score + '%</strong><div style="display:grid; gap:0.25rem; margin-top:0.45rem;">' + checks.map(c => '<div>' + c + '</div>').join('') + '</div>';
      }

      // Live Yoast SEO script (copied in full from lines 1500-1637)
      function triggerSEO() {
        const keyword = document.getElementById('edit-seo_keywords').value.trim();
        const seoTitle = document.getElementById('edit-seo_title').value.trim();
        const slug = document.getElementById('edit-edit-slug') ? document.getElementById('edit-edit-slug').value.trim() : document.getElementById('edit-slug').value.trim();
        const desc = document.getElementById('edit-seo_description').value.trim();
        
        let content = "";
        if (richEditors['edit-overview']) {
          content = richEditors['edit-overview'].getData();
        }
        
        const textContent = content.replace(/<[^>]*>/g, '').toLowerCase();
        const cleanTitle = seoTitle.toLowerCase();
        const cleanSlug = slug.toLowerCase();
        const cleanDesc = desc.toLowerCase();
        
        let checks = [];
        let passedCount = 0;
        
        if (!keyword) {
          document.getElementById('seo-checklist').innerHTML = '<div>Please enter a focus keyphrase to perform live Yoast-style diagnostics.</div>';
          document.getElementById('seo-score-badge').style.background = '#6b7280';
          document.getElementById('seo-score-badge').textContent = 'Gray (No Keyphrase)';
          
          document.getElementById('serp-title').textContent = seoTitle || 'Please enter a title...';
          document.getElementById('serp-url').textContent = 'http://127.0.0.1:8080/trekking/' + (slug || 'slug');
          document.getElementById('serp-desc').textContent = desc || 'Please enter a meta description to see the search snippet preview here...';
          return;
        }
        
        // 1. Keyphrase in Title
        if (cleanTitle.includes(keyword.toLowerCase())) {
          checks.push('🟢 Keyphrase in Title: <strong>Yes</strong>');
          passedCount++;
        } else {
          checks.push('🔴 Keyphrase in Title: <strong>No</strong> (Add it to your page title)');
        }
        
        // 2. Keyphrase in Slug
        const cleanKeywordSlug = keyword.toLowerCase().replace(/\s+/g, '-');
        if (cleanSlug.includes(cleanKeywordSlug) || cleanSlug.includes(keyword.toLowerCase())) {
          checks.push('🟢 Keyphrase in URL Slug: <strong>Yes</strong>');
          passedCount++;
        } else {
          checks.push('🔴 Keyphrase in URL Slug: <strong>No</strong> (Format keyphrase inside slug)');
        }
        
        // 3. Keyphrase in Meta Description
        if (cleanDesc.includes(keyword.toLowerCase())) {
          checks.push('🟢 Keyphrase in Meta Description: <strong>Yes</strong>');
          passedCount++;
        } else {
          checks.push('🔴 Keyphrase in Meta Description: <strong>No</strong>');
        }
        
        // 4. Content Word Count
        const wordCount = textContent.split(/\s+/).filter(w => w.length > 0).length;
        if (wordCount >= 300) {
          checks.push('🟢 Content length: <strong>' + wordCount + ' words</strong> (Good)');
          passedCount++;
        } else {
          checks.push('🟠 Content length: <strong>' + wordCount + ' words</strong> (Short, consider writing over 300 words)');
        }
        
        // 5. Meta Description length check
        if (desc.length >= 120 && desc.length <= 160) {
          checks.push('🟢 Meta Description length: <strong>Optimal (' + desc.length + ' chars)</strong>');
          passedCount++;
        } else if (desc.length > 160) {
          checks.push('🟠 Meta Description length: <strong>Too Long (' + desc.length + ' chars)</strong> (Limit to 160)');
        } else {
          checks.push('🔴 Meta Description length: <strong>Too Short (' + desc.length + ' chars)</strong> (Make it between 120-160)');
        }
        
        // 6. Paragraph length
        const paragraphs = content.split(/<\/p>/i);
        let longPara = false;
        paragraphs.forEach(p => {
          const pWordCount = p.replace(/<[^>]*>/g, '').split(/\s+/).filter(w => w.length > 0).length;
          if (pWordCount > 150) longPara = true;
        });
        if (!longPara) {
          checks.push('🟢 Paragraph readability: <strong>Perfect</strong>');
          passedCount++;
        } else {
          checks.push('🟠 Paragraph readability: <strong>Paragraphs too long</strong> (Break them up)');
        }
        
        // 7. Inclusive language check
        const nonInclusive = ['blacklist', 'whitelist', 'master', 'slave', 'mankind'];
        let foundTerms = [];
        nonInclusive.forEach(term => {
          if (textContent.includes(term)) foundTerms.push(term);
        });
        if (foundTerms.length === 0) {
          checks.push('🟢 Inclusive language: <strong>Pass</strong>');
          passedCount++;
        } else {
          checks.push('🔴 Inclusive language audit: <strong>Non-inclusive terms found</strong> (' + foundTerms.join(', ') + ')');
        }

        // Interlinking recommendations
        let linksFound = [];
        const linkMap = {
          'everest': '<a href="/trekking/everest-base-camp-trek">Everest Base Camp</a>',
          'annapurna': '<a href="/trekking/annapurna-circuit-trek">Annapurna Circuit</a>',
          'langtang': '<a href="/trekking/langtang-valley-trek">Langtang Valley</a>',
          'climbing': '<a href="/peak-climbing">Peak Climbing</a>'
        };
        Object.keys(linkMap).forEach(key => {
          if (textContent.includes(key) && !content.includes('href')) {
            linksFound.push(linkMap[key]);
          }
        });
        if (linksFound.length > 0) {
          checks.push('<div style="margin-top:10px; border-top:1px dashed var(--border-color); padding-top:8px;"><strong>Interlinking Suggestions:</strong> Link text to ' + linksFound.join(', ') + '</div>');
        }

        // Calculate score
        const scorePercent = Math.round((passedCount / 7) * 100);
        let badgeColor = 'var(--color-danger)';
        let scoreName = 'Red';
        
        if (scorePercent >= 80) {
          badgeColor = 'var(--color-success)';
          scoreName = 'Green';
        } else if (scorePercent >= 50) {
          badgeColor = 'var(--color-warning)';
          scoreName = 'Orange';
        }
        
        document.getElementById('seo-score-badge').style.background = badgeColor;
        document.getElementById('seo-score-badge').textContent = scoreName + ' (' + scorePercent + '%)';
        document.getElementById('seo-checklist').innerHTML = checks.map(c => '<div style="margin-bottom:2px;">' + c + '</div>').join('');
        
        // Update SERP preview
        document.getElementById('serp-title').textContent = seoTitle || 'Please enter a title...';
        document.getElementById('serp-url').textContent = 'http://127.0.0.1:8080/trekking/' + (slug || 'slug');
        document.getElementById('serp-desc').textContent = desc || 'Please enter a meta description to see the search snippet preview here...';
      }
    </script>

  <?php endif; ?>

  <div class="cookie-consent-banner" id="cookie-consent-banner" role="dialog" aria-live="polite" aria-label="Cookie notice">
    <div>
      <strong>Accept cookies</strong>
      <p>We use essential cookies to keep the admin panel and visitor site working correctly in the same browser.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" id="cookie-consent-accept">Accept</button>
  </div>
  <script>
    (function() {
      var banner = document.getElementById('cookie-consent-banner');
      var accept = document.getElementById('cookie-consent-accept');
      var key = 'topmountain_cookie_consent';
      if (!banner || !accept) return;
      function hasConsent() {
        return localStorage.getItem(key) === 'accepted' || document.cookie.indexOf('tmt_cookie_consent=accepted') !== -1;
      }
      function saveConsent() {
        localStorage.setItem(key, 'accepted');
        document.cookie = 'tmt_cookie_consent=accepted; Max-Age=31536000; Path=/; SameSite=Lax';
        banner.classList.remove('visible');
      }
      if (!hasConsent()) {
        banner.classList.add('visible');
      }
      accept.addEventListener('click', saveConsent);
    })();
  </script>

</body>
</html>
