<template>
    <StaffLayout panelTitle="Zarządzanie Stolikami">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center flex-wrap gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><i class="fa-solid fa-chair mr-2 text-amber-600"></i> Stoliki</h1>
                    <p class="text-gray-500 mt-1">Zarządzaj stolikami, numerami, miejscami i kodami QR</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-white border border-gray-200 shadow-sm rounded-lg px-4 py-2 text-center">
                        <p class="text-xs text-gray-500">Łącznie stolików</p>
                        <p class="text-2xl font-bold text-blue-600">{{ filteredTables.length }}</p>
                    </div>
                    <a :href="route('tenant.staff.waiter')"
                        class="px-4 py-2 bg-white border border-gray-200 shadow-sm hover:bg-gray-50 text-gray-700 rounded-lg font-medium text-sm transition-colors">
                        ← Powrót
                    </a>
                    <button @click="openModal()"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-sm transition-colors">
                        + Dodaj stolik
                    </button>
                </div>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success" class="mb-4 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ $page.props.flash.success }}
        </div>

        <!-- Filter bar -->
        <div class="mb-5 flex flex-wrap gap-3 items-center bg-white border border-gray-200 shadow-sm rounded-lg px-4 py-3">
            <select v-model="filterLocation" class="px-3 py-1.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-700 focus:ring-2 focus:ring-blue-500">
                <option value="">Wszystkie lokalizacje</option>
                <option v-for="loc in uniqueLocations" :key="loc" :value="loc">{{ loc }}</option>
            </select>
            <select v-model="filterStatus" class="px-3 py-1.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-700 focus:ring-2 focus:ring-blue-500">
                <option value="">Wszystkie statusy</option>
                <option value="available">Wolne</option>
                <option value="occupied">Zajęte</option>
                <option value="reserved">Rezerwacja</option>
                <option value="cleaning">Sprzątanie</option>
            </select>
            <label class="flex items-center gap-2 text-sm text-gray-500 cursor-pointer">
                <input type="checkbox" v-model="showInactive" class="rounded" />
                Pokaż nieaktywne
            </label>
            <span class="text-sm text-gray-400 ml-auto">{{ filteredTables.length }} stolików</span>
        </div>

        <!-- Tables Grid -->
        <div v-if="filteredTables.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <div
                v-for="table in filteredTables"
                :key="table.id"
                class="bg-white rounded-xl shadow border-l-4 flex flex-col transition-all"
                :class="[!table.is_active ? 'opacity-60' : '', statusBorderClass(table.status)]"
            >
                <!-- Card header -->
                <div class="p-4 border-b border-gray-100">
                    <div class="flex justify-between items-start mb-1">
                        <div>
                            <span class="text-2xl font-extrabold text-gray-900 leading-none">#{{ table.number }}</span>
                            <span v-if="table.name" class="ml-2 text-sm font-semibold text-gray-600">{{ table.name }}</span>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full font-semibold" :class="statusBadgeClass(table.status)">
                            {{ statusLabel(table.status) }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 space-y-0.5 mt-1">
                        <div>
                            <i class="fa-solid fa-users mr-1 text-indigo-500"></i> <span>{{ table.min_guests && table.min_guests > 1 ? table.min_guests + '–' : '' }}{{ table.seats }} miejsc</span>
                        </div>
                        <div v-if="table.location"><i class="fa-solid fa-location-dot mr-1 text-red-500"></i> {{ table.location }}</div>
                        <div v-if="table.description" class="text-gray-400 italic truncate" :title="table.description">{{ table.description }}</div>
                    </div>
                </div>

                <!-- QR Section -->
                <div class="p-3 flex flex-col items-center bg-gray-50">
                    <div v-if="table.qr_image_url" class="text-center">
                        <img :src="table.qr_image_url" :alt="`QR Stolik ${table.number}`" class="w-24 h-24 mx-auto rounded border border-gray-200" />
                        <a :href="table.qr_image_url" :download="`stolik-${table.number}-qr.png`" target="_blank"
                            class="mt-1 text-xs text-blue-600 hover:text-blue-700 block">
                            ↓ Pobierz QR
                        </a>
                    </div>
                    <div v-else class="py-3 text-center text-gray-400">
                        <p class="text-3xl mb-1"><i class="fa-solid fa-camera text-indigo-500"></i></p>
                        <p class="text-xs">Brak kodu QR</p>
                    </div>
                </div>

                <!-- Status quick-change -->
                <div class="px-3 pb-2 pt-2">
                    <select
                        :value="table.status || 'available'"
                        @change="changeStatus(table, $event.target.value)"
                        class="w-full px-2 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-700 focus:ring-2 focus:ring-blue-500"
                        :disabled="!table.is_active"
                    >
                        <option value="available">Wolny</option>
                        <option value="occupied">Zajęty</option>
                        <option value="reserved">Rezerwacja</option>
                        <option value="cleaning">Sprzątanie</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="px-3 pb-3 flex flex-col gap-1.5 mt-auto">
                    <button @click="generateQr(table)"
                        class="w-full py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium transition-colors">
                        <i class="fa-solid fa-qrcode mr-1"></i> {{ table.qr_image_url ? 'Odnów QR' : 'Generuj QR' }}
                    </button>
                    <div class="flex gap-1.5">
                        <button @click="openModal(table)"
                            class="flex-1 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-medium transition-colors">
                            <i class="fa-solid fa-pen mr-1 text-blue-500"></i> Edytuj
                        </button>
                        <button @click="toggleTable(table)"
                            class="flex-1 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="table.is_active ? 'bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200' : 'bg-green-50 hover:bg-green-100 text-green-700 border border-green-200'">
                            {{ table.is_active ? 'Dezakt.' : 'Aktywuj' }}
                        </button>
                        <button @click="deleteTable(table)"
                            class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs transition-colors">
                            <i class="fa-solid fa-trash text-red-500"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-lg shadow p-12 text-center">
            <div class="text-5xl mb-3"><i class="fa-solid fa-chair text-gray-300"></i></div>
            <p class="text-xl font-semibold text-gray-700">Brak stolików</p>
            <p class="text-gray-400 mt-1 text-sm">Dodaj stoliki, aby zarządzać zamówieniami na miejscu</p>
            <button @click="openModal()" class="mt-6 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                + Dodaj pierwszy stolik
            </button>
        </div>

        <!-- Add/Edit Modal -->
        <div v-if="modal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">
                        {{ modal.table ? `Edytuj stolik #${modal.table.number}` : 'Nowy stolik' }}
                    </h2>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Numer stolika *</label>
                                <input v-model.number="form.number" type="number" required min="1"
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-blue-500" />
                                <p v-if="form.errors.number" class="text-red-500 text-xs mt-1">{{ form.errors.number }}</p>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Etykieta / nazwa</label>
                                <input v-model="form.name" type="text" placeholder="np. A1, VIP-1"
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-blue-500" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Maks. miejsc *</label>
                                <input v-model.number="form.seats" type="number" required min="1" max="50"
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-blue-500" />
                                <p v-if="form.errors.seats" class="text-red-500 text-xs mt-1">{{ form.errors.seats }}</p>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Min. gości</label>
                                <input v-model.number="form.min_guests" type="number" min="1" :max="form.seats || 50"
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-blue-500" />
                                <p class="text-xs text-gray-400 mt-0.5">Dla rezerwacji</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Lokalizacja / strefa</label>
                            <input v-model="form.location" type="text" placeholder="np. Sala główna, Ogródek, VIP, Piętro"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-blue-500" />
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Notatki (widoczne tylko dla obsługi)</label>
                            <textarea v-model="form.description" rows="2"
                                placeholder="np. stolik przy oknie, podjazd dla wózków"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="modal.open = false"
                                class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                                Anuluj
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50">
                                {{ form.processing ? 'Zapisywanie...' : (modal.table ? 'Zapisz zmiany' : 'Dodaj stolik') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import StaffLayout from '@/Layouts/StaffLayout.vue'

const props = defineProps({ tables: Array })

const localTables = ref(props.tables.map(t => ({ ...t })))

// Filters
const filterLocation = ref('')
const filterStatus   = ref('')
const showInactive   = ref(false)

const uniqueLocations = computed(() => {
    const locs = localTables.value.map(t => t.location).filter(Boolean)
    return [...new Set(locs)]
})

const filteredTables = computed(() => {
    return localTables.value.filter(t => {
        if (!showInactive.value && !t.is_active) return false
        if (filterLocation.value && t.location !== filterLocation.value) return false
        if (filterStatus.value && (t.status || 'available') !== filterStatus.value) return false
        return true
    })
})

// Modal
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
    modal.open = true
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

const reloadTables = () => {
    router.reload({ only: ['tables'], onSuccess: (page) => {
        localTables.value = page.props.tables.map(t => ({ ...t }))
    }})
}

const submitForm = () => {
    if (modal.table) {
        form.put(route('tenant.staff.waiter.tables.update', modal.table.id), {
            onSuccess: () => { modal.open = false; reloadTables() },
        })
    } else {
        form.post(route('tenant.staff.waiter.tables.store'), {
            onSuccess: () => { modal.open = false; form.reset(); reloadTables() },
        })
    }
}

const toggleTable = (table) => {
    router.patch(route('tenant.staff.waiter.tables.toggle', table.id), {}, {
        preserveScroll: true,
        onSuccess: () => reloadTables(),
    })
}

const deleteTable = (table) => {
    const label = `#${table.number}${table.name ? ' (' + table.name + ')' : ''}`
    if (!confirm(`Usunąć stolik ${label}? Tej operacji nie można cofnąć.`)) return
    router.delete(route('tenant.staff.waiter.tables.destroy', table.id), {
        preserveScroll: true,
        onSuccess: () => reloadTables(),
    })
}

const changeStatus = async (table, status) => {
    try {
        await axios.patch(route('tenant.staff.waiter.tables.status', table.id), { status })
        const t = localTables.value.find(x => x.id === table.id)
        if (t) t.status = status
    } catch {
        reloadTables()
    }
}

const generateQr = async (table) => {
    try {
        const res = await axios.post(route('tenant.staff.waiter.tables.qr-code', table.id))
        const t = localTables.value.find(t => t.id === table.id)
        if (t) {
            t.qr_image_url = res.data.qr_image_url
            t.qr_code      = res.data.menu_url
        }
    } catch {
        reloadTables()
    }
}

// Style helpers
const statusLabel = (s) => ({
    available: 'Wolny',
    occupied:  'Zajęty',
    reserved:  'Rezerwacja',
    cleaning:  'Sprzątanie',
}[s] || 'Wolny')

const statusBadgeClass = (s) => ({
    available: 'bg-green-100 text-green-700',
    occupied:  'bg-red-100 text-red-700',
    reserved:  'bg-purple-100 text-purple-700',
    cleaning:  'bg-amber-100 text-amber-700',
}[s] || 'bg-green-100 text-green-700')

const statusBorderClass = (s) => ({
    available: 'border-green-400',
    occupied:  'border-red-400',
    reserved:  'border-purple-400',
    cleaning:  'border-amber-400',
}[s] || 'border-green-400')
</script>
