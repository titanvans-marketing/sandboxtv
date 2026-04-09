(function (window, document) {
  "use strict";

  function onReady(callback) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", callback, { once: true });
      return;
    }

    callback();
  }

  function findExistingScript(src) {
    const scripts = document.querySelectorAll("script[src]");
    for (const script of scripts) {
      if (script.getAttribute("src") === src) {
        return script;
      }
    }
    return null;
  }

  function loadScript(src) {
    return new Promise((resolve, reject) => {
      if (!src) {
        resolve();
        return;
      }

      const existing = findExistingScript(src);
      if (existing) {
        resolve(existing);
        return;
      }

      const script = document.createElement("script");
      script.src = src;
      script.async = false;
      script.onload = () => resolve(script);
      script.onerror = () =>
        reject(new Error("Failed to load script: " + src));

      document.body.appendChild(script);
    });
  }

  async function loadScriptsSequentially(scriptPaths) {
    if (!Array.isArray(scriptPaths) || scriptPaths.length === 0) {
      return;
    }

    for (const src of scriptPaths) {
      await loadScript(src);
    }
  }

  function initReviewTrack(trackId) {
    const id = trackId || "servicesReviewsTrack";
    const track = document.getElementById(id);

    if (!track) return;
    if (track.dataset.cloned === "true") return;

    track.innerHTML += track.innerHTML;
    track.dataset.cloned = "true";
  }

  function initReviewModal(config) {
    const modalId = config?.modalId || "reviewModal";
    const titleId = config?.titleId || "reviewModalTitle";
    const metaId = config?.metaId || "reviewModalMeta";
    const sourceId = config?.sourceId || "reviewModalSource";
    const contentId = config?.contentId || "reviewModalContent";
    const imageId = config?.imageId || "reviewModalImage";
    const triggerSelector = config?.triggerSelector || ".review-card__more-btn";
    const cardSelector = config?.cardSelector || ".review-card";
    const closeSelector =
      config?.closeSelector || "[data-review-modal-close]";

    const modal = document.getElementById(modalId);
    const modalTitle = document.getElementById(titleId);
    const modalMeta = document.getElementById(metaId);
    const modalSource = document.getElementById(sourceId);
    const modalContent = document.getElementById(contentId);
    const modalImage = document.getElementById(imageId);

    if (
      !modal ||
      !modalTitle ||
      !modalMeta ||
      !modalSource ||
      !modalContent ||
      !modalImage
    ) {
      return;
    }

    if (modal.dataset.initialized === "true") {
      return;
    }

    function closeModal() {
      modal.classList.remove("is-open");
      modal.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
      modalTitle.textContent = "";
      modalMeta.textContent = "";
      modalSource.textContent = "";
      modalContent.textContent = "";
      modalImage.src = "";
      modalImage.alt = "";
    }

    function openModal(card) {
      if (!card) return;

      const name = card.getAttribute("data-review-name") || "";
      const source = card.getAttribute("data-review-source") || "";
      const date = card.getAttribute("data-review-date") || "";
      const full = card.getAttribute("data-review-full") || "";
      const image = card.getAttribute("data-review-image") || "";
      const imageAlt =
        card.getAttribute("data-review-image-alt") || name || "Reviewer image";

      modalTitle.textContent = name;
      modalSource.textContent = source;
      modalMeta.textContent = date;
      modalContent.textContent = full ? `“${full}”` : "";
      modalImage.src = image;
      modalImage.alt = imageAlt;

      modal.classList.add("is-open");
      modal.setAttribute("aria-hidden", "false");
      document.body.style.overflow = "hidden";
    }

    document.addEventListener("click", function (event) {
      const closeTarget = event.target.closest(closeSelector);
      if (closeTarget && modal.contains(closeTarget)) {
        closeModal();
        return;
      }

      const trigger = event.target.closest(triggerSelector);
      if (!trigger) return;

      const card = trigger.closest(cardSelector);
      if (!card) return;

      event.preventDefault();
      openModal(card);
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && modal.classList.contains("is-open")) {
        closeModal();
      }
    });

    modal.dataset.initialized = "true";
  }

  function updateReviewCardTruncation(config) {
    const cardSelector = config?.cardSelector || ".review-card";
    const textSelector = config?.textSelector || ".review-card__text-clamp";
    const actionsSelector = config?.actionsSelector || ".review-card__actions";
    const triggerSelector = config?.triggerSelector || ".review-card__more-btn";

    const reviewCards = document.querySelectorAll(cardSelector);

    reviewCards.forEach((card) => {
      const text = card.querySelector(textSelector);
      const actions = card.querySelector(actionsSelector);
      const trigger =
        card.querySelector(triggerSelector) ||
        (actions ? actions.querySelector("button, a") : null);

      if (!text) return;

      const isOverflowing = text.scrollHeight > text.clientHeight + 2;

      card.classList.toggle("is-truncated", isOverflowing);

      if (actions) {
        actions.hidden = !isOverflowing;
        actions.setAttribute("aria-hidden", String(!isOverflowing));
      }

      if (trigger) {
        trigger.hidden = !isOverflowing;
        trigger.tabIndex = isOverflowing ? 0 : -1;
        trigger.setAttribute("aria-hidden", String(!isOverflowing));
      }
    });
  }

  function initReviewCardTruncation(config) {
    let resizeFrame = null;

    function run() {
      updateReviewCardTruncation(config);
    }

    function queueRun() {
      if (resizeFrame) {
        window.cancelAnimationFrame(resizeFrame);
      }

      resizeFrame = window.requestAnimationFrame(() => {
        run();
        resizeFrame = null;
      });
    }

    queueRun();
    window.addEventListener("load", queueRun);
    window.addEventListener("resize", queueRun);

    if (document.fonts && typeof document.fonts.ready?.then === "function") {
      document.fonts.ready.then(queueRun).catch(() => {});
    }

    return {
      refresh: queueRun,
    };
  }

  async function initServicePage(config) {
    const pageConfig = config || {};

    try {
      await loadScriptsSequentially(pageConfig.scriptPaths || []);
    } catch (error) {
      console.error("[ServiceUtils] Script load error:", error);
    }

    initReviewTrack(pageConfig.reviewTrackId);
    initReviewModal(pageConfig.reviewModal || {});

    const reviewCardsController = initReviewCardTruncation(
      pageConfig.reviewCards || {}
    );

    window.ServiceUtils.reviewCardsController = reviewCardsController;
  }

  window.ServiceUtils = {
    initServicePage,
    loadScriptsSequentially,
    initReviewTrack,
    initReviewModal,
    initReviewCardTruncation,
    updateReviewCardTruncation,
    reviewCardsController: null,
  };

  onReady(function () {
    if (window.servicePageConfig) {
      window.ServiceUtils.initServicePage(window.servicePageConfig);
    }
  });
})(window, document);