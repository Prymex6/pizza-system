<template>
    <ManagerLayout>
        <Head title="Rezerwacje" />

        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Rezerwacje</h1>
                    <p class="mt-1 text-sm text-gray-600">Zarządzanie rezerwacjami stolików</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow rounded-lg p-4 flex flex-wrap gap-4 items-center">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Data</label>
                    <input
                        type="date"
                        :value="filters.date"
                        @change="applyFilter('date', $event.target.value)"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select
                        :value="filters.status"
                        @change="applyFilter('status', $event.target.value)"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm"
                    >
                        <option value="">Wszystkie</option>
                        <option value="pending">Oczekujące</option>
                        <option value="confirmed">Potwierdzone</option>
                        <option value="cancelled">Anulowane</option>
                        <option value="completed">Zrealizowane</option>
                    </select>
                </div>
                <button
                    v-if="filters.date || filters.status"
                    @click="clearFilters"
                    class="mt-5 text-sm text-gray-500 hover:text-gray-700"
                >
                    Wyczyść filtry
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Godzina</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gość</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telefon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Osoby</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="res in reservations.data" :key="res.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ formatDate(res.reservation_date) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ res.reservation_time?.slice(0, 5) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div>{{ res.customer_name }}</div>
                                <div v-if="res.customer_email" class="text-gray-400 text-xs">{{ res.customer_email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ formatPhone(res.customer_phone) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">{{ res.party_size }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="statusClass(res.status)" class="px-2.5 py-1 rounded-full text-xs font-semibold">
                                    {{ statusLabel(res.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex gap-1">
                                    <button
                                        v-if="res.status === 'pending'"
                                        @click="changeStatus(res.id, 'confirmed')"
                                        class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs hover:bg-green-200"
                                    >
                                        Potwierdź
                                    </button>
                                    <button
                                        v-if="res.status === 'confirmed'"
                                        @click="changeStatus(res.id, 'completed')"
                                        class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs hover:bg-blue-200"
                                    >
                                        Zrealizowana
                                    </button>
                                    <button
                                        v-if="res.status !== 'cancelled' && res.status !== 'completed'"
                                        @click="changeStatus(res.id, 'cancelled')"
                                        class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs hover:bg-red-200"
                                    >
                                        Anuluj
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!reservations.data?.length">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                Brak rezerwacji
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="reservations.last_page > 1" class="flex justify-center gap-1">
                <Link
                    v-for="link in reservations.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="px-3 py-2 text-sm rounded"
                    :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                    v-html="link.label"
                />
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { formatPhone } from '@/utils/phone'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
    reservations: Object,
    filters: { type: Object, default: () => ({}) },
})

const statusLabel = (status) => ({
    pending: 'Oczekująca',
    confirmed: 'Potwierdzona',
    cancelled: 'Anulowana',
    completed: 'Zrealizowana',
})[status] || status

const statusClass = (status) => ({
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    completed: 'bg-blue-100 text-blue-800',
})[status] || 'bg-gray-100 text-gray-800'

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('pl-PL', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const applyFilter = (key, value) => {
    router.get(route('tenant.manager.reservations.index'), {
        ...props.filters,
        [key]: value || undefined,
    }, { preserveState: true })
}

const clearFilters = () => {
    router.get(route('tenant.manager.reservations.index'))
}

const changeStatus = (id, status) => {
    router.patch(route('tenant.manager.reservations.update-status', id), { status })
}
</script>
