<template>
    <StaffLayout panelTitle="Panel Kelnera">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center flex-wrap gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><i class="fa-solid fa-utensils mr-2 text-orange-400"></i> Panel Kelnera</h1>
                    <p class="text-gray-500 mt-1">Zamówienia na miejscu i stoliki</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-white border border-gray-200 shadow-sm rounded-lg px-4 py-2 text-center">
                        <p class="text-xs text-gray-500">Aktywnych zamówień</p>
                        <p class="text-2xl font-bold text-blue-600">{{ localOrders.length }}</p>
                    </div>
                    <a
                        :href="route('tenant.staff.waiter.tables')"
                        class="px-4 py-2 bg-white border border-gray-200 shadow-sm hover:bg-gray-50 text-gray-700 rounded-lg font-medium text-sm transition-colors"
                    >
                        <i class="fa-solid fa-clipboard-list mr-1 text-blue-500"></i> Stoliki
                    </a>
                    <button
                        @click="openOrderModal()"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium text-sm transition-colors"
                    >
                        <i class="fa-solid fa-plus mr-1"></i> Nowe zamówienie
                    </button>
                </div>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success" class="mb-4 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ $page.props.flash.success }}
        </div>

        <!-- Orders Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div
                v-for="order in sortedOrders"
                :key="order.id"
                :class="[
                    'bg-white rounded-lg shadow border-l-4 p-3 space-y-2 transition-all duration-300',
                    getOrderBorderClass(order),
                    order.isNew ? 'animate-pulse shadow-lg shadow-blue-500/30' : ''
                ]"
            >
                <!-- Header: numer + timer + stolik w jednej linii -->
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <h3 class="text-lg font-bold text-gray-900">#{{ order.order_number }}</h3>
                        <span :class="getStatusBadgeClass(order)" class="px-2 py-0.5 rounded-full text-xs font-medium">
                            {{ getStatusLabel(order.status) }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                            {{ getTypeLabel(order.type) }}
                        </span>
                    </div>
                    <div class="text-right shrink-0">
                        <p :class="getTimerClass(order)" class="text-sm font-mono font-bold leading-none">{{ formatElapsedTime(order.created_at) }}</p>
                        <p class="text-xs text-gray-400">{{ formatTime(order.created_at) }}</p>
                    </div>
                </div>

                <!-- Klient + stolik w jednej linii -->
                <div class="flex items-center gap-2 text-xs text-gray-600">
                    <i class="fa-solid fa-user text-gray-400"></i>
                    <span class="font-semibold text-gray-800">{{ order.customer_name }}</span>
                    <template v-if="order.table">
                        <span class="text-gray-300">·</span>
                        <i class="fa-solid fa-chair text-amber-500"></i>
                        <span>Stolik {{ order.table.number }}</span>
                    </template>
                </div>

                <!-- Order Items -->
                <div class="bg-gray-50 rounded-lg px-2.5 py-2 space-y-1 max-h-36 overflow-y-auto">
                    <div v-for="item in order.items" :key="item.id">
                        <p class="text-xs font-semibold text-gray-800">{{ item.quantity }}× {{ item.name }}
                            <span v-if="item.variant_name" class="font-normal text-gray-500">– {{ item.variant_name }}</span>
                        </p>
                        <p v-if="item.addons && item.addons.length > 0" class="text-xs text-green-700 ml-3">+ {{ item.addons.map(a => a.name).join(', ') }}</p>
                        <p v-if="item.exclusions && item.exclusions.length > 0" class="text-xs text-red-600 ml-3">BEZ: {{ item.exclusions.join(', ') }}</p>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="order.notes" class="bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5 text-xs text-amber-800">
                    <i class="fa-solid fa-comment mr-1 text-amber-500"></i>{{ order.notes }}
                </div>

                <!-- Total + przyciski w jednej linii gdy możliwe -->
                <div class="flex items-center gap-2">
                    <div class="flex-1 flex justify-between items-center bg-gray-50 rounded-lg px-2.5 py-1.5">
                        <span class="text-xs text-gray-400">Suma</span>
                        <span class="text-sm font-bold text-gray-900">{{ formatPrice(order.total) }} zł</span>
                    </div>
                    <button
                        v-if="order.status === 'pending'"
                        @click="acceptOrder(order)"
                        class="shrink-0 px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-medium text-xs transition-colors"
                    >
                        <i class="fa-solid fa-check mr-1"></i> Przyjmij
                    </button>
                    <button
                        v-if="order.status === 'ready' && order.type !== 'delivery'"
                        @click="completeOrder(order)"
                        class="shrink-0 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium text-xs transition-colors"
                    >
                        <i class="fa-solid fa-flag-checkered mr-1"></i> Zakończ
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="localOrders.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
            <div class="text-5xl mb-3"><i class="fa-solid fa-sparkles text-yellow-400"></i></div>
            <p class="text-xl font-semibold text-gray-700">Brak aktywnych zamówień</p>
            <p class="text-gray-400 mt-1 text-sm">Nowe zamówienia pojawią się tutaj automatycznie</p>
            <button @click="openOrderModal()" class="mt-6 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                <i class="fa-solid fa-plus mr-1"></i> Dodaj pierwsze zamówienie
            </button>
        </div>

        <!-- POS Order Modal -->
        <PosOrderModal
            :show="orderModal.open"
            :categories="categories"
            :tables="tables"
            @close="orderModal.open = false"
            @success="onOrderCreated"
        />
    </StaffLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PosOrderModal from '@/Components/Pos/PosOrderModal.vue'
const props = defineProps({
    orders:     Array,
    tables:     Array,
    categories: Array,
})

const localOrders = ref([...props.orders])
let echo = null

watch(() => props.orders, (newOrders) => {
    localOrders.value = [...newOrders]
})

// ── Order creation modal ──────────────────────────────────────────────────
const orderModal = ref({ open: false })

const openOrderModal = () => { orderModal.value.open = true }

const onOrderCreated = () => {
    router.reload({ only: ['orders'] })
    playNotificationSound()
}

// ── Sorted orders ─────────────────────────────────────────────────────────
const sortedOrders = computed(() => {
    return [...localOrders.value].sort((a, b) => {
        if (a.isNew && !b.isNew) return -1
        if (!a.isNew && b.isNew) return 1

        const statusPriority = { 'pending': 0, 'paid': 1, 'preparing': 2, 'ready': 3, 'on_delivery': 4 }
        const aPriority = statusPriority[a.status] ?? 999
        const bPriority = statusPriority[b.status] ?? 999

        if (aPriority !== bPriority) return aPriority - bPriority
        return new Date(a.created_at) - new Date(b.created_at)
    })
})

const acceptOrder = async (order) => {
    try {
        await axios.patch(route('tenant.staff.waiter.accept', order.id))
        const idx = localOrders.value.findIndex(o => o.id === order.id)
        if (idx !== -1) localOrders.value[idx] = { ...localOrders.value[idx], status: 'paid' }
    } catch (err) {
        alert(err.response?.data?.message || 'Błąd przy przyjmowaniu zamówienia.')
    }
}

const completeOrder = async (order) => {
    try {
        await axios.patch(route('tenant.staff.waiter.complete', order.id))
        const idx = localOrders.value.findIndex(o => o.id === order.id)
        if (idx !== -1) localOrders.value.splice(idx, 1)
    } catch (err) {
        alert(err.response?.data?.message || 'Błąd przy kończeniu zamówienia.')
    }
}

const getOrderBorderClass = (order) => {
    if (order.isNew) return 'border-blue-500'
    return {
        'pending':     'border-gray-400',
        'paid':        'border-yellow-500',
        'preparing':   'border-blue-500',
        'ready':       'border-green-500',
        'on_delivery': 'border-purple-500',
    }[order.status] || 'border-gray-300'
}

const getStatusBadgeClass = (order) => {
    return {
        'pending':     'bg-gray-100 text-gray-600',
        'paid':        'bg-yellow-100 text-yellow-700',
        'preparing':   'bg-blue-100 text-blue-700',
        'ready':       'bg-green-100 text-green-700',
        'on_delivery': 'bg-purple-100 text-purple-700',
    }[order.status] || 'bg-gray-100 text-gray-600'
}

const getStatusLabel = (status) => {
    return {
        'pending':     'Oczekuje',
        'paid':        'Przyjęte',
        'preparing':   'W przygotowaniu',
        'ready':       'Gotowe',
        'on_delivery': 'W dostawie',
    }[status] || status
}

const getTypeLabel = (type) => {
    return { 'dine_in': 'Na miejscu', 'pickup': 'Odbiór', 'delivery': 'Dostawa' }[type] || type
}

const formatTime = (datetime) => new Date(datetime).toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' })

const formatElapsedTime = (created_at) => {
    const diff = Math.floor((new Date() - new Date(created_at)) / 1000 / 60)
    if (diff < 1) return '< 1 min'
    if (diff < 60) return `${diff} min`
    return `${Math.floor(diff / 60)}h ${diff % 60}m`
}

const getTimerClass = (order) => {
    const elapsed = (new Date() - new Date(order.created_at)) / 1000 / 60
    if (elapsed > 30) return 'text-red-600'
    if (elapsed > 20) return 'text-amber-600'
    return 'text-green-600'
}

const formatPrice = (price) => parseFloat(price).toFixed(2)

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

// ── Real-time — reuse window.Echo created by StaffLayout ──────────────────
onMounted(() => {
    echo = window.Echo?.private('orders')
        .listen('.order.created', (e) => {
            if (e.order.type === 'dine_in') {
                localOrders.value.push({ ...e.order, isNew: true })
                playNotificationSound()
                setTimeout(() => {
                    const order = localOrders.value.find(o => o.id === e.order.id)
                    if (order) order.isNew = false
                }, 5000)
            }
        })
        .listen('.order.status-changed', (e) => {
            const idx = localOrders.value.findIndex(o => o.id === e.order.id)
            if (idx !== -1) {
                if (e.new_status === 'completed' || e.new_status === 'cancelled') {
                    localOrders.value.splice(idx, 1)
                } else {
                    localOrders.value[idx] = { ...e.order, isNew: false }
                }
            }
        })

    setInterval(() => { localOrders.value = [...localOrders.value] }, 60000)
})

// Don't disconnect window.Echo — StaffLayout manages its lifecycle
onUnmounted(() => {
    echo?.stopListening('.order.created')
    echo?.stopListening('.order.status-changed')
})
</script>

<style scoped>
.overflow-y-auto::-webkit-scrollbar { width: 4px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f3f4f6; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 2px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
</style>
