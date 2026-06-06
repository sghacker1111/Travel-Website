<?php
// Reusable package card template component
// Expects an array variable named $pkg containing package details.
if (isset($pkg) && is_array($pkg)):
    $difficulty_class = '';
    if (isset($pkg['difficulty'])) {
        $diff = strtolower($pkg['difficulty']);
        if (strpos($diff, 'easy') !== false) $difficulty_class = 'easy';
        elseif (strpos($diff, 'moderate') !== false) $difficulty_class = 'moderate';
        elseif (strpos($diff, 'challenging') !== false || strpos($diff, 'strenuous') !== false) $difficulty_class = 'challenging';
    }
    $offer_badge = trim($pkg['offer_badge'] ?? '');
    $group_offer_text = trim($pkg['group_offer_text'] ?? '');
    $offer_end_date = trim($pkg['offer_end_date'] ?? '');
    $old_price_usd = isset($pkg['offer_old_price_usd']) ? (int)$pkg['offer_old_price_usd'] : 0;
?>
<div class="card-wrapper">
  <div class="card-image-box">
    <img src="<?php echo htmlspecialchars($pkg['image']); ?>" alt="<?php echo htmlspecialchars($pkg['title']); ?> landscape scenery" loading="lazy">
    <div class="card-badge"><?php echo htmlspecialchars($pkg['difficulty']); ?></div>
    <?php if (!empty($offer_badge)): ?>
      <div class="card-badge offer-pulse-badge" style="left:auto; right:0.85rem; top:0.85rem; background:#dc2626; color:white; border-color:#dc2626;"><?php echo htmlspecialchars($offer_badge); ?></div>
    <?php endif; ?>
    <div class="card-price-tag">From $<?php echo number_format($pkg['priceUSD']); ?></div>
  </div>
  <div class="card-body">
    <h3 class="card-title"><?php echo htmlspecialchars($pkg['title']); ?></h3>
    <?php if ($old_price_usd > $pkg['priceUSD'] || !empty($group_offer_text) || !empty($offer_end_date)): ?>
      <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center; margin:-0.25rem 0 0.9rem;">
        <?php if ($old_price_usd > $pkg['priceUSD']): ?>
          <span style="font-size:0.82rem; color:var(--text-muted); text-decoration:line-through; font-weight:800;">Was $<?php echo number_format($old_price_usd); ?></span>
        <?php endif; ?>
        <?php if (!empty($group_offer_text)): ?>
          <span class="package-countdown" style="background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8;"><?php echo htmlspecialchars($group_offer_text); ?></span>
        <?php endif; ?>
        <?php if (!empty($offer_end_date)): ?>
          <span class="package-countdown limited-time-text" data-offer-end="<?php echo htmlspecialchars($offer_end_date); ?>">Limited time</span>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <div class="card-meta-ribbon">
      <div class="card-meta-item">
        <svg viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M6 2a1 1 0 00-1-1v1a1 1 0 00-2 0v1H2a2 2 0 00-2 2v11a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2h-1V2a1 1 0 00-2 0v1H5V2zm0 3H2v11h14V5H6z" clip-rule="evenodd"/>
        </svg>
        <span><?php echo htmlspecialchars($pkg['duration']); ?> Days</span>
      </div>
      <?php if (!empty($pkg['maxAltitude'])): ?>
      <div class="card-meta-item">
        <svg viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M5.05 3.636a1 1 0 011.414 0l10 10a1 1 0 010 1.414l-2 2a1 1 0 01-1.414 0l-10-10a1 1 0 010-1.414l2-2z" clip-rule="evenodd"/>
        </svg>
        <span>Max: <?php echo htmlspecialchars(explode(',', $pkg['maxAltitude'])[0]); ?></span>
      </div>
      <?php endif; ?>
    </div>
    <p class="card-description"><?php echo htmlspecialchars($pkg['overview']); ?></p>
    <div class="card-footer">
      <div style="font-size: 0.8rem; color: var(--text-muted); font-weight:600;">
        Category: <span style="text-transform: capitalize; color: var(--color-accent);"><?php echo htmlspecialchars($pkg['category']); ?></span>
      </div>
      <a href="/<?php echo htmlspecialchars($pkg['category'] === 'climbing' ? 'peak-climbing' : $pkg['category']); ?>/<?php echo htmlspecialchars($pkg['slug']); ?>" class="btn btn-primary btn-sm">Explore Details</a>
    </div>
  </div>
</div>
<?php endif; ?>
