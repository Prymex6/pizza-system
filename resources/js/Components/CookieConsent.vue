<template>
  <div class="cookie-wrapper">
    <button
      v-if="!isOpen && !isModalOpen && !isPolicyModalOpen"
      class="cookie-reopen-btn"
      @click="openModal"
      aria-label="Ustawienia plików cookie"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="24"
        height="24"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5Z" />
        <path d="M8.5 8.5v.01" />
        <path d="M16 15.5v.01" />
        <path d="M12 12v.01" />
        <path d="M11 17v.01" />
        <path d="M7 14v.01" />
      </svg>
    </button>

    <transition name="slide-up">
      <div
        v-if="isOpen && !isModalOpen && !isPolicyModalOpen"
        class="cookie-banner"
      >
        <div class="cookie-content">
          <h3>Szanujemy Twoją prywatność</h3>
          <p>
            Ta strona używa plików cookie. Niektóre są niezbędne, inne pomagają
            nam analizować ruch i targetować reklamy. Kliknij
            <strong @click="openModal">"Dostosuj"</strong> lub
            <strong @click="acceptAll">"Akceptuj wszystko"</strong> by
            kontynuować.
          </p>
        </div>
        <div class="cookie-actions">
          <div class="cookie-actions-row">
            <button class="btn btn-outline" @click="openModal">Dostosuj</button>
            <button class="btn btn-secondary" @click="denyAll">Odrzuć</button>
          </div>
          <button class="btn btn-primary btn-full" @click="acceptAll">
            Akceptuj wszystko
          </button>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div
        v-if="isModalOpen"
        class="cookie-modal-backdrop"
        @click.self="isModalOpen = false"
      >
        <div class="cookie-modal" role="dialog" aria-modal="true">
          <div class="modal-header">
            <h2 class="modal-title">Dostosuj preferencje dotyczące zgody</h2>
            <button class="close-btn" @click="isModalOpen = false">
              &times;
            </button>
          </div>

          <div class="modal-body-wrapper">
            <div class="modal-description">
              <p>
                Używamy plików cookie, aby pomóc użytkownikom w sprawnej
                nawigacji i wykonywaniu określonych funkcji. Szczegółowe
                informacje na temat wszystkich plików cookie odpowiadających
                poszczególnym kategoriom zgody znajdują się poniżej.
              </p>
              <p>
                Pliki cookie sklasyfikowane jako
                <strong :style="{ color: 'var(--primary-color)' }"
                  >„niezbędne"</strong
                >
                są przechowywane w przeglądarce użytkownika, ponieważ są
                niezbędne do włączenia podstawowych funkcji witryny.
              </p>
            </div>

            <div class="dma-link">
              Pełna treść:
              <a href="#" @click.prevent="openPolicyModal"
                >Polityka Prywatności i Plików Cookie</a
              >
            </div>

            <div class="horizontal-separator"></div>

            <div class="categories-list">
              <div
                v-for="(catData, catKey) in categories"
                :key="catKey"
                class="category-item"
                :class="{ 'is-open': activeAccordion === catKey }"
              >
                <div class="category-header" @click="toggleAccordion(catKey)">
                  <div class="accordion-header-main">
                    <span
                      class="chevron"
                      :class="{ rotated: activeAccordion === catKey }"
                      >›</span
                    >
                    <button class="accordion-btn">
                      <span class="category-name">
                        {{ catData.label }}
                        <span
                          v-if="
                            catData.detectedItems.length > 0 &&
                            catKey !== 'necessary'
                          "
                          class="badge"
                          >({{ catData.detectedItems.length }})</span
                        >
                      </span>
                    </button>

                    <div class="category-controls">
                      <span v-if="catData.required" class="always-active"
                        >Zawsze aktywne</span
                      >
                      <label
                        v-else
                        class="switch"
                        :class="{ disabled: catData.required }"
                        @click.stop
                      >
                        <input
                          type="checkbox"
                          v-model="catData.enabled"
                          :disabled="catData.required"
                        />
                        <span class="slider round"></span>
                      </label>
                    </div>
                  </div>
                </div>

                <transition name="accordion-slide">
                  <div
                    v-show="activeAccordion === catKey"
                    class="category-body"
                  >
                    <p class="category-desc-full">{{ catData.description }}</p>

                    <div class="audit-table">
                      <div
                        v-if="catData.detectedItems.length === 0"
                        class="empty-text"
                      >
                        Brak plików cookie do wyświetlenia w tej kategorii.
                      </div>
                      <ul v-else class="cookie-table">
                        <li
                          v-for="(item, index) in catData.detectedItems"
                          :key="index"
                        >
                          <div class="table-row type-row">
                            <span class="table-label">Plik cookie:</span>
                            <span class="table-value bold">{{
                              item.name
                            }}</span>
                          </div>
                          <div class="table-row source-row">
                            <span class="table-label">Źródło/Czas:</span>
                            <span class="table-value break-word">{{
                              item.src || item.duration || "Sesja"
                            }}</span>
                          </div>
                          <div class="table-row description-row">
                            <span class="table-label">Opis:</span>
                            <span class="table-value">{{
                              item.description ||
                              "Automatycznie wykryty skrypt/ciasteczko."
                            }}</span>
                          </div>
                          <div class="status-row">
                            <span
                              class="status-badge"
                              :class="
                                catData.enabled
                                  ? 'status-allowed'
                                  : 'status-blocked'
                              "
                            >
                              {{ catData.enabled ? "Aktywny" : "Zablokowany" }}
                            </span>
                          </div>
                        </li>
                      </ul>
                    </div>
                  </div>
                </transition>
              </div>
            </div>
          </div>

          <div class="cky-footer-wrapper">
            <span class="cky-footer-shadow"></span>

            <div class="modal-footer">
              <button class="cky-btn cky-btn-reject" @click="denyAll">
                Odrzuć wszystkie
              </button>
              <button
                class="cky-btn cky-btn-preferences"
                @click="savePreferences"
              >
                Zapisz preferencje
              </button>
              <button class="cky-btn cky-btn-accept" @click="acceptAll">
                Akceptuj wszystko
              </button>
            </div>

            <div class="powered-by">
              <span class="powered-text">Zgodność RODO</span>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div
        v-if="isPolicyModalOpen"
        class="cookie-modal-backdrop"
        @click.self="isPolicyModalOpen = false"
      >
        <div class="cookie-modal policy-modal" role="dialog" aria-modal="true">
          <div class="modal-header">
            <h2 class="modal-title">Polityka Prywatności i Plików Cookie</h2>
            <button class="close-btn" @click="isPolicyModalOpen = false">
              &times;
            </button>
          </div>
          <div class="modal-body-wrapper">
            <PolicyContent :restaurant-name="restaurantName" :restaurant-email="restaurantEmail" />
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
import PolicyContent from "./PolicyContent.vue";

const props = defineProps({
  gaId: { type: String, default: '' },
  pixelId: { type: String, default: '' },
  restaurantName: { type: String, default: 'Restauracja' },
  restaurantEmail: { type: String, default: '' },
});

const STORAGE_KEY = "pizza_cookie_consent_v1";
const isOpen = ref(false);
const isModalOpen = ref(false);
const isPolicyModalOpen = ref(false);
const activeAccordion = ref("necessary");

const previousConsents = ref({});

const gtagBaseSrc = "https://www.googletagmanager.com/gtag";

const COOKIES_TO_CLEAR = {
  analytics: [
    "_ga",
    "_gid",
    "_gat",
    "_gat_.*",
    "_ga_.*",
    "_hjSession",
    "_hjSessionUser",
  ],
  marketing: ["_fbp", "_fbc", "IDE", "li_dvs"],
  functional: ["intercom-id", "zd-client-id"],
};

const PATTERNS = {
  analytics: [
    /google-analytics/,
    /googletagmanager/,
    /gtag/,
    /hotjar/,
    /matomo/,
    /segment\.com/,
  ],
  marketing: [
    /facebook\.net/,
    /connect\.facebook/,
    /doubleclick/,
    /googleadservices/,
    /ads\.twitter/,
    /linkedin/,
    /tiktok/,
  ],
  functional: [/intercom/, /zendesk/, /chat/],
};

const categories = reactive({
  necessary: {
    label: "Niezbędne (Wymagane)",
    description:
      "Niezbędne pliki cookie są kluczowe dla podstawowych funkcji witryny. Bez nich strona nie może działać poprawnie (np. utrzymanie sesji, koszyk, bezpieczeństwo, zapamiętanie preferencji zgody). Te pliki cookie nie przechowują danych osobowych i są zawsze aktywne.",
    required: true,
    enabled: true,
    detectedItems: [
      {
        name: "pizza_cookie_consent_v1",
        duration: "1 rok",
        description:
          "Utrzymuje Twoje preferencje dotyczące zgód na ciasteczka.",
      },
    ],
  },
  functional: {
    label: "Funkcjonalne",
    description:
      "Funkcjonalne pliki cookie umożliwiają dodatkowe funkcje, takie jak chat, usługi wideo, personalizacja interfejsu lub integracje map. Wyłączenie może ograniczyć użyteczność niektórych elementów strony.",
    required: false,
    enabled: false,
    detectedItems: [],
  },
  analytics: {
    label: "Analityka i Statystyka",
    description:
      "Analityczne pliki cookie (np. Google Analytics) zbierają informacje o sposobie korzystania z witryny, liczbie odwiedzających, współczynniku odrzuceń i źródłach ruchu. Pomaga nam to poprawiać wydajność i zawartość strony.",
    required: false,
    enabled: false,
    detectedItems: [],
  },
  marketing: {
    label: "Reklama i Profilowanie",
    description:
      "Marketingowe pliki cookie śledzą aktywność użytkowników w celu wyświetlania spersonalizowanych reklam na stronach trzecich (np. Facebook Pixel, Google Ads). Służą do tworzenia Twojego profilu i optymalizacji kampanii reklamowych.",
    required: false,
    enabled: false,
    detectedItems: [],
  },
});

const blockedQueue = [];

const toggleAccordion = (key) => {
  activeAccordion.value = activeAccordion.value === key ? null : key;
};

const deleteCookie = (name) => {
  if (name.includes(".*")) {
    const regex = new RegExp("^" + name.replace(".*", ".*") + "$");
    const allCookies = document.cookie.split(";");
    allCookies.forEach((cookie) => {
      const cookieName = cookie.trim().split("=")[0];
      if (regex.test(cookieName)) {
        document.cookie = cookieName + "=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/";
        document.cookie = cookieName + "=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/; domain=" + window.location.hostname;
      }
    });
    return;
  }

  document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/; domain=" + window.location.hostname;
  document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/";

  const parts = window.location.hostname.split(".");
  if (parts.length > 1) {
    const domain = parts.slice(parts.length - 2).join(".");
    document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/; domain=." + domain;
  }
};

const clearCookies = (categoryKey) => {
  const cookies = COOKIES_TO_CLEAR[categoryKey];
  if (cookies && cookies.length > 0) {
    cookies.forEach((cookieName) => deleteCookie(cookieName));
  }
  if (categoryKey === "analytics") {
    window.dataLayer = [];
  }
};

const activateAnalytics = () => {
  const gaId = props.gaId?.trim();
  const isEnabled = categories.analytics.enabled;
  const gtagFullSrc = gaId ? `${gtagBaseSrc}/js?id=${gaId}` : null;

  if (window.gtag) {
    window.gtag("consent", "update", {
      analytics_storage: isEnabled ? "granted" : "denied",
      ad_storage: categories.marketing.enabled ? "granted" : "denied",
    });
  }

  if (!isEnabled || !gaId) return;

  const existingScript = document.querySelector(`script[src*="${gaId}"]`);
  if (!existingScript) {
    const script = document.createElement("script");
    script.async = true;
    script.src = gtagFullSrc;
    document.head.appendChild(script);

    script.onload = () => {
      window.dataLayer = window.dataLayer || [];
      function gtag() { dataLayer.push(arguments); }
      gtag("js", new Date());
      gtag("config", gaId, {
        anonymize_ip: true,
        consent_mode: {
          analytics_storage: "granted",
          ad_storage: categories.marketing.enabled ? "granted" : "denied",
        },
      });
    };
  }

  const alreadyDetected = categories.analytics.detectedItems.some(
    (item) => item.src && item.src.includes(gtagBaseSrc)
  );
  if (!alreadyDetected && gtagFullSrc) {
    categories.analytics.detectedItems.push({
      name: "gtag.js",
      duration: "2 lata",
      description: "Główny skrypt zarządzający analityką Google Analytics.",
      src: gtagFullSrc,
    });
  }
};

const activateMarketing = () => {
  const pixelId = props.pixelId?.trim();
  const isEnabled = categories.marketing.enabled;

  if (!isEnabled || !pixelId || !/^\d{10,20}$/.test(pixelId)) return;

  if (!document.getElementById('fb-pixel')) {
    const s = document.createElement('script');
    s.id = 'fb-pixel';
    s.textContent = `!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','${pixelId}');fbq('track','PageView');`;
    document.head.appendChild(s);
  }

  const alreadyDetected = categories.marketing.detectedItems.some(
    (item) => item.name === 'Facebook Pixel'
  );
  if (!alreadyDetected) {
    categories.marketing.detectedItems.push({
      name: 'Facebook Pixel',
      duration: '3 miesiące',
      description: 'Śledzi konwersje z reklam Facebook i pomaga w targetowaniu reklam.',
    });
  }
};

const setupAutoBlocker = () => {
  const classifyScript = (src) => {
    if (!src) return null;
    const lowerSrc = src.toLowerCase();
    for (const [catKey, regexArray] of Object.entries(PATTERNS)) {
      if (regexArray.some((rx) => rx.test(lowerSrc))) {
        return catKey;
      }
    }
    return null;
  };

  document.querySelectorAll("script").forEach((script) => {
    const src = script.src;
    const category = classifyScript(src);
    const gaId = props.gaId?.trim();

    if (category && categories[category]) {
      const isStaticAnalytics = src && gaId && src.includes(gaId);

      if (!categories[category].enabled && !isStaticAnalytics) {
        if (!script.getAttribute("data-allow")) {
          script.type = "text/plain";
          blockedQueue.push({ element: script, src: src, category: category });
        }
      }
      if (!categories[category].detectedItems.some((i) => i.src === src)) {
        categories[category].detectedItems.push({
          name: src.split("/").pop().split("?")[0] || "Zewnętrzny Skrypt",
          duration: "Sesja/Brak danych",
          description: categories[category].description.substring(0, 100) + "...",
          src: src,
          originalElement: script,
        });
      }
    }
  });

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.tagName === "SCRIPT" && node.src) {
          const category = classifyScript(node.src);
          if (category) {
            if (!categories[category].enabled) {
              node.type = "text/plain";
              blockedQueue.push({ element: node, src: node.src, category: category });
              if (!categories[category].detectedItems.some((i) => i.src === node.src)) {
                categories[category].detectedItems.push({
                  name: node.src.split("/").pop().split("?")[0],
                  duration: "Sesja/Brak danych",
                  description: categories[category].description.substring(0, 100) + "...",
                  src: node.src,
                });
              }
              node.parentElement && node.parentElement.removeChild(node);
            }
          }
        }
      });
    });
  });

  observer.observe(document.documentElement, { childList: true, subtree: true });
};

const saveToStorage = () => {
  const consents = {};
  Object.keys(categories).forEach((k) => (consents[k] = categories[k].enabled));
  localStorage.setItem(STORAGE_KEY, JSON.stringify({ consents, timestamp: Date.now() }));
};

const blockAnalyticsImmediately = () => {
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({
    consent: {
      ad_storage: "denied",
      analytics_storage: "denied",
      ad_user_data: "denied",
      ad_personalization: "denied",
    },
  });
  const gaId = props.gaId?.trim();
  if (gaId) {
    window[`ga-disable-${gaId}`] = true;
  }
};

const applySettings = () => {
  activateAnalytics();
  activateMarketing();

  blockedQueue.forEach((item, index) => {
    const isEnabled = categories[item.category].enabled;
    if (isEnabled && item.element && item.element.type === "text/plain") {
      const script = document.createElement("script");
      script.src = item.src;
      script.type = "text/javascript";
      script.async = true;
      document.head.appendChild(script);
      blockedQueue[index].element.type = "text/javascript";
    }
  });

  isOpen.value = false;
  isModalOpen.value = false;
};

const acceptAll = () => {
  Object.keys(categories).forEach((k) => {
    if (!categories[k].required) categories[k].enabled = true;
  });
  saveToStorage();
  applySettings();
};

const denyAll = () => {
  Object.keys(categories).forEach((k) => {
    if (!categories[k].required) categories[k].enabled = false;
  });
  saveToStorage();
  Object.keys(categories).forEach((catKey) => {
    if (!categories[catKey].required) clearCookies(catKey);
  });
  blockAnalyticsImmediately();
  clearCookies("analytics");
  isOpen.value = false;
  isModalOpen.value = false;
  isPolicyModalOpen.value = false;
  setTimeout(() => { window.location.reload(); }, 300);
};

const savePreferences = () => {
  let shouldReload = false;
  const keyCategories = ["analytics", "marketing", "functional"];

  keyCategories.forEach((catKey) => {
    if (previousConsents.value[catKey] === true && categories[catKey].enabled === false) {
      shouldReload = true;
    }
    if (catKey === "analytics" && categories[catKey].enabled === false) {
      shouldReload = true;
    }
  });

  saveToStorage();
  Object.keys(categories).forEach((catKey) => {
    if (!categories[catKey].required && !categories[catKey].enabled) {
      clearCookies(catKey);
    }
  });

  if (shouldReload) {
    blockAnalyticsImmediately();
    clearCookies("analytics");
    setTimeout(() => { window.location.reload(); }, 300);
    return;
  }

  applySettings();
};

const openModal = () => {
  Object.keys(categories).forEach((k) => {
    previousConsents.value[k] = categories[k].enabled;
  });
  isModalOpen.value = true;
  isPolicyModalOpen.value = false;
};

const openPolicyModal = () => {
  isPolicyModalOpen.value = true;
  isModalOpen.value = false;
  isOpen.value = false;
};

onMounted(() => {
  if (window.gtag) {
    window.gtag("consent", "default", {
      analytics_storage: "denied",
      ad_storage: "denied",
      wait_for_update: 500,
    });
  }

  setupAutoBlocker();

  const saved = localStorage.getItem(STORAGE_KEY);
  if (saved) {
    const parsed = JSON.parse(saved);
    Object.keys(categories).forEach((k) => {
      if (parsed.consents[k] !== undefined && !categories[k].required) {
        categories[k].enabled = parsed.consents[k];
      }
    });
    applySettings();
    isOpen.value = false;
  } else {
    isOpen.value = true;
  }
});
</script>

<style scoped>
.cookie-wrapper {
  --primary-color: #dc2626;
  --primary-color-darker: #b91c1c;
  --primary-color-light: rgba(220, 38, 38, 0.1);
  --success-color: #28a745;
  --danger-color: #dc3545;
  --secondary-color: #6c757d;
  --text-color: #343a40;
  --text-color-white: #ffffff;
  --text-color-muted: #6c757d;
  --bg-white: #ffffff;
  --bg-light: #f8f9fa;
  --bg-medium: #e9ecef;
  --bg-dark: #212529;
  --border-color: #dee2e6;
  --border-radius: 8px;
  --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  --box-shadow-dark: 0 10px 30px rgba(0, 0, 0, 0.15);
  --transition-speed: 0.3s;
  --modal-bg: #ffffff;
  --audit-bg: #f8f9fa;
  color: #343a40;
  line-height: 1.5;
}

.accordion-slide-enter-active,
.accordion-slide-leave-active {
  transition: max-height 0.4s ease-in-out, padding 0.4s ease-in-out;
  overflow: hidden;
}
.accordion-slide-enter-from,
.accordion-slide-leave-to {
  max-height: 0;
  padding-top: 0;
  padding-bottom: 0;
  opacity: 0;
}
.accordion-slide-enter-to,
.accordion-slide-leave-from {
  max-height: 1000px;
  opacity: 1;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(20px);
  opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.cookie-banner {
  position: fixed;
  bottom: 20px;
  left: 20px;
  width: calc(100% - 40px);
  max-width: 420px;
  background: var(--bg-white);
  padding: 24px;
  border-radius: var(--border-radius);
  box-shadow: var(--box-shadow-dark);
  z-index: 9998;
  border-left: 5px solid var(--primary-color);
}
.cookie-content h3 {
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 10px;
  color: var(--text-color);
}
.cookie-content p {
  font-size: 14px;
  color: var(--text-color-muted);
  margin-bottom: 20px;
}
.cookie-content p strong {
  color: var(--primary-color);
  cursor: pointer;
  text-decoration: underline;
}

.cookie-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.cookie-actions-row {
  display: flex;
  gap: 8px;
}
.cookie-actions-row .btn {
  flex: 1;
}
.btn {
  padding: 8px 12px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: 0.2s;
  border: 1px solid transparent;
}
.btn-full {
  width: 100%;
}
.btn-primary {
  background: var(--primary-color);
  color: var(--text-color-white);
  border-color: var(--primary-color);
}
.btn-primary:hover {
  background: var(--primary-color-darker);
  border-color: var(--primary-color-darker);
}
.btn-secondary {
  background: var(--secondary-color);
  color: var(--text-color-white);
  border-color: var(--secondary-color);
}
.btn-secondary:hover {
  background: var(--bg-dark);
  border-color: var(--bg-dark);
}
.btn-outline {
  background: var(--bg-white);
  color: var(--text-color);
  border-color: var(--border-color);
}
.btn-outline:hover {
  background: var(--bg-light);
  border-color: var(--text-color-muted);
}

.cookie-modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(3px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cookie-modal {
  background: var(--modal-bg);
  width: 95%;
  max-width: 750px;
  max-height: 90vh;
  border-radius: var(--border-radius);
  box-shadow: var(--box-shadow);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid var(--border-color);
}

.modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-light);
}
.modal-title {
  font-size: 16px;
  font-weight: 700;
  margin: 0;
  color: var(--text-color);
}
.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: var(--text-color-muted);
  line-height: 1;
}

.modal-body-wrapper {
  padding: 24px;
  overflow-y: auto;
  color: var(--text-color);
}

.modal-description p {
  font-size: 14px;
  margin-bottom: 10px;
}
.dma-link {
  font-size: 14px;
  margin-top: 15px;
}
.dma-link a {
  color: var(--primary-color);
  text-decoration: none;
  font-weight: 600;
  cursor: pointer;
}
.horizontal-separator {
  height: 1px;
  background: var(--border-color);
  margin: 20px 0;
}

.category-item {
  border: 1px solid var(--border-color);
  border-radius: 6px;
  margin-bottom: 10px;
  overflow: hidden;
  transition: var(--transition-speed);
}
.category-item.is-open {
  border-color: var(--primary-color);
  box-shadow: 0 0 5px var(--primary-color-light);
}

.category-header {
  padding: 16px 24px;
  cursor: pointer;
}
.accordion-header-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.accordion-btn {
  background: none;
  border: none;
  padding: 0;
  display: flex;
  align-items: center;
  font-weight: 600;
  font-size: 15px;
  color: var(--text-color);
  flex-grow: 1;
  text-align: left;
  cursor: pointer;
}

.chevron {
  font-size: 18px;
  color: var(--primary-color);
  transition: var(--transition-speed) transform;
  margin-right: 15px;
  line-height: 1;
}
.chevron.rotated {
  transform: rotate(90deg);
}

.badge {
  background: var(--bg-medium);
  color: var(--text-color-muted);
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 4px;
  margin-left: 5px;
  font-weight: 400;
}

.category-controls {
  display: flex;
  align-items: center;
}
.always-active {
  font-size: 12px;
  font-weight: 700;
  color: var(--success-color);
  text-transform: uppercase;
  margin-left: 15px;
  flex-shrink: 0;
}

.category-body {
  border-top: 1px solid var(--border-color);
  background: var(--audit-bg);
  padding: 15px 24px;
}
.category-desc-full {
  font-size: 13px;
  color: var(--text-color);
  margin-bottom: 15px;
  line-height: 1.6;
}

.audit-table {
  background: var(--bg-white);
  border: 1px solid var(--border-color);
  border-radius: 4px;
  overflow: hidden;
}
.empty-text {
  padding: 15px;
  text-align: center;
  font-size: 14px;
  color: var(--text-color-muted);
}

.cookie-table {
  list-style: none;
  padding: 0;
  margin: 0;
}
.cookie-table li {
  padding: 12px 15px;
  border-bottom: 1px solid var(--border-color);
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 5px 15px;
}
.cookie-table li:last-child {
  border-bottom: none;
}

.table-row {
  display: flex;
  font-size: 13px;
}
.table-label {
  font-weight: 600;
  color: var(--text-color-muted);
  flex-shrink: 0;
  text-align: right;
  padding-right: 5px;
}
.table-value {
  color: var(--text-color);
  word-break: break-word;
  flex-grow: 1;
}
.table-value.bold {
  font-weight: 700;
}
.description-row {
  grid-column: 1 / -1;
  margin-top: 5px;
}
.status-row {
  grid-column: 1 / -1;
  margin-top: 10px;
}

.status-badge {
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}
.status-allowed {
  background: rgba(40, 167, 69, 0.1);
  color: var(--success-color);
}
.status-blocked {
  background: rgba(220, 53, 69, 0.1);
  color: var(--danger-color);
}

.switch {
  position: relative;
  display: inline-block;
  width: 40px;
  height: 22px;
  margin-left: 15px;
}
.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}
.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: 0.4s;
  border-radius: 34px;
}
.slider:before {
  position: absolute;
  content: "";
  height: 16px;
  width: 16px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.4s;
  border-radius: 50%;
}
input:checked + .slider {
  background-color: var(--primary-color);
}
input:checked + .slider:before {
  transform: translateX(18px);
}

.cky-footer-wrapper {
  position: relative;
}
.cky-footer-shadow {
  content: "";
  position: absolute;
  top: -20px;
  left: 0;
  right: 0;
  height: 20px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, var(--bg-white) 100%);
  z-index: 10;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  background: var(--bg-white);
  position: relative;
  z-index: 11;
}

.cky-btn {
  padding: 10px 18px;
  border-radius: 4px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: var(--transition-speed);
  border: 1px solid transparent;
}
.cky-btn-accept {
  background: var(--primary-color);
  color: var(--text-color-white);
  border-color: var(--primary-color);
}
.cky-btn-accept:hover {
  background: var(--primary-color-darker);
  border-color: var(--primary-color-darker);
}
.cky-btn-preferences {
  background: var(--secondary-color);
  color: var(--text-color-white);
  border-color: var(--secondary-color);
}
.cky-btn-preferences:hover {
  background: var(--bg-dark);
  border-color: var(--bg-dark);
}
.cky-btn-reject {
  background: var(--bg-light);
  color: var(--text-color);
  border-color: var(--border-color);
}
.cky-btn-reject:hover {
  background: var(--bg-medium);
  border-color: var(--text-color-muted);
}

.powered-by {
  font-size: 12px;
  text-align: right;
  padding: 8px 24px;
  color: var(--text-color-muted);
  background-color: var(--bg-light);
  font-weight: 400;
  border-top: 1px solid var(--border-color);
}

.cookie-reopen-btn {
  position: fixed;
  bottom: 20px;
  left: 20px;
  width: 45px;
  height: 45px;
  border-radius: 50%;
  background: var(--bg-white);
  border: 1px solid var(--border-color);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 9990;
  display: flex;
  justify-content: center;
  align-items: center;
  color: var(--primary-color);
  cursor: pointer;
}

@media (max-width: 600px) {
  .modal-footer {
    flex-direction: column-reverse;
  }
  .modal-footer .cky-btn {
    width: 100%;
  }
  .cookie-banner {
    left: 0;
    bottom: 0;
    width: 100%;
    max-width: none;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
  }
  .cookie-table li {
    grid-template-columns: 1fr;
    gap: 5px 0;
  }
  .table-label {
    width: auto;
    text-align: left;
    padding-right: 0;
    margin-bottom: 2px;
  }
  .type-row,
  .source-row {
    grid-column: 1 / -1;
  }
}
</style>
