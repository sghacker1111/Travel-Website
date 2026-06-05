<?php
/**
 * Server-side AI Chat Responder
 * Indexes packages, blogs, pages, and settings to formulate structured answers.
 */
header('Content-Type: application/json');

$query = isset($_POST['query']) ? trim($_POST['query']) : '';
if (empty($query)) {
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
}

if (empty($query)) {
    echo json_encode(['reply' => "Hello! How can I help you today? Ask me about package prices, permits, itineraries, our team, or how to contact us."]);
    exit;
}

// Load databases
$packages_path = __DIR__ . '/data/packages.json';
$blogs_path = __DIR__ . '/data/blogs.json';
$pages_path = __DIR__ . '/data/pages.json';
$settings_path = __DIR__ . '/data/settings.json';

$packages = file_exists($packages_path) ? json_decode(file_get_contents($packages_path), true) : [];
$blogs = file_exists($blogs_path) ? json_decode(file_get_contents($blogs_path), true) : [];
$pages = file_exists($pages_path) ? json_decode(file_get_contents($pages_path), true) : [];
$settings = file_exists($settings_path) ? json_decode(file_get_contents($settings_path), true) : [];

if (!is_array($packages)) $packages = [];
if (!is_array($blogs)) $blogs = [];
if (!is_array($pages)) $pages = [];
if (!is_array($settings)) $settings = [];

$site_name = $settings['company_name'] ?? 'Top Mountain Treks';
$site_whatsapp = $settings['whatsapp_number'] ?? '+977 985-1123456';

$cleanQuery = strtolower($query);

// 1. Check for Contact/Office Location info
if (preg_match('/(contact|office|phone|email|location|address|whatsapp|emergency|hotline|number|map|call)/i', $cleanQuery)) {
    $reply = "You can contact **{$site_name}** via the following details:<br>"
           . "• **Address:** Satghumti Marg, Thamel-26, Kathmandu, Nepal<br>"
           . "• **WhatsApp Helpline:** {$site_whatsapp}<br>"
           . "• **Landline Phone:** +977 1-4700720<br>"
           . "• **24/7 Emergency Hotline:** +977 98511-23456<br>"
           . "• **Email:** info@topmountaintreks.com<br><br>"
           . "Feel free to check our office location map on our <a href=\"/contact\" style=\"color:var(--color-secondary); font-weight:700;\">Contact Us Page</a>.";
    echo json_encode(['reply' => $reply]);
    exit;
}

// 2. Check for Team / Sherpa info
if (preg_match('/(team|guide|staff|sherpa|expert|personnel|crew|member|pasang|ang)/i', $cleanQuery)) {
    $team_page = null;
    foreach ($pages as $p) {
        if ($p['slug'] === 'our-team') {
            $team_page = $p;
            break;
        }
    }
    if ($team_page) {
        $reply = "Our team is guided by experienced mountaineers and Sherpas. "
               . strip_tags($team_page['content']) . "<br><br>"
               . "Read more on our <a href=\"/page/our-team\" style=\"color:var(--color-secondary); font-weight:700;\">Our Team Page</a>.";
        echo json_encode(['reply' => $reply]);
        exit;
    }
}

// 3. Check for Legal registration / certifications / licenses
if (preg_match('/(legal|document|license|registration|authorized|permit|pan|vat|tax|ministry|taan|nma)/i', $cleanQuery)) {
    $legal_page = null;
    foreach ($pages as $p) {
        if ($p['slug'] === 'legal-documents') {
            $legal_page = $p;
            break;
        }
    }
    if ($legal_page) {
        $reply = "Yes, we are a fully authorized travel operator. Here is a summary of our registrations:<br>"
               . strip_tags($legal_page['content'], '<table><tr><th><td>') . "<br>"
               . "You can view official certificates on our <a href=\"/page/legal-documents\" style=\"color:var(--color-secondary); font-weight:700;\">Legal Documents Page</a>.";
        echo json_encode(['reply' => $reply]);
        exit;
    }
}

// 4. Check for Awards / certificates of excellence
if (preg_match('/(award|achievement|recognition|tripadvisor|excellence|certificate)/i', $cleanQuery)) {
    $awards_page = null;
    foreach ($pages as $p) {
        if ($p['slug'] === 'awards') {
            $awards_page = $p;
            break;
        }
    }
    if ($awards_page) {
        $reply = "We are proud of our accomplishments in safety and sustainable tourism:<br>"
               . strip_tags($awards_page['content']) . "<br><br>"
               . "For more details, check our <a href=\"/page/awards\" style=\"color:var(--color-secondary); font-weight:700;\">Awards Page</a>.";
        echo json_encode(['reply' => $reply]);
        exit;
    }
}

// 5. Check for Terms / Cancellation / Insurance / Policies
if (preg_match('/(term|condition|policy|cancellation|refund|insurance|deposit|payment|rescue)/i', $cleanQuery)) {
    $terms_page = null;
    foreach ($pages as $p) {
        if ($p['slug'] === 'terms-and-conditions') {
            $terms_page = $p;
            break;
        }
    }
    if ($terms_page) {
        $reply = "Here is a summary of our booking and cancellation policies:<br>"
               . strip_tags($terms_page['content']) . "<br><br>"
               . "Read the full terms on our <a href=\"/page/terms-and-conditions\" style=\"color:var(--color-secondary); font-weight:700;\">Terms & Conditions Page</a>.";
        echo json_encode(['reply' => $reply]);
        exit;
    }
}

// 6. Check for static pages (about us, why travel, etc.)
foreach ($pages as $p) {
    $title = strtolower($p['title']);
    $slug_clean = str_replace('-', ' ', $p['slug']);
    if (strpos($cleanQuery, $slug_clean) !== false || strpos($cleanQuery, strtolower($p['title'])) !== false) {
        $reply = "### " . $p['title'] . "<br>"
               . strip_tags(substr($p['content'], 0, 400)) . "...<br><br>"
               . "<a href=\"/page/{$p['slug']}\" style=\"color:var(--color-secondary); font-weight:700;\">Read Full Article →</a>";
        echo json_encode(['reply' => $reply]);
        exit;
    }
}

// 7. Check for Trekking regions query (Everest, Annapurna, Langtang, Manaslu, Mustang)
$regions = ['everest', 'annapurna', 'langtang', 'manaslu', 'mustang'];
foreach ($regions as $r) {
    if (strpos($cleanQuery, $r) !== false && !preg_match('/(base camp|circuit|valley|trek|climb|tour)/i', $cleanQuery)) {
        $matching = [];
        foreach ($packages as $pkg) {
            if (strpos(strtolower($pkg['region']), $r) !== false || strpos(strtolower($pkg['title']), $r) !== false) {
                $matching[] = "• <a href=\"/trekking/{$pkg['slug']}\" style=\"color:var(--color-primary); font-weight:700;\">{$pkg['title']}</a> - **\${$pkg['priceUSD']} USD** ({$pkg['duration']} Days)";
            }
        }
        if (!empty($matching)) {
            $reply = "We operate the following itineraries in the **" . ucfirst($r) . " Region**:<br>"
                   . implode("<br>", $matching) . "<br><br>"
                   . "You can view all regional cards on our <a href=\"/destination\" style=\"color:var(--color-secondary); font-weight:700;\">Destinations Hub</a>.";
            echo json_encode(['reply' => $reply]);
            exit;
        }
    }
}

// 8. Match specific trekking package
$matchedPkg = null;
foreach ($packages as $pkg) {
    $title = strtolower($pkg['title']);
    $slug_clean = str_replace('-', ' ', $pkg['slug']);
    if (strpos($cleanQuery, $title) !== false || strpos($cleanQuery, $slug_clean) !== false || strpos($cleanQuery, strtolower($pkg['id'])) !== false) {
        $matchedPkg = $pkg;
        break;
    }
}

// Sub-matching words if no direct match
if (!$matchedPkg) {
    $bestScore = 0;
    foreach ($packages as $pkg) {
        $words = explode(' ', strtolower($pkg['title']));
        $score = 0;
        foreach ($words as $w) {
            if (strlen($w) > 3 && strpos($cleanQuery, $w) !== false) {
                $score++;
            }
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $matchedPkg = $pkg;
        }
    }
}

if ($matchedPkg) {
    if (preg_match('/(price|cost|fee|charge|usd|npr|rate)/i', $cleanQuery)) {
        $reply = "The starting cost for the **{$matchedPkg['title']}** is **\${$matchedPkg['priceUSD']} USD** (approx. NPR " . number_format($matchedPkg['priceNPR']) . ") per person. <br>"
               . "• **What's included:** *{$matchedPkg['priceNote']}*.<br><br>"
               . "For details on inclusions and exclusions, check the <a href=\"/trekking/{$matchedPkg['slug']}#detail-inclusions\" style=\"color:var(--color-secondary); font-weight:700;\">Price Inclusions page</a>.";
        echo json_encode(['reply' => $reply]);
        exit;
    }

    if (preg_match('/(duration|day|long|itinerary|schedule|stop)/i', $cleanQuery)) {
        $itinerary_outline = [];
        foreach (array_slice($matchedPkg['itinerary'], 0, 5) as $day) {
            $itinerary_outline[] = "• **Day {$day['day']}:** " . htmlspecialchars($day['title']);
        }
        $reply = "The **{$matchedPkg['title']}** spans **{$matchedPkg['duration']} Days**.<br>"
               . "Here is the start outline of the itinerary:<br>"
               . implode("<br>", $itinerary_outline) . "<br>"
               . "• ... and " . ($matchedPkg['duration'] - 5) . " more days.<br><br>"
               . "Read the full day-by-day outline on the <a href=\"/trekking/{$matchedPkg['slug']}#detail-itinerary\" style=\"color:var(--color-secondary); font-weight:700;\">Detailed Itinerary section</a>.";
        echo json_encode(['reply' => $reply]);
        exit;
    }

    if (preg_match('/(permit|tims|card|check|fee|nma|entry)/i', $cleanQuery)) {
        $permits = array_map(function($p) { return "• " . htmlspecialchars($p); }, $matchedPkg['requiredPermits']);
        $reply = "To trek **{$matchedPkg['title']}**, you need these official permits:<br>"
               . implode("<br>", $permits) . "<br><br>"
               . "*Note: All conservation entry passes and TIMS registration cards are fully arranged by our guides.*";
        echo json_encode(['reply' => $reply]);
        exit;
    }

    if (preg_match('/(difficult|hard|fit|challenging|strenuous|easy|altitude|elevation)/i', $cleanQuery)) {
        $reply = "The **{$matchedPkg['title']}** reaches a maximum altitude of **{$matchedPkg['maxAltitude']}** and has a difficulty level of **{$matchedPkg['difficulty']}**.<br>"
               . "Acclimatization days are integrated to ensure high safety standards and comfort.";
        echo json_encode(['reply' => $reply]);
        exit;
    }

    // Default package details reply
    $reply = "### {$matchedPkg['title']} ({$matchedPkg['duration']} Days)<br>"
           . "• **Region:** {$matchedPkg['region']}<br>"
           . "• **Starting price:** \${$matchedPkg['priceUSD']} USD<br>"
           . "• **Maximum elevation:** {$matchedPkg['maxAltitude']}<br>"
           . "• **Difficulty level:** {$matchedPkg['difficulty']}<br><br>"
           . "Overview: *" . substr(strip_tags($matchedPkg['overview']), 0, 250) . "...*<br><br>"
           . "View full details and book on the <a href=\"/trekking/{$matchedPkg['slug']}\" style=\"color:var(--color-secondary); font-weight:700;\">Package Details Page</a>.";
    echo json_encode(['reply' => $reply]);
    exit;
}

// 9. Match blogs
$matchedBlog = null;
foreach ($blogs as $b) {
    if (strpos($cleanQuery, strtolower($b['title'])) !== false || strpos($cleanQuery, strtolower($b['slug'])) !== false) {
        $matchedBlog = $b;
        break;
    }
}
if ($matchedBlog) {
    $reply = "### Blog: {$matchedBlog['title']}<br>"
           . substr(strip_tags($matchedBlog['content']), 0, 300) . "...<br><br>"
           . "Read the full article here: <a href=\"/blog/{$matchedBlog['slug']}\" style=\"color:var(--color-secondary); font-weight:700;\">Read Blog Post →</a>";
    echo json_encode(['reply' => $reply]);
    exit;
}

// 10. General search keyword fallback
$matchesFound = [];
foreach ($packages as $pkg) {
    if (strpos(strtolower($pkg['overview']), $cleanQuery) !== false) {
        $matchesFound[] = "• Package: <a href=\"/trekking/{$pkg['slug']}\">{$pkg['title']}</a>";
    }
}
foreach ($blogs as $b) {
    if (strpos(strtolower($b['content']), $cleanQuery) !== false) {
        $matchesFound[] = "• Blog: <a href=\"/blog/{$b['slug']}\">{$b['title']}</a>";
    }
}
foreach ($pages as $p) {
    if (strpos(strtolower($p['content']), $cleanQuery) !== false) {
        $matchesFound[] = "• Page: <a href=\"/page/{$p['slug']}\">{$p['title']}</a>";
    }
}

if (!empty($matchesFound)) {
    $reply = "I found some resources related to your query:<br>"
           . implode("<br>", array_slice($matchesFound, 0, 5)) . "<br><br>"
           . "Please click the links to inspect detailed information.";
    echo json_encode(['reply' => $reply]);
    exit;
}

// 11. Final fallback
$reply = "I'm sorry, I couldn't find a direct answer. Try asking about:<br>"
       . "• *What is the price of Everest Base Camp Trek?*<br>"
       . "• *What permits do I need for Manaslu Circuit?*<br>"
       . "• *How can I contact your office in Kathmandu?*<br>"
       . "• *Tell me about your guide team*";

echo json_encode(['reply' => $reply]);
