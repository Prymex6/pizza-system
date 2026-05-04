<template>
    <Head title="Stoliki i Rezerwacje" />
    <ManagerLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Stoliki i Rezerwacje</h1>
                    <p class="text-sm text-gray-500 mt-1">Zarządzaj stolikami oraz rezerwacjami gości</p>
                </div>
                <button v-if="activeTab === 'tables'" @click="openModal()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    + Dodaj stolik
                </button>
            </div>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                {{ $page.props.flash.success }}
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex gap-6">
                    <button
                        @click="activeTab = 'reservations'"
                        :class="activeTab === 'reservations'
                            ? 'border-blue-500 text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition-colors"
                    >
                        <i class="fa-solid fa-calendar-days mr-1.5 text-blue-500"></i>
                        Rezerwacje
                        <span v-if="pendingCount > 0" class="ml-1.5 bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-xs">{{ pendingCount }}</span>
                    </button>
                    <button
                        @click="activeTab = 'tables'"
                        :class="activeTab === 'tables'
                            ? 'border-blue-500 text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition-colors"
                    >
                        <i class="fa-solid fa-chair mr-1.5 text-amber-600"></i>
                        Stoliki
                        <span class="ml-1.5 bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs">{{ tables.length }}</span>
                    </button>
                </nav>
            </div>

            <!-- ===== TABLES TAB ===== -->
            <template v-if="activeTab === 'tables'">
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ tables.length }}</div>
                        <div class="text-xs text-gray-500 mt-1">Wszystkich stolików</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">{{ countByStatus('available') }}</div>
                        <div class="text-xs text-gray-500 mt-1">Wolnych</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                        <div class="text-2xl font-bold text-red-600">{{ countByStatus('occupied') }}</div>
                        <div class="text-xs text-gray-500 mt-1">Zajętych</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ totalSeats }}</div>
                        <div class="text-xs text-gray-500 mt-1">Łączna liczba miejsc</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap gap-3 items-center">
                    <select v-model="filterLocation" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Wszystkie lokalizacje</option>
                        <option v-for="loc in uniqueLocations" :key="loc" :value="loc">{{ loc }}</option>
                    </select>
                    <select v-model="filterStatus" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Wszystkie statusy</option>
                        <option value="available">Wolne</option>
                        <option value="occupied">Zajęte</option>
                        <option value="reserved">Rezerwacja</option>
                        <option value="cleaning">Sprzątanie</option>
                    </select>
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" v-model="showInactive" class="rounded" />
                        Pokaż nieaktywne
                    </label>
                    <span class="text-sm text-gray-400 ml-auto">{{ filteredTables.length }} stolików</span>
                </div>

                <!-- Table List -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nr / Nazwa</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Miejsca</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lokalizacja</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notatki</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktywny</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">QR kod</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="table in filteredTables" :key="table.id"
                                class="hover:bg-gray-50 transition-colors"
                                :class="{ 'opacity-50': !table.is_active }">
                                <td class="px-4 py-3">
                                    <span class="font-bold text-gray-900 text-lg">#{{ table.number }}</span>
                                    <span v-if="table.name" class="ml-2 text-sm text-gray-500">{{ table.name }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <span v-if="table.min_guests && table.min_guests > 1">{{ table.min_guests }}–</span>{{ table.seats }} miejsc
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ table.location || '–' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate" :title="table.description">
                                    {{ table.description || '–' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="statusBadgeClass(table.status)">
                                        {{ statusLabel(table.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="table.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                        class="px-2 py-1 rounded text-xs font-medium">
                                        {{ table.is_active ? 'Aktywny' : 'Nieaktywny' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="table.qr_image_url" class="flex items-center gap-2">
                                        <img :src="table.qr_image_url" class="w-10 h-10 rounded border border-gray-200" :alt="`QR ${table.number}`" />
                                        <a :href="table.qr_image_url" :download="`stolik-${table.number}-qr.png`" target="_blank"
                                            class="text-xs text-blue-600 hover:text-blue-700">Pobierz</a>
                                    </div>
                                    <button v-else @click="generateQr(table)" class="text-xs text-blue-600 hover:text-blue-700">
                                        Generuj QR
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button v-if="table.qr_image_url" @click="generateQr(table)"
                                            class="text-xs text-gray-500 hover:text-gray-700">Odnów QR</button>
                                        <button @click="openModal(table)"
                                            class="text-xs text-blue-600 hover:text-blue-700 font-medium">Edytuj</button>
                                        <button @click="toggleActive(table)"
                                            class="text-xs font-medium"
                                            :class="table.is_active ? 'text-yellow-600 hover:text-yellow-700' : 'text-green-600 hover:text-green-700'">
                                            {{ table.is_active ? 'Dezaktywuj' : 'Aktywuj' }}
                                        </button>
                                        <button @click="deleteTable(table)" class="text-xs text-red-500 hover:text-red-600">Usuń</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredTables.length === 0">
                                <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                                    Brak stolików. Kliknij "+ Dodaj stolik", aby dodać pierwszy.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <!-- ===== RESERVATIONS TAB ===== -->
            <template v-else>
                <!-- Filters -->
                <div class="flex flex-wrap gap-3 items-center">
                    <input type="date" v-model="resDate"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm"
                        placeholder="Filtruj datę" />
                    <select v-model="resStatus" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Wszystkie statusy</option>
                        <option value="pending">Oczekuje</option>
                        <option value="confirmed">Potwierdzona</option>
                        <option value="cancelled">Anulowana</option>
                        <option value="completed">Zrealizowana</option>
                    </select>
                    <button @click="applyResFilters" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                        Filtruj
                    </button>
                    <button @click="clearResFilters" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
                        Wyczyść
                    </button>
                </div>

                <!-- Reservations table -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data / godzina</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gość</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kontakt</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stolik</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Osób</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Uwagi</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="res in reservations.data" :key="res.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium text-gray-900">{{ formatDate(res.reservation_date) }}</div>
                                    <div class="text-gray-500">{{ res.reservation_time?.slice(0, 5) }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ res.customer_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <div>{{ res.customer_phone }}</div>
                                    <div v-if="res.customer_email" class="text-xs text-gray-400">{{ res.customer_email }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <span v-if="res.table">#{{ res.table.number }}<span v-if="res.table.name"> {{ res.table.name }}</span></span>
                                    <span v-else class="text-gray-400">–</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ res.party_size }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 max-w-[150px] truncate" :title="res.notes">
                                    {{ res.notes || '–' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="resBadgeClass(res.status)">
                                        {{ resStatusLabel(res.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button v-if="res.status === 'pending'" @click="openConfirmModal(res)"
                                            class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded hover:bg-green-200">
                                            Potwierdź
                                        </button>
                                        <button v-if="res.status === 'confirmed'" @click="updateResStatus(res, 'completed')"
                                            class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                                            Zrealizuj
                                        </button>
                                        <button v-if="['pending','confirmed'].includes(res.status)" @click="updateResStatus(res, 'cancelled')"
                                            class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">
                                            Anuluj
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="reservations.data.length === 0">
                                <td colspan="8" class="px-4 py-12 text-center text-gray-500">Brak rezerwacji.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="reservations.last_page > 1" class="flex justify-center gap-2">
                    <Link
                        v-for="link in reservations.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50',
                            !link.url ? 'opacity-40 cursor-not-allowed' : '',
                            'px-3 py-1.5 text-sm border border-gray-300 rounded-lg'
                        ]"
                        v-html="link.label"
                        @click.prevent="link.url && router.visit(link.url)"
                    />
                </div>
            </template>
        </div>

        <!-- Confirm Reservation Modal -->
        <div v-if="confirmModal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                <div class="p-6 space-y-4">
                    <h3 class="text-lg font-bold text-gray-900">Potwierdź rezerwację</h3>
                    <div class="bg-gray-50 rounded-lg p-3 text-sm space-y-1">
                        <p><span class="text-gray-500">Gość:</span> <span class="font-medium">{{ confirmModal.res?.customer_name }}</span></p>
                        <p><span class="text-gray-500">Termin:</span> {{ formatDate(confirmModal.res?.reservation_date) }} {{ confirmModal.res?.reservation_time?.slice(0,5) }}</p>
                        <p><span class="text-gray-500">Osób:</span> {{ confirmModal.res?.party_size }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Przypisz stolik</label>
                        <select v-model="confirmModal.tableId"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option :value="null">-- Nie przypisuj teraz --</option>
                            <option
                                v-for="t in availableTablesForRes(confirmModal.res)"
                                :key="t.id"
                                :value="t.id"
                            >
                                #{{ t.number }}{{ t.name ? ` (${t.name})` : '' }}
                                · {{ t.seats }} miejsc
                                <template v-if="t.location"> · {{ t.location }}</template>
                                <template v-if="(t.status || 'available') !== 'available'"> ({{ statusLabel(t.status) }})</template>
                            </option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Wolne stoliki z wystarczającą liczbą miejsc są wyróżnione.</p>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button @click="confirmModal.open = false"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Anuluj
                        </button>
                        <button @click="submitConfirm"
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
                            Potwierdź rezerwację
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Table Modal -->
        <div v-if="modal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-5">
                        {{ modal.table ? `Edytuj stolik #${modal.table.number}` : 'Dodaj nowy stolik' }}
                    </h3>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Numer stolika *</label>
                                <input v-model.number="form.number" type="number" required min="1"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                <p v-if="form.errors.number" class="mt-1 text-xs text-red-600">{{ form.errors.number }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Etykieta / nazwa</label>
                                <input v-model="form.name" type="text" placeholder="np. A1, VIP-1, Okrągły"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                <p class="text-xs text-gray-400 mt-0.5">Wyświetlana obok numeru</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Maks. miejsc *</label>
                                <input v-model.number="form.seats" type="number" required min="1" max="50"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                <p v-if="form.errors.seats" class="mt-1 text-xs text-red-600">{{ form.errors.seats }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Min. gości</label>
                                <input v-model.number="form.min_guests" type="number" min="1" :max="form.seats || 50"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                <p class="text-xs text-gray-400 mt-0.5">Dla systemu rezerwacji</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lokalizacja / strefa</label>
                            <input v-model="form.location" type="text" placeholder="np. Sala główna, Ogródek, VIP, Piętro"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notatki dla obsługi</label>
                            <textarea v-model="form.description" rows="2"
                                placeholder="np. stolik przy oknie, podjazd dla wózków"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="modal.open = false"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                                Anuluj
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50">
                                {{ form.processing ? 'Zapisywanie...' : (modal.table ? 'Zapisz zmiany' : 'Dodaj stolik') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
    tables: Array,
    reservations: Object,
    resFilters: Object,
})

// Active tab — switch to reservations if filter active
const activeTab = ref('reservations')

const localTables = ref(props.tables.map(t => ({ ...t })))

// Tables filters
const filterLocation = ref('')
const filterStatus   = ref('')
const showInactive   = ref(false)

const uniqueLocations = computed(() => {
    const locs = localTables.value.map(t => t.location).filter(Boolean)
    return [...new Set(locs)]
})

const filteredTables = computed(() =>
    localTables.value.filter(t => {
        if (!showInactive.value && !t.is_active) return false
        if (filterLocation.value && t.location !== filterLocation.value) return false
        if (filterStatus.value && (t.status || 'available') !== filterStatus.value) return false
        return true
    })
)

const countByStatus = (s) => localTables.value.filter(t => t.is_active && (t.status || 'available') === s).length
const totalSeats = computed(() => localTables.value.filter(t => t.is_active).reduce((sum, t) => sum + (t.seats || 0), 0))

// Reservations filters
const resDate   = ref(props.resFilters?.res_date || '')
const resStatus = ref(props.resFilters?.res_status || '')

const pendingCount = computed(() => props.reservations?.data?.filter(r => r.status === 'pending').length || 0)

const applyResFilters = () => {
    activeTab.value = 'reservations'
    router.get(route('tenant.manager.tables.index'), {
        res_date: resDate.value || undefined,
        res_status: resStatus.value || undefined,
    }, { preserveScroll: true })
}

const clearResFilters = () => {
    resDate.value   = ''
    resStatus.value = ''
    router.get(route('tenant.manager.tables.index'), {}, { preserveScroll: true })
}

const confirmModal = reactive({ open: false, res: null, tableId: null })

const openConfirmModal = (res) => {
    confirmModal.res = res
    confirmModal.tableId = res.table_id || null
    confirmModal.open = true
}

const submitConfirm = () => {
    updateResStatus(confirmModal.res, 'confirmed', confirmModal.tableId)
    confirmModal.open = false
}

const availableTablesForRes = (res) => {
    if (!res) return []
    return localTables.value
        .filter(t => t.is_active && t.seats >= res.party_size)
        .sort((a, b) => {
            const aFree = (a.status || 'available') === 'available' ? 0 : 1
            const bFree = (b.status || 'available') === 'available' ? 0 : 1
            return aFree - bFree || a.number - b.number
        })
}

const updateResStatus = (res, status, tableId = undefined) => {
    const payload = { status }
    if (tableId !== undefined) payload.table_id = tableId
    router.patch(route('tenant.manager.reservations.update-status', res.id), payload, {
        preserveScroll: true,
        onSuccess: (page) => {
            localTables.value = page.props.tables.map(t => ({ ...t }))
        },
    })
}

// Table modal
const modal = reactive({ open: false, table: null })

const form = useForm({
    number: '',
    name: '',
    seats: 4,
    min_guests: 1,
    location: '',
    description: '',
})

const openModal = (table = null) => {
    modal.table = table
    modal.open  = true
    if (table) {
        form.number      = table.number
        form.name        = table.name || ''
        form.seats       = table.seats
        form.min_guests  = table.min_guests || 1
        form.location    = table.location || ''
        form.description = table.description || ''
    } else {
        form.reset()
        form.seats      = 4
        form.min_guests = 1
    }
}

const reload = () => router.reload({ only: ['tables'], onSuccess: (p) => {
    localTables.value = p.props.tables.map(t => ({ ...t }))
}})

const submitForm = () => {
    if (modal.table) {
        form.put(route('tenant.manager.tables.update', modal.table.id), {
            onSuccess: () => { modal.open = false; reload() },
        })
    } else {
        form.post(route('tenant.manager.tables.store'), {
            onSuccess: () => { modal.open = false; form.reset(); reload() },
        })
    }
}

const toggleActive = (table) => {
    router.patch(route('tenant.manager.tables.toggle', table.id), {}, {
        preserveScroll: true,
        onSuccess: () => reload(),
    })
}

const deleteTable = (table) => {
    const label = `#${table.number}${table.name ? ' (' + table.name + ')' : ''}`
    if (!confirm(`Usunąć stolik ${label}?`)) return
    router.delete(route('tenant.manager.tables.destroy', table.id), {
        preserveScroll: true,
        onSuccess: () => reload(),
    })
}

const generateQr = (table) => {
    router.post(route('tenant.manager.tables.qr-code', table.id), {}, {
        preserveScroll: true,
        onSuccess: () => reload(),
    })
}

// Helpers
const statusLabel = (s) => ({ available: 'Wolny', occupied: 'Zajęty', reserved: 'Rezerwacja', cleaning: 'Sprzątanie' }[s] || 'Wolny')

const statusBadgeClass = (s) => ({
    available: 'bg-green-100 text-green-700',
    occupied:  'bg-red-100 text-red-700',
    reserved:  'bg-purple-100 text-purple-700',
    cleaning:  'bg-yellow-100 text-yellow-700',
}[s] || 'bg-green-100 text-green-700')

const resStatusLabel = (s) => ({
    pending:   'Oczekuje',
    confirmed: 'Potwierdzona',
    cancelled: 'Anulowana',
    completed: 'Zrealizowana',
}[s] || s)

const resBadgeClass = (s) => ({
    pending:   'bg-yellow-100 text-yellow-700',
    confirmed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
    completed: 'bg-gray-100 text-gray-600',
}[s] || 'bg-gray-100 text-gray-600')

const formatDate = (d) => {
    if (!d) return ''
    const dateOnly = d.split('T')[0]
    const [y, m, day] = dateOnly.split('-')
    return `${day}.${m}.${y}`
}
</script>
