<template>
    <!-- SEO + Favicon (#15) -->
    <Head>
        <title>{{ tenantName }}</title>
        <meta name="description" :content="tenant?.description || tenantName" />
        <link v-if="tenant?.favicon_url" rel="icon" :href="tenant.favicon_url" />
    </Head>

    <div class="min-h-screen bg-white text-gray-900 antialiased flex flex-col">
        <!-- Header -->
        <nav class="bg-white/95 backdrop-blur-sm sticky top-0 z-50 shadow-sm">
            <div class="container mx-auto px-4 lg:px-6 py-4 flex justify-between items-center">
                <!-- Logo -->
                <Link :href="route('tenant.menu')" class="flex items-center gap-2">
                    <img
                        v-if="tenant?.logo_url"
                        :src="tenant.logo_url"
                        :alt="tenantName"
                        class="h-10 w-auto object-contain"
                    />
                    <span class="text-2xl md:text-3xl font-extrabold tracking-tight theme-primary">
                        {{ tenantName }}
                    </span>
                </Link>

                <!-- Desktop nav -->
                <div class="hidden md:flex space-x-8 font-medium">
                    <Link :href="route('tenant.menu')" class="nav-link transition" :class="isActive('/') ? 'theme-primary' : 'text-gray-700'">
                        Menu
                    </Link>
                    <a v-if="hasGoogleReviews" href="#opinie" class="nav-link text-gray-700 transition">Opinie</a>
                    <Link v-if="tenant?.reservations_enabled" :href="route('tenant.reservation')" class="nav-link transition" :class="isActive('/rezerwacja') ? 'theme-primary' : 'text-gray-700'">
                        Rezerwacja
                    </Link>
                    <Link :href="route('tenant.contact')" class="nav-link transition" :class="isActive('/kontakt') ? 'theme-primary' : 'text-gray-700'">
                        Kontakt
                    </Link>
                </div>

                <!-- Right side -->
                <div class="flex items-center gap-4">
                    <!-- Opening hours badge -->
                    <div class="hidden lg:flex items-center text-sm" :class="isOpenNow ? 'text-green-600' : 'text-red-500'">
                        <span class="w-2 h-2 rounded-full mr-1.5" :class="isOpenNow ? 'bg-green-500' : 'bg-red-400'"></span>
                        {{ isOpenNow ? 'Otwarte' : 'Zamknięte' }}
                    </div>

                    <!-- Cart -->
                    <button
                        @click="cartStore.toggleCart()"
                        class="nav-link relative text-gray-700 text-2xl transition"
                        title="Koszyk"
                    >
                        <i class="fa-solid fa-cart-shopping text-2xl theme-primary"></i>
                        <span
                            v-if="cartStore.itemCount > 0"
                            class="absolute -top-1.5 -right-2 inline-flex items-center justify-center theme-primary-bg text-white text-xs font-bold rounded-full w-5 h-5"
                        >
                            {{ cartStore.itemCount }}
                        </span>
                    </button>

                    <!-- User panel -->
                    <template v-if="customer">
                        <div class="relative hidden md:block" ref="userMenuRef">
                            <button
                                @click="userMenuOpen = !userMenuOpen"
                                class="flex items-center space-x-2 bg-gray-100 py-1.5 px-3 rounded-full hover:bg-gray-200 transition"
                            >
                                <span class="w-8 h-8 theme-primary-bg rounded-full flex items-center justify-center text-white font-bold text-sm">
                                    {{ customer.name?.charAt(0)?.toUpperCase() }}
                                </span>
                                <span class="font-medium text-sm">{{ customer.name?.split(' ')[0] }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-gray-500"></i>
                            </button>
                            <div v-if="userMenuOpen" class="absolute right-0 mt-2 w-44 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50">
                                <Link :href="route('tenant.account')" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-900 hover:bg-gray-50">
                                    <i class="fa-solid fa-user w-4 text-gray-700"></i> Moje konto
                                </Link>
                                <Link :href="route('tenant.client.logout')" method="post" as="button" class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-gray-900 hover:bg-gray-50">
                                    <i class="fa-solid fa-right-from-bracket w-4 text-gray-700"></i> Wyloguj
                                </Link>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('tenant.client.login')"
                            class="hidden md:block text-sm font-medium text-gray-700 hover:text-red-700 transition"
                        >
                            Zaloguj
                        </Link>
                    </template>

                    <!-- Hamburger mobile -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-700 focus:outline-none text-2xl">
                        <i v-if="!mobileMenuOpen" class="fa-solid fa-bars"></i>
                        <i v-else class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <div v-show="mobileMenuOpen" class="md:hidden bg-white border-t border-gray-100 px-4 py-3 space-y-2 text-base">
                <Link :href="route('tenant.menu')" class="block py-2 nav-link">Menu</Link>
                <a v-if="hasGoogleReviews" href="#opinie" class="block py-2 nav-link" @click="mobileMenuOpen = false">Opinie</a>
                <Link v-if="tenant?.reservations_enabled" :href="route('tenant.reservation')" class="block py-2 nav-link">Rezerwacja</Link>
                <Link :href="route('tenant.contact')" class="block py-2 nav-link">Kontakt</Link>
                <template v-if="customer">
                    <Link :href="route('tenant.account')" class="block py-2 nav-link">Moje konto</Link>
                    <Link :href="route('tenant.client.logout')" method="post" as="button" class="block py-2 text-gray-500 hover:text-gray-700">
                        Wyloguj
                    </Link>
                </template>
                <template v-else>
                    <Link :href="route('tenant.client.login')" class="block py-2 hover:text-red-700">Zaloguj</Link>
                    <Link :href="route('tenant.client.register')" class="block py-2 hover:text-red-700">Rejestracja</Link>
                </template>
                <div class="pt-2 border-t border-gray-100">
                    <div class="flex items-center text-sm" :class="isOpenNow ? 'text-green-600' : 'text-red-500'">
                        <span class="w-2 h-2 rounded-full mr-1.5" :class="isOpenNow ? 'bg-green-500' : 'bg-red-400'"></span>
                        {{ isOpenNow ? 'Otwarte' : 'Zamknięte' }}
                        <span v-if="todayHours" class="ml-2 text-gray-500">{{ todayHours }}</span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Vacation Banner -->
        <div v-if="tenant?.vacation_mode" class="bg-yellow-50 border-b border-yellow-200 py-4 px-4">
            <div class="container mx-auto max-w-4xl flex items-center justify-center gap-3 text-center flex-wrap">
                <i class="fa-solid fa-triangle-exclamation text-2xl text-yellow-700"></i>
                <div>
                    <p class="font-bold text-yellow-800 text-lg">Restauracja jest chwilowo niedostępna</p>
                    <p class="text-yellow-700 text-sm mt-0.5">{{ tenant.vacation_message || 'Zapraszamy wkrótce!' }}</p>
                </div>
            </div>
        </div>

        <!-- Orders paused banner -->
        <div v-else-if="tenant?.orders_paused" class="bg-orange-50 border-b border-orange-200 py-4 px-4">
            <div class="container mx-auto max-w-4xl flex items-center justify-center gap-3 text-center flex-wrap">
                <i class="fa-solid fa-pause-circle text-2xl text-orange-700"></i>
                <div>
                    <p class="font-bold text-orange-800 text-lg">Przyjmowanie zamówień wstrzymane</p>
                    <p class="text-orange-700 text-sm mt-0.5">Restauracja chwilowo nie przyjmuje zamówień online. Zapraszamy wkrótce!</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Cart Sidebar -->
        <CartSidebar />

        <!-- Footer -->
        <footer class="bg-gray-900 text-white pt-10 sm:pt-16 pb-8">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 sm:gap-10">
                    <!-- Brand -->
                    <div class="col-span-2 md:col-span-1">
                        <div class="flex items-center gap-2 mb-4">
                            <img v-if="tenant?.logo_url" :src="tenant.logo_url" :alt="tenantName" class="h-8 w-auto" />
                            <span class="text-2xl font-bold text-white">{{ tenantName }}</span>
                        </div>
                        <p v-if="tenant?.description" class="text-gray-400 text-sm leading-relaxed">
                            {{ tenant.description }}
                        </p>
                        <!-- Social media links -->
                        <div v-if="tenant?.facebook_url || tenant?.instagram_url || tenant?.tiktok_url" class="flex items-center gap-3 mt-5">
                            <a v-if="tenant.facebook_url" :href="tenant.facebook_url" target="_blank" rel="noopener noreferrer" class="w-9 h-9 bg-gray-800 hover:bg-blue-600 rounded-full flex items-center justify-center transition-colors" aria-label="Facebook">
                                <i class="fa-brands fa-facebook-f text-sm"></i>
                            </a>
                            <a v-if="tenant.instagram_url" :href="tenant.instagram_url" target="_blank" rel="noopener noreferrer" class="w-9 h-9 bg-gray-800 hover:bg-pink-600 rounded-full flex items-center justify-center transition-colors" aria-label="Instagram">
                                <i class="fa-brands fa-instagram text-sm"></i>
                            </a>
                            <a v-if="tenant.tiktok_url" :href="tenant.tiktok_url" target="_blank" rel="noopener noreferrer" class="w-9 h-9 bg-gray-800 hover:bg-gray-600 rounded-full flex items-center justify-center transition-colors" aria-label="TikTok">
                                <i class="fa-brands fa-tiktok text-sm"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div>
                        <h4 class="text-lg font-semibold mb-4">Kontakt</h4>
                        <ul class="text-gray-400 text-sm space-y-3">
                            <li v-if="tenant?.phone">
                                <i class="fa-solid fa-phone mr-2 theme-primary"></i>
                                <a :href="'tel:' + tenant.phone" class="hover:text-white">{{ tenant.phone }}</a>
                            </li>
                            <li v-if="tenant?.email">
                                <i class="fa-solid fa-envelope mr-2 theme-primary"></i>
                                {{ tenant.email }}
                            </li>
                            <li v-if="tenant?.address">
                                <i class="fa-solid fa-location-dot mr-2 theme-primary"></i>
                                {{ tenant.address }}
                            </li>
                        </ul>
                    </div>

                    <!-- Opening hours -->
                    <div>
                        <h4 class="text-lg font-semibold mb-4">Godziny otwarcia</h4>
                        <ul class="text-gray-400 text-sm space-y-1">
                            <li
                                v-for="(day, index) in weekDays"
                                :key="index"
                                class="flex justify-between"
                                :class="index === currentDayIndex ? 'text-white font-medium' : ''"
                            >
                                <span>{{ day.label }}</span>
                                <span>{{ getDayHours(day.key) }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Links -->
                    <div>
                        <h4 class="text-lg font-semibold mb-4">Na skróty</h4>
                        <ul class="text-gray-400 text-sm space-y-2">
                            <li><Link :href="route('tenant.menu')" class="hover:text-white">Menu</Link></li>
                            <li><Link :href="route('tenant.contact')" class="hover:text-white">Kontakt</Link></li>
                            <li v-if="tenant?.reservations_enabled"><Link :href="route('tenant.reservation')" class="hover:text-white">Rezerwacja</Link></li>
                            <li v-if="customer"><Link :href="route('tenant.account')" class="hover:text-white">Moje konto</Link></li>
                            <li><Link :href="route('tenant.terms')" class="hover:text-white">Regulamin</Link></li>
                            <li><Link :href="route('tenant.privacy')" class="hover:text-white">Polityka prywatności</Link></li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-800 mt-14 pt-8 text-center text-gray-500 text-xs">
                    &copy; {{ new Date().getFullYear() }} {{ tenantName }}. Wszystkie prawa zastrzeżone.
                    <span class="block mt-1 text-gray-600">
                        Sklep działa na platformie <a href="https://roveto.pl" target="_blank" rel="noopener" class="hover:text-gray-400 transition-colors">roveto.pl</a>
                    </span>
                </div>
            </div>
        </footer>

        <!-- Cookie consent (RODO) -->
        <CookieConsent
            :ga-id="tenant?.google_analytics_id || ''"
            :pixel-id="tenant?.facebook_pixel_id || ''"
            :restaurant-name="tenantName"
            :restaurant-email="tenant?.contact_email || ''"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { useCartStore } from '@/Stores/cartStore'
import CartSidebar from '@/Components/Client/CartSidebar.vue'
import CookieConsent from '@/Components/CookieConsent.vue'

const page = usePage()
const cartStore = useCartStore()
const mobileMenuOpen = ref(false)
const userMenuOpen = ref(false)
const userMenuRef = ref(null)

const handleClickOutside = (e) => {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
        userMenuOpen.value = false
    }
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

const tenant = computed(() => page.props.tenant)
const customer = computed(() => page.props.auth?.customer)
const tenantName = computed(() => tenant.value?.name || 'Restauracja')

onMounted(() => {
    applyThemeCSS()

    // Save table ID from QR code URL (?table=1) to localStorage
    const urlParams = new URLSearchParams(window.location.search)
    const tableParam = urlParams.get('table')
    if (tableParam && /^\d+$/.test(tableParam)) {
        localStorage.setItem('qr_table_id', tableParam)
    }
})

// --- Theme CSS ---
const fontFamilies = {
    inter: "'Inter', 'Segoe UI', sans-serif",
    roboto: "'Roboto', 'Arial', sans-serif",
    merriweather: "'Merriweather', 'Georgia', serif",
    playfair: "'Playfair Display', 'Georgia', serif",
}

const themeCSS = computed(() => {
    const primary = tenant.value?.theme_primary_color || '#b91c1c'
    const font = tenant.value?.theme_font || 'inter'
    // Strip dangerous patterns to prevent style-block injection (defense-in-depth alongside backend validation)
    const rawCss = tenant.value?.custom_css || ''
    const customCss = rawCss
        .replace(/<\/style/gi, '')
        .replace(/<script/gi, '')
        .replace(/javascript\s*:/gi, '')
        .replace(/expression\s*\(/gi, '')
        .replace(/url\s*\(\s*["']?\s*javascript/gi, '')
    const fontFamily = fontFamilies[font] || fontFamilies.inter

    return `
:root { --color-primary: ${primary}; --font-main: ${fontFamily}; }
body { font-family: var(--font-main); }
.theme-primary { color: var(--color-primary) !important; }
.theme-primary-bg { background-color: var(--color-primary) !important; }
.nav-link:hover { color: var(--color-primary) !important; }
${customCss}
`.trim()
})

// Inject theme CSS into <head> dynamically (can't use <style> tag in Vue template)
const applyThemeCSS = () => {
    let el = document.getElementById('tenant-theme')
    if (!el) {
        el = document.createElement('style')
        el.id = 'tenant-theme'
        document.head.appendChild(el)
    }
    el.textContent = themeCSS.value
}
watch(themeCSS, applyThemeCSS)

const hasGoogleReviews = computed(() => !!tenant.value?.google_place_id)

const weekDays = [
    { key: 'monday', label: 'pon.' },
    { key: 'tuesday', label: 'wt.' },
    { key: 'wednesday', label: 'śr.' },
    { key: 'thursday', label: 'czw.' },
    { key: 'friday', label: 'pt.' },
    { key: 'saturday', label: 'sob.' },
    { key: 'sunday', label: 'niedz.' },
]

const currentDayIndex = computed(() => {
    const day = new Date().getDay()
    return day === 0 ? 6 : day - 1
})

const openingHours = computed(() => {
    try {
        const raw = tenant.value?.opening_hours
        if (!raw) return null
        return typeof raw === 'string' ? JSON.parse(raw) : raw
    } catch {
        return null
    }
})

const getDayHours = (dayKey) => {
    if (!openingHours.value) return '-'
    const day = openingHours.value[dayKey]
    if (!day || !day.enabled) return 'zamknięte'
    return `${day.open || '00:00'}-${day.close || '00:00'}`
}

const todayHours = computed(() => {
    const dayKey = weekDays[currentDayIndex.value]?.key
    if (!dayKey) return null
    return getDayHours(dayKey)
})

// Server-side calculation (respects restaurant timezone and vacation_mode)
const isOpenNow = computed(() => tenant.value?.is_open_now ?? false)

const isActive = (path) => {
    const url = page.url || ''
    if (path === '/') return url === '/' || url === ''
    return url.startsWith(path)
}

</script>

