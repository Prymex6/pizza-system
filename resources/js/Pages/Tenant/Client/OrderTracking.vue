<template>
    <div class="min-h-screen bg-gradient-to-br from-orange-50 to-red-50">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b sticky top-0 z-10">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <a :href="route('tenant.menu')" class="text-orange-600 hover:text-orange-700 font-medium flex items-center">
                        ← Powrót do menu
                    </a>
                    <h1 class="text-xl font-bold text-gray-900">Śledzenie zamówienia</h1>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Order Number & Status Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 mb-6">
                <div class="text-center mb-6">
                    <p class="text-gray-600 text-sm mb-2">Numer zamówienia</p>
                    <h2 class="text-3xl font-bold text-gray-900">#{{ localOrder.order_number }}</h2>
                </div>

                <!-- Status Icon -->
                <div class="flex justify-center mb-6">
                    <div :class="[
                        'w-24 h-24 rounded-full flex items-center justify-center text-4xl',
                        getStatusColor()
                    ]">
                        <span v-html="getStatusIcon()"></span>
                    </div>
                </div>

                <!-- Status Text -->
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">
                        {{ getStatusTitle() }}
                    </h3>
                    <p class="text-gray-600">
                        {{ getStatusDescription() }}
                    </p>
                </div>

                <!-- Estimated Time -->
                <div v-if="estimatedDeliveryTime && !isCompleted" class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-center">
                    <p class="text-sm text-gray-600 mb-1">Szacowany czas {{ getDeliveryTypeLabel() }}</p>
                    <p class="text-2xl font-bold text-orange-600">{{ estimatedDeliveryTime }}</p>
                </div>
            </div>

            <!-- Progress Timeline -->
            <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-6">Status realizacji</h3>

                <div class="space-y-4">
                    <!-- Placed -->
                    <div class="flex items-start">
                        <div :class="[
                            'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 mr-4',
                            isStatusReached('pending') ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400'
                        ]">
                            <span v-if="isStatusReached('pending')"><i class="fa-solid fa-check"></i></span>
                            <span v-else>1</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900">Zamówienie złożone</h4>
                            <p class="text-sm text-gray-600">Twoje zamówienie zostało złożone i oczekuje na przyjęcie przez restaurację</p>
                        </div>
                    </div>

                    <!-- Accepted (paid) -->
                    <div class="flex items-start">
                        <div :class="[
                            'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 mr-4',
                            isStatusReached('paid') ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400'
                        ]">
                            <span v-if="isStatusReached('paid')"><i class="fa-solid fa-check"></i></span>
                            <span v-else>2</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900">Zamówienie przyjęte</h4>
                            <p class="text-sm text-gray-600">Restauracja przyjęła Twoje zamówienie do realizacji</p>
                        </div>
                    </div>

                    <!-- Preparing -->
                    <div class="flex items-start">
                        <div :class="[
                            'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 mr-4',
                            isStatusReached('preparing') ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400'
                        ]">
                            <span v-if="isStatusReached('preparing')"><i class="fa-solid fa-check"></i></span>
                            <span v-else>3</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900">Przygotowywanie</h4>
                            <p class="text-sm text-gray-600">Nasza kuchnia przygotowuje Twoje zamówienie</p>
                            <div v-if="localOrder.status === 'preparing'" class="mt-2">
                                <div class="flex items-center text-orange-600">
                                    <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                                    <span class="text-sm font-medium">W trakcie przygotowania...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ready -->
                    <div class="flex items-start">
                        <div :class="[
                            'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 mr-4',
                            isStatusReached('ready') ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400'
                        ]">
                            <span v-if="isStatusReached('ready')"><i class="fa-solid fa-check"></i></span>
                            <span v-else>4</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900">Gotowe</h4>
                            <p class="text-sm text-gray-600">
                                <span v-if="localOrder.type === 'delivery'">Zamówienie gotowe do wydania kierowcy</span>
                                <span v-else-if="localOrder.type === 'pickup'">Zamówienie gotowe do odbioru</span>
                                <span v-else>Zamówienie gotowe do podania</span>
                            </p>
                        </div>
                    </div>

                    <!-- On Delivery / Pickup / Dine-in specific -->
                    <div v-if="localOrder.type === 'delivery'" class="flex items-start">
                        <div :class="[
                            'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 mr-4',
                            isStatusReached('on_delivery') ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400'
                        ]">
                            <span v-if="isStatusReached('on_delivery')"><i class="fa-solid fa-check"></i></span>
                            <span v-else>5</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900">W drodze</h4>
                            <p class="text-sm text-gray-600">Kierowca dostarczy Twoje zamówienie pod wskazany adres</p>
                            <div v-if="localOrder.status === 'on_delivery'" class="mt-2">
                                <div class="flex items-center text-blue-600">
                                    <i class="fa-solid fa-truck-fast mr-2 animate-bounce"></i>
                                    <span class="text-sm font-medium">Zamówienie w drodze do Ciebie...</span>
                                </div>
                            </div>

                            <!-- Live Map -->
                            <div v-if="props.driverTrackingEnabled && localOrder.status === 'on_delivery' && driverLocation" class="mt-3">
                                <p class="text-xs text-gray-500 mb-1">Lokalizacja kierowcy (na żywo):</p>
                                <iframe
                                    :src="mapUrl"
                                    class="w-full rounded-lg border border-gray-200"
                                    style="height: 200px"
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                ></iframe>
                            </div>
                            <div v-else-if="props.driverTrackingEnabled && localOrder.status === 'on_delivery'" class="mt-3 text-xs text-gray-400 italic">
                                Oczekiwanie na sygnał GPS kierowcy...
                            </div>
                        </div>
                    </div>

                    <!-- Completed -->
                    <div class="flex items-start">
                        <div :class="[
                            'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 mr-4',
                            isStatusReached('completed') ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400'
                        ]">
                            <span v-if="isStatusReached('completed')"><i class="fa-solid fa-check"></i></span>
                            <span v-else>{{ localOrder.type === 'delivery' ? '6' : '5' }}</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900">{{ localOrder.type === 'delivery' ? 'Dostarczone' : 'Zakończone' }}</h4>
                            <p class="text-sm text-gray-600">Smacznego! Dziękujemy za zamówienie</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Szczegóły zamówienia</h3>

                <!-- Order Type -->
                <div class="mb-4 pb-4 border-b">
                    <p class="text-sm text-gray-600">Typ zamówienia</p>
                    <p class="font-semibold text-gray-900" v-html="getOrderTypeLabel(localOrder.type)"></p>
                </div>

                <!-- Delivery Address or Table -->
                <div v-if="localOrder.type === 'delivery'" class="mb-4 pb-4 border-b">
                    <p class="text-sm text-gray-600">Adres dostawy</p>
                    <p class="font-semibold text-gray-900">{{ localOrder.delivery_address }}</p>
                </div>
                <div v-else-if="localOrder.type === 'dine_in' && localOrder.table" class="mb-4 pb-4 border-b">
                    <p class="text-sm text-gray-600">Stolik</p>
                    <p class="font-semibold text-gray-900">Stolik {{ localOrder.table.number }}</p>
                </div>

                <!-- Order Items -->
                <div class="mb-4 pb-4 border-b">
                    <p class="text-sm text-gray-600 mb-3">Zamówione pozycje</p>
                    <div class="space-y-3">
                        <div v-for="item in localOrder.items" :key="item.id" class="flex justify-between">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">
                                    {{ item.quantity }}x {{ item.name }}
                                </p>
                                <p v-if="item.variant_name" class="text-sm text-gray-600">
                                    {{ item.variant_name }}
                                </p>
                                <p v-if="item.addons && item.addons.length > 0" class="text-xs text-green-600 mt-1">
                                    + {{ item.addons.map(a => a.name).join(', ') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">{{ formatPrice(item.price * item.quantity) }} zł</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="space-y-2">
                    <div class="flex justify-between text-gray-600">
                        <span>Suma częściowa</span>
                        <span>{{ formatPrice(localOrder.subtotal) }} zł</span>
                    </div>
                    <div v-if="localOrder.delivery_fee > 0" class="flex justify-between text-gray-600">
                        <span>Dostawa</span>
                        <span>{{ formatPrice(localOrder.delivery_fee) }} zł</span>
                    </div>
                    <div v-if="localOrder.discount > 0" class="flex justify-between text-green-600">
                        <span>Rabat</span>
                        <span>-{{ formatPrice(localOrder.discount) }} zł</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t">
                        <span>Razem</span>
                        <span>{{ formatPrice(localOrder.total) }} zł</span>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="bg-orange-50 border border-orange-200 rounded-2xl p-6 text-center">
                <p class="text-gray-700 mb-2">Masz pytania dotyczące zamówienia?</p>
                <p class="text-sm text-gray-600">Skontaktuj się z nami pod numerem:</p>
                <a v-if="restaurantPhone" :href="'tel:' + restaurantPhone" class="text-xl font-bold text-orange-600 hover:text-orange-700">
                    <i class="fa-solid fa-phone mr-1"></i> {{ restaurantPhone }}
                </a>
                <p v-else class="text-xl font-bold text-orange-600"><i class="fa-solid fa-phone mr-1"></i> Zadzwoń do restauracji</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

const props = defineProps({
    order: Object,
    estimatedDeliveryTime: String,
    driverTrackingEnabled: { type: Boolean, default: false },
})

const page = usePage()
const restaurantPhone = computed(() => page.props.tenant?.phone || null)

const localOrder = ref({ ...props.order })
const driverLocation = ref(null)
let echo = null
let pollInterval = null

const mapUrl = computed(() => {
    if (!driverLocation.value) return ''
    const { lat, lng } = driverLocation.value
    const delta = 0.008
    return `https://www.openstreetmap.org/export/embed.html?bbox=${lng - delta},${lat - delta},${lng + delta},${lat + delta}&layer=mapnik&marker=${lat},${lng}`
})

const isCompleted = computed(() => {
    return localOrder.value.status === 'completed' || localOrder.value.status === 'cancelled'
})

const statusOrder = ['pending', 'paid', 'preparing', 'ready', 'on_delivery', 'completed']

const isStatusReached = (status) => {
    const currentIndex = statusOrder.indexOf(localOrder.value.status)
    const checkIndex = statusOrder.indexOf(status)
    return currentIndex >= checkIndex
}

const getStatusColor = () => {
    const colors = {
        'pending': 'bg-gray-100 text-gray-600',
        'paid': 'bg-blue-100 text-blue-600',
        'preparing': 'bg-orange-100 text-orange-600',
        'ready': 'bg-green-100 text-green-600',
        'on_delivery': 'bg-blue-100 text-blue-600',
        'completed': 'bg-green-100 text-green-600',
        'cancelled': 'bg-red-100 text-red-600',
    }
    return colors[localOrder.value.status] || 'bg-gray-100 text-gray-600'
}

const getStatusIcon = () => {
    const icons = {
        'pending': '<i class="fa-solid fa-hourglass-half text-gray-500"></i>',
        'paid': '<i class="fa-solid fa-check text-blue-500"></i>',
        'preparing': '<i class="fa-solid fa-fire-burner text-orange-500"></i>',
        'ready': '<i class="fa-solid fa-pizza-slice text-green-500"></i>',
        'on_delivery': '<i class="fa-solid fa-car text-blue-500"></i>',
        'completed': '<i class="fa-solid fa-champagne-glasses text-purple-500"></i>',
        'cancelled': '<i class="fa-solid fa-xmark text-red-500"></i>',
    }
    return icons[localOrder.value.status] || '<i class="fa-solid fa-box text-gray-500"></i>'
}

const getStatusTitle = () => {
    const titles = {
        'pending': localOrder.value.payment_status === 'awaiting_payment'
            ? 'Oczekiwanie na płatność'
            : 'Zamówienie złożone',
        'paid': 'Zamówienie przyjęte',
        'preparing': 'Przygotowujemy Twoje zamówienie',
        'ready': 'Zamówienie gotowe',
        'on_delivery': 'Zamówienie w drodze',
        'completed': 'Zamówienie dostarczone',
        'cancelled': 'Zamówienie anulowane',
    }
    return titles[localOrder.value.status] || 'Status nieznany'
}

const getStatusDescription = () => {
    const descriptions = {
        'pending': localOrder.value.payment_status === 'awaiting_payment'
            ? 'Czekamy na potwierdzenie płatności online'
            : 'Twoje zamówienie zostało złożone i oczekuje na potwierdzenie przez restaurację',
        'paid': 'Rozpoczynamy przygotowanie Twojego zamówienia',
        'preparing': 'Nasi kucharze już pracują nad Twoim zamówieniem',
        'ready': localOrder.value.type === 'delivery'
            ? 'Zamówienie gotowe, wkrótce zostanie przekazane kierowcy'
            : localOrder.value.type === 'pickup'
            ? 'Zamówienie gotowe do odbioru w restauracji'
            : 'Zamówienie gotowe do podania',
        'on_delivery': 'Kierowca jest w drodze z Twoim zamówieniem',
        'completed': 'Dziękujemy za zamówienie! Smacznego!',
        'cancelled': 'Zamówienie zostało anulowane',
    }
    return descriptions[localOrder.value.status] || ''
}

const getOrderTypeLabel = (type) => {
    const labels = {
        'delivery': '<i class="fa-solid fa-truck mr-1 text-blue-500"></i> Dostawa',
        'pickup': '<i class="fa-solid fa-person-running mr-1 text-green-500"></i> Odbiór osobisty',
        'dine_in': '<i class="fa-solid fa-chair mr-1 text-amber-600"></i> Na miejscu'
    }
    return labels[type] || type
}

const getDeliveryTypeLabel = () => {
    if (localOrder.value.type === 'delivery') return 'dostawy'
    if (localOrder.value.type === 'pickup') return 'odbioru'
    return 'realizacji'
}

const formatPrice = (price) => {
    return parseFloat(price).toFixed(2)
}

// Polling fallback – refreshes order data every 30s when WebSocket not connected
const startPolling = () => {
    if (pollInterval) return
    pollInterval = setInterval(() => {
        if (isCompleted.value) {
            clearInterval(pollInterval)
            pollInterval = null
            return
        }
        router.reload({ only: ['order'] })
    }, 30000)
}

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval)
        pollInterval = null
    }
}

// Initialize real-time connection
onMounted(() => {
    // Always start polling as a safety net
    startPolling()

    if (!import.meta.env.VITE_REVERB_APP_KEY) return

    try {
        window.Pusher = Pusher

        echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        })

        echo.channel('order.' + localOrder.value.order_number)
            .listen('.order.status-changed', (e) => {
                localOrder.value = { ...e.order }
            })
            .listen('.driver.location-updated', (e) => {
                if (props.driverTrackingEnabled) {
                    driverLocation.value = { lat: e.latitude, lng: e.longitude }
                }
            })

        // When WebSocket connects, stop polling; when it drops, resume polling
        echo.connector.pusher.connection.bind('connected', () => stopPolling())
        echo.connector.pusher.connection.bind('disconnected', () => startPolling())
        echo.connector.pusher.connection.bind('unavailable', () => startPolling())
    } catch (e) {
        // Real-time unavailable, polling covers tracking updates
    }
})

// Cleanup
onUnmounted(() => {
    stopPolling()
    if (echo) {
        echo.disconnect()
    }
})
</script>
