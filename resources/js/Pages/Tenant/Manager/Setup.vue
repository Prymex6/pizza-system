<template>
    <Head title="Kreator ustawień" />
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
        <div class="w-full max-w-2xl">
            <!-- Logo / Header -->
            <div class="text-center mb-8">
                <img src="/images/logo.png" alt="Logo" class="h-16 mx-auto mb-3 object-contain" />
                <h1 class="text-3xl font-bold text-gray-900">Witaj w {{ $page.props.app_name }}!</h1>
                <p class="text-gray-500 mt-1">Skonfiguruj swoją restaurację w kilku krokach</p>
            </div>

            <!-- Progress -->
            <div class="flex items-center justify-between mb-8 px-4">
                <div
                    v-for="(s, i) in steps"
                    :key="i"
                    class="flex items-center"
                >
                    <div
                        class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
                        :class="currentStep > i ? 'bg-green-500 text-white' : currentStep === i ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500'"
                    >
                        <span v-if="currentStep > i"><i class="fa-solid fa-check"></i></span>
                        <span v-else>{{ i + 1 }}</span>
                    </div>
                    <span class="hidden md:block ml-2 text-sm font-medium" :class="currentStep >= i ? 'text-gray-700' : 'text-gray-400'">{{ s }}</span>
                    <div v-if="i < steps.length - 1" class="flex-1 h-0.5 mx-3 md:mx-4" :class="currentStep > i ? 'bg-green-400' : 'bg-gray-200'" style="min-width: 20px;"></div>
                </div>
            </div>

            <!-- Step 1: Restaurant info -->
            <div v-if="currentStep === 0" class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-1">Informacje o restauracji</h2>
                <p class="text-sm text-gray-500 mb-6">Dane podstawowe widoczne dla klientów</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nazwa restauracji *</label>
                        <input v-model="form.restaurant_name" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500" required placeholder="np. Pizzeria Mama Mia" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                            <input v-model="form.restaurant_phone" type="tel" placeholder="123 456 789" @blur="form.restaurant_phone = formatPhone(form.restaurant_phone)" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                            <input v-model="form.restaurant_email" type="email" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500" placeholder="kontakt@restauracja.pl" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adres restauracji</label>
                        <input v-model="form.restaurant_address" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500" placeholder="ul. Przykładowa 1, Warszawa" />
                    </div>
                </div>

                <div class="flex justify-end mt-8">
                    <button @click="saveStep1" type="button" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors">
                        Dalej →
                    </button>
                </div>
            </div>

            <!-- Step 2: Opening hours -->
            <div v-if="currentStep === 1" class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-1">Godziny otwarcia</h2>
                <p class="text-sm text-gray-500 mb-6">Kiedy restauracja jest otwarta?</p>

                <div class="space-y-3">
                    <div v-for="(day, key) in hours" :key="key" class="flex items-center gap-4">
                        <div class="w-32 flex items-center gap-2">
                            <input type="checkbox" v-model="day.enabled" class="w-4 h-4 text-blue-600" />
                            <span class="text-sm font-medium">{{ dayNames[key] }}</span>
                        </div>
                        <div v-if="day.enabled" class="flex items-center gap-2">
                            <input v-model="day.open" type="time" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            <span class="text-gray-400">–</span>
                            <input v-model="day.close" type="time" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <span v-else class="text-sm text-gray-400">Zamknięte</span>
                    </div>
                </div>

                <div class="flex justify-between mt-8">
                    <button @click="currentStep--" type="button" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl">
                        ← Wstecz
                    </button>
                    <button @click="saveStep2" type="button" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors">
                        Dalej →
                    </button>
                </div>
            </div>

            <!-- Step 3: First category & product -->
            <div v-if="currentStep === 2" class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-1">Pierwsze danie</h2>
                <p class="text-sm text-gray-500 mb-6">Opcjonalnie dodaj pierwszą kategorię i produkt do menu</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nazwa kategorii</label>
                        <input v-model="form.category_name" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500" placeholder="np. Pizza, Napoje, Desery" />
                    </div>
                    <div v-if="form.category_name" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nazwa produktu</label>
                            <input v-model="form.product_name" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500" placeholder="np. Pizza Margherita" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cena (PLN)</label>
                            <input v-model="form.product_price" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500" placeholder="np. 25.00" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-between mt-8">
                    <button @click="currentStep--" type="button" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl">
                        ← Wstecz
                    </button>
                    <button @click="complete" type="button" class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition-colors">
                        Zakończ konfigurację
                    </button>
                </div>
            </div>

            <p class="text-center mt-4 text-sm text-gray-400">
                Możesz pominąć kroki – wszystko można skonfigurować później w Ustawienia
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { formatPhone } from '@/utils/phone'

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
})

const currentStep = ref(0)
const steps = ['Restauracja', 'Godziny', 'Menu']

const form = reactive({
    restaurant_name: props.settings.restaurant_name ?? '',
    restaurant_phone: props.settings.restaurant_phone ?? '',
    restaurant_email: props.settings.restaurant_email ?? '',
    restaurant_address: props.settings.restaurant_address ?? '',
    category_name: '',
    product_name: '',
    product_price: '',
})

const dayNames = { monday: 'Pon', tuesday: 'Wt', wednesday: 'Śr', thursday: 'Czw', friday: 'Pt', saturday: 'Sob', sunday: 'Nd' }

const hours = reactive({
    monday:    { enabled: true, open: '10:00', close: '22:00' },
    tuesday:   { enabled: true, open: '10:00', close: '22:00' },
    wednesday: { enabled: true, open: '10:00', close: '22:00' },
    thursday:  { enabled: true, open: '10:00', close: '22:00' },
    friday:    { enabled: true, open: '10:00', close: '23:00' },
    saturday:  { enabled: true, open: '11:00', close: '23:00' },
    sunday:    { enabled: false, open: '12:00', close: '21:00' },
})

function saveStep1() {
    if (!form.restaurant_name.trim()) return alert('Podaj nazwę restauracji')
    router.post(route('tenant.manager.setup.store'), {
        step: 'restaurant',
        restaurant_name: form.restaurant_name,
        restaurant_phone: form.restaurant_phone,
        restaurant_email: form.restaurant_email,
        restaurant_address: form.restaurant_address,
    }, {
        preserveState: true,
        onSuccess: () => { currentStep.value = 1 },
    })
}

function saveStep2() {
    router.post(route('tenant.manager.setup.store'), {
        step: 'hours',
        opening_hours: hours,
    }, {
        preserveState: true,
        onSuccess: () => { currentStep.value = 2 },
    })
}

function complete() {
    router.post(route('tenant.manager.setup.store'), {
        step: 'complete',
        category_name: form.category_name,
        product_name: form.product_name,
        product_price: form.product_price,
    })
}
</script>
