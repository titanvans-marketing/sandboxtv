document.addEventListener("DOMContentLoaded", function () {
  const navbar = document.querySelector(".navbar");
  const navPanel = document.querySelector("#site-nav-menu");
  const hamburger = document.querySelector(".hamburger");
  const dropdownParents = Array.from(
    document.querySelectorAll(".nav-item.dropdown-parent"),
  );

  if (!navbar || !navPanel) {
    return;
  }

  const desktopMedia = window.matchMedia("(min-width: 1201px)");
  const OPEN_DELAY = 90;
  const CLOSE_DELAY = 180;

  let openTimer = null;
  let closeTimer = null;
  let escCloseTimer = null;

  function isDesktop() {
    return desktopMedia.matches;
  }

  function getTrigger(item) {
    return item.querySelector(":scope > .nav-link");
  }

  function getDropdown(item) {
    return item.querySelector(":scope > .dropdown");
  }

  function setExpanded(item, expanded) {
    const trigger = getTrigger(item);
    if (trigger) {
      trigger.setAttribute("aria-expanded", expanded ? "true" : "false");
    }
  }

  function syncBackdropState() {
    const hasOpenDesktopDropdown = dropdownParents.some((item) =>
      item.classList.contains("is-open"),
    );

    if (hasOpenDesktopDropdown) {
      document.body.classList.add("nav-dropdown-open");
      document.documentElement.classList.add("nav-dropdown-open");
    } else {
      document.body.classList.remove("nav-dropdown-open");
      document.documentElement.classList.remove("nav-dropdown-open");
    }
  }

  function setMobilePageLock(locked) {
    document.body.classList.toggle("nav-open", locked);
    document.documentElement.classList.toggle("nav-open", locked);
  }

  function clearDesktopTimers() {
    if (openTimer) {
      clearTimeout(openTimer);
      openTimer = null;
    }

    if (closeTimer) {
      clearTimeout(closeTimer);
      closeTimer = null;
    }
  }

  function suppressDesktopHoverOnce() {
  if (!isDesktop()) return;

  if (escCloseTimer) {
    clearTimeout(escCloseTimer);
    escCloseTimer = null;
  }

  navbar.classList.add("is-esc-closing");

  escCloseTimer = window.setTimeout(() => {
    navbar.classList.remove("is-esc-closing");
    escCloseTimer = null;
  }, 180);
}

  function closeDesktopItem(item) {
    if (!item) return;
    item.classList.remove("is-open");
    setExpanded(item, false);
    syncBackdropState();
  }

  function openDesktopItem(item) {
    if (!item) return;

    dropdownParents.forEach((otherItem) => {
      if (otherItem !== item) {
        otherItem.classList.remove("is-open");
        setExpanded(otherItem, false);
      }
    });

    item.classList.add("is-open");
    setExpanded(item, true);
    syncBackdropState();
  }

  function closeAllDesktopItems() {
    dropdownParents.forEach((item) => {
      item.classList.remove("is-open");
      setExpanded(item, false);
    });

    syncBackdropState();
  }

  function scheduleOpen(item) {
    if (!isDesktop()) return;

    clearDesktopTimers();

    openTimer = window.setTimeout(() => {
      openDesktopItem(item);
    }, OPEN_DELAY);
  }

  function scheduleClose(item) {
    if (!isDesktop()) return;

    clearDesktopTimers();

    closeTimer = window.setTimeout(() => {
      closeDesktopItem(item);
    }, CLOSE_DELAY);
  }

  function closeAllMobileDropdowns() {
    dropdownParents.forEach((item) => {
      item.classList.remove("active");
      setExpanded(item, false);
    });
  }

  function closeMobileMenu() {
    navPanel.classList.remove("active");

    if (hamburger) {
      hamburger.classList.remove("active");
      hamburger.setAttribute("aria-expanded", "false");
    }

    closeAllMobileDropdowns();
    setMobilePageLock(false);
  }

  function openMobileMenu() {
    navPanel.classList.add("active");

    if (hamburger) {
      hamburger.classList.add("active");
      hamburger.setAttribute("aria-expanded", "true");
    }

    setMobilePageLock(true);
  }

  function toggleMobileMenu() {
    if (navPanel.classList.contains("active")) {
      closeMobileMenu();
    } else {
      openMobileMenu();
    }
  }

  function resetForBreakpoint() {
    clearDesktopTimers();
    closeAllDesktopItems();

    if (isDesktop()) {
      navPanel.classList.remove("active");

      if (hamburger) {
        hamburger.classList.remove("active");
        hamburger.setAttribute("aria-expanded", "false");
      }

      dropdownParents.forEach((item) => {
        item.classList.remove("active");
        setExpanded(item, false);
      });

      document.body.classList.remove("nav-open", "nav-dropdown-open");
      document.documentElement.classList.remove(
        "nav-open",
        "nav-dropdown-open",
      );
    } else {
      closeAllDesktopItems();
    }
  }

  dropdownParents.forEach((item) => {
    const trigger = getTrigger(item);
    const dropdown = getDropdown(item);

    if (!trigger || !dropdown) return;

    trigger.setAttribute("aria-expanded", "false");
    trigger.setAttribute("aria-haspopup", "true");

    if (!trigger.hasAttribute("href")) {
      trigger.setAttribute("role", "button");
      trigger.setAttribute("tabindex", "0");
    }

    item.addEventListener("mouseenter", function () {
      if (!isDesktop()) return;
      scheduleOpen(item);
    });

    item.addEventListener("mouseleave", function () {
      if (!isDesktop()) return;
      scheduleClose(item);
    });

    trigger.addEventListener("focus", function () {
      if (!isDesktop()) return;
      openDesktopItem(item);
    });

    item.addEventListener("focusin", function () {
      if (!isDesktop()) return;
      clearDesktopTimers();
      openDesktopItem(item);
    });

    item.addEventListener("focusout", function () {
      if (!isDesktop()) return;

      window.setTimeout(() => {
        const stillInside = item.contains(document.activeElement);
        if (!stillInside) {
          scheduleClose(item);
        }
      }, 0);
    });

    trigger.addEventListener("click", function (event) {
      if (isDesktop()) {
        event.preventDefault();

        const alreadyOpen = item.classList.contains("is-open");
        closeAllDesktopItems();

        if (!alreadyOpen) {
          openDesktopItem(item);
        }
        return;
      }

      event.preventDefault();

      const isActive = item.classList.contains("active");

      dropdownParents.forEach((otherItem) => {
        if (otherItem !== item) {
          otherItem.classList.remove("active");
          setExpanded(otherItem, false);
        }
      });

      item.classList.toggle("active", !isActive);
      setExpanded(item, !isActive);
    });

    trigger.addEventListener("keydown", function (event) {
      if (event.key !== "Enter" && event.key !== " ") {
        return;
      }

      event.preventDefault();

      if (isDesktop()) {
        const alreadyOpen = item.classList.contains("is-open");
        closeAllDesktopItems();

        if (!alreadyOpen) {
          openDesktopItem(item);
        }
      } else {
        const isActive = item.classList.contains("active");

        dropdownParents.forEach((otherItem) => {
          if (otherItem !== item) {
            otherItem.classList.remove("active");
            setExpanded(otherItem, false);
          }
        });

        item.classList.toggle("active", !isActive);
        setExpanded(item, !isActive);
      }
    });

    dropdown.addEventListener("mouseenter", function () {
      if (!isDesktop()) return;
      clearDesktopTimers();
      openDesktopItem(item);
    });

    dropdown.addEventListener("mouseleave", function () {
      if (!isDesktop()) return;
      scheduleClose(item);
    });
  });

  if (hamburger) {
    hamburger.addEventListener("click", function () {
      if (isDesktop()) return;
      toggleMobileMenu();
    });

    hamburger.addEventListener("keydown", function (event) {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        if (isDesktop()) return;
        toggleMobileMenu();
      }
    });
  }

  document.addEventListener("click", function (event) {
    const clickedInsideNavbar = navbar.contains(event.target);

    if (isDesktop()) {
      if (!clickedInsideNavbar) {
        closeAllDesktopItems();
      }
      return;
    }

    if (!clickedInsideNavbar) {
      closeMobileMenu();
    }
  });

  document.addEventListener("keydown", function (event) {
  if (event.key !== "Escape") return;

  const hadDesktopDropdownOpen = isDesktop() &&
    dropdownParents.some((item) => item.classList.contains("is-open"));

  const hadMobileMenuOpen =
    !isDesktop() && navPanel.classList.contains("active");

  const hadMobileDropdownOpen =
    !isDesktop() &&
    dropdownParents.some((item) => item.classList.contains("active"));

  clearDesktopTimers();
  closeAllDesktopItems();

  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur();
  }

  if (hadDesktopDropdownOpen) {
    suppressDesktopHoverOnce();
    return;
  }

  if (!isDesktop() && (hadMobileMenuOpen || hadMobileDropdownOpen)) {
    closeMobileMenu();

    if (hamburger) {
      hamburger.focus();
    }
  }
});

  navPanel.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", function () {
      if (!isDesktop() && !link.closest(".dropdown-parent > .nav-link")) {
        closeMobileMenu();
      }
    });
  });

  if (typeof desktopMedia.addEventListener === "function") {
    desktopMedia.addEventListener("change", resetForBreakpoint);
  } else if (typeof desktopMedia.addEventListener === "function") {
    desktopMedia.addEventListener(resetForBreakpoint);
  }

  resetForBreakpoint();
});
