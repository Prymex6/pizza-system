<template>
    <Head title="Konfiguracja restauracji" />
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
                <div v-for="(s, i) in steps" :key="i" class="flex items-center">
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

            <!-- Step 0: Account (create new OR set password for existing) -->
            <div v-if="currentStep === 0" class="bg-white rounded-2xl shadow-lg p-8">
                <template v-if="existingManagerEmail">
                    <!-- Manager already exists — just set password -->
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Ustaw hasło do konta</h2>
                    <p class="text-sm text-gray-500 mb-6">
                        Konto administratora istnieje pod adresem
                        <strong class="text-gray-700">{{ existingManagerEmail }}</strong>.
                        Ustaw nowe hasło do logowania.
                    </p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nowe hasło *</label>
                            <input
                                v-model="form.password"
                                type="password"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :class="{ 'border-red-400': errors.password }"
                                placeholder="Minimum 8 znaków"
                                required
                            />
                            <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Powtórz hasło *</label>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Powtórz hasło"
                                required
                            />
                        </div>
                    </div>
                </template>

                <template v-else>
                    <!-- Fresh install — create account -->
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Utwórz konto administratora</h2>
                    <p class="text-sm text-gray-500 mb-6">To konto będzie służyło do zarządzania restauracją</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Imię i nazwisko *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :class="{ 'border-red-400': errors.name }"
                                placeholder="Jan Kowalski"
                                required
                            />
                            <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Adres e-mail *</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :class="{ 'border-red-400': errors.email }"
                                placeholder="jan@restauracja.pl"
                                required
                            />
                            <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hasło *</label>
                            <input
                                v-model="form.password"
                                type="password"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :class="{ 'border-red-400': errors.password }"
                                placeholder="Minimum 8 znaków"
                                required
                            />
                            <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Powtórz hasło *</label>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Powtórz hasło"
                                required
                            />
                        </div>
                    </div>
                </template>

                <div class="flex justify-end mt-8">
                    <button
                        @click="submitAccount"
                        type="button"
                        :disabled="processing"
                        class="px-8 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold rounded-xl transition-colors"
                    >
                        {{ processing ? 'Zapisywanie...' : 'Dalej →' }}
                    </button>
                </div>
            </div>

            <!-- Step 1: Restaurant info -->
            <div v-if="currentStep === 1" class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-1">Informacje o restauracji</h2>
                <p class="text-sm text-gray-500 mb-6">Dane podstawowe widoczne dla klientów</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nazwa restauracji *</label>
                        <input
                            v-model="form.restaurant_name"
                            type="text"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{ 'border-red-400': errors.restaurant_name }"
                            placeholder="np. Pizzeria Mama Mia"
                            required
                        />
                        <p v-if="errors.restaurant_name" class="mt-1 text-sm text-red-600">{{ errors.restaurant_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Imię i nazwisko właściciela / nazwa firmy *
                            <span class="text-gray-400 font-normal text-xs">(do regulaminu i polityki prywatności)</span>
                        </label>
                        <input
                            v-model="form.restaurant_owner_name"
                            type="text"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{ 'border-red-400': errors.restaurant_owner_name }"
                            placeholder="np. Jan Kowalski lub XYZ Sp. z o.o."
                            required
                        />
                        <p class="mt-1 text-xs text-gray-500">Pojawi się w dokumentach prawnych jako dane Usługodawcy/Administratora danych.</p>
                        <p v-if="errors.restaurant_owner_name" class="mt-1 text-sm text-red-600">{{ errors.restaurant_owner_name }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                            <input
                                v-model="form.restaurant_phone"
                                type="tel"
                                placeholder="123 456 789"
                                @blur="form.restaurant_phone = formatPhone(form.restaurant_phone)"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :class="{ 'border-red-400': errors.restaurant_phone }"
                            />
                            <p v-if="errors.restaurant_phone" class="mt-1 text-sm text-red-600">{{ errors.restaurant_phone }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail kontaktowy</label>
                            <input
                                v-model="form.restaurant_email"
                                type="email"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :class="{ 'border-red-400': errors.restaurant_email }"
                                placeholder="kontakt@restauracja.pl"
                            />
                            <p v-if="errors.restaurant_email" class="mt-1 text-sm text-red-600">{{ errors.restaurant_email }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adres restauracji</label>
                        <input
                            v-model="form.restaurant_address"
                            type="text"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{ 'border-red-400': errors.restaurant_address }"
                            placeholder="ul. Przykładowa 1, Warszawa"
                        />
                        <p v-if="errors.restaurant_address" class="mt-1 text-sm text-red-600">{{ errors.restaurant_address }}</p>
                    </div>

                </div>

                <div class="flex justify-end mt-8">
                    <button @click="submitRestaurant" type="button" :disabled="processing" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold rounded-xl transition-colors">
                        {{ processing ? 'Zapisywanie...' : 'Dalej →' }}
                    </button>
                </div>
            </div>

            <!-- Step 2: Opening hours -->
            <div v-if="currentStep === 2" class="bg-white rounded-2xl shadow-lg p-8">
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
                    <button @click="submitComplete" type="button" :disabled="processing" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 font-medium rounded-xl">
                        Pomiń
                    </button>
                    <button @click="submitHours" type="button" :disabled="processing" class="px-8 py-3 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-semibold rounded-xl transition-colors">
                        {{ processing ? 'Zapisywanie...' : 'Zakończ konfigurację' }}
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
import { ref, reactive, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { formatPhone } from '@/utils/phone'

const props = defineProps({
    step:                   { type: Number, default: 0 },
    existing_manager_email: { type: String, default: null },
})

const page = usePage()
const currentStep = ref(props.step)
const processing = ref(false)
const errors = computed(() => page.props.errors ?? {})
const existingManagerEmail = props.existing_manager_email

const steps = ['Konto', 'Restauracja', 'Godziny']

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    restaurant_name: '',
    restaurant_owner_name: '',
    restaurant_phone: '',
    restaurant_email: '',
    restaurant_address: '',

})

const dayNames = {
    monday: 'Pon', tuesday: 'Wt', wednesday: 'Śr',
    thursday: 'Czw', friday: 'Pt', saturday: 'Sob', sunday: 'Nd',
}

const hours = reactive({
    monday:    { enabled: true,  open: '10:00', close: '22:00' },
    tuesday:   { enabled: true,  open: '10:00', close: '22:00' },
    wednesday: { enabled: true,  open: '10:00', close: '22:00' },
    thursday:  { enabled: true,  open: '10:00', close: '22:00' },
    friday:    { enabled: true,  open: '10:00', close: '23:00' },
    saturday:  { enabled: true,  open: '11:00', close: '23:00' },
    sunday:    { enabled: false, open: '12:00', close: '21:00' },
})

function submitAccount() {
    processing.value = true
    router.post(route('tenant.install.account'), {
        name: form.name,
        email: form.email,
        password: form.password,
        password_confirmation: form.password_confirmation,
    }, {
        onFinish: () => { processing.value = false },
        onSuccess: () => { currentStep.value = 1 },
    })
}

function submitRestaurant() {
    processing.value = true
    router.post(route('tenant.install.restaurant'), {
        restaurant_name: form.restaurant_name,
        restaurant_owner_name: form.restaurant_owner_name,
        restaurant_phone: form.restaurant_phone,
        restaurant_email: form.restaurant_email,
        restaurant_address: form.restaurant_address,
    }, {
        onFinish: () => { processing.value = false },
        onSuccess: () => { currentStep.value = 2 },
    })
}

function submitHours() {
    processing.value = true
    router.post(route('tenant.install.hours'), {
        opening_hours: hours,
    }, {
        onFinish: () => { processing.value = false },
    })
}

function submitComplete() {
    processing.value = true
    router.post(route('tenant.install.complete'), {}, {
        onFinish: () => { processing.value = false },
    })
}
</script>
