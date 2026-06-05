/* ==========================================================================
   Summit Main Application Scripts - Top Everest Treks
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
  initStickyHeader();
  initMobileMenu();
  initHeroSlider();
  initTabsSystem();
  initAccordionSystem();
  initPriceCalculator();
  initAltitudeSimulator();
});

/* 1. Sticky Header Shadow on Scroll */
function initStickyHeader() {
  const header = document.getElementById('site-header');
  if (!header) return;

  window.addEventListener('scroll', () => {
    if (window.scrollY > 20) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  });
}

/* 2. Mobile Responsive Menu Dropdown */
function initMobileMenu() {
  const menuBtn = document.getElementById('mobile-menu-btn');
  const nav = document.getElementById('site-nav');
  const navItems = document.querySelectorAll('.nav-item');

  if (!menuBtn || !nav) return;

  menuBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    nav.classList.toggle('open');
  });

  // Toggle dropdown items on click for mobile devices
  navItems.forEach(item => {
    const link = item.querySelector('.nav-link');
    link.addEventListener('click', (e) => {
      if (window.innerWidth <= 992) {
        e.preventDefault();
        item.classList.toggle('open-mobile');
      }
    });
  });

  // Close when clicking outside
  document.addEventListener('click', (e) => {
    if (nav.classList.contains('open') && !nav.contains(e.target) && e.target !== menuBtn) {
      nav.classList.remove('open');
    }
  });
}

/* 3. Auto-rotating Hero Slider */
function initHeroSlider() {
  const slider = document.getElementById('home-hero-slider');
  if (!slider) return;

  const slides = slider.querySelectorAll('.hero-slide');
  const prevBtn = document.getElementById('hero-prev');
  const nextBtn = document.getElementById('hero-next');
  const dotsContainer = document.getElementById('hero-dots-container');

  if (slides.length === 0 || !dotsContainer) return;

  let currentIndex = 0;
  let slideInterval;

  // Generate dots dynamically
  dotsContainer.innerHTML = '';
  slides.forEach((slide, index) => {
    const dot = document.createElement('span');
    dot.className = `hero-dot ${index === 0 ? 'active' : ''}`;
    dot.setAttribute('data-index', index);
    dotsContainer.appendChild(dot);

    dot.addEventListener('click', () => {
      goToSlide(index);
      resetAutoplay();
    });
  });

  const dots = dotsContainer.querySelectorAll('.hero-dot');

  function goToSlide(index) {
    slides[currentIndex].classList.remove('active');
    dots[currentIndex].classList.remove('active');

    currentIndex = (index + slides.length) % slides.length;

    slides[currentIndex].classList.add('active');
    dots[currentIndex].classList.add('active');
  }

  function nextSlide() {
    goToSlide(currentIndex + 1);
  }

  function prevSlide() {
    goToSlide(currentIndex - 1);
  }

  // Bind arrows
  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      nextSlide();
      resetAutoplay();
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      prevSlide();
      resetAutoplay();
    });
  }

  // Autoplay
  function startAutoplay() {
    slideInterval = setInterval(nextSlide, 5000); // 5 seconds
  }

  function resetAutoplay() {
    clearInterval(slideInterval);
    startAutoplay();
  }

  startAutoplay();
}

/* 4. Tab Switching on Package Detail page */
function initTabsSystem() {
  const tabsContainer = document.querySelector('.tabs');
  if (!tabsContainer) return;

  const tabs = tabsContainer.querySelectorAll('.tab');
  
  tabs.forEach(tab => {
    tab.addEventListener('click', (e) => {
      const targetId = e.currentTarget.getAttribute('data-tab');
      
      // Deactivate all
      tabs.forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
      
      // Activate target
      e.currentTarget.classList.add('active');
      const targetPane = document.getElementById(targetId);
      if (targetPane) targetPane.classList.add('active');
    });
  });
}

/* 5. Collapsible Accordion on Package Itinerary */
function initAccordionSystem() {
  const items = document.querySelectorAll('.acc-item');
  if (items.length === 0) return;

  items.forEach(item => {
    const header = item.querySelector('.acc-header');
    const content = item.querySelector('.acc-content');

    header.addEventListener('click', () => {
      const isActive = item.classList.contains('active');

      // Collapse all
      items.forEach(i => {
        i.classList.remove('active');
        i.querySelector('.acc-content').style.maxHeight = '0px';
      });

      if (!isActive) {
        item.classList.add('active');
        // Calculate content inner height
        const height = content.querySelector('.acc-content-inner').scrollHeight + 40; // padding buffer
        content.style.maxHeight = `${height}px`;
      }
    });
  });
}

/* 6. Dynamic Package Booking Sidebar Calculator */
function initPriceCalculator() {
  const calcCard = document.querySelector('.package-sidebar');
  if (!calcCard) return;

  const grpRange = document.getElementById('grp-range');
  const grpSizeVal = document.getElementById('grp-size-val');
  const guideTier = document.getElementById('guide-tier');
  const addPorter = document.getElementById('add-porter');
  const addHeli = document.getElementById('add-heli');
  const priceTotal = document.getElementById('price-total');
  const bookInquireBtn = document.getElementById('book-inquire-btn');

  // Verify elements & global bindings from PHP
  if (!grpRange || typeof packageBasePrice === 'undefined') return;

  function calculate() {
    const travelers = parseInt(grpRange.value);
    
    // Group Discounts (5% for 2 people, 10% for 3-5, 15% for 6+)
    let discountPercent = 0;
    if (travelers === 2) discountPercent = 0.05;
    else if (travelers >= 3 && travelers <= 5) discountPercent = 0.10;
    else if (travelers >= 6) discountPercent = 0.15;

    const baseCost = packageBasePrice * travelers;
    const discount = baseCost * discountPercent;
    
    // Upgrades / Service addon totals
    const guideCost = parseFloat(guideTier.value);
    const porterCost = addPorter.checked ? (15 * packageDuration) : 0;
    const heliCost = addHeli.checked ? (350 * travelers) : 0;

    const finalTotal = baseCost - discount + guideCost + porterCost + heliCost;

    // Display formatted price
    priceTotal.textContent = `$${Math.round(finalTotal).toLocaleString()}`;
  }

  grpRange.addEventListener('input', (e) => {
    const num = e.target.value;
    grpSizeVal.textContent = num == 1 ? "1 Trekker" : `${num} Trekkers`;
    calculate();
  });

  guideTier.addEventListener('change', calculate);
  addPorter.addEventListener('change', calculate);
  addHeli.addEventListener('change', calculate);

  // Initial calculation
  calculate();

  // Booking button pre-fills click-action redirect
  if (bookInquireBtn) {
    bookInquireBtn.addEventListener('click', () => {
      const size = grpRange.value;
      const guideText = guideTier.options[guideTier.selectedIndex].text;
      const porter = addPorter.checked ? 'Yes' : 'No';
      const heli = addHeli.checked ? 'Yes' : 'No';
      const total = priceTotal.textContent;

      window.location.href = `contact.php?trek=${packageSlug}&travelers=${size}&guide=${encodeURIComponent(guideText)}&porter=${porter}&heli=${heli}&price=${encodeURIComponent(total)}`;
    });
  }
}

/* 7. Homepage Altitude Safety & Pressure Simulator */
function initAltitudeSimulator() {
  const range = document.getElementById('alt-range');
  const altVal = document.getElementById('alt-val');
  const oxyVal = document.getElementById('oxy-val');
  const pressureVal = document.getElementById('pressure-val');
  const safetyCard = document.getElementById('safety-card');

  if (!range) return;

  function update(altitude) {
    altVal.textContent = `${altitude.toLocaleString()}m (${Math.round(altitude * 3.28084).toLocaleString()} ft)`;

    // Calculate pressure: 1013.25 * e^(-alt / 7400)
    const pressure = Math.round(1013.25 * Math.exp(-altitude / 7400));
    pressureVal.textContent = `${pressure} hPa`;

    // Calculate effective oxygen percentage relative to sea level
    const oxygenRelative = Math.round(Math.exp(-altitude / 7400) * 100);
    oxyVal.textContent = `${oxygenRelative}%`;

    // Symptoms check and advise Inclusions
    let adviceClass = "success";
    let adviceTitle = "Safe Altitude Level";
    let adviceText = "No altitude symptoms. Ideal physical conditions. Standard hydration (2L per day) is recommended.";

    if (altitude >= 2500 && altitude < 3500) {
      adviceClass = "success";
      adviceTitle = "Initial Acclimatization Zone";
      adviceText = "Adaptation begins. Shortness of breath may occur during steps. Climb slowly and take rest shifts.";
    } else if (altitude >= 3500 && altitude < 4700) {
      adviceClass = "error"; // triggers warm yellow/red
      adviceTitle = "High Altitude (AMS Risk Zone)";
      adviceText = "Mild headache and fatigue are common. Limit climb rate to 400m/day. Acclimatization rest day is mandatory.";
      safetyCard.style.backgroundColor = '#fffbeb';
      safetyCard.style.color = '#92400e';
      safetyCard.style.borderColor = '#fef3c7';
    } else if (altitude >= 4700 && altitude < 8000) {
      adviceClass = "error";
      adviceTitle = "Extreme High Altitude (Dangerous Zone)";
      adviceText = "Severe lack of sleep, coughing, and dizziness. Supplementary oximeter reading required. Descend immediately if AMS worsens.";
      safetyCard.style.backgroundColor = '#fef2f2';
      safetyCard.style.color = '#991b1b';
      safetyCard.style.borderColor = '#fca5a5';
    } else if (altitude >= 8000) {
      adviceClass = "error";
      adviceTitle = "The Death Zone (8,000m+)";
      adviceText = "Body consumes its own cells. Human adaptation is impossible. Limit exposure to a few hours under oxygen cylinders.";
      safetyCard.style.backgroundColor = '#fef2f2';
      safetyCard.style.color = '#991b1b';
      safetyCard.style.borderColor = '#fca5a5';
    }

    if (altitude < 3500) {
      safetyCard.style.backgroundColor = '#ecfdf5';
      safetyCard.style.color = '#065f46';
      safetyCard.style.borderColor = '#a7f3d0';
    }

    safetyCard.innerHTML = `<strong>${adviceTitle}</strong><br>${adviceText}`;
  }

  range.addEventListener('input', (e) => {
    update(parseInt(e.target.value));
  });

  // Default initial Kathmandu state
  update(1400);
}
