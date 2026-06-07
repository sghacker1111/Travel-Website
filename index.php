<?php
$page_title = "Top Mountain Treks Pvt. Ltd. | Nepal Trekking & Travel Specialist";
$page_desc = "Top Mountain Treks Pvt. Ltd. is a local government-registered trekking agency offering expert-led expeditions to Everest Base Camp, Annapurna, Manaslu, and custom Nepal tour packages.";
include 'includes/header.php';

// Load packages
$packages_path = 'data/packages.json';
$packages = [];
if (file_exists($packages_path)) {
    $packages = json_decode(file_get_contents($packages_path), true);
}
if (!is_array($packages)) $packages = [];

$settings_data = [];
if (file_exists('data/settings.json')) {
    $settings_data = json_decode(file_get_contents('data/settings.json'), true);
}
if (!is_array($settings_data)) $settings_data = [];
$home_offer_defaults = [
    'default_badge' => 'Hot Deal',
    'default_group_text' => 'Group Offer',
    'default_offer_days' => '30',
    'limited_text' => 'Limited time'
];
$home_offer_settings = array_merge($home_offer_defaults, isset($settings_data['home_offers']) && is_array($settings_data['home_offers']) ? $settings_data['home_offers'] : []);

// Get slider packages
$slider_packages = [];
$featured_packages = [];
if (is_array($packages)) {
    foreach ($packages as $pkg) {
        if (in_array($pkg['slug'], ['everest-base-camp-trek', 'annapurna-circuit-trek', 'langtang-valley-trek', 'yala-peak-climbing'])) {
            $slider_packages[] = $pkg;
        }
        if (in_array($pkg['slug'], ['everest-base-camp-trek', 'annapurna-base-camp-trek', 'mera-peak-climbing', 'chitwan-wildlife-tour'])) {
            $featured_packages[] = $pkg;
        }
    }
}
// Determine slider layout alignment
$slider_align = $theme_settings['slider_text_align'] ?? 'left';
$slider_flex_align = 'flex-start';
if ($slider_align === 'center') {
    $slider_flex_align = 'center';
} elseif ($slider_align === 'right') {
    $slider_flex_align = 'flex-end';
}

function home_extract_altitude_meters($text) {
    if (!is_string($text) || $text === '') return null;
    if (preg_match_all('/([1-6][0-9,]{3,5})\s*(?:m|meter|meters|metre|metres)/i', $text, $matches)) {
        $values = array_map(function($value) {
            return (int)str_replace(',', '', $value);
        }, $matches[1]);
        return max($values);
    }
    return null;
}

function home_clean_day_place($item) {
    $location = trim($item['location'] ?? '');
    if ($location !== '') return $location;
    $title = trim($item['title'] ?? '');
    $title = preg_replace('/\([^)]*\)/', '', $title);
    if (preg_match('/\barrival\s+in\s+([^,.;]+?)(?:\s+and\s+|$)/i', $title, $match)) {
        return trim($match[1]);
    }
    if (preg_match_all('/\bto\s+([^,.;]+?)(?=\s+and\s+|$)/i', $title, $matches) && !empty($matches[1])) {
        $place = trim(end($matches[1]));
        if (!preg_match('/^(hotel|airport)$/i', $place)) {
            return $place;
        }
    }
    if (preg_match('/\bin\s+([^,.;]+?)(?:\s+and\s+|$)/i', $title, $match)) {
        return trim($match[1]);
    }
    $title = preg_replace('/^(fly|drive|trek|hike|walk|return|arrival|departure)\s+(from|to)?\s*/i', '', $title);
    return trim($title) !== '' ? trim($title) : 'Trail waypoint';
}

$simulator_packages = [];
foreach ($packages as $pkg) {
    if (empty($pkg['itinerary']) || !is_array($pkg['itinerary'])) continue;
    $days = [];
    $last_altitude = home_extract_altitude_meters($pkg['startPoint'] ?? '') ?: 1300;
    foreach ($pkg['itinerary'] as $item) {
        $text_blob = implode(' ', [
            $item['title'] ?? '',
            $item['desc'] ?? '',
            $item['location'] ?? ''
        ]);
        $altitude = home_extract_altitude_meters($text_blob);
        if ($altitude === null) {
            $altitude = $last_altitude;
        }
        $last_altitude = $altitude;
        $title = trim($item['title'] ?? ('Day ' . ($item['day'] ?? count($days) + 1)));
        $desc = trim(strip_tags($item['desc'] ?? 'Follow the planned trail at a steady pace with guide monitoring.'));
        $days[] = [
            'day' => (int)($item['day'] ?? count($days) + 1),
            'title' => $title,
            'place' => home_clean_day_place($item),
            'path' => $title,
            'altitude' => $altitude,
            'desc' => $desc
        ];
    }
    if (empty($days)) continue;
    $max_altitude = max(array_column($days, 'altitude'));
    $simulator_packages[] = [
        'slug' => $pkg['slug'],
        'title' => $pkg['title'],
        'region' => $pkg['region'] ?? '',
        'maxAltitude' => $max_altitude,
        'days' => $days
    ];
}
?>

  <!-- Hero Slider -->
  <section class="hero-slider-wrapper home-hero-slider" id="home-hero-slider">
    <?php if (empty($slider_packages)): ?>
      <div class="hero-slide active" data-title="Explore the roof of the world" style="background-image: linear-gradient(180deg, rgba(15,23,42,0.75) 0%, rgba(15,23,42,0.55) 100%), url('assets/images/packages/hero_home.jpg');">
        <div class="container" style="position: relative; z-index: 3; padding: 2rem; display: flex; flex-direction: column; align-items: <?php echo $slider_flex_align; ?>; text-align: <?php echo $slider_align; ?>;">
          <h2 style="font-family:var(--font-heading); font-weight:800; margin-bottom:1rem; color: var(--slider-title-color, #ffffff); font-size: var(--slider-title-size, 50px) !important; text-shadow:0 2px 4px rgba(0,0,0,0.6);"><?php echo htmlspecialchars($pkg['title'] ?? 'Explore the roof of the world'); ?></h2>
          <p style="max-width:700px; margin-bottom:2rem; line-height:1.6; font-weight:600; color: var(--slider-desc-color, #ffffff); font-size: var(--slider-desc-size, 18px) !important; text-shadow:0 1px 3px rgba(0,0,0,0.6);"><?php echo htmlspecialchars($pkg['overview'] ?? 'Expert-led high-altitude trekking, peak climbing, and tours in the Himalayas.'); ?></p>
          <a href="/trekking" class="btn btn-primary">Choose Your Trail</a>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($slider_packages as $idx => $pkg): ?>
        <div class="hero-slide <?php echo $idx === 0 ? 'active' : ''; ?>" data-title="<?php echo htmlspecialchars($pkg['title']); ?>" style="background-image: linear-gradient(180deg, rgba(15,23,42,0.5) 0%, rgba(15,23,42,0.5) 100%), url('/<?php echo htmlspecialchars($pkg['image']); ?>');" id="slide-<?php echo $idx; ?>">
          <div class="container" style="position: relative; z-index: 3; padding: 2rem; display: flex; flex-direction: column; align-items: <?php echo $slider_flex_align; ?>; text-align: <?php echo $slider_align; ?>;">
            <span class="card-badge" style="position:static; display:inline-block; margin-bottom:0.75rem; background:var(--color-secondary); color:#ffffff; border:none;"><?php echo htmlspecialchars($pkg['region']); ?> Region</span>
            <h2 style="font-family:var(--font-heading); font-weight:800; margin-top:0.5rem; margin-bottom:1rem; text-shadow:0 2px 4px rgba(0,0,0,0.6); color: var(--slider-title-color, #ffffff); font-size: var(--slider-title-size, 50px) !important;"><?php echo htmlspecialchars($pkg['title']); ?></h2>
            <p style="max-width:700px; margin-bottom:2rem; line-height:1.6; text-shadow:0 1px 3px rgba(0,0,0,0.6); font-weight:600; color: var(--slider-desc-color, #ffffff); font-size: var(--slider-desc-size, 18px) !important;"><?php echo htmlspecialchars(substr(strip_tags($pkg['overview']), 0, 180)); ?>...</p>
            <div style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap; justify-content: <?php echo $slider_flex_align === 'center' ? 'center' : ($slider_flex_align === 'flex-end' ? 'flex-end' : 'flex-start'); ?>;">
              <a href="package.php?slug=<?php echo htmlspecialchars($pkg['slug']); ?>" target="_blank" class="btn btn-primary">View Package Details</a>
              <span style="font-size:1.2rem; font-weight:800; color:var(--color-secondary); text-shadow: 0 1px 2px rgba(0,0,0,0.6);">$<?php echo number_format($pkg['priceUSD']); ?> USD starting price</span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      
      <!-- Slider Arrows -->
      <button onclick="prevHeroSlide()" style="position:absolute; left:20px; top:50%; transform:translateY(-50%); background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.25); color:white; border-radius:50%; width:44px; height:44px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:1.2rem; transition:all 0.3s;" onmouseover="this.style.background='var(--color-secondary)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">❮</button>
      <button onclick="nextHeroSlide()" style="position:absolute; right:20px; top:50%; transform:translateY(-50%); background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.25); color:white; border-radius:50%; width:44px; height:44px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:1.2rem; transition:all 0.3s;" onmouseover="this.style.background='var(--color-secondary)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">❯</button>
      <div class="hero-helicopter" aria-hidden="true">
        <span class="heli-airframe">
          <span class="heli-rotor"></span>
          <span class="heli-tail"></span>
          <span class="heli-body"></span>
          <span class="heli-skid"></span>
        </span>
        <span class="hero-heli-label" id="hero-heli-label"><?php echo htmlspecialchars($slider_packages[0]['title']); ?></span>
      </div>

      <button type="button" class="hero-arrow hero-arrow-left" id="hero-prev" aria-label="Previous package">&#8249;</button>
      <button type="button" class="hero-arrow hero-arrow-right" id="hero-next" aria-label="Next package">&#8250;</button>
      <div class="hero-dots" id="hero-dots-container" aria-label="Slider position"></div>
    <?php endif; ?>
  </section>

  <!-- Featured Packages Section -->
  <section style="padding: 5rem 0;">
    <div class="container">
      <div style="text-align:center; margin-bottom:4rem;">
        <span class="card-badge" style="position:static; display:inline-block; margin-bottom:0.75rem;">Top Rated</span>
        <h2 style="font-family:var(--font-heading); font-size:2.5rem; font-weight:800; color:var(--color-primary); margin-bottom:0.75rem;">Our Featured Himalayan Expeditions</h2>
        <p style="color:var(--text-medium); font-size:1.1rem; max-width:650px; margin: 0 auto;">Select from our best-selling packages, complete with daily guides, oxygen checking monitors, and hot bookings.</p>
      </div>

      <div class="grid grid-3" style="gap:2.5rem;">
        <?php 
        foreach ($featured_packages as $pkg) {
            if (empty($pkg['offer_badge'])) $pkg['offer_badge'] = $home_offer_settings['default_badge'];
            if (empty($pkg['group_offer_text'])) $pkg['group_offer_text'] = $home_offer_settings['default_group_text'];
            if (empty($pkg['offer_end_date'])) {
                $days = max(1, (int)$home_offer_settings['default_offer_days']);
                $pkg['offer_end_date'] = date('Y-m-d', time() + ($days * 86400));
            }
            include 'includes/package-card.php';
        }
        ?>
      </div>
    </div>
  </section>

  <!-- Interactive Altitude Adaptability Simulator -->
  <section class="section-bg-alternate" style="padding: 5rem 0; border-top:1px solid var(--border-color); border-bottom:1px solid var(--border-color);">
    <div class="container">
      <div style="display:grid; grid-template-columns:1fr 1.2fr; gap:4rem; align-items:center;" id="altitude-simulator-grid">
        
        <div>
          <span class="card-badge" style="position:static; display:inline-block; margin-bottom:0.75rem; background:rgba(0,119,182,0.1); color:var(--color-accent); border:1px solid rgba(0,119,182,0.2);">Safety First</span>
          <h2 style="font-family:var(--font-heading); font-size:2.2rem; font-weight:800; color:var(--color-primary); margin-bottom:1rem;">Himalayan Altitude Adaptability Simulator</h2>
          <p style="color:var(--text-medium); line-height:1.75; font-size:1rem; margin-bottom:1.5rem;">
            Safety at high-altitude is our primary focus. Slide the mountain elevation gauge to see oxygen depletion levels, pressure details, acclimatization zones, and guide tips.
          </p>
          
          <div class="glass-card" style="padding:1.5rem; border:1px solid var(--border-color); background-color:white;">
            <div style="margin-bottom:1.25rem;">
              <label style="font-weight:700; color:var(--text-dark); display:block; margin-bottom:0.5rem;" for="sim-package-select">Select Package:</label>
              <select id="sim-package-select" class="form-input" onchange="changeAltitudePackage(this.value)">
                <?php foreach ($simulator_packages as $sim_idx => $sim_pkg): ?>
                  <option value="<?php echo $sim_idx; ?>" <?php echo $sim_idx === 0 ? 'selected' : ''; ?>><?php echo htmlspecialchars($sim_pkg['title']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="margin-bottom:1.25rem;">
              <label style="font-weight:700; color:var(--text-dark); display:block; margin-bottom:0.5rem;" for="sim-altitude-range">Slide or Choose Day Elevation:</label>
              <input type="range" id="sim-altitude-range" min="1300" max="6500" value="3440" step="50" style="width:100%; accent-color:var(--color-accent);" oninput="updateAltitudeSimulation(this.value)">
            </div>
            <div style="display:flex; justify-content:space-between; font-weight:800; font-size:1.1rem; color:var(--color-primary);">
              <span>Elevation: <span id="sim-alt-val" style="color:var(--color-secondary);">3,440</span> m</span>
              <span>Oxygen Level: <span id="sim-oxy-val" style="color:var(--color-secondary);">67</span>%</span>
            </div>
          </div>
        </div>

        <div>
          <div class="glass-card" style="padding:2.5rem; background:white; border:1px solid var(--border-color); border-radius:var(--radius-lg); box-shadow:var(--shadow-lg);" id="sim-results-card">
            <h3 style="font-family:var(--font-heading); font-size:1.4rem; color:var(--color-primary); margin-bottom:1rem; font-weight:800;" id="sim-title">Namche Bazaar (Alt. 3,440 m)</h3>
            <p style="color:var(--text-medium); font-size:0.95rem; line-height:1.75; margin-bottom:1.5rem;" id="sim-desc">
              Entering the High Altitude zone. Acclimatization rest days are critical here. Mild headaches are common. Pacing slowly (Bistarai Bistarai) is key.
            </p>
            <div style="border:1px solid var(--border-color); border-radius:var(--radius-md); background:#f8fafc; padding:0.85rem; margin-bottom:1.25rem;">
              <div style="font-size:0.78rem; color:var(--text-muted); font-weight:900; text-transform:uppercase; margin-bottom:0.25rem;">Day-by-Day Trail Path</div>
              <div id="sim-path-desc" style="font-weight:800; color:var(--color-primary); line-height:1.45;">Day 1: Kathmandu</div>
              <div id="sim-day-track" class="sim-day-track" style="display:flex; gap:0.4rem; flex-wrap:wrap; margin-top:0.75rem;"></div>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; font-size:0.88rem; border-top:1px solid var(--border-color); padding-top:1.25rem;">
              <div><strong>Effective Oxygen:</strong> <span id="sim-oxy-desc">67% (Thin air)</span></div>
              <div><strong>Campsite Level:</strong> <span id="sim-zone-desc">High Altitude Zone</span></div>
              <div><strong>Air Pressure:</strong> <span id="sim-press-desc">680 hPa (Normal: 1013)</span></div>
              <div><strong>Safety Advisory:</strong> <span id="sim-safety-desc" style="color:var(--color-secondary); font-weight:700;">Rest day mandatory</span></div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- Why Choose Us & Testimonials -->
  <section style="padding: 5rem 0;">
    <div class="container">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:4rem;" id="why-testimonials-grid">
        
        <div>
          <h2 style="font-family:var(--font-heading); font-size:2.2rem; font-weight:800; color:var(--color-primary); margin-bottom:1.5rem;">Why Book With Top Mountain Treks?</h2>
          <p style="color:var(--text-medium); line-height:1.8; margin-bottom:2rem;">We are a local government-registered operator registered with the Ministry of Tourism, TIMS, and NMA, focusing strictly on high safety records.</p>
          
          <ul style="list-style:none; display:flex; flex-direction:column; gap:1.25rem; padding:0;">
            <li style="display:flex; align-items:flex-start; gap:0.75rem;">
              <span style="font-size:1.3rem; color:var(--color-secondary);">✔</span>
              <div>
                <strong>Expert NMA Sherpa Guides:</strong> All group expeditions are led by licensed mountain Sherpas with rescue and emergency first aid training.
              </div>
            </li>
            <li style="display:flex; align-items:flex-start; gap:0.75rem;">
              <span style="font-size:1.3rem; color:var(--color-secondary);">✔</span>
              <div>
                <strong>Twice-Daily Health Monitoring:</strong> We check SPO2 oxygen levels, pulse rates, and acclimatization markers twice a day on high-altitude trails.
              </div>
            </li>
            <li style="display:flex; align-items:flex-start; gap:0.75rem;">
              <span style="font-size:1.3rem; color:var(--color-secondary);">✔</span>
              <div>
                <strong>Satellite Communications:</strong> Carrying backup satellite communication links and GPS devices to trace routes in remote zones.
              </div>
            </li>
          </ul>
        </div>

        <div>
          <h2 style="font-family:var(--font-heading); font-size:2.2rem; font-weight:800; color:var(--color-primary); margin-bottom:1.5rem;">Reviews From Our Trekkers</h2>
          
          <div class="glass-card" style="padding:2rem; background:white; border:1px solid var(--border-color); border-radius:var(--radius-lg); margin-bottom:1.5rem;">
            <div style="color:var(--color-secondary); margin-bottom:0.5rem; font-size:1.15rem;">★★★★★</div>
            <p style="font-style:italic; color:var(--text-medium); line-height:1.7; font-size:0.95rem; margin-bottom:1rem;">
              "Everest Base Camp was a lifelong dream of mine. Top Mountain Treks planned everything to perfection. Our guide was extremely careful, carrying a medical pack and checking our oxygen twice a day. The sunrise from Kala Patthar was simply magical."
            </p>
            <strong style="color:var(--color-primary); display:block; font-size:0.9rem;">— Sarah Jenkins, United Kingdom</strong>
          </div>

          <div class="glass-card" style="padding:2rem; background:white; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
            <div style="color:var(--color-secondary); margin-bottom:0.5rem; font-size:1.15rem;">★★★★★</div>
            <p style="font-style:italic; color:var(--text-medium); line-height:1.7; font-size:0.95rem; margin-bottom:1rem;">
              "Mustang has the most unique desert-like canyon landscapes. Since it's a restricted area, we needed an official agency to get our permits. Top Mountain Treks arranged the flights to Jomsom and got our permits in a day. Unbelievable trip!"
            </p>
            <strong style="color:var(--color-primary); display:block; font-size:0.9rem;">— Marcus Müller, Germany</strong>
          </div>
        </div>

      </div>
    </div>
  </section>

  <script>
    const altitudePackages = <?php echo json_encode($simulator_packages); ?>;
    let activeAltitudePackage = 0;

    function getAltitudeProfile(altitude) {
        if (altitude >= 5500) {
            return {
                oxygen: 50,
                pressure: 505,
                zone: "Extreme Altitude Zone",
                safety: "Danger zone. Brief stay only"
            };
        }
        if (altitude >= 4500) {
            return {
                oxygen: 57,
                pressure: 580,
                zone: "Very High Altitude Zone",
                safety: "Caution. Stage climb mandatory"
            };
        }
        if (altitude >= 3400) {
            return {
                oxygen: 67,
                pressure: 680,
                zone: "High Altitude Zone",
                safety: "Rest day strongly recommended"
            };
        }
        if (altitude >= 2500) {
            return {
                oxygen: 75,
                pressure: 750,
                zone: "Moderate Altitude Zone",
                safety: "Watch pace and hydration"
            };
        }
        return {
            oxygen: 90,
            pressure: 880,
            zone: "Low Altitude Zone",
            safety: "Comfortable acclimatization"
        };
    }

    function closestSimulatorDay(pkg, altitude) {
        if (!pkg || !pkg.days || !pkg.days.length) return null;
        return pkg.days.reduce((best, day) => {
            return Math.abs(day.altitude - altitude) < Math.abs(best.altitude - altitude) ? day : best;
        }, pkg.days[0]);
    }

    function renderSimulatorDayTrack(pkg, activeDay) {
        const track = document.getElementById('sim-day-track');
        if (!track || !pkg || !pkg.days) return;
        track.innerHTML = '';
        pkg.days.forEach(day => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'sim-day-chip' + (activeDay && day.day === activeDay.day ? ' active' : '');
            btn.textContent = 'Day ' + day.day;
            btn.addEventListener('click', () => {
                document.getElementById('sim-altitude-range').value = day.altitude;
                updateAltitudeSimulation(day.altitude);
            });
            track.appendChild(btn);
        });
    }

    function changeAltitudePackage(index) {
        activeAltitudePackage = parseInt(index, 10) || 0;
        const pkg = altitudePackages[activeAltitudePackage];
        const range = document.getElementById('sim-altitude-range');
        if (!pkg || !pkg.days || !pkg.days.length || !range) return;
        const values = pkg.days.map(day => day.altitude);
        range.min = Math.max(1000, Math.min(...values) - 300);
        range.max = Math.max(...values) + 300;
        range.value = pkg.days[0].altitude;
        updateAltitudeSimulation(pkg.days[0].altitude);
    }

    function updateAltitudeSimulation(alt) {
        const altitude = parseInt(alt, 10);
        const pkg = altitudePackages[activeAltitudePackage] || altitudePackages[0];
        const day = closestSimulatorDay(pkg, altitude) || {
            day: 1,
            place: "Kathmandu",
            path: "Kathmandu arrival",
            title: "Kathmandu arrival",
            desc: "Safe valley elevation. Oxygen is rich, and air pressure is high."
        };
        const profile = getAltitudeProfile(altitude);

        document.getElementById('sim-alt-val').textContent = altitude.toLocaleString();
        document.getElementById('sim-oxy-val').textContent = profile.oxygen;
        document.getElementById('sim-oxy-desc').textContent = profile.oxygen + "% effective oxygen";
        document.getElementById('sim-zone-desc').textContent = profile.zone;
        document.getElementById('sim-press-desc').textContent = profile.pressure + " hPa (Normal: 1013)";
        document.getElementById('sim-safety-desc').textContent = profile.safety;
        document.getElementById('sim-title').textContent = "Day " + day.day + ": " + day.place + " (Alt. " + altitude.toLocaleString() + " m)";
        document.getElementById('sim-desc').textContent = day.desc || "Follow the planned trail slowly with guide monitoring and proper hydration.";
        document.getElementById('sim-path-desc').textContent = "Day " + day.day + ": " + day.path;
        renderSimulatorDayTrack(pkg, day);
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (altitudePackages.length) {
            changeAltitudePackage(0);
        }
    });
  </script>

  <!-- Need Help WhatsApp CTA Section -->
  <section style="padding: 5rem 0; background-color: var(--color-primary); color: white; border-top: 1px solid var(--border-color);">
    <div class="container" style="max-width: 800px; text-align: center;">
      <span class="card-badge" style="position:static; display:inline-block; margin-bottom:0.75rem; background-color:var(--color-secondary); border:none; color:white;">Instant Support</span>
      <h2 style="font-family: var(--font-heading); font-size: 2.2rem; font-weight: 800; margin-bottom: 0.5rem; color: white;">Have Questions About Booking a Trip?</h2>
      <p style="color: rgba(255,255,255,0.85); margin-bottom: 2.5rem; font-size: 1.05rem;">Submit your details directly to our WhatsApp hotline and receive free travel consultation.</p>
      
      <div class="glass-card" style="padding: 2.5rem; background: white; border: 1px solid var(--border-color); border-radius: var(--radius-lg); text-align: left; max-width: 520px; margin: 0 auto; color: var(--text-dark); box-shadow: var(--shadow-lg);">
        <form onsubmit="sendHomeWhatsAppMessage(event)" style="display:flex; flex-direction:column; gap:1.1rem;">
          <div>
            <label style="font-weight: 700; font-size: 0.85rem; display: block; margin-bottom: 0.25rem;">Your Full Name</label>
            <input type="text" placeholder="e.g. Sarah Connor" required class="form-input wa-home-name" style="height:40px;">
          </div>
          <div>
            <label style="font-weight: 700; font-size: 0.85rem; display: block; margin-bottom: 0.25rem;">Email Address</label>
            <input type="email" placeholder="e.g. sarah@example.com" required class="form-input wa-home-email" style="height:40px;">
          </div>
          <div style="display:flex; gap:0.5rem;">
            <div style="flex: 0.3;">
              <label style="font-weight: 700; font-size: 0.85rem; display: block; margin-bottom: 0.25rem;">Code</label>
              <input type="text" placeholder="+1" required class="form-input wa-home-code" style="height:40px; text-align:center;" value="+977">
            </div>
            <div style="flex: 0.7;">
              <label style="font-weight: 700; font-size: 0.85rem; display: block; margin-bottom: 0.25rem;">Phone Number</label>
              <input type="text" placeholder="e.g. 9851123456" required class="form-input wa-home-phone" style="height:40px;">
            </div>
          </div>
          <div>
            <label style="font-weight: 700; font-size: 0.85rem; display: block; margin-bottom: 0.25rem;">Your Question</label>
            <textarea placeholder="e.g. Is oxygen included on the Everest trek?" required class="form-input wa-home-question" rows="3" style="resize:none; font-family:var(--font-body);"></textarea>
          </div>
          <button type="submit" class="btn btn-primary" style="height:46px; font-size:1rem; width:100%; display:flex; align-items:center; justify-content:center; gap:0.5rem; background-color:#25d366; border-color:#25d366; color:white; font-weight:700; border-radius:var(--radius-sm);">
            💬 Send WhatsApp Inquiry
          </button>
        </form>
      </div>
    </div>
  </section>

  <script>
  function sendHomeWhatsAppMessage(event) {
    event.preventDefault();
    var form = event.target;
    var name = form.querySelector('.wa-home-name').value.trim();
    var email = form.querySelector('.wa-home-email').value.trim();
    var code = form.querySelector('.wa-home-code').value.trim();
    var phone = form.querySelector('.wa-home-phone').value.trim();
    var question = form.querySelector('.wa-home-question').value.trim();
    
    var text = "Hello Top Mountain Treks,\n\n" +
               "I have a general inquiry from the homepage.\n" +
               "Name: " + name + "\n" +
               "Email: " + email + "\n" +
               "Phone: " + code + " " + phone + "\n" +
               "Question: " + question;
               
    var whatsappNum = "<?php echo preg_replace('/[^0-9]/', '', $site_whatsapp); ?>";
    var url = "https://wa.me/" + whatsappNum + "?text=" + encodeURIComponent(text);
    window.open(url, '_blank');
  }
  </script>

<?php include 'includes/footer.php'; ?>
