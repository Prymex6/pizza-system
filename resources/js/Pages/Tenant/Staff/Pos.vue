<template>
    <StaffLayout panelTitle="POS – Punkt Sprzedaży">
        <div class="max-w-6xl mx-auto grid md:grid-cols-2 gap-6">
            <!-- Product Picker -->
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900">Wybierz produkty</h2>

                <!-- Category tabs -->
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        @click="activeCategory = cat.id"
                        :class="activeCategory === cat.id
                            ? 'bg-blue-600 text-white'
                            : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                    >
                        {{ cat.icon ? cat.icon + ' ' : '' }}{{ cat.name }}
                    </button>
                </div>

                <!-- Products grid -->
                <div class="grid grid-cols-2 gap-3">
                    <template v-for="cat in categories" :key="cat.id">
                        <template v-if="activeCategory === cat.id">
                            <template v-for="product in cat.products" :key="product.id">
                            <div
                                v-if="product.is_available"
                                class="bg-white border border-gray-200 rounded-lg p-3 flex flex-col gap-2"
                            >
                                <div>
                                    <p class="font-medium text-gray-900 text-sm">{{ product.name }}</p>
                                    <p v-if="product.description" class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ product.description }}</p>
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        <span
                                            v-for="variant in product.variants"
                                            :key="variant.id"
                                            class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded font-medium"
                                        >
                                            {{ variant.name }} – {{ variant.price }} zł
                                        </span>
                                    </div>
                                    <div v-if="product.addons?.length" class="mt-1">
                                        <span class="text-xs text-green-600"><i class="fa-solid fa-plus mr-1"></i>{{ product.addons.length }} dodatków</span>
                                    </div>
                                    <div v-if="product.ingredients?.length" class="mt-0.5">
                                        <span class="text-xs text-gray-400"><i class="fa-solid fa-pen-to-square mr-1"></i>możliwość wykluczeń</span>
                                    </div>
                                </div>
                                <button
                                    @click="openProductModal(product)"
                                    class="w-full py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors"
                                >
                                    <i class="fa-solid fa-plus mr-1"></i> Dodaj do zamówienia
                                </button>
                            </div>
                            </template>
                        </template>
                    </template>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-5 h-fit sticky top-4">
                <h2 class="text-lg font-bold text-gray-900">Zamówienie</h2>

                <!-- Cart items -->
                <div class="space-y-2 max-h-72 overflow-y-auto">
                    <div v-if="cart.length === 0" class="text-center py-6 text-gray-400 text-sm">
                        Kliknij produkt aby dodać
                    </div>
                    <div
                        v-for="(item, idx) in cart"
                        :key="idx"
                        class="bg-gray-50 rounded-lg p-2.5 text-sm"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900">{{ item.name }}
                                    <span class="text-gray-500 font-normal"> – {{ item.variant_name }}</span>
                                </p>
                                <p v-if="item.addons?.length" class="text-xs text-green-700 mt-0.5">
                                    + {{ item.addons.map(a => a.name).join(', ') }}
                                </p>
                                <p v-if="item.exclusions?.length" class="text-xs text-red-600 mt-0.5">
                                    BEZ: {{ item.exclusions.join(', ') }}
                                </p>
                                <p v-if="item.item_notes" class="text-xs text-amber-700 italic mt-0.5">
                                    <i class="fa-solid fa-comment mr-1"></i>{{ item.item_notes }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button @click="decreaseQty(idx)" class="w-6 h-6 rounded-full bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 text-xs font-bold">-</button>
                                <span class="w-5 text-center font-semibold text-sm">{{ item.quantity }}</span>
                                <button @click="increaseQty(idx)" class="w-6 h-6 rounded-full bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 text-xs font-bold">+</button>
                                <button @click="removeItem(idx)" class="w-6 h-6 rounded-full bg-red-50 hover:bg-red-100 text-red-500 text-xs ml-1">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                                <span class="ml-1 text-gray-600 w-16 text-right text-xs font-semibold">{{ itemTotal(item).toFixed(2) }} zł</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total -->
                <div class="border-t border-gray-100 pt-3 flex justify-between font-bold text-gray-900">
                    <span>Suma</span>
                    <span>{{ total.toFixed(2) }} zł</span>
                </div>

                <!-- Form fields -->
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Typ zamówienia</label>
                        <select v-model="form.type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="dine_in">Na miejscu</option>
                            <option value="pickup">Odbiór osobisty</option>
                            <option value="delivery">Dostawa</option>
                        </select>
                    </div>
                    <div v-if="form.type === 'dine_in' && tables.length > 0">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Stolik</label>
                        <select v-model="form.table_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option :value="null">— bez stolika —</option>
                            <option v-for="table in tables" :key="table.id" :value="table.id">
                                Stolik #{{ table.number }}{{ table.name ? ' – ' + table.name : '' }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Imię klienta</label>
                        <input v-model="form.customer_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Jan Kowalski" required />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Telefon (opcjonalnie)</label>
                        <input v-model="form.customer_phone" type="tel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="123 456 789" @blur="form.customer_phone = formatPhone(form.customer_phone)" />
                    </div>
                    <div v-if="form.type === 'delivery'">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Adres dostawy</label>
                        <input v-model="form.delivery_address" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ul. Przykładowa 1, Kraków" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Płatność</label>
                        <select v-model="form.payment_method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="cash">Gotówka</option>
                            <option value="card_on_delivery">Karta</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Uwagi do zamówienia</label>
                        <textarea v-model="form.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Uwagi do całego zamówienia..."></textarea>
                    </div>
                </div>

                <button
                    @click="submitOrder"
                    :disabled="cart.length === 0 || processing"
                    class="w-full py-3 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-bold rounded-lg transition-colors"
                >
                    <i class="fa-solid fa-check mr-2"></i>
                    {{ processing ? 'Przyjmowanie...' : 'Przyjmij zamówienie' }}
                </button>
            </div>
        </div>

        <!-- Product customization modal -->
        <ProductModal
            :show="!!selectedProduct"
            :product="selectedProduct"
            :use-cart-store="false"
            @close="selectedProduct = null"
            @add="addFromModal"
        />
    </StaffLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import ProductModal from '@/Components/Client/ProductModal.vue'
import { formatPhone } from '@/utils/phone'

const props = defineProps({
    categories: Array,
    tables:     { type: Array, default: () => [] },
})

const activeCategory = ref(props.categories?.[0]?.id || null)
const cart = ref([])
const processing = ref(false)
const selectedProduct = ref(null)

const form = ref({
    type: 'dine_in',
    table_id: null,
    customer_name: '',
    customer_phone: '',
    delivery_address: '',
    payment_method: 'cash',
    notes: '',
})

const openProductModal = (product) => {
    selectedProduct.value = product
}

const addFromModal = ({ product, variant, addons, exclusions, notes, quantity }) => {
    // Check if identical item already in cart (same product, variant, addons, exclusions, notes)
    const existing = cart.value.find(i =>
        i.product_id === product.id &&
        i.variant_id === variant.id &&
        JSON.stringify(i.addons) === JSON.stringify(addons) &&
        JSON.stringify(i.exclusions) === JSON.stringify(exclusions) &&
        i.item_notes === notes
    )

    if (existing) {
        existing.quantity += quantity
    } else {
        cart.value.push({
            product_id: product.id,
            variant_id: variant.id,
            name: product.name,
            variant_name: variant.name,
            price: parseFloat(variant.price),
            addons: addons,
            exclusions: exclusions,
            item_notes: notes,
            quantity,
        })
    }

    selectedProduct.value = null
}

const itemTotal = (item) => {
    const addonSum = (item.addons || []).reduce((s, a) => s + parseFloat(a.price || 0), 0)
    return (item.price + addonSum) * item.quantity
}

const total = computed(() => cart.value.reduce((sum, item) => sum + itemTotal(item), 0))

const increaseQty = (idx) => { cart.value[idx].quantity++ }
const decreaseQty = (idx) => {
    if (cart.value[idx].quantity <= 1) {
        cart.value.splice(idx, 1)
    } else {
        cart.value[idx].quantity--
    }
}
const removeItem = (idx) => { cart.value.splice(idx, 1) }

const submitOrder = () => {
    if (cart.value.length === 0) return
    processing.value = true

    const items = cart.value.map(i => ({
        product_id: i.product_id,
        variant_id: i.variant_id,
        quantity: i.quantity,
        addons: i.addons.map(a => a.id),
        exclusions: i.exclusions,
        notes: i.item_notes || null,
    }))

    router.post(route('tenant.staff.pos.store'), {
        ...form.value,
        items,
    }, {
        onSuccess: () => {
            cart.value = []
            form.value = { type: 'dine_in', table_id: null, customer_name: '', customer_phone: '', delivery_address: '', payment_method: 'cash', notes: '' }
        },
        onFinish: () => { processing.value = false },
    })
}
</script>
