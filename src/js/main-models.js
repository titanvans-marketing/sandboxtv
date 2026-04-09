document.addEventListener("DOMContentLoaded", function () {
  const navbar = document.querySelector(".navbar");
  const navMenu = document.querySelector(".nav-menu");
  const hamburger = document.querySelector(".hamburger");
  const dropdownParents = Array.from(
    document.querySelectorAll(".nav-item.dropdown-parent")
  );

  if (!navbar || !navMenu) {
    return;
  }

  const desktopMedia = window.matchMedia("(min-width: 1201px)");
  const OPEN_DELAY = 90;
  const CLOSE_DELAY = 180;

  let openTimer = null;
  let closeTimer = null;

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

  function closeDesktopItem(item) {
    if (!item) return;
    item.classList.remove("is-open");
    setExpanded(item, false);
  }

  function openDesktopItem(item) {
    if (!item) return;

    dropdownParents.forEach((otherItem) => {
      if (otherItem !== item) {
        closeDesktopItem(otherItem);
      }
    });

    item.classList.add("is-open");
    setExpanded(item, true);
  }

  function closeAllDesktopItems() {
    dropdownParents.forEach((item) => {
      closeDesktopItem(item);
    });
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
    navMenu.classList.remove("active");

    if (hamburger) {
      hamburger.classList.remove("active");
      hamburger.setAttribute("aria-expanded", "false");
    }

    closeAllMobileDropdowns();
    document.body.classList.remove("nav-open");
  }

  function openMobileMenu() {
    navMenu.classList.add("active");

    if (hamburger) {
      hamburger.classList.add("active");
      hamburger.setAttribute("aria-expanded", "true");
    }

    document.body.classList.add("nav-open");
  }

  function toggleMobileMenu() {
    if (navMenu.classList.contains("active")) {
      closeMobileMenu();
    } else {
      openMobileMenu();
    }
  }

  function resetForBreakpoint() {
    clearDesktopTimers();
    closeAllDesktopItems();

    if (isDesktop()) {
      navMenu.classList.remove("active");

      if (hamburger) {
        hamburger.classList.remove("active");
        hamburger.setAttribute("aria-expanded", "false");
      }

      dropdownParents.forEach((item) => {
        item.classList.remove("active");
        setExpanded(item, false);
      });

      document.body.classList.remove("nav-open");
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

    closeAllDesktopItems();

    if (!isDesktop()) {
      closeMobileMenu();
    }
  });

  navMenu.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", function () {
      if (!isDesktop() && !link.closest(".dropdown-parent > .nav-link")) {
        closeMobileMenu();
      }
    });
  });

  if (typeof desktopMedia.addEventListener === "function") {
    desktopMedia.addEventListener("change", resetForBreakpoint);
  } else if (typeof desktopMedia.addListener === "function") {
    desktopMedia.addListener(resetForBreakpoint);
  }

  resetForBreakpoint();
});