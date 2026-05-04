<template>
    <ManagerLayout :title="`Zamówienie #${order.order_number}`">
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Link :href="route('tenant.manager.orders.index')" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-2">
                        ← Wróć do listy zamówień
                    </Link>
                    <h1 class="text-3xl font-bold text-gray-900">Zamówienie #{{ order.order_number }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ formatDate(order.created_at) }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="statusClass(order.status)" class="px-3 py-1 rounded-full text-sm font-semibold">
                        {{ statusLabel(order.status) }}
                    </span>
                    <!-- Faktura VAT – tylko wersja testowa -->
                    <a
                        v-if="$page.props.app_version === 'test'"
                        :href="route('tenant.manager.orders.invoice', order.order_number)"
                        target="_blank"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium bg-indigo-50 border border-indigo-200 text-indigo-700 hover:bg-indigo-100 transition-colors"
                    >
                        Faktura VAT
                    </a>
                </div>
            </div>

            <!-- Status update -->
            <div class="bg-white rounded-lg shadow p-5">
                <h2 class="font-semibold text-gray-900 mb-3">Zmień status</h2>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="s in availableStatuses"
                        :key="s.value"
                        @click="updateStatus(s.value)"
                        :disabled="order.status === s.value || updating"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                            order.status === s.value ? 'bg-gray-200 text-gray-500 cursor-default' : 'bg-white border border-gray-300 hover:border-gray-400 text-gray-700'
                        ]"
                    >
                        {{ s.label }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Customer info -->
                <div class="bg-white rounded-lg shadow p-5">
                    <h2 class="font-semibold text-gray-900 mb-4">Dane klienta</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex gap-2">
                            <dt class="text-gray-500 w-28 shrink-0">Imię i nazwisko</dt>
                            <dd class="font-medium">{{ order.customer_name }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-gray-500 w-28 shrink-0">Telefon</dt>
                            <dd><a :href="`tel:${order.customer_phone}`" class="text-blue-600 hover:underline">{{ formatPhone(order.customer_phone) }}</a></dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-gray-500 w-28 shrink-0">Email</dt>
                            <dd><a :href="`mailto:${order.customer_email}`" class="text-blue-600 hover:underline">{{ order.customer_email }}</a></dd>
                        </div>
                        <div v-if="order.delivery_address" class="flex gap-2">
                            <dt class="text-gray-500 w-28 shrink-0">Adres</dt>
                            <dd>{{ order.delivery_address }}</dd>
                        </div>
                        <div v-if="order.notes" class="flex gap-2">
                            <dt class="text-gray-500 w-28 shrink-0">Uwagi</dt>
                            <dd class="text-yellow-700 bg-yellow-50 px-2 py-1 rounded">{{ order.notes }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Order info -->
                <div class="bg-white rounded-lg shadow p-5">
                    <h2 class="font-semibold text-gray-900 mb-4">Informacje o zamówieniu</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex gap-2">
                            <dt class="text-gray-500 w-28 shrink-0">Typ</dt>
                            <dd class="font-medium">{{ typeLabel(order.type) }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-gray-500 w-28 shrink-0">Płatność</dt>
                            <dd>{{ paymentLabel(order.payment_method) }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-gray-500 w-28 shrink-0">Status płatności</dt>
                            <dd :class="order.payment_status === 'paid' ? 'text-green-700 font-semibold' : 'text-yellow-700'">
                                {{ paymentStatusLabel(order.payment_status) }}
                            </dd>
                        </div>
                        <div v-if="order.paid_at" class="flex gap-2">
                            <dt class="text-gray-500 w-28 shrink-0">Opłacono</dt>
                            <dd>{{ formatDate(order.paid_at) }}</dd>
                        </div>
                        <div v-if="order.driver" class="flex gap-2">
                            <dt class="text-gray-500 w-28 shrink-0">Kierowca</dt>
                            <dd>{{ order.driver.name }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white rounded-lg shadow p-5">
                <h2 class="font-semibold text-gray-900 mb-4">Pozycje zamówienia</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="pb-2">Produkt</th>
                            <th class="pb-2 text-center">Ilość</th>
                            <th class="pb-2 text-right">Cena</th>
                            <th class="pb-2 text-right">Suma</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in order.items" :key="item.id" class="py-2">
                            <td class="py-2">
                                <div class="font-medium">{{ item.product_name }}</div>
                                <div v-if="item.variant_name" class="text-gray-500 text-xs">{{ item.variant_name }}</div>
                                <div v-if="item.addons_json" class="text-gray-400 text-xs">
                                    {{ formatAddons(item.addons_json) }}
                                </div>
                            </td>
                            <td class="py-2 text-center">{{ item.quantity }}</td>
                            <td class="py-2 text-right">{{ formatMoney(item.price) }}</td>
                            <td class="py-2 text-right font-medium">{{ formatMoney(item.price * item.quantity) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="mt-4 pt-4 border-t space-y-1 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Suma częściowa</span>
                        <span>{{ formatMoney(order.subtotal) }}</span>
                    </div>
                    <div v-if="order.delivery_fee > 0" class="flex justify-between text-gray-600">
                        <span>Dostawa</span>
                        <span>{{ formatMoney(order.delivery_fee) }}</span>
                    </div>
                    <div v-if="order.discount > 0" class="flex justify-between text-green-700">
                        <span>Rabat</span>
                        <span>-{{ formatMoney(order.discount) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-base pt-1 border-t">
                        <span>Razem</span>
                        <span>{{ formatMoney(order.total) }}</span>
                    </div>
                </div>
            </div>

        </div>
    </ManagerLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { formatPhone } from '@/utils/phone'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
    order: Object,
})

const updating = ref(false)

onMounted(() => {
    if (window.Echo) {
        window.Echo.private('orders')
            .listen('.order.status-changed', (e) => {
                if (e.order?.id === props.order.id) {
                    router.reload({ only: ['order'], preserveScroll: true })
                }
            })
    }
})


const availableStatuses = [
    { value: 'pending', label: 'Złożone' },
    { value: 'paid', label: 'Przyjęte' },
    { value: 'preparing', label: 'W przygotowaniu' },
    { value: 'ready', label: 'Gotowe' },
    { value: 'on_delivery', label: 'W dostawie' },
    { value: 'completed', label: 'Zakończone' },
    { value: 'cancelled', label: 'Anulowane' },
]

function updateStatus(status) {
    if (updating.value) return
    updating.value = true
    router.patch(route('tenant.manager.orders.update-status', props.order.id), { status }, {
        preserveScroll: true,
        onFinish: () => { updating.value = false },
    })
}

function statusLabel(s) {
    return availableStatuses.find(x => x.value === s)?.label ?? s
}

function statusClass(s) {
    const map = {
        pending: 'bg-gray-100 text-gray-700',
        awaiting_payment: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-blue-100 text-blue-800',
        preparing: 'bg-orange-100 text-orange-800',
        ready: 'bg-green-100 text-green-800',
        on_delivery: 'bg-purple-100 text-purple-800',
        completed: 'bg-green-200 text-green-900',
        cancelled: 'bg-red-100 text-red-800',
    }
    return map[s] ?? 'bg-gray-100 text-gray-700'
}

function typeLabel(t) {
    return { delivery: 'Dostawa', pickup: 'Odbiór', dine_in: 'Na miejscu' }[t] ?? t
}

function paymentLabel(m) {
    return { cash: 'Gotówka', card: 'Karta', przelewy24: 'Przelewy24', stripe: 'Karta online', payu: 'PayU', tpay: 'Tpay' }[m] ?? m
}

function paymentStatusLabel(s) {
    return { pending: 'Oczekuje', awaiting_payment: 'Oczekuje na płatność', paid: 'Opłacone', failed: 'Nieudana' }[s] ?? s
}

function formatMoney(val) {
    return Number(val ?? 0).toFixed(2) + ' zł'
}

function formatDate(d) {
    return d ? new Date(d).toLocaleString('pl-PL') : '–'
}

function formatAddons(json) {
    try {
        const addons = typeof json === 'string' ? JSON.parse(json) : json
        return Array.isArray(addons) ? addons.map(a => a.name).join(', ') : ''
    } catch {
        return ''
    }
}
</script>
