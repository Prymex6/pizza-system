<template>
    <ManagerLayout title="Zamówienia">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Zamówienia
                        <span v-if="newOrdersCount > 0" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            {{ newOrdersCount }} nowych
                        </span>
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Zarządzaj zamówieniami restauracji
                    </p>
                </div>
                <button
                    @click="posModalOpen = true"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2"
                >
                    <i class="fa-solid fa-plus"></i> Nowe zamówienie
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            v-model="filters.status"
                            @change="applyFilters"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">Wszystkie</option>
                            <option value="pending">Złożone</option>
                            <option value="paid">Przyjęte</option>
                            <option value="preparing">W przygotowaniu</option>
                            <option value="ready">Gotowe</option>
                            <option value="on_delivery">W dostawie</option>
                            <option value="completed">Zakończone</option>
                            <option value="cancelled">Anulowane</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Typ</label>
                        <select
                            v-model="filters.type"
                            @change="applyFilters"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">Wszystkie</option>
                            <option value="delivery">Dostawa</option>
                            <option value="pickup">Odbiór osobisty</option>
                            <option value="dine_in">Na miejscu</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Płatność</label>
                        <select
                            v-model="filters.payment_status"
                            @change="applyFilters"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">Wszystkie</option>
                            <option value="pending">Oczekująca</option>
                            <option value="paid">Opłacona</option>
                            <option value="failed">Nieudana</option>
                            <option value="refunded">Zwrócona</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Szukaj</label>
                        <input
                            v-model="filters.search"
                            @input="debounceSearch"
                            type="text"
                            placeholder="Nr, imię, telefon..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                    </div>
                </div>
            </div>

            <!-- Orders List -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Numer
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Klient
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Typ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Metoda płatności
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status płatności
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kwota
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Data
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Akcje
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">#{{ order.order_number }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ order.customer_name }}</div>
                                    <div class="text-sm text-gray-500">{{ formatPhone(order.customer_phone) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="typeClass(order.type)" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ typeLabel(order.type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <select
                                        :value="order.status"
                                        @change="updateStatus(order, $event.target.value)"
                                        :class="statusClass(order.status)"
                                        class="text-xs font-semibold rounded px-2 py-1 border-0 cursor-pointer"
                                    >
                                        <option value="pending">Złożone</option>
                                        <option value="paid">Przyjęte</option>
                                        <option value="preparing">W przygotowaniu</option>
                                        <option value="ready">Gotowe</option>
                                        <option value="on_delivery">W dostawie</option>
                                        <option value="completed">Zakończone</option>
                                        <option value="cancelled">Anulowane</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900">{{ paymentMethodLabel(order.payment_method) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <select
                                        :value="order.payment_status"
                                        @change="updatePaymentStatus(order, $event.target.value)"
                                        :class="paymentStatusClass(order.payment_status)"
                                        class="text-xs font-semibold rounded px-2 py-1 border-0 cursor-pointer"
                                    >
                                        <option value="pending">Oczekująca</option>
                                        <option value="paid">Opłacona</option>
                                        <option value="failed">Nieudana</option>
                                        <option value="refunded">Zwrócona</option>
                                        <option value="refund_failed">Zwrot nieudany</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatPrice(order.total) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div>{{ formatDate(order.created_at) }}</div>
                                    <div class="text-xs text-gray-400">{{ timeAgo(order.created_at) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button
                                        @click="viewOrder(order)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        Szczegóły
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="orders.data.length === 0">
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                    Brak zamówień
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="orders.data.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Pokazuję <span class="font-medium">{{ orders.from }}</span> do <span class="font-medium">{{ orders.to }}</span> z <span class="font-medium">{{ orders.total }}</span> wyników
                        </div>
                        <div class="flex space-x-2">
                            <template v-for="link in orders.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50',
                                        'px-3 py-2 border border-gray-300 text-sm font-medium rounded-md'
                                    ]"
                                    v-html="link.label"
                                ></Link>
                                <span
                                    v-else
                                    :class="[
                                        link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700',
                                        'px-3 py-2 border border-gray-300 text-sm font-medium rounded-md opacity-50 cursor-not-allowed'
                                    ]"
                                    v-html="link.label"
                                ></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Details Modal -->
        <div
            v-if="selectedOrder"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            @click.self="selectedOrder = null"
        >
            <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white mb-10">
                <div class="space-y-4">
                    <div class="flex justify-between items-start">
                        <h3 class="text-lg font-medium text-gray-900">
                            Zamówienie #{{ selectedOrder.order_number }}
                        </h3>
                        <button @click="selectedOrder = null" class="text-gray-400 hover:text-gray-600">
                            <span class="text-2xl">×</span>
                        </button>
                    </div>

                    <!-- Customer Info -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-2">Dane klienta</h4>
                        <div class="space-y-1 text-sm">
                            <p><span class="text-gray-600">Imię:</span> {{ selectedOrder.customer_name }}</p>
                            <p><span class="text-gray-600">Email:</span> {{ selectedOrder.customer_email || 'Brak' }}</p>
                            <p><span class="text-gray-600">Telefon:</span> {{ formatPhone(selectedOrder.customer_phone) }}</p>
                            <p v-if="selectedOrder.delivery_address">
                                <span class="text-gray-600">Adres:</span> {{ selectedOrder.delivery_address }}
                            </p>
                            <p v-if="selectedOrder.table">
                                <span class="text-gray-600">Stolik:</span> {{ selectedOrder.table.number }}
                                <span v-if="selectedOrder.table.name" class="text-gray-500">({{ selectedOrder.table.name }})</span>
                            </p>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Pozycje zamówienia</h4>
                        <div class="space-y-2">
                            <div
                                v-for="item in selectedOrder.items"
                                :key="item.id"
                                class="flex justify-between items-start p-3 bg-gray-50 rounded"
                            >
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">
                                        {{ item.quantity }}x {{ item.name }}
                                        <span v-if="item.variant_name" class="text-gray-600">({{ item.variant_name }})</span>
                                    </p>
                                    <p v-if="item.addons && item.addons.length > 0" class="text-sm text-gray-600">
                                        + {{ item.addons.map(a => a.name).join(', ') }}
                                    </p>
                                    <p v-if="item.exclusions && item.exclusions.length > 0" class="text-sm text-gray-600">
                                        Bez: {{ item.exclusions.join(', ') }}
                                    </p>
                                    <p v-if="item.notes" class="text-sm text-gray-500 italic">
                                        {{ item.notes }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-gray-900">{{ formatPrice(item.price * item.quantity) }}</p>
                                    <p class="text-xs text-gray-500">{{ formatPrice(item.price) }} / szt</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="border-t pt-4">
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Suma częściowa:</span>
                                <span>{{ formatPrice(selectedOrder.subtotal) }}</span>
                            </div>
                            <div v-if="selectedOrder.delivery_fee > 0" class="flex justify-between text-sm">
                                <span class="text-gray-600">Dostawa:</span>
                                <span>{{ formatPrice(selectedOrder.delivery_fee) }}</span>
                            </div>
                            <div v-if="selectedOrder.discount > 0" class="flex justify-between text-sm">
                                <span class="text-gray-600">Rabat:</span>
                                <span class="text-red-600">-{{ formatPrice(selectedOrder.discount) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-lg border-t pt-2">
                                <span>Razem:</span>
                                <span>{{ formatPrice(selectedOrder.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="selectedOrder.notes" class="bg-yellow-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-1">Uwagi do zamówienia</h4>
                        <p class="text-sm text-gray-700">{{ selectedOrder.notes }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-2 border-t">
                        <a
                            v-if="$page.props.app_version === 'test'"
                            :href="route('tenant.manager.orders.invoice', selectedOrder.order_number)"
                            target="_blank"
                            class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md transition-colors"
                        >
                            Faktura / Drukuj
                        </a>
                        <button
                            @click="selectedOrder = null"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors"
                        >
                            Zamknij
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- POS Order Modal -->
        <PosOrderModal
            :show="posModalOpen"
            :categories="categories"
            :tables="tables"
            @close="posModalOpen = false"
            @success="onPosOrderCreated"
        />
    </ManagerLayout>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { formatPhone } from '@/utils/phone'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import PosOrderModal from '@/Components/Pos/PosOrderModal.vue'

const props = defineProps({
    orders:     Object,
    categories: { type: Array, default: () => [] },
    tables:     { type: Array, default: () => [] },
})

const posModalOpen = ref(false)

const onPosOrderCreated = () => {
    router.reload({ only: ['orders'], preserveScroll: true })
}

const selectedOrder = ref(null)
let searchTimeout = null

const newOrdersCount = ref(0)
const originalTitle = document.title

const updateTitle = () => {
    document.title = newOrdersCount.value > 0
        ? `(${newOrdersCount.value}) ${originalTitle}`
        : originalTitle
}

onMounted(() => {
    if (window.Echo) {
        window.Echo.private('orders')
            .listen('.OrderCreated', () => {
                newOrdersCount.value++
                updateTitle()
                router.reload({ only: ['orders'], preserveScroll: true })
            })
            .listen('.order.status-changed', () => {
                router.reload({ only: ['orders'], preserveScroll: true })
            })
    }
})

onUnmounted(() => {
    document.title = originalTitle
})

const filters = reactive({
    status: new URL(window.location.href).searchParams.get('status') || '',
    type: new URL(window.location.href).searchParams.get('type') || '',
    payment_status: new URL(window.location.href).searchParams.get('payment_status') || '',
    search: new URL(window.location.href).searchParams.get('search') || '',
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN',
    }).format(price)
}

const formatDate = (date) => {
    return new Date(date).toLocaleString('pl-PL', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const timeAgo = (date) => {
    const minutes = Math.floor((Date.now() - new Date(date)) / 60000)
    if (minutes < 1) return 'przed chwilą'
    if (minutes < 60) return `${minutes} min temu`
    const hours = Math.floor(minutes / 60)
    if (hours < 24) return `${hours} godz. temu`
    return `${Math.floor(hours / 24)} dni temu`
}

const typeLabel = (type) => {
    const labels = {
        delivery: 'Dostawa',
        pickup: 'Odbiór',
        dine_in: 'Na miejscu'
    }
    return labels[type] || type
}

const typeClass = (type) => {
    const classes = {
        delivery: 'bg-blue-100 text-blue-800',
        pickup: 'bg-green-100 text-green-800',
        dine_in: 'bg-purple-100 text-purple-800'
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}

const statusClass = (status) => {
    const classes = {
        pending: 'bg-gray-100 text-gray-700',
        paid: 'bg-blue-100 text-blue-800',
        preparing: 'bg-orange-100 text-orange-800',
        ready: 'bg-green-100 text-green-800',
        on_delivery: 'bg-purple-100 text-purple-800',
        completed: 'bg-green-200 text-green-900',
        cancelled: 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const paymentMethodLabel = (method) => {
    const labels = {
        cash: 'Gotówka przy odbiorze',
        card_on_delivery: 'Karta przy odbiorze',
        przelewy24: 'Przelewy24',
        payu: 'PayU',
        tpay: 'Tpay',
        stripe: 'Stripe',
        online: 'Płatność online',
    }
    return labels[method] || method || '—'
}

const paymentStatusLabel = (status) => {
    const labels = {
        pending: 'Oczekująca',
        paid: 'Opłacona',
        failed: 'Nieudana',
        refunded: 'Zwrócona'
    }
    return labels[status] || status
}

const paymentStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        refunded: 'bg-gray-100 text-gray-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const applyFilters = () => {
    newOrdersCount.value = 0
    document.title = originalTitle
    router.get(route('tenant.manager.orders.index'), filters, {
        preserveState: true,
        preserveScroll: true,
    })
}

const debounceSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        applyFilters()
    }, 500)
}

const updateStatus = (order, newStatus) => {
    router.patch(route('tenant.manager.orders.update-status', order.id), {
        status: newStatus
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const updatePaymentStatus = (order, newPaymentStatus) => {
    router.patch(route('tenant.manager.orders.update-payment-status', order.id), {
        payment_status: newPaymentStatus
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const viewOrder = (order) => {
    selectedOrder.value = order
}
</script>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-20px); }
</style>
