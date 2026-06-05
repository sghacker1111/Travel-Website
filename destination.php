<?php
$page_title = "Nepal Travel Destinations | Top Mountain Treks Pvt. Ltd.";
$page_desc = "Explore Nepal's beautiful regions: Everest, Annapurna, Langtang, Manaslu, Mustang and more. Detailed guides and trekking packages for each region.";
include 'includes/header.php';

// Load activities
$activities_path = 'data/activities.json';
$activities = [];
if (file_exists($activities_path)) {
    $activities = json_decode(file_get_contents($activities_path), true);
}
if (!is_array($activities)) $activities = [];

$region_images = [
    'everest-region-trekking' => '/assets/images/everest.png',
    'annapurna-region-trekking' => '/assets/images/annapurna.png',
    'langtang-region-trekking' => '/assets/images/langtang.png',
    'manaslu-region-trekking' => '/assets/images/manaslu.png',
    'mustang-region-trekking' => '/assets/images/packages/upper-mustang.jpg',
    'restricted-area-trekking' => '/assets/images/packages/kanchenjunga-base-camp.jpg',
    'peak-climbing-nepal' => '/assets/images/hero_home.png',
    'nepal-tours-excursions' => '/assets/images/packages/kathmandu-pokhara-chitwan.jpg',
];

$theme_settings = $theme_settings ?? [];
$page_align = isset($theme_settings['page_align']) ? $theme_settings['page_align'] : 'left';
$page_title_color = isset($theme_settings['page_title_color']) ? $theme_settings['page_title_color'] : 'var(--color-primary)';
$page_title_size = isset($theme_settings['page_title_size']) ? $theme_settings['page_title_size'] : '2.8rem';
$page_desc_color = isset($theme_settings['page_desc_color']) ? $theme_settings['page_desc_color'] : 'var(--text-medium)';
$page_desc_size = isset($theme_settings['page_desc_size']) ? $theme_settings['page_desc_size'] : '1.1rem';
?>

  <!-- Header Banner -->
  <section class="section-bg-alternate" style="padding: 4rem 0; border-bottom:1px solid var(--border-color); text-align: <?php echo htmlspecialchars($page_align); ?>;">
    <div class="container">
      <span class="card-badge" style="position:static; display:inline-block; margin-bottom:0.75rem; background-color: var(--color-secondary);">Himalayan Regions</span>
      <h1 style="font-family:var(--font-heading); font-size: <?php echo htmlspecialchars($page_title_size); ?>; font-weight:800; color: <?php echo htmlspecialchars($page_title_color); ?>; margin-bottom:0.5rem;">Explore Nepal Destinations</h1>
      <p style="color: <?php echo htmlspecialchars($page_desc_color); ?>; font-size: <?php echo htmlspecialchars($page_desc_size); ?>; max-width:800px; <?php if ($page_align === 'center') echo 'margin: 0 auto;'; ?>">Choose from our curated Himalayan trekking, tour, and climbing destinations. Start planning your epic journey.</p>
    </div>
  </section>

  <!-- Destinations Grid Section -->
  <section style="padding: 4rem 0; background-color: var(--color-page-bg, #ffffff);">
    <div class="container">
      <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2.5rem;">
        <?php foreach ($activities as $act): 
            $slug = $act['slug'];
            $img = isset($region_images[$slug]) ? $region_images[$slug] : '/assets/images/hero_home.png';
            $category = $act['category'];
            $link = "/trekking/" . htmlspecialchars($slug);
            if ($category === 'tour') {
                $link = "/tour/" . htmlspecialchars($slug);
            } elseif ($category === 'climbing') {
                $link = "/peak-climbing/" . htmlspecialchars($slug);
            }
        ?>
          <div class="glass-card" style="padding: 0; overflow: hidden; border-radius: var(--radius-lg); border: 1px solid var(--border-color); display: flex; flex-direction: column; height: 420px; transition: transform 0.3s ease, box-shadow 0.3s ease; background-color: #ffffff;" onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 12px 30px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
            <div style="height: 200px; overflow: hidden; position: relative;">
              <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($act['name']); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;" class="region-card-image">
              <span class="card-badge" style="position: absolute; top: 15px; left: 15px; text-transform: uppercase; font-weight: 800; font-size: 0.75rem; background-color: var(--color-primary); color: white;"><?php echo htmlspecialchars($category); ?></span>
            </div>
            <div style="padding: 1.5rem; display: flex; flex-direction: column; flex-grow: 1; justify-content: space-between;">
              <div>
                <h3 style="font-family: var(--font-heading); font-size: 1.35rem; color: var(--color-title, var(--color-primary)); margin-bottom: 0.75rem; font-weight: 800;">
                  <?php echo htmlspecialchars($act['name']); ?>
                </h3>
                <p style="color: var(--text-medium); font-size: 0.9rem; line-height: 1.6; margin: 0;">
                  <?php echo htmlspecialchars($act['description']); ?>
                </p>
              </div>
              <div style="margin-top: 1.5rem;">
                <a href="<?php echo $link; ?>" class="btn btn-primary" style="width: 100%; display: block; text-align: center; font-weight: 700; height: 42px; line-height: 42px; padding: 0; text-decoration: none;">Explore Packages</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

<?php include 'includes/footer.php'; ?>
