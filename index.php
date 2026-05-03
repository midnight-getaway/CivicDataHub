<?php
/**
 * index.php — Homepage that links to all Civic Data Hub dashboards and shows rotating dashboard previews.
 *
 * Dependencies: Session support and shared header include.
 * Data sources: None (client-side embeds dashboard previews only).
 * Last updated: 2026-05-03
 * Authors: Owen Sim, Kylie Mugrace, Keady Van Zandt
 */

// Start session so header auth state can render correctly.
session_start();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Civic Data Hub | Home</title>
  <link rel="icon" href="assets/favicon.ico" sizes="any" />
  <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png" />
  <link rel="icon" type="image/png" href="assets/favicon.png" />
  <link rel="apple-touch-icon" href="assets/favicon.png" />
  <link rel="stylesheet" href="styles.css" />
</head>

<body>
  <?php require 'includes/header.php'; ?>

  <main class="home-layout">
    <section id="dashboards" aria-label="Dashboard shortcuts">
      <div class="container dashboard-grid">
        <a class="dashboard-tile" href="dashboard1.php">Economic Hardship</a>
        <a class="dashboard-tile" href="dashboard2.php">Housing &amp; Homelessness</a>
        <a class="dashboard-tile" href="dashboard3.php">Health &amp; Wellbeing</a>
      </div>
    </section>

    <section id="overview" aria-label="Data and summary">
      <div class="container content-grid">
        <div class="graphic-placeholder" aria-label="Live dashboard previews">
          <div class="dashboard-carousel" id="dashboard-carousel">
            <a id="carousel-link" class="carousel-link" href="dashboard1.php" aria-label="Open dashboard preview">
              <iframe id="carousel-frame" title="Dashboard preview" loading="lazy"></iframe>
            </a>
            <div class="carousel-overlay">
              <div class="carousel-caption" id="carousel-caption">Economic Hardship</div>
              <div class="carousel-controls">
                <button type="button" id="carousel-prev" class="carousel-btn" aria-label="Previous dashboard preview">‹</button>
                <div class="carousel-dots" id="carousel-dots"></div>
                <button type="button" id="carousel-next" class="carousel-btn" aria-label="Next dashboard preview">›</button>
              </div>
            </div>
          </div>
        </div>

        <div class="insight-column">
          <p class="insight-text">New York's data, made accessible<span style="color: #12229d;">.</span></p>
          <div class="datapoint-box"><span class="datapoint-label">NYS Counties Covered</span><span class="datapoint-value">62</span></div>
          <div class="datapoint-box"><span class="datapoint-label">Data Sources</span><span class="datapoint-value">4</span></div>
          <div class="datapoint-box"><span class="datapoint-label">Years of Data</span><span class="datapoint-value">5</span></div>
        </div>
      </div>
    </section>
  </main>
  <script>
    /**
     * index.php — Carousel preview controller.
     * Charts: None (embedded iframes only).
     * Filters: Carousel navigation arrows and dot buttons.
     * Dependencies: Native DOM APIs.
     */
    (function () {
      const slides = [
        { title: 'Economic Hardship', href: 'dashboard1.php', src: 'dashboard1.php?embed=1' },
        { title: 'Housing & Homelessness', href: 'dashboard2.php', src: 'dashboard2.php?embed=1' },
        { title: 'Health & Wellbeing', href: 'dashboard3.php', src: 'dashboard3.php?embed=1' }
      ];

      const frame = document.getElementById('carousel-frame');
      const link = document.getElementById('carousel-link');
      const caption = document.getElementById('carousel-caption');
      const dotsWrap = document.getElementById('carousel-dots');
      const prevBtn = document.getElementById('carousel-prev');
      const nextBtn = document.getElementById('carousel-next');
      if (!frame || !link || !caption || !dotsWrap || !prevBtn || !nextBtn) return;

      let current = 0;
      let timer = null;

      // Render the currently selected dashboard preview slide.
      function render() {
        const slide = slides[current];
        frame.src = slide.src;
        link.href = slide.href;
        caption.textContent = slide.title;
        [...dotsWrap.children].forEach((dot, i) => dot.classList.toggle('active', i === current));
      }

      // Move carousel index with wraparound behavior.
      function goTo(idx) {
        current = (idx + slides.length) % slides.length;
        render();
      }

      // Restart automatic rotation after user interaction.
      function restartAuto() {
        if (timer) clearInterval(timer);
        timer = setInterval(() => goTo(current + 1), 7000);
      }

      slides.forEach((_, i) => {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'carousel-dot';
        dot.setAttribute('aria-label', `Show preview ${i + 1}`);
        dot.addEventListener('click', () => {
          goTo(i);
          restartAuto();
        });
        dotsWrap.appendChild(dot);
      });

      prevBtn.addEventListener('click', () => {
        goTo(current - 1);
        restartAuto();
      });
      nextBtn.addEventListener('click', () => {
        goTo(current + 1);
        restartAuto();
      });

      render();
      restartAuto();
    })();
  </script>
</body>

</html>
