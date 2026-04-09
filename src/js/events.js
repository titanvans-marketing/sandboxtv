document.addEventListener("DOMContentLoaded", () => {
  const eventCards = document.querySelectorAll("[data-event-card]");
  const mapToggleButtons = document.querySelectorAll("[data-event-map-toggle]");
  const mapCloseButtons = document.querySelectorAll("[data-event-map-close]");
  const weatherToggleButtons = document.querySelectorAll(
    "[data-weather-toggle]",
  );
  const weatherCloseButtons = document.querySelectorAll("[data-weather-close]");
  const calendarDownloadButtons = document.querySelectorAll(
    "[data-calendar-download]",
  );

  function setToggleButtonState(button, isOpen) {
    if (!button) return;

    const label = button.querySelector(".event-btn__text");
    const openLabel = button.getAttribute("data-open-label") || "Hide";
    const closedLabel = button.getAttribute("data-closed-label") || "View";

    button.setAttribute("aria-expanded", isOpen ? "true" : "false");

    if (label) {
      label.textContent = isOpen ? openLabel : closedLabel;
    }
  }

  function closeAllPanels(exceptId = null) {
    eventCards.forEach((card) => {
      const panels = card.querySelectorAll(
        "[data-event-map-panel], [data-event-weather-panel]",
      );
      const toggles = card.querySelectorAll(
        "[data-event-map-toggle], [data-weather-toggle]",
      );

      panels.forEach((panel) => {
        if (exceptId && panel.id === exceptId) return;
        panel.hidden = true;
      });

      toggles.forEach((toggle) => {
        const targetId = toggle.getAttribute("data-target");
        if (exceptId && targetId === exceptId) return;
        setToggleButtonState(toggle, false);
      });

      const hasOpenPanel = Array.from(
        card.querySelectorAll(
          "[data-event-map-panel], [data-event-weather-panel]",
        ),
      ).some((panel) => !panel.hidden);

      card.classList.toggle("is-map-open", hasOpenPanel);
    });
  }

  function openPanel(targetId) {
    const panel = document.getElementById(targetId);
    if (!panel) return;

    const card = panel.closest("[data-event-card]");
    const toggle = card?.querySelector(
      `[data-target="${targetId}"][data-event-map-toggle], [data-target="${targetId}"][data-weather-toggle]`,
    );

    closeAllPanels(targetId);

    panel.hidden = false;
    card?.classList.add("is-map-open");
    setToggleButtonState(toggle, true);

    requestAnimationFrame(() => {
      panel.scrollIntoView({
        behavior: "smooth",
        block: "nearest",
      });
    });
  }

  function closePanel(targetId) {
    const panel = document.getElementById(targetId);
    if (!panel) return;

    const card = panel.closest("[data-event-card]");
    const toggle = card?.querySelector(
      `[data-target="${targetId}"][data-event-map-toggle], [data-target="${targetId}"][data-weather-toggle]`,
    );

    panel.hidden = true;
    setToggleButtonState(toggle, false);

    const hasOpenPanel = Array.from(
      card?.querySelectorAll(
        "[data-event-map-panel], [data-event-weather-panel]",
      ) || [],
    ).some((item) => !item.hidden);

    card?.classList.toggle("is-map-open", hasOpenPanel);
  }

  mapToggleButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const targetId = button.getAttribute("data-target");
      if (!targetId) return;

      const panel = document.getElementById(targetId);
      if (!panel) return;

      const isOpen = !panel.hidden;
      if (isOpen) {
        closePanel(targetId);
      } else {
        openPanel(targetId);
      }
    });
  });

  weatherToggleButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const targetId = button.getAttribute("data-target");
      if (!targetId) return;

      const panel = document.getElementById(targetId);
      if (!panel) return;

      const isOpen = !panel.hidden;
      if (isOpen) {
        closePanel(targetId);
      } else {
        openPanel(targetId);
      }
    });
  });

  mapCloseButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const targetId = button.getAttribute("data-target");
      if (!targetId) return;
      closePanel(targetId);
    });
  });

  weatherCloseButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const targetId = button.getAttribute("data-target");
      if (!targetId) return;
      closePanel(targetId);
    });
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeAllPanels();
    }
  });

  function escapeIcsText(value) {
    return String(value || "")
      .replace(/\\/g, "\\\\")
      .replace(/\n/g, "\\n")
      .replace(/,/g, "\\,")
      .replace(/;/g, "\\;");
  }

  function createIcsContent({ title, description, location, start, end }) {
    const uid = `${Date.now()}-${Math.random().toString(36).slice(2)}@titanvans.com`;
    const stamp = new Date()
      .toISOString()
      .replace(/[-:]/g, "")
      .replace(/\.\d{3}Z$/, "Z");

    return [
      "BEGIN:VCALENDAR",
      "VERSION:2.0",
      "PRODID:-//Titan Vans//Events//EN",
      "CALSCALE:GREGORIAN",
      "BEGIN:VEVENT",
      `UID:${uid}`,
      `DTSTAMP:${stamp}`,
      `DTSTART;VALUE=DATE:${start}`,
      `DTEND;VALUE=DATE:${end}`,
      `SUMMARY:${escapeIcsText(title)}`,
      `DESCRIPTION:${escapeIcsText(description)}`,
      `LOCATION:${escapeIcsText(location)}`,
      "END:VEVENT",
      "END:VCALENDAR",
    ].join("\r\n");
  }

  calendarDownloadButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const title = button.getAttribute("data-title") || "";
      const description = button.getAttribute("data-description") || "";
      const location = button.getAttribute("data-location") || "";
      const start = button.getAttribute("data-start") || "";
      const end = button.getAttribute("data-end") || "";
      const filename = button.getAttribute("data-filename") || "event.ics";

      if (!title || !start || !end) return;

      const icsContent = createIcsContent({
        title,
        description,
        location,
        start,
        end,
      });

      const blob = new Blob([icsContent], {
        type: "text/calendar;charset=utf-8",
      });
      const url = URL.createObjectURL(blob);

      const link = document.createElement("a");
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      link.remove();

      setTimeout(() => {
        URL.revokeObjectURL(url);
      }, 1000);
    });
  });
});
