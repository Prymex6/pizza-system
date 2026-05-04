<template>
    <StaffLayout panelTitle="Panel Kuchni (KDS)">
        <!-- WebSocket disconnect warning -->
        <div v-if="!wsConnected" class="mb-4 p-3 bg-red-100 border border-red-400 rounded-lg flex items-center gap-2 text-red-800">
            <i class="fa-solid fa-triangle-exclamation text-amber-500 text-xl"></i>
            <span class="font-semibold">Brak połączenia real-time! Nowe zamówienia mogą nie docierać. Sprawdź połączenie sieciowe lub odśwież stronę.</span>
        </div>

        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><i class="fa-solid fa-fire-burner mr-2 text-orange-500"></i> Panel Kuchni (KDS)</h1>
                    <p class="text-gray-500 mt-1">Aktywne zamówienia w czasie rzeczywistym</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="bg-white border border-gray-200 shadow-sm rounded-lg px-4 py-2 text-center">
                        <p class="text-xs text-gray-500">Aktywnych zamówień</p>
                        <p class="text-2xl font-bold text-blue-600">{{ orders.length }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div
                v-for="order in sortedOrders"
                :key="order.id"
                :class="[
                    'bg-white rounded-lg shadow border-l-4 p-4 space-y-3 transition-all duration-300',
                    getOrderBorderClass(order),
                    order.isNew ? 'animate-pulse shadow-lg shadow-blue-500/30' : ''
                ]"
            >
                <!-- Order Header -->
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">#{{ order.order_number }}</h3>
                        <p class="text-sm text-gray-500">
                            {{ getOrderTypeLabel(order.type) }}
                            <span v-if="order.table" class="ml-1 font-semibold text-yellow-600">— stolik {{ order.table.number }}</span>
                        </p>
                    </div>
                    <div class="text-right">
                        <p :class="getTimerClass(order)" class="text-lg font-mono font-bold">
                            {{ formatElapsedTime(order.created_at) }}
                        </p>
                        <p class="text-xs text-gray-400">{{ formatTime(order.created_at) }}</p>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="bg-gray-50 rounded-lg p-3 space-y-1.5 max-h-48 overflow-y-auto">
                    <p class="text-xs font-medium text-gray-500 uppercase mb-1">Pozycje</p>
                    <div
                        v-for="item in order.items"
                        :key="item.id"
                    >
                        <p class="text-sm font-semibold text-gray-800">{{ item.quantity }}× {{ item.name }}</p>
                        <p v-if="item.variant_name" class="text-xs text-gray-500">{{ item.variant_name }}</p>
                        <p v-if="item.addons && item.addons.length > 0" class="text-xs text-green-700">
                            + {{ item.addons.map(a => a.name).join(', ') }}
                        </p>
                        <p v-if="item.exclusions && item.exclusions.length > 0" class="text-xs text-red-600 font-medium">
                            BEZ: {{ item.exclusions.join(', ') }}
                        </p>
                        <p v-if="item.notes" class="text-xs text-amber-700 italic">
                            <i class="fa-solid fa-comment mr-1 text-amber-600"></i> {{ item.notes }}
                        </p>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="text-sm text-gray-700">
                    <p><span class="text-gray-400">Klient:</span> <span class="font-semibold">{{ order.customer_name }}</span></p>
                    <p><span class="text-gray-400">Telefon:</span> {{ formatPhone(order.customer_phone) }}</p>
                    <p v-if="order.delivery_address" class="text-xs text-gray-500 mt-0.5">
                        <i class="fa-solid fa-location-dot mr-1 text-red-500"></i> {{ order.delivery_address }}
                    </p>
                </div>

                <!-- Notes -->
                <div v-if="order.notes" class="bg-amber-50 border border-amber-200 rounded-lg p-2.5 text-sm text-amber-800">
                    <i class="fa-solid fa-comment mr-1 text-amber-600"></i> {{ order.notes }}
                </div>

                <!-- Action Buttons -->
                <div class="space-y-2">
                    <button
                        v-if="order.status === 'paid'"
                        @click="updateOrderStatus(order, 'preparing')"
                        class="w-full py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-sm transition-colors"
                    >
                        <i class="fa-solid fa-play mr-1"></i> Rozpocznij przygotowanie
                    </button>
                    <button
                        v-else-if="order.status === 'preparing'"
                        @click="updateOrderStatus(order, 'ready')"
                        class="w-full py-2 px-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium text-sm transition-colors"
                    >
                        <i class="fa-solid fa-check mr-1"></i> Oznacz jako gotowe
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="orders.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
            <div class="text-5xl mb-3"><i class="fa-solid fa-sparkles text-yellow-400"></i></div>
            <p class="text-xl font-semibold text-gray-700">Brak aktywnych zamówień</p>
            <p class="text-gray-400 mt-1 text-sm">Nowe zamówienia pojawią się tutaj automatycznie</p>
        </div>

        <!-- Audio placeholder (sound generated via Web Audio API) -->
    </StaffLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { formatPhone } from '@/utils/phone'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({
    orders: Array,
})

const notificationSound = ref(null) // kept for compatibility
const localOrders = ref([...props.orders])
const wsConnected = ref(true)
let kitchenChannel = null
let timerInterval = null

watch(() => props.orders, (newOrders) => {
    localOrders.value = [...newOrders]
})

// Sorted orders - najstarsze pierwsze, ale nowe na początku
const sortedOrders = computed(() => {
    return [...localOrders.value].sort((a, b) => {
        // Nowe zamówienia zawsze na początku
        if (a.isNew && !b.isNew) return -1
        if (!a.isNew && b.isNew) return 1

        // Status priority: paid (przyjęte) > preparing
        const statusPriority = { 'paid': 0, 'preparing': 1 }
        const aPriority = statusPriority[a.status] || 999
        const bPriority = statusPriority[b.status] || 999

        if (aPriority !== bPriority) {
            return aPriority - bPriority
        }

        // Starsze pierwsze (w ramach tego samego statusu)
        return new Date(a.created_at) - new Date(b.created_at)
    })
})

const getOrderBorderClass = (order) => {
    if (order.isNew) return 'border-blue-500'

    const statusColors = {
        'paid': 'border-yellow-500',
        'preparing': 'border-blue-500',
    }
    return statusColors[order.status] || 'border-gray-300'
}

const getOrderTypeLabel = (type) => {
    const labels = {
        'delivery': 'Dostawa',
        'pickup': 'Odbiór',
        'dine_in': 'Na miejscu'
    }
    return labels[type] || type
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

const getTimerClass = (order) => {
    const created = new Date(order.created_at)
    const elapsed = (new Date() - created) / 1000 / 60 // minutes

    if (elapsed > 30) return 'text-red-600'
    if (elapsed > 20) return 'text-amber-600'
    return 'text-green-600'
}

const playBeep = () => {
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
    } catch (e) {
        console.error('Cannot play sound:', e)
    }
}

const playTestSound = () => {
    playBeep()
}

const playNotificationSound = () => {
    playBeep()
}

const updateOrderStatus = (order, newStatus) => {
    router.patch(route('tenant.staff.kitchen.update-status', order.id), {
        status: newStatus
    }, {
        preserveState: false,
        preserveScroll: true,
    })
}

// Initialize real-time connection — reuse window.Echo created by StaffLayout
onMounted(() => {
    // Monitor connection state
    const connection = window.Echo?.connector?.pusher?.connection
    if (connection) {
        connection.bind('disconnected', () => { wsConnected.value = false })
        connection.bind('connected', () => { wsConnected.value = true })
        connection.bind('unavailable', () => { wsConnected.value = false })
    }

    // Listen for new and updated orders (private channel – authenticated staff)
    const kitchenStatuses = ['paid', 'preparing']

    kitchenChannel = window.Echo?.private('orders')
        ?.listen('.order.created', (e) => {
            // Kuchnia ignoruje nowe zamówienia — pojawią się dopiero po akceptacji managera
        })
        ?.listen('.order.status-changed', (e) => {
            const orderIndex = localOrders.value.findIndex(o => o.id === e.order.id)
            const isKitchenStatus = kitchenStatuses.includes(e.new_status)

            if (orderIndex !== -1) {
                // Zamówienie już widoczne — usuń jeśli wychodzi poza kuchnię, inaczej aktualizuj
                if (!isKitchenStatus) {
                    localOrders.value.splice(orderIndex, 1)
                } else {
                    localOrders.value[orderIndex] = { ...e.order, isNew: false }
                }
            } else if (isKitchenStatus) {
                // Zamówienie pojawia się w kuchni po raz pierwszy (np. zmiana na 'accepted')
                localOrders.value.push({ ...e.order, isNew: true })
                playNotificationSound()
                setTimeout(() => {
                    const order = localOrders.value.find(o => o.id === e.order.id)
                    if (order) order.isNew = false
                }, 5000)
            }
        })

    // Update elapsed time every 10 seconds for accurate display
    timerInterval = setInterval(() => {
        localOrders.value = [...localOrders.value]
    }, 10000)
})

// Cleanup
onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval)
    // Don't disconnect window.Echo — StaffLayout manages its lifecycle
})
</script>

<style scoped>
.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f3f4f6;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 2px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
