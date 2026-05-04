<template>
    <Head title="Logowanie" />

    <ClientLayout>
        <div class="flex items-center justify-center min-h-[70vh] px-4 py-8">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-lg shadow-md p-8">
                <h1 class="text-2xl font-bold text-gray-900 text-center mb-6">Zaloguj się</h1>

                <div v-if="$page.props.errors?.throttle" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ $page.props.errors.throttle }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Hasło</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.password }"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember" v-model="form.remember" type="checkbox" class="h-4 w-4 text-red-600 rounded" />
                            <label for="remember" class="ml-2 text-sm text-gray-700">Zapamiętaj mnie</label>
                        </div>
                        <Link :href="route('tenant.client.password.request')" class="text-sm text-red-600 hover:text-red-700">
                            Zapomniałem hasła
                        </Link>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Logowanie...' : 'Zaloguj się' }}
                    </button>
                </form>

                <!-- Social login (dostępne tylko w wersji testowej) -->
                <div v-if="$page.props.app_version === 'test'" class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">lub kontynuuj przez</span>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <a
                            :href="route('tenant.client.social.redirect', 'google')"
                            class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            <i class="fa-brands fa-google mr-2 text-lg" style="color: #4285F4"></i>
                            <span class="text-sm font-medium text-gray-700">Google</span>
                        </a>
                        <a
                            :href="route('tenant.client.social.redirect', 'facebook')"
                            class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            <i class="fa-brands fa-facebook-f mr-2 text-lg" style="color: #1877F2"></i>
                            <span class="text-sm font-medium text-gray-700">Facebook</span>
                        </a>
                    </div>
                </div>

                <p class="mt-6 text-center text-sm text-gray-600">
                    Nie masz konta?
                    <Link :href="route('tenant.client.register')" class="text-red-600 hover:text-red-700 font-medium">
                        Zarejestruj się
                    </Link>
                </p>
            </div>
        </div>
        </div>
    </ClientLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('tenant.client.login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>
