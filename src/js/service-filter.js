(function () {
  const MODEL_KEY = "titan_selected_model_v1";

  function safeLower(value) {
    return String(value || "")
      .trim()
      .toLowerCase();
  }

  function getBrowserId(root) {
    return root.getAttribute("data-service-browser-id") || "";
  }

  function getSortStorageKey(root) {
    return `titan_selected_service_sort_v1__${getBrowserId(root)}`;
  }

  function getSearchStorageKey(root) {
    return `titan_selected_service_search_v1__${getBrowserId(root)}`;
  }

  function getModelInputs(root) {
    return Array.from(root.querySelectorAll(".model-pill__input"));
  }

  function getSortInputs(root) {
    return Array.from(root.querySelectorAll(".sort-pill__input"));
  }

  function getSelectedModel(root) {
    const checked = root.querySelector(".model-pill__input:checked");

    if (checked && checked.value) return checked.value;

    return localStorage.getItem(MODEL_KEY) || "";
  }

  function setSelectedModel(model) {
    if (!model) {
      localStorage.removeItem(MODEL_KEY);
      return;
    }

    localStorage.setItem(MODEL_KEY, model);
  }

  function getSelectedSort(root) {
    const checked = root.querySelector(".sort-pill__input:checked");

    if (checked && checked.value) return checked.value;

    return (
      localStorage.getItem(getSortStorageKey(root)) ||
      root.querySelector(".sort-pill__input")?.value ||
      "recent"
    );
  }

  function setSelectedSort(root, sortValue) {
    localStorage.setItem(
      getSortStorageKey(root),
      sortValue || root.querySelector(".sort-pill__input")?.value || "recent",
    );
  }

  function getSearchQuery(root) {
    return localStorage.getItem(getSearchStorageKey(root)) || "";
  }

  function setSearchQuery(root, query) {
    const normalized = String(query || "").trim();

    if (!normalized) {
      localStorage.removeItem(getSearchStorageKey(root));
      return;
    }

    localStorage.setItem(getSearchStorageKey(root), normalized);
  }

  function getGrid(root) {
    const browserId = getBrowserId(root);

    return document.querySelector(
      `[data-service-grid][data-service-browser-id="${browserId}"]`,
    );
  }

  function getCards(root) {
    const grid = getGrid(root);
    return grid ? Array.from(grid.querySelectorAll("[data-service-card]")) : [];
  }

  function getEmptyState(root) {
    const browserId = getBrowserId(root);

    return document.querySelector(
      `[data-service-empty][data-service-browser-id="${browserId}"]`,
    );
  }

  function getResultsTitle(root) {
    const browserId = getBrowserId(root);

    return document.querySelector(
      `[data-service-results-title][data-service-browser-id="${browserId}"]`,
    );
  }

  function getServiceModelHeading(root) {
    const browserId = getBrowserId(root);

    return document.querySelector(
      `[data-service-model-heading][data-service-browser-id="${browserId}"]`,
    );
  }

  function updateServiceModelHeading(root, model) {
    const heading = getServiceModelHeading(root);
    if (!heading) return;

    heading.textContent = model ? model : "";
  }

  function parseModels(raw) {
    return String(raw || "")
      .split(",")
      .map((item) => item.trim())
      .filter(Boolean);
  }

  function matchesModel(card, selectedModel) {
    if (!selectedModel) return true;

    const models = parseModels(card.getAttribute("data-service-models"));
    if (!models.length) return true;

    return models.includes(selectedModel);
  }

  function matchesSearch(card, query) {
    if (!query) return true;

    const serviceName = card.getAttribute("data-service-name") || "";
    const serviceId = card.getAttribute("data-service-id") || "";
    const models = card.getAttribute("data-service-models") || "";
    const searchBlob = card.getAttribute("data-service-search") || "";
    const text = `${serviceName} ${serviceId} ${models} ${searchBlob}`;

    return safeLower(text).includes(safeLower(query));
  }

  function getSortValueForDate(card) {
    const raw = card.getAttribute("data-service-recent") || "";
    const time = Date.parse(raw);
    return Number.isFinite(time) ? time : 0;
  }

  function getSortValueForPopularity(card) {
    const raw = Number(card.getAttribute("data-service-popularity"));
    return Number.isFinite(raw) ? raw : 0;
  }

  function getSortValueForName(card) {
    return safeLower(card.getAttribute("data-service-name") || "");
  }

  function sortCards(cards, sortValue) {
    const sorted = [...cards];

    if (sortValue === "name") {
      sorted.sort((a, b) =>
        getSortValueForName(a).localeCompare(getSortValueForName(b)),
      );
      return sorted;
    }

    if (sortValue === "popular") {
      sorted.sort(
        (a, b) => getSortValueForPopularity(b) - getSortValueForPopularity(a),
      );
      return sorted;
    }

    sorted.sort((a, b) => getSortValueForDate(b) - getSortValueForDate(a));
    return sorted;
  }

  function updateServiceModelBadges(root, selectedModel) {
    const grid = getGrid(root);
    if (!grid) return;

    const safeSelectedModel = String(selectedModel || "").trim() || "None";

    grid.querySelectorAll("[data-model-badge]").forEach((badge) => {
      badge.textContent = safeSelectedModel;
    });
  }

  function syncModelRadios(root, model) {
    const hasModel = !!model;

    getModelInputs(root).forEach((radio) => {
      const isSelected = radio.value === model;
      const pill = radio.closest(".model-pill");

      radio.checked = isSelected;

      if (pill) {
        pill.classList.toggle("is-selected", isSelected);
      }
    });

    root.querySelectorAll(".vehicle-model").forEach((hidden) => {
      hidden.value = model || "";
    });

    root.classList.toggle("is-model-selected", hasModel);

    if (!hasModel) {
      root.classList.remove("is-model-expanded");
    }
  }

  function syncSortRadios(root, sortValue) {
    const resolved =
      sortValue || root.querySelector(".sort-pill__input")?.value || "recent";

    getSortInputs(root).forEach((radio) => {
      radio.checked = radio.value === resolved;
    });

    root.querySelectorAll(".service-sort").forEach((hidden) => {
      hidden.value = resolved;
    });
  }

  function syncSearchInputs(root, query) {
    root.querySelectorAll("[data-service-search-input]").forEach((input) => {
      input.value = query || "";
    });
  }

  function updateFilterNote(root, model, sortValue, query, visibleCount) {
    const note = root.querySelector("[data-service-filter-note]");
    if (!note) return;

    if (query) {
      note.textContent = `${visibleCount} result${
        visibleCount === 1 ? "" : "s"
      } for "${query}"`;
      return;
    }

    if (model) {
      note.textContent = `Showing ${visibleCount} result${
        visibleCount === 1 ? "" : "s"
      } for ${model}`;
      return;
    }

    note.textContent = `Showing ${visibleCount} result${
      visibleCount === 1 ? "" : "s"
    }`;
  }

  function updateResultsTitle(root, model, sortValue, query, visibleCount) {
    const title = getResultsTitle(root);
    if (!title) return;

    if (query) {
      title.textContent = `Results for "${query}" (${visibleCount})`;
      return;
    }

    if (model) {
      title.textContent = `${model} Results (${visibleCount})`;
      return;
    }

    title.textContent = `Showing All Results (${visibleCount})`;
  }

  function applyFiltersAndSort(root) {
    const grid = getGrid(root);
    if (!grid) return;

    const cards = getCards(root);
    const emptyState = getEmptyState(root);

    const selectedModel = getSelectedModel(root);
    const sortValue = getSelectedSort(root);
    const query = getSearchQuery(root);

    root.classList.toggle("is-model-selected", !!selectedModel);

    updateServiceModelBadges(root, selectedModel);
    updateServiceModelHeading(root, selectedModel);

    const visibleCards = cards.filter((card) => {
      return matchesModel(card, selectedModel) && matchesSearch(card, query);
    });

    cards.forEach((card) => {
      const isVisible = visibleCards.includes(card);

      card.classList.toggle("is-hidden", !isVisible);
      card.hidden = !isVisible;
      card.style.display = isVisible ? "" : "none";
    });

    const sortedVisibleCards = sortCards(visibleCards, sortValue);

    sortedVisibleCards.forEach((card) => {
      grid.appendChild(card);
    });

    if (emptyState) {
      const hasVisible = sortedVisibleCards.length > 0;
      emptyState.classList.toggle("is-hidden", hasVisible);
      emptyState.hidden = hasVisible;
      emptyState.style.display = hasVisible ? "none" : "";
    }

    updateFilterNote(
      root,
      selectedModel,
      sortValue,
      query,
      sortedVisibleCards.length,
    );

    updateResultsTitle(
      root,
      selectedModel,
      sortValue,
      query,
      sortedVisibleCards.length,
    );
  }

  function openSearchPanel(root) {
    const panel = root.querySelector("[data-service-search-panel]");
    if (!panel) return;

    panel.classList.remove("is-hidden");
    panel.hidden = false;

    const input = panel.querySelector("[data-service-search-input]");
    if (input) {
      window.requestAnimationFrame(() => {
        input.focus();
        input.select();
      });
    }
  }

  function closeSearchPanel(root) {
    const panel = root.querySelector("[data-service-search-panel]");
    if (!panel) return;

    panel.classList.add("is-hidden");
    panel.hidden = true;
  }

  function bindModelFilters(root) {
    const radios = getModelInputs(root);

    const tray =
      root.querySelector("[data-model-pill-tray]") ||
      root.querySelector(
        ".service-model-filter__group--models .service-model-filter__pill-tray",
      );

    const shell =
      root.querySelector(".service-model-filter__models-shell") || tray;

    if (!tray || !radios.length) return;

    let clearTimer = null;
    let previewLeaveTimer = null;
    let naturalFullWidth = 0;

    let isPointerDown = false;
    let activePointerId = null;
    let dragStartX = 0;
    let dragStartLeft = 0;
    let didDrag = false;
    let activePill = null;
    let suppressClickUntil = 0;

    function trayCanScroll() {
      return tray.scrollWidth > tray.clientWidth + 4;
    }

    function getSelectedPill() {
      return root.querySelector(
        ".service-model-filter__group--models .model-pill.is-selected",
      );
    }

    function getTrayPaddingX() {
      const style = window.getComputedStyle(tray);
      const left = parseFloat(style.paddingLeft || "0") || 0;
      const right = parseFloat(style.paddingRight || "0") || 0;
      return left + right;
    }

    function setTrayWidth(width) {
      if (!width || Number.isNaN(width)) return;
      tray.style.setProperty("--tray-animated-width", `${Math.ceil(width)}px`);
    }

    function clearTrayWidth() {
      tray.style.removeProperty("--tray-animated-width");
    }

    function scrollTrayToStart(behavior = "smooth") {
      tray.scrollTo({
        left: 0,
        behavior,
      });
    }

    function measureNaturalFullTrayWidth() {
      const wasSelected = root.classList.contains("is-model-selected");
      const wasExpanded = root.classList.contains("is-model-expanded");
      const wasPreview = root.classList.contains("is-model-preview");

      const previousInlineWidth = tray.style.width;
      const previousVar = tray.style.getPropertyValue("--tray-animated-width");

      root.classList.remove("is-model-selected");
      root.classList.remove("is-model-expanded");
      root.classList.remove("is-model-preview");

      tray.style.width = "auto";
      tray.style.removeProperty("--tray-animated-width");

      const measuredWidth = Math.ceil(
        shell.getBoundingClientRect().width ||
          tray.scrollWidth ||
          tray.offsetWidth,
      );

      tray.style.width = previousInlineWidth;
      if (previousVar) {
        tray.style.setProperty("--tray-animated-width", previousVar);
      }

      if (wasSelected) root.classList.add("is-model-selected");
      if (wasExpanded) root.classList.add("is-model-expanded");
      if (wasPreview) root.classList.add("is-model-preview");

      return measuredWidth;
    }

    function updateNaturalFullWidth() {
      naturalFullWidth = measureNaturalFullTrayWidth();
      return naturalFullWidth;
    }

    function getExpandedWidth() {
      return naturalFullWidth || updateNaturalFullWidth();
    }

    function measureCollapsedWidth() {
      const selectedPill = getSelectedPill();
      if (!selectedPill) return getExpandedWidth();

      return Math.ceil(selectedPill.offsetWidth + getTrayPaddingX());
    }

    function animateTrayWidth(targetWidth) {
      const currentWidth = tray.getBoundingClientRect().width;
      setTrayWidth(currentWidth);

      window.requestAnimationFrame(() => {
        setTrayWidth(targetWidth);
      });
    }

    function previewOpenTray() {
      if (!root.classList.contains("is-model-selected")) return;

      window.clearTimeout(previewLeaveTimer);
      root.classList.remove("is-model-expanded");
      root.classList.add("is-model-preview");

      animateTrayWidth(getExpandedWidth());

      window.requestAnimationFrame(() => {
        scrollSelectedPillIntoView("smooth", "center");
      });
    }

    function previewCloseTray() {
      if (!root.classList.contains("is-model-selected")) return;

      window.clearTimeout(previewLeaveTimer);
      previewLeaveTimer = window.setTimeout(() => {
        root.classList.remove("is-model-preview");
        root.classList.remove("is-model-expanded");
        animateTrayWidth(measureCollapsedWidth());

        window.requestAnimationFrame(() => {
          scrollSelectedPillIntoView("smooth", "nearest");
        });
      }, 10);
    }

    function refreshTrayWidth() {
      const hasSelected = root.classList.contains("is-model-selected");
      const isPreview = root.classList.contains("is-model-preview");
      const isExpanded = root.classList.contains("is-model-expanded");

      updateNaturalFullWidth();

      if (!hasSelected) {
        clearTrayWidth();
        return;
      }

      if (isPreview || isExpanded) {
        animateTrayWidth(getExpandedWidth());

        window.requestAnimationFrame(() => {
          scrollSelectedPillIntoView("auto", "center");
        });
        return;
      }

      animateTrayWidth(measureCollapsedWidth());

      window.requestAnimationFrame(() => {
        scrollSelectedPillIntoView("auto", "nearest");
      });
    }

    function selectModel(selectedValue) {
      window.clearTimeout(previewLeaveTimer);
      root.classList.remove("is-model-preview");
      root.classList.remove("is-model-expanded");

      updateNaturalFullWidth();
      animateTrayWidth(getExpandedWidth());

      window.requestAnimationFrame(() => {
        setSelectedModel(selectedValue);
        syncModelRadios(root, selectedValue);
        applyFiltersAndSort(root);

        window.requestAnimationFrame(() => {
          scrollSelectedPillIntoView("smooth", "center");

          root.classList.remove("is-model-expanded");
          root.classList.remove("is-model-preview");
          animateTrayWidth(measureCollapsedWidth());

          window.requestAnimationFrame(() => {
            scrollSelectedPillIntoView("smooth", "nearest");
          });
        });
      });
    }

    function clearModel() {
      window.clearTimeout(previewLeaveTimer);
      root.classList.remove("is-model-preview");
      root.classList.add("is-model-expanded");

      updateNaturalFullWidth();
      animateTrayWidth(getExpandedWidth());

      window.clearTimeout(clearTimer);
      clearTimer = window.setTimeout(() => {
        setSelectedModel("");
        syncModelRadios(root, "");
        applyFiltersAndSort(root);

        root.classList.remove("is-model-expanded");
        root.classList.remove("is-model-preview");
        clearTrayWidth();
        updateNaturalFullWidth();

        scrollTrayToStart("smooth");
      }, 260);
    }

    function handlePillSelectionFromElement(pill) {
      if (!pill) return;

      const radio = pill.querySelector(".model-pill__input");
      if (!radio) return;

      const currentModel = getSelectedModel(root);
      const clickedValue = radio.value || "";

      if (currentModel === clickedValue) {
        clearModel();
        return;
      }

      selectModel(clickedValue);
    }

    tray.addEventListener(
      "wheel",
      (event) => {
        if (!trayCanScroll()) return;

        const mostlyVertical = Math.abs(event.deltaY) > Math.abs(event.deltaX);
        if (!mostlyVertical) return;

        event.preventDefault();
        tray.scrollBy({
          left: event.deltaY,
          behavior: "auto",
        });
      },
      { passive: false },
    );

    tray.addEventListener("pointerdown", (event) => {
      if (!trayCanScroll()) return;
      if (event.pointerType === "mouse" && event.button !== 0) return;

      isPointerDown = true;
      activePointerId = event.pointerId;
      didDrag = false;
      dragStartX = event.clientX;
      dragStartLeft = tray.scrollLeft;
      activePill = event.target.closest(".model-pill");
    });

    tray.addEventListener("pointermove", (event) => {
      if (!isPointerDown) return;
      if (activePointerId !== null && event.pointerId !== activePointerId)
        return;

      const deltaX = event.clientX - dragStartX;

      if (!didDrag && Math.abs(deltaX) > 6) {
        didDrag = true;
        tray.classList.add("is-dragging");

        if (tray.setPointerCapture) {
          try {
            tray.setPointerCapture(event.pointerId);
          } catch (error) {
            // no-op
          }
        }
      }

      if (!didDrag) return;

      event.preventDefault();
      tray.scrollLeft = dragStartLeft - deltaX;
    });

    function endPointerDrag(event) {
      if (!isPointerDown) return;
      if (activePointerId !== null && event.pointerId !== activePointerId)
        return;

      const pillToHandle = activePill;
      const wasDrag = didDrag;

      isPointerDown = false;
      activePointerId = null;
      activePill = null;
      didDrag = false;

      tray.classList.remove("is-dragging");

      if (tray.releasePointerCapture) {
        try {
          tray.releasePointerCapture(event.pointerId);
        } catch (error) {
          // no-op
        }
      }

      if (wasDrag) {
        suppressClickUntil = Date.now() + 100;
        return;
      }

      if (!pillToHandle) return;

      suppressClickUntil = Date.now() + 100;
      handlePillSelectionFromElement(pillToHandle);
    }

    tray.addEventListener("pointerup", endPointerDrag);
    tray.addEventListener("pointercancel", endPointerDrag);

    tray.addEventListener("mouseenter", () => {
      previewOpenTray();
    });

    tray.addEventListener("mouseleave", () => {
      previewCloseTray();
    });

    tray.addEventListener("focusin", () => {
      previewOpenTray();
    });

    tray.addEventListener("focusout", () => {
      window.requestAnimationFrame(() => {
        const stillInside = tray.contains(document.activeElement);
        if (!stillInside) {
          previewCloseTray();
        }
      });
    });

    radios.forEach((radio) => {
      const pill = radio.closest(".model-pill");
      if (!pill) return;

      pill.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (Date.now() < suppressClickUntil) {
          return;
        }

        handlePillSelectionFromElement(pill);
      });

      radio.addEventListener("change", () => {
        if (!radio.checked) return;
        handlePillSelectionFromElement(pill);
      });
    });

    window.addEventListener("resize", () => {
      refreshTrayWidth();
    });

    window.addEventListener("load", () => {
      refreshTrayWidth();
    });

    updateNaturalFullWidth();
    refreshTrayWidth();

    if (root.classList.contains("is-model-selected")) {
      scrollSelectedPillIntoView("auto", "nearest");
    }
  }

  function bindSortFilters(root) {
    getSortInputs(root).forEach((radio) => {
      radio.addEventListener("change", () => {
        const selected = radio.value || "recent";
        setSelectedSort(root, selected);
        syncSortRadios(root, selected);
        applyFiltersAndSort(root);
      });
    });
  }

  function bindSearchUI(root) {
    root.querySelectorAll("[data-service-search-open]").forEach((button) => {
      button.addEventListener("click", () => {
        openSearchPanel(root);
      });
    });

    root.querySelectorAll("[data-service-search-close]").forEach((button) => {
      button.addEventListener("click", () => {
        closeSearchPanel(root);
      });
    });

    root.querySelectorAll("[data-service-search-input]").forEach((input) => {
      input.addEventListener("input", () => {
        const query = input.value || "";
        setSearchQuery(root, query);
        syncSearchInputs(root, query);
        applyFiltersAndSort(root);
      });

      input.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
          closeSearchPanel(root);
          return;
        }

        if (event.key === "Enter") {
          event.preventDefault();
          applyFiltersAndSort(root);
          closeSearchPanel(root);

          const grid = getGrid(root);
          if (grid) {
            grid.scrollIntoView({
              behavior: "smooth",
              block: "start",
            });
          }
        }
      });

      input.addEventListener("search", () => {
        const query = input.value || "";
        setSearchQuery(root, query);
        syncSearchInputs(root, query);
        applyFiltersAndSort(root);
      });
    });
  }

  function restoreInitialState(root) {
    const model = getSelectedModel(root);
    const sortValue = getSelectedSort(root);
    const query = getSearchQuery(root);

    syncModelRadios(root, model);
    syncSortRadios(root, sortValue);
    syncSearchInputs(root, query);

    const panel = root.querySelector("[data-service-search-panel]");
    if (panel) {
      if (String(query || "").trim()) {
        panel.classList.remove("is-hidden");
        panel.hidden = false;
      } else {
        panel.classList.add("is-hidden");
        panel.hidden = true;
      }
    }
  }

  function bindStorageSync(root) {
    const sortKey = getSortStorageKey(root);
    const searchKey = getSearchStorageKey(root);

    window.addEventListener("storage", (e) => {
      if (e.key === MODEL_KEY || e.key === sortKey || e.key === searchKey) {
        restoreInitialState(root);
        applyFiltersAndSort(root);
      }
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    const roots = Array.from(
      document.querySelectorAll("[data-service-filter-root]"),
    );
    if (!roots.length) return;

    roots.forEach((root) => {
      if (!getGrid(root)) return;
      bindModelFilters(root);
      bindSortFilters(root);
      bindSearchUI(root);
      bindStorageSync(root);
      restoreInitialState(root);
      applyFiltersAndSort(root);
    });
  });
})();
