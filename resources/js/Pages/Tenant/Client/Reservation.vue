<template>
    <Head title="Rezerwacja stolika" />

    <ClientLayout>
        <div class="bg-gray-50 py-16">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="text-center mb-14">
                    <span class="text-red-600 font-semibold text-sm uppercase tracking-[0.25em]">Zarezerwuj</span>
                    <h1 class="text-4xl md:text-5xl font-bold mt-3">Rezerwacja stolika</h1>
                    <p class="text-gray-600 mt-4 max-w-xl mx-auto">
                        Wypełnij formularz, a my potwierdzimy Twoją rezerwację.
                    </p>
                </div>

                <div class="max-w-lg mx-auto">
                    <!-- Success message -->
                    <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 border border-green-200 rounded-2xl p-4 text-green-800 text-center">
                        {{ $page.props.flash.success }}
                    </div>

                    <form @submit.prevent="submit" class="bg-white rounded-3xl shadow-lg p-8 space-y-6">
                        <!-- <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                            <i class="fa-solid fa-circle-info mr-1.5"></i>
                            Stolik zostanie przypisany przez obsługę przy potwierdzeniu rezerwacji.
                        </div> -->

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Imię i nazwisko *</label>
                            <input
                                v-model="form.customer_name"
                                name="name"
                                type="text"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                placeholder="Jan Kowalski"
                            />
                            <p v-if="form.errors.customer_name" class="text-red-500 text-sm mt-1">{{ form.errors.customer_name }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Telefon *</label>
                                <input
                                    v-model="form.customer_phone"
                                    name="phone"
                                    type="tel"
                                    required
                                    placeholder="123 456 789"
                                    @blur="form.customer_phone = formatPhone(form.customer_phone)"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                />
                                <p v-if="form.errors.customer_phone" class="text-red-500 text-sm mt-1">{{ form.errors.customer_phone }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                                <input
                                    v-model="form.customer_email"
                                    type="email"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                    placeholder="jan@email.pl"
                                />
                                <p v-if="form.errors.customer_email" class="text-red-500 text-sm mt-1">{{ form.errors.customer_email }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data *</label>
                                <input
                                    v-model="form.reservation_date"
                                    name="date"
                                    type="date"
                                    required
                                    :min="minDate"
                                    :max="maxDate"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                />
                                <p v-if="form.errors.reservation_date" class="text-red-500 text-sm mt-1">{{ form.errors.reservation_date }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Godzina *</label>
                                <input
                                    v-model="form.reservation_time"
                                    name="time"
                                    type="time"
                                    required
                                    :min="minTime || undefined"
                                    :max="maxTime || undefined"
                                    :disabled="isSelectedDayClosed"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:text-gray-400"
                                />
                                <p v-if="timeRangeLabel" class="text-xs mt-1" :class="isSelectedDayClosed ? 'text-red-500' : 'text-gray-400'">
                                    {{ timeRangeLabel }}
                                </p>
                                <p v-if="form.errors.reservation_time" class="text-red-500 text-sm mt-1">{{ form.errors.reservation_time }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Liczba osób *</label>
                            <select
                                v-model="form.party_size"
                                name="party_size"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            >
                                <option v-for="n in partySizeOptions" :key="n" :value="n">
                                    {{ n }} {{ n === 1 ? 'osoba' : n < 5 ? 'osoby' : 'osób' }}
                                </option>
                            </select>
                            <p v-if="form.errors.party_size" class="text-red-500 text-sm mt-1">{{ form.errors.party_size }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Uwagi</label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                placeholder="np. stolik przy oknie, krzesełko dla dziecka..."
                            ></textarea>
                            <p v-if="form.errors.notes" class="text-red-500 text-sm mt-1">{{ form.errors.notes }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 rounded-full text-lg shadow-lg transition disabled:opacity-50"
                        >
                            {{ form.processing ? 'Wysyłanie...' : 'Zarezerwuj stolik' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { Head, usePage, useForm } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { formatPhone } from '@/utils/phone'

const page = usePage()

const props = defineProps({
    maxPartySize: { type: Number, default: 20 },
    advanceDays: { type: Number, default: 14 },
    slotDuration: { type: Number, default: 120 },
    openingHours: { type: Object, default: null },
})

const customer = computed(() => page.props.auth?.customer)

const form = useForm({
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    reservation_date: '',
    reservation_time: '',
    party_size: 2,
    notes: '',
})

onMounted(() => {
    if (customer.value) {
        form.customer_name = customer.value.name || ''
        form.customer_phone = formatPhone(customer.value.phone || '')
        form.customer_email = customer.value.email || ''
    }
})

const today = new Date()
const minDate = today.toISOString().split('T')[0]
const maxDateObj = new Date(today)
maxDateObj.setDate(maxDateObj.getDate() + props.advanceDays)
const maxDate = maxDateObj.toISOString().split('T')[0]

const partySizeOptions = computed(() => {
    const options = []
    for (let i = 1; i <= props.maxPartySize; i++) options.push(i)
    return options
})

const DAY_NAMES = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']

// Returns opening hours for the selected date, or null if closed/no date
const selectedDayHours = computed(() => {
    if (!form.reservation_date || !props.openingHours) return null
    const date = new Date(form.reservation_date + 'T00:00:00')
    const dayName = DAY_NAMES[date.getDay()]
    const day = props.openingHours[dayName]
    if (!day) return null
    const enabled = typeof day.enabled !== 'undefined' ? day.enabled : !day.closed
    if (!enabled) return null
    return day
})

const isSelectedDayClosed = computed(() => {
    if (!form.reservation_date || !props.openingHours) return false
    const date = new Date(form.reservation_date + 'T00:00:00')
    const dayName = DAY_NAMES[date.getDay()]
    const day = props.openingHours[dayName]
    if (!day) return true
    const enabled = typeof day.enabled !== 'undefined' ? day.enabled : !day.closed
    return !enabled
})

// Convert "HH:MM" to total minutes
const toMinutes = (t) => {
    const [h, m] = (t || '00:00').split(':').map(Number)
    return h * 60 + m
}

// Convert total minutes to "HH:MM"
const fromMinutes = (m) => {
    const h = Math.floor(m / 60) % 24
    const min = m % 60
    return `${String(h).padStart(2, '0')}:${String(min).padStart(2, '0')}`
}

// Minimum time: opening time
const minTime = computed(() => {
    const day = selectedDayHours.value
    return day ? (day.open || '00:00') : null
})

// Maximum time: closing time minus slot duration
const maxTime = computed(() => {
    const day = selectedDayHours.value
    if (!day) return null
    let closeMin = toMinutes(day.close || '23:59')
    // 00:00 close = midnight = end of day
    if (closeMin === 0) closeMin = 24 * 60
    const latestMin = closeMin - props.slotDuration
    return latestMin > 0 ? fromMinutes(latestMin) : null
})

const timeRangeLabel = computed(() => {
    if (!form.reservation_date) return null
    if (isSelectedDayClosed.value) return 'Restauracja jest w tym dniu zamknięta.'
    if (!selectedDayHours.value) return null
    if (!minTime.value || !maxTime.value) return null
    return `Dostępne godziny: ${minTime.value} – ${maxTime.value}`
})

// Clear time when date changes and current time is out of range
watch(() => form.reservation_date, () => {
    if (!form.reservation_time) return
    const min = minTime.value
    const max = maxTime.value
    if (min && max && (form.reservation_time < min || form.reservation_time > max)) {
        form.reservation_time = ''
    }
})

const submit = () => {
    form.post(route('tenant.reservation.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}
</script>
