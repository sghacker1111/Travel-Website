<?php
// Load dynamic footer settings
$settings_path = __DIR__ . '/../data/settings.json';
$site_name = "Top Mountain Treks Pvt. Ltd.";
$site_logo = "assets/images/logo.png";
$site_whatsapp = "+977 985-1123456";
$footer_icons = [];
$footer_custom_html = "";

if (file_exists($settings_path)) {
    $set_data = json_decode(file_get_contents($settings_path), true);
    if (is_array($set_data)) {
        if (!empty($set_data['company_name'])) $site_name = $set_data['company_name'];
        if (!empty($set_data['logo_path'])) $site_logo = $set_data['logo_path'];
        if (!empty($set_data['whatsapp_number'])) $site_whatsapp = $set_data['whatsapp_number'];
        if (!empty($set_data['footer_icons']) && is_array($set_data['footer_icons'])) {
            $footer_icons = $set_data['footer_icons'];
        }
        if (!empty($set_data['footer_custom_html'])) $footer_custom_html = $set_data['footer_custom_html'];
    }
}
?>
  <!-- Footer -->
  <footer>
    <div class="container">
      <div class="footer-top">
        <div class="footer-brand">
          <a href="/index.php" class="logo-wrapper" style="margin-bottom:1.5rem; display:inline-flex; align-items:center; text-decoration:none;">
            <img src="/<?php echo htmlspecialchars($site_logo); ?>" alt="<?php echo htmlspecialchars($site_name); ?> Logo" style="height:36px; width:auto; object-fit:contain; margin-right:0.5rem;" onerror="this.style.display='none'">
            <div class="logo-text" style="color:white; line-height:1.2; font-family:var(--font-heading); font-weight:800; font-size:1.1rem;">
              <?php echo htmlspecialchars($site_name); ?>
            </div>
          </a>
          <p><?php echo htmlspecialchars($site_name); ?> is a local government-registered trekking agency operating out of Thamel, Kathmandu. We design premium high-altitude expeditions, cultural heritage tours, and wildlife safaris across Nepal.</p>
        </div>

        <div>
          <h4 class="footer-title">Trekking Regions</h4>
          <ul class="footer-links">
            <li><a href="/trekking/everest-base-camp-trek" class="footer-link">Everest Region</a></li>
            <li><a href="/trekking/annapurna-circuit-trek" class="footer-link">Annapurna Circuit</a></li>
            <li><a href="/trekking/langtang-valley-trek" class="footer-link">Langtang Valley</a></li>
            <li><a href="/trekking/manaslu-circuit-trek" class="footer-link">Manaslu Circuit</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-title">Helpful Resources</h4>
          <ul class="footer-links">
            <li><a href="/page/about-us" class="footer-link">About Our Company</a></li>
            <li><a href="/contact" class="footer-link">Booking Forms</a></li>
            <li><a href="/trekking" class="footer-link">Trek Grading System</a></li>
            <li><a href="/blog" class="footer-link">Travel Blog</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-title">Legal & Security</h4>
          <div class="footer-contact-row">
            <div>
              Government Reg No: 84521/067<br>
              Tourism License No: 1205/067<br>
              TIMS & TAAN Affiliated Agency
            </div>
          </div>
          <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5); line-height: 1.5; margin-top: 1rem;">
            Payments secured by Himalayan Bank Merchant Gateway. Visa, Mastercard, and Wire Transfers fully accepted.
          </div>
        </div>
      </div>

      <!-- Center Dynamic Partner Badges Section (Req 9) -->
      <?php if (!empty($footer_icons)): ?>
        <div style="display:flex; justify-content:center; align-items:center; gap:2.5rem; margin: 2rem 0; flex-wrap:wrap; border-top:1px solid rgba(255,255,255,0.1); border-bottom:1px solid rgba(255,255,255,0.1); padding: 1.5rem 0;">
          <?php foreach ($footer_icons as $icon): ?>
            <img src="/<?php echo htmlspecialchars($icon); ?>" style="height:40px; width:auto; object-fit:contain; filter:grayscale(100%); opacity:0.6; transition:all 0.3s ease;" onmouseover="this.style.filter='none'; this.style.opacity='1'; this.style.transform='scale(1.08)';" onmouseout="this.style.filter='grayscale(100%)'; this.style.opacity='0.6'; this.style.transform='scale(1)';" alt="Affiliation Badge">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="footer-bottom">
        <div class="footer-copy">
          © <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>. All rights reserved.
        </div>
        <div class="footer-badges">
          <span class="footer-badge">GOVT REGISTERED</span>
          <span class="footer-badge">TAAN MEMBER</span>
          <span class="footer-badge">NMA APPROVED</span>
        </div>
        <div class="footer-developer">Developed By: Sunil Gotame (SGHACKER)</div>
      </div>
    </div>
  </footer>

  <!-- Floating WhatsApp Click-to-Chat Button -->
  <?php
  $wa_number = preg_replace('/[^0-9]/', '', $site_whatsapp);
  $wa_text = "Hello " . $site_name . "! I'm visiting your website and need assistance planning a trek.";
  ?>
  <a href="https://wa.me/<?php echo $wa_number; ?>?text=<?php echo urlencode($wa_text); ?>" class="whatsapp-floating-btn" target="_blank" aria-label="Chat on WhatsApp">
    <svg viewBox="0 0 24 24">
      <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.666.988 3.301 1.488 5.35 1.489 5.535 0 10.04-4.502 10.043-10.04.002-2.684-1.04-5.207-2.937-7.105-1.897-1.897-4.417-2.941-7.11-2.942-5.54 0-10.044 4.502-10.047 10.042-.001 2.083.52 3.733 1.528 5.39l-.988 3.606 3.692-.969zm13.14-5.97c-.302-.15-1.785-.88-2.062-.98-.277-.1-.478-.15-.678.15-.2.3-.775.98-.95 1.18-.175.2-.35.225-.65.075-.3-.15-1.265-.467-2.41-1.487-.893-.797-1.496-1.78-1.672-2.08-.175-.3-.019-.462.13-.611.135-.135.302-.35.45-.525.15-.175.2-.3.3-.5.1-.2.05-.375-.025-.525-.075-.15-.678-1.635-.93-2.245-.244-.59-.49-.51-.678-.52-.175-.008-.376-.01-.577-.01-.2 0-.527.075-.803.375-.276.3-1.054 1.03-1.054 2.515 0 1.485 1.08 2.92 1.23 3.122.15.2 2.126 3.248 5.15 4.553.72.31 1.28.495 1.72.635.722.23 1.38.197 1.9.12.58-.087 1.787-.73 2.037-1.435.25-.705.25-1.31.175-1.435-.075-.125-.275-.2-.575-.35z"/>
    </svg>
  </a>

  <!-- Main JavaScript Bundle -->
  <script src="/assets/js/main.js"></script>
  <!-- Local Offline AI Knowledge Bot Search -->
  <script src="/assets/js/ai-bot.js"></script>

  <!-- Injected Custom Footer Codes (Req 8) -->
  <?php echo $footer_custom_html; ?>
</body>
</html>
