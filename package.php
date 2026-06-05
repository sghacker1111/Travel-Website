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
    $rev_rating = isset($_POST['rev_rating']) ? (int)$_POST['rev_rating'] : 5;
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

// Set package-specific SEO parameters
$page_title = isset($current_pkg['seo_title']) && !empty($current_pkg['seo_title']) ? $current_pkg['seo_title'] : $current_pkg['title'] . " | Top Mountain Treks Pvt. Ltd.";
$page_desc = isset($current_pkg['seo_description']) && !empty($current_pkg['seo_description']) ? $current_pkg['seo_description'] : strip_tags($current_pkg['overview']);
$image_alt = isset($current_pkg['image_alt']) && !empty($current_pkg['image_alt']) ? $current_pkg['image_alt'] : $current_pkg['title'] . " landscape photo";

include 'includes/header.php';

$prefix = 'trek';
if ($current_pkg['category'] === 'tour') $prefix = 'tour';
elseif ($current_pkg['category'] === 'climbing') $prefix = 'climb';
?>

  <!-- Header Hero Banner -->
  <section class="hero" style="background-image: url('/<?php echo htmlspecialchars($current_pkg['image']); ?>'); padding: 6rem 0 4rem; min-height: 50vh; background-size:cover; background-position:center; position:relative; display:flex; align-items:center; background-attachment: fixed;">
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

          <!-- Tab Pane: Overview -->
          <div class="tab-pane active" id="detail-overview">
            <h2 style="font-family: var(--font-heading); font-size:1.75rem; color:var(--color-primary); margin-bottom:1rem; font-weight:800;">Trip Overview</h2>
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
              <div><strong>Permit Check:</strong> <?php echo count($current_pkg['requiredPermits']); ?> required</div>
            </div>
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

            <h2 style="font-family: var(--font-heading); font-size:1.75rem; color:var(--color-primary); margin-bottom:1.5rem; font-weight:800;">Day-by-Day Detailed Itinerary</h2>
            
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
                <?php if (is_array($current_pkg['costIncludes'])): ?>
                  <ul style="display:grid; gap:0.75rem; list-style:none; padding:0;">
                    <?php foreach ($current_pkg['costIncludes'] as $item): ?>
                      <li style="display:flex; align-items:flex-start; gap:0.5rem; font-size:0.9rem; color:var(--text-medium);">
                        <span style="color:var(--color-success); font-weight:bold;">+</span>
                        <span><?php echo htmlspecialchars($item); ?></span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <div style="font-size:0.95rem; color:var(--text-medium); line-height:1.75;">
                    <?php echo $current_pkg['costIncludes']; ?>
                  </div>
                <?php endif; ?>
              </div>

              <div>
                <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; font-weight:800;">
                  <span style="color:var(--color-danger); font-size:1.5rem;">✘</span> Price Excludes
                </h3>
                <?php if (is_array($current_pkg['costExcludes'])): ?>
                  <ul style="display:grid; gap:0.75rem; list-style:none; padding:0;">
                    <?php foreach ($current_pkg['costExcludes'] as $item): ?>
                      <li style="display:flex; align-items:flex-start; gap:0.5rem; font-size:0.9rem; color:var(--text-medium);">
                        <span style="color:var(--color-danger); font-weight:bold;">-</span>
                        <span><?php echo htmlspecialchars($item); ?></span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <div style="font-size:0.95rem; color:var(--text-medium); line-height:1.75;">
                    <?php echo $current_pkg['costExcludes']; ?>
                  </div>
                <?php endif; ?>
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
                <?php foreach ($gallery as $img_path): ?>
                  <div style="height:200px; border-radius:var(--radius-md); overflow:hidden; border:1px solid var(--border-color); background:#f1f5f9; cursor:pointer; transition:all 0.3s ease;" onmouseover="this.style.transform='scale(1.03)';" onmouseout="this.style.transform='scale(1)';" onclick="openLightbox('/<?php echo htmlspecialchars($img_path); ?>')">
                    <img src="/<?php echo htmlspecialchars($img_path); ?>" style="width:100%; height:100%; object-fit:cover;" alt="Trekking view">
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

        <!-- Right Side: Booking Side Calculator Card -->
        <div>
          <div class="form-card" style="padding: 2rem; position: sticky; top: 100px; background: white; border: 1px solid var(--border-color); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);" id="price-calculator-card">
            <h3 style="font-family: var(--font-heading); font-size:1.35rem; color:var(--color-primary); margin-bottom:1.25rem; font-weight:800;">Trip Calculator</h3>
            
            <div style="display:flex; flex-direction:column; gap:1.25rem; font-size:0.9rem;">
              <div>
                <label style="font-weight:700; display:block; margin-bottom:0.25rem;" for="calc-travelers">Group Size (People)</label>
                <input type="number" id="calc-travelers" min="1" max="30" value="1" class="form-input" oninput="calculateTripTotal()">
              </div>
              
              <div>
                <label style="font-weight:700; display:block; margin-bottom:0.25rem;" for="calc-guide">Guide Assistance Level</label>
                <select id="calc-guide" class="form-input" onchange="calculateTripTotal()">
                  <option value="standard">Standard Local Guide (Included)</option>
                  <option value="expert">Senior Climber Guide (+$35/day)</option>
                  <option value="porter">Guide + Porter Team (+$25/day)</option>
                </select>
              </div>

              <div>
                <label style="font-weight:700; display:block; margin-bottom:0.5rem;">Add-On Services</label>
                <div style="display:flex; flex-direction:column; gap:0.5rem;">
                  <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                    <input type="checkbox" id="add-insurance" onchange="calculateTripTotal()"> Helicopter Rescue Cover (+$150/person)
                  </label>
                  <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                    <input type="checkbox" id="add-kit" onchange="calculateTripTotal()"> Down Jacket & Gear Kit Hire (+$80/person)
                  </label>
                </div>
              </div>

              <div style="border-top:1px solid var(--border-color); padding-top:1.25rem; margin-top:0.5rem; text-align:center;">
                <span style="font-size:0.85rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; display:block;">Estimated Total Price</span>
                <span style="font-size:2.2rem; font-weight:800; color:var(--color-secondary); display:block; margin: 0.25rem 0;" id="calc-total-display">$0</span>
                <span style="font-size:0.75rem; color:var(--text-muted); font-weight:600;" id="calc-per-person">($0 USD per person)</span>
              </div>
              
              <a href="/contact" class="btn btn-primary" style="text-align:center; height:44px; display:flex; align-items:center; justify-content:center; text-decoration:none; font-weight:700; margin-top:0.5rem;" id="calc-book-btn">
                Book Expedition Now
              </a>
            </div>
          </div>

          <!-- Reusable Need Help WhatsApp Widget -->
          <div class="glass-card help-whatsapp-widget" style="padding:1.75rem; background:white; border:1px solid var(--border-color); border-radius:var(--radius-lg); margin-top:2rem; box-shadow: var(--shadow-sm);">
            <h3 style="font-family:var(--font-heading); font-size:1.2rem; color:var(--color-primary); margin-bottom:0.75rem; font-weight:800; display:flex; align-items:center; gap:0.5rem;">
              <span>💬</span> Need Instant Help?
            </h3>
            <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:1.25rem; line-height:1.4;">Chat directly with our Sherpa travel specialist on WhatsApp. Get answers in minutes!</p>
            
            <form onsubmit="sendWhatsAppMessage(event)" style="display:flex; flex-direction:column; gap:0.85rem;">
              <div>
                <input type="text" placeholder="Your Name" required class="form-input wa-name" style="height:36px; font-size:0.85rem; padding:0 0.75rem;">
              </div>
              <div>
                <input type="email" placeholder="Your Email" required class="form-input wa-email" style="height:36px; font-size:0.85rem; padding:0 0.75rem;">
              </div>
              <div style="display:flex; gap:0.5rem;">
                <input type="text" placeholder="+1" required class="form-input wa-code" style="width:60px; height:36px; font-size:0.85rem; padding:0 0.5rem; text-align:center;" value="+977">
                <input type="text" placeholder="Phone Number" required class="form-input wa-phone" style="flex:1; height:36px; font-size:0.85rem; padding:0 0.75rem;">
              </div>
              <div>
                <textarea placeholder="Ask a question..." required class="form-input wa-question" rows="3" style="font-size:0.85rem; padding:0.5rem 0.75rem; resize:none;"></textarea>
              </div>
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
            var code = form.querySelector('.wa-code').value.trim();
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
        </div>

      </div>

    </div>
  </section>

  <!-- Lightbox Modal for Gallery Images -->
  <div id="gallery-lightbox" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.9); z-index:2000; align-items:center; justify-content:center;" onclick="closeLightbox()">
    <span style="position:absolute; right:20px; top:20px; color:white; font-size:2rem; cursor:pointer;">&times;</span>
    <img id="lightbox-img" src="" style="max-width:90%; max-height:85%; object-fit:contain; border-radius:var(--radius-md);" onclick="event.stopPropagation()">
  </div>

  <script>
    // Tab switching script
    const tabs = document.querySelectorAll('.tab');
    const panes = document.querySelectorAll('.tab-pane');
    
    tabs.forEach(t => {
        t.addEventListener('click', () => {
            tabs.forEach(x => x.classList.remove('active'));
            panes.forEach(x => x.classList.remove('active'));
            
            t.classList.add('active');
            const target = t.getAttribute('data-tab');
            const targetPane = document.getElementById(target);
            if (targetPane) targetPane.classList.add('active');
            
            // Recalculate Leaflet map layout when the Route Map tab is activated
            if (target === 'detail-route' && typeof map !== 'undefined' && map !== null) {
                setTimeout(() => {
                    map.invalidateSize();
                    if (typeof polyline !== 'undefined' && polyline !== null) {
                        map.fitBounds(polyline.getBounds(), {padding: [50, 50]});
                    }
                }, 100);
            }
        });
    });

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
        const travelers = parseInt(document.getElementById('calc-travelers').value) || 1;
        const guide = document.getElementById('calc-guide').value;
        const insurance = document.getElementById('add-insurance').checked;
        const kit = document.getElementById('add-kit').checked;
        
        // Calculate base price with group discounts
        let perPersonBase = basePriceUSD;
        if (travelers >= 10) {
            perPersonBase *= 0.85; // 15% discount
        } else if (travelers >= 5) {
            perPersonBase *= 0.90; // 10% discount
        } else if (travelers >= 3) {
            perPersonBase *= 0.95; // 5% discount
        }
        
        // Guide surcharges
        let guideDayFee = 0;
        if (guide === 'expert') guideDayFee = 35;
        if (guide === 'porter') guideDayFee = 25;
        
        let perPersonSurcharges = guideDayFee * durationDays;
        
        // Addons
        if (insurance) perPersonSurcharges += 150;
        if (kit) perPersonSurcharges += 80;
        
        const totalPerPerson = perPersonBase + perPersonSurcharges;
        const grandTotal = totalPerPerson * travelers;
        
        // Display values
        document.getElementById('calc-total-display').textContent = '$' + Math.round(grandTotal).toLocaleString();
        document.getElementById('calc-per-person').textContent = '($' + Math.round(totalPerPerson).toLocaleString() + ' USD per person)';
        
        // Pre-fill links to contact form booking
        const bookBtn = document.getElementById('calc-book-btn');
        bookBtn.href = '/contact?package=<?php echo urlencode($current_pkg['title']); ?>&travelers=' + travelers;
    }
    
    // Run calculator on initial load
    calculateTripTotal();
  </script>

<?php include 'includes/footer.php'; ?>