(function () {
  const Core = window.TitanCartCore;
  if (!Core) return;

  const {
    APP_CART_PAGE_PATH,
    APP_CHECKOUT_PAGE_PATH,
    getCart,
    setCart,
    getCartItemMeta,
    getScheduleSelections,
    setItemScheduleSelection,
    getItemScheduleSelection,
    getScheduleItemKey,
    formatStoredScheduleSelection,
    areAllItemsScheduled,
    escapeHtml,
    updateCartBadge,
    queueCartSessionSync,
    CART_KEY,
    SCHEDULE_KEY,
  } = Core;

  const sessionCart = Array.isArray(window.TITAN_SESSION_CART)
    ? window.TITAN_SESSION_CART
    : [];

  let scheduleData = window.TITAN_SCHEDULE_DATA || {};
  let scheduleMeta = window.TITAN_SCHEDULE_META || {};
  let CLOSED_WEEKDAYS = new Set(
    Array.isArray(scheduleMeta.closedWeekdays)
      ? scheduleMeta.closedWeekdays
      : [0, 5, 6],
  );

  const SCHEDULE_ENDPOINT_PATH = String(
    window.TITAN_SCHEDULE_ENDPOINT ||
      "/updated-service/includes/get-schedule-data.php",
  ).trim();

  const SCHEDULE_UI_STATE_KEY = "titan_schedule_ui_state_v1";

  const monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];

  const weekdayNamesShort = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

  function safeObject(value) {
    return value && typeof value === "object" ? value : {};
  }

  function startOfDay(date) {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    return d;
  }

  function startOfMonth(date) {
    const d = new Date(date);
    return new Date(d.getFullYear(), d.getMonth(), 1);
  }

  function pad(num) {
    return String(num).padStart(2, "0");
  }

  function formatDateKey(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(
      date.getDate(),
    )}`;
  }

  function parseDateKey(dateKey) {
    const [y, m, d] = String(dateKey).split("-").map(Number);
    return new Date(y, (m || 1) - 1, d || 1);
  }

  function formatLongDate(date) {
    return date.toLocaleDateString("en-US", {
      weekday: "long",
      month: "long",
      day: "numeric",
      year: "numeric",
    });
  }

  function formatMonthTitle(date) {
    return `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
  }

  function formatFullSlotLabel(dateKey, time) {
    return `${formatLongDate(parseDateKey(dateKey))} at ${time}`;
  }

  function hasMeaningfulValue(value) {
    if (value === 0 || value === false) return true;
    if (typeof value === "number") return Number.isFinite(value);
    return String(value ?? "").trim() !== "";
  }

  function pickFirst(...values) {
    for (const value of values) {
      if (hasMeaningfulValue(value)) {
        return value;
      }
    }
    return "";
  }

  function toTrimmedString(value, fallback = "") {
    const chosen = hasMeaningfulValue(value) ? value : fallback;
    return String(chosen ?? "").trim();
  }

  function toPositiveInt(value, fallback = 1) {
    const num = Number(value);
    if (Number.isFinite(num) && num > 0) {
      return Math.round(num);
    }
    return fallback;
  }

  function uniqueStrings(values) {
    return Array.from(
      new Set((Array.isArray(values) ? values : []).filter(Boolean)),
    );
  }

  function loadScheduleUiState() {
    try {
      const raw = sessionStorage.getItem(SCHEDULE_UI_STATE_KEY);
      const parsed = raw ? JSON.parse(raw) : null;

      if (!parsed || typeof parsed !== "object") {
        return { view: "month" };
      }

      return {
        view: String(parsed.view || "month").trim() || "month",
      };
    } catch {
      return { view: "month" };
    }
  }

  function persistScheduleUiState() {
    try {
      sessionStorage.setItem(
        SCHEDULE_UI_STATE_KEY,
        JSON.stringify({
          view: state.view || "month",
        }),
      );
    } catch {
      // ignore
    }
  }

  const persistedUiState = loadScheduleUiState();

  const state = {
    cartItems: [],
    currentDate: startOfMonth(new Date()),
    selectedDate: null,
    view: persistedUiState.view || "month",
    scheduleRequestKey: "",
    isScheduleLoading: false,
    expandedInlineDateKey: null,
  };

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

  function normalizeType(rawType, rawLabel) {
    const typeCandidate = toTrimmedString(rawType).toLowerCase();
    if (typeCandidate) return typeCandidate;

    const labelCandidate = toTrimmedString(rawLabel).toLowerCase();
    if (labelCandidate === "upgrade") return "upgrade";
    if (labelCandidate === "service") return "service";

    return "service";
  }

  function normalizeLabel(rawLabel, normalizedType) {
    const labelCandidate = toTrimmedString(rawLabel);
    if (labelCandidate) return labelCandidate;
    return normalizedType === "upgrade" ? "Upgrade" : "Service";
  }

  function getSafeItemKey(item) {
    try {
      return toTrimmedString(getScheduleItemKey(item));
    } catch {
      return "";
    }
  }

  function getSelectionLabel(selection) {
    if (!selection) return "";

    const explicit = toTrimmedString(
      pickFirst(selection.label, selection.displayLabel, selection.fullLabel),
    );
    if (explicit) return explicit;

    if (typeof formatStoredScheduleSelection === "function") {
      const formatted = toTrimmedString(
        formatStoredScheduleSelection(selection),
      );
      if (formatted) return formatted;
    }

    if (selection.date && selection.time) {
      return formatFullSlotLabel(selection.date, selection.time);
    }

    return "";
  }

  function getNormalizedCartItem(item) {
    const rawItem = safeObject(item);
    const meta = safeObject(getCartItemMeta(rawItem));

    const key = toTrimmedString(
      pickFirst(
        getSafeItemKey(rawItem),
        rawItem.scheduleItemKey,
        rawItem.cartItemKey,
        rawItem.cartItemId,
        rawItem.lineItemKey,
        rawItem.itemKey,
        rawItem.uuid,
        rawItem.id,
        rawItem.serviceId,
        rawItem.productId,
        rawItem.slug,
      ),
    );

    if (!key) return null;

    const serviceId = toTrimmedString(
      pickFirst(
        rawItem.serviceId,
        rawItem.productId,
        rawItem.itemId,
        rawItem.id,
        meta.serviceId,
        meta.productId,
        meta.id,
        meta.slug,
        key,
      ),
    );

    const type = normalizeType(
      pickFirst(
        rawItem.type,
        rawItem.itemType,
        rawItem.serviceType,
        rawItem.productType,
        meta.type,
        meta.itemType,
      ),
      pickFirst(rawItem.label, rawItem.itemLabel, meta.label),
    );

    const label = normalizeLabel(
      pickFirst(
        rawItem.label,
        rawItem.itemLabel,
        rawItem.serviceLabel,
        meta.label,
      ),
      type,
    );

    const serviceName = toTrimmedString(
      pickFirst(
        rawItem.serviceName,
        rawItem.name,
        rawItem.title,
        rawItem.itemName,
        rawItem.productName,
        rawItem.displayName,
        meta.name,
        meta.title,
        "Untitled Item",
      ),
    );

    const servicePrice = toTrimmedString(
      pickFirst(
        rawItem.servicePrice,
        rawItem.price,
        rawItem.displayPrice,
        rawItem.formattedPrice,
        rawItem.priceLabel,
        rawItem.unitPrice,
        meta.price,
        meta.displayPrice,
        meta.formattedPrice,
      ),
    );

    const vehicleModel = toTrimmedString(
      pickFirst(
        rawItem.vehicleModel,
        rawItem.selectedModel,
        rawItem.model,
        rawItem.vanModel,
        rawItem.vehicle,
        rawItem.vehicleType,
        meta.vehicleModel,
        meta.model,
      ),
    );

    const qty = toPositiveInt(
      pickFirst(rawItem.qty, rawItem.quantity, rawItem.count, rawItem.units),
      1,
    );

    const duration = toTrimmedString(
      pickFirst(
        rawItem.duration,
        rawItem.timeNeeded,
        rawItem.estimatedTime,
        rawItem.estimated_time,
        rawItem.estimatedDuration,
        meta.duration,
        meta.timeNeeded,
        meta.estimatedTime,
        meta.estimated_time,
      ),
    );

    const description = toTrimmedString(
      pickFirst(
        rawItem.description,
        rawItem.excerpt,
        rawItem.summary,
        rawItem.shortDescription,
        meta.description,
        meta.excerpt,
        meta.summary,
      ),
    );

    const image = toTrimmedString(
      pickFirst(
        rawItem.image,
        rawItem.cardImage,
        rawItem.imageUrl,
        rawItem.card_image,
        rawItem.thumbnail,
        meta.image,
        meta.cardImage,
        meta.card_image,
        meta.thumbnail,
      ),
    );

    const variant = toTrimmedString(
      pickFirst(
        rawItem.variant,
        rawItem.variantName,
        rawItem.variantTitle,
        rawItem.optionLabel,
        rawItem.optionName,
      ),
    );

    const notes = toTrimmedString(
      pickFirst(rawItem.notes, rawItem.scheduleNotes, rawItem.cartNotes),
    );

    const requiresDate =
      typeof rawItem.requiresDate === "boolean"
        ? rawItem.requiresDate
        : typeof meta.requiresDate === "boolean"
          ? meta.requiresDate
          : true;

    return {
      key,
      serviceId,
      serviceName,
      servicePrice,
      vehicleModel,
      qty,
      label,
      type,
      duration,
      description,
      image,
      variant,
      notes,
      requiresDate,
      raw: rawItem,
    };
  }

  function normalizeCartItems() {
    const sourceCart = hydrateCartFromSessionIfNeeded();
    const list = Array.isArray(sourceCart) ? sourceCart : [];
    const unique = [];
    const seen = new Set();

    list.forEach((item) => {
      const normalized = getNormalizedCartItem(item);
      if (!normalized || seen.has(normalized.key)) return;
      seen.add(normalized.key);
      unique.push(normalized);
    });

    return unique;
  }

  function canonicalizeSchedulingServiceType(value) {
    const raw = String(value || "")
      .trim()
      .toLowerCase();

    if (!raw) return "";
    if (/\bservice\s*a\b/.test(raw)) return "service a";
    if (/\bservice\s*b\b/.test(raw)) return "service b";
    if (/\btire\s*rotation\b/.test(raw)) return "tire rotation";
    if (/\bwheel\s*alignment\b/.test(raw)) return "wheel alignment";
    if (/\balignment\s*service\b/.test(raw)) return "wheel alignment";

    return "";
  }

  function inferSchedulingServiceType(item) {
    const rawName = String(
      item?.serviceName ||
        item?.name ||
        item?.title ||
        item?.raw?.serviceName ||
        item?.raw?.name ||
        item?.raw?.title ||
        "",
    ).trim();

    return canonicalizeSchedulingServiceType(rawName);
  }

  function parseDurationToMinutes(value) {
    const raw = String(value || "")
      .trim()
      .toLowerCase();
    if (!raw) return 0;

    const rangeHours = raw.match(
      /(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)\s*(h|hr|hrs|hour|hours)\b/,
    );
    if (rangeHours) {
      return Math.round(parseFloat(rangeHours[2]) * 60);
    }

    const rangeMinutes = raw.match(
      /(\d+)\s*-\s*(\d+)\s*(m|min|mins|minute|minutes)\b/,
    );
    if (rangeMinutes) {
      return parseInt(rangeMinutes[2], 10);
    }

    let total = 0;

    const hourMatch = raw.match(/(\d+(?:\.\d+)?)\s*(h|hr|hrs|hour|hours)\b/);
    if (hourMatch) {
      total += Math.round(parseFloat(hourMatch[1]) * 60);
    }

    const minuteMatch = raw.match(/(\d+)\s*(m|min|mins|minute|minutes)\b/);
    if (minuteMatch) {
      total += parseInt(minuteMatch[1], 10);
    }

    if (total > 0) return total;

    const compactHourMinute = raw.match(/(\d+)h(?:\s*(\d+)m)?/);
    if (compactHourMinute) {
      const hours = parseInt(compactHourMinute[1], 10) || 0;
      const minutes = parseInt(compactHourMinute[2] || "0", 10) || 0;
      return hours * 60 + minutes;
    }

    const onlyNumber = raw.match(/^\d+$/);
    if (onlyNumber) {
      return parseInt(onlyNumber[0], 10);
    }

    return 0;
  }

  function getItemTotalMinutes(item) {
    const qty = Number(item?.qty) || 1;
    const durationMinutes = parseDurationToMinutes(item?.duration);
    return durationMinutes * Math.max(1, qty);
  }

  function getGroupTotalMinutes(items) {
    return (Array.isArray(items) ? items : []).reduce(
      (sum, item) => sum + getItemTotalMinutes(item),
      0,
    );
  }

  function formatMinutesHuman(totalMinutes) {
    const minutes = Math.max(0, Number(totalMinutes) || 0);
    const hours = Math.floor(minutes / 60);
    const remainder = minutes % 60;

    if (hours && remainder) return `${hours} hr ${remainder} min`;
    if (hours) return `${hours} hr`;
    return `${remainder} min`;
  }

  function parseClockTimeToMinutes(timeLabel) {
    const raw = String(timeLabel || "")
      .trim()
      .toUpperCase();
    const match = raw.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/);
    if (!match) return null;

    let hours = parseInt(match[1], 10);
    const minutes = parseInt(match[2], 10);
    const meridiem = match[3];

    if (meridiem === "PM" && hours !== 12) hours += 12;
    if (meridiem === "AM" && hours === 12) hours = 0;

    return hours * 60 + minutes;
  }

  function formatMinutesToClockTime(totalMinutes) {
    if (!Number.isFinite(totalMinutes)) return "";

    const normalized = ((totalMinutes % 1440) + 1440) % 1440;
    let hours = Math.floor(normalized / 60);
    const minutes = normalized % 60;

    const meridiem = hours >= 12 ? "PM" : "AM";
    hours = hours % 12;
    if (hours === 0) hours = 12;

    return `${hours}:${String(minutes).padStart(2, "0")} ${meridiem}`;
  }

  function addMinutesToClockTime(timeLabel, minutesToAdd) {
    const startMinutes = parseClockTimeToMinutes(timeLabel);
    if (!Number.isFinite(startMinutes)) return "";
    return formatMinutesToClockTime(startMinutes + (Number(minutesToAdd) || 0));
  }

  function getSchedulableItems() {
    return state.cartItems.filter(
      (item) => item && item.requiresDate !== false,
    );
  }

  function getSchedulingScopeItems() {
    return getSchedulableItems();
  }

  function getTopDisplayItems() {
    return getSchedulingScopeItems();
  }

  function getTopDisplaySelection(items = getSchedulingScopeItems()) {
    for (const item of items) {
      const selection = getItemScheduleSelection(item);
      if (selection?.date && selection?.time) {
        return selection;
      }
    }
    return null;
  }

  function isClosedWeekday(date) {
    return CLOSED_WEEKDAYS.has(date.getDay());
  }

  function isPastDate(date) {
    return startOfDay(date) < startOfDay(new Date());
  }

  function isSameCalendarDay(a, b) {
    return (
      a.getFullYear() === b.getFullYear() &&
      a.getMonth() === b.getMonth() &&
      a.getDate() === b.getDate()
    );
  }

  function isPastTimeSlot(dateKey, timeLabel) {
    const slotDate = parseDateKey(dateKey);

    if (isPastDate(slotDate)) {
      return true;
    }

    const now = new Date();

    if (!isSameCalendarDay(slotDate, now)) {
      return false;
    }

    const slotMinutes = parseClockTimeToMinutes(timeLabel);
    if (!Number.isFinite(slotMinutes)) {
      return false;
    }

    const nowMinutes = now.getHours() * 60 + now.getMinutes();

    return slotMinutes <= nowMinutes;
  }

  function guardCartState() {
    state.cartItems = normalizeCartItems();

    if (!state.cartItems.length) {
      window.location.href = APP_CART_PAGE_PATH;
      return false;
    }

    return true;
  }

  function getMonthRange(date) {
    const start = new Date(date.getFullYear(), date.getMonth(), 1);
    const end = new Date(date.getFullYear(), date.getMonth() + 1, 0);
    return { start, end };
  }

  function getWeekRange(date) {
    const base = startOfDay(date);
    const day = base.getDay();
    const start = new Date(base);
    start.setDate(base.getDate() - day);

    const end = new Date(start);
    end.setDate(start.getDate() + 6);

    return { start, end };
  }

  function addDays(date, days) {
    const d = new Date(date);
    d.setDate(d.getDate() + days);
    return d;
  }

  function addMonths(date, months) {
    return new Date(date.getFullYear(), date.getMonth() + months, 1);
  }

  function dateKeysInRange(start, end) {
    const keys = [];
    let cursor = startOfDay(start);

    while (cursor <= end) {
      keys.push(formatDateKey(cursor));
      cursor = addDays(cursor, 1);
    }

    return keys;
  }

  function getSlotsForDate(dateKey) {
    return Array.isArray(scheduleData[dateKey]) ? scheduleData[dateKey] : [];
  }

  function getVisibleSlotsForDate(dateKey) {
    return getSlotsForDate(dateKey).filter((slot) => {
      const slotTime = toTrimmedString(slot?.time);
      return !isPastTimeSlot(dateKey, slotTime);
    });
  }

  function hasVisibleAvailableSlots(dateKey) {
    return getVisibleSlotsForDate(dateKey).some((slot) => !!slot.available);
  }

  function getAvailableDates() {
    return Object.keys(scheduleData)
      .filter((dateKey) => hasVisibleAvailableSlots(dateKey))
      .sort();
  }

  function getRequestedServiceTypesForItems(items) {
    return uniqueStrings(
      (Array.isArray(items) ? items : [])
        .map((item) => inferSchedulingServiceType(item))
        .filter(Boolean),
    );
  }

  function getScheduleRequestKeyForItems(items) {
    return getRequestedServiceTypesForItems(items).join("|");
  }

  function updateScheduleMeta(nextMeta) {
    const safeMeta = nextMeta && typeof nextMeta === "object" ? nextMeta : {};
    scheduleMeta = {
      ...scheduleMeta,
      ...safeMeta,
    };

    CLOSED_WEEKDAYS = new Set(
      Array.isArray(scheduleMeta.closedWeekdays)
        ? scheduleMeta.closedWeekdays
        : [0, 5, 6],
    );
  }

  function setSelectedDateFromScope() {
    const selection = getTopDisplaySelection();

    if (selection?.date) {
      const selected = parseDateKey(selection.date);

      if (
        !isPastDate(selected) &&
        !isPastTimeSlot(selection.date, selection.time)
      ) {
        state.selectedDate = selected;
        state.currentDate = startOfMonth(state.selectedDate);
        return;
      }
    }

    const todayKey = formatDateKey(new Date());
    if (hasVisibleAvailableSlots(todayKey)) {
      state.selectedDate = parseDateKey(todayKey);
      state.currentDate = startOfMonth(state.selectedDate);
      return;
    }

    const availableDates = getAvailableDates().filter((dateKey) => {
      return !isPastDate(parseDateKey(dateKey));
    });

    if (availableDates.length) {
      state.selectedDate = parseDateKey(availableDates[0]);
      state.currentDate = startOfMonth(state.selectedDate);
      return;
    }

    state.selectedDate = startOfDay(new Date());
    state.currentDate = startOfMonth(state.selectedDate);
  }

  function ensureSelectedDateIsValidForSchedule() {
    if (!state.selectedDate) {
      setSelectedDateFromScope();
      return;
    }

    const selectedDateKey = formatDateKey(state.selectedDate);

    if (getVisibleSlotsForDate(selectedDateKey).length) {
      return;
    }

    setSelectedDateFromScope();
  }

  async function fetchScheduleDataForItems(items, options = {}) {
    const scopeItems = Array.isArray(items) ? items : [];
    const requestKey = getScheduleRequestKeyForItems(scopeItems);
    const force = !!options.force;

    if (!SCHEDULE_ENDPOINT_PATH) {
      return {
        ok: true,
        schedule: scheduleData,
        meta: scheduleMeta,
        skipped: true,
      };
    }

    if (!force && requestKey === state.scheduleRequestKey) {
      return {
        ok: true,
        schedule: scheduleData,
        meta: scheduleMeta,
        skipped: true,
      };
    }

    const url = new URL(SCHEDULE_ENDPOINT_PATH, window.location.origin);
    const requestedServices = getRequestedServiceTypesForItems(scopeItems);

    if (requestedServices.length) {
      url.searchParams.set("services", requestedServices.join(","));
    }

    const response = await fetch(url.toString(), {
      credentials: "same-origin",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    if (!response.ok) {
      throw new Error(
        `Schedule data request failed with status ${response.status}.`,
      );
    }

    const payload = await response.json().catch(() => null);

    if (
      !payload ||
      payload.ok !== true ||
      typeof payload.schedule !== "object"
    ) {
      throw new Error("Schedule data request returned an invalid response.");
    }

    scheduleData = payload.schedule || {};
    updateScheduleMeta(payload.meta || {});
    state.scheduleRequestKey = requestKey;

    return payload;
  }

  async function syncScheduleDataForCurrentScope(options = {}) {
    const scopeItems = getSchedulingScopeItems();

    if (!scopeItems.length) {
      renderSchedule();
      return;
    }

    const preserveDate =
      options.preserveDate !== undefined ? !!options.preserveDate : true;
    const force = !!options.force;
    const notice = document.getElementById("scheduleNotice");
    const previousNoticeText = notice ? notice.textContent : "";

    try {
      state.isScheduleLoading = true;

      if (notice) {
        notice.textContent = "Loading appointment availability…";
      }

      await fetchScheduleDataForItems(scopeItems, { force });

      if (!preserveDate) {
        setSelectedDateFromScope();
      } else {
        ensureSelectedDateIsValidForSchedule();
      }
    } catch (error) {
      console.error("Unable to refresh schedule data.", error);

      if (notice) {
        notice.textContent =
          previousNoticeText ||
          "Unable to refresh appointment availability right now.";
      }
    } finally {
      state.isScheduleLoading = false;
      renderSchedule();
    }
  }

  function getItemDisplayName(item) {
    return toTrimmedString(item?.serviceName, "Untitled Item");
  }

  function getItemModelText(item) {
    return toTrimmedString(item?.vehicleModel, "Vehicle");
  }

  function formatGroupedItemNames(items, maxVisible = 3) {
    const names = (Array.isArray(items) ? items : [])
      .map((item) => getItemDisplayName(item))
      .filter(Boolean);

    if (!names.length) return "No items";
    if (names.length <= maxVisible) return names.join(", ");

    return `${names.slice(0, maxVisible).join(", ")} +${names.length - maxVisible} more`;
  }

  function getCombinedModelText(items) {
    const models = Array.from(
      new Set(
        (Array.isArray(items) ? items : [])
          .map((item) => getItemModelText(item))
          .filter(Boolean),
      ),
    );

    if (!models.length) return "Vehicle";
    if (models.length === 1) return models[0];
    return models.join(" • ");
  }

  function renderCombinedThumbs(items, maxVisible = 5) {
    const visibleItems = (Array.isArray(items) ? items : []).slice(
      0,
      maxVisible,
    );

    const thumbsHtml = visibleItems
      .map((item) => {
        const image = toTrimmedString(item.image);
        const title = getItemDisplayName(item);
        const fallbackLetter = escapeHtml(
          (title.trim().charAt(0) || "?").toUpperCase(),
        );

        if (image) {
          return `
            <span class="schedule-combined-thumb" title="${escapeHtml(title)}">
              <img
                src="${escapeHtml(image)}"
                alt="${escapeHtml(title)}"
                loading="lazy"
              />
            </span>
          `;
        }

        return `
          <span class="schedule-combined-thumb schedule-combined-thumb--fallback" title="${escapeHtml(title)}">
            ${fallbackLetter}
          </span>
        `;
      })
      .join("");

    const remaining = items.length - visibleItems.length;

    return `
      <div class="schedule-combined-thumbs">
        ${thumbsHtml}
        ${
          remaining > 0
            ? `
              <span class="schedule-combined-thumb schedule-combined-thumb--count">
                +${remaining}
              </span>
            `
            : ""
        }
      </div>
    `;
  }

  function buildScopeSelectionPayload(slotDate, slotTime) {
    const fullLabel = formatFullSlotLabel(slotDate, slotTime);

    return {
      date: slotDate,
      time: slotTime,
      label: fullLabel,
      displayLabel: fullLabel,
    };
  }

  function applyScopeAppointment(
    slotDate,
    slotTime,
    scopeItems = getSchedulingScopeItems(),
  ) {
    const safeItems = Array.isArray(scopeItems) ? scopeItems : [];
    const totalMinutes = getGroupTotalMinutes(safeItems);
    const durationLabel = formatMinutesHuman(totalMinutes);
    const estimatedEndTime = addMinutesToClockTime(slotTime, totalMinutes);
    const payload = buildScopeSelectionPayload(slotDate, slotTime);

    safeItems.forEach((item) => {
      setItemScheduleSelection(item, payload);
    });

    return {
      groupItems: safeItems,
      totalMinutes,
      durationLabel,
      estimatedEndTime,
      selectionLabel: payload.label,
    };
  }

  function clearScopeSelection(scopeItems = getSchedulingScopeItems()) {
    const nextSelections = {
      ...safeObject(getScheduleSelections()),
    };

    let clearedCount = 0;

    (Array.isArray(scopeItems) ? scopeItems : []).forEach((item) => {
      if (!item?.key) return;
      if (nextSelections[item.key]) {
        delete nextSelections[item.key];
        clearedCount += 1;
      }
    });

    localStorage.setItem(SCHEDULE_KEY, JSON.stringify(nextSelections));
    return clearedCount;
  }

  function renderScheduleSelectionSummary() {
    const root = document.getElementById("scheduleItemTabs");
    if (!root) return;

    const items = getSchedulingScopeItems();
    if (!items.length) {
      root.innerHTML = `
        <article class="schedule-selection-summary__card schedule-service-card">
          <div class="schedule-service-card__main">
            <h3 class="schedule-service-card__title">No schedulable items</h3>
            <p class="schedule-service-card__desc">
              There are no cart items that currently require a date and time.
            </p>
          </div>
        </article>
      `;
      return;
    }

    const selection = getTopDisplaySelection(items);
    const selectionLabel = getSelectionLabel(selection);
    const totalMinutes = getGroupTotalMinutes(items);
    const totalDuration = formatMinutesHuman(totalMinutes);
    const groupedModelText = getCombinedModelText(items);
    const titleText =
      items.length === 1
        ? "Schedule This Item"
        : `Schedule ${items.length} Items Together`;

    const currentSelectionText = selectionLabel
      ? `${selectionLabel}${totalDuration ? ` • ${totalDuration}` : ""}${
          selection?.time
            ? ` • Ends about ${addMinutesToClockTime(selection.time, totalMinutes)}`
            : ""
        }`
      : "No date/time selected yet.";

    const itemsHtml = items
      .map((item) => {
        const image = toTrimmedString(item.image);
        const title = getItemDisplayName(item);
        const fallbackLetter = escapeHtml(
          (title.trim().charAt(0) || "?").toUpperCase(),
        );

        return `
          <div class="schedule-selection-summary__included-item">
            <div class="schedule-selection-summary__included-media">
              ${
                image
                  ? `
                    <img
                      class="schedule-selection-summary__included-image"
                      src="${escapeHtml(image)}"
                      alt="${escapeHtml(title)}"
                      loading="lazy"
                    />
                  `
                  : `
                    <div
                      class="schedule-selection-summary__included-fallback"
                      aria-hidden="true"
                    >
                      ${fallbackLetter}
                    </div>
                  `
              }
            </div>

            <div class="schedule-selection-summary__included-body">
              <strong>${escapeHtml(title)}</strong>
              <span>${escapeHtml(item.label)} • ${escapeHtml(getItemModelText(item))}</span>

              <div class="schedule-selection-summary__included-meta">
                <span>${escapeHtml(item.servicePrice || "—")}</span>
                <span>${escapeHtml(item.duration || "To be confirmed")}</span>
                <span>Qty ${item.qty}</span>
                ${item.variant ? `<span>${escapeHtml(item.variant)}</span>` : ""}
              </div>

              ${
                item.description
                  ? `
                    <p class="schedule-selection-summary__included-desc">
                      ${escapeHtml(item.description)}
                    </p>
                  `
                  : ""
              }
            </div>
          </div>
        `;
      })
      .join("");

    root.innerHTML = `
      <article class="schedule-selection-summary__card schedule-service-card">
        <div class="schedule-service-card__main">
          <div class="schedule-service-card__badges">

            <span class="schedule-service-pill schedule-service-pill--type">
              One Appointment
            </span>

            <span class="schedule-service-pill schedule-service-pill--model">
              ${escapeHtml(groupedModelText)}
            </span>

            <span class="schedule-service-pill ${
              selectionLabel
                ? "schedule-service-pill--complete"
                : "schedule-service-pill--pending"
            }">
              ${selectionLabel ? "Scheduled" : "Needs Date"}
            </span>
          </div>

          <h3 class="schedule-service-card__title">
            ${escapeHtml(titleText)}
          </h3>

          <p class="schedule-service-card__desc">
            ${escapeHtml(formatGroupedItemNames(items, 8))}
          </p>

          ${renderCombinedThumbs(items, 8)}

          <div class="schedule-service-card__meta">
            <div class="schedule-service-meta">
              <span class="schedule-service-meta__label">Items</span>
              <strong>${items.length}</strong>
            </div>

            <div class="schedule-service-meta">
              <span class="schedule-service-meta__label">Estimated Time</span>
              <strong>${escapeHtml(totalDuration)}</strong>
            </div>

            <div class="schedule-service-meta">
              <span class="schedule-service-meta__label">Vehicle</span>
              <strong>${escapeHtml(groupedModelText)}</strong>
            </div>
          </div>

          <div class="schedule-service-card__selection">
            <strong>Current Selection:</strong>
            <span>${escapeHtml(currentSelectionText)}</span>
          </div>

          <div class="schedule-selection-summary__switcher">
            <div class="schedule-selection-summary__section-head">
              <h4>Included Cart Items</h4>

              <p>All schedulable items below will use the same appointment time.</p>
            </div>

            <div class="schedule-selection-summary__included-list">
              ${itemsHtml}
            </div>
          </div>
        </div>
      </article>
    `;
  }

  function renderProgress() {
    const text = document.getElementById("scheduleProgressText");
    const list = document.getElementById("scheduleProgressList");
    if (!text || !list) return;

    const items = getSchedulingScopeItems();
    const selection = getTopDisplaySelection(items);
    const selectionLabel = getSelectionLabel(selection);
    const totalMinutes = getGroupTotalMinutes(items);
    const totalDuration = formatMinutesHuman(totalMinutes);
    const estimatedEndTime = selection?.time
      ? addMinutesToClockTime(selection.time, totalMinutes)
      : "";

    const scheduledAppointments = selectionLabel ? 1 : 0;
    const coveredItems = selectionLabel ? items.length : 0;

    text.textContent = `${coveredItems} of ${items.length} items covered`;

    list.innerHTML = `
      <div class="schedule-progress-controls">
        <div class="schedule-progress-controls__summary schedule-progress-row__main">

          <strong>${escapeHtml(getCombinedModelText(items))} • ${items.length} item${
            items.length === 1 ? "" : "s"
          }</strong>

          <div class="schedule-progress-row__items">
            ${escapeHtml(formatGroupedItemNames(items, 8))}
          </div>

          
          <div class="schedule-progress-row__grouped-meta">
            ${escapeHtml(totalDuration)}
          </div>

        </div>

        ${
          selectionLabel
            ? `
              <div class="schedule-progress-controls__group-current">
                <div class="schedule-progress-controls__summary schedule-progress-controls__summary--current">
                  <span>
                    ${escapeHtml(selectionLabel)}
                  </span>
                </div>

                <div class="schedule-progress-controls__actions">
                  <button
                    type="button"
                    class="schedule-reset-btn"
                    id="scheduleClearGroupedBtn"
                  >
                    Clear Appointment
                  </button>
                </div>
              </div>
            `
            : ""
        }
      </div>

      
    `;

    const clearBtn = document.getElementById("scheduleClearGroupedBtn");
    if (clearBtn) {
      clearBtn.addEventListener("click", () => {
        const clearedCount = clearScopeSelection(items);
        state.selectedDate = null;

        const notice = document.getElementById("scheduleNotice");
        if (notice) {
          notice.textContent = `Cleared the saved appointment for ${clearedCount} item${
            clearedCount === 1 ? "" : "s"
          }. Choose a new time to schedule them together.`;
        }

        syncScheduleDataForCurrentScope({
          force: true,
          preserveDate: false,
        });
      });
    }
  }

  function renderMonthYearSelectors() {
    const monthSelect = document.getElementById("scheduleMonthSelect");
    const yearSelect = document.getElementById("scheduleYearSelect");
    if (!monthSelect || !yearSelect) return;

    monthSelect.innerHTML = monthNames
      .map(
        (name, index) =>
          `<option value="${index}">${escapeHtml(name)}</option>`,
      )
      .join("");

    const currentYear = new Date().getFullYear();
    const years = [];
    for (let year = currentYear - 1; year <= currentYear + 2; year += 1) {
      years.push(year);
    }

    yearSelect.innerHTML = years
      .map((year) => `<option value="${year}">${year}</option>`)
      .join("");

    monthSelect.value = String(state.currentDate.getMonth());
    yearSelect.value = String(state.currentDate.getFullYear());

    monthSelect.addEventListener("change", () => {
      const month = Number(monthSelect.value);
      const year = Number(yearSelect.value);
      state.currentDate = new Date(year, month, 1);
      renderSchedule();
    });

    yearSelect.addEventListener("change", () => {
      const month = Number(monthSelect.value);
      const year = Number(yearSelect.value);
      state.currentDate = new Date(year, month, 1);
      renderSchedule();
    });
  }

  function usesSidebarSlotsView(view = state.view) {
    return view === "month";
  }

  function isInlineSlotsView(view = state.view) {
    return !usesSidebarSlotsView(view);
  }

  function isAutoExpandedInlineView(view = state.view) {
    return view === "week" || view === "day";
  }

  function syncScheduleViewLayout() {
    const contentGrid = document.querySelector(".schedule-content-grid");
    const calendarPanel = document.querySelector(".schedule-calendar-panel");
    const weekdaysRoot = document.getElementById("scheduleWeekdays");

    const inlineView = isInlineSlotsView();

    if (contentGrid) {
      contentGrid.classList.toggle("is-inline-slot-view", inlineView);
    }

    if (calendarPanel) {
      calendarPanel.classList.toggle("is-inline-slot-view", inlineView);
    }

    if (weekdaysRoot) {
      weekdaysRoot.classList.toggle("is-hidden", inlineView);
    }
  }

  function setCurrentDateForView(view, baseDate) {
    const anchor = baseDate ? startOfDay(baseDate) : startOfDay(new Date());

    if (view === "month" || view === "available") {
      state.currentDate = startOfMonth(anchor);
      return;
    }

    state.currentDate = anchor;
  }

  function toggleExpandedInlineDate(dateKey) {
    state.expandedInlineDateKey =
      state.expandedInlineDateKey === dateKey ? null : dateKey;
  }

  function bindViewButtons() {
    document.querySelectorAll(".schedule-view-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const nextView = btn.getAttribute("data-view") || "month";
        const nextBaseDate =
          state.selectedDate || state.currentDate || new Date();

        state.view = nextView;

        if (!isAutoExpandedInlineView(nextView)) {
          state.expandedInlineDateKey = null;
        }

        setCurrentDateForView(nextView, nextBaseDate);
        persistScheduleUiState();

        document.querySelectorAll(".schedule-view-btn").forEach((button) => {
          button.classList.toggle(
            "is-active",
            button.getAttribute("data-view") === nextView,
          );
        });

        renderSchedule();
      });
    });
  }

  function bindNavButtons() {
    const prevBtn = document.getElementById("schedulePrevBtn");
    const nextBtn = document.getElementById("scheduleNextBtn");

    if (prevBtn) {
      prevBtn.addEventListener("click", () => {
        if (state.view === "month" || state.view === "available") {
          state.currentDate = addMonths(state.currentDate, -1);
        } else if (state.view === "week") {
          state.currentDate = addDays(state.currentDate, -7);
        } else {
          state.currentDate = addDays(state.currentDate, -1);
        }
        renderSchedule();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener("click", () => {
        if (state.view === "month" || state.view === "available") {
          state.currentDate = addMonths(state.currentDate, 1);
        } else if (state.view === "week") {
          state.currentDate = addDays(state.currentDate, 7);
        } else {
          state.currentDate = addDays(state.currentDate, 1);
        }
        renderSchedule();
      });
    }
  }

  function renderWeekdays() {
    const weekdaysRoot = document.getElementById("scheduleWeekdays");
    if (!weekdaysRoot) return;

    if (isInlineSlotsView()) {
      weekdaysRoot.innerHTML = "";
      weekdaysRoot.classList.add("is-hidden");
      return;
    }

    weekdaysRoot.classList.remove("is-hidden");
    weekdaysRoot.innerHTML = weekdayNamesShort
      .map((day) => `<div class="schedule-weekday">${escapeHtml(day)}</div>`)
      .join("");
  }

  function renderCalendarTitle() {
    const title = document.getElementById("scheduleCalendarTitle");
    const subtitle = document.getElementById("scheduleCalendarSubtitle");
    const monthSelect = document.getElementById("scheduleMonthSelect");
    const yearSelect = document.getElementById("scheduleYearSelect");

    if (!title || !subtitle) return;

    if (monthSelect) monthSelect.value = String(state.currentDate.getMonth());
    if (yearSelect) yearSelect.value = String(state.currentDate.getFullYear());

    if (state.view === "month") {
      title.textContent = formatMonthTitle(state.currentDate);
      subtitle.textContent = "Full month calendar";
    } else if (state.view === "available") {
      title.textContent = formatMonthTitle(state.currentDate);
      subtitle.textContent = "Open a day below to choose a time";
    } else if (state.view === "week") {
      const range = getWeekRange(state.currentDate);
      title.textContent = `${formatLongDate(range.start)} – ${formatLongDate(
        range.end,
      )}`;
      subtitle.textContent = "Open a day below to choose a time";
    } else if (state.view === "day") {
      title.textContent = formatLongDate(state.currentDate);
      subtitle.textContent = "Open the day below to choose a time";
    }
  }

  function buildMonthGridDates(date) {
    const { start, end } = getMonthRange(date);
    const firstGridDate = addDays(start, -start.getDay());
    const lastGridDate = addDays(end, 6 - end.getDay());
    return dateKeysInRange(firstGridDate, lastGridDate);
  }

  function buildWeekGridDates(date) {
    const { start, end } = getWeekRange(date);
    return dateKeysInRange(start, end);
  }

  function buildDayGridDates(date) {
    return [formatDateKey(date)];
  }

  function buildAvailableOnlyDates(date) {
    const { start, end } = getMonthRange(date);

    return getAvailableDates().filter((dateKey) => {
      const d = parseDateKey(dateKey);

      if (d < start || d > end) return false;
      if (isPastDate(d)) return false;
      if (isClosedWeekday(d)) return false;

      const slots = getVisibleSlotsForDate(dateKey);
      if (!slots.length) return false;

      const availableCount = slots.filter((slot) => !!slot.available).length;
      return availableCount > 0;
    });
  }

  function buildSlotCardMarkup(
    slot,
    dateKey,
    topSelection,
    totalMinutes,
    totalDuration,
  ) {
    const slotTime = toTrimmedString(slot?.time);
    const available = !!slot?.available;
    const isSelected =
      topSelection?.date === dateKey && topSelection?.time === slotTime;
    const estimatedEndTime = addMinutesToClockTime(slotTime, totalMinutes);

    return `
    <button
      type="button"
      class="schedule-slot-card ${
        available ? "is-available" : "is-unavailable"
      } ${isSelected ? "is-selected" : ""}"
      data-slot-date="${escapeHtml(dateKey)}"
      data-slot-time="${escapeHtml(slotTime)}"
      ${available ? "" : 'disabled aria-disabled="true"'}
    >
      <div class="schedule-slot-card__time">${escapeHtml(slotTime)}</div>

      <div class="schedule-slot-card__status">${escapeHtml(
        toTrimmedString(slot?.label) ||
          (available ? "Available" : "Unavailable"),
      )}</div>

      <div class="schedule-slot-card__group-meta">
        <span>${escapeHtml(totalDuration)} total</span>
        ${
          estimatedEndTime
            ? `<span>Ends about ${escapeHtml(estimatedEndTime)}</span>`
            : ""
        }
      </div>
    </button>
  `;
  }

  function bindSlotButtonHandlers(root, displayItems) {
    const notice = document.getElementById("scheduleNotice");

    if (!root || !displayItems.length || !notice) return;

    root.querySelectorAll("[data-slot-date][data-slot-time]").forEach((btn) => {
      btn.addEventListener("click", () => {
        const slotDate = btn.getAttribute("data-slot-date") || "";
        const slotTime = btn.getAttribute("data-slot-time") || "";
        if (!slotDate || !slotTime) return;

        if (isPastTimeSlot(slotDate, slotTime)) {
          notice.textContent =
            "That appointment time has already passed. Please choose another available slot.";
          syncScheduleDataForCurrentScope({
            force: true,
            preserveDate: true,
          });
          return;
        }

        state.selectedDate = parseDateKey(slotDate);
        state.expandedInlineDateKey = slotDate;
        setCurrentDateForView(state.view, state.selectedDate);

        const result = applyScopeAppointment(slotDate, slotTime, displayItems);

        notice.textContent = `Scheduled ${
          result.groupItems.length
        } items for ${formatFullSlotLabel(
          slotDate,
          slotTime,
        )}. Total estimated time: ${result.durationLabel}${
          result.estimatedEndTime
            ? `. Estimated completion: ${result.estimatedEndTime}.`
            : "."
        }`;

        syncScheduleDataForCurrentScope({
          force: true,
          preserveDate: true,
        });
      });
    });
  }

  function getInlineDayEmptyMarkup(dateObj, dateKey) {
    const closedDay = isClosedWeekday(dateObj);
    const passedDay = isPastDate(dateObj);
    const isToday = isSameCalendarDay(dateObj, new Date());

    if (passedDay) {
      return `
      <div class="schedule-inline-day__empty">
        <p>This day has already passed.</p>
      </div>
    `;
    }

    if (closedDay) {
      return `
      <div class="schedule-inline-day__empty">
        <p>This day is closed for scheduling.</p>
      </div>
    `;
    }

    if (!getVisibleSlotsForDate(dateKey).length) {
      return `
      <div class="schedule-inline-day__empty">
        <p>${
          isToday
            ? "No remaining appointment times are available today."
            : "No appointment times are listed for this day."
        }</p>
      </div>
    `;
    }

    return "";
  }

  function buildInlineDayMarkup(dateKey, displayItems, topSelection) {
    const dateObj = parseDateKey(dateKey);
    const slots = getVisibleSlotsForDate(dateKey);
    const totalMinutes = getGroupTotalMinutes(displayItems);
    const totalDuration = formatMinutesHuman(totalMinutes);
    const forcedExpanded = isAutoExpandedInlineView();
    const isExpanded =
      forcedExpanded || state.expandedInlineDateKey === dateKey;
    const isSelected =
      state.selectedDate && formatDateKey(state.selectedDate) === dateKey;

    let bodyMarkup = "";

    if (isExpanded) {
      bodyMarkup =
        slots.length && !isPastDate(dateObj) && !isClosedWeekday(dateObj)
          ? `
          <div class="schedule-slot-list">
            ${slots
              .map((slot) =>
                buildSlotCardMarkup(
                  slot,
                  dateKey,
                  topSelection,
                  totalMinutes,
                  totalDuration,
                ),
              )
              .join("")}
          </div>
        `
          : getInlineDayEmptyMarkup(dateObj, dateKey);
    }

    if (forcedExpanded) {
      return `
      <section class="schedule-inline-day is-expanded ${
        isSelected ? "is-selected" : ""
      }">
        <div class="schedule-inline-day__toggle schedule-inline-day__toggle--static">
          <div class="schedule-inline-day__heading">
            <h4>${escapeHtml(formatLongDate(dateObj))}</h4>
          </div>
        </div>

        <div class="schedule-inline-day__body">
          ${bodyMarkup}
        </div>
      </section>
    `;
    }

    return `
    <section class="schedule-inline-day ${
      isExpanded ? "is-expanded" : ""
    } ${isSelected ? "is-selected" : ""}">
      <button
        type="button"
        class="schedule-inline-day__toggle"
        data-inline-date-key="${escapeHtml(dateKey)}"
        aria-expanded="${isExpanded ? "true" : "false"}"
      >
        <div class="schedule-inline-day__heading">
          <h4>${escapeHtml(formatLongDate(dateObj))}</h4>
        </div>

        <span class="schedule-inline-day__chevron" aria-hidden="true">${
          isExpanded ? "−" : "+"
        }</span>
      </button>

      ${
        isExpanded
          ? `
            <div class="schedule-inline-day__body">
              ${bodyMarkup}
            </div>
          `
          : ""
      }
    </section>
  `;
  }

  function renderInlineAccordionGrid(grid, dateKeys) {
    const displayItems = getSchedulingScopeItems();
    const topSelection = getTopDisplaySelection(displayItems);
    const forcedExpanded = isAutoExpandedInlineView();

    grid.classList.add("schedule-calendar-grid--inline-slots");

    if (state.view === "week") {
      grid.classList.add("schedule-calendar-grid--inline-week");
    } else if (state.view === "day") {
      grid.classList.add("schedule-calendar-grid--inline-day");
    } else if (state.view === "available") {
      grid.classList.add("schedule-calendar-grid--inline-available");
    }

    grid.innerHTML = dateKeys
      .map((dateKey) =>
        buildInlineDayMarkup(dateKey, displayItems, topSelection),
      )
      .join("");

    if (!forcedExpanded) {
      grid.querySelectorAll("[data-inline-date-key]").forEach((btn) => {
        btn.addEventListener("click", () => {
          const dateKey = btn.getAttribute("data-inline-date-key") || "";
          if (!dateKey) return;

          state.selectedDate = parseDateKey(dateKey);
          toggleExpandedInlineDate(dateKey);
          renderSchedule();
        });
      });
    } else {
      grid.querySelectorAll(".schedule-inline-day").forEach((panel, index) => {
        const dateKey = dateKeys[index];
        if (!dateKey) return;

        panel.addEventListener("click", (event) => {
          const slotButton = event.target.closest(
            "[data-slot-date][data-slot-time]",
          );
          if (slotButton) return;

          state.selectedDate = parseDateKey(dateKey);
          renderSchedule();
        });
      });
    }

    bindSlotButtonHandlers(grid, displayItems);
  }
  function renderCalendarGrid() {
    const grid = document.getElementById("scheduleCalendarGrid");
    if (!grid) return;

    grid.className = "schedule-calendar-grid";

    let dateKeys = [];

    if (state.view === "month") {
      dateKeys = buildMonthGridDates(state.currentDate);
      grid.classList.add("schedule-calendar-grid--month");
    } else if (state.view === "available") {
      dateKeys = buildAvailableOnlyDates(state.currentDate);
    } else if (state.view === "week") {
      dateKeys = buildWeekGridDates(state.currentDate);
    } else if (state.view === "day") {
      dateKeys = buildDayGridDates(state.currentDate);
    }

    if (!dateKeys.length) {
      grid.innerHTML = `
      <div class="schedule-empty-calendar">
        <p>No appointment dates are available for this view.</p>
      </div>
    `;
      return;
    }

    if (isInlineSlotsView()) {
      renderInlineAccordionGrid(grid, dateKeys);
      return;
    }

    const currentMonth = state.currentDate.getMonth();
    const currentYear = state.currentDate.getFullYear();
    const todayKey = formatDateKey(new Date());

    grid.innerHTML = dateKeys
      .map((dateKey) => {
        const dateObj = parseDateKey(dateKey);
        const slots = getVisibleSlotsForDate(dateKey);
        const totalCount = slots.length;
        const availableCount = slots.filter((slot) => !!slot.available).length;
        const isOtherMonth =
          state.view === "month" &&
          (dateObj.getMonth() !== currentMonth ||
            dateObj.getFullYear() !== currentYear);
        const isToday = dateKey === todayKey;
        const isSelected =
          state.selectedDate && formatDateKey(state.selectedDate) === dateKey;
        const closedDay = isClosedWeekday(dateObj);
        const passedDay = isPastDate(dateObj);

        let statusText = "No slots";
        let statusClass = "";
        let isDisabledDay = false;

        if (passedDay) {
          statusText = "Passed";
          statusClass = "is-passed";
          isDisabledDay = true;
        } else if (closedDay) {
          statusText = "Closed";
          statusClass = "is-closed";
          isDisabledDay = true;
        } else if (totalCount > 0 && availableCount > 0) {
          statusText = `${availableCount} available`;
          statusClass = "is-available";
        } else if (totalCount > 0 && availableCount === 0) {
          statusText = "Fully booked";
          statusClass = "is-booked";
        }

        const countText = passedDay
          ? ""
          : totalCount
            ? `${totalCount} slot${totalCount === 1 ? "" : "s"}`
            : "—";

        return `
        <button
          type="button"
          class="schedule-day-card ${isOtherMonth ? "is-other-month" : ""} ${
            isToday ? "is-today" : ""
          } ${isSelected ? "is-selected" : ""} ${
            isDisabledDay ? "is-disabled-day" : ""
          } ${passedDay ? "is-passed-day" : ""}"
          data-date-key="${escapeHtml(dateKey)}"
          ${isDisabledDay ? 'disabled aria-disabled="true"' : ""}
        >
          <div class="schedule-day-card__top">
            <span class="schedule-day-card__day">${dateObj.getDate()}</span>
            <span class="schedule-day-card__weekday">${
              weekdayNamesShort[dateObj.getDay()]
            }</span>
          </div>

          <div class="schedule-day-card__meta">
            <span class="schedule-day-card__status ${statusClass}">${escapeHtml(
              statusText,
            )}</span>
            <span class="schedule-day-card__count">${escapeHtml(
              countText,
            )}</span>
          </div>
        </button>
      `;
      })
      .join("");

    grid.querySelectorAll("[data-date-key]").forEach((btn) => {
      btn.addEventListener("click", () => {
        const dateKey = btn.getAttribute("data-date-key") || "";
        if (!dateKey) return;

        state.selectedDate = parseDateKey(dateKey);
        state.currentDate = startOfMonth(state.selectedDate);
        renderSchedule();
      });
    });
  }

  function renderSelectedDaySlots() {
    const title = document.getElementById("scheduleSelectedDateTitle");
    const subtitle = document.getElementById("scheduleSelectedDateSubtitle");
    const slotsRoot = document.getElementById("scheduleDaySlots");
    const notice = document.getElementById("scheduleNotice");

    if (!title || !subtitle || !slotsRoot || !notice) return;

    const displayItems = getSchedulingScopeItems();
    const topSelection = getTopDisplaySelection(displayItems);
    const displayLabel = `${displayItems.length} selected item${
      displayItems.length === 1 ? "" : "s"
    }`;

    if (isInlineSlotsView()) {
      title.textContent = "Choose a Time";
      subtitle.textContent =
        "Open a day in the calendar area to view appointment times.";
      slotsRoot.innerHTML = "";
      slotsRoot.removeAttribute("data-rendered-date-key");
      slotsRoot.scrollTop = 0;

      if (topSelection?.date && topSelection?.time) {
        const totalMinutes = getGroupTotalMinutes(displayItems);
        const totalDuration = formatMinutesHuman(totalMinutes);
        const endText = addMinutesToClockTime(topSelection.time, totalMinutes);

        notice.textContent = `${displayLabel}: ${getSelectionLabel(
          topSelection,
        )} • ${totalDuration}${endText ? ` • Ends about ${endText}` : ""}`;
      } else if (displayItems.length) {
        notice.textContent = `Open a day below and choose one available appointment slot for ${displayLabel}.`;
      }

      syncContinueButton();
      return;
    }

    if (!displayItems.length) {
      slotsRoot.innerHTML = `
      <div class="schedule-empty-slots">
        <p>No cart items currently require scheduling.</p>
      </div>
    `;
      slotsRoot.removeAttribute("data-rendered-date-key");
      slotsRoot.scrollTop = 0;
      syncContinueButton();
      return;
    }

    if (!state.selectedDate) {
      slotsRoot.innerHTML = `
      <div class="schedule-empty-slots">
        <p>Select a day to view available appointment times.</p>
      </div>
    `;
      slotsRoot.removeAttribute("data-rendered-date-key");
      slotsRoot.scrollTop = 0;
      syncContinueButton();
      return;
    }

    const dateKey = formatDateKey(state.selectedDate);
    const previousDateKey =
      slotsRoot.getAttribute("data-rendered-date-key") || "";
    const shouldResetScroll = previousDateKey !== dateKey;
    const slots = getVisibleSlotsForDate(dateKey);
    const totalMinutes = getGroupTotalMinutes(displayItems);
    const totalDuration = formatMinutesHuman(totalMinutes);

    title.textContent = formatLongDate(state.selectedDate);

    const closedDay = isClosedWeekday(state.selectedDate);
    const passedDay = isPastDate(state.selectedDate);
    const isToday = isSameCalendarDay(state.selectedDate, new Date());

    subtitle.textContent = passedDay
      ? "This date has already passed"
      : slots.length
        ? `Availability for ${displayLabel}`
        : closedDay
          ? "Closed for scheduling"
          : isToday
            ? "No remaining appointment times are available today"
            : "This day currently has no appointment slots";

    if (passedDay) {
      slotsRoot.innerHTML = "";
      slotsRoot.setAttribute("data-rendered-date-key", dateKey);
      slotsRoot.scrollTop = 0;
      syncContinueButton();
      notice.textContent = "Please choose today or a future open business day.";
      return;
    }

    if (!slots.length) {
      slotsRoot.innerHTML = `
      <div class="schedule-empty-slots">
        <p>${
          closedDay
            ? "This day is closed."
            : isToday
              ? "All earlier appointment times for today have already passed."
              : "No appointments are listed for this day."
        }</p>
      </div>
    `;
      slotsRoot.setAttribute("data-rendered-date-key", dateKey);
      slotsRoot.scrollTop = 0;
      syncContinueButton();
      notice.textContent = closedDay
        ? "Please choose an open business day."
        : isToday
          ? "Please choose a later time today or select another future day."
          : `Select a day with available times for ${displayLabel}.`;
      return;
    }

    slotsRoot.innerHTML = `
    <div class="schedule-slot-list">
      ${slots
        .map((slot) =>
          buildSlotCardMarkup(
            slot,
            dateKey,
            topSelection,
            totalMinutes,
            totalDuration,
          ),
        )
        .join("")}
    </div>
  `;

    bindSlotButtonHandlers(slotsRoot, displayItems);

    slotsRoot.setAttribute("data-rendered-date-key", dateKey);

    if (shouldResetScroll) {
      slotsRoot.scrollTop = 0;
    }

    if (topSelection?.date && topSelection?.time) {
      const endText = addMinutesToClockTime(topSelection.time, totalMinutes);

      notice.textContent = `${displayLabel}: ${getSelectionLabel(
        topSelection,
      )} • ${totalDuration}${endText ? ` • Ends about ${endText}` : ""}`;
    } else {
      notice.textContent = `Choose one available appointment slot for ${displayLabel}.`;
    }

    syncContinueButton();
  }

  let scheduleSidebarHeightRaf = 0;
  let scheduleSidebarHeightObserver = null;

  function syncScheduleSidebarHeight() {
    const calendarPanel = document.querySelector(".schedule-calendar-panel");
    const sidebar = document.querySelector(".schedule-sidebar");

    if (!calendarPanel || !sidebar) return;

    if (window.innerWidth <= 1180 || isInlineSlotsView()) {
      sidebar.style.removeProperty("--schedule-sidebar-height");
      return;
    }

    const calendarHeight = Math.ceil(
      calendarPanel.getBoundingClientRect().height,
    );

    if (calendarHeight > 0) {
      sidebar.style.setProperty(
        "--schedule-sidebar-height",
        `${calendarHeight}px`,
      );
    }
  }

  function requestScheduleSidebarHeightSync() {
    if (scheduleSidebarHeightRaf) {
      window.cancelAnimationFrame(scheduleSidebarHeightRaf);
    }

    scheduleSidebarHeightRaf = window.requestAnimationFrame(() => {
      scheduleSidebarHeightRaf = 0;
      syncScheduleSidebarHeight();
    });
  }

  function bindScheduleSidebarHeightSync() {
    const calendarPanel = document.querySelector(".schedule-calendar-panel");

    if (!calendarPanel) return;

    if (scheduleSidebarHeightObserver) {
      scheduleSidebarHeightObserver.disconnect();
      scheduleSidebarHeightObserver = null;
    }

    if (typeof ResizeObserver === "function") {
      scheduleSidebarHeightObserver = new ResizeObserver(() => {
        requestScheduleSidebarHeightSync();
      });

      scheduleSidebarHeightObserver.observe(calendarPanel);
    }

    window.addEventListener("resize", requestScheduleSidebarHeightSync);
    window.addEventListener("load", requestScheduleSidebarHeightSync);
  }

  function syncContinueButton() {
    const continueBtn = document.getElementById("continueToCheckoutBtn");
    if (!continueBtn) return;

    const enabled = areAllItemsScheduled(getCart(), getScheduleSelections());

    continueBtn.classList.toggle("is-disabled", !enabled);
    continueBtn.setAttribute("aria-disabled", enabled ? "false" : "true");
    continueBtn.href = enabled ? APP_CHECKOUT_PAGE_PATH : "#";

    continueBtn.onclick = (event) => {
      if (!enabled) {
        event.preventDefault();
      }
    };
  }

  function renderSchedule() {
    persistScheduleUiState();
    syncScheduleViewLayout();
    renderScheduleSelectionSummary();
    renderProgress();
    renderCalendarTitle();
    renderWeekdays();
    renderCalendarGrid();
    renderSelectedDaySlots();
    syncContinueButton();
    requestScheduleSidebarHeightSync();
  }

  document.addEventListener("DOMContentLoaded", () => {
    const hasSchedulingUI =
      !!document.getElementById("scheduleCalendarGrid") &&
      !!document.getElementById("scheduleItemTabs");

    if (!hasSchedulingUI) return;

    updateCartBadge();

    if (!guardCartState()) return;

    if (typeof queueCartSessionSync === "function") {
      queueCartSessionSync(getCart());
    }

    setSelectedDateFromScope();

    renderMonthYearSelectors();
    bindViewButtons();
    bindNavButtons();
    bindScheduleSidebarHeightSync();

    renderSchedule();

    syncScheduleDataForCurrentScope({
      force: true,
      preserveDate: true,
    });

    window.addEventListener("storage", (e) => {
      if (e.key === CART_KEY || e.key === SCHEDULE_KEY) {
        if (!guardCartState()) return;

        setSelectedDateFromScope();

        syncScheduleDataForCurrentScope({
          force: true,
          preserveDate: true,
        });
      }
    });
  });
})();
