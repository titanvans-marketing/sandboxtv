(function () {
  const Core = window.TitanCartCore;
  if (!Core) return;

  const {
    APP_PAGE_PATH,
    APP_CART_PAGE_PATH,
    APP_SCHEDULING_PAGE_PATH,
    CART_KEY,
    MODEL_KEY,
    SCHEDULE_KEY,
    getCart,
    setCart,
    clearCart,
    getSelectedModel,
    setSelectedModel,
    getScheduleSelections,
    getScheduleSummaryText,
    parseMoneyToNumber,
    formatMoney,
    escapeHtml,
    buildCartSummary,
    getCartItemMeta,
    getCartTotals,
    updateCartBadge,
    queueCartSessionSync,
    bindContinueToSchedulingButton,
  } = Core;

  const DEFAULT_CART_IMAGE =
    "/assets/images/placeholders/service-placeholder.jpg";

  function updateFloatingCartVisibility(count) {
    document.querySelectorAll(".cart-link--floating").forEach((floating) => {
      floating.classList.toggle("is-hidden", !count || count < 1);
    });
  }

  function refreshBadgeAndFloatingCart() {
    const count = updateCartBadge();
    updateFloatingCartVisibility(count);
  }

  function bindFloatingCartScrollVisibility() {
    const buttons = document.querySelectorAll(".cart-link--floating");
    if (!buttons.length) return;

    function update() {
      const show = window.scrollY > 200;

      buttons.forEach((btn) => {
        btn.classList.toggle("is-visible", show);
      });
    }

    update();
    window.addEventListener("scroll", update, { passive: true });
  }

  function updateSelectedModelUI(model) {
    document.querySelectorAll(".vehicle-model").forEach((hidden) => {
      hidden.value = model || "";
    });

    document.querySelectorAll(".service-model-note").forEach((note) => {
      note.textContent = model
        ? `Selected model: ${model}. You can now add services or upgrades to your cart.`
        : "Choose a model above to add services or upgrades to your cart.";
    });

    document.querySelectorAll("[data-model-badge]").forEach((el) => {
      el.textContent = model || "None";
    });

    document.querySelectorAll(".service-model-heading").forEach((heading) => {
      heading.textContent = model ? model : "";
    });
  }

  function getButtonDefaultLabel() {
    return "Add to Cart";
  }

  function ensureServiceCardButtonMarkup(button) {
    if (!button) {
      return { label: null, icon: null };
    }

    let label = button.querySelector(".service-card-button__label");
    let iconWrap = button.querySelector(".service-card-button__icon");
    let icon = iconWrap ? iconWrap.querySelector("i") : null;

    if (label && icon) {
      return { label, icon };
    }

    const existingText =
      String(button.textContent || "").trim() || getButtonDefaultLabel();

    button.textContent = "";

    label = document.createElement("span");
    label.className = "service-card-button__label";
    label.textContent = existingText;

    iconWrap = document.createElement("span");
    iconWrap.className = "service-card-button__icon";
    iconWrap.setAttribute("aria-hidden", "true");

    icon = document.createElement("i");
    icon.className = "fa-solid fa-cart-plus";

    iconWrap.appendChild(icon);
    button.appendChild(label);
    button.appendChild(iconWrap);

    return { label, icon };
  }

  function setServiceCardButtonState(button, state) {
    if (!button) return;

    const { label, icon } = ensureServiceCardButtonMarkup(button);

    button.classList.remove("is-added", "is-remove");
    button.setAttribute("data-button-state", state);

    if (state === "remove") {
      button.classList.add("is-remove");
      button.setAttribute("aria-pressed", "true");

      if (label) {
        label.textContent = "Remove Item";
      }

      if (icon) {
        icon.className = "fa-solid fa-xmark fa-sm";
      }

      button.closest(".service-options")?.classList.add("in-cart");
      return;
    }

    if (state === "added") {
      button.classList.add("is-added");
      button.setAttribute("aria-pressed", "true");

      if (label) {
        label.textContent = "Added to Cart";
      }

      if (icon) {
        icon.className = "fa-solid fa-thumbs-up fa-sm";
      }

      button.closest(".service-options")?.classList.add("in-cart");
      return;
    }
    
    button.setAttribute("aria-pressed", "false");

    if (label) {
      label.textContent = getButtonDefaultLabel();
    }

    if (icon) {
      icon.className = "fa-solid fa-cart-plus fa-sm";
    }

    button.closest(".service-options")?.classList.remove("in-cart");
  }

  function setButtonToDefault(btn) {
    setServiceCardButtonState(btn, "default");
  }

  function setButtonToAdded(btn) {
    setServiceCardButtonState(btn, "added");
  }

  function setButtonToRemove(btn) {
    setServiceCardButtonState(btn, "remove");
  }

  function getServiceDataFromButton(btn, model) {
    const serviceId = btn.getAttribute("data-service-id") || "";
    const serviceName = btn.getAttribute("data-service-name") || "";
    const servicePrice = btn.getAttribute("data-service-price") || "";
    const serviceType = btn.getAttribute("data-service-type") || "service";
    const serviceLabel = btn.getAttribute("data-service-label") || "";
    const serviceDuration = btn.getAttribute("data-service-duration") || "";
    const serviceDescription =
      btn.getAttribute("data-service-description") || "";
    const serviceImage = btn.getAttribute("data-service-image") || "";
    const requiresDateAttr = btn.getAttribute("data-service-requires-date");
    const requiresDate =
      requiresDateAttr === null ? true : requiresDateAttr === "true";

    return {
      serviceId,
      serviceName,
      servicePrice,
      vehicleModel: model,
      type: serviceType,
      label: serviceLabel,
      duration: serviceDuration,
      description: serviceDescription,
      image: serviceImage,
      requiresDate,
      qty: 1,
      addedAt: new Date().toISOString(),
    };
  }

  function findCartIndexByServiceAndModel(cart, serviceId, model, type = "") {
    return cart.findIndex(
      (x) =>
        x &&
        x.serviceId === serviceId &&
        x.vehicleModel === model &&
        String(x.type || "service") === String(type || "service"),
    );
  }

  function syncRadioGroupsToModel(model) {
    const radioSelector =
      'input[name="vanModel-services"], input[name="vanModel-upgrades"]';

    document.querySelectorAll(radioSelector).forEach((radio) => {
      radio.checked = radio.value === model;
    });
  }

  function syncServiceCardStatesFromCart() {
    const cart = getCart();
    const model = getSelectedModel();

    document.querySelectorAll(".service-add, .upgrade-add").forEach((btn) => {
      const serviceId = btn.getAttribute("data-service-id") || "";
      const serviceType = btn.getAttribute("data-service-type") || "service";

      if (!serviceId) return;

      if (!model) {
        setButtonToDefault(btn);
        return;
      }

      const idx = findCartIndexByServiceAndModel(
        cart,
        serviceId,
        model,
        serviceType,
      );

      if (idx >= 0) {
        setButtonToRemove(btn);
      } else {
        setButtonToDefault(btn);
      }
    });
  }

  function removeOneFromCart(serviceId, model, type = "") {
    const cart = getCart();
    const idx = findCartIndexByServiceAndModel(cart, serviceId, model, type);

    if (idx < 0) return;

    const currentQty = Number(cart[idx]?.qty) || 1;

    if (currentQty > 1) {
      cart[idx].qty = currentQty - 1;
    } else {
      cart.splice(idx, 1);
    }

    setCart(cart);
    refreshBadgeAndFloatingCart();
    syncServiceCardStatesFromCart();
  }

  function addItemToCart(item) {
    const cart = getCart();
    const type = String(item.type || "service");

    const idx = findCartIndexByServiceAndModel(
      cart,
      item.serviceId,
      item.vehicleModel,
      type,
    );

    if (idx >= 0) {
      cart[idx].qty = (Number(cart[idx].qty) || 1) + (Number(item.qty) || 1);
    } else {
      cart.push({
        ...item,
        cartItemKey:
          item.cartItemKey ||
          Core.makeCartItemKey({
            serviceId: item.serviceId,
            vehicleModel: item.vehicleModel,
            type,
          }),
      });
    }

    setCart(cart);
    refreshBadgeAndFloatingCart();
    syncServiceCardStatesFromCart();
  }

  function removeItem(index) {
    const cart = getCart();
    if (!cart[index]) return;

    cart.splice(index, 1);
    setCart(cart);
    refreshBadgeAndFloatingCart();
    syncServiceCardStatesFromCart();
  }

  function setQty(index, qty) {
    const cart = getCart();

    if (!cart[index]) return;

    const q = Math.max(1, Math.min(99, Number(qty) || 1));
    cart[index].qty = q;

    setCart(cart);
    refreshBadgeAndFloatingCart();
    syncServiceCardStatesFromCart();
  }

  function getResolvedItemMeta(item) {
    const meta = getCartItemMeta(item) || {};

    const name = String(
      meta.name ||
        item.serviceName ||
        item.name ||
        item.title ||
        "Selected Item",
    );

    const label = String(meta.label || item.label || item.type || "Service");
    const type = String(meta.type || item.type || "service");
    const description = String(
      meta.description ||
        item.description ||
        "Selected for your vehicle based on your chosen model and cart options.",
    );

    const duration = String(
      meta.duration ||
        item.duration ||
        item.timeNeeded ||
        item.estimated_time ||
        "",
    );

    const price = String(meta.price || item.servicePrice || item.price || "0");

    const image = String(
      meta.image ||
        item.image ||
        item.card_image ||
        item.cardImage ||
        DEFAULT_CART_IMAGE,
    );

    const requiresDate =
      typeof meta.requiresDate === "boolean"
        ? meta.requiresDate
        : typeof item.requiresDate === "boolean"
          ? item.requiresDate
          : true;

    return {
      ...meta,
      name,
      label,
      type,
      description,
      duration,
      price,
      image,
      requiresDate,
    };
  }

  function buildCartItemMarkup(item, index) {
    const qty = Number(item.qty) || 1;
    const model = String(item.vehicleModel || "");
    const meta = getResolvedItemMeta(item);
    const unitPrice = parseMoneyToNumber(meta.price);
    const lineTotal = unitPrice * qty;
    const image = meta.image ? String(meta.image) : "";
    const imageAlt = meta.name || "Cart item";

    return `
      <article class="cart-amazon-card ${meta.type === "upgrade" ? "is-upgrade" : "is-service"}">
        <div class="cart-amazon-card__media">
          ${
            image
              ? `<img
                  src="${escapeHtml(image)}"
                  alt="${escapeHtml(imageAlt)}"
                  loading="lazy"
                  decoding="async"
                  data-cart-item-image
                />`
              : `<div class="cart-amazon-card__media-placeholder">
                  <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
                </div>`
          }
        </div>

        <div class="cart-amazon-card__content">
          <div class="cart-amazon-card__top">
            <div class="cart-amazon-card__badges">
              <span class="cart-pill cart-pill--type">${escapeHtml(meta.label)}</span>
              <span class="cart-pill cart-pill--model">${escapeHtml(model || "Model TBD")}</span>
              <span class="cart-pill cart-pill--date ${meta.requiresDate ? "is-required" : "is-optional"}">
                ${escapeHtml(meta.requiresDate ? "Date Required" : "No Date Needed")}
              </span>
            </div>

            <div class="cart-amazon-card__price">
              ${escapeHtml(formatMoney(lineTotal))}
            </div>
          </div>

          <h3 class="cart-amazon-card__title">${escapeHtml(meta.name)}</h3>

          <p class="cart-amazon-card__desc">
            ${escapeHtml(meta.description)}
          </p>

          <div class="cart-amazon-card__meta">
            <div class="cart-meta-chip">
              <span class="cart-meta-chip__label">Unit Cost</span>
              <strong>${escapeHtml(formatMoney(unitPrice))}</strong>
            </div>

            <div class="cart-meta-chip">
              <span class="cart-meta-chip__label">Time Needed</span>
              <strong>${escapeHtml(meta.duration || "To be confirmed")}</strong>
            </div>

            <div class="cart-meta-chip">
              <span class="cart-meta-chip__label">Vehicle</span>
              <strong>${escapeHtml(model || "Not selected")}</strong>
            </div>
          </div>

          <div class="cart-amazon-card__footer">
            <label class="cart-qty-inline">
              <span>Qty</span>
              <input type="number" min="1" max="99" value="${qty}" data-qty="${index}" />
            </label>

            <button type="button" class="cart-link-btn" data-remove="${index}">
              Remove
            </button>
          </div>
        </div>
      </article>
    `;
  }

  function attachCartImageFallbacks(root) {
    if (!root) return;

    root.querySelectorAll("img[data-cart-item-image]").forEach((img) => {
      img.addEventListener(
        "error",
        () => {
          const parent = img.closest(".cart-amazon-card__media");
          if (!parent) return;

          parent.innerHTML = `
            <div class="cart-amazon-card__media-placeholder">
              <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
            </div>
          `;
        },
        { once: true },
      );
    });
  }

  function renderCartTotals(cart) {
    const totalsRoot = document.getElementById("cartTotals");
    if (!totalsRoot) return;

    if (!cart.length) {
      totalsRoot.innerHTML = "";
      return;
    }

    const totals = getCartTotals(cart);
    const selections = getScheduleSelections();

    totalsRoot.innerHTML = `
      <section class="cart-summary-box__card" aria-label="Order Summary">
        <div class="cart-summary-box__header">
          <h3>Order Summary</h3>
          <p>${totals.itemCount} item${totals.itemCount === 1 ? "" : "s"} selected</p>
        </div>

        <div class="cart-summary-box__rows">
          <div class="cart-summary-row">
            <span>Subtotal</span>
            <strong>${formatMoney(totals.subtotal)}</strong>
          </div>

          <div class="cart-summary-row">
            <span>Estimated Tax</span>
            <strong>${formatMoney(totals.tax)}</strong>
          </div>

          <div class="cart-summary-row cart-summary-row--total">
            <span>Estimated Total</span>
            <strong>${formatMoney(totals.total)}</strong>
          </div>
        </div>

        <div class="cart-summary-box__note">
          <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
          <span>${escapeHtml(getScheduleSummaryText(cart, selections))}</span>
        </div>

        <a
          class="cart-summary-box__cta learn-more"
          href="${APP_SCHEDULING_PAGE_PATH}"
          id="continueToSchedulingBtn"
          data-scheduling-path="${APP_SCHEDULING_PAGE_PATH}"
        >
          Proceed to Scheduling
        </a>

        <a class="cart-summary-box__secondary" href="${APP_PAGE_PATH}">
          Continue Shopping
        </a>
      </section>
    `;

    bindContinueToSchedulingButton({
      button: totalsRoot.querySelector("#continueToSchedulingBtn"),
      schedulingPath: APP_SCHEDULING_PAGE_PATH,
    });
  }

  function attachServicePageHandlers() {
    const modelRadios = document.querySelectorAll(
      'input[name="vanModel-services"], input[name="vanModel-upgrades"]',
    );

    modelRadios.forEach((radio) => {
      radio.addEventListener("change", () => {
        const selected = radio.value || "";
        setSelectedModel(selected);
        syncRadioGroupsToModel(selected);
        updateSelectedModelUI(selected);
        syncServiceCardStatesFromCart();
      });
    });

    const initialModel = getSelectedModel();
    if (initialModel) {
      syncRadioGroupsToModel(initialModel);
    }

    updateSelectedModelUI(initialModel);
    syncServiceCardStatesFromCart();

    document.querySelectorAll(".service-add, .upgrade-add").forEach((btn) => {
      btn.addEventListener("click", () => {
        const model = getSelectedModel();

        if (!model) {
          return;
        }

        const item = getServiceDataFromButton(btn, model);

        if (!item.serviceId || !item.serviceName) return;

        const cart = getCart();
        const idx = findCartIndexByServiceAndModel(
          cart,
          item.serviceId,
          model,
          item.type,
        );
        const existsForModel = idx >= 0;

        if (btn._addedTimer) {
          clearTimeout(btn._addedTimer);
          btn._addedTimer = null;
        }

        if (!existsForModel) {
          addItemToCart(item);

          setButtonToAdded(btn);

          btn._addedTimer = setTimeout(() => {
            const latest = getCart();
            const stillExists =
              findCartIndexByServiceAndModel(
                latest,
                item.serviceId,
                model,
                item.type,
              ) >= 0;

            if (stillExists) {
              setButtonToRemove(btn);
            } else {
              setButtonToDefault(btn);
            }

            btn._addedTimer = null;
          }, 900);
        } else {
          removeOneFromCart(item.serviceId, model, item.type);

          const latest = getCart();
          const stillExists =
            findCartIndexByServiceAndModel(
              latest,
              item.serviceId,
              model,
              item.type,
            ) >= 0;

          if (stillExists) {
            setButtonToRemove(btn);
          } else {
            setButtonToDefault(btn);
          }
        }

        btn.blur();
      });
    });

    document.querySelectorAll(".service-buy-now").forEach((btn) => {
      btn.addEventListener("click", () => {
        const model = getSelectedModel();

        if (!model) {
          return;
        }

        const item = getServiceDataFromButton(btn, model);

        if (!item.serviceId || !item.serviceName) return;

        const cart = getCart();
        const idx = findCartIndexByServiceAndModel(
          cart,
          item.serviceId,
          model,
          item.type,
        );

        if (idx < 0) {
          addItemToCart(item);
        }

        queueCartSessionSync(getCart());
        window.location.href = APP_CART_PAGE_PATH;
      });
    });
  }

  function renderCartPage() {
    const cartRoot = document.getElementById("cartItems");
    if (!cartRoot) return;

    const cart = getCart();
    cartRoot.innerHTML = "";

    if (!cart.length) {
      renderCartTotals([]);
      refreshBadgeAndFloatingCart();

      if (Core.isAppCartPage()) {
        clearCart();
        window.location.href = APP_PAGE_PATH;
      }

      return;
    }

    queueCartSessionSync(cart);

    cartRoot.innerHTML = cart
      .map((item, index) => buildCartItemMarkup(item, index))
      .join("");

    attachCartImageFallbacks(cartRoot);

    cartRoot.querySelectorAll("input[data-qty]").forEach((input) => {
      input.addEventListener("change", () => {
        const idx = Number(input.getAttribute("data-qty"));
        setQty(idx, input.value);
        renderCartPage();
      });
    });

    cartRoot.querySelectorAll("button[data-remove]").forEach((btn) => {
      btn.addEventListener("click", () => {
        const idx = Number(btn.getAttribute("data-remove"));
        removeItem(idx);
        renderCartPage();
      });
    });

    const cartField = document.getElementById("cart_json");
    if (cartField) {
      cartField.value = JSON.stringify(getCart());
    }

    const summaryField = document.getElementById("cart_summary");
    if (summaryField) {
      summaryField.value = buildCartSummary(getCart());
    }

    const modelHidden = document.getElementById("vehicleModel");
    if (modelHidden) {
      modelHidden.value = localStorage.getItem(MODEL_KEY) || "";
    }

    renderCartTotals(cart);
    refreshBadgeAndFloatingCart();
  }

  function attachSchedulingPageHandlers() {
    const cart = getCart();
    if (!cart.length) {
      clearCart();
      window.location.href = APP_PAGE_PATH;
    }
  }

  function attachModelSearchHandlers() {
    document.querySelectorAll("[data-model-search-wrap]").forEach((wrap) => {
      const input = wrap.querySelector("[data-model-search-input]");
      const emptyState = wrap.querySelector("[data-model-search-empty]");
      const filterRoot = wrap.closest(".service-model-filter");
      const options = filterRoot
        ? filterRoot.querySelectorAll("[data-model-option]")
        : [];

      if (!input || !options.length) return;

      function updateSearch() {
        const query = String(input.value || "")
          .trim()
          .toLowerCase();

        let visibleCount = 0;

        options.forEach((option) => {
          const haystack =
            option.getAttribute("data-model-search") ||
            option.textContent ||
            "";

          const matches = !query || haystack.toLowerCase().includes(query);

          option.classList.toggle("is-hidden", !matches);

          if (matches) visibleCount += 1;
        });

        if (emptyState) {
          emptyState.classList.toggle("is-hidden", visibleCount > 0);
        }
      }

      input.addEventListener("input", updateSearch);
      input.addEventListener("search", updateSearch);

      updateSearch();
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    refreshBadgeAndFloatingCart();
    bindFloatingCartScrollVisibility();

    const hasCartItemsRoot = !!document.getElementById("cartItems");
    const hasModelFilter = !!document.querySelector(".service-model-filter");

    if (hasModelFilter) {
      attachServicePageHandlers();
      attachModelSearchHandlers();
    }

    if (hasCartItemsRoot) {
      renderCartPage();
    }

    if (Core.isAppCartPage() && !getCart().length) {
      clearCart();
      window.location.href = APP_PAGE_PATH;
      return;
    }

    if (Core.isSchedulingPage() && !getCart().length) {
      clearCart();
      window.location.href = APP_PAGE_PATH;
      return;
    }

    if (Core.isSchedulingPage()) {
      attachSchedulingPageHandlers();
    }

    window.addEventListener("storage", (e) => {
      if (e.key === CART_KEY) {
        refreshBadgeAndFloatingCart();
        syncServiceCardStatesFromCart();

        if (document.getElementById("cartItems")) {
          renderCartPage();
        }

        if (Core.isAppCartPage() && !getCart().length) {
          clearCart();
          window.location.href = APP_PAGE_PATH;
          return;
        }

        if (Core.isSchedulingPage() && !getCart().length) {
          clearCart();
          window.location.href = APP_PAGE_PATH;
          return;
        }
      }

      if (e.key === MODEL_KEY) {
        const model = getSelectedModel();
        syncRadioGroupsToModel(model);
        updateSelectedModelUI(model);
        syncServiceCardStatesFromCart();
      }

      if (e.key === SCHEDULE_KEY) {
        if (document.getElementById("cartItems")) {
          renderCartPage();
        }
      }
    });
  });
})();
