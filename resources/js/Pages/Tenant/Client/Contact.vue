<template>
    <Head title="Kontakt" />

    <ClientLayout>
        <div class="bg-gray-50 py-16">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="text-center mb-14">
                    <span class="text-red-600 font-semibold text-sm uppercase tracking-[0.25em]">Skontaktuj się</span>
                    <h1 class="text-4xl md:text-5xl font-bold mt-3">Kontakt</h1>
                </div>

                <div class="max-w-5xl mx-auto grid md:grid-cols-2 gap-12">
                    <!-- Contact Info -->
                    <div class="space-y-8">
                        <div v-if="tenant?.address" class="flex items-start gap-4">
                            <div class="shrink-0 w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center">
                                <i class="fa-solid fa-location-dot text-xl text-red-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 text-lg">Adres</h3>
                                <p class="text-gray-600 mt-1">{{ tenant.address }}</p>
                            </div>
                        </div>

                        <div v-if="tenant?.phone" class="flex items-start gap-4">
                            <div class="shrink-0 w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center">
                                <i class="fa-solid fa-phone text-xl text-red-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 text-lg">Telefon</h3>
                                <a :href="'tel:' + tenant.phone" class="text-red-600 hover:text-red-700 mt-1 block text-lg font-medium">
                                    {{ formatPhone(tenant.phone) }}
                                </a>
                            </div>
                        </div>

                        <div v-if="tenant?.email" class="flex items-start gap-4">
                            <div class="shrink-0 w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center">
                                <i class="fa-solid fa-envelope text-xl text-red-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 text-lg">E-mail</h3>
                                <a :href="'mailto:' + tenant.email" class="text-red-600 hover:text-red-700 mt-1 block">
                                    {{ tenant.email }}
                                </a>
                            </div>
                        </div>

                        <!-- Opening hours -->
                        <div class="flex items-start gap-4">
                            <div class="shrink-0 w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center">
                                <i class="fa-solid fa-clock text-xl text-red-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 text-lg mb-2">Godziny otwarcia</h3>
                                <ul class="text-gray-600 text-sm space-y-1">
                                    <li
                                        v-for="(day, index) in weekDays"
                                        :key="index"
                                        class="flex justify-between gap-8"
                                        :class="index === currentDayIndex ? 'text-red-600 font-semibold' : ''"
                                    >
                                        <span>{{ day.label }}</span>
                                        <span>{{ getDayHours(day.key) }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Contact form (#35) -->
                    <div class="bg-white rounded-3xl shadow-lg p-8">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Wyślij wiadomość</h2>

                        <div v-if="contactSent" class="text-center py-10">
                            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                                <i class="fa-solid fa-circle-check text-4xl text-green-500"></i>
                            </div>
                            <p class="font-bold text-gray-900 text-xl mb-2">Wiadomość wysłana!</p>
                            <p class="text-gray-500 text-sm mb-1">Dziękujemy za kontakt.</p>
                            <p class="text-gray-500 text-sm">Odpiszemy na podany adres e-mail.</p>
                            <button @click="contactSent = false" class="mt-6 px-5 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium transition-colors">
                                Wyślij kolejną wiadomość
                            </button>
                        </div>

                        <form v-else @submit.prevent="submitContact" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Imię i nazwisko</label>
                                <input v-model="contactForm.name" name="name" type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="Jan Kowalski" />
                                <p v-if="contactErrors.name" class="mt-1 text-sm text-red-600">{{ contactErrors.name[0] }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                                <input v-model="contactForm.email" type="email" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="jan@example.com" />
                                <p v-if="contactErrors.email" class="mt-1 text-sm text-red-600">{{ contactErrors.email[0] }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Wiadomość</label>
                                <textarea v-model="contactForm.message" name="message" required rows="5" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="W czym możemy pomóc?"></textarea>
                                <p v-if="contactErrors.message" class="mt-1 text-sm text-red-600">{{ contactErrors.message[0] }}</p>
                            </div>
                            <p v-if="contactErrors.general" class="text-sm text-red-600">{{ contactErrors.general }}</p>
                            <button type="submit" :disabled="contactSending" class="w-full py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition-colors disabled:opacity-50">
                                {{ contactSending ? 'Wysyłanie...' : 'Wyślij wiadomość' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map section below if Google Place ID set -->
        <div v-if="tenant?.google_place_id" class="container mx-auto px-4 lg:px-8 pb-16 max-w-5xl">
            <div class="bg-white rounded-3xl shadow-lg overflow-hidden h-80">
                <iframe
                    :src="mapEmbedUrl"
                    width="100%"
                    height="100%"
                    style="border:0;"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
            </div>
        </div>
    </ClientLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { formatPhone } from '@/utils/phone'

const page = usePage()
const tenant = computed(() => page.props.tenant)

const mapEmbedUrl = computed(() => {
    if (!tenant.value?.google_place_id) return ''
    const apiKey = page.props.google_maps_api_key || ''
    return `https://www.google.com/maps/embed/v1/place?key=${apiKey}&q=place_id:${tenant.value.google_place_id}`
})

const weekDays = [
    { key: 'monday', label: 'Poniedziałek' },
    { key: 'tuesday', label: 'Wtorek' },
    { key: 'wednesday', label: 'Środa' },
    { key: 'thursday', label: 'Czwartek' },
    { key: 'friday', label: 'Piątek' },
    { key: 'saturday', label: 'Sobota' },
    { key: 'sunday', label: 'Niedziela' },
]

const currentDayIndex = computed(() => {
    const day = new Date().getDay()
    return day === 0 ? 6 : day - 1
})

const openingHours = computed(() => {
    try {
        const raw = tenant.value?.opening_hours
        if (!raw) return null
        return typeof raw === 'string' ? JSON.parse(raw) : raw
    } catch {
        return null
    }
})

const getDayHours = (dayKey) => {
    if (!openingHours.value) return '-'
    const day = openingHours.value[dayKey]
    if (!day || !day.enabled) return 'zamknięte'
    return `${day.open || '00:00'} - ${day.close || '00:00'}`
}

// Contact form (#35)
const contactForm = reactive({ name: '', email: '', message: '' })
const contactErrors = reactive({})
const contactSending = ref(false)
const contactSent = ref(false)

const submitContact = async () => {
    Object.keys(contactErrors).forEach(k => delete contactErrors[k])
    contactSending.value = true
    try {
        await axios.post(route('tenant.contact.send'), contactForm)
        contactSent.value = true
        contactForm.name = ''
        contactForm.email = ''
        contactForm.message = ''
    } catch (e) {
        if (e.response?.status === 422) {
            Object.assign(contactErrors, e.response.data.errors || {})
        } else {
            contactErrors.general = 'Nie udało się wysłać wiadomości. Spróbuj ponownie.'
        }
    } finally {
        contactSending.value = false
    }
}
</script>
