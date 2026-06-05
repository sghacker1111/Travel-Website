<?php
$page_title = "Contact Us | Top Mountain Treks Pvt. Ltd.";
$page_desc = "Get in touch with Top Mountain Treks Pvt. Ltd. Office location, hotline number, and dynamic inquiry booking form to personalize your Nepal adventure.";
include 'includes/header.php';

// Handle booking submission
$inquiry_status = "";
$inquiry_msg = "";
$inquiries_path = 'data/inquiries.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_inquiry'])) {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $package = isset($_POST['package']) ? trim($_POST['package']) : '';
    $travel_date = isset($_POST['travel_date']) ? trim($_POST['travel_date']) : '';
    $travelers = isset($_POST['travelers']) ? (int)$_POST['travelers'] : 1;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if (!empty($name) && !empty($email) && !empty($package)) {
        $inquiries = [];
        if (file_exists($inquiries_path)) {
            $inquiries = json_decode(file_get_contents($inquiries_path), true);
        }
        if (!is_array($inquiries)) $inquiries = [];

        $new_inquiry = [
            'id' => 'inq_' . uniqid(),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'package' => $package,
            'travel_date' => $travel_date,
            'travelers' => $travelers,
            'message' => $message,
            'submitted_at' => date('Y-m-d H:i:s')
        ];

        $inquiries[] = $new_inquiry;
        file_put_contents($inquiries_path, json_encode($inquiries, JSON_PRETTY_PRINT));
        
        $inquiry_status = "success";
        $inquiry_msg = "Thank you! Your travel booking inquiry has been logged successfully. Our Sherpa sales manager will contact you within 12 hours.";
    } else {
        $inquiry_status = "error";
        $inquiry_msg = "Please complete Name, Email, and select a Package to submit your booking.";
    }
}

// Retrieve selected package if passed from detail page CTA
$preselected_pkg = isset($_GET['package']) ? $_GET['package'] : '';
$preselected_date = isset($_GET['travel_date']) ? $_GET['travel_date'] : '';
$preselected_travelers = isset($_GET['travelers']) ? (int)$_GET['travelers'] : 1;
?>

  <!-- Header Banner -->
  <section class="section-bg-alternate" style="padding: 4rem 0; border-bottom:1px solid var(--border-color);">
    <div class="container">
      <span class="card-badge" style="position:static; display:inline-block; margin-bottom:0.75rem;">Get In Touch</span>
      <h1 style="font-family:var(--font-heading); font-size:2.8rem; font-weight:800; color:var(--color-primary); margin-bottom:0.5rem;">Contact Top Mountain Treks</h1>
      <p style="color:var(--text-medium); font-size:1.1rem; max-width:800px;">Plan your custom Himalayan trail itinerary with our expert Nepalese guides.</p>
    </div>
  </section>

  <!-- Main Section -->
  <section>
    <div class="container">
      
      <?php if (!empty($inquiry_msg)): ?>
        <div style="padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 2.5rem; font-weight:600; line-height:1.4; border:1px solid <?php echo $inquiry_status === 'success' ? 'var(--color-success)' : 'var(--color-danger)'; ?>; background-color: <?php echo $inquiry_status === 'success' ? '#f0fdf4' : '#fef2f2'; ?>; color: <?php echo $inquiry_status === 'success' ? '#166534' : '#991b1b'; ?>;">
          <?php echo htmlspecialchars($inquiry_msg); ?>
        </div>
      <?php endif; ?>

      <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 4rem;" id="contact-grid">
        
        <!-- Contact details -->
        <div>
          <h2 style="font-family:var(--font-heading); font-size:1.75rem; color:var(--color-primary); margin-bottom:1.5rem; font-weight:800;">Office Location & Hotline</h2>
          
          <div style="display:flex; flex-direction:column; gap:1.5rem;">
            <div>
              <span style="font-weight:800; text-transform:uppercase; font-size:0.75rem; color:var(--text-muted); display:block; margin-bottom:0.25rem;">Headquarters Address</span>
              <p style="font-size:0.95rem; color:var(--text-dark); font-weight:600; line-height:1.5;">
                Top Mountain Treks Pvt. Ltd.<br>
                Satghumti Marg, Thamel-26<br>
                Kathmandu, Nepal (Post Box: 44600)
              </p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
              <div>
                <span style="font-weight:800; text-transform:uppercase; font-size:0.75rem; color:var(--text-muted); display:block; margin-bottom:0.25rem;">WhatsApp Helpline</span>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $site_whatsapp); ?>" style="font-size:1.05rem; color:var(--color-secondary); font-weight:800; text-decoration:none;" target="_blank">
                  🟢 <?php echo htmlspecialchars($site_whatsapp); ?>
                </a>
              </div>
              <div>
                <span style="font-weight:800; text-transform:uppercase; font-size:0.75rem; color:var(--text-muted); display:block; margin-bottom:0.25rem;">Direct Landline</span>
                <a href="tel:+97714700720" style="font-size:1.05rem; color:var(--color-primary); font-weight:800; text-decoration:none;">
                  📞 +977 1-4700720
                </a>
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
              <div>
                <span style="font-weight:800; text-transform:uppercase; font-size:0.75rem; color:var(--text-muted); display:block; margin-bottom:0.25rem;">Emergency 24/7 Hotline</span>
                <a href="tel:+9779851123456" style="font-size:1.05rem; color:#dc2626; font-weight:800; text-decoration:none;">
                  🚨 +977 98511-23456
                </a>
              </div>
              <div>
                <span style="font-weight:800; text-transform:uppercase; font-size:0.75rem; color:var(--text-muted); display:block; margin-bottom:0.25rem;">Direct Emails</span>
                <a href="mailto:info@topmountaintreks.com" style="font-size:0.95rem; color:var(--color-primary); font-weight:700; text-decoration:none;">
                  info@topmountaintreks.com
                </a>
              </div>
            </div>
            
            <!-- Leaflet Map CSS/JS & Container -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            
            <div id="office-map" style="height: 280px; border-radius: var(--radius-md); overflow:hidden; border:1px solid var(--border-color); z-index: 10;"></div>
            
            <script>
              document.addEventListener('DOMContentLoaded', function() {
                // Thamel Thamel center coordinates: [27.7150, 85.3120]
                var map = L.map('office-map').setView([27.7150, 85.3120], 16);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                  maxZoom: 19,
                  attribution: '&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'
                }).addTo(map);
                
                var marker = L.marker([27.7150, 85.3120]).addTo(map);
                marker.bindPopup("<b>Top Mountain Treks Pvt. Ltd.</b><br>Satghumti Marg, Thamel, Kathmandu.").openPopup();
              });
            </script>
          </div>
        </div>

        <!-- Inquiry Form -->
        <div class="glass-card" style="padding:2.5rem; border:1px solid var(--border-color); background-color:white;">
          <h2 style="font-family:var(--font-heading); font-size:1.75rem; color:var(--color-primary); margin-bottom:1.5rem; font-weight:800;">Send Booking Inquiry</h2>
          
          <form method="POST" action="" style="display:flex; flex-direction:column; gap:1.25rem;">
            <div class="form-group" style="margin-bottom:0;">
              <label for="name">Your Full Name <span style="color:var(--color-secondary);">*</span></label>
              <input type="text" id="name" name="name" class="form-input" required placeholder="e.g. John Doe">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;" id="form-dual-row">
              <div class="form-group" style="margin-bottom:0;">
                <label for="email">Email Address <span style="color:var(--color-secondary);">*</span></label>
                <input type="email" id="email" name="email" class="form-input" required placeholder="e.g. john@email.com">
              </div>
              <div class="form-group" style="margin-bottom:0;">
                <label for="phone">Phone / WhatsApp</label>
                <input type="text" id="phone" name="phone" class="form-input" placeholder="e.g. +1 (555) 123-456">
              </div>
            </div>

            <div class="form-group" style="margin-bottom:0;">
              <label for="package">Select Trek/Tour Package <span style="color:var(--color-secondary);">*</span></label>
              <select id="package" name="package" class="form-input" required>
                <option value="">-- Choose a package --</option>
                <?php foreach ($packages as $pkg): ?>
                  <option value="<?php echo htmlspecialchars($pkg['title']); ?>" <?php echo ($preselected_pkg === $pkg['title'] || $preselected_pkg === $pkg['slug']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($pkg['title']); ?> (<?php echo htmlspecialchars($pkg['category']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:1.25rem;" id="form-booking-details">
              <div class="form-group" style="margin-bottom:0;">
                <label for="travel_date">Preferred Travel Date</label>
                <input type="date" id="travel_date" name="travel_date" class="form-input" value="<?php echo htmlspecialchars($preselected_date); ?>">
              </div>
              <div class="form-group" style="margin-bottom:0;">
                <label for="travelers">Number of Travelers</label>
                <input type="number" id="travelers" name="travelers" class="form-input" min="1" max="100" value="<?php echo (int)$preselected_travelers; ?>">
              </div>
            </div>

            <div class="form-group" style="margin-bottom:0;">
              <label for="message">Custom Requirements or Questions</label>
              <textarea id="message" name="message" class="form-input" rows="4" placeholder="Mention group fitness levels, custom extension days, or any dietary limits..."></textarea>
            </div>

            <button type="submit" name="submit_inquiry" class="btn btn-primary" style="height:48px; font-size:1.02rem; font-weight:700;">Submit Booking Request</button>
          </form>
        </div>

      </div>

    </div>
  </section>

<?php include 'includes/footer.php'; ?>