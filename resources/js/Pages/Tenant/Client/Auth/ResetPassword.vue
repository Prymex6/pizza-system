<template>
    <Head title="Nowe hasło" />

    <ClientLayout>
        <div class="flex items-center justify-center min-h-[70vh] px-4 py-8">
        <div class="max-w-md w-full">

            <div class="bg-white rounded-lg shadow-md p-8">
                <h1 class="text-2xl font-bold text-gray-900 text-center mb-6">Ustaw nowe hasło</h1>

                <div v-if="!tokenValid" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    <i class="fa-solid fa-circle-exclamation mr-2"></i>Token resetowania hasła jest nieprawidłowy lub wygasł.
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adres e-mail</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            :disabled="!tokenValid"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                            :class="{ 'border-red-500': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Nowe hasło</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            :disabled="!tokenValid"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                            :class="{ 'border-red-500': form.errors.password }"
                            placeholder="Min. 8 znaków"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Potwierdź hasło</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            :disabled="!tokenValid"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                            placeholder="Powtórz nowe hasło"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || !tokenValid"
                        class="w-full py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
                    >
                        <i v-if="form.processing" class="fa-solid fa-spinner fa-spin mr-2"></i>
                        {{ form.processing ? 'Zapisywanie...' : 'Ustaw nowe hasło' }}
                    </button>

                    <p v-if="!tokenValid" class="text-center text-sm">
                        <a :href="route('tenant.client.password.request')" class="text-red-600 hover:text-red-700">← Wyślij nowy link resetujący</a>
                    </p>
                </form>
            </div>

        </div>
        </div>
    </ClientLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'

const props = defineProps({
    token: String,
    email: String,
    tokenValid: Boolean,
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('tenant.client.password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>
