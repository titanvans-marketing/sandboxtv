<?php

$pageMeta = [
  'title' => 'Camper Van Conversions | Conversion Vans Built in Boulder, Colorado',
  'description' => 'Titan Vans builds high quality camper vans designed for ultimate utility and ruggedness. Most of our components are manufactured in-house in our Boulder, Colorado facility.',
  'canonical' => 'https://www.titanvans.com',
  'robots' => 'index, follow',
  'body_class' => 'b-home',
  'nav_type' => 'main',
  'active_page' => 'home',
  'type' => 'website',
  'image' => 'https://www.titanvans.com/assets/images/about2.jpg',
  'og' => [
    'title' => 'Camper Van Conversions | Camper Vans Built in Boulder, Colorado',
    'description' => 'Titan Vans builds high quality camper vans designed for ultimate utility and ruggedness.',
    'url' => 'https://www.titanvans.com',
    'image' => 'https://www.titanvans.com/assets/images/about2.jpg',
    'type' => 'website',
  ],
  'twitter' => [
    'card' => 'summary_large_image',
    'title' => 'Camper Van Conversions | Camper Vans Built in Boulder, Colorado',
    'description' => 'Titan Vans builds high quality camper vans designed for ultimate utility and ruggedness.',
    'image' => 'https://www.titanvans.com/assets/images/about2.jpg',
  ],
  'extra_styles' => [
    '/src/styles/m072224.css',
  ],
  'extra_head' => <<<'HTML'
<link rel="preload" as="video" href="/assets/videos/SizzlerWeb.mp4" type="video/mp4" />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@100;200;300;400&display=swap" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
HTML,
];

$extraFooterScripts = <<<'HTML'
<script>
document.addEventListener("DOMContentLoaded", function () {
  const video = document.getElementById("header-video");
  if (!video) return;

  video.addEventListener("canplaythrough", function () {
    video.classList.add("is-playing");
  });
});
</script>

<script>
function initInlineMp4({
  containerId,
  thumbId,
  buttonId,
  title,
  animateToRatio = false,
  ratio = 16 / 9,
  cinemaOnLargeScreens = false,
  cinemaMinWidth = 1000,
  loop = false,
  controls = true,
}) {
  const container = document.getElementById(containerId);
  const thumb = document.getElementById(thumbId);
  const button = document.getElementById(buttonId);

  const smallScreenMQ = window.matchMedia
    ? window.matchMedia(`(max-width: ${cinemaMinWidth - 1}px)`)
    : null;

  if (!container) return;

  const section = container.closest(".inline-video") || null;
  const mp4Src = container.getAttribute("data-mp4-src");
  const youtubeId = container.getAttribute("data-youtube-id");
  const poster = container.getAttribute("data-poster") || "";

  function setSmallButtonState(isClose) {
    if (!button) return;

    if (!smallScreenMQ || !smallScreenMQ.matches) {
      button.classList.remove("is-close");
      const lbl = button.querySelector(".btn-label");
      if (lbl) lbl.textContent = "Play Video";
      return;
    }

    button.classList.toggle("is-close", isClose);
    const lbl = button.querySelector(".btn-label");
    if (lbl) lbl.textContent = isClose ? "Close Video" : "Play Video";
  }

  function ensureCloseButton() {
    const isCinemaActive =
      cinemaOnLargeScreens &&
      section &&
      section.classList.contains("cinema");

    if (!isCinemaActive) return null;

    const grid = container.closest(".ex3-w") || null;
    if (!grid) return null;

    let closeRow = grid.querySelector(
      `.inline-video__close-row[data-for="${containerId}"]`
    );

    if (!closeRow) {
      closeRow = document.createElement("div");
      closeRow.className = "inline-video__close-row";
      closeRow.setAttribute("data-for", containerId);
      container.insertAdjacentElement("afterend", closeRow);
    }

    let close = closeRow.querySelector(".inline-video__close");
    if (close) return close;

    close = document.createElement("button");
    close.type = "button";
    close.className = "inline-video__close inline-video__close--below";
    close.setAttribute("aria-label", "Close video");
    close.textContent = "Close Video";
    close.addEventListener("click", stop);

    closeRow.appendChild(close);
    return close;
  }

  function removeCloseRow() {
    const grid = container.closest(".ex3-w") || null;
    if (!grid) return;

    const closeRow = grid.querySelector(
      `.inline-video__close-row[data-for="${containerId}"]`
    );

    if (closeRow) closeRow.remove();
  }

  function stop() {
    const vid = container.querySelector("video");
    if (vid) {
      try {
        vid.pause();
        vid.removeAttribute("src");
        vid.load();
      } catch (_) {}
      vid.remove();
    }

    const iframe = container.querySelector("iframe");
    if (iframe) iframe.remove();

    container.classList.remove("is-playing");
    container.classList.remove("is-ratio");
    container.style.height = "";

    if (thumb) thumb.classList.remove("is-playing");

    if (cinemaOnLargeScreens && section) {
      section.classList.remove("cinema");
    }

    document.removeEventListener("keydown", onEsc);
    disarmOutsideClose();

    setSmallButtonState(false);
    removeCloseRow();
  }

  function onEsc(e) {
    if (e.key === "Escape") stop();
  }

  let outsideCloseArmed = false;

  function onOutsidePointerDown(ev) {
    if (!container.classList.contains("is-playing")) return;
    if (container.contains(ev.target)) return;
    if (button && button.contains(ev.target)) return;
    stop();
  }

  function armOutsideClose() {
    if (outsideCloseArmed) return;
    outsideCloseArmed = true;
    document.addEventListener("pointerdown", onOutsidePointerDown, true);
  }

  function disarmOutsideClose() {
    if (!outsideCloseArmed) return;
    outsideCloseArmed = false;
    document.removeEventListener("pointerdown", onOutsidePointerDown, true);
  }

  function play(e) {
    if (e) e.preventDefault();

    if (container.querySelector("video") || container.querySelector("iframe")) {
      return;
    }

    if (!mp4Src && !youtubeId) return;

    const prefersReduced =
      window.matchMedia &&
      window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    if (cinemaOnLargeScreens && section) {
      const mq = window.matchMedia
        ? window.matchMedia(`(min-width: ${cinemaMinWidth}px)`)
        : { matches: true };

      if (mq.matches) section.classList.add("cinema");
      ensureCloseButton();
    }

    if (animateToRatio && !prefersReduced) {
      const startH = container.getBoundingClientRect().height;
      container.style.height = `${Math.round(startH)}px`;
    }

    if (youtubeId) {
      const iframe = document.createElement("iframe");
      const src =
        `https://www.youtube.com/embed/${encodeURIComponent(youtubeId)}` +
        `?autoplay=1&playsinline=1&rel=0&modestbranding=1`;

      iframe.src = src;
      iframe.title = title || "YouTube video player";
      iframe.setAttribute("frameborder", "0");
      iframe.setAttribute(
        "allow",
        "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
      );
      iframe.setAttribute("allowfullscreen", "");

      container.appendChild(iframe);

      container.classList.add("is-playing");
      if (thumb) thumb.classList.add("is-playing");

      setSmallButtonState(true);

      document.addEventListener("keydown", onEsc);
      setTimeout(() => armOutsideClose(), 0);

      if (animateToRatio) {
        if (prefersReduced) {
          container.classList.add("is-ratio");
          container.style.height = "";
          return;
        }

        requestAnimationFrame(() => {
          const w = container.clientWidth || container.getBoundingClientRect().width;
          const targetH = Math.round(w / ratio);
          container.style.height = `${targetH}px`;

          const onDone = (ev) => {
            if (ev.propertyName !== "height") return;
            container.removeEventListener("transitionend", onDone);
            container.classList.add("is-ratio");
            container.style.height = "";
          };

          container.addEventListener("transitionend", onDone);
        });
      }

      return;
    }

    const video = document.createElement("video");
    video.setAttribute("playsinline", "");
    video.setAttribute("webkit-playsinline", "");
    video.preload = "metadata";
    video.autoplay = true;
    video.controls = true;
    video.loop = false;
    video.muted = true;
    video.src = mp4Src;
    video.poster = poster;

    container.appendChild(video);

    container.classList.add("is-playing");
    if (thumb) thumb.classList.add("is-playing");

    setSmallButtonState(true);

    document.addEventListener("keydown", onEsc);
    setTimeout(() => armOutsideClose(), 0);

    requestAnimationFrame(() => {
      const playPromise = video.play();

      if (playPromise !== undefined) {
        playPromise
          .then(() => {
            video.muted = false;
          })
          .catch(() => {
            video.muted = false;
            video.play().catch(() => {});
          });
      }
    });

    if (animateToRatio) {
      if (prefersReduced) {
        container.classList.add("is-ratio");
        container.style.height = "";
        return;
      }

      requestAnimationFrame(() => {
        const w = container.clientWidth || container.getBoundingClientRect().width;
        const targetH = Math.round(w / ratio);
        container.style.height = `${targetH}px`;

        const onDone = (ev) => {
          if (ev.propertyName !== "height") return;
          container.removeEventListener("transitionend", onDone);
          container.classList.add("is-ratio");
          container.style.height = "";
        };

        container.addEventListener("transitionend", onDone);
      });
    }
  }

  if (thumb) {
    thumb.addEventListener("click", play);
    thumb.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        play(e);
      }
    });
  }

  if (button) {
    button.addEventListener("click", (e) => {
      if (
        container.classList.contains("is-playing") &&
        (!smallScreenMQ || smallScreenMQ.matches)
      ) {
        e.preventDefault();
        stop();
        return;
      }

      play(e);
    });
  }

  if (cinemaOnLargeScreens && section) {
    window.addEventListener("resize", () => {
      const mq = window.matchMedia
        ? window.matchMedia(`(min-width: ${cinemaMinWidth}px)`)
        : { matches: true };

      if (!mq.matches) {
        section.classList.remove("cinema");
        removeCloseRow();
      }
    });
  }
}

document.addEventListener("DOMContentLoaded", function () {
  initInlineMp4({
    containerId: "process-video",
    thumbId: "process-thumb",
    buttonId: "process-play-btn",
    title: "Build Process Guide",
    animateToRatio: true,
    ratio: 16 / 9,
    cinemaOnLargeScreens: true,
    cinemaMinWidth: 1000,
    loop: true,
    controls: true,
  });

  initInlineMp4({
    containerId: "ex3-video",
    thumbId: "ex3-thumb",
    buttonId: "ex3-play-btn",
    title: "EX3 Electrical System",
    animateToRatio: true,
    ratio: 16 / 9,
    cinemaOnLargeScreens: true,
    cinemaMinWidth: 1000,
    loop: true,
    controls: true,
  });
});
</script>
HTML;

require_once __DIR__ . '/includes/header.php';
?>

<header class="header">
  <div class="header-media" aria-hidden="true">
    <video id="header-video" class="header-media" autoplay muted playsinline webkit-playsinline loop preload="metadata"
      poster="/assets/images/campervan-flatirons-logos.jpg">
      <source src="/assets/videos/SizzlerWeb.mp4" type="video/mp4" />
    </video>
  </div>

  <div class="header-box">
    <h1>Camper Van Conversions</h1>

    <p>
      Rugged. Modular. Serviceable.<br />
      Customized Build to Order Van Conversions.
    </p>

    <button class="learn-more" onclick="location.href='models'">
      Explore Models
    </button>
  </div>

  <div class="top-banner">
    <span>Now Scheduling Conversions for July 2026</span>
  </div>
</header>

<main class="main light-bg">
  <section class="s-showcase">
    <div class="showcase-wrapper">
      <div class="showcase dark-bg">
        <a class="showcase-img" href="build-price/build-form">
          <img src="/assets/images/camper-van-builder-z.jpg" alt="Our van conversion builder shown on a laptop" />
        </a>

        <div class="showcase-text">
          <h3>Design Your Van</h3>

          <p>
            Use our online van builder to visualize your customized van
            conversion. Start by selecting a model and add options to see
            the transformation in real time. Each addition updates the look
            and pricing, providing a clear estimate of the total cost.
          </p>

          <div class="learn-more">
            <a href="build-price/build-form" class="non-video">Explore</a>
          </div>
        </div>
      </div>

      <div class="showcase dark-bg">
        <a class="showcase-img" href="models">
          <img src="/assets/images/camper-van-models-z.jpg" alt="A Titan Vans camper van" />
        </a>

        <div class="showcase-text">
          <h3>Models</h3>

          <p>
            Select your model, add your options, and schedule your van
            conversion. We can source and supply you with a van, or we can
            build on a van you already own. We offer camper van builds for
            both the Mercedes Sprinter and Ford Transit chassis.
          </p>

          <div class="learn-more">
            <a href="models" class="non-video">Explore</a>
          </div>
        </div>
      </div>

      <div class="showcase dark-bg">
        <a class="showcase-img" href="service">
          <img src="/assets/images/camper-van-service-z.jpg" alt="Service and upgrades" />
        </a>

        <div class="showcase-text">
          <h3>Service &amp; Upgrades</h3>

          <p>
            Upgrade, repair, and installation services for camper vans 10
            years old or newer. Our service also extends to DIY van
            conversions and camper vans built by other van builders. Now
            servicing Revel, Storyteller, and Ekko camper vans.
          </p>

          <div class="learn-more">
            <a class="non-video" href="service">Explore</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="inline-video" id="process-section" aria-label="build process section">
    <div class="ex3-w">
      <div class="ex3-media video-wrapper inline-video__media bpg-video" id="process-video"
        data-mp4-src="/assets/videos/build-process-guide.mp4" data-poster="/assets/images/custom-van-conversion.jpg">
        <img class="ex3-img inline-video__thumb" id="process-thumb" src="/assets/images/custom-van-conversion.jpg"
          alt="Our Build Process (Play Button)" role="button" tabindex="0" />
      </div>

      <div class="ex3-txt inline-video__content">
        <h2>The Process Guide</h2>

        <p>
          Follow along as we go through the entire build process from start
          to finish.
        </p>

        <div class="learn-more inline-video__btn">
          <a href="#" id="process-play-btn" aria-controls="process-video">
            <span class="btn-icon" aria-hidden="true"></span>
            <span class="btn-label">Play Video</span>
          </a>
        </div>
      </div>
    </div>
  </section>

  <section>
    <div class="ex3-w vccompanies">
      <div class="ex3-txt">
        <h2>Why Choosing the Right Van Builder Matters</h2>

        <p>
          When searching for the best van conversion companies, it's
          essential to focus on the details that truly matter. While
          features and aesthetics play a role, the foundational elements of
          your van conversion are critical to ensuring reliability,
          especially when venturing off-grid. Pay close attention to the
          electrical system, the joinery of the cabinetry, and the
          accessibility of key systems for maintenance and repairs.
        </p>
      </div>
    </div>
  </section>

  <section class="rvia-eu">
    <div class="rvia">
      <a href="rvia">
        <img src="/assets/images/rvia.svg" alt="RVIA Logo" />
      </a>

      <p>
        Our commitment to quality and safety is affirmed by our RVIA
        certification. This certification ensures that our van conversions
        adhere to stringent industry standards, offering our customers peace
        of mind.
      </p>
    </div>

    <div class="eu">
      <a href="expert-upfitter">
        <img src="/assets/images/expert-upfitter.svg" alt="Expert Upfitter Logo" />
      </a>

      <p>
        Titan Vans is recognized as an eXpertUpfitter by Mercedes-Benz, a
        designation that highlights our adherence to the highest standards
        in Sprinter van conversions.
      </p>
    </div>
  </section>

  <section class="inline-video" id="ex3-section" aria-label="EX3 video section">
    <div class="ex3-w">
      <div class="ex3-media video-wrapper inline-video__media" id="ex3-video" data-youtube-id="4MRqW6TpUN0"
        data-poster="/assets/images/hp-ex3.png">
        <img class="ex3-img inline-video__thumb" id="ex3-thumb" src="/assets/images/hp-ex3.png"
          alt="The EX3 camper van electrical system (Play video)" role="button" tabindex="0" />
      </div>

      <div class="ex3-txt inline-video__content">
        <h2>The Ultimate Camper Van Electrical System</h2>

        <p>
          Titan Vans' EX3 camper van electrical system is included in each
          2025 van conversion. A fully recoverable, 4 season system designed
          to function in temps as low as -5 degrees fahrenheit.
        </p>

        <div class="learn-more inline-video__btn">
          <a href="#" id="ex3-play-btn" aria-controls="ex3-video">
            <span class="btn-icon" aria-hidden="true"></span>
            <span class="btn-label">Play Video</span>
          </a>
        </div>
      </div>
    </div>
  </section>

  <?php require_once __DIR__ . '/includes/footer.php'; ?>