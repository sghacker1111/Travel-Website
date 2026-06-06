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
  initCountryCodePickers();
  initOfferCountdowns();
  initPackageHeroRotation();
  initBackToTop();
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

function initBackToTop() {
  const btn = document.getElementById('back-to-top-btn');
  if (!btn) return;

  const toggle = () => {
    btn.classList.toggle('visible', window.scrollY > 520);
  };

  btn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  window.addEventListener('scroll', toggle, { passive: true });
  toggle();
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
    menuBtn.classList.toggle('active', nav.classList.contains('open'));
    menuBtn.setAttribute('aria-expanded', nav.classList.contains('open') ? 'true' : 'false');
  });

  // Toggle dropdown items on click for mobile devices
  navItems.forEach(item => {
    const link = item.querySelector('.nav-link');
    const dropdown = item.querySelector('.dropdown-menu');
    if (!link) return;
    link.addEventListener('click', (e) => {
      if (window.innerWidth <= 992 && dropdown) {
        e.preventDefault();
        navItems.forEach(other => {
          if (other !== item) other.classList.remove('open-mobile');
        });
        item.classList.toggle('open-mobile');
      }
    });
  });

  // Close when clicking outside
  document.addEventListener('click', (e) => {
    if (nav.classList.contains('open') && !nav.contains(e.target) && e.target !== menuBtn) {
      nav.classList.remove('open');
      menuBtn.classList.remove('active');
      menuBtn.setAttribute('aria-expanded', 'false');
      navItems.forEach(item => item.classList.remove('open-mobile'));
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
  const tabs = document.querySelectorAll('.tab, .package-shortcut-button');
  const sticky = document.getElementById('package-sticky-shortcuts');
  const panes = Array.from(document.querySelectorAll('.package-details-main .tab-pane'));
  if (!tabs.length || !panes.length) return;
  
  function setActiveShortcut(targetId) {
    tabs.forEach(t => t.classList.toggle('active', t.getAttribute('data-tab') === targetId));
    panes.forEach(pane => pane.classList.toggle('active', pane.id === targetId));
  }

  function getShortcutOffset() {
    const stickyHeight = sticky && sticky.classList.contains('visible') ? sticky.offsetHeight + 18 : 0;
    return Math.max(96, stickyHeight + 96);
  }

  function refreshRouteMap(targetId) {
    if (targetId === 'detail-route' && window.map) {
      setTimeout(() => {
        window.map.invalidateSize();
        if (window.polyline) {
          window.map.fitBounds(window.polyline.getBounds(), { padding: [50, 50] });
        }
      }, 180);
    }
  }

  function scrollToPackageSection(targetId) {
    const targetPane = document.getElementById(targetId);
    if (!targetPane) return;
    setActiveShortcut(targetId);
    const top = targetPane.getBoundingClientRect().top + window.scrollY - getShortcutOffset() - 40;
    window.scrollTo({ top, behavior: 'smooth' });
    refreshRouteMap(targetId);
    if (targetId === 'detail-itinerary' && typeof window.refreshItineraryAccordionHeights === 'function') {
      setTimeout(window.refreshItineraryAccordionHeights, 220);
    }
  }

  tabs.forEach(tab => {
    tab.addEventListener('click', (e) => {
      e.preventDefault();
      scrollToPackageSection(e.currentTarget.getAttribute('data-tab'));
    });
  });

  function updateStickyAndActiveSection() {
    if (sticky && tabsContainer) {
      const tabsRect = tabsContainer.getBoundingClientRect();
      const detailEnd = document.querySelector('.package-details-main');
      const endRect = detailEnd ? detailEnd.getBoundingClientRect() : { bottom: 9999 };
      sticky.classList.toggle('visible', tabsRect.bottom <= 8 && endRect.bottom > 180);
    }

    const offset = getShortcutOffset() + 96;
    let activeId = panes[0].id;
    panes.forEach(pane => {
      if (pane.getBoundingClientRect().top <= offset) {
        activeId = pane.id;
      }
    });
    setActiveShortcut(activeId);
  }

  window.addEventListener('scroll', updateStickyAndActiveSection, { passive: true });
  window.addEventListener('resize', updateStickyAndActiveSection);
  updateStickyAndActiveSection();
}

/* 5. Collapsible Accordion on Package Itinerary */
function initAccordionSystem() {
  const items = document.querySelectorAll('.acc-item');
  if (items.length === 0) return;
  const expandBtn = document.getElementById('expand-all-itinerary');
  const modeToggle = document.getElementById('itinerary-expand-mode');
  let expandedAll = false;

  function getAccordionHeight(item) {
    const inner = item.querySelector('.acc-content-inner');
    return inner ? inner.scrollHeight + 40 : 0;
  }

  function setAccordionOpen(item, isOpen) {
    const content = item.querySelector('.acc-content');
    if (!content) return;
    item.classList.toggle('active', isOpen);
    content.style.maxHeight = isOpen ? getAccordionHeight(item) + 'px' : '0px';
  }

  window.refreshItineraryAccordionHeights = function() {
    items.forEach(item => {
      if (item.classList.contains('active')) {
        setAccordionOpen(item, true);
      }
    });
  };

  items.forEach(item => {
    const header = item.querySelector('.acc-header');
    if (!header) return;

    header.addEventListener('click', () => {
      const isActive = item.classList.contains('active');
      const allowMulti = modeToggle && modeToggle.checked;

      // Collapse all
      if (!allowMulti) {
        items.forEach(i => setAccordionOpen(i, false));
      }

      if (!isActive) {
        setAccordionOpen(item, true);
      } else if (allowMulti) {
        setAccordionOpen(item, false);
      }
    });
  });

  if (expandBtn) {
    expandBtn.addEventListener('click', () => {
      expandedAll = !expandedAll;
      items.forEach(item => setAccordionOpen(item, expandedAll));
      expandBtn.textContent = expandedAll ? 'Collapse All' : 'Expand All';
    });
  }

  window.addEventListener('resize', window.refreshItineraryAccordionHeights);
  window.refreshItineraryAccordionHeights();
}

function initCountryCodePickers() {
  const widgets = document.querySelectorAll('.country-code-picker');
  if (!widgets.length) return;

  const fallbackCountries = [
    { name: 'Nepal', code: '+977' },
    { name: 'India', code: '+91' },
    { name: 'United States', code: '+1' },
    { name: 'United Kingdom', code: '+44' },
    { name: 'Australia', code: '+61' },
    { name: 'Canada', code: '+1' },
    { name: 'Germany', code: '+49' },
    { name: 'France', code: '+33' },
    { name: 'Spain', code: '+34' },
    { name: 'Italy', code: '+39' },
    { name: 'Netherlands', code: '+31' },
    { name: 'Switzerland', code: '+41' },
    { name: 'China', code: '+86' },
    { name: 'Japan', code: '+81' },
    { name: 'South Korea', code: '+82' },
    { name: 'Singapore', code: '+65' },
    { name: 'Thailand', code: '+66' },
    { name: 'Malaysia', code: '+60' },
    { name: 'United Arab Emirates', code: '+971' },
    { name: 'Qatar', code: '+974' },
    { name: 'Saudi Arabia', code: '+966' },
    { name: 'Bangladesh', code: '+880' },
    { name: 'Sri Lanka', code: '+94' },
    { name: 'Pakistan', code: '+92' },
    { name: 'Brazil', code: '+55' },
    { name: 'Mexico', code: '+52' },
    { name: 'South Africa', code: '+27' }
  ];

  function optionLabel(country) {
    return `${country.name} (${country.code})`;
  }

  function populate(widget, countries) {
    const search = widget.querySelector('.country-code-search');
    const select = widget.querySelector('.country-code-select');
    const hidden = widget.querySelector('.country-code-value');
    if (!select) return;

    const sorted = countries
      .filter(c => c.name && c.code)
      .sort((a, b) => a.name.localeCompare(b.name));

    function render(filter = '') {
      const needle = filter.trim().toLowerCase();
      const currentValue = select.value || hidden?.value || '+977';
      select.innerHTML = '';
      sorted
        .filter(c => !needle || c.name.toLowerCase().includes(needle) || c.code.includes(needle))
        .forEach(country => {
          const option = document.createElement('option');
          option.value = country.code;
          option.textContent = optionLabel(country);
          if (country.code === currentValue || (country.name === 'Nepal' && currentValue === '+977')) {
            option.selected = true;
          }
          select.appendChild(option);
        });
      if (hidden) hidden.value = select.value || '+977';
    }

    search?.addEventListener('input', () => render(search.value));
    select.addEventListener('change', () => {
      if (hidden) hidden.value = select.value;
    });
    render();
  }

  widgets.forEach(widget => populate(widget, fallbackCountries));

  fetch('https://restcountries.com/v3.1/all?fields=name,idd')
    .then(res => res.ok ? res.json() : Promise.reject(new Error('country fetch failed')))
    .then(list => list.map(item => {
      const root = item.idd && item.idd.root ? item.idd.root : '';
      const suffix = item.idd && item.idd.suffixes && item.idd.suffixes.length ? item.idd.suffixes[0] : '';
      return {
        name: item.name && item.name.common ? item.name.common : '',
        code: root ? root + suffix : ''
      };
    }))
    .then(countries => {
      widgets.forEach(widget => populate(widget, countries));
    })
    .catch(() => {});
}

function initOfferCountdowns() {
  const timers = document.querySelectorAll('[data-offer-end]');
  if (!timers.length) return;

  function render() {
    const now = Date.now();
    timers.forEach(timer => {
      const endValue = timer.getAttribute('data-offer-end');
      const end = new Date(endValue).getTime();
      if (!end || Number.isNaN(end)) return;
      const diff = end - now;
      if (diff <= 0) {
        timer.textContent = 'Offer ended';
        timer.classList.remove('limited-time-text');
        return;
      }
      const days = Math.floor(diff / 86400000);
      const hours = Math.floor((diff % 86400000) / 3600000);
      const minutes = Math.floor((diff % 3600000) / 60000);
      timer.textContent = days > 0 ? `${days}d ${hours}h left` : `${hours}h ${minutes}m left`;
    });
  }

  render();
  setInterval(render, 60000);
}

function initPackageHeroRotation() {
  const hero = document.getElementById('package-hero');
  if (!hero || !hero.dataset.images) return;
  let images = [];
  try {
    images = JSON.parse(hero.dataset.images);
  } catch (err) {
    images = [];
  }
  if (!Array.isArray(images) || images.length < 2) return;

  let index = 0;
  setInterval(() => {
    index = (index + 1) % images.length;
    hero.style.backgroundImage = `url('/${images[index]}')`;
  }, 5000);
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
