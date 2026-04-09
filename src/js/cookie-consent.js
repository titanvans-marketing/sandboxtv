(function () {
  const STORAGE_KEY = "site_cookie_consent_v1";
  const CONSENT_VERSION = "2026-04-05";
  const GTM_EVENT_UPDATE = "cookie_consent_update";
  const GTM_EVENT_ACCEPT_ALL = "cookie_consent_accept_all";
  const GTM_EVENT_REJECT_ALL = "cookie_consent_reject_all";
  const GTM_EVENT_PREFERENCES_SAVED = "cookie_consent_preferences_saved";

  const defaultConsent = {
    necessary: true,
    preferences: false,
    analytics: false,
    marketing: false,
    consented: false,
    consentVersion: CONSENT_VERSION,
    updatedAt: null,
  };

  function ensureDataLayer() {
    window.dataLayer = window.dataLayer || [];
    window.gtag =
      window.gtag ||
      function () {
        window.dataLayer.push(arguments);
      };
  }

  function readConsent() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === "object" ? parsed : null;
    } catch (error) {
      return null;
    }
  }

  function writeConsent(consent) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(consent));
  }

  function mapToGoogleConsent(consent) {
    return {
      ad_storage: consent.marketing ? "granted" : "denied",
      ad_user_data: consent.marketing ? "granted" : "denied",
      ad_personalization: consent.marketing ? "granted" : "denied",
      analytics_storage: consent.analytics ? "granted" : "denied",
      functionality_storage: consent.preferences ? "granted" : "denied",
      personalization_storage: consent.preferences ? "granted" : "denied",
      security_storage: "granted",
    };
  }

  function pushConsentUpdate(consent, eventName) {
    ensureDataLayer();

    const googleConsent = mapToGoogleConsent(consent);

    window.gtag("consent", "update", googleConsent);

    const payload = {
      cookie_consent_necessary: consent.necessary ? "granted" : "denied",
      cookie_consent_preferences: consent.preferences ? "granted" : "denied",
      cookie_consent_analytics: consent.analytics ? "granted" : "denied",
      cookie_consent_marketing: consent.marketing ? "granted" : "denied",
      cookie_consent_version: consent.consentVersion,
      cookie_consent_updated_at: consent.updatedAt,
    };

    if (eventName) {
      window.dataLayer.push({
        event: eventName,
        ...payload,
      });
    }

    window.dataLayer.push({
      event: GTM_EVENT_UPDATE,
      ...payload,
    });
  }

  function getEffectiveConsent() {
    const saved = readConsent();

    if (!saved || saved.consentVersion !== CONSENT_VERSION) {
      return null;
    }

    return {
      ...defaultConsent,
      ...saved,
    };
  }

  function saveConsent(partial, eventName) {
    const nextConsent = {
      ...defaultConsent,
      ...partial,
      necessary: true,
      consented: true,
      consentVersion: CONSENT_VERSION,
      updatedAt: new Date().toISOString(),
    };

    writeConsent(nextConsent);
    pushConsentUpdate(nextConsent, eventName);
    hideBanner();
    closeModal();

    document.documentElement.setAttribute("data-cookie-consent", "saved");
    return nextConsent;
  }

  function acceptAll() {
    saveConsent(
      {
        preferences: true,
        analytics: true,
        marketing: true,
      },
      GTM_EVENT_ACCEPT_ALL,
    );
  }

  function rejectAll() {
    saveConsent(
      {
        preferences: false,
        analytics: false,
        marketing: false,
      },
      GTM_EVENT_REJECT_ALL,
    );
  }

  function saveFromModal() {
    const preferences =
      document.getElementById("cc-toggle-preferences")?.checked || false;
    const analytics =
      document.getElementById("cc-toggle-analytics")?.checked || false;
    const marketing =
      document.getElementById("cc-toggle-marketing")?.checked || false;

    saveConsent(
      {
        preferences,
        analytics,
        marketing,
      },
      GTM_EVENT_PREFERENCES_SAVED,
    );
  }

  function buildMarkup() {
    const wrapper = document.createElement("div");
    wrapper.id = "cc-root";
    wrapper.innerHTML = `
      <div id="cc-banner" class="cc-banner cc-hidden" role="dialog" aria-live="polite" aria-label="Cookie consent banner">
        <div class="cc-banner__inner">
          <div>
            <h2 class="cc-banner__title">Your privacy choices</h2>
            <p class="cc-banner__text">
              We use strictly necessary cookies to make this site work. With your permission, we also use
              preference, analytics, and marketing technologies to improve the site, measure performance,
              and support advertising.
            </p>
            <p class="cc-banner__meta">
              <a href="/cookie-policy" class="cc-link" target="_self" rel="noopener">
                Cookie Policy
              </a>
            </p>
          </div>

          <div class="cc-banner__actions">
            <button type="button" class="cc-btn cc-btn--primary" data-cc-action="accept-all">
              Ok
            </button>

            <button type="button" class="cc-btn cc-btn--ghost" data-cc-action="open-preferences">
              Customize
            </button>
          </div>
        </div>
      </div>

      <div id="cc-modal-backdrop" class="cc-modal-backdrop cc-hidden" aria-hidden="true">
        <div class="cc-modal" role="dialog" aria-modal="true" aria-labelledby="cc-modal-title">
          <div class="cc-modal__header">
            <h2 id="cc-modal-title" class="cc-modal__title">Cookie settings</h2>
            <button type="button" class="cc-modal__close" aria-label="Close cookie settings" data-cc-action="close-modal">
                <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <div class="cc-modal__body">
            <p class="cc-modal__intro">
              You can choose which categories of cookies and similar technologies we use. Strictly necessary
              technologies are always on because the site cannot function properly without them.
            </p>

            <p class="cc-modal__legal">
              For more details, please review our
              <a href="/cookie-policy.php" class="cc-link" target="_self" rel="noopener">
                Cookie Policy
              </a>.
            </p>

            <section class="cc-category" aria-labelledby="cc-cat-necessary">
              <div class="cc-category__row">
                <div>
                  <h3 id="cc-cat-necessary" class="cc-category__title">Strictly necessary</h3>
                  <p class="cc-category__desc">
                    Required for core site functions such as security, network management, and saving your privacy choices.
                  </p>
                </div>
                <div class="cc-category__status cc-status--always">Always active</div>
              </div>
            </section>

            <section class="cc-category" aria-labelledby="cc-cat-preferences">
              <div class="cc-category__row">
                <div>
                  <h3 id="cc-cat-preferences" class="cc-category__title">Preferences</h3>
                  <p class="cc-category__desc">
                    Remembers optional settings such as interface preferences and enhanced functionality choices.
                  </p>
                </div>
                <label class="cc-switch" aria-label="Toggle preference cookies">
                  <input id="cc-toggle-preferences" type="checkbox" />
                  <span class="cc-switch__slider"></span>
                </label>
              </div>
            </section>

            <section class="cc-category" aria-labelledby="cc-cat-analytics">
              <div class="cc-category__row">
                <div>
                  <h3 id="cc-cat-analytics" class="cc-category__title">Analytics</h3>
                  <p class="cc-category__desc">
                    Helps us understand site traffic and page performance so we can improve content and user experience.
                  </p>
                </div>
                <label class="cc-switch" aria-label="Toggle analytics cookies">
                  <input id="cc-toggle-analytics" type="checkbox" />
                  <span class="cc-switch__slider"></span>
                </label>
              </div>
            </section>

            <section class="cc-category" aria-labelledby="cc-cat-marketing">
              <div class="cc-category__row">
                <div>
                  <h3 id="cc-cat-marketing" class="cc-category__title">Marketing</h3>
                  <p class="cc-category__desc">
                    Enables advertising, campaign measurement, retargeting, and related marketing technologies.
                  </p>
                </div>
                <label class="cc-switch" aria-label="Toggle marketing cookies">
                  <input id="cc-toggle-marketing" type="checkbox" />
                  <span class="cc-switch__slider"></span>
                </label>
              </div>
            </section>
          </div>

          <div class="cc-modal__footer">
            <button type="button" class="cc-btn cc-btn--secondary" data-cc-action="reject-all">
              Reject all
            </button>
            <button type="button" class="cc-btn cc-btn--ghost" data-cc-action="accept-all">
              Accept all
            </button>
            <button type="button" class="cc-btn cc-btn--primary" data-cc-action="save-preferences">
              Save choices
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.appendChild(wrapper);
  }

  function showBanner() {
    const banner = document.getElementById("cc-banner");
    if (banner) banner.classList.remove("cc-hidden");
  }

  function hideBanner() {
    const banner = document.getElementById("cc-banner");
    if (banner) banner.classList.add("cc-hidden");
  }

  function openModal() {
    const backdrop = document.getElementById("cc-modal-backdrop");
    if (!backdrop) return;
    backdrop.classList.remove("cc-hidden");
    backdrop.setAttribute("aria-hidden", "false");
    document.body.classList.add("cc-lock-scroll");
  }

  function closeModal() {
    const backdrop = document.getElementById("cc-modal-backdrop");
    if (!backdrop) return;
    backdrop.classList.add("cc-hidden");
    backdrop.setAttribute("aria-hidden", "true");
    document.body.classList.remove("cc-lock-scroll");
  }

  function syncModalFromConsent(consent) {
    const current = consent || getEffectiveConsent() || defaultConsent;

    const preferences = document.getElementById("cc-toggle-preferences");
    const analytics = document.getElementById("cc-toggle-analytics");
    const marketing = document.getElementById("cc-toggle-marketing");

    if (preferences) preferences.checked = !!current.preferences;
    if (analytics) analytics.checked = !!current.analytics;
    if (marketing) marketing.checked = !!current.marketing;
  }

  function bindEvents() {
    document.addEventListener("click", function (event) {
      const actionTarget = event.target.closest("[data-cc-action]");
      const settingsLink = event.target.closest(
        "[data-cc-open-preferences], #open-cookie-settings",
      );

      if (settingsLink) {
        event.preventDefault();
        syncModalFromConsent();
        openModal();
        return;
      }

      if (!actionTarget) return;

      const action = actionTarget.getAttribute("data-cc-action");

      if (action === "accept-all") {
        acceptAll();
        return;
      }

      if (action === "reject-all") {
        rejectAll();
        return;
      }

      if (action === "open-preferences") {
        syncModalFromConsent();
        openModal();
        return;
      }

      if (action === "close-modal") {
        closeModal();
        return;
      }

      if (action === "save-preferences") {
        saveFromModal();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeModal();
      }
    });

    const backdrop = document.getElementById("cc-modal-backdrop");
    if (backdrop) {
      backdrop.addEventListener("click", function (event) {
        if (event.target === backdrop) {
          closeModal();
        }
      });
    }
  }

  function init() {
    ensureDataLayer();
    buildMarkup();
    bindEvents();

    const existingConsent = getEffectiveConsent();

    if (existingConsent) {
      syncModalFromConsent(existingConsent);
      hideBanner();
      document.documentElement.setAttribute("data-cookie-consent", "saved");
    } else {
      document.documentElement.setAttribute("data-cookie-consent", "pending");
      syncModalFromConsent(defaultConsent);
      showBanner();
    }

    window.CookieConsent = {
      openPreferences: function () {
        syncModalFromConsent();
        openModal();
      },
      getConsent: function () {
        return getEffectiveConsent();
      },
      acceptAll,
      rejectAll,
    };
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
