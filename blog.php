<?php
$page_title = "Travel Blog & Adventure Guide | Top Mountain Treks Pvt. Ltd.";
$page_desc = "Read our latest Himalayan travel guidelines, preparation tips, safety resources, and climbing advisories written by experienced local guides.";
include 'includes/header.php';

// Load blog posts
$blogs_path = 'data/blogs.json';
$all_blogs = [];
if (file_exists($blogs_path)) {
    $all_blogs = json_decode(file_get_contents($blogs_path), true);
}
if (!is_array($all_blogs)) $all_blogs = [];
?>

  <!-- Header Banner -->
  <section class="section-bg-alternate" style="padding: 4rem 0; border-bottom:1px solid var(--border-color); text-align: var(--blog-align, left);">
    <div class="container">
      <span class="card-badge" style="position:static; display:inline-block; margin-bottom:0.75rem; background-color: var(--color-secondary);">Adventure Journal</span>
      <h1 style="font-family:var(--font-heading); font-size: var(--blog-title-size, 2.8rem); font-weight:800; color: var(--blog-title-color, var(--color-primary)); margin-bottom:0.5rem;">Himalayan Travel Blog</h1>
      <p style="color: var(--blog-desc-color, var(--text-medium)); font-size: var(--blog-desc-size, 1.1rem); max-width:800px; <?php if (($theme_settings['blog_align'] ?? 'left') === 'center') echo 'margin: 0 auto;'; ?>">Expert tips, packing guides, and training checklists directly from our local Sherpa guides.</p>
    </div>
  </section>

  <!-- Blog Listings Grid -->
  <section>
    <div class="container">
      <?php if (empty($all_blogs)): ?>
        <div style="text-align:center; padding:5rem 0; color:var(--text-muted);">
          <h3>No Blog Articles Published Yet</h3>
          <p>Please check back soon for our latest Himalayan guides.</p>
        </div>
      <?php else: ?>
        <div class="grid grid-3" style="gap:2.5rem;">
          <?php foreach ($all_blogs as $post): ?>
            <article class="glass-card" style="border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; display: flex; flex-direction: column; background: white; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='var(--shadow-lg)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
              <?php if (!empty($post['image'])): ?>
                <div style="height: 220px; overflow: hidden; position: relative;">
                  <img src="/<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
              <?php endif; ?>
              
              <div style="padding: 1.5rem; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; gap: 1rem;">
                <div>
                  <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; display: block; margin-bottom: 0.5rem; text-transform: uppercase;">
                    Published on: <?php echo htmlspecialchars($post['date']); ?>
                  </span>
                  <h3 style="font-family: var(--font-heading); font-size: 1.2rem; line-height: 1.4; color: var(--color-primary); margin-bottom: 0.5rem; font-weight: 800;">
                    <?php echo htmlspecialchars($post['title']); ?>
                  </h3>
                  <p style="font-size: 0.88rem; color: var(--text-medium); line-height: 1.6;">
                    <?php 
                    $desc = strip_tags($post['content']);
                    echo htmlspecialchars(substr($desc, 0, 140)) . '...';
                    ?>
                  </p>
                </div>
                
                <a href="/blog/<?php echo htmlspecialchars($post['slug']); ?>" class="btn btn-outline btn-sm" style="align-self: flex-start; text-decoration:none; font-weight:700;">
                  Read Article →
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

<?php include 'includes/footer.php'; ?>