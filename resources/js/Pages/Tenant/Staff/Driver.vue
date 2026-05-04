<template>
    <StaffLayout panelTitle="Panel Kierowcy">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Panel Kierowcy</h1>
                    <p class="text-gray-500 mt-1">Zarządzanie dostawami</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="bg-white border border-gray-200 shadow-sm rounded-lg px-4 py-2 text-center">
                        <p class="text-xs text-gray-500">Moje dostawy</p>
                        <p class="text-2xl font-bold text-blue-600">{{ localMyDeliveries.length }}</p>
                    </div>
                    <div class="bg-white border border-gray-200 shadow-sm rounded-lg px-4 py-2 text-center">
                        <p class="text-xs text-gray-500">Dostępne</p>
                        <p class="text-2xl font-bold text-green-600">{{ localAvailableDeliveries.length }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Deliveries -->
        <div v-if="localMyDeliveries.length > 0" class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Moje dostawy w trakcie</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="order in localMyDeliveries"
                    :key="order.id"
                    class="bg-white rounded-lg shadow border-l-4 border-blue-500 p-4 space-y-3"
                >
                    <!-- Order Header -->
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">#{{ order.order_number }}</h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                W drodze
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-blue-600">{{ formatElapsedTime(order.created_at) }}</p>
                            <p class="text-xs text-gray-400">{{ formatTime(order.created_at) }}</p>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="bg-gray-50 rounded-lg p-3 space-y-1">
                        <p class="font-semibold text-gray-900">{{ order.customer_name }}</p>
                        <a :href="`tel:${order.customer_phone}`" class="text-blue-600 hover:text-blue-700 text-sm flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            {{ order.customer_phone }}
                        </a>
                        <p class="text-sm text-gray-600 flex items-start gap-1">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ order.delivery_address }}
                        </p>
                    </div>

                    <!-- Order Items -->
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Zamówienie</p>
                        <div class="space-y-0.5">
                            <div v-for="item in order.items" :key="item.id" class="text-sm text-gray-700">
                                <span class="font-semibold">{{ item.quantity }}×</span> {{ item.name }}
                                <span v-if="item.variant_name" class="text-gray-400"> ({{ item.variant_name }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total + Payment -->
                    <div class="flex justify-between items-center bg-gray-50 rounded-lg px-3 py-2">
                        <div>
                            <span v-if="order.payment_status === 'paid'" class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-100 px-2 py-0.5 rounded-full">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Opłacone online
                            </span>
                            <span v-else class="inline-flex items-center text-xs font-medium text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">
                                Płatność przy odbiorze
                            </span>
                        </div>
                        <p v-if="order.payment_status === 'paid'" class="text-lg font-bold text-green-700">Opłacono</p>
                        <p v-else class="text-lg font-bold text-gray-900">{{ formatPrice(order.total) }} zł</p>
                    </div>

                    <!-- Notes -->
                    <div v-if="order.notes" class="bg-amber-50 border border-amber-200 rounded-lg p-2.5 text-sm text-amber-800">
                        {{ order.notes }}
                    </div>

                    <!-- GPS Sharing (only when tracking enabled in settings) -->
                    <template v-if="props.driverTrackingEnabled">
                        <button
                            v-if="!gpsActiveOrders.has(order.id)"
                            @click="startGpsSharing(order)"
                            class="w-full py-2 px-3 border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg text-sm font-medium transition-colors"
                        >
                            Włącz śledzenie GPS
                        </button>
                        <button
                            v-else
                            @click="stopGpsSharing(order.id)"
                            class="w-full py-2 px-3 border border-amber-400 bg-amber-100 hover:bg-amber-200 text-amber-900 rounded-lg text-sm font-medium transition-colors"
                        >
                            Wyłącz śledzenie GPS
                        </button>
                    </template>

                    <!-- Navigation -->
                    <a
                        :href="getNavigationUrl(order.delivery_address)"
                        target="_blank"
                        class="w-full block text-center py-2 px-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium text-sm transition-colors"
                    >
                        Nawiguj (Google Maps)
                    </a>

                    <!-- Complete -->
                    <button
                        @click="completeDelivery(order)"
                        class="w-full py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-sm transition-colors"
                    >
                        Oznacz jako dostarczone
                    </button>
                </div>
            </div>
        </div>

        <!-- Available Deliveries -->
        <div v-if="localAvailableDeliveries.length > 0" class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Dostępne dostawy do odbioru</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="order in localAvailableDeliveries"
                    :key="order.id"
                    class="bg-white rounded-lg shadow border-l-4 border-green-500 p-4 space-y-3"
                >
                    <!-- Order Header -->
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">#{{ order.order_number }}</h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                Gotowe do odbioru
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-green-600">{{ formatElapsedTime(order.created_at) }}</p>
                            <p class="text-xs text-gray-400">{{ formatTime(order.created_at) }}</p>
                        </div>
                    </div>

                    <!-- Delivery Info -->
                    <div class="bg-gray-50 rounded-lg p-3 space-y-1">
                        <p class="font-semibold text-gray-900">{{ order.customer_name }}</p>
                        <p class="text-sm text-gray-600 flex items-start gap-1">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ order.delivery_address }}
                        </p>
                        <p v-if="order.delivery_zone" class="text-xs text-gray-400">
                            Strefa: {{ order.delivery_zone.name }}
                        </p>
                    </div>

                    <!-- Order Items -->
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Zamówienie ({{ order.items.length }} poz.)</p>
                        <div class="space-y-0.5">
                            <div v-for="item in order.items.slice(0, 3)" :key="item.id" class="text-sm text-gray-700">
                                <span class="font-semibold">{{ item.quantity }}×</span> {{ item.name }}
                            </div>
                            <p v-if="order.items.length > 3" class="text-xs text-gray-400">
                                i {{ order.items.length - 3 }} więcej...
                            </p>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="flex justify-between items-center bg-gray-50 rounded-lg px-3 py-2">
                        <p class="text-xs text-gray-500">Wartość zamówienia</p>
                        <p v-if="order.payment_status === 'paid'" class="text-lg font-bold text-green-700">Opłacono</p>
                        <p v-else class="text-lg font-bold text-gray-900">{{ formatPrice(order.total) }} zł</p>
                    </div>

                    <!-- Assign -->
                    <button
                        @click="assignToMe(order)"
                        class="w-full py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-sm transition-colors"
                    >
                        Przypisz do mnie
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="localMyDeliveries.length === 0 && localAvailableDeliveries.length === 0"
             class="bg-white rounded-lg shadow p-12 text-center">
            <div class="text-5xl mb-3"><i class="fa-solid fa-sparkles text-yellow-400"></i></div>
            <p class="text-xl font-semibold text-gray-700">Brak dostaw do realizacji</p>
            <p class="text-gray-400 mt-1 text-sm">Nowe dostawy pojawią się tutaj automatycznie</p>
        </div>

        <!-- Confirm complete delivery modal -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="confirmModal.open"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4"
                     @click.self="confirmModal.open = false">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-circle-check text-3xl text-blue-600"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 mb-1">Potwierdzenie dostawy</h2>
                        <p class="text-gray-500 text-sm mb-1">Zamówienie <span class="font-semibold text-gray-700">#{{ confirmModal.order?.order_number }}</span></p>
                        <p class="text-gray-500 text-sm mb-6">{{ confirmModal.order?.delivery_address }}</p>
                        <div class="flex gap-3">
                            <button
                                @click="confirmModal.open = false"
                                class="flex-1 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
                            >
                                Anuluj
                            </button>
                            <button
                                @click="confirmComplete"
                                class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold transition-colors"
                            >
                                Dostarczone
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </StaffLayout>
</template>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-fade-enter-active .relative, .modal-fade-leave-active .relative { transition: transform 0.2s ease; }
.modal-fade-enter-from .relative, .modal-fade-leave-to .relative { transform: scale(0.95); }
</style>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const myUserId = usePage().props.auth?.user?.id
const props = defineProps({
    myDeliveries: Array,
    availableDeliveries: Array,
    driverTrackingEnabled: { type: Boolean, default: false },
})

const localMyDeliveries = ref([...props.myDeliveries])
const localAvailableDeliveries = ref([...props.availableDeliveries])
const confirmModal = ref({ open: false, order: null })

// Sync local state when Inertia reloads props after POST
// Auto-start GPS for newly assigned orders when tracking is enabled
watch(() => props.myDeliveries, (val) => {
    if (props.driverTrackingEnabled) {
        const prevIds = new Set(localMyDeliveries.value.map(o => o.id))
        val.forEach(order => {
            if (!prevIds.has(order.id) && !gpsActiveOrders.value.has(order.id)) {
                startGpsSharing(order)
            }
        })
    }
    localMyDeliveries.value = [...val]
})
watch(() => props.availableDeliveries, (val) => { localAvailableDeliveries.value = [...val] })

// GPS tracking state: Map<orderId, watchId>
const gpsActiveOrders = ref(new Set())
const gpsWatchIds = new Map()

const startGpsSharing = (order) => {
    if (!navigator.geolocation) {
        alert('Twoja przeglądarka nie obsługuje GPS.')
        return
    }
    const watchId = navigator.geolocation.watchPosition(
        (position) => {
            axios.post(route('tenant.staff.driver.location', order.id), {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
            }).catch(() => {})
        },
        () => {},
        { enableHighAccuracy: true, maximumAge: 10000, timeout: 15000 }
    )
    gpsWatchIds.set(order.id, watchId)
    gpsActiveOrders.value = new Set([...gpsActiveOrders.value, order.id])
}

const stopGpsSharing = (orderId) => {
    const watchId = gpsWatchIds.get(orderId)
    if (watchId !== undefined) {
        navigator.geolocation.clearWatch(watchId)
        gpsWatchIds.delete(orderId)
    }
    const next = new Set(gpsActiveOrders.value)
    next.delete(orderId)
    gpsActiveOrders.value = next
}

const formatTime = (datetime) => {
    return new Date(datetime).toLocaleTimeString('pl-PL', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatElapsedTime = (created_at) => {
    const now = new Date()
    const created = new Date(created_at)
    const diff = Math.floor((now - created) / 1000 / 60) // minutes

    if (diff < 1) return '< 1 min'
    if (diff < 60) return `${diff} min`

    const hours = Math.floor(diff / 60)
    const minutes = diff % 60
    return `${hours}h ${minutes}m`
}

const formatPrice = (price) => {
    return parseFloat(price).toFixed(2)
}

const getNavigationUrl = (address) => {
    // Google Maps navigation URL
    return `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(address)}`
}

const assignToMe = (order) => {
    router.post(route('tenant.staff.driver.assign', order.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            playNotificationSound()
        }
    })
}

const completeDelivery = (order) => {
    confirmModal.value = { open: true, order }
}

const confirmComplete = () => {
    const order = confirmModal.value.order
    confirmModal.value.open = false
    router.post(route('tenant.staff.driver.complete', order.id), {}, {
        preserveScroll: true,
    })
}

const playNotificationSound = () => {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)()
        const times = [0, 0.18]
        times.forEach(t => {
            const osc = ctx.createOscillator()
            const gain = ctx.createGain()
            osc.connect(gain)
            gain.connect(ctx.destination)
            osc.type = 'sine'
            osc.frequency.value = 880
            gain.gain.setValueAtTime(0.4, ctx.currentTime + t)
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + t + 0.15)
            osc.start(ctx.currentTime + t)
            osc.stop(ctx.currentTime + t + 0.15)
        })
    } catch {}
}

let driverOrdersChannel = null

// Initialize real-time connection — reuse window.Echo created by StaffLayout
onMounted(() => {
    // Listen for order status changes (private channel — same as Kitchen/StaffLayout)
    driverOrdersChannel = window.Echo?.private('orders')
    driverOrdersChannel?.listen('.order.status-changed', (e) => {
            const order = e.order
            const newStatus = e.new_status

            if (order.type !== 'delivery') return

            const myIdx = localMyDeliveries.value.findIndex(o => o.id === order.id)
            const avIdx = localAvailableDeliveries.value.findIndex(o => o.id === order.id)

            // Status not relevant to driver panel → remove from both lists
            if (!['ready', 'on_delivery', 'completed', 'cancelled'].includes(newStatus)) {
                if (myIdx !== -1) localMyDeliveries.value.splice(myIdx, 1)
                if (avIdx !== -1) localAvailableDeliveries.value.splice(avIdx, 1)
                return
            }

            // Terminal: remove from both lists
            if (newStatus === 'completed' || newStatus === 'cancelled') {
                if (myIdx !== -1) localMyDeliveries.value.splice(myIdx, 1)
                if (avIdx !== -1) localAvailableDeliveries.value.splice(avIdx, 1)
                return
            }

            // ready + no driver → available deliveries
            if (newStatus === 'ready' && !order.driver_id) {
                if (myIdx !== -1) localMyDeliveries.value.splice(myIdx, 1)
                if (avIdx === -1) localAvailableDeliveries.value.push(order)
                else localAvailableDeliveries.value[avIdx] = order
                return
            }

            // ready + driver assigned → belongs to myDeliveries (manager pushed it back)
            if (newStatus === 'ready' && order.driver_id) {
                if (avIdx !== -1) localAvailableDeliveries.value.splice(avIdx, 1)
                if (myIdx !== -1) localMyDeliveries.value[myIdx] = order
                else if (order.driver_id === myUserId) localMyDeliveries.value.push(order)
                return
            }

            // on_delivery → move from available to myDeliveries (if mine)
            if (newStatus === 'on_delivery') {
                if (avIdx !== -1) localAvailableDeliveries.value.splice(avIdx, 1)
                if (order.driver_id === myUserId) {
                    if (myIdx !== -1) localMyDeliveries.value[myIdx] = order
                    else localMyDeliveries.value.push(order)
                } else {
                    if (myIdx !== -1) localMyDeliveries.value.splice(myIdx, 1)
                }
                return
            }
        })

    // Update elapsed time every minute
    setInterval(() => {
        localMyDeliveries.value = [...localMyDeliveries.value]
        localAvailableDeliveries.value = [...localAvailableDeliveries.value]
    }, 60000)
})

// Cleanup — stop only Driver-specific listeners, don't disconnect shared window.Echo
onUnmounted(() => {
    driverOrdersChannel?.stopListening('.order.status-changed')
    // Stop all GPS watches
    gpsWatchIds.forEach((watchId) => navigator.geolocation.clearWatch(watchId))
    gpsWatchIds.clear()
})
</script>
