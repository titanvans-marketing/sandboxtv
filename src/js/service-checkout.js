(function () {
  const Core = window.TitanCartCore;
  if (!Core) return;

  const checkoutAuth = window.TITAN_CHECKOUT_AUTH || {
    isLoggedIn: false,
    isGuest: false,
    canProceed: false,
    customer: {},
  };

  const sessionCart = Array.isArray(window.TITAN_SESSION_CART)
    ? window.TITAN_SESSION_CART
    : [];

  const RECAPTCHA_SITE_KEY = "6LeXTvIqAAAAAFEs2ICE9rHgjir0B0IdtcqL74xP";

  const {
    APP_PAGE_PATH,
    APP_SCHEDULING_PAGE_PATH,
    getCart,
    setCart,
    getScheduleSelections,
    getScheduleItemKey,
    formatStoredScheduleSelection,
    areAllItemsScheduled,
    getCartItemMeta,
    getCartTotals,
    parseMoneyToNumber,
    formatMoney,
    escapeHtml,
    buildCartSummary,
    buildScheduleSummary,
    updateCartBadge,
    queueCartSessionSync,
  } = Core;

  function waitForRecaptcha(timeoutMs = 4000) {
    return new Promise((resolve, reject) => {
      const start = Date.now();

      (function tick() {
        if (
          window.grecaptcha &&
          typeof window.grecaptcha.ready === "function"
        ) {
          resolve(window.grecaptcha);
          return;
        }

        if (Date.now() - start >= timeoutMs) {
          reject(new Error("reCAPTCHA script not available (timeout)"));
          return;
        }

        setTimeout(tick, 50);
      })();
    });
  }

  async function getRecaptchaToken(action = "submit") {
    const grecaptcha = await waitForRecaptcha(4000);

    return await new Promise((resolve, reject) => {
      grecaptcha.ready(() => {
        grecaptcha
          .execute(RECAPTCHA_SITE_KEY, { action })
          .then(resolve)
          .catch(reject);
      });
    });
  }

  function hydrateCartFromSessionIfNeeded() {
    const localCart = Array.isArray(getCart()) ? getCart() : [];

    if (localCart.length) {
      return localCart;
    }

    if (sessionCart.length && typeof setCart === "function") {
      setCart(sessionCart);
      return Array.isArray(getCart()) ? getCart() : sessionCart;
    }

    return sessionCart;
  }

  function getEffectiveCart() {
    const cart = hydrateCartFromSessionIfNeeded();
    return Array.isArray(cart) ? cart : [];
  }

  function getEffectiveVehicleModel(cart) {
    const storedModel = String(localStorage.getItem(Core.MODEL_KEY) || "").trim();
    if (storedModel) return storedModel;

    const firstCartModel = Array.isArray(cart)
      ? cart.find((item) => String(item?.vehicleModel || "").trim())
      : null;

    return String(firstCartModel?.vehicleModel || "").trim();
  }

  function getResolvedItemMeta(item) {
    const meta = getCartItemMeta(item) || {};

    return {
      name: String(meta.name || item?.serviceName || item?.name || "Item"),
      label: String(meta.label || item?.label || item?.type || "Service"),
      duration: String(
        meta.duration ||
        item?.duration ||
        item?.timeNeeded ||
        item?.estimated_time ||
        "To be confirmed",
      ),
      price: String(meta.price || item?.servicePrice || item?.price || "0"),
    };
  }

  function getSelectionDisplay(selection) {
    if (!selection) return "Not selected";

    const base =
      String(formatStoredScheduleSelection(selection) || "").trim() ||
      String(selection.label || "").trim() ||
      "Not selected";

    if (!selection.groupedAppointment) {
      return base;
    }

    const extras = ["Grouped"];
    if (selection.groupTotalDurationLabel) {
      extras.push(String(selection.groupTotalDurationLabel));
    }
    if (selection.estimatedEndTime) {
      extras.push(`Ends about ${selection.estimatedEndTime}`);
    }

    return `${base} • ${extras.join(" • ")}`;
  }

  function setInputValueIfEmpty(id, value) {
    const input = document.getElementById(id);
    if (!input) return;

    const currentValue = String(input.value || "").trim();
    const nextValue = String(value || "").trim();

    if (!currentValue && nextValue) {
      input.value = nextValue;
    }
  }

  function hydrateLoggedInCustomerFields() {
    if (!checkoutAuth.isLoggedIn || !checkoutAuth.customer) return;

    setInputValueIfEmpty(
      "checkout_first_name",
      checkoutAuth.customer.firstName,
    );
    setInputValueIfEmpty("checkout_last_name", checkoutAuth.customer.lastName);
    setInputValueIfEmpty("checkout_email", checkoutAuth.customer.email);
    setInputValueIfEmpty("checkout_phone", checkoutAuth.customer.phone);
  }

  function ensureCheckoutCanProceed() {
    return !!checkoutAuth.canProceed;
  }

  function syncHiddenFields(cart, scheduleSelections) {
    const cartField = document.getElementById("cart_json");
    const summaryField = document.getElementById("cart_summary");
    const modelHidden = document.getElementById("vehicleModel");
    const scheduledSlotsField = document.getElementById("scheduled_slots_json");
    const legacySlotField = document.getElementById("scheduled_slot");

    if (cartField) {
      cartField.value = JSON.stringify(cart);
    }

    if (summaryField) {
      summaryField.value = buildCartSummary(cart);
    }

    if (modelHidden) {
      modelHidden.value = getEffectiveVehicleModel(cart);
    }

    if (scheduledSlotsField) {
      scheduledSlotsField.value = JSON.stringify(scheduleSelections);
    }

    if (legacySlotField) {
      legacySlotField.value = buildScheduleSummary(cart, scheduleSelections);
    }
  }

  function renderCheckoutReview() {
  const reviewRoot = document.getElementById("checkoutReview");
  const sidebarRoot = document.getElementById("checkoutSidebarSummary");

  if (!reviewRoot && !sidebarRoot) return;

  const cart = getEffectiveCart();
  const scheduleSelections = getScheduleSelections();

  if (!cart.length) {
    window.location.href = APP_PAGE_PATH;
    return;
  }

  if (!areAllItemsScheduled(cart, scheduleSelections)) {
    window.location.href = APP_SCHEDULING_PAGE_PATH;
    return;
  }

  const totals = getCartTotals(cart);

  const scheduledAppointmentsHtml = cart
    .map((item) => {
      const itemKey = getScheduleItemKey(item);
      const selection = scheduleSelections[itemKey];
      const meta = getResolvedItemMeta(item);
      const qty = Number(item.qty) || 1;
      const model = String(item.vehicleModel || "");
      const quantityText = qty > 1 ? `Qty: ${qty}` : "";

      return `
        <div class="checkout-review-item">
          <strong>${escapeHtml(meta.name || item.serviceName || "Item")}</strong>
          <div>Type: ${escapeHtml(meta.label || item.label || "Service")}</div>
          <div>Model: ${escapeHtml(model || "—")}</div>
          ${quantityText ? `<div>${escapeHtml(quantityText)}</div>` : ""}
          <div>Appointment: ${escapeHtml(getSelectionDisplay(selection))}</div>
        </div>
      `;
    })
    .join("");

  if (reviewRoot) {
    reviewRoot.innerHTML = `
      <div class="checkout-review-summary">
        <div class="checkout-review-section">
          <h4>Scheduled Appointments</h4>
          ${scheduledAppointmentsHtml}
        </div>

        <div class="checkout-review-section">
          <h4>Estimated Totals</h4>
          <div class="checkout-review-item">
            <div>Subtotal: ${escapeHtml(formatMoney(totals.subtotal))}</div>
            <div>Tax: ${escapeHtml(formatMoney(totals.tax))}</div>
            <div><strong>Total: ${escapeHtml(formatMoney(totals.total))}</strong></div>
          </div>
        </div>
      </div>
    `;
  }

  if (sidebarRoot) {
    sidebarRoot.innerHTML = `
      <div class="checkout-summary-card__header">
        <h3>Order Summary</h3>
        <p>${totals.itemCount} scheduled item${totals.itemCount === 1 ? "" : "s"}</p>
      </div>

      <div class="checkout-summary-section">
        ${cart
          .map((item) => {
            const key = getScheduleItemKey(item);
            const selection = scheduleSelections[key];

            return `
              <div class="checkout-summary-item">
                <strong>${escapeHtml(item.serviceName || "Item")}</strong>
                <span>${escapeHtml(item.vehicleModel || "Vehicle")}</span>
                <span>${escapeHtml(getSelectionDisplay(selection))}</span>
              </div>
            `;
          })
          .join("")}
      </div>

      <div class="checkout-summary-totals">
        <div class="checkout-summary-row">
          <span>Subtotal</span>
          <strong>${formatMoney(totals.subtotal)}</strong>
        </div>

        <div class="checkout-summary-row">
          <span>Estimated Tax</span>
          <strong>${formatMoney(totals.tax)}</strong>
        </div>

        <div class="checkout-summary-row checkout-summary-row--total">
          <span>Estimated Total</span>
          <strong>${formatMoney(totals.total)}</strong>
        </div>
      </div>
    `;
  }

  syncHiddenFields(cart, scheduleSelections);
}

  function attachCheckoutFormHandler() {
    const form = document.getElementById("cartCheckoutForm");
    if (!form) return;

    const cart = getEffectiveCart();
    const scheduleSelections = getScheduleSelections();

    if (!cart.length) {
      window.location.href = APP_PAGE_PATH;
      return;
    }

    if (!areAllItemsScheduled(cart, scheduleSelections)) {
      window.location.href = APP_SCHEDULING_PAGE_PATH;
      return;
    }

    const startField = document.getElementById("form_start_ts");
    const recField = document.getElementById("recaptchaToken");

    syncHiddenFields(cart, scheduleSelections);

    if (startField && !startField.value) {
      startField.value = String(Date.now());
    }

    form.addEventListener("submit", async (e) => {
      const latestCart = getEffectiveCart();
      const latestSelections = getScheduleSelections();

      if (!latestCart.length) {
        e.preventDefault();
        alert("Your cart is empty.");
        window.location.href = APP_PAGE_PATH;
        return;
      }

      if (!areAllItemsScheduled(latestCart, latestSelections)) {
        e.preventDefault();
        alert("Please select an appointment for each item before continuing.");
        window.location.href = APP_SCHEDULING_PAGE_PATH;
        return;
      }

      const start = parseInt(startField?.value || "0", 10);
      const elapsed = Date.now() - start;

      if (!start || elapsed < 5000) {
        e.preventDefault();
        alert(
          "Please take a moment before submitting checkout (minimum 5 seconds).",
        );
        return;
      }

      syncHiddenFields(latestCart, latestSelections);

      e.preventDefault();

      try {
        if (typeof queueCartSessionSync === "function") {
          await queueCartSessionSync(latestCart);
        }

        const token = await getRecaptchaToken("submit");
        if (recField) {
          recField.value = token;
        }

        form.submit();
      } catch (err) {
        console.error(err);
        alert(
          "reCAPTCHA failed to load. Disable ad blockers / allow Google reCAPTCHA, then reload.",
        );
      }
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    const hasCheckoutForm = !!document.getElementById("cartCheckoutForm");
    const hasCheckoutReview = !!document.getElementById("checkoutReview");
    const hasCheckoutSidebar = !!document.getElementById(
      "checkoutSidebarSummary",
    );

    updateCartBadge();

    const cart = getEffectiveCart();
    const scheduleSelections = getScheduleSelections();

    if (!cart.length) {
      window.location.href = APP_PAGE_PATH;
      return;
    }

    if (!areAllItemsScheduled(cart, scheduleSelections)) {
      window.location.href = APP_SCHEDULING_PAGE_PATH;
      return;
    }

    if (typeof queueCartSessionSync === "function") {
      queueCartSessionSync(cart);
    }

    if (!ensureCheckoutCanProceed()) {
      if (hasCheckoutSidebar) {
        renderCheckoutReview();
      }
      return;
    }

    if (!hasCheckoutForm && !hasCheckoutReview && !hasCheckoutSidebar) {
      return;
    }

    hydrateLoggedInCustomerFields();
    renderCheckoutReview();
    attachCheckoutFormHandler();

    window.addEventListener("storage", (e) => {
      if (e.key === Core.CART_KEY || e.key === Core.SCHEDULE_KEY) {
        const latestCart = getEffectiveCart();
        const latestSelections = getScheduleSelections();

        if (!latestCart.length) {
          window.location.href = APP_PAGE_PATH;
          return;
        }

        if (!areAllItemsScheduled(latestCart, latestSelections)) {
          window.location.href = APP_SCHEDULING_PAGE_PATH;
          return;
        }

        renderCheckoutReview();
      }
    });
  });
})();