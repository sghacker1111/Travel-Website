<?php
$page_title = "Nepal Tour Packages | Top Mountain Treks Pvt. Ltd.";
$page_desc = "Explore our selection of dynamic leisure and wildlife tour packages in Nepal. Visit historical UNESCO Kathmandu heritage sites, lakeside Pokhara, and Chitwan safari parks.";
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

// Filter activities that belong to 'tour'
$tour_activities = [];
foreach ($activities as $act) {
    if ($act['category'] === 'tour') {
        $tour_activities[] = $act;
    }
}

// Get filter inputs from query params
$selected_activity = isset($_GET['activity']) ? $_GET['activity'] : 'all';
$selected_region = isset($_GET['region']) ? $_GET['region'] : 'all';

// Process and filter packages (keep category = tour)
$filtered_tours = [];
$unique_regions = [];

if (is_array($packages)) {
    foreach ($packages as $pkg) {
        if ($pkg['category'] !== 'tour') {
            continue;
        }

        // Collect regions for the dynamic region filter
        if (!empty($pkg['region']) && !in_array($pkg['region'], $unique_regions)) {
            $unique_regions[] = $pkg['region'];
        }

        // Apply Activity Filter
        if ($selected_activity !== 'all' && (!isset($pkg['activity_id']) || $pkg['activity_id'] !== $selected_activity)) {
            continue;
        }

        // Apply Region Filter
        if ($selected_region !== 'all' && (empty($pkg['region']) || $pkg['region'] !== $selected_region)) {
            continue;
        }

        $filtered_tours[] = $pkg;
    }
}
?>

  <!-- Header Banner -->
  <section class="section-bg-alternate" style="padding: 3rem 0; border-bottom:1px solid var(--border-color); text-align: var(--tour-align, left);">
    <div class="container">
      <h1 style="font-family:var(--font-heading); font-size: var(--tour-title-size, 2.5rem); font-weight:800; color: var(--tour-title-color, var(--color-primary)); margin-bottom:0.5rem;">Nepal Tour Packages</h1>
      <p style="color: var(--tour-desc-color, var(--text-medium)); font-size: var(--tour-desc-size, 1.05rem); <?php if (($theme_settings['tour_align'] ?? 'left') === 'center') echo 'margin: 0 auto;'; ?>">Explore Nepal's cultural heritage, natural lakeside views, and deep jungle safari adventures.</p>
    </div>
  </section>

  <!-- Filter & Listing Section -->
  <section>
    <div class="container">
      
      <!-- Filter Controls Form -->
      <form method="GET" action="/tour" class="form-card" style="padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 3.5rem; display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-end;">
        <div class="form-group" style="margin-bottom:0; min-width:200px; flex-grow:1;">
          <label for="activity">Filter by Tour Type</label>
          <select name="activity" id="activity" class="form-input">
            <option value="all" <?php echo $selected_activity === 'all' ? 'selected' : ''; ?>>All Tour Types</option>
            <?php foreach ($tour_activities as $act): ?>
              <option value="<?php echo htmlspecialchars($act['id']); ?>" <?php echo $selected_activity === $act['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($act['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0; min-width:200px; flex-grow:1;">
          <label for="region">Filter by Region</label>
          <select name="region" id="region" class="form-input">
            <option value="all" <?php echo $selected_region === 'all' ? 'selected' : ''; ?>>All Regions</option>
            <?php foreach ($unique_regions as $reg): ?>
              <option value="<?php echo htmlspecialchars($reg); ?>" <?php echo $selected_region === $reg ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($reg); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:flex; gap:0.75rem;">
          <button type="submit" class="btn btn-primary" style="height:44px;">Apply Filters</button>
          <a href="tour.php" class="btn btn-outline" style="height:44px; padding: 0.85rem 1.25rem;">Reset</a>
        </div>
      </form>

      <!-- Packages Grouped separately by Activity Subtitle -->
      <?php if (empty($filtered_tours)): ?>
        <div style="text-align:center; padding:5rem 0;">
          <svg viewBox="0 0 20 20" fill="currentColor" width="48" height="48" style="color:var(--text-muted); margin-bottom:1rem;">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
          <h3 style="font-family:var(--font-heading); font-size:1.5rem; margin-bottom:0.5rem; color:var(--color-primary);">No Tour Packages Found</h3>
          <p style="color:var(--text-medium);">Try adjusting your filter options above.</p>
        </div>
      <?php else: ?>
        
        <?php
        // Group filtered packages by activity_id
        $grouped_tours = [];
        $other_tours = [];
        
        foreach ($filtered_tours as $pkg) {
            $act_id = isset($pkg['activity_id']) ? $pkg['activity_id'] : '';
            $is_known_act = false;
            foreach ($tour_activities as $act) {
                if ($act['id'] === $act_id) {
                    $is_known_act = true;
                    break;
                }
            }
            
            if (empty($act_id) || !$is_known_act) {
                $other_tours[] = $pkg;
            } else {
                $grouped_tours[$act_id][] = $pkg;
            }
        }
        
        // Loop through tour activities to render grouped regions
        $rendered_groups = 0;
        foreach ($tour_activities as $act) {
            $act_id = $act['id'];
            if (!isset($grouped_tours[$act_id]) || empty($grouped_tours[$act_id])) {
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
                    foreach ($grouped_tours[$act_id] as $pkg) {
                        include 'includes/package-card.php';
                    }
                    ?>
                </div>
            </div>
            <?php
        }
        
        // Render uncategorized tour packages
        if (!empty($other_tours)):
            $rendered_groups++;
            ?>
            <div class="region-group-section" style="margin-bottom: 4rem;">
                <h2 style="font-family:var(--font-heading); font-size:1.8rem; font-weight:800; color:var(--color-primary); margin-bottom:1.5rem; border-bottom:2px solid var(--color-secondary); padding-bottom:0.5rem; display:inline-block;">
                    Other Tour Packages
                </h2>
                <div class="grid grid-3">
                    <?php 
                    foreach ($other_tours as $pkg) {
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