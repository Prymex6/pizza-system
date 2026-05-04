<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show && product"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click.self="close"
            >
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50 transition-opacity" @click="close"></div>

                    <div class="relative bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                        <!-- Close button -->
                        <button
                            @click="close"
                            class="absolute top-4 right-4 z-10 text-gray-400 hover:text-gray-600 bg-white rounded-full w-10 h-10 flex items-center justify-center text-xl"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                        <!-- Product Image -->
                        <div class="relative aspect-video bg-gray-200">
                            <img
                                v-if="product.image"
                                :src="product.image"
                                :alt="product.name"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-8xl">
                                <span v-if="product.category?.icon || category?.icon">{{ product.category?.icon || category?.icon }}</span>
                                <i v-else class="fa-solid fa-utensils text-orange-400"></i>
                            </div>
                        </div>

                        <div class="p-6">
                            <!-- Product Name & Description -->
                            <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ product.name }}</h2>
                            <p v-if="product.description" class="text-gray-600 mb-4">{{ product.description }}</p>

                            <!-- Ingredients -->
                            <div v-if="product.ingredients && product.ingredients.length" class="mb-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-2">Składniki:</h3>
                                <p class="text-sm text-gray-600">{{ product.ingredients.join(', ') }}</p>
                            </div>

                            <!-- Allergens -->
                            <div v-if="product.allergens && product.allergens.length" class="mb-6">
                                <p class="text-xs text-gray-500">
                                    <i class="fa-solid fa-triangle-exclamation mr-1 text-amber-500"></i> Alergeny: {{ product.allergens.join(', ') }}
                                </p>
                            </div>

                            <!-- Variants Selection -->
                            <div v-if="product.variants && product.variants.length" class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Wybierz rozmiar:</h3>
                                <div class="grid grid-cols-3 gap-3">
                                    <button
                                        v-for="variant in product.variants"
                                        :key="variant.id"
                                        @click="selectedVariant = variant"
                                        class="border-2 rounded-lg p-4 text-center transition-all"
                                        :class="{
                                            'border-red-600 bg-red-50': selectedVariant?.id === variant.id,
                                            'border-gray-300 hover:border-blue-300': selectedVariant?.id !== variant.id,
                                        }"
                                    >
                                        <div class="font-semibold text-gray-900">{{ variant.name }}</div>
                                        <div class="text-red-600 font-bold mt-1">
                                            {{ formatPrice(variant.price) }}
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Addons Selection -->
                            <div v-if="availableAddons.length" class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Dodatki:</h3>
                                <div class="space-y-2">
                                    <label
                                        v-for="addon in availableAddons"
                                        :key="addon.id"
                                        class="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                                    >
                                        <div class="flex items-center">
                                            <input
                                                type="checkbox"
                                                :value="addon"
                                                v-model="selectedAddons"
                                                class="w-5 h-5 text-red-600 rounded"
                                            />
                                            <span class="ml-3 text-gray-900">{{ addon.name }}</span>
                                        </div>
                                        <span class="text-gray-600 font-semibold">
                                            +{{ formatPrice(addon.price) }}
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- Exclusions -->
                            <div v-if="product.ingredients && product.ingredients.length" class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Wyklucz składniki:</h3>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="ingredient in product.ingredients"
                                        :key="ingredient"
                                        @click="toggleExclusion(ingredient)"
                                        class="px-3 py-2 rounded-full text-sm transition-all"
                                        :class="{
                                            'bg-red-100 text-red-700 border-2 border-red-300': exclusions.includes(ingredient),
                                            'bg-gray-100 text-gray-700 border-2 border-transparent hover:border-gray-300': !exclusions.includes(ingredient),
                                        }"
                                    >
                                        <i v-if="exclusions.includes(ingredient)" class="fa-solid fa-xmark mr-1"></i>{{ ingredient }}
                                    </button>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="mb-6">
                                <label class="block text-lg font-semibold text-gray-900 mb-3">
                                    Uwagi do zamówienia:
                                </label>
                                <textarea
                                    v-model="notes"
                                    rows="3"
                                    placeholder="np. dobrze wypieczone, bez czosnku..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                ></textarea>
                            </div>

                            <!-- Quantity -->
                            <div class="mb-6">
                                <label class="block text-lg font-semibold text-gray-900 mb-3">Ilość:</label>
                                <div class="flex items-center space-x-4">
                                    <button
                                        @click="quantity = Math.max(1, quantity - 1)"
                                        class="w-10 h-10 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center font-bold"
                                    >
                                        <i class="fa-solid fa-minus text-gray-700"></i>
                                    </button>
                                    <span class="text-2xl font-bold w-12 text-center">{{ quantity }}</span>
                                    <button
                                        @click="quantity++"
                                        class="w-10 h-10 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center font-bold"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>

                            <!-- Total & Add to Cart -->
                            <div class="border-t pt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-lg text-gray-600">Suma:</span>
                                    <span class="text-3xl font-bold text-red-600">
                                        {{ formatPrice(totalPrice) }}
                                    </span>
                                </div>

                                <button
                                    @click="addToCart"
                                    :disabled="!selectedVariant"
                                    class="w-full bg-red-600 hover:bg-red-700 text-white py-4 rounded-lg text-lg font-semibold transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
                                >
                                    {{ useCartStore ? 'Dodaj do koszyka' : 'Dodaj do zamówienia' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useCartStore } from '@/Stores/cartStore'

const props = defineProps({
    show: Boolean,
    product: {
        type: [Object, null],
        default: null,
    },
    category: {
        type: [Object, null],
        default: null,
    },
    useCartStore: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['close', 'add'])

const cartStore = useCartStore()

const selectedVariant = ref(null)
const selectedAddons = ref([])
const exclusions = ref([])
const notes = ref('')
const quantity = ref(1)

const availableAddons = computed(() => {
    return props.product?.addons?.filter(addon => addon.is_available) || []
})

const totalPrice = computed(() => {
    if (!selectedVariant.value) return 0

    let total = parseFloat(selectedVariant.value.price)

    selectedAddons.value.forEach(addon => {
        total += parseFloat(addon.price)
    })

    return total * quantity.value
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN',
    }).format(price)
}

const toggleExclusion = (ingredient) => {
    const index = exclusions.value.indexOf(ingredient)
    if (index > -1) {
        exclusions.value.splice(index, 1)
    } else {
        exclusions.value.push(ingredient)
    }
}

const addToCart = () => {
    if (!selectedVariant.value) return

    if (props.useCartStore) {
        cartStore.addItem(
            props.product,
            selectedVariant.value,
            selectedAddons.value,
            exclusions.value,
            notes.value,
            quantity.value
        )
        cartStore.openCart()
    } else {
        emit('add', {
            product: props.product,
            variant: selectedVariant.value,
            addons: selectedAddons.value,
            exclusions: exclusions.value,
            notes: notes.value,
            quantity: quantity.value,
        })
    }

    close()
}

const close = () => {
    emit('close')
}

const resetForm = () => {
    selectedVariant.value = props.product.variants?.[0] || null
    selectedAddons.value = []
    exclusions.value = []
    notes.value = ''
    quantity.value = 1
}

watch(() => props.show, (newVal) => {
    if (newVal) {
        resetForm()
    }
})
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
