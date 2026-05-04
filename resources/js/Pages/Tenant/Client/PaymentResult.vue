<template>
    <ClientLayout>
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <!-- Success State -->
                <div v-if="success">
                    <div class="text-6xl mb-4 text-green-500"><i class="fa-solid fa-check"></i></div>
                    <h1 class="text-3xl font-bold text-green-600 mb-4">
                        Płatność zakończona sukcesem!
                    </h1>
                    <p class="text-lg text-gray-700 mb-2">
                        {{ message }}
                    </p>
                    <p v-if="orderNumber" class="text-gray-600 mb-8">
                        Numer zamówienia: <span class="font-semibold">{{ orderNumber }}</span>
                    </p>

                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-8">
                        <p class="text-sm text-green-800">
                            Twoje zamówienie zostało przekazane do realizacji.
                            Otrzymasz powiadomienie SMS/Email gdy będzie gotowe.
                        </p>
                    </div>

                    <a
                        :href="route('tenant.menu')"
                        class="inline-block bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors"
                    >
                        Powrót do menu
                    </a>
                </div>

                <!-- Error State -->
                <div v-else>
                    <div class="text-6xl mb-4 text-red-500"><i class="fa-solid fa-xmark"></i></div>
                    <h1 class="text-3xl font-bold text-red-600 mb-4">
                        Problem z płatnością
                    </h1>
                    <p class="text-lg text-gray-700 mb-8">
                        {{ message }}
                    </p>

                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8">
                        <p class="text-sm text-red-800">
                            Jeśli kwota została pobrana z Twojego konta, skontaktuj się z nami.
                            Nie składaj ponownie tego samego zamówienia.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <a
                            :href="route('tenant.checkout')"
                            class="block bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors"
                        >
                            Spróbuj ponownie
                        </a>
                        <a
                            :href="route('tenant.menu')"
                            class="block text-gray-600 hover:text-gray-800 py-2"
                        >
                            Powrót do menu
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { useCartStore } from '@/Stores/cartStore'

const props = defineProps({
    success: {
        type: Boolean,
        required: true,
    },
    orderNumber: {
        type: String,
        default: null,
    },
    message: {
        type: String,
        required: true,
    },
})

const cartStore = useCartStore()

onMounted(() => {
    if (props.success) {
        cartStore.clearCart()
    }
})
</script>
