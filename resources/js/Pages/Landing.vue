<template>
    <Head :title="`${appName} – System zarządzania restauracją w chmurze`">
        <meta name="description" content="Roveto to nowoczesny system SaaS do zarządzania restauracją. Zamówienia online, kasa, kuchnia, dostawa – wszystko w jednym miejscu. Wypróbuj za darmo." />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="https://roveto.pl/" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://roveto.pl/" />
        <meta property="og:title" content="Roveto – System zarządzania restauracją w chmurze" />
        <meta property="og:description" content="Nowoczesny system SaaS do zarządzania restauracją. Zamówienia online, kasa, kuchnia, dostawa – wszystko w jednym miejscu." />
        <meta property="og:image" content="https://roveto.pl/images/og-image.jpg" />
        <meta property="og:locale" content="pl_PL" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Roveto – System zarządzania restauracją w chmurze" />
        <meta name="twitter:description" content="Nowoczesny system SaaS do zarządzania restauracją. Zamówienia online, kasa, kuchnia, dostawa – wszystko w jednym miejscu." />
        <meta name="twitter:image" content="https://roveto.pl/images/og-image.jpg" />
        <component :is="'script'" type="application/ld+json" v-html="jsonLd" />
    </Head>

    <div class="min-h-screen bg-white font-sans">

        <!-- Nav -->
        <nav class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <img src="/images/logo.png" alt="Logo" class="h-8 w-auto" />
                    <span class="text-xl sm:text-2xl font-extrabold text-blue-600">{{ appName }}</span>
                </div>
                <div class="hidden md:flex items-center gap-8 text-sm text-gray-600">
                    <a href="#funkcje" @click.prevent="scrollTo('funkcje')" class="hover:text-blue-600 transition-colors cursor-pointer">Funkcje</a>
                    <a href="#jak-dziala" @click.prevent="scrollTo('jak-dziala')" class="hover:text-blue-600 transition-colors cursor-pointer">Jak działa</a>
                    <a href="#cennik" @click.prevent="scrollTo('cennik')" class="hover:text-blue-600 transition-colors cursor-pointer">Cennik</a>
                    <a href="#kontakt" @click.prevent="scrollTo('kontakt')" class="hover:text-blue-600 transition-colors cursor-pointer">Kontakt</a>
                </div>
                <!-- Mobile nav links -->
                <div class="flex md:hidden items-center gap-4 text-sm text-gray-600">
                    <a href="#cennik" @click.prevent="scrollTo('cennik')" class="hover:text-blue-600 font-medium">Cennik</a>
                    <a href="#kontakt" @click.prevent="scrollTo('kontakt')" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg font-medium text-xs hover:bg-blue-700 transition-colors">Kontakt</a>
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <section class="bg-gradient-to-br from-blue-700 via-blue-800 to-blue-950 text-white pt-12 sm:pt-20 pb-0 px-4 overflow-hidden relative">
            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 20% 80%, white 1px, transparent 1px), radial-gradient(circle at 80% 20%, white 1px, transparent 1px); background-size: 60px 60px;"></div>

            <div class="max-w-7xl mx-auto relative">
                <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-end">

                    <!-- Left: text -->
                    <div class="pb-10 sm:pb-20 pt-4">
                        <div class="inline-block bg-blue-500/40 text-blue-100 text-xs font-bold px-4 py-1.5 rounded-full mb-4 sm:mb-6 uppercase tracking-widest border border-blue-400/30">
                            Kompletny system SaaS dla gastronomii
                        </div>
                        <h1 class="text-3xl sm:text-4xl md:text-5xl xl:text-6xl font-extrabold mb-4 sm:mb-6 leading-tight">
                            Twoja restauracja<br/>
                            <span class="text-blue-200">w jednym systemie</span>
                        </h1>
                        <p class="text-base sm:text-lg text-blue-100 mb-5 sm:mb-6 leading-relaxed">
                            {{ appName }} to wielofunkcyjny system zarządzania restauracją działający w chmurze.
                            Zamówienia online, panele dla kelnera, kuchni i kierowcy, program lojalnościowy, rezerwacje, raporty i marketing —
                            wszystko w jednym miejscu, dostępne z dowolnego urządzenia.
                        </p>
                        <p class="text-blue-200 text-xs sm:text-sm mb-6 sm:mb-8">Uruchomiony w minuty. Bez technicznej wiedzy. Bez ukrytych kosztów.</p>

                        <div class="flex flex-col sm:flex-row gap-3 mb-7 sm:mb-10">
                            <button @click="scrollTo('cennik')" class="bg-white text-blue-800 font-bold px-6 py-3 rounded-xl hover:bg-blue-50 transition-colors text-center text-sm">
                                Zobacz cennik
                            </button>
                            <button @click="scrollTo('kontakt')" class="border border-blue-400 text-white font-bold px-6 py-3 rounded-xl hover:bg-blue-700/50 transition-colors text-center text-sm">
                                Porozmawiaj z nami
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-2 sm:gap-3 text-blue-100 text-xs sm:text-sm">
                            <div class="flex items-center gap-2"><i class="fa-solid fa-check text-green-300"></i> Nieograniczone zamówienia</div>
                            <div class="flex items-center gap-2"><i class="fa-solid fa-check text-green-300"></i> Panel dla każdej roli</div>
                            <div class="flex items-center gap-2"><i class="fa-solid fa-check text-green-300"></i> Własna subdomena restauracji</div>
                            <div class="flex items-center gap-2"><i class="fa-solid fa-check text-green-300"></i> Aktualizacje w cenie</div>
                        </div>
                    </div>

                    <!-- Right: browser mockups (hidden on mobile) -->
                    <div
                        class="relative hidden lg:flex flex-col items-center pb-0 pt-4 lg:pt-0 hero-mockup-scene"
                        @mousemove="onMockupMouseMove"
                        @mouseleave="onMockupMouseLeave"
                        ref="mockupScene"
                    >

                        <!-- Manager dashboard mockup -->
                        <div class="w-full rounded-t-xl overflow-hidden shadow-2xl ring-1 ring-white/10 relative z-10 hero-mockup-back">
                            <div class="bg-gray-200 px-3 py-2 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-400 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-400 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-400 inline-block"></span>
                                <div class="ml-2 flex-1 bg-white rounded text-gray-400 text-xs px-3 py-1 font-mono truncate">
                                    mojarestauracja.roveto.pl/manager
                                </div>
                            </div>
                            <img
                                src="/images/hero-dashboard.png"
                                alt="Panel zarządzania restauracją"
                                class="w-full block"
                                loading="eager"
                            />
                        </div>

                        <!-- Client store mockup -->
                        <div class="w-[88%] rounded-t-xl overflow-hidden shadow-2xl ring-1 ring-white/10 relative z-20 -mt-6 hero-mockup-front">
                            <div class="bg-gray-200 px-3 py-2 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-400 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-400 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-400 inline-block"></span>
                                <div class="ml-2 flex-1 bg-white rounded text-gray-400 text-xs px-3 py-1 font-mono truncate">
                                    mojarestauracja.roveto.pl
                                </div>
                            </div>
                            <img
                                src="/images/hero-client.png"
                                alt="Strona restauracji dla klientów"
                                class="w-full block"
                                loading="eager"
                            />
                        </div>

                    </div>
                </div>
            </div>
        </section>

        <!-- Stats bar -->
        <section class="bg-blue-600 text-white py-6 px-4">
            <div class="max-w-5xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div><div class="text-3xl font-extrabold">30+</div><div class="text-blue-200 text-sm mt-1">modułów systemu</div></div>
                <div><div class="text-3xl font-extrabold">24/7</div><div class="text-blue-200 text-sm mt-1">dostęp dla klientów</div></div>
                <div><div class="text-3xl font-extrabold">5</div><div class="text-blue-200 text-sm mt-1">ról pracowniczych</div></div>
                <div><div class="text-3xl font-extrabold">100%</div><div class="text-blue-200 text-sm mt-1">w chmurze, bez instalacji</div></div>
            </div>
        </section>

        <!-- Main Features -->
        <section id="funkcje" class="py-12 sm:py-24 px-4 bg-white">
            <div class="max-w-6xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-900 mb-4">Wszystko, czego potrzebuje Twoja restauracja</h2>
                <p class="text-center text-gray-500 mb-16 max-w-2xl mx-auto">
                    Kompletny zestaw narzędzi zaprojektowany specjalnie dla gastronomii. Jeden system zamiast kilku oddzielnych aplikacji.
                </p>

                <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-5 sm:gap-8">
                    <div v-for="f in mainFeatures" :key="f.title" class="bg-gray-50 rounded-2xl p-5 sm:p-7 hover:shadow-md transition-shadow border border-gray-100">
                        <div class="text-4xl mb-4" v-html="f.icon"></div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ f.title }}</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">{{ f.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Deep dive: Order management -->
        <section class="bg-blue-50 py-12 sm:py-24 px-4">
            <div class="max-w-6xl mx-auto">
                <div class="grid md:grid-cols-2 gap-8 md:gap-16 items-center">
                    <div>
                        <div class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-3">Zamówienia i dostawa</div>
                        <h2 class="text-3xl font-bold text-gray-900 mb-5">Pełna kontrola nad każdym zamówieniem</h2>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            Od złożenia zamówienia przez klienta, przez kuchnię, aż do dostawy pod drzwi — każdy krok jest monitorowany w czasie rzeczywistym.
                            Klient otrzymuje automatyczne powiadomienia e-mail, a kierowca udostępnia lokalizację GPS.
                        </p>
                        <ul class="space-y-3 text-sm text-gray-700">
                            <li v-for="item in orderFeatures" :key="item" class="flex items-start gap-3">
                                <i class="fa-solid fa-check text-blue-500 font-bold mt-0.5"></i>{{ item }}
                            </li>
                        </ul>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div v-for="card in orderCards" :key="card.label" class="bg-white rounded-xl p-5 shadow-sm border border-blue-100">
                            <div class="text-3xl mb-3" v-html="card.icon"></div>
                            <div class="font-semibold text-gray-800 text-sm">{{ card.label }}</div>
                            <div class="text-xs text-gray-400 mt-1">{{ card.sub }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Staff roles section -->
        <section class="py-12 sm:py-24 px-4 bg-white">
            <div class="max-w-6xl mx-auto">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">Dedykowane panele dla każdej roli</h2>
                <p class="text-center text-gray-500 mb-14 max-w-xl mx-auto">
                    Każdy pracownik widzi tylko to, czego potrzebuje do pracy. Bez zbędnych zakłóceń, bez pomyłek.
                </p>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                    <div v-for="role in roles" :key="role.name" class="text-center p-4 sm:p-6 rounded-2xl border-2 hover:border-blue-400 transition-colors" :class="role.borderClass">
                        <div class="text-4xl mb-3" v-html="role.icon"></div>
                        <div class="font-bold text-gray-900 text-sm mb-2">{{ role.name }}</div>
                        <div class="text-xs text-gray-500 leading-relaxed">{{ role.desc }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Marketing & Loyalty -->
        <section class="bg-gray-50 py-12 sm:py-24 px-4">
            <div class="max-w-6xl mx-auto">
                <div class="grid md:grid-cols-2 gap-8 md:gap-16 items-center">
                    <div class="grid grid-cols-2 gap-3 sm:gap-4 order-2 md:order-1">
                        <div v-for="card in loyaltyCards" :key="card.label" class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                            <div class="text-3xl mb-3" v-html="card.icon"></div>
                            <div class="font-semibold text-gray-800 text-sm">{{ card.label }}</div>
                            <div class="text-xs text-gray-400 mt-1">{{ card.sub }}</div>
                        </div>
                    </div>
                    <div class="order-1 md:order-2">
                        <div class="text-xs font-bold text-purple-600 uppercase tracking-widest mb-3">Lojalność i marketing</div>
                        <h2 class="text-3xl font-bold text-gray-900 mb-5">Zbuduj bazę stałych klientów</h2>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            Wbudowany program lojalnościowy z czterema poziomami (brąz, srebro, złoto, platyna) nagradza klientów za każde zamówienie.
                            <!-- Moduł e-mail marketingu pozwala wysyłać kampanie do segmentowanych grup klientów. -->
                        </p>
                        <ul class="space-y-3 text-sm text-gray-700">
                            <li v-for="item in loyaltyFeatures" :key="item" class="flex items-start gap-3">
                                <i class="fa-solid fa-check text-purple-500 font-bold mt-0.5"></i>{{ item }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section id="jak-dziala" class="py-12 sm:py-24 px-4 bg-white">
            <div class="max-w-5xl mx-auto">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">Jak działa {{ appName }}?</h2>
                <p class="text-center text-gray-500 mb-16 max-w-xl mx-auto">Od rejestracji do pierwszego zamówienia w kilka minut</p>

                <div class="grid md:grid-cols-3 gap-8">
                    <div v-for="(step, i) in steps" :key="step.title" class="text-center">
                        <div class="w-16 h-16 rounded-full bg-blue-600 text-white flex items-center justify-center text-2xl font-extrabold mx-auto mb-5">
                            {{ i + 1 }}
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-3">{{ step.title }}</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">{{ step.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Technical features -->
        <section class="bg-gray-900 text-white py-12 sm:py-24 px-4">
            <div class="max-w-6xl mx-auto">
                <h2 class="text-3xl font-bold text-center mb-4">Architektura techniczna</h2>
                <p class="text-center text-gray-400 mb-14 max-w-xl mx-auto">Solidna architektura, bezpieczeństwo danych i izolacja tenantów</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                    <div v-for="t in techFeatures" :key="t.title" class="bg-gray-800 rounded-2xl p-4 sm:p-6 border border-gray-700">
                        <div class="text-3xl mb-4" v-html="t.icon"></div>
                        <div class="font-semibold text-white mb-2">{{ t.title }}</div>
                        <div class="text-sm text-gray-400 leading-relaxed">{{ t.desc }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing -->
        <section id="cennik" class="py-12 sm:py-24 px-4 bg-white">
            <div class="max-w-5xl mx-auto">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">Prosta, przejrzysta cena</h2>
                <p class="text-center text-gray-500 mb-14">Pełny dostęp do systemu, bez ukrytych kosztów</p>

                <div class="max-w-lg mx-auto bg-white rounded-3xl shadow-2xl border-2 border-blue-500 overflow-hidden">
                    <div class="bg-blue-600 text-white text-center py-6 px-8">
                        <div class="text-xs font-bold uppercase tracking-widest text-blue-200 mb-2">Pełny dostęp</div>
                        <div class="text-6xl font-extrabold">300</div>
                        <div class="text-blue-200 text-sm mt-1">PLN / rok &nbsp;·&nbsp; czyli tylko 25 zł miesięcznie</div>
                    </div>
                    <div class="p-5 sm:p-8">
                        <div class="grid grid-cols-2 gap-2 mb-6 sm:mb-8">
                            <div v-for="item in pricingFeatures" :key="item" class="flex items-start gap-2 text-sm text-gray-700">
                                <i class="fa-solid fa-check text-green-500 font-bold shrink-0 mt-0.5"></i>
                                <span>{{ item }}</span>
                            </div>
                        </div>
                        <div class="border-t border-gray-100 pt-6 text-center text-xs text-gray-400">
                            Wersja systemu (zakres dostępnych integracji) ustalana indywidualnie przy zakupie.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact / CTA -->
        <section id="kontakt" class="bg-blue-700 py-12 sm:py-24 px-4">
            <div class="max-w-2xl mx-auto">
                <h2 class="text-3xl font-bold text-white text-center mb-3">Zainteresowany systemem?</h2>
                <p class="text-blue-100 text-center mb-10 text-lg">
                    Napisz do nas — odpowiemy w ciągu 24 godzin w dni robocze.
                </p>

                <!-- Success message -->
                <div v-if="contactSuccess" class="bg-white rounded-2xl shadow-xl p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-circle-check text-3xl text-green-500"></i>
                    </div>
                    <p class="text-xl font-bold text-gray-900 mb-2">Wiadomość wysłana!</p>
                    <p class="text-gray-500 text-sm mb-5">Skontaktujemy się wkrótce.</p>
                    <button @click="contactSuccess = false" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition-colors">
                        Wyślij kolejną wiadomość
                    </button>
                </div>

                <!-- Contact Form -->
                <form v-else @submit.prevent="submitContact" class="bg-white rounded-2xl shadow-xl p-5 sm:p-8 space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Imię i nazwisko *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                placeholder="Jan Kowalski"
                            />
                            <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail *</label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                placeholder="jan@restauracja.pl"
                            />
                            <p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email }}</p>
                        </div>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                placeholder="+48 600 000 000"
                                @blur="form.phone = formatPhone(form.phone)"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Temat</label>
                            <input
                                v-model="form.subject"
                                type="text"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                placeholder="np. Demo systemu"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Wiadomość *</label>
                        <textarea
                            v-model="form.message"
                            required
                            rows="4"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm resize-none"
                            placeholder="Opisz potrzeby swojej restauracji..."
                        ></textarea>
                        <p v-if="errors.message" class="text-red-500 text-xs mt-1">{{ errors.message }}</p>
                    </div>
                    <p v-if="errors.general" class="text-red-500 text-sm">{{ errors.general }}</p>
                    <button
                        type="submit"
                        :disabled="sending"
                        class="w-full py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-bold text-base rounded-xl transition-colors"
                    >
                        {{ sending ? 'Wysyłanie…' : 'Wyślij wiadomość →' }}
                    </button>
                </form>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-gray-400 py-10 px-4">
            <div class="max-w-6xl mx-auto">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-2">
                        <img src="/images/logo.png" alt="Logo" class="h-6 w-auto brightness-0 invert" />
                        <span class="text-lg font-bold text-white">{{ appName }}</span>
                    </div>
                    <div class="text-sm text-center text-gray-500">
                        System zarządzania restauracją w chmurze &nbsp;·&nbsp; Wielodostęp SaaS
                    </div>
                    <div class="text-sm">
                        &copy; {{ new Date().getFullYear() }} {{ appName }}. Wszelkie prawa zastrzeżone.
                    </div>
                </div>
            </div>
        </footer>

    </div>
</template>

<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { computed, ref, reactive } from 'vue'
import axios from 'axios'
import { formatPhone } from '@/utils/phone'

const page = usePage()
const appName = computed(() => page.props.app_name ?? 'Roveto')

const jsonLd = computed(() => JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'SoftwareApplication',
    'name': appName.value,
    'url': 'https://roveto.pl',
    'applicationCategory': 'BusinessApplication',
    'operatingSystem': 'Web',
    'description': 'Nowoczesny system SaaS do zarządzania restauracją. Zamówienia online, kasa, kuchnia, dostawa – wszystko w jednym miejscu.',
    'offers': {
        '@type': 'AggregateOffer',
        'priceCurrency': 'PLN',
        'offerCount': String(props.plans?.length ?? 1),
        'lowPrice': props.plans?.length ? String(Math.min(...props.plans.map(p => p.price))) : '0',
    },
    'publisher': {
        '@type': 'Organization',
        'name': appName.value,
        'url': 'https://roveto.pl',
        'logo': 'https://roveto.pl/images/logo.png',
    },
}))

const props = defineProps({
    plans: { type: Array, default: () => [] },
})

// Smooth scroll with easing
const scrollTo = (id) => {
    const el = document.getElementById(id)
    if (!el) return
    const navHeight = 72
    const top = el.getBoundingClientRect().top + window.scrollY - navHeight
    window.scrollTo({ top, behavior: 'smooth' })
}

// Hero 3D tilt on mouse move
const mockupScene = ref(null)

const onMockupMouseMove = (e) => {
    const el = mockupScene.value
    if (!el) return
    const rect = el.getBoundingClientRect()
    const cx = rect.left + rect.width / 2
    const cy = rect.top + rect.height / 2
    const dx = (e.clientX - cx) / (rect.width / 2)   // -1 … 1
    const dy = (e.clientY - cy) / (rect.height / 2)  // -1 … 1
    const rotY = dx * 8   // max ±8deg horizontal
    const rotX = -dy * 5  // max ±5deg vertical
    el.style.transform = `perspective(1200px) rotateX(${rotX}deg) rotateY(${rotY}deg)`
}

const onMockupMouseLeave = () => {
    const el = mockupScene.value
    if (!el) return
    el.style.transform = 'perspective(1200px) rotateX(0deg) rotateY(0deg)'
}

// Contact form
const contactSuccess = ref(false)
const contactErrors = reactive({})
const sending = ref(false)
const form = reactive({ name: '', email: '', phone: '', subject: '', message: '' })
const errors = computed(() => contactErrors)

const submitContact = async () => {
    Object.keys(contactErrors).forEach(k => delete contactErrors[k])
    sending.value = true
    try {
        await axios.post(route('contact.store'), form)
        contactSuccess.value = true
        form.name = ''
        form.email = ''
        form.phone = ''
        form.subject = ''
        form.message = ''
    } catch (e) {
        if (e.response?.status === 422) {
            Object.assign(contactErrors, e.response.data.errors || {})
        } else {
            contactErrors.general = 'Nie udało się wysłać wiadomości. Spróbuj ponownie.'
        }
    } finally {
        sending.value = false
    }
}

const mainFeatures = [
    { icon: '<i class="fa-solid fa-clipboard-list text-blue-500"></i>', title: 'Zarządzanie menu', desc: 'Kategorie, produkty, warianty (rozmiary), dodatki i alergeny. Aktualizacja cen i dostępności w sekundy.' },
    { icon: '<i class="fa-solid fa-truck text-blue-600"></i>', title: 'Zamówienia i dostawa', desc: 'Zamówienia online, odbiór osobisty, dine-in. Strefa dostaw na mapie, śledzenie kierowcy GPS na żywo.' },
    { icon: '<i class="fa-solid fa-fire-burner text-orange-500"></i>', title: 'Panel kuchni (KDS)', desc: 'Ekran KDS dla kucharzy wyświetla zamówienia w kolejności. Brak pomyłek, brak papierowych biletów.' },
    { icon: '<i class="fa-solid fa-star text-yellow-400"></i>', title: 'Program lojalnościowy', desc: 'Punkty za zamówienia, 4 poziomy (brąz/srebro/złoto/platyna), konfigurowane progi i nagrody dla stałych klientów.' },
    { icon: '<i class="fa-solid fa-chart-bar text-indigo-500"></i>', title: 'Raporty i statystyki', desc: 'Wykresy przychodów (30 dni), mapa ciepła sprzedaży godzinowej, popularne produkty, eksport CSV.' },
    { icon: '<i class="fa-solid fa-palette text-pink-500"></i>', title: 'Personalizacja wyglądu', desc: 'Własne kolory, logo, zdjęcie hero, galeria. Strona wygląda jak Twoja restauracja — nie jak szablon.' },
    { icon: '<i class="fa-solid fa-envelope text-blue-500"></i>', title: 'Powiadomienia e-mail', desc: 'Automatyczne e-maile: potwierdzenie zamówienia, zmiana statusu, kampanie marketingowe, przypomnienia o rezerwacji.' },
    { icon: '<i class="fa-solid fa-mobile-screen text-green-500"></i>', title: 'Powiadomienia push', desc: 'Webpush dla pracowników — natychmiastowe alerty o nowych zamówieniach bez otwierania przeglądarki.' },
    { icon: '<i class="fa-solid fa-calendar-days text-blue-500"></i>', title: 'Rezerwacje stolików', desc: 'Formularz rezerwacji z detekcją konfliktu, potwierdzenia e-mail, panel zarządzania rezerwacjami dla managera.' },
    { icon: '<i class="fa-solid fa-lock text-red-500"></i>', title: 'Kontrola dostępu', desc: 'Uprawnienia per rola — każda rola widzi tylko swoje funkcje. Menedżer zarządza uprawnieniami z poziomu panelu.' },
    { icon: '<i class="fa-solid fa-comment text-teal-500"></i>', title: 'System wsparcia', desc: 'Zgłoszenia serwisowe bezpośrednio z panelu restauracji. Komunikacja z administracją systemu w jednym miejscu.' },
    { icon: '<i class="fa-solid fa-shield-halved text-blue-600"></i>', title: 'Bezpieczeństwo', desc: 'Szyfrowanie haseł i kluczy API, HTTPS, CSRF, rate limiting, izolacja danych każdej restauracji (multi-tenant).' },
]

const orderFeatures = [
    'Kolejkowanie zamówień z automatycznym numerem ORD-YYMMDD-NNNN',
    'WebSocket – nowe zamówienia pojawiają się bez odświeżania strony',
    'Śledzenie statusu: nowe → przyjęte → w przygotowaniu → gotowe → w dostarczeniu → dostarczone',
    'Powiadomienia e-mail przy każdej zmianie statusu zamówienia',
    'Śledzenie kierowcy na mapie OpenStreetMap w czasie rzeczywistym',
    'Kody rabatowe i zniżki lojalnościowe automatycznie odliczane',
    'Walidacja strefy dostaw – tylko zamówienia z obszaru obsługiwanego',
    'Tryb wakacyjny – automatyczne zamknięcie restauracji z komunikatem',
]

const orderCards = [
    { icon: '<i class="fa-solid fa-bolt text-yellow-400"></i>', label: 'Czas rzeczywisty', sub: 'WebSocket (Reverb)' },
    { icon: '<i class="fa-solid fa-map text-green-500"></i>', label: 'GPS kierowcy', sub: 'OpenStreetMap' },
    { icon: '<i class="fa-solid fa-location-dot text-red-500"></i>', label: 'Strefa dostaw', sub: 'Mapa wielokątów' },
    { icon: '<i class="fa-solid fa-file-lines text-blue-500"></i>', label: 'Historia zamówień', sub: 'Filtrowanie i eksport' },
]

const roles = [
    { icon: '<i class="fa-solid fa-user-tie text-blue-600"></i>', name: 'Manager', desc: 'Pełny dostęp: menu, zamówienia, pracownicy, raporty, ustawienia', borderClass: 'border-blue-200 bg-blue-50' },
    { icon: '<i class="fa-solid fa-fire-burner text-orange-500"></i>', name: 'Kucharz', desc: 'KDS: lista zamówień, zmiana statusu potraw', borderClass: 'border-orange-200 bg-orange-50' },
    { icon: '<i class="fa-solid fa-utensils text-yellow-600"></i>', name: 'Kelner', desc: 'Zamówienia przy stolikach, POS, kody QR dla stolików', borderClass: 'border-yellow-200 bg-yellow-50' },
    { icon: '<i class="fa-solid fa-car text-green-600"></i>', name: 'Kierowca', desc: 'Lista dostaw, GPS, oznaczanie dostarczonych', borderClass: 'border-green-200 bg-green-50' },
    { icon: '<i class="fa-solid fa-money-bill-wave text-purple-600"></i>', name: 'Kasjer', desc: 'POS: zamówienia gotówkowe i kartowe na miejscu', borderClass: 'border-purple-200 bg-purple-50' },
]

const loyaltyCards = [
    { icon: '<i class="fa-solid fa-medal text-amber-700"></i>', label: 'Poziom Brązowy', sub: 'Startowy dla nowych klientów' },
    { icon: '<i class="fa-solid fa-award text-gray-400"></i>', label: 'Poziom Srebrny', sub: 'Zniżka po przekroczeniu progu' },
    { icon: '<i class="fa-solid fa-trophy text-yellow-400"></i>', label: 'Poziom Złoty', sub: 'Wyższe nagrody, priorytet' },
    { icon: '<i class="fa-solid fa-gem text-blue-400"></i>', label: 'Poziom Platynowy', sub: 'Ekskluzywne korzyści VIP' },
]

const loyaltyFeatures = [
    'Automatyczne naliczanie punktów za każde zamówienie',
    'Konfigurowalne progi i wartości nagród',
    // 'Kampanie e-mail marketingowe z segmentacją klientów',
    'HMAC-bezpieczny link wypisania z listy mailingowej',
    'Eksport bazy klientów do CSV',
    'Historia zakupów i punktów dla każdego klienta',
]

const steps = [
    {
        title: 'Rejestracja restauracji',
        desc: 'Administrator systemu tworzy konto Twojej restauracji i przypisuje subdomenę. Otrzymujesz e-mail z danymi dostępowymi.',
    },
    {
        title: 'Kreator instalacji',
        desc: 'W 3 krokach konfigurujesz dane restauracji, godziny otwarcia i podstawowe ustawienia. Gotowe w mniej niż 5 minut.',
    },
    {
        title: 'Zarządzaj i zarabiaj',
        desc: 'Uzupełniasz menu, konfigurujesz strefy dostaw i przyjmujesz pierwsze zamówienia. Wszystko w jednym miejscu.',
    },
]

const techFeatures = [
    { icon: '<i class="fa-solid fa-bolt text-yellow-400"></i>', title: 'WebSocket (Reverb)', desc: 'Zamówienia, statusy i lokalizacja GPS aktualizują się na żywo bez odświeżania strony.' },
    { icon: '<i class="fa-solid fa-box text-indigo-500"></i>', title: 'Multi-tenant SaaS', desc: 'Każda restauracja ma izolowaną bazę danych. Dane jednej nigdy nie mieszają się z danymi innej.' },
    { icon: '<i class="fa-solid fa-mobile-screen text-green-500"></i>', title: 'PWA / Mobile', desc: 'Aplikacja działa jako progresywna aplikacja webowa. Instalacja na telefonie bez App Store.' },
    { icon: '<i class="fa-solid fa-lock text-red-500"></i>', title: 'Szyfrowanie', desc: 'Klucze API bramek płatności i hasła przechowywane w postaci zaszyfrowanej. HTTPS i CSRF na każdej trasie.' },
]

const pricingFeatures = [
    'Nieograniczone zamówienia',
    'Panel managera i pracowników',
    'Własna subdomena restauracji',
    'Program lojalnościowy 4 poziomy',
    'Powiadomienia e-mail',
    'Rezerwacje stolików',
    'Raporty i eksport CSV',
    // 'Drukarka termiczna',
    // 'Marketing e-mailowy',
    'KDS dla kuchni',
    'Panel kelnera i POS',
    'Śledzenie kierowcy GPS',
    'Kreator instalacji',
    'System wsparcia',
    'Aktualizacje w cenie',
    'Wsparcie techniczne',
]
</script>

<style scoped>
/* ── Hero mockup container ─────────────────────────────────────── */
.hero-mockup-scene {
    transform-style: preserve-3d;
    transition: transform 0.12s ease-out;
    /* entry animation */
    animation: hero-slide-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* back (dashboard) mockup — floats slower */
.hero-mockup-back {
    animation: hero-float-back 6s ease-in-out infinite;
}

/* front (client page) mockup — floats faster, slight offset */
.hero-mockup-front {
    animation: hero-float-front 5s ease-in-out 0.4s infinite;
}

/* ── Keyframes ─────────────────────────────────────────────────── */
@keyframes hero-slide-up {
    from {
        opacity: 0;
        transform: perspective(1200px) translateY(48px) rotateX(6deg);
        filter: drop-shadow(0 0 32px rgba(220, 38, 38, 0));
    }
    to {
        opacity: 1;
        transform: perspective(1200px) translateY(0) rotateX(0deg);
        filter: drop-shadow(0 0 32px rgba(29, 78, 216, 0.25));
    }
}

@keyframes hero-float-back {
    0%, 100% { transform: translateY(0px); }
    50%       { transform: translateY(-10px); }
}

@keyframes hero-float-front {
    0%, 100% { transform: translateY(0px); }
    50%       { transform: translateY(-14px); }
}
</style>
