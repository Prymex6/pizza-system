<template>
    <Head title="Nowe hasło" />

    <div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <img v-if="$page.props.tenant?.logo_url" :src="$page.props.tenant.logo_url" alt="Logo" class="h-16 mx-auto mb-3 object-contain" />
                <img v-else src="/images/logo.png" alt="Logo" class="h-16 mx-auto mb-3 object-contain" />
                <h1 class="text-2xl font-bold text-gray-900">{{ $page.props.tenant?.name ?? $page.props.app_name }}</h1>
                <p class="text-sm text-gray-500 mt-1">Panel Pracownika — ustaw nowe hasło</p>
            </div>

            <div v-if="!tokenValid" class="bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Token resetowania hasła jest nieprawidłowy lub wygasł.
            </div>

            <div class="bg-white rounded-lg shadow-md p-8">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Adres e-mail</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            :disabled="!tokenValid"
                            class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                            :class="{ 'border-red-500': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Nowe hasło</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            :disabled="!tokenValid"
                            class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                            :class="{ 'border-red-500': form.errors.password }"
                            placeholder="Min. 8 znaków"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Potwierdź hasło</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            :disabled="!tokenValid"
                            class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                            placeholder="Powtórz nowe hasło"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || !tokenValid"
                        class="w-full flex justify-center py-3 px-4 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 transition-colors"
                    >
                        <i v-if="form.processing" class="fa-solid fa-spinner fa-spin mr-2"></i>
                        {{ form.processing ? 'Zapisywanie...' : 'Ustaw nowe hasło' }}
                    </button>
                </form>
            </div>

            <div class="text-center">
                <a :href="route('tenant.password.request')" class="text-sm text-blue-600 hover:text-blue-700">
                    ← Wyślij nowy link resetujący
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'

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
    form.post(route('tenant.password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>
