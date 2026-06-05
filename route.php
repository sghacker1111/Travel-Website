<?php
// Main URL Route Resolver - Top Mountain Treks
$type = isset($_GET['type']) ? $_GET['type'] : '';
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($type) || empty($slug)) {
    header("Location: /index.php");
    exit;
}

// Route resolvers based on category type
if (in_array($type, ['trekking', 'tour', 'climbing', 'peak-climbing'])) {
    // First check if this slug is a package
    $is_package = false;
    $packages_data = file_exists('data/packages.json') ? json_decode(file_get_contents('data/packages.json'), true) : [];
    if (is_array($packages_data)) {
        foreach ($packages_data as $pkg) {
            if ($pkg['slug'] === $slug) {
                $is_package = true;
                break;
            }
        }
    }

    if ($is_package) {
        $_GET['slug'] = $slug;
        include 'package.php';
        exit;
    }

    // If it's not a package, check if it's an activity
    $is_activity = false;
    $current_act = null;
    $activities_path = 'data/activities.json';
    if (file_exists($activities_path)) {
        $activities_data = json_decode(file_get_contents($activities_path), true);
        if (is_array($activities_data)) {
            foreach ($activities_data as $act) {
                if ($act['slug'] === $slug) {
                    $is_activity = true;
                    $current_act = $act;
                    break;
                }
            }
        }
    }

    if ($is_activity && $current_act) {
        // Resolve Activity/Region archive lists
        $matching_packages = [];
        if (is_array($packages_data)) {
            foreach ($packages_data as $pkg) {
                if (isset($pkg['activity_id']) && $pkg['activity_id'] === $current_act['id']) {
                    $matching_packages[] = $pkg;
                }
            }
        }

        $page_title = $current_act['name'] . " | Top Mountain Treks";
        $page_desc = $current_act['description'];
        include 'includes/header.php';
        ?>
        <?php
        $prefix = 'trek';
        if ($type === 'tour') $prefix = 'tour';
        elseif ($type === 'climbing' || $type === 'peak-climbing') $prefix = 'climb';
        ?>
        <section class="section-bg-alternate" style="padding: 3rem 0; border-bottom:1px solid var(--border-color); text-align: var(--<?php echo $prefix; ?>-align, left);">
            <div class="container">
                <h1 style="font-family:var(--font-heading); font-size: var(--<?php echo $prefix; ?>-title-size, 2.5rem); font-weight:800; color: var(--<?php echo $prefix; ?>-title-color, var(--color-primary)); margin-bottom:0.5rem;"><?php echo htmlspecialchars($current_act['name']); ?></h1>
                <p style="color: var(--<?php echo $prefix; ?>-desc-color, var(--text-medium)); font-size: var(--<?php echo $prefix; ?>-desc-size, 1.05rem);"><?php echo htmlspecialchars($current_act['description']); ?></p>
            </div>
        </section>
        <section>
            <div class="container" style="padding: 3rem 0;">
                <?php if (empty($matching_packages)): ?>
                    <div style="text-align:center; padding:5rem 0;">
                      <h3 style="font-family:var(--font-heading); font-size:1.5rem; margin-bottom:0.5rem; color:var(--color-primary);">No Packages Found</h3>
                      <p style="color:var(--text-medium);">We are currently preparing custom itineraries for this region. Please contact us directly.</p>
                    </div>
                <?php else: ?>
                    <!-- Quick Region Package Navigation Index (Req 6) -->
                    <div style="margin-bottom: 2.5rem; padding: 1.5rem; background: var(--bg-light); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                        <h3 style="font-family:var(--font-heading); font-size:1.1rem; font-weight:800; color:var(--color-primary); margin-top:0; margin-bottom:1rem; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem;">Explore Regional Expedition Itineraries:</h3>
                        <div style="display:flex; flex-wrap:wrap; gap:1.25rem;">
                            <?php foreach ($matching_packages as $pkg): ?>
                                <a href="/<?php echo htmlspecialchars($type === 'climbing' ? 'peak-climbing' : $type); ?>/<?php echo htmlspecialchars($pkg['slug']); ?>" style="font-weight:800; font-size:1.05rem; color: var(--color-secondary); text-decoration:underline; display:inline-flex; align-items:center; gap:0.25rem; transition: all 0.2s;" onmouseover="this.style.color='var(--color-primary)'; this.style.transform='translateX(3px)';" onmouseout="this.style.color='var(--color-secondary)'; this.style.transform='none';">
                                    🏔️ <?php echo htmlspecialchars($pkg['title']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="grid grid-3">
                        <?php 
                        foreach ($matching_packages as $pkg) {
                            include 'includes/package-card.php';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        include 'includes/footer.php';
        exit;
    }

    // Fallback to home page if not found
    header("Location: /index.php");
    exit;
} elseif ($type === 'page') {
    // 2. Resolve Static Pages (Loads pages.json)
    $pages_path = 'data/pages.json';
    $current_page = null;
    if (file_exists($pages_path)) {
        $pages = json_decode(file_get_contents($pages_path), true);
        if (is_array($pages)) {
            foreach ($pages as $p) {
                if ($p['slug'] === $slug) {
                    $current_page = $p;
                    break;
                }
            }
        }
    }

    if (!$current_page) {
        header("HTTP/1.0 404 Not Found");
        include 'index.php'; // Fallback
        exit;
    }

    $page_title = $current_page['seo_title'] ?: $current_page['title'] . " | Top Mountain Treks";
    $page_desc = $current_page['seo_description'] ?: strip_tags($current_page['content']);
    include 'includes/header.php';
    ?>
    <section class="section-bg-alternate" style="padding: 3rem 0; border-bottom:1px solid var(--border-color); text-align: var(--page-align, left);">
        <div class="container">
            <h1 style="font-family:var(--font-heading); font-size: var(--page-title-size, 2.5rem); font-weight:800; color: var(--page-title-color, var(--color-primary)); margin-bottom:0.5rem;"><?php echo htmlspecialchars($current_page['title']); ?></h1>
        </div>
    </section>
    <section>
        <div class="container" style="max-width: 800px; line-height: 1.8; color: var(--text-medium); padding: 3rem 0;">
            <?php echo $current_page['content']; ?>
        </div>
    </section>
    <?php
    include 'includes/footer.php';
    exit;
} elseif ($type === 'blog') {
    // 3. Resolve Blog Posts (Loads blogs.json)
    $blogs_path = 'data/blogs.json';
    $current_blog = null;
    $all_blogs = [];
    if (file_exists($blogs_path)) {
        $all_blogs = json_decode(file_get_contents($blogs_path), true);
        if (is_array($all_blogs)) {
            foreach ($all_blogs as $b) {
                if ($b['slug'] === $slug) {
                    $current_blog = $b;
                    break;
                }
            }
        }
    }

    if (!$current_blog) {
        header("HTTP/1.0 404 Not Found");
        include 'index.php';
        exit;
    }

    $page_title = $current_blog['seo_title'] ?: $current_blog['title'] . " | Top Mountain Treks Blog";
    $page_desc = $current_blog['seo_description'] ?: strip_tags($current_blog['content']);
    include 'includes/header.php';
    ?>
    <section class="section-bg-alternate" style="padding: 3rem 0; border-bottom:1px solid var(--border-color); text-align: var(--blog-align, left);">
        <div class="container">
            <span class="card-badge" style="position:static; display:inline-block; margin-bottom:0.75rem; background-color: var(--color-secondary);">Blog Article</span>
            <h1 style="font-family:var(--font-heading); font-size: var(--blog-title-size, 2.5rem); font-weight:800; color: var(--blog-title-color, var(--color-primary)); margin-bottom:0.5rem;"><?php echo htmlspecialchars($current_blog['title']); ?></h1>
            <span style="font-size: var(--blog-desc-size, 0.85rem); color: var(--blog-desc-color, var(--text-muted));">Published on: <?php echo htmlspecialchars($current_blog['date']); ?> | By Top Mountain Guides</span>
        </div>
    </section>
    <section>
        <div class="container" style="padding: 3rem 0;">
            <div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 3rem;" id="blog-detail-columns">
                <!-- Main Blog Content -->
                <div style="line-height: 1.8; color: var(--text-medium);">
                    <?php if (!empty($current_blog['image'])): ?>
                        <div style="height: 380px; border-radius: var(--radius-lg); overflow:hidden; margin-bottom: 2rem;">
                            <img src="/<?php echo htmlspecialchars($current_blog['image']); ?>" alt="<?php echo htmlspecialchars($current_blog['title']); ?>" style="width:100%; height:100%; object-fit:cover;">
                        </div>
                    <?php endif; ?>
                    <div class="blog-rich-content">
                        <?php echo $current_blog['content']; ?>
                    </div>
                </div>
                <!-- Sidebar Recents -->
                <div>
                    <div class="form-card" style="padding: 1.5rem; position: sticky; top: 100px;">
                        <h3 style="font-family:var(--font-heading); font-size:1.15rem; color:var(--color-primary); margin-bottom:1rem; border-bottom:1.5px solid var(--border-color); padding-bottom:0.5rem;">Recent Articles</h3>
                        <div style="display:flex; flex-direction:column; gap:1rem;">
                            <?php foreach ($all_blogs as $rb): if ($rb['slug'] === $slug) continue; ?>
                                <div>
                                    <a href="/blog/<?php echo htmlspecialchars($rb['slug']); ?>" style="font-weight:700; font-size:0.9rem; color:var(--color-primary); text-decoration:none; display:block; line-height:1.4; margin-bottom:0.25rem;"><?php echo htmlspecialchars($rb['title']); ?></a>
                                    <span style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($rb['date']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
    include 'includes/footer.php';
    exit;
} elseif ($type === 'activity') {
    // 4. Resolve Activity/Region archive lists
    $activities_path = 'data/activities.json';
    $current_act = null;
    if (file_exists($activities_path)) {
        $activities = json_decode(file_get_contents($activities_path), true);
        if (is_array($activities)) {
            foreach ($activities as $a) {
                if ($a['slug'] === $slug) {
                    $current_act = $a;
                    break;
                }
            }
        }
    }

    if (!$current_act) {
        header("HTTP/1.0 404 Not Found");
        include 'index.php';
        exit;
    }

    // Get all matching packages
    $packages_path = 'data/packages.json';
    $matching_packages = [];
    if (file_exists($packages_path)) {
        $packages_data = json_decode(file_get_contents($packages_path), true);
        if (is_array($packages_data)) {
            foreach ($packages_data as $pkg) {
                if (isset($pkg['activity_id']) && $pkg['activity_id'] === $current_act['id']) {
                    $matching_packages[] = $pkg;
                }
            }
        }
    }

    $page_title = $current_act['name'] . " | Top Mountain Treks";
    $page_desc = $current_act['description'];
    include 'includes/header.php';
    ?>
    <?php
    $prefix = 'trek';
    if ($current_act['category'] === 'tour') $prefix = 'tour';
    elseif ($current_act['category'] === 'climbing') $prefix = 'climb';
    ?>
    <section class="section-bg-alternate" style="padding: 3rem 0; border-bottom:1px solid var(--border-color); text-align: var(--<?php echo $prefix; ?>-align, left);">
        <div class="container">
            <h1 style="font-family:var(--font-heading); font-size: var(--<?php echo $prefix; ?>-title-size, 2.5rem); font-weight:800; color: var(--<?php echo $prefix; ?>-title-color, var(--color-primary)); margin-bottom:0.5rem;"><?php echo htmlspecialchars($current_act['name']); ?></h1>
            <p style="color: var(--<?php echo $prefix; ?>-desc-color, var(--text-medium)); font-size: var(--<?php echo $prefix; ?>-desc-size, 1.05rem);"><?php echo htmlspecialchars($current_act['description']); ?></p>
        </div>
    </section>
    <section>
        <div class="container" style="padding: 3rem 0;">
            <?php if (empty($matching_packages)): ?>
                <div style="text-align:center; padding:5rem 0;">
                  <h3 style="font-family:var(--font-heading); font-size:1.5rem; margin-bottom:0.5rem; color:var(--color-primary);">No Packages Found</h3>
                  <p style="color:var(--text-medium);">We are currently preparing custom itineraries for this region. Please contact us directly.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-3">
                    <?php 
                    foreach ($matching_packages as $pkg) {
                        include 'includes/package-card.php';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    include 'includes/footer.php';
    exit;
} else {
    header("Location: /index.php");
    exit;
}