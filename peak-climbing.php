<?php
$page_title = "Peak Climbing in Nepal | Top Mountain Treks Pvt. Ltd.";
$page_desc = "Expeditions to scale snow-capped summits between 5,500m and 6,500m in Nepal. Guided by expert local Sherpas with safety-first equipment, TIMS permits, and logistics support.";
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

// Filter activities of category 'climbing'
$climbing_activities = [];
foreach ($activities as $act) {
    if ($act['category'] === 'climbing') {
        $climbing_activities[] = $act;
    }
}

// Get filter inputs from query params
$selected_activity = isset($_GET['activity']) ? $_GET['activity'] : 'all';
$selected_difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : 'all';

// Process and filter packages (keep category = climbing)
$filtered_climbs = [];
if (is_array($packages)) {
    foreach ($packages as $pkg) {
        if ($pkg['category'] !== 'climbing') {
            continue;
        }

        // Apply Difficulty Filter
        if ($selected_difficulty !== 'all') {
            $diff_slug = strtolower($pkg['difficulty']);
            if ($selected_difficulty === 'easy' && strpos($diff_slug, 'easy') === false) continue;
            if ($selected_difficulty === 'moderate' && strpos($diff_slug, 'moderate') === false) continue;
            if ($selected_difficulty === 'challenging' && strpos($diff_slug, 'challenging') === false && strpos($diff_slug, 'strenuous') === false) continue;
        }

        // Apply Activity Filter
        if ($selected_activity !== 'all' && (!isset($pkg['activity_id']) || $pkg['activity_id'] !== $selected_activity)) {
            continue;
        }

        $filtered_climbs[] = $pkg;
    }
}
?>

  <!-- Header Banner -->
  <section class="section-bg-alternate" style="padding: 3rem 0; border-bottom:1px solid var(--border-color); text-align: var(--climb-align, left);">
    <div class="container">
      <h1 style="font-family:var(--font-heading); font-size: var(--climb-title-size, 2.5rem); font-weight:800; color: var(--climb-title-color, var(--color-primary)); margin-bottom:0.5rem;">Peak Climbing in Nepal</h1>
      <p style="color: var(--climb-desc-color, var(--text-medium)); font-size: var(--climb-desc-size, 1.05rem); <?php if (($theme_settings['climb_align'] ?? 'left') === 'center') echo 'margin: 0 auto;'; ?>">Scale the snow-capped summits of the Himalayas. Guided by professional NMA climbing Sherpas.</p>
    </div>
  </section>

  <!-- Filter & Listing Section -->
  <section>
    <div class="container">
      
      <!-- Filter Controls Form -->
      <form method="GET" action="/peak-climbing" class="form-card" style="padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 3.5rem; display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-end;">
        <div class="form-group" style="margin-bottom:0; min-width:200px; flex-grow:1;">
          <label for="activity">Filter by Climbing Category</label>
          <select name="activity" id="activity" class="form-input">
            <option value="all" <?php echo $selected_activity === 'all' ? 'selected' : ''; ?>>All Categories</option>
            <?php foreach ($climbing_activities as $act): ?>
              <option value="<?php echo htmlspecialchars($act['id']); ?>" <?php echo $selected_activity === $act['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($act['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0; min-width:200px; flex-grow:1;">
          <label for="difficulty">Filter by Difficulty</label>
          <select name="difficulty" id="difficulty" class="form-input">
            <option value="all" <?php echo $selected_difficulty === 'all' ? 'selected' : ''; ?>>All Difficulties</option>
            <option value="moderate" <?php echo $selected_difficulty === 'moderate' ? 'selected' : ''; ?>>Moderate Peaks</option>
            <option value="challenging" <?php echo $selected_difficulty === 'challenging' ? 'selected' : ''; ?>>Challenging Peaks</option>
          </select>
        </div>

        <div style="display:flex; gap:0.75rem;">
          <button type="submit" class="btn btn-primary" style="height:44px;">Apply Filters</button>
          <a href="peak-climbing.php" class="btn btn-outline" style="height:44px; padding: 0.85rem 1.25rem;">Reset</a>
        </div>
      </form>

      <!-- Packages Grouped separately by Activity Subtitle -->
      <?php if (empty($filtered_climbs)): ?>
        <div style="text-align:center; padding:5rem 0;">
          <svg viewBox="0 0 20 20" fill="currentColor" width="48" height="48" style="color:var(--text-muted); margin-bottom:1rem;">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
          <h3 style="font-family:var(--font-heading); font-size:1.5rem; margin-bottom:0.5rem; color:var(--color-primary);">No Climbing Packages Found</h3>
          <p style="color:var(--text-medium);">Try adjusting your filter options above.</p>
        </div>
      <?php else: ?>
        
        <?php
        // Group filtered packages by activity_id
        $grouped_climbs = [];
        $other_climbs = [];
        
        foreach ($filtered_climbs as $pkg) {
            $act_id = isset($pkg['activity_id']) ? $pkg['activity_id'] : '';
            $is_known_act = false;
            foreach ($climbing_activities as $act) {
                if ($act['id'] === $act_id) {
                    $is_known_act = true;
                    break;
                }
            }
            
            if (empty($act_id) || !$is_known_act) {
                $other_climbs[] = $pkg;
            } else {
                $grouped_climbs[$act_id][] = $pkg;
            }
        }
        
        // Loop through climbing activities to render grouped regions
        $rendered_groups = 0;
        foreach ($climbing_activities as $act) {
            $act_id = $act['id'];
            if (!isset($grouped_climbs[$act_id]) || empty($grouped_climbs[$act_id])) {
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
                    foreach ($grouped_climbs[$act_id] as $pkg) {
                        include 'includes/package-card.php';
                    }
                    ?>
                </div>
            </div>
            <?php
        }
        
        // Render uncategorized climbing packages
        if (!empty($other_climbs)):
            $rendered_groups++;
            ?>
            <div class="region-group-section" style="margin-bottom: 4rem;">
                <h2 style="font-family:var(--font-heading); font-size:1.8rem; font-weight:800; color:var(--color-primary); margin-bottom:1.5rem; border-bottom:2px solid var(--color-secondary); padding-bottom:0.5rem; display:inline-block;">
                    Other Peak Climbing Expeditions
                </h2>
                <div class="grid grid-3">
                    <?php 
                    foreach ($other_climbs as $pkg) {
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