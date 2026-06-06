<?php
// Load package details from data/packages.json based on slug parameter
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$packages_path = 'data/packages.json';
$packages = [];
$current_pkg = null;

if (file_exists($packages_path)) {
    $packages = json_decode(file_get_contents($packages_path), true);
    if (is_array($packages)) {
        foreach ($packages as $pkg) {
            if ($pkg['slug'] === $slug) {
                $current_pkg = $pkg;
                break;
            }
        }
    }
}

// Redirect to home if package not found
if (!$current_pkg) {
    header("Location: /index.php");
    exit;
}

// Handle Review Submission
$review_status = "";
$review_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rev_name = isset($_POST['rev_name']) ? trim($_POST['rev_name']) : '';
    $rev_rating = isset($_POST['rev_rating']) ? max(1, min(5, (int)$_POST['rev_rating'])) : 5;
    $rev_comment = isset($_POST['rev_comment']) ? trim($_POST['rev_comment']) : '';

    if (!empty($rev_name) && !empty($rev_comment)) {
        // Load master list, update
        $master_packages = json_decode(file_get_contents($packages_path), true);
        if (is_array($master_packages)) {
            foreach ($master_packages as &$mpkg) {
                if ($mpkg['slug'] === $slug) {
                    if (!isset($mpkg['reviews']) || !is_array($mpkg['reviews'])) {
                         $mpkg['reviews'] = [];
                    }
                    $mpkg['reviews'][] = [
                        'name' => $rev_name,
                        'rating' => $rev_rating,
                        'comment' => $rev_comment,
                        'date' => date('Y-m-d'),
                        'approved' => false // Needs moderation
                    ];
                    $current_pkg['reviews'] = $mpkg['reviews'];
                    break;
                }
            }
            file_put_contents($packages_path, json_encode($master_packages, JSON_PRETTY_PRINT));
            $review_status = "success";
            $review_msg = "Thank you! Your traveler review has been submitted and is pending guide approval.";
        }
    } else {
        $review_status = "error";
        $review_msg = "Please complete all fields to submit your review.";
    }
}

// Helper to parse location from day title
function getDayLocationFallback($title) {
    if (preg_match('/(?:to|reach|at)\s+([A-Z][a-zA-Z0-9\s-]+)/', $title, $matches)) {
        $loc = trim($matches[1]);
        // Strip trailing words like 'and', 'with'
        $loc = preg_replace('/\s+(?:and|with|for|campsite|village|pass).*$/i', '', $loc);
        return $loc;
    }
    $words = explode(' ', $title);
    if (count($words) >= 2) {
        return $words[count($words)-2] . ' ' . $words[count($words)-1];
    }
    return $title;
}

function renderPackagePointList($items, $type = 'include') {
    $is_exclude = $type === 'exclude';
    $class = $is_exclude ? 'excludes' : 'includes';
    $icon = $is_exclude ? '&#10005;' : '&#10003;';
    $points = [];

    if (is_array($items)) {
        foreach ($items as $item) {
            $item = trim(strip_tags((string)$item));
            if ($item !== '') {
                $points[] = $item;
            }
        }
    } else {
        $html = trim((string)$items);
        if ($html !== '' && preg_match('/<li[\s>]/i', $html)) {
            echo '<div class="package-rich-list ' . $class . '">' . $html . '</div>';
            return;
        }

        $normalized = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $normalized = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n", $normalized);
        $normalized = preg_replace('/<\/div>\s*<div[^>]*>/i', "\n", $normalized);
        $plain = trim(strip_tags($normalized));
        $parts = preg_split('/\r\n|\r|\n/', $plain);
        foreach ($parts as $part) {
            $part = trim($part, " \t\n\r\0\x0B-*+•");
            if ($part !== '') {
                $points[] = $part;
            }
        }
    }

    if (empty($points)) {
        echo '<p style="color:var(--text-muted); font-size:0.92rem;">Details will be updated soon.</p>';
        return;
    }

    echo '<ul class="package-point-list ' . $class . '">';
    foreach ($points as $point) {
        echo '<li><span class="package-point-icon" aria-hidden="true">' . $icon . '</span><span>' . htmlspecialchars($point) . '</span></li>';
    }
    echo '</ul>';
}

// Set package-specific SEO parameters
$page_title = isset($current_pkg['seo_title']) && !empty($current_pkg['seo_title']) ? $current_pkg['seo_title'] : $current_pkg['title'] . " | Top Mountain Treks Pvt. Ltd.";
$page_desc = isset($current_pkg['seo_description']) && !empty($current_pkg['seo_description']) ? $current_pkg['seo_description'] : strip_tags($current_pkg['overview']);
$image_alt = isset($current_pkg['image_alt']) && !empty($current_pkg['image_alt']) ? $current_pkg['image_alt'] : $current_pkg['title'] . " landscape photo";

include 'includes/header.php';

$prefix = 'trek';
if ($current_pkg['category'] === 'tour') $prefix = 'tour';
elseif ($current_pkg['category'] === 'climbing') $prefix = 'climb';

$gallery_images = isset($current_pkg['gallery']) && is_array($current_pkg['gallery']) ? $current_pkg['gallery'] : [];
$hero_images = array_values(array_unique(array_filter(array_merge([$current_pkg['image']], $gallery_images))));
$offer_badge = trim($current_pkg['offer_badge'] ?? '');
$group_offer_text = trim($current_pkg['group_offer_text'] ?? 'We offer group price');
$offer_end_date = trim($current_pkg['offer_end_date'] ?? '');
$old_price_usd = isset($current_pkg['offer_old_price_usd']) ? (int)$current_pkg['offer_old_price_usd'] : 0;
$review_count = 0;
if (isset($current_pkg['reviews']) && is_array($current_pkg['reviews'])) {
    foreach ($current_pkg['reviews'] as $rev) {
        if (!empty($rev['approved'])) {
            $review_count++;
        }
    }
}
?>

  <!-- Header Hero Banner -->
  <section id="package-hero" class="hero" data-images='<?php echo htmlspecialchars(json_encode($hero_images), ENT_QUOTES); ?>' style="background-image: url('/<?php echo htmlspecialchars($current_pkg['image']); ?>'); padding: 6rem 0 4rem; min-height: 50vh; background-size:cover; background-position:center; position:relative; display:flex; align-items:center; background-attachment: fixed; transition: background-image 0.6s ease;">
    <div style="position:absolute; inset:0; background:rgba(15,23,42,0.5);"></div>
    <div class="container" style="position:relative; z-index:10; text-align: var(--<?php echo $prefix; ?>-align, left); max-width: 900px; <?php if (($theme_settings[$prefix . '_align'] ?? 'left') === 'left') { echo 'margin: 0; padding-left: 2rem;'; } else { echo 'margin: 0 auto;'; } ?>">
      <span class="card-badge" style="position:static; display:inline-block; margin-bottom:1rem; background: rgba(255, 122, 0, 0.25); border-color: rgba(255, 122, 0, 0.4); color: white;"><?php echo htmlspecialchars($current_pkg['difficulty']); ?> Category</span>
      <h1 style="color: var(--<?php echo $prefix; ?>-title-color, white); font-size: var(--<?php echo $prefix; ?>-title-size, 3rem); margin-top: 1rem; text-shadow: 0 2px 6px rgba(0,0,0,0.6); font-family:var(--font-heading); font-weight:800; line-height:1.2;"><?php echo htmlspecialchars($current_pkg['title']); ?></h1>
      <p style="color: var(--<?php echo $prefix; ?>-desc-color, rgba(255,255,255,0.9)); font-size: var(--<?php echo $prefix; ?>-desc-size, 1.15rem); text-shadow: 0 1px 3px rgba(0,0,0,0.5); font-weight:600;"><?php echo htmlspecialchars($current_pkg['region']); ?> Region</p>
    </div>
  </section>

  <!-- Detail Content Section -->
  <section style="padding: 4rem 0;">
    <div class="container">
      
      <!-- Key Trip Facts Ribbon -->
      <div class="detail-facts-ribbon" style="display:grid; grid-template-columns: repeat(4, 1fr); gap:1.5rem; background:var(--bg-light); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem; margin-bottom:3rem; text-align:center;">
        <div class="fact-item">
          <span class="fact-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Starting Price</span>
          <span class="fact-val" style="color:var(--color-secondary); font-size:1.6rem; font-weight:800; display:block; margin-top:0.25rem;">$<?php echo number_format($current_pkg['priceUSD']); ?> <span style="font-size:0.8rem; font-weight:600; color:var(--text-muted);">USD</span></span>
        </div>
        <div class="fact-item">
          <span class="fact-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Duration</span>
          <span class="fact-val" style="font-size:1.6rem; font-weight:800; display:block; margin-top:0.25rem; color:var(--color-primary);"><?php echo htmlspecialchars($current_pkg['duration']); ?> Days</span>
        </div>
        <div class="fact-item">
          <span class="fact-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Max Altitude</span>
          <span class="fact-val" style="font-size:1.3rem; font-weight:800; display:block; margin-top:0.45rem; color:var(--color-primary); line-height:1.2;"><?php echo htmlspecialchars($current_pkg['maxAltitude']); ?></span>
        </div>
        <div class="fact-item">
          <span class="fact-label" style="display:block; font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Best Season</span>
          <span class="fact-val" style="font-size:1rem; font-weight:700; display:block; margin-top:0.45rem; color:var(--color-primary); line-height:1.2; font-family:var(--font-body);"><?php echo htmlspecialchars($current_pkg['bestSeason']); ?></span>
        </div>
      </div>

      <!-- Main Columns layout -->
      <div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 3.5rem;" id="package-grid">
        
        <div class="package-details-main">
          
          <!-- Tab Buttons navigation -->
          <div class="tabs" style="margin-bottom: 2rem; display:flex; gap:0.5rem; flex-wrap:wrap; border-bottom:1.5px solid var(--border-color); padding-bottom:2px;">
            <span class="tab active" data-tab="detail-overview" style="cursor:pointer; padding:0.75rem 1.25rem; font-family:var(--font-heading); font-weight:800; font-size:0.92rem; border-radius:var(--radius-sm); transition:all 0.2s;">Overview</span>
            <span class="tab" data-tab="detail-itinerary" style="cursor:pointer; padding:0.75rem 1.25rem; font-family:var(--font-heading); font-weight:800; font-size:0.92rem; border-radius:var(--radius-sm); transition:all 0.2s;">Itinerary</span>
            <span class="tab" data-tab="detail-route" style="cursor:pointer; padding:0.75rem 1.25rem; font-family:var(--font-heading); font-weight:800; font-size:0.92rem; border-radius:var(--radius-sm); transition:all 0.2s;">Route Map</span>
            <span class="tab" data-tab="detail-inclusions" style="cursor:pointer; padding:0.75rem 1.25rem; font-family:var(--font-heading); font-weight:800; font-size:0.92rem; border-radius:var(--radius-sm); transition:all 0.2s;">Inclusions</span>
            <span class="tab" data-tab="detail-faqs" style="cursor:pointer; padding:0.75rem 1.25rem; font-family:var(--font-heading); font-weight:800; font-size:0.92rem; border-radius:var(--radius-sm); transition:all 0.2s;">FAQs</span>
            <span class="tab" data-tab="detail-gallery" style="cursor:pointer; padding:0.75rem 1.25rem; font-family:var(--font-heading); font-weight:800; font-size:0.92rem; border-radius:var(--radius-sm); transition:all 0.2s;">Photo Gallery</span>
            <span class="tab" data-tab="detail-reviews" style="cursor:pointer; padding:0.75rem 1.25rem; font-family:var(--font-heading); font-weight:800; font-size:0.92rem; border-radius:var(--radius-sm); transition:all 0.2s;">Reviews</span>
          </div>

          <div class="package-sticky-shortcuts" id="package-sticky-shortcuts" aria-label="Package section shortcuts">
            <button type="button" class="package-shortcut-button active" data-tab="detail-overview">Overview</button>
            <button type="button" class="package-shortcut-button" data-tab="detail-itinerary">Itinerary</button>
            <button type="button" class="package-shortcut-button" data-tab="detail-route">Route Map</button>
            <button type="button" class="package-shortcut-button" data-tab="detail-inclusions">Inclusions</button>
            <button type="button" class="package-shortcut-button" data-tab="detail-faqs">FAQs</button>
            <button type="button" class="package-shortcut-button" data-tab="detail-gallery">Photo Gallery</button>
            <button type="button" class="package-shortcut-button" data-tab="detail-reviews">Reviews</button>
          </div>

          <!-- Tab Pane: Overview -->
          <div class="tab-pane active" id="detail-overview">
            <h2 style="font-family: var(--font-heading); font-size:1.75rem; color:var(--color-primary); margin-bottom:1rem; font-weight:800;">Trip Overview</h2>
            <?php if (!empty($current_pkg['short_overview'])): ?>
              <div style="font-size:1.08rem; line-height:1.75; color:var(--text-dark); background:#f8fafc; border-left:4px solid var(--color-secondary); padding:1rem 1.25rem; border-radius:var(--radius-sm); margin-bottom:1.25rem;">
                <?php echo nl2br(htmlspecialchars($current_pkg['short_overview'])); ?>
              </div>
            <?php endif; ?>
            <div style="color:var(--text-medium); line-height:1.8; font-size:1.02rem; margin-bottom:2.5rem;"><?php echo $current_pkg['overview']; ?></div>

            <h3 style="font-family: var(--font-heading); font-size:1.4rem; color:var(--color-primary); margin-bottom:1.25rem; font-weight:800;">Trip Highlights</h3>
            <?php if (is_array($current_pkg['highlights'])): ?>
              <ul style="display: grid; gap: 0.85rem; list-style: none; margin-bottom: 3rem; padding:0;">
                <?php foreach ($current_pkg['highlights'] as $highlight): ?>
                  <li style="display: flex; align-items: flex-start; gap: 0.75rem; font-size:0.98rem; color:var(--text-medium);">
                    <span style="color:var(--color-secondary); font-weight:bold; margin-top:1px;">✔</span>
                    <span><?php echo htmlspecialchars($highlight); ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <div class="rich-highlights-content" style="margin-bottom: 3rem; line-height:1.8;">
                <?php echo $current_pkg['highlights']; ?>
              </div>
            <?php endif; ?>

            <h3 style="font-family: var(--font-heading); font-size:1.4rem; color:var(--color-primary); margin-bottom:1.25rem; font-weight:800;">Quick Route Specifications</h3>
            <div style="background-color: var(--bg-light); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">
              <div><strong>Start Point:</strong> <?php echo htmlspecialchars($current_pkg['startPoint']); ?></div>
              <div><strong>End Point:</strong> <?php echo htmlspecialchars($current_pkg['endPoint']); ?></div>
              <div><strong>Accommodation:</strong> <?php echo $current_pkg['accommodation']; ?></div>
              <div><strong>Meals:</strong> <?php echo $current_pkg['meals']; ?></div>
              <div><strong>Group Size:</strong> <?php echo htmlspecialchars($current_pkg['groupSize']); ?></div>
              <div><strong>Permit Check:</strong> <?php echo is_array($current_pkg['requiredPermits']) ? count($current_pkg['requiredPermits']) : (trim(strip_tags((string)$current_pkg['requiredPermits'])) !== '' ? 1 : 0); ?> required</div>
            </div>

            <?php if (!empty($current_pkg['tripFacts']) && is_array($current_pkg['tripFacts'])): ?>
              <h3 style="font-family: var(--font-heading); font-size:1.4rem; color:var(--color-primary); margin:2.5rem 0 1.25rem; font-weight:800;">Detailed Trip Facts</h3>
              <div style="overflow-x:auto; border:1px solid var(--border-color); border-radius:var(--radius-md); margin-bottom:2.5rem;">
                <table style="width:100%; border-collapse:collapse; background:white;">
                  <tbody>
                    <?php foreach ($current_pkg['tripFacts'] as $fact): ?>
                      <tr>
                        <th style="text-align:left; width:38%; padding:0.85rem 1rem; background:#f8fafc; border-bottom:1px solid var(--border-color); color:var(--color-primary); font-weight:800;"><?php echo htmlspecialchars($fact['label'] ?? ''); ?></th>
                        <td style="padding:0.85rem 1rem; border-bottom:1px solid var(--border-color); color:var(--text-medium);"><?php echo htmlspecialchars($fact['value'] ?? ''); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

            <?php if (!empty($current_pkg['detailSections']) && is_array($current_pkg['detailSections'])): ?>
              <div style="display:grid; gap:1.5rem; margin-top:2rem;">
                <?php foreach ($current_pkg['detailSections'] as $section): ?>
                  <article style="border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.5rem; background:white;">
                    <h3 style="font-family:var(--font-heading); color:var(--color-primary); font-size:1.25rem; margin:0 0 0.85rem; font-weight:800;"><?php echo htmlspecialchars($section['title'] ?? ''); ?></h3>
                    <div style="line-height:1.75; color:var(--text-medium);"><?php echo nl2br($section['content'] ?? ''); ?></div>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($current_pkg['packingList']) && is_array($current_pkg['packingList'])): ?>
              <h3 style="font-family: var(--font-heading); font-size:1.4rem; color:var(--color-primary); margin:2.5rem 0 1.25rem; font-weight:800;">Recommended Packing List</h3>
              <div style="display:flex; flex-wrap:wrap; gap:0.6rem; margin-bottom:2rem;">
                <?php foreach ($current_pkg['packingList'] as $item): ?>
                  <span class="card-badge" style="position:static; background:#f8fafc; border:1px solid var(--border-color); color:var(--text-dark);"><?php echo htmlspecialchars($item); ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($current_pkg['tags']) && is_array($current_pkg['tags'])): ?>
              <div style="display:flex; flex-wrap:wrap; gap:0.55rem; margin:1.5rem 0;">
                <?php foreach ($current_pkg['tags'] as $tag): ?>
                  <span style="font-size:0.78rem; font-weight:800; color:var(--color-primary); background:#eff6ff; border:1px solid #bfdbfe; border-radius:999px; padding:0.35rem 0.65rem;"><?php echo htmlspecialchars($tag); ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($current_pkg['cta_text'])): ?>
              <div style="margin-top:2.5rem; background:var(--color-primary); color:white; padding:1.5rem; border-radius:var(--radius-md); display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                <div style="line-height:1.6; font-weight:700;"><?php echo nl2br(htmlspecialchars($current_pkg['cta_text'])); ?></div>
                <a href="/contact?package=<?php echo urlencode($current_pkg['title']); ?>" class="btn btn-primary" style="background:var(--color-secondary); border-color:var(--color-secondary); color:white;">Book / Ask Price</a>
              </div>
            <?php endif; ?>
          </div>

          <!-- Tab Pane: Itinerary -->
          <div class="tab-pane" id="detail-itinerary">
            <h2 style="font-family: var(--font-heading); font-size:1.75rem; color:var(--color-primary); margin-bottom:1rem; font-weight:800;">Outline Itinerary</h2>
            <ul style="list-style:none; padding:0; margin-bottom:2.5rem; display:grid; gap:0.5rem; background:var(--bg-light); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.5rem;">
              <?php foreach ($current_pkg['itinerary'] as $item): ?>
                <li style="display:flex; align-items:baseline; gap:0.75rem; font-size:0.95rem; color:var(--text-dark);">
                  <span style="font-weight:800; color:var(--color-secondary); min-width:60px;">Day <?php echo $item['day']; ?>:</span>
                  <span><?php echo htmlspecialchars($item['title']); ?></span>
                </li>
              <?php endforeach; ?>
            </ul>

            <div class="itinerary-toolbar">
              <h2 style="font-family: var(--font-heading); font-size:1.75rem; color:var(--color-primary); margin:0; font-weight:800;">Day-by-Day Detailed Itinerary</h2>
              <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                <label style="display:flex; align-items:center; gap:0.45rem; font-size:0.88rem; font-weight:700; color:var(--text-medium);">
                  <input type="checkbox" id="itinerary-expand-mode" checked>
                  Allow multiple open
                </label>
                <button type="button" class="btn btn-outline btn-sm" id="expand-all-itinerary">Expand All</button>
              </div>
            </div>
            
            <div class="accordion-wrapper">
              <?php foreach ($current_pkg['itinerary'] as $idx => $item): ?>
                <div class="acc-item <?php echo $idx === 0 ? 'active' : ''; ?>">
                  <div class="acc-header">
                    <h3>
                      <span class="acc-day-tag">Day <?php echo $item['day']; ?></span>
                      <span><?php echo htmlspecialchars($item['title']); ?></span>
                    </h3>
                    <svg class="acc-icon" viewBox="0 0 20 20" fill="currentColor" style="width:18px; height:18px;">
                      <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                  <div class="acc-content" style="<?php echo $idx === 0 ? 'max-height: none;' : 'max-height: 0px;'; ?>">
                    <div class="acc-content-inner">
                      <div><?php echo $item['desc']; ?></div>
                      
                      <div style="font-size:0.85rem; color:var(--text-muted); margin-top:0.75rem; display:flex; gap:2rem; border-top:1px dashed var(--border-color); padding-top:0.5rem; flex-wrap:wrap;">
                        <span>🏨 <strong>Accommodation:</strong> <?php echo $item['accommodation']; ?></span>
                        <span>🍽️ <strong>Meals:</strong> <?php echo $item['meals']; ?></span>
                        <?php if (!empty($item['location'])): ?>
                          <span>📍 <strong>Reached Map Point:</strong> <?php echo htmlspecialchars($item['location']); ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Tab Pane: Interactive Route Map -->
          <div class="tab-pane" id="detail-route">
            <h2 style="font-family: var(--font-heading); font-size:1.75rem; color:var(--color-primary); margin-bottom:1rem; font-weight:800;">Visual Route Mapper</h2>
            <?php if (!empty($current_pkg['route_map_description'])): ?>
              <div style="background:#f8fafc; border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1rem 1.25rem; margin-bottom:1.5rem; color:var(--text-medium); line-height:1.7;">
                <strong style="color:var(--color-primary);">Route:</strong>
                <?php echo nl2br(htmlspecialchars($current_pkg['route_map_description'])); ?>
              </div>
            <?php endif; ?>
            
            <?php if (!empty($current_pkg['route_map'])): ?>
              <!-- Show Static Uploaded Route Map if exists (Req 5) -->
              <div style="border-radius: var(--radius-lg); overflow:hidden; border:1px solid var(--border-color); margin-bottom:2rem; background:#f8fafc; text-align:center; padding:1rem;">
                <img src="/<?php echo htmlspecialchars($current_pkg['route_map']); ?>" alt="Static Route Map for <?php echo htmlspecialchars($current_pkg['title']); ?>" style="max-width:100%; height:auto; border-radius:var(--radius-md);">
              </div>
            <?php endif; ?>

            <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">Below is the interactive map showing the trail route and key stops. You can zoom in and click on markers to see daily details.</p>
            
            <!-- Leaflet JS & CSS -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
            
            <div id="leaflet-route-map" style="height: 450px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); margin-bottom: 2rem; z-index: 1;"></div>
            
            <script>
              var map = null;
              var polyline = null;
              
              // Helper to parse location from day title in JS
              function getJsLocationFallback(title) {
                  if (!title) return "";
                  const match = title.match(/(?:to|reach|at)\s+([A-Z][a-zA-Z0-9\s-]+)/i);
                  if (match) {
                      let loc = match[1].trim();
                      loc = loc.replace(/\s+(?:and|with|for|campsite|village|pass).*$/i, '');
                      return loc;
                  }
                  const words = title.split(' ');
                  if (words.length >= 2) {
                      return words[words.length - 2] + ' ' + words[words.length - 1];
                  }
                  return title;
              }

              document.addEventListener('DOMContentLoaded', function() {
                  // Initialize Leaflet map
                  map = L.map('leaflet-route-map', {
                      center: [28.3949, 84.1240],
                      zoom: 7,
                      zoomControl: true
                  });
                  
                  // Google Terrain Map layer (default for high quality mountain visual)
                  const googleTerrain = L.tileLayer('https://mt1.google.com/vt/lyrs=p&x={x}&y={y}&z={z}', {
                      maxZoom: 20,
                      attribution: '© Google Maps'
                  });
                  
                  // Google Satellite Imagery layer
                  const googleSatellite = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
                      maxZoom: 20,
                      attribution: '© Google Maps'
                  });

                  // OpenStreetMap standard road layer
                  const openStreetMap = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                      maxZoom: 19,
                      attribution: '© OpenStreetMap contributors'
                  });

                  // Add default layer
                  googleTerrain.addTo(map);

                  // Add layer switcher control
                  const baseLayers = {
                      "Google Terrain (Detailed)": googleTerrain,
                      "Google Satellite (Imagery)": googleSatellite,
                      "OpenStreetMap": openStreetMap
                  };
                  L.control.layers(baseLayers).addTo(map);

                  const itinerary = <?php echo json_encode($current_pkg['itinerary'] ?? []); ?>;
                  
                  // Expanded geocoding dictionary for Nepal trekking regions
                  const knownLocations = {
                      'Kathmandu': [27.7172, 85.3240],
                      'Pokhara': [28.2096, 83.9856],
                      'Lukla': [27.6865, 86.7285],
                      'Namche': [27.8069, 86.7140],
                      'Everest Base Camp': [28.0026, 86.8526],
                      'Annapurna Base Camp': [28.5300, 83.8780],
                      'Macchapucchre Base Camp': [28.5159, 83.8967],
                      'Ghorepani': [28.4000, 83.6983],
                      'Poon Hill': [28.4026, 83.6881],
                      'Dingboche': [27.8931, 86.8306],
                      'Tengboche': [27.8361, 86.7642],
                      'Gorak Shep': [27.9810, 86.8285],
                      'Kala Patthar': [27.9958, 86.8284],
                      'Syangboche': [27.8183, 86.7119],
                      'Phakding': [27.7371, 86.7118],
                      'Birethanti': [28.3129, 83.7431],
                      'Jomsom': [28.7845, 83.7225],
                      'Muktinath': [28.8166, 83.8711],
                      'Chame': [28.5529, 84.2407],
                      'Pisang': [28.6148, 84.1481],
                      'Manang': [28.6657, 84.0202],
                      'Thorong La': [28.7937, 83.9377],
                      'Langtang Village': [28.2144, 85.5039],
                      'Kyanjin Gompa': [28.2114, 85.5670],
                      'Manaslu': [28.5497, 84.5597],
                      'Soti Khola': [28.0264, 84.8219],
                      'Machha Khola': [28.1250, 84.8694],
                      'Jagat': [28.1667, 84.8958],
                      'Deng': [28.5167, 84.8167],
                      'Namrung': [28.6167, 84.7500],
                      'Lho': [28.5833, 84.6667],
                      'Sama Gaon': [28.5833, 84.6333],
                      'Samdo': [28.6500, 84.6167],
                      'Dharamsala': [28.6750, 84.5667],
                      'Bhimthang': [28.6167, 84.4500],
                      'Tilije': [28.5000, 84.4167],
                      'Tal': [28.4667, 84.3833],
                      'Syangja': [28.0989, 83.8211],
                      'Kagbeni': [28.8359, 83.7848],
                      'Chele': [28.9000, 83.8333],
                      'Ghami': [29.0500, 83.8833],
                      'Charang': [29.0833, 83.9333],
                      'Lo Manthang': [29.1833, 83.9500],
                      'Kanchenjunga': [27.7022, 88.1475],
                      'Taplejung': [27.3517, 87.6697],
                      'Rara Lake': [29.5312, 82.0838],
                      'Dunai': [28.9833, 82.9000],
                      'Shey Phoksundo': [29.2081, 82.9554]
                  };
 
                  let latlngs = [];
                  let lastKnown = [28.3949, 84.1240];
                  
                  itinerary.forEach((item, index) => {
                      let loc = item.location || item.title || '';
                      let coords = null;
                      
                      // Match known locations
                      for (const [key, value] of Object.entries(knownLocations)) {
                          if (loc.toLowerCase().includes(key.toLowerCase())) {
                              coords = value;
                              break;
                          }
                      }
                      
                      // If unknown, advance slightly to form a path
                      if (!coords) {
                          coords = [
                              lastKnown[0] + (Math.random() - 0.2) * 0.05,
                              lastKnown[1] + (Math.random() - 0.2) * 0.05
                          ];
                      }
                      
                      lastKnown = coords;
                      latlngs.push(coords);
                      
                      const marker = L.marker(coords).addTo(map);
                      
                      // Detailed popup for hover/clicks
                      const shortDesc = (item.desc || '').substring(0, 100);
                      marker.bindPopup(`<b>Day ${item.day}: ${(item.title || '')}</b><br>${shortDesc}...`);
                      
                      // Permanent visual label text on the map (Req 5 / 11)
                      const labelLoc = item.location || getJsLocationFallback(item.title || '');
                      if (labelLoc) {
                          marker.bindTooltip(`Day ${item.day}: ${labelLoc}`, {
                              permanent: true,
                              direction: 'top',
                              offset: [0, -10],
                              className: 'map-label-tooltip'
                          });
                      }
                  });
 
                  if (latlngs.length > 0) {
                      polyline = L.polyline(latlngs, {color: 'var(--color-secondary)', weight: 4, dashArray: '8, 8'}).addTo(map);
                      map.fitBounds(polyline.getBounds(), {padding: [50, 50]});
                  }
              });
            </script>
          </div>

          <!-- Tab Pane: Inclusions & Permits -->
          <div class="tab-pane" id="detail-inclusions">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2.5rem; margin-bottom:3rem;" id="inclusions-columns">
              
              <div>
                <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; font-weight:800;">
                  <span style="color:var(--color-success); font-size:1.5rem;">✔</span> Price Includes
                </h3>
                <?php renderPackagePointList($current_pkg['costIncludes'] ?? [], 'include'); ?>
              </div>

              <div>
                <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; font-weight:800;">
                  <span style="color:var(--color-danger); font-size:1.5rem;">✘</span> Price Excludes
                </h3>
                <?php renderPackagePointList($current_pkg['costExcludes'] ?? [], 'exclude'); ?>
              </div>

            </div>

            <!-- Required Permits list -->
            <div style="border-top:1px solid var(--border-color); padding-top:2.5rem;">
              <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1.25rem; font-weight:800;">Required Travel Permits</h3>
              <?php if (is_array($current_pkg['requiredPermits'])): ?>
                <div style="display:flex; flex-wrap:wrap; gap:1rem;">
                  <?php foreach ($current_pkg['requiredPermits'] as $permit): ?>
                    <span class="card-badge" style="position:static; padding:0.5rem 1rem; border-radius:var(--radius-sm); border:1px solid var(--border-color); font-weight:600; font-size:0.85rem; color:var(--text-dark); background:var(--bg-light);">
                      🏷️ <?php echo htmlspecialchars($permit); ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div style="font-size:0.95rem; color:var(--text-medium); line-height:1.75;">
                  <?php echo $current_pkg['requiredPermits']; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Tab Pane: Trip FAQs -->
          <div class="tab-pane" id="detail-faqs">
            <h2 style="font-family: var(--font-heading); font-size:1.75rem; color:var(--color-primary); margin-bottom:1.5rem; font-weight:800;">Trip Frequently Asked Questions</h2>
            <div class="accordion-wrapper" style="display:flex; flex-direction:column; gap:1rem;">
              <?php foreach ($current_pkg['faqs'] as $idx => $faq): ?>
                <div class="accordion-item" style="border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow:hidden; background:white;">
                  <div class="accordion-header" style="padding:1.25rem; background:var(--bg-light); display:flex; justify-content:space-between; align-items:center; cursor:pointer;" onclick="toggleAccordion(this)">
                    <h4 style="font-family:var(--font-heading); font-size:1rem; font-weight:800; color:var(--color-primary); margin:0;"><?php echo htmlspecialchars($faq['q']); ?></h4>
                    <span>▼</span>
                  </div>
                  <div class="accordion-content" style="display:none; padding:1.25rem; border-top:1px solid var(--border-color); line-height:1.75; color:var(--text-medium);">
                    <?php echo $faq['a']; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Tab Pane: Photo Gallery (Req 13) -->
          <div class="tab-pane" id="detail-gallery">
            <h2 style="font-family: var(--font-heading); font-size:1.75rem; color:var(--color-primary); margin-bottom:1.5rem; font-weight:800;">Package Photos & Media</h2>
            <?php 
            $gallery = isset($current_pkg['gallery']) ? $current_pkg['gallery'] : [];
            if (empty($gallery)): 
            ?>
              <p style="color:var(--text-muted); text-align:center; padding:3rem 0;">No photos uploaded in the gallery list yet.</p>
            <?php else: ?>
              <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:1.25rem;" id="gallery-grid">
                <?php foreach ($gallery as $gidx => $img_path):
                  $gallery_alt = !empty($current_pkg['image_alt_texts'][$gidx]) ? $current_pkg['image_alt_texts'][$gidx] : ($image_alt ?? $current_pkg['title']);
                ?>
                  <div style="height:200px; border-radius:var(--radius-md); overflow:hidden; border:1px solid var(--border-color); background:#f1f5f9; cursor:pointer; transition:all 0.3s ease;" onmouseover="this.style.transform='scale(1.03)';" onmouseout="this.style.transform='scale(1)';" onclick="openLightbox('/<?php echo htmlspecialchars($img_path); ?>')">
                    <img src="/<?php echo htmlspecialchars($img_path); ?>" style="width:100%; height:100%; object-fit:cover;" alt="<?php echo htmlspecialchars($gallery_alt); ?>">
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Tab Pane: Traveler Reviews -->
          <div class="tab-pane" id="detail-reviews">
            <h2 style="font-family: var(--font-heading); font-size:1.75rem; color:var(--color-primary); margin-bottom:1.5rem; font-weight:800;">Traveler Reviews & Feedback</h2>
            
            <?php
            $approved_reviews = [];
            if (isset($current_pkg['reviews']) && is_array($current_pkg['reviews'])) {
                foreach ($current_pkg['reviews'] as $rev) {
                    if (isset($rev['approved']) && $rev['approved'] === true) {
                        $approved_reviews[] = $rev;
                    }
                }
            }
            ?>

            <?php if (empty($approved_reviews)): ?>
              <p style="color:var(--text-muted); margin-bottom:2.5rem;">No approved reviews available for this package. Be the first to leave feedback below!</p>
            <?php else: ?>
              <div style="display:flex; flex-direction:column; gap:1.5rem; margin-bottom:3rem;">
                <?php foreach ($approved_reviews as $rev): ?>
                  <div class="glass-card" style="padding:1.5rem; background:white; border:1px solid var(--border-color); border-radius:var(--radius-md);">
                    <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem; font-size:0.85rem; color:var(--text-muted);">
                      <strong><?php echo htmlspecialchars($rev['name']); ?></strong>
                      <span><?php echo htmlspecialchars($rev['date']); ?></span>
                    </div>
                    <div style="color:var(--color-secondary); margin-bottom:0.5rem;">
                      <?php echo str_repeat('★', $rev['rating']); ?>
                    </div>
                    <p style="margin:0; font-size:0.92rem; line-height:1.6; color:var(--text-medium);"><?php echo htmlspecialchars($rev['comment']); ?></p>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <!-- Leave a Review Form -->
            <div class="form-card" style="padding:2rem; background:var(--bg-light); border-radius:var(--radius-lg); border:1px solid var(--border-color);">
              <h3 style="font-family:var(--font-heading); font-size:1.25rem; color:var(--color-primary); margin-bottom:1.25rem; font-weight:800;">Submit Traveler Review</h3>
              <?php if (!empty($review_msg)): ?>
                <div style="padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; font-size: 0.9rem; font-weight: 600; border: 1px solid <?php echo $review_status === 'success' ? 'var(--color-success)' : 'var(--color-danger)'; ?>; background: <?php echo $review_status === 'success' ? '#f0fdf4' : '#fef2f2'; ?>; color: <?php echo $review_status === 'success' ? '#166534' : '#991b1b'; ?>;">
                  <?php echo htmlspecialchars($review_msg); ?>
                </div>
              <?php endif; ?>

              <form method="POST" action="" style="display:flex; flex-direction:column; gap:1rem;">
                <div class="form-group" style="margin-bottom:0;">
                  <label for="rev_name">Your Name</label>
                  <input type="text" id="rev_name" name="rev_name" class="form-input" required placeholder="e.g. Alice Smith">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                  <label for="rev_rating">Rating (1 to 5 Stars)</label>
                  <select id="rev_rating" name="rev_rating" class="form-input" style="max-width:180px;">
                    <option value="5">★★★★★ (5 Stars)</option>
                    <option value="4">★★★★☆ (4 Stars)</option>
                    <option value="3">★★★☆☆ (3 Stars)</option>
                    <option value="2">★★☆☆☆ (2 Stars)</option>
                    <option value="1">★☆☆☆☆ (1 Star)</option>
                  </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                  <label for="rev_comment">Review Comments</label>
                  <textarea id="rev_comment" name="rev_comment" class="form-input" rows="4" required placeholder="Describe your trekking experience, guide support, and logistics..."></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn btn-primary" style="height:40px; font-weight:700; max-width:180px;">Submit Review</button>
              </form>
            </div>
          </div>

        </div>

        <!-- Right Side: Booking and Help Stack -->
        <aside class="booking-sidebar-stack">
          <div class="booking-widget" id="price-calculator-card">
            <?php if (!empty($offer_badge)): ?>
              <div class="offer-pulse-badge" style="display:inline-flex; align-items:center; margin-bottom:0.75rem; background:#dc2626; color:white; border-radius:999px; padding:0.3rem 0.65rem; font-size:0.75rem; font-weight:900; letter-spacing:0.02em;">
                <?php echo htmlspecialchars($offer_badge); ?>
              </div>
            <?php endif; ?>
            <button type="button" aria-label="Save trip" style="float:right; border:0; background:transparent; color:#1684d8; font-size:1.75rem; line-height:1; cursor:pointer;">♡</button>
            <div style="font-size:0.9rem; color:#444; margin-bottom:0.15rem;">Price from:</div>
            <div class="price-line">
              <span class="current-price">US$<?php echo number_format($current_pkg['priceUSD']); ?></span>
              <?php if ($old_price_usd > $current_pkg['priceUSD']): ?>
                <span class="old-price">US$<?php echo number_format($old_price_usd); ?></span>
              <?php endif; ?>
              <span style="font-size:0.82rem; color:#444; font-weight:700;">P/P</span>
            </div>
            <?php if (!empty($offer_end_date)): ?>
              <div class="package-countdown limited-time-text" data-offer-end="<?php echo htmlspecialchars($offer_end_date); ?>" style="margin-top:0.75rem;">Limited time offer</div>
            <?php endif; ?>

            <div class="booking-offer-row">
              <span><?php echo htmlspecialchars($group_offer_text); ?></span>
              <span style="font-size:1.25rem; color:#16a34a;">+</span>
            </div>

            <div class="form-group">
              <label for="calc-travel-date">Departure Date</label>
              <input type="date" id="calc-travel-date" class="form-input" onchange="calculateTripTotal()">
            </div>

            <div class="form-group">
              <label for="calc-travelers">No. of Traveler</label>
              <div class="booking-stepper">
                <button type="button" onclick="stepTravelers(-1)" aria-label="Decrease travelers">-</button>
                <input type="number" id="calc-travelers" min="1" max="100" value="1" oninput="calculateTripTotal()">
                <button type="button" onclick="stepTravelers(1)" aria-label="Increase travelers">+</button>
              </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin:1rem 0; font-size:1rem;">
              <span>Total Price</span>
              <strong id="calc-total-display" style="font-size:1.1rem;">US$<?php echo number_format($current_pkg['priceUSD']); ?></strong>
            </div>
            <div id="calc-per-person" style="font-size:0.75rem; color:var(--text-muted); margin-top:-0.5rem; margin-bottom:1rem;">US$<?php echo number_format($current_pkg['priceUSD']); ?> per person</div>

            <div class="booking-actions">
              <a href="/contact?package=<?php echo urlencode($current_pkg['title']); ?>" class="btn btn-primary" id="calc-book-btn">Book Now</a>
              <button type="button" class="btn btn-primary" onclick="alert('Trip added to your planning list. You can continue with Check Availability or Send Inquiry.');">Add to Cart</button>
            </div>
            <div class="booking-actions-full">
              <a href="/contact?package=<?php echo urlencode($current_pkg['title']); ?>" class="btn btn-outline" id="calc-availability-btn" style="background:#2d7dcc; color:white; border-color:#2d7dcc;">Check Availability</a>
              <a href="#instant-help-widget" class="btn btn-outline" style="background:#2d7dcc; color:white; border-color:#2d7dcc;">Send Inquiry</a>
            </div>

            <div style="text-align:center; margin-top:1rem; color:#188038; font-weight:800;">
              ★ 5.0 <span style="color:#555; font-weight:500;"><?php echo $review_count > 0 ? $review_count . ' reviews' : 'verified trip'; ?></span>
            </div>
          </div>

          <!-- Reusable Need Help WhatsApp Widget -->
          <div class="glass-card help-whatsapp-widget" id="instant-help-widget" style="padding:1.25rem; background:white; border:1px solid var(--border-color); border-radius:var(--radius-lg); box-shadow: var(--shadow-sm);">
            <h3 style="font-family:var(--font-heading); font-size:1.2rem; color:var(--color-primary); margin-bottom:0.75rem; font-weight:800; display:flex; align-items:center; gap:0.5rem;">
              <span>💬</span> Need Instant Help?
            </h3>
            <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:1rem; line-height:1.4;">Ask our Nepal travel specialist directly on WhatsApp.</p>
            
            <form onsubmit="sendWhatsAppMessage(event)" style="display:flex; flex-direction:column; gap:0.85rem;">
              <input type="text" placeholder="Your Name" required class="form-input wa-name" style="height:38px; font-size:0.85rem;">
              <input type="email" placeholder="Your Email" required class="form-input wa-email" style="height:38px; font-size:0.85rem;">
              <div class="country-code-picker">
                <input type="search" class="form-input country-code-search" placeholder="Search country code">
                <div class="country-code-row">
                  <select class="form-input country-code-select wa-code" aria-label="Country phone code"></select>
                  <input type="text" placeholder="Phone Number" required class="form-input wa-phone">
                </div>
                <input type="hidden" class="country-code-value" value="+977">
              </div>
              <textarea placeholder="Ask a question..." required class="form-input wa-question" rows="3" style="font-size:0.85rem; resize:none;"></textarea>
              <button type="submit" class="btn btn-primary" style="height:38px; font-size:0.88rem; width:100%; display:flex; align-items:center; justify-content:center; gap:0.5rem; background-color:#25d366; border-color:#25d366; color:white; font-weight:700; border-radius:var(--radius-sm);">
                Send WhatsApp Inquiry
              </button>
            </form>
          </div>

          <script>
          function sendWhatsAppMessage(event) {
            event.preventDefault();
            var form = event.target;
            var name = form.querySelector('.wa-name').value.trim();
            var email = form.querySelector('.wa-email').value.trim();
            var code = form.querySelector('.country-code-value') ? form.querySelector('.country-code-value').value.trim() : form.querySelector('.wa-code').value.trim();
            var phone = form.querySelector('.wa-phone').value.trim();
            var question = form.querySelector('.wa-question').value.trim();
            
            var text = "Hello Top Mountain Treks,\n\n" +
                       "I have an inquiry regarding " + <?php echo json_encode($current_pkg['title']); ?> + ".\n" +
                       "Name: " + name + "\n" +
                       "Email: " + email + "\n" +
                       "Phone: " + code + " " + phone + "\n" +
                       "Question: " + question;
                       
            var whatsappNum = "<?php echo preg_replace('/[^0-9]/', '', $site_whatsapp); ?>";
            var url = "https://wa.me/" + whatsappNum + "?text=" + encodeURIComponent(text);
            window.open(url, '_blank');
          }
          </script>
        </aside>

      </div>

    </div>
  </section>

  <!-- Lightbox Modal for Gallery Images -->
  <div id="gallery-lightbox" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.9); z-index:2000; align-items:center; justify-content:center;" onclick="closeLightbox()">
    <span style="position:absolute; right:20px; top:20px; color:white; font-size:2rem; cursor:pointer;">&times;</span>
    <img id="lightbox-img" src="" style="max-width:90%; max-height:85%; object-fit:contain; border-radius:var(--radius-md);" onclick="event.stopPropagation()">
  </div>

  <script>
    // SVG node toggles (No longer used as Leaflet handles popups)
    function showTrekOverlayDetails(day, title, desc) {}

    // FAQ Accordion toggler
    function toggleAccordion(header) {
        const item = header.parentElement;
        const content = item.querySelector('.accordion-content');
        const indicator = header.querySelector('span');
        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'block';
            if (indicator) indicator.textContent = '▲';
        } else {
            content.style.display = 'none';
            if (indicator) indicator.textContent = '▼';
        }
    }

    // Lightbox modal scripts
    function openLightbox(src) {
        document.getElementById('lightbox-img').src = src;
        document.getElementById('gallery-lightbox').style.display = 'flex';
    }
    function closeLightbox() {
        document.getElementById('gallery-lightbox').style.display = 'none';
    }

    // Pricing Calculator logic
    const basePriceUSD = <?php echo (int)$current_pkg['priceUSD']; ?>;
    const durationDays = <?php echo (int)$current_pkg['duration']; ?>;
    
    function calculateTripTotal() {
        const travelersInput = document.getElementById('calc-travelers');
        const travelers = Math.max(1, parseInt(travelersInput.value, 10) || 1);
        travelersInput.value = travelers;
        
        let perPersonBase = basePriceUSD;
        if (travelers >= 10) {
            perPersonBase *= 0.85;
        } else if (travelers >= 5) {
            perPersonBase *= 0.90;
        } else if (travelers >= 3) {
            perPersonBase *= 0.95;
        }
        
        const totalPerPerson = Math.round(perPersonBase);
        const grandTotal = totalPerPerson * travelers;
        const dateValue = document.getElementById('calc-travel-date') ? document.getElementById('calc-travel-date').value : '';
        
        document.getElementById('calc-total-display').textContent = 'US$' + Math.round(grandTotal).toLocaleString();
        document.getElementById('calc-per-person').textContent = 'US$' + totalPerPerson.toLocaleString() + ' per person';
        
        const query = '&travelers=' + travelers + (dateValue ? '&travel_date=' + encodeURIComponent(dateValue) : '');
        const bookBtn = document.getElementById('calc-book-btn');
        const availabilityBtn = document.getElementById('calc-availability-btn');
        if (bookBtn) bookBtn.href = '/contact?package=<?php echo urlencode($current_pkg['title']); ?>' + query;
        if (availabilityBtn) availabilityBtn.href = '/contact?package=<?php echo urlencode($current_pkg['title']); ?>' + query;
    }

    function stepTravelers(delta) {
        const input = document.getElementById('calc-travelers');
        const current = parseInt(input.value, 10) || 1;
        input.value = Math.min(100, Math.max(1, current + delta));
        calculateTripTotal();
    }
    
    // Run calculator on initial load
    calculateTripTotal();
  </script>

<?php include 'includes/footer.php'; ?>
