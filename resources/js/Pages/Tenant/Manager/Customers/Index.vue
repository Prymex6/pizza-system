<template>
    <Head title="Klienci" />
    <ManagerLayout title="Klienci">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Klienci</h1>
                    <p class="text-gray-500 text-sm mt-1">{{ customers.total }} zarejestrowanych klientów</p>
                </div>
                <a v-if="$page.props.app_version === 'test'" :href="route('tenant.manager.customers.export')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium flex items-center gap-2">
                    <i class="fa-solid fa-file-csv"></i>
                    Eksportuj CSV
                </a>
            </div>

            <!-- Search -->
            <div class="bg-white shadow rounded-lg p-4">
                <div class="flex gap-3">
                    <input
                        v-model="search"
                        @keyup.enter="applySearch"
                        type="text"
                        placeholder="Szukaj po nazwie, e-mail lub telefonie..."
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                    />
                    <button @click="applySearch" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">Szukaj</button>
                    <button v-if="filters.search" @click="clearSearch" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Wyczyść</button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Klient</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Telefon</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Miasto</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Zamówień</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Punkty</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Dołączył</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ customer.name }}</div>
                                <div class="text-gray-500 text-xs">{{ customer.email }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ formatPhone(customer.phone) || '–' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ customer.delivery_city || '–' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-semibold text-gray-900">{{ customer.orders_count }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="tierClass(customer.loyalty_tier)">
                                    {{ customer.loyalty_points ?? 0 }} pkt
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(customer.created_at) }}</td>
                            <td class="px-4 py-3">
                                <Link :href="route('tenant.manager.customers.show', customer.id)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                    Szczegóły
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!customers.data.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">Brak klientów</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="customers.last_page > 1" class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm">
                    <span class="text-gray-500">Strona {{ customers.current_page }} z {{ customers.last_page }}</span>
                    <div class="flex gap-2">
                        <Link v-if="customers.prev_page_url" :href="customers.prev_page_url" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Poprzednia</Link>
                        <Link v-if="customers.next_page_url" :href="customers.next_page_url" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Następna</Link>
                    </div>
                </div>
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { formatPhone } from '@/utils/phone'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
    customers: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')

const applySearch = () => {
    router.get(route('tenant.manager.customers.index'), { search: search.value }, { preserveState: true, replace: true })
}
const clearSearch = () => {
    search.value = ''
    router.get(route('tenant.manager.customers.index'), {}, { preserveState: true, replace: true })
}

const tierClass = (tier) => ({
    'bronze': 'bg-orange-100 text-orange-700',
    'silver': 'bg-gray-100 text-gray-700',
    'gold':   'bg-yellow-100 text-yellow-700',
    'platinum': 'bg-purple-100 text-purple-700',
}[tier] || 'bg-gray-100 text-gray-600')

const formatDate = (d) => d ? new Date(d).toLocaleDateString('pl-PL') : '–'
</script>
