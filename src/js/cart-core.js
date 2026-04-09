(function () {
  const CART_KEY = "titan_service_cart_v1";
  const MODEL_KEY = "titan_selected_model_v1";
  const SCHEDULE_KEY = "titan_service_schedule_v2";
  const CHECKOUT_MODE_KEY = "titan_checkout_mode_v1";

  const APP_PAGE_PATH = "/updated-service/index.php";
  const APP_CART_PAGE_PATH = "/updated-service/my-cart.php";
  const APP_SCHEDULING_PAGE_PATH = "/updated-service/app-scheduling.php";
  const APP_CHECKOUT_PAGE_PATH = "/updated-service/app-checkout.php";
  const CART_SESSION_SYNC_PATH =
    window.TITAN_CART_SESSION_SYNC_PATH ||
    "/../includes/store-cart-session.php";

  const FRONTEND_CATALOG = window.TITAN_CART_CATALOG || {};
  const TAX_RATE = Number(window.TITAN_CART_TAX_RATE) || 0;

  let lastSyncedCartHash = null;

  function pathnameEndsWith(path) {
    return window.location.pathname.endsWith(path);
  }

  function isAppPage() {
    return pathnameEndsWith("/index.php") || pathnameEndsWith("/index");
  }

  function isAppCartPage() {
    return pathnameEndsWith("/my-cart.php") || pathnameEndsWith("/my-cart");
  }

  function isSchedulingPage() {
    return (
      pathnameEndsWith("/app-scheduling.php") ||
      pathnameEndsWith("/app-scheduling")
    );
  }

  function isCheckoutPage() {
    return (
      pathnameEndsWith("/app-checkout.php") || pathnameEndsWith("/app-checkout")
    );
  }

  function safeParse(json, fallback) {
    try {
      return JSON.parse(json);
    } catch {
      return fallback;
    }
  }

  function getCatalogMeta(serviceId) {
    if (!serviceId || !FRONTEND_CATALOG[serviceId]) {
      return {
        serviceId: serviceId || "",
        name: "",
        price: "",
        duration: "",
        label: "Service",
        type: "service",
        requiresDate: true,
        description: "",
        modelNote: "",
        image: "",
      };
    }

    return FRONTEND_CATALOG[serviceId];
  }

  function makeCartItemKey(item) {
    const serviceId = String(item?.serviceId || "")
      .trim()
      .toLowerCase();
    const vehicleModel = String(item?.vehicleModel || "")
      .trim()
      .toLowerCase();
    const type = String(item?.type || "service")
      .trim()
      .toLowerCase();

    return `${serviceId}__${vehicleModel}__${type}`;
  }

  function normalizeCartItem(item) {
    const source = item && typeof item === "object" ? item : {};
    const serviceId = String(
      source.serviceId || source.id || source.slug || "",
    ).trim();
    const meta = getCatalogMeta(serviceId);

    const type =
      String(source.type || meta.type || "service").trim() || "service";
    const vehicleModel = String(
      source.vehicleModel || source.model || "",
    ).trim();
    const serviceName = String(
      source.serviceName || source.name || source.title || meta.name || "",
    ).trim();
    const servicePrice = String(
      source.servicePrice || source.price || meta.price || "",
    ).trim();
    const label = String(
      source.label ||
        meta.label ||
        (type === "upgrade" ? "Upgrade" : "Service"),
    ).trim();
    const duration = String(
      source.duration ||
        source.timeNeeded ||
        source.estimated_time ||
        meta.duration ||
        "",
    ).trim();
    const description = String(
      source.description || meta.description || "",
    ).trim();
    const image = String(
      source.image || source.card_image || source.cardImage || meta.image || "",
    ).trim();

    const requiresDate =
      typeof source.requiresDate === "boolean"
        ? source.requiresDate
        : typeof meta.requiresDate === "boolean"
          ? meta.requiresDate
          : true;

    const qty = Math.max(1, Math.min(99, Number(source.qty) || 1));
    const cartItemKey =
      String(source.cartItemKey || "").trim() ||
      makeCartItemKey({
        serviceId,
        vehicleModel,
        type,
      });

    const addedAt = String(source.addedAt || "").trim();

    if (!serviceId || !serviceName) {
      return null;
    }

    return {
      cartItemKey,
      serviceId,
      serviceName,
      servicePrice,
      vehicleModel,
      type,
      label,
      duration,
      description,
      image,
      requiresDate,
      qty,
      addedAt,
    };
  }

  function normalizeCart(cart) {
    if (!Array.isArray(cart)) return [];

    return cart.map((item) => normalizeCartItem(item)).filter(Boolean);
  }

  function serializeCart(cart = []) {
    return JSON.stringify(normalizeCart(cart));
  }

  function getStoredCartRaw() {
    return safeParse(localStorage.getItem(CART_KEY), []);
  }

  function getCart() {
    const rawParsed = getStoredCartRaw();
    const normalized = normalizeCart(rawParsed);
    const normalizedJson = JSON.stringify(normalized);
    const rawJson = JSON.stringify(Array.isArray(rawParsed) ? rawParsed : []);

    if (rawJson !== normalizedJson) {
      localStorage.setItem(CART_KEY, normalizedJson);
    }

    return normalized;
  }

  function getScheduleItemKey(item) {
    return String(item?.cartItemKey || makeCartItemKey(item));
  }

  function getScheduleSelections() {
    const raw = localStorage.getItem(SCHEDULE_KEY);
    const parsed = safeParse(raw, {});
    return parsed && typeof parsed === "object" && !Array.isArray(parsed)
      ? parsed
      : {};
  }

  function setScheduleSelections(selections) {
    localStorage.setItem(SCHEDULE_KEY, JSON.stringify(selections || {}));
  }

  function clearScheduleSelections() {
    localStorage.removeItem(SCHEDULE_KEY);
  }

  function setItemScheduleSelection(item, selection) {
    const key = getScheduleItemKey(item);
    const selections = getScheduleSelections();

    if (!selection || !selection.date || !selection.time) {
      delete selections[key];
    } else {
      selections[key] = {
        date: String(selection.date),
        time: String(selection.time),
        label:
          String(selection.label || "").trim() ||
          formatStoredScheduleSelection({
            date: selection.date,
            time: selection.time,
          }),
      };
    }

    setScheduleSelections(selections);
  }

  function clearItemScheduleSelection(item) {
    setItemScheduleSelection(item, null);
  }

  function syncScheduleSelectionsToCart(cart = getCart()) {
    const selections = getScheduleSelections();
    const validKeys = new Set(cart.map((item) => getScheduleItemKey(item)));
    let mutated = false;

    Object.keys(selections).forEach((key) => {
      if (!validKeys.has(key)) {
        delete selections[key];
        mutated = true;
      }
    });

    if (mutated) {
      setScheduleSelections(selections);
    }

    return selections;
  }

  function setCart(cart) {
    const normalized = normalizeCart(cart);
    localStorage.setItem(CART_KEY, JSON.stringify(normalized));
    syncScheduleSelectionsToCart(normalized);
    queueCartSessionSync(normalized);
    return normalized;
  }

  function clearCart() {
    localStorage.setItem(CART_KEY, JSON.stringify([]));
    clearScheduleSelections();
    queueCartSessionSync([]);
  }

  function getSelectedModel() {
    const checked =
      document.querySelector('input[name="vanModel-services"]:checked') ||
      document.querySelector('input[name="vanModel-upgrades"]:checked');

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

  function clearSelectedModel() {
    localStorage.removeItem(MODEL_KEY);
  }

  function getItemScheduleSelection(item) {
    const key = getScheduleItemKey(item);
    const selections = getScheduleSelections();
    return selections[key] || null;
  }

  function formatStoredScheduleSelection(selection) {
    if (!selection || !selection.date || !selection.time) return "";

    const [y, m, d] = String(selection.date).split("-").map(Number);
    const dateObj = new Date(y, (m || 1) - 1, d || 1);

    const formattedDate = dateObj.toLocaleDateString("en-US", {
      weekday: "long",
      month: "long",
      day: "numeric",
      year: "numeric",
    });

    return `${formattedDate} at ${selection.time}`;
  }

  function areAllItemsScheduled(
    cart = getCart(),
    selections = getScheduleSelections(),
  ) {
    if (!cart.length) return false;

    return cart.every((item) => {
      if (item.requiresDate === false) return true;

      const key = getScheduleItemKey(item);
      return selections[key]?.date && selections[key]?.time;
    });
  }

  function getScheduleSummaryText(
    cart = getCart(),
    selections = getScheduleSelections(),
  ) {
    if (!cart.length) {
      return "A preferred appointment date is required before final checkout.";
    }

    const requiringDate = cart.filter((item) => item.requiresDate !== false);

    if (!requiringDate.length) {
      return "No appointment date is required for the current cart.";
    }

    const scheduledCount = requiringDate.reduce((count, item) => {
      const key = getScheduleItemKey(item);
      return count + (selections[key]?.date && selections[key]?.time ? 1 : 0);
    }, 0);

    if (!scheduledCount) {
      return "A preferred appointment date is required before final checkout.";
    }

    if (scheduledCount === requiringDate.length) {
      return `All ${requiringDate.length} item${requiringDate.length === 1 ? "" : "s"} that require scheduling have appointment times selected.`;
    }

    return `${scheduledCount} of ${requiringDate.length} required item${requiringDate.length === 1 ? "" : "s"} scheduled.`;
  }

  function parseMoneyToNumber(value) {
    const str = String(value || "").trim();
    const num = Number(str.replace(/[^0-9.]/g, ""));
    return Number.isFinite(num) ? num : 0;
  }

  function formatMoney(value) {
    const num = Number(value);
    if (!Number.isFinite(num)) return "$0.00";
    return `$${num.toFixed(2)}`;
  }

  function escapeHtml(str) {
    return String(str || "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function buildCartSummary(cart = getCart()) {
    return cart
      .map((item) => {
        const qty = Number(item.qty) || 1;
        const name = String(item.serviceName || "").trim();
        const model = String(item.vehicleModel || "").trim();
        const price = String(item.servicePrice || "").trim();
        return `${qty}x ${name}${model ? ` (${model})` : ""}${price ? ` - ${price}` : ""}`;
      })
      .join("\n");
  }

  function buildScheduleSummary(
    cart = getCart(),
    selections = getScheduleSelections(),
  ) {
    return cart
      .map((item) => {
        const key = getScheduleItemKey(item);
        const selection = selections[key];
        const formatted = formatStoredScheduleSelection(selection);
        return `${item.serviceName || "Item"}${item.vehicleModel ? ` (${item.vehicleModel})` : ""}: ${formatted || "Not selected"}`;
      })
      .join("\n");
  }

  function getCartItemMeta(item) {
    const meta = getCatalogMeta(item?.serviceId || "");

    return {
      serviceId: item?.serviceId || meta.serviceId || "",
      name: item?.serviceName || item?.name || meta.name || "",
      price: item?.servicePrice || item?.price || meta.price || "",
      duration: item?.duration || meta.duration || "",
      label:
        item?.label ||
        meta.label ||
        ((meta.type || item?.type) === "upgrade" ? "Upgrade" : "Service"),
      type: item?.type || meta.type || "service",
      requiresDate:
        typeof item?.requiresDate === "boolean"
          ? item.requiresDate
          : typeof meta.requiresDate === "boolean"
            ? meta.requiresDate
            : true,
      description: item?.description || meta.description || "",
      modelNote: item?.modelNote || meta.modelNote || "",
      image: item?.image || meta.image || "",
    };
  }

function getCartTotals(cart = getCart()) {
  let subtotal = 0;
  let itemCount = 0;

  cart.forEach((item) => {
    const qty = Math.max(1, Number(item?.qty) || 1);
    const meta = getCartItemMeta(item);
    const unitPrice = parseMoneyToNumber(
      meta.price || item.servicePrice || item.price || "0",
    );

    subtotal += unitPrice * qty;
    itemCount += qty;
  });

  subtotal = Math.round(subtotal * 100) / 100;

  const taxRate = Number(window.TITAN_CART_TAX_RATE) || 0;
  const tax = Math.round(subtotal * taxRate * 100) / 100;
  const total = Math.round((subtotal + tax) * 100) / 100;

  return {
    itemCount,
    subtotal,
    tax,
    total,
  };
}

  function updateCartBadge() {
    const cart = getCart();

    const count = cart.reduce((sum, item) => {
      return sum + (Number(item?.qty) || 1);
    }, 0);

    document.querySelectorAll(".cart-badge").forEach((badge) => {
      badge.textContent = String(count);
    });

    return count;
  }

  function getCheckoutMode() {
    const raw = localStorage.getItem(CHECKOUT_MODE_KEY);
    const parsed = safeParse(raw, null);

    if (!parsed || typeof parsed !== "object") {
      return {
        mode: null,
        updatedAt: null,
      };
    }

    return {
      mode: parsed.mode || null,
      updatedAt: parsed.updatedAt || null,
    };
  }

  function setCheckoutMode(mode) {
    const payload = {
      mode: mode || null,
      updatedAt: new Date().toISOString(),
    };

    localStorage.setItem(CHECKOUT_MODE_KEY, JSON.stringify(payload));
    return payload;
  }

  function clearCheckoutMode() {
    localStorage.removeItem(CHECKOUT_MODE_KEY);
  }

  function getCartSessionSyncPath() {
    return String(
      window.TITAN_CART_SESSION_SYNC_PATH || CART_SESSION_SYNC_PATH || "",
    ).trim();
  }

  async function syncCartToSession(cartItems = getCart()) {
    const path = getCartSessionSyncPath();
    const normalized = normalizeCart(cartItems);

    if (!path) {
      throw new Error("Missing cart session sync path.");
    }

    const response = await fetch(path, {
      method: "POST",
      credentials: "same-origin",
      keepalive: true,
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        cart: normalized,
        selectedModel: localStorage.getItem(MODEL_KEY) || "",
        syncedAt: new Date().toISOString(),
      }),
    });

    if (!response.ok) {
      throw new Error(
        `Cart session sync failed with status ${response.status}.`,
      );
    }

    const result = await response.json().catch(() => null);

    if (!result || result.ok !== true) {
      throw new Error("Cart session sync returned an invalid response.");
    }

    lastSyncedCartHash = serializeCart(normalized);
    return result;
  }

  function queueCartSessionSync(cartItems = getCart()) {
    const normalized = normalizeCart(cartItems);
    const nextHash = serializeCart(normalized);

    if (nextHash === lastSyncedCartHash) {
      return Promise.resolve({
        ok: true,
        skipped: true,
      });
    }

    return syncCartToSession(normalized).catch((error) => {
      console.warn("Unable to sync cart session.", error);
      return {
        ok: false,
        error,
      };
    });
  }

  function buildSchedulingUrlFromCart(
    cartItems,
    schedulingPath = APP_SCHEDULING_PAGE_PATH,
    baseHref = window.location.href,
  ) {
    const normalized = normalizeCart(cartItems);

    if (!normalized.length) {
      return new URL(APP_PAGE_PATH, window.location.origin).toString();
    }

    const url = new URL(
      schedulingPath || APP_SCHEDULING_PAGE_PATH || window.location.pathname,
      window.location.origin,
    );

    const currentUrl = new URL(baseHref || window.location.href);

    currentUrl.searchParams.forEach((value, key) => {
      if (key !== "serviceType" && key !== "services" && key !== "services[]") {
        url.searchParams.append(key, value);
      }
    });

    return url.toString();
  }

  async function navigateToSchedulingFromCart(
    cartItems = getCart(),
    schedulingPath = APP_SCHEDULING_PAGE_PATH,
    baseHref = window.location.href,
  ) {
    const normalized = normalizeCart(cartItems);

    if (!normalized.length) {
      window.location.href = APP_PAGE_PATH;
      return APP_PAGE_PATH;
    }

    await syncCartToSession(normalized);

    const nextUrl = buildSchedulingUrlFromCart(
      normalized,
      schedulingPath,
      baseHref,
    );

    window.location.href = nextUrl;
    return nextUrl;
  }

  function bindContinueToSchedulingButton(options = {}) {
    const selector = options.selector || "#continueToSchedulingBtn";
    const button =
      options.button ||
      (typeof document !== "undefined"
        ? document.querySelector(selector)
        : null);

    if (!button) return false;
    if (button.dataset.schedulingBound === "true") return true;

    button.dataset.schedulingBound = "true";

    button.addEventListener("click", async (event) => {
      if (
        button.classList.contains("is-disabled") ||
        button.getAttribute("aria-disabled") === "true"
      ) {
        event.preventDefault();
        return;
      }

      event.preventDefault();
      button.classList.add("is-loading");

      try {
        await navigateToSchedulingFromCart(
          getCart(),
          options.schedulingPath ||
            button.getAttribute("data-scheduling-path") ||
            button.getAttribute("href") ||
            APP_SCHEDULING_PAGE_PATH,
          window.location.href,
        );
      } catch (error) {
        console.error("Failed to continue to scheduling.", error);
        button.classList.remove("is-loading");
        alert("There was a problem sending your cart to scheduling.");
      }
    });

    return true;
  }

  function initCartCoreUiBindings() {
    bindContinueToSchedulingButton();
  }

  (function fixBadStorageShape() {
    getCart();
    syncScheduleSelectionsToCart(getCart());
  })();

  if (typeof document !== "undefined") {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", initCartCoreUiBindings, {
        once: true,
      });
    } else {
      initCartCoreUiBindings();
    }
  }

  window.TitanCartCore = {
    CART_KEY,
    MODEL_KEY,
    SCHEDULE_KEY,
    CHECKOUT_MODE_KEY,
    APP_PAGE_PATH,
    APP_CART_PAGE_PATH,
    APP_SCHEDULING_PAGE_PATH,
    APP_CHECKOUT_PAGE_PATH,
    CART_SESSION_SYNC_PATH,
    FRONTEND_CATALOG,
    TAX_RATE,
    pathnameEndsWith,
    isAppPage,
    isAppCartPage,
    isSchedulingPage,
    isCheckoutPage,
    safeParse,
    getCatalogMeta,
    makeCartItemKey,
    normalizeCartItem,
    normalizeCart,
    getCart,
    setCart,
    clearCart,
    getSelectedModel,
    setSelectedModel,
    clearSelectedModel,
    getScheduleSelections,
    setScheduleSelections,
    clearScheduleSelections,
    getScheduleItemKey,
    setItemScheduleSelection,
    getItemScheduleSelection,
    clearItemScheduleSelection,
    syncScheduleSelectionsToCart,
    formatStoredScheduleSelection,
    areAllItemsScheduled,
    getScheduleSummaryText,
    parseMoneyToNumber,
    formatMoney,
    escapeHtml,
    buildCartSummary,
    buildScheduleSummary,
    getCartItemMeta,
    getCartTotals,
    updateCartBadge,
    getCheckoutMode,
    setCheckoutMode,
    clearCheckoutMode,
    getCartSessionSyncPath,
    syncCartToSession,
    queueCartSessionSync,
    buildSchedulingUrlFromCart,
    navigateToSchedulingFromCart,
    bindContinueToSchedulingButton,
    initCartCoreUiBindings,
  };
})();
