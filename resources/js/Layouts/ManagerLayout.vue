<template>
    <Head :title="title ? `${title} – Panel zarządzania` : 'Panel zarządzania'" />
    <div class="min-h-screen bg-gray-100">
        <FlashMessage />

        <!-- Toast notification for new orders -->
        <transition name="slide-down">
            <div v-if="newOrderAlert"
                 class="fixed top-4 right-4 z-50 bg-green-600 text-white px-6 py-3 rounded-lg shadow-xl font-medium flex items-center gap-2">
                <i class="fa-solid fa-bell"></i>
                {{ newOrderAlert }}
            </div>
        </transition>

        <!-- Impersonation banner -->
        <div v-if="impersonating" class="bg-yellow-400 text-yellow-900 px-4 py-2 flex items-center justify-between text-sm font-medium">
            <span>Przeglądasz panel jako manager (tryb podglądu administratora)</span>
            <Link :href="route('tenant.manager.impersonate.stop')" method="post" as="button"
                  class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700">
                Zakończ podgląd
            </Link>
        </div>

        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <h1 class="text-xl font-bold text-blue-600">Panel Managera</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <QuickControls />
                        <span class="text-sm text-gray-600">{{ auth?.name }}</span>
                        <a :href="route('tenant.menu')" target="_blank" class="text-gray-600 hover:text-gray-900 text-sm flex items-center gap-1">
                            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                            Podgląd strony
                        </a>
                        <Link
                            :href="route('tenant.logout')"
                            method="post"
                            as="button"
                            class="text-red-600 hover:text-red-700 text-sm font-medium"
                        >
                            Wyloguj
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex">
            <!-- Sidebar Navigation -->
            <aside class="w-64 bg-white shadow-sm min-h-screen">
                <nav class="mt-5 px-4">
                    <div class="space-y-1">
                        <Link
                            :href="route('tenant.manager.dashboard')"
                            :class="[
                                isActive('dashboard')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-chart-line mr-3 w-5 text-center text-indigo-500"></i>
                            Dashboard
                        </Link>

                        <Link
                            :href="route('tenant.manager.menu.index')"
                            :class="[
                                isActive('menu')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-utensils mr-3 w-5 text-center text-orange-400"></i>
                            Zarządzanie Menu
                        </Link>

                        <Link
                            :href="route('tenant.manager.orders.index')"
                            :class="[
                                isActive('orders')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-box mr-3 w-5 text-center text-indigo-400"></i>
                            <span class="flex-1">Zamówienia</span>
                            <span v-if="newOrderCount > 0"
                                  class="ml-2 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1 animate-pulse">
                                {{ newOrderCount > 9 ? '9+' : newOrderCount }}
                            </span>
                            <span v-if="hasNewOrders && newOrderCount === 0"
                                  class="ml-2 bg-orange-500 text-white text-xs font-bold rounded px-1.5 py-0.5 animate-pulse">
                                NOWE
                            </span>
                        </Link>

                        <Link
                            :href="route('tenant.manager.customers.index')"
                            :class="[
                                isActive('customers')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-address-book mr-3 w-5 text-center text-indigo-500"></i>
                            Klienci
                        </Link>

                        <Link
                            :href="route('tenant.manager.staff.index')"
                            :class="[
                                isActive('staff')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-users mr-3 w-5 text-center text-indigo-500"></i>
                            Pracownicy
                        </Link>

                        <Link
                            :href="route('tenant.manager.delivery-zones.index')"
                            :class="[
                                isActive('delivery-zones')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-map mr-3 w-5 text-center text-green-600"></i>
                            Strefy Dostawy
                        </Link>

                        <Link
                            :href="route('tenant.manager.discounts.index')"
                            :class="[
                                isActive('discounts')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-tag mr-3 w-5 text-center text-purple-500"></i>
                            Kody Rabatowe
                        </Link>

                        <Link
                            :href="route('tenant.manager.reviews.index')"
                            :class="[
                                isActive('reviews')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-star mr-3 w-5 text-center text-yellow-500"></i>
                            <span class="flex-1">Opinie</span>
                            <span v-if="newReviewCount > 0"
                                  class="ml-2 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1 animate-pulse">
                                {{ newReviewCount > 9 ? '9+' : newReviewCount }}
                            </span>
                        </Link>

                        <Link
                            :href="route('tenant.manager.loyalty.index')"
                            :class="[
                                isActive('loyalty')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-star mr-3 w-5 text-center text-yellow-400"></i>
                            Program Lojalnościowy
                        </Link>

                        <Link
                            :href="route('tenant.manager.tables.index')"
                            :class="[
                                isActive('tables')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-chair mr-3 w-5 text-center text-amber-600"></i>
                            <span class="flex-1">Stoliki i Rezerwacje</span>
                            <span v-if="pendingReservationsCount > 0"
                                  class="ml-2 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1">
                                {{ pendingReservationsCount > 9 ? '9+' : pendingReservationsCount }}
                            </span>
                        </Link>

                        <Link
                            :href="route('tenant.manager.reports.index')"
                            :class="[
                                isActive('reports')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-chart-bar mr-3 w-5 text-center text-indigo-500"></i>
                            Raporty
                        </Link>

                        <Link
                            :href="route('tenant.manager.staff-reports.index')"
                            :class="[
                                isActive('staff-reports')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-flag mr-3 w-5 text-center text-red-500"></i>
                            <span class="flex-1">Raporty pracowników</span>
                            <span v-if="newStaffReportCount > 0"
                                  class="ml-2 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1 animate-pulse">
                                {{ newStaffReportCount > 9 ? '9+' : newStaffReportCount }}
                            </span>
                        </Link>

                        <Link
                            :href="route('tenant.manager.role-permissions.index')"
                            :class="[
                                isActive('role-permissions')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-shield-halved mr-3 w-5 text-center text-blue-600"></i>
                            Uprawnienia ról
                        </Link>

                        <div class="border-t border-gray-200 my-3"></div>
                        <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Panele pracowników</p>

                        <a
                            :href="route('tenant.staff.kitchen')"
                            target="_blank"
                            class="border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors"
                        >
                            <i class="fa-solid fa-kitchen-set mr-3 w-5 text-center text-orange-500"></i>
                            Kuchnia
                        </a>

                        <a
                            :href="route('tenant.staff.waiter')"
                            target="_blank"
                            class="border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors"
                        >
                            <i class="fa-solid fa-utensils mr-3 w-5 text-center text-orange-400"></i>
                            Kelner / POS
                        </a>

                        <a
                            :href="route('tenant.staff.driver')"
                            target="_blank"
                            class="border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors"
                        >
                            <i class="fa-solid fa-truck mr-3 w-5 text-center text-blue-600"></i>
                            Kierowca
                        </a>

                        <Link
                            v-if="$page.props.app_version === 'test'"
                            :href="route('tenant.manager.marketing.index')"
                            :class="[
                                isActive('marketing')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-bullhorn mr-3 w-5 text-center text-pink-500"></i>
                            Marketing
                        </Link>

                        <div class="border-t border-gray-200 my-3"></div>

                        <Link
                            :href="route('tenant.manager.support.index')"
                            :class="[
                                isActive('support')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-headset mr-3 w-5 text-center text-blue-500"></i>
                            Wsparcie techniczne
                            <span v-if="newSupportCount > 0"
                                class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full min-w-[20px] text-center">
                                {{ newSupportCount }}
                            </span>
                        </Link>

                        <Link
                            :href="route('tenant.manager.license')"
                            :class="[
                                isActive('license')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-id-card mr-3 w-5 text-center text-indigo-500"></i>
                            Licencja
                        </Link>

                        <div class="border-t border-gray-200 my-3"></div>

                        <Link
                            :href="route('tenant.manager.settings.index')"
                            :class="[
                                isActive('settings')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-gear mr-3 w-5 text-center text-gray-500"></i>
                            Ustawienia
                        </Link>
                    </div>

                    <div class="mt-6 px-4">
                        <button @click="testSound" class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-400 hover:text-gray-600 border border-dashed border-gray-200 rounded-lg transition-colors">
                            <i class="fa-solid fa-volume-high w-4 text-center"></i>
                            Test dźwięku
                        </button>
                    </div>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 p-8">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-20px); }
</style>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import FlashMessage from '@/Components/FlashMessage.vue'
import QuickControls from '@/Components/QuickControls.vue'

const props = defineProps({ title: { type: String, default: '' } })
const { title } = props
const page = usePage()
const auth = page.props.auth?.user
const impersonating = computed(() => page.props.impersonating)

const newOrderAlert = ref(null)
const newOrderCount = ref(0)
const hasNewOrders = ref(false)
const newSupportCount = ref(0)
const newStaffReportCount = ref(0)
const newReviewCount = ref(0)

const pendingReservationsCount = computed(() => page.props.pendingReservationsCount || 0)
let echo = null
let newOrderTimer = null
let heartbeatTimer = null
let pollInterval = null

const updateTitle = () => {
    const total = newOrderCount.value + newSupportCount.value + newStaffReportCount.value + pendingReservationsCount.value + newReviewCount.value
    const base = document.title.replace(/^\(\d+\)\s*/, '')
    document.title = total > 0 ? `(${total}) ${base}` : base
}

const startPolling = () => {
    if (pollInterval) return
    pollInterval = setInterval(() => {
        if (page.url.includes('/manager/orders')) {
            router.reload({ only: ['orders'] })
        }
    }, 60000)
}

const stopPolling = () => {
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null }
}

// Web Audio API — no file needed, no autoplay issues
let audioCtx = null

const getAudioCtx = () => {
    if (!audioCtx) audioCtx = new AudioContext()
    return audioCtx
}

const playNotification = () => {
    try {
        const ctx = getAudioCtx()
        const osc = ctx.createOscillator()
        const gain = ctx.createGain()
        osc.connect(gain)
        gain.connect(ctx.destination)
        osc.type = 'sine'
        osc.frequency.setValueAtTime(880, ctx.currentTime)
        osc.frequency.setValueAtTime(660, ctx.currentTime + 0.1)
        gain.gain.setValueAtTime(0.4, ctx.currentTime)
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4)
        osc.start(ctx.currentTime)
        osc.stop(ctx.currentTime + 0.4)
    } catch {}
}

const testSound = () => playNotification()

const isActive = (section) => {
    const url = page.url
    if (section === 'dashboard') return url === '/manager' || url === '/manager/'
    return url.includes(`/manager/${section}`)
}

// Clear badge when user navigates to relevant page
watch(() => page.url, (url) => {
    if (url.includes('/manager/orders')) {
        newOrderCount.value = 0
        hasNewOrders.value = false
        if (newOrderTimer) { clearTimeout(newOrderTimer); newOrderTimer = null }
        updateTitle()
    }
    if (url.includes('/manager/support')) {
        newSupportCount.value = 0
        updateTitle()
    }
    if (url.includes('/manager/staff-reports')) {
        newStaffReportCount.value = 0
        updateTitle()
    }
    if (url.includes('/manager/reviews')) {
        newReviewCount.value = 0
        updateTitle()
    }
    if (url.includes('/manager/tables') || url.includes('/manager/reservations')) {
        router.reload({ only: ['pendingReservationsCount'] })
    }
})

watch(pendingReservationsCount, updateTitle)

onMounted(() => {
    updateTitle()
    window.Pusher = Pusher
    if (!window.Echo) {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            auth: {
                headers: {
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
                },
            },
        })
    }
    echo = window.Echo.private('orders')
        .listen('.order.created', (e) => {
            playNotification()
            const orderNum = e.order?.order_number || ''
            newOrderAlert.value = `Nowe zamówienie: #${orderNum}!`
            setTimeout(() => { newOrderAlert.value = null }, 5000)

            if (page.url.includes('/manager/orders')) {
                hasNewOrders.value = true
                if (newOrderTimer) clearTimeout(newOrderTimer)
                newOrderTimer = setTimeout(() => { hasNewOrders.value = false }, 5 * 60 * 1000)
                router.reload({ only: ['orders'] })
            } else {
                newOrderCount.value++
                updateTitle()
            }
        })

    const tenantId = page.props.tenant?.id
    if (tenantId) {
        window.Echo.private(`support.${tenantId}`)
            .listen('.support.message', (e) => {
                playNotification()
                newOrderAlert.value = `Odpowiedź od wsparcia: ${e.subject}`
                setTimeout(() => { newOrderAlert.value = null }, 7000)
                if (page.url.includes('/manager/support')) {
                    router.reload()
                } else {
                    newSupportCount.value++
                    updateTitle()
                }
            })
    }

    window.Echo.private('staff-reports')
        .listen('.staff-report.created', (e) => {
            playNotification()
            const title = e.report?.title || 'Nowy raport'
            const name = e.report?.staff_user?.name || ''
            newOrderAlert.value = `Raport od ${name}: ${title}`
            setTimeout(() => { newOrderAlert.value = null }, 6000)
            if (page.url.includes('/manager/staff-reports')) {
                router.reload({ only: ['reports', 'newCount'] })
            } else {
                newStaffReportCount.value++
                updateTitle()
            }
        })

    window.Echo.private('reviews-manager')
        .listen('.review.created', (e) => {
            playNotification()
            newOrderAlert.value = `Nowa opinia od ${e.author_name} (${e.rating}★)`
            setTimeout(() => { newOrderAlert.value = null }, 6000)
            if (page.url.includes('/manager/reviews')) {
                router.reload()
            } else {
                newReviewCount.value++
                updateTitle()
            }
        })

    window.Echo.private('reservations-manager')
        .listen('.reservation.created', (e) => {
            playNotification()
            const r = e.reservation
            const date = r.reservation_date ? new Date(r.reservation_date).toLocaleDateString('pl-PL') : ''
            newOrderAlert.value = `Nowa rezerwacja: ${r.customer_name}, ${date} ${r.reservation_time}, ${r.party_size} os.`
            setTimeout(() => { newOrderAlert.value = null }, 8000)
            if (page.url.includes('/manager/tables') || page.url.includes('/manager/reservations')) {
                router.reload({ only: ['reservations', 'pendingCount'] })
            }
            router.reload({ only: ['pendingReservationsCount'] })
        })

    window.Echo.connector?.pusher?.connection?.bind('connected', stopPolling)
    window.Echo.connector?.pusher?.connection?.bind('unavailable', startPolling)
    window.Echo.connector?.pusher?.connection?.bind('disconnected', startPolling)

    // Po deployu OPcache Apache może serwować stare pliki config/route przez chwilę,
    // przez co sesja jest nieczytelna i broadcasting/auth zwraca 403.
    // Wykryj to i przeładuj stronę raz (sessionStorage zapobiega pętli).
    window.Echo.connector?.pusher?.bind_global?.((eventName, data) => {
        if (eventName === 'pusher:subscription_error') {
            const status = typeof data === 'number' ? data : (data?.status ?? data?.error?.status)
            if (status === 403) {
                const reloadKey = 'broadcast_auth_reload'
                if (!sessionStorage.getItem(reloadKey)) {
                    sessionStorage.setItem(reloadKey, '1')
                    // Krótkie opóźnienie — daj czas OPcache na wygaśnięcie
                    setTimeout(() => window.location.reload(), 1500)
                }
            }
        }
    })
    // Po udanym reconnect czyść flagę (deploy już się zakończył)
    window.Echo.connector?.pusher?.connection?.bind('connected', () => {
        sessionStorage.removeItem('broadcast_auth_reload')
    })

    // Odświeżaj sesję HTTP co 10 minut (WebSocket nie aktualizuje last_activity)
    heartbeatTimer = setInterval(() => {
        fetch('/up', { credentials: 'same-origin' }).catch(() => {})
    }, 10 * 60 * 1000)
})

onUnmounted(() => {
    if (newOrderTimer) clearTimeout(newOrderTimer)
    if (heartbeatTimer) clearInterval(heartbeatTimer)
    if (echo) window.Echo?.leave('orders')
    stopPolling()
})
</script>
