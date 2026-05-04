<template>
    <Head :title="'Klient: ' + customer.name" />
    <ManagerLayout>
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-4 mb-6">
                <Link :href="route('tenant.manager.customers.index')" class="text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-arrow-left"></i>
                </Link>
                <h1 class="text-3xl font-bold text-gray-900">{{ customer.name }}</h1>
            </div>

            <!-- Customer info -->
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white shadow rounded-lg p-6 space-y-3">
                    <h2 class="font-semibold text-gray-900 mb-4">Dane kontaktowe</h2>
                    <div class="flex items-center gap-2 text-sm">
                        <i class="fa-solid fa-envelope w-4 text-gray-400"></i>
                        <span>{{ customer.email }}</span>
                    </div>
                    <div v-if="customer.phone" class="flex items-center gap-2 text-sm">
                        <i class="fa-solid fa-phone w-4 text-gray-400"></i>
                        <a :href="'tel:' + customer.phone" class="text-blue-600">{{ formatPhone(customer.phone) }}</a>
                    </div>
                    <div v-if="customer.delivery_address" class="flex items-start gap-2 text-sm">
                        <i class="fa-solid fa-location-dot w-4 text-gray-400 mt-0.5"></i>
                        <span>{{ customer.delivery_address }}, {{ customer.delivery_postal_code }} {{ customer.delivery_city }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <i class="fa-solid fa-calendar w-4 text-gray-400"></i>
                        <span>Dołączył: {{ formatDate(customer.created_at) }}</span>
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="font-semibold text-gray-900 mb-4">Program lojalnościowy</h2>
                    <div class="text-center mb-4">
                        <div class="text-4xl font-bold text-gray-900">{{ customer.loyalty_points ?? 0 }}</div>
                        <div class="text-gray-500 text-sm mt-1">punktów</div>
                        <span class="mt-3 inline-block px-3 py-1 rounded-full text-sm font-medium" :class="tierClass(customer.loyalty_tier)">
                            Poziom: {{ tierLabel(customer.loyalty_tier) }}
                        </span>
                    </div>
                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-xs text-gray-500 font-medium mb-2 uppercase tracking-wide">Ręczna korekta punktów</p>
                        <form @submit.prevent="submitPoints" class="space-y-2">
                            <input
                                v-model.number="pointsForm.points"
                                type="number"
                                required
                                placeholder="np. 50 lub -20"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            />
                            <input
                                v-model="pointsForm.description"
                                type="text"
                                required
                                placeholder="Powód (np. korekta, bonus...)"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            />
                            <p v-if="pointsForm.errors.points" class="text-xs text-red-600">{{ pointsForm.errors.points }}</p>
                            <p v-if="pointsForm.errors.description" class="text-xs text-red-600">{{ pointsForm.errors.description }}</p>
                            <button
                                type="submit"
                                :disabled="pointsForm.processing"
                                class="w-full px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50"
                            >
                                {{ pointsForm.processing ? 'Zapisywanie...' : 'Zapisz punkty' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="font-semibold text-gray-900 mb-4">Statystyki</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Łączna liczba zamówień:</span>
                            <span class="font-semibold">{{ customer.orders?.length ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Łączna kwota (opłacone):</span>
                            <span class="font-semibold text-green-600">{{ formatPrice(totalSpent) }} zł</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Punkty lojalnościowe:</span>
                            <span class="font-semibold">{{ customer.loyalty_points ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Łącznie zarobione pkt:</span>
                            <span class="font-semibold">{{ customer.loyalty_points_earned_total ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="font-semibold text-gray-900 mb-4">Ulubione produkty</h2>
                    <div v-if="favoriteProducts?.length > 0" class="space-y-2">
                        <div v-for="(prod, i) in favoriteProducts" :key="prod.name" class="flex justify-between items-center text-sm">
                            <span class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center">{{ i + 1 }}</span>
                                {{ prod.name }}
                            </span>
                            <span class="text-gray-500">{{ prod.total_qty }}x</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">Brak zamówień</p>
                </div>
            </div>

            <!-- Orders -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-gray-900">Historia zamówień (ostatnie 20)</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Numer</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Kwota</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="order in customer.orders" :key="order.id">
                            <td class="px-4 py-3 font-medium text-blue-600">{{ order.order_number }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ formatDate(order.created_at) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold">{{ formatPrice(order.total) }} zł</td>
                        </tr>
                        <tr v-if="!customer.orders?.length">
                            <td colspan="4" class="px-4 py-6 text-center text-gray-400">Brak zamówień</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { formatPhone } from '@/utils/phone'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
    customer: Object,
    totalSpent: { type: Number, default: 0 },
    favoriteProducts: { type: Array, default: () => [] },
})

const pointsForm = useForm({ points: null, description: '' })

const submitPoints = () => {
    pointsForm.post(route('tenant.manager.loyalty.add-points', props.customer.id), {
        onSuccess: () => pointsForm.reset(),
    })
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('pl-PL') : '–'
const formatPrice = (p) => Number(p || 0).toFixed(2).replace('.', ',')

const tierClass = (tier) => ({
    'bronze':   'bg-orange-100 text-orange-700',
    'silver':   'bg-gray-100 text-gray-700',
    'gold':     'bg-yellow-100 text-yellow-700',
    'platinum': 'bg-purple-100 text-purple-700',
}[tier] || 'bg-gray-100 text-gray-600')

const tierLabel = (tier) => ({ bronze: 'Brązowy', silver: 'Srebrny', gold: 'Złoty', platinum: 'Platynowy' }[tier] || tier)

const statusLabel = (s) => ({
    pending: 'Złożone', paid: 'Przyjęte', preparing: 'W przygotowaniu',
    ready: 'Gotowe', on_delivery: 'W dostawie', completed: 'Zakończone', cancelled: 'Anulowane',
}[s] || s)

const statusClass = (status) => ({
    'completed':   'bg-green-100 text-green-700',
    'cancelled':   'bg-red-100 text-red-700',
    'pending':     'bg-gray-100 text-gray-600',
    'paid':        'bg-blue-100 text-blue-700',
    'preparing':   'bg-orange-100 text-orange-700',
    'ready':       'bg-green-100 text-green-700',
    'on_delivery': 'bg-purple-100 text-purple-700',
}[status] || 'bg-gray-100 text-gray-600')
</script>
