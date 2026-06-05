<?php
$page_title = "Nepal Trekking Packages | Top Mountain Treks Pvt. Ltd.";
$page_desc = "Explore our selection of dynamic trekking packages in Nepal. Grouped by region (Everest, Annapurna, Langtang, Manaslu, Mustang, restricted areas) to find your dream Himalayan trail.";
include 'includes/header.php';

// Load activities and packages
$activities_path = 'data/activities.json';
$packages_path = 'data/packages.json';

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

// Get filter inputs from query params
$selected_region = isset($_GET['region']) ? $_GET['region'] : 'all';
$selected_difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : 'all';

// Process and filter packages (keep category = trekking)
$filtered_treks = [];
if (is_array($packages)) {
    foreach ($packages as $pkg) {
        if ($pkg['category'] !== 'trekking') {
            continue;
        }

        // Apply Difficulty Filter
        if ($selected_difficulty !== 'all') {
            $diff_slug = strtolower($pkg['difficulty']);
            if ($selected_difficulty === 'easy' && strpos($diff_slug, 'easy') === false) continue;
            if ($selected_difficulty === 'moderate' && strpos($diff_slug, 'moderate') === false) continue;
            if ($selected_difficulty === 'challenging' && strpos($diff_slug, 'challenging') === false && strpos($diff_slug, 'strenuous') === false) continue;
        }

        // Apply Region/Activity Filter (pre-filtering list if specific region selected)
        if ($selected_region !== 'all') {
            $pkg_act = isset($pkg['activity_id']) ? $pkg['activity_id'] : '';
            
            // Map selected region slug to activity ID
            if ($selected_region === 'everest' && $pkg_act !== 'everest-region') continue;
            if ($selected_region === 'annapurna' && $pkg_act !== 'annapurna-region') continue;
            if ($selected_region === 'langtang' && $pkg_act !== 'langtang-region') continue;
            if ($selected_region === 'manaslu' && $pkg_act !== 'manaslu-region') continue;
            if ($selected_region === 'mustang' && $pkg_act !== 'mustang-region') continue;
            if ($selected_region === 'restricted' && $pkg_act !== 'restricted-areas') continue;
        }

        $filtered_treks[] = $pkg;
    }
}
?>

  <!-- Header Banner -->
  <section class="section-bg-alternate" style="padding: 3rem 0; border-bottom:1px solid var(--border-color); text-align: var(--trek-align, left);">
    <div class="container">
      <h1 style="font-family:var(--font-heading); font-size: var(--trek-title-size, 2.5rem); font-weight:800; color: var(--trek-title-color, var(--color-primary)); margin-bottom:0.5rem;">Trekking Packages in Nepal</h1>
      <p style="color: var(--trek-desc-color, var(--text-medium)); font-size: var(--trek-desc-size, 1.05rem); <?php if (($theme_settings['trek_align'] ?? 'left') === 'center') echo 'margin: 0 auto;'; ?>">Explore the roof of the world. Select your preferred mountain region and hiking difficulty.</p>
    </div>
  </section>

  <!-- Filter & Listing Section -->
  <section>
    <div class="container">
      
      <!-- Filter Controls Form -->
      <form method="GET" action="/trekking" class="form-card" style="padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 3.5rem; display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-end;">
        <div class="form-group" style="margin-bottom:0; min-width:200px; flex-grow:1;">
          <label for="region">Filter by Region</label>
          <select name="region" id="region" class="form-input">
            <option value="all" <?php echo $selected_region === 'all' ? 'selected' : ''; ?>>All Regions</option>
            <option value="everest" <?php echo $selected_region === 'everest' ? 'selected' : ''; ?>>Everest / Khumbu</option>
            <option value="annapurna" <?php echo $selected_region === 'annapurna' ? 'selected' : ''; ?>>Annapurna Region</option>
            <option value="langtang" <?php echo $selected_region === 'langtang' ? 'selected' : ''; ?>>Langtang Valley</option>
            <option value="manaslu" <?php echo $selected_region === 'manaslu' ? 'selected' : ''; ?>>Manaslu Region</option>
            <option value="mustang" <?php echo $selected_region === 'mustang' ? 'selected' : ''; ?>>Mustang Region</option>
            <option value="restricted" <?php echo $selected_region === 'restricted' ? 'selected' : ''; ?>>Restricted / Remote Areas</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0; min-width:200px; flex-grow:1;">
          <label for="difficulty">Filter by Difficulty</label>
          <select name="difficulty" id="difficulty" class="form-input">
            <option value="all" <?php echo $selected_difficulty === 'all' ? 'selected' : ''; ?>>All Difficulties</option>
            <option value="easy" <?php echo $selected_difficulty === 'easy' ? 'selected' : ''; ?>>Easy to Moderate</option>
            <option value="moderate" <?php echo $selected_difficulty === 'moderate' ? 'selected' : ''; ?>>Moderate</option>
            <option value="challenging" <?php echo $selected_difficulty === 'challenging' ? 'selected' : ''; ?>>Challenging / Strenuous</option>
          </select>
        </div>

        <div style="display:flex; gap:0.75rem;">
          <button type="submit" class="btn btn-primary" style="height:44px;">Apply Filters</button>
          <a href="trekking.php" class="btn btn-outline" style="height:44px; padding: 0.85rem 1.25rem;">Reset</a>
        </div>
      </form>

      <!-- Packages Grouped separately by Region Subtitle -->
      <?php if (empty($filtered_treks)): ?>
        <div style="text-align:center; padding:5rem 0;">
          <svg viewBox="0 0 20 20" fill="currentColor" width="48" height="48" style="color:var(--text-muted); margin-bottom:1rem;">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
          <h3 style="font-family:var(--font-heading); font-size:1.5rem; margin-bottom:0.5rem; color:var(--color-primary);">No Trekking Packages Found</h3>
          <p style="color:var(--text-medium);">Try adjusting your filter options above.</p>
        </div>
      <?php else: ?>
        
        <?php
        // Group filtered packages by activity_id
        $grouped_treks = [];
        $other_treks = [];
        
        foreach ($filtered_treks as $pkg) {
            $act_id = isset($pkg['activity_id']) ? $pkg['activity_id'] : '';
            if (empty($act_id)) {
                $other_treks[] = $pkg;
            } else {
                $grouped_treks[$act_id][] = $pkg;
            }
        }
        
        // Loop through trekking activities to render grouped regions
        $rendered_groups = 0;
        foreach ($activities as $act) {
            if ($act['category'] !== 'trekking') continue;
            
            $act_id = $act['id'];
            if (!isset($grouped_treks[$act_id]) || empty($grouped_treks[$act_id])) {
                continue;
            }
            $rendered_groups++;
            ?>
            <div class="region-group-section" style="margin-bottom: 4rem;">
                <h2 style="font-family:var(--font-heading); font-size:1.8rem; font-weight:800; color:var(--color-primary); margin-bottom:1.5rem; border-bottom:2px solid var(--color-secondary); padding-bottom:0.5rem; display:inline-block;">
                    <?php echo htmlspecialchars($act['name']); ?>
                </h2>
                <div class="grid grid-3">
                    <?php 
                    foreach ($grouped_treks[$act_id] as $pkg) {
                        include 'includes/package-card.php';
                    }
                    ?>
                </div>
            </div>
            <?php
        }
        
        // Render uncategorized trekking packages
        if (!empty($other_treks)):
            $rendered_groups++;
            ?>
            <div class="region-group-section" style="margin-bottom: 4rem;">
                <h2 style="font-family:var(--font-heading); font-size:1.8rem; font-weight:800; color:var(--color-primary); margin-bottom:1.5rem; border-bottom:2px solid var(--color-secondary); padding-bottom:0.5rem; display:inline-block;">
                    Other Trekking Destinations
                </h2>
                <div class="grid grid-3">
                    <?php 
                    foreach ($other_treks as $pkg) {
                        include 'includes/package-card.php';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </section>

<?php include 'includes/footer.php'; ?>