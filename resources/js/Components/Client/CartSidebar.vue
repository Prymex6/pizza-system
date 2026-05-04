<template>
    <Teleport to="body">
        <Transition name="sidebar">
            <div v-if="cartStore.isCartOpen" class="fixed inset-0 z-50 overflow-hidden">
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/50 transition-opacity"
                    @click="cartStore.closeCart()"
                ></div>

                <!-- Sidebar -->
                <div class="absolute inset-y-0 right-0 max-w-md w-full bg-white shadow-xl flex flex-col">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-bold text-gray-900">
                                Koszyk ({{ cartStore.itemCount }})
                            </h2>
                            <button
                                @click="cartStore.closeCart()"
                                class="text-gray-400 hover:text-gray-600 text-xl"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Cart Items -->
                    <div class="flex-1 overflow-y-auto px-6 py-4">
                        <!-- Removed items notice -->
                        <div v-if="removedNotice.length > 0" class="mb-4 p-3 bg-amber-50 border border-amber-300 rounded-lg text-sm text-amber-800">
                            <p class="font-semibold mb-1"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Niektóre produkty zostały usunięte z koszyka:</p>
                            <ul class="list-disc list-inside">
                                <li v-for="name in removedNotice" :key="name">{{ name }}</li>
                            </ul>
                        </div>

                        <div v-if="cartStore.items.length === 0" class="text-center py-12">
                            <div class="text-6xl mb-4"><i class="fa-solid fa-cart-shopping theme-primary"></i></div>
                            <p class="text-gray-500 text-lg">Koszyk jest pusty</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="item in cartStore.items"
                                :key="item.id"
                                class="bg-gray-50 rounded-lg p-4"
                            >
                                <!-- Item Header -->
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900">{{ item.product.name }}</h3>
                                        <p class="text-sm text-gray-600">{{ item.variant.name }}</p>
                                    </div>
                                    <button
                                        @click="cartStore.removeItem(item.id)"
                                        class="text-red-500 hover:text-red-700 ml-2"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>

                                <!-- Addons -->
                                <div v-if="item.selectedAddons.length" class="mb-2">
                                    <p class="text-xs text-gray-500">
                                        Dodatki: {{ item.selectedAddons.map(a => a.name).join(', ') }}
                                    </p>
                                </div>

                                <!-- Exclusions -->
                                <div v-if="item.exclusions.length" class="mb-2">
                                    <p class="text-xs text-red-600">
                                        Bez: {{ item.exclusions.join(', ') }}
                                    </p>
                                </div>

                                <!-- Notes -->
                                <div v-if="item.notes" class="mb-2">
                                    <p class="text-xs text-gray-500 italic">
                                        Uwagi: {{ item.notes }}
                                    </p>
                                </div>

                                <!-- Quantity & Price -->
                                <div class="flex items-center justify-between mt-3">
                                    <div class="flex items-center space-x-2">
                                        <button
                                            @click="cartStore.updateQuantity(item.id, item.quantity - 1)"
                                            class="w-8 h-8 rounded-full bg-white border hover:bg-gray-100 flex items-center justify-center"
                                        >
                                            <i class="fa-solid fa-minus text-gray-600"></i>
                                        </button>
                                        <span class="w-8 text-center font-semibold">{{ item.quantity }}</span>
                                        <button
                                            @click="cartStore.updateQuantity(item.id, item.quantity + 1)"
                                            class="w-8 h-8 rounded-full bg-white border hover:bg-gray-100 flex items-center justify-center"
                                        >
                                            +
                                        </button>
                                    </div>

                                    <div class="font-bold text-gray-900">
                                        {{ formatPrice(getItemTotal(item)) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div v-if="cartStore.items.length > 0" class="border-t px-6 py-4 bg-gray-50">
                        <!-- Subtotal -->
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-lg text-gray-600">Suma:</span>
                            <span class="text-2xl font-bold text-gray-900">
                                {{ formatPrice(cartStore.subtotal) }}
                            </span>
                        </div>

                        <!-- Checkout Button -->
                        <a
                            :href="route('tenant.checkout')"
                            class="block w-full bg-red-600 hover:bg-red-700 text-white text-center py-4 rounded-lg text-lg font-semibold transition-colors"
                        >
                            Przejdź do kasy
                        </a>

                        <!-- Clear Cart -->
                        <button
                            @click="clearCartConfirm"
                            class="w-full mt-3 text-red-600 hover:text-red-700 py-2 text-sm"
                        >
                            Wyczyść koszyk
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useCartStore } from '@/Stores/cartStore'

const cartStore = useCartStore()
const removedNotice = ref([])

watch(() => cartStore.isCartOpen, async (opened) => {
    if (!opened) return
    removedNotice.value = []
    const removed = await cartStore.validateCart()
    if (removed.length > 0) {
        removedNotice.value = removed
    }
})

const getItemTotal = (item) => {
    let total = parseFloat(item.variant.price) * item.quantity

    if (item.selectedAddons) {
        item.selectedAddons.forEach(addon => {
            total += parseFloat(addon.price) * item.quantity
        })
    }

    return total
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN',
    }).format(price)
}

const clearCartConfirm = () => {
    if (confirm('Czy na pewno chcesz wyczyścić koszyk?')) {
        cartStore.clearCart()
    }
}
</script>

<style scoped>
.sidebar-enter-active,
.sidebar-leave-active {
    transition: all 0.3s ease;
}

.sidebar-enter-from,
.sidebar-leave-to {
    transform: translateX(100%);
}
</style>
