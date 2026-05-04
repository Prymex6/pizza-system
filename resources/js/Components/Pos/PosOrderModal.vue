<template>
    <div v-if="show" class="fixed inset-0 bg-black/60 z-50 flex items-stretch overflow-hidden">
        <div class="bg-white w-full max-w-5xl mx-auto flex flex-col overflow-hidden md:my-6 md:rounded-xl md:shadow-2xl">
            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900"><i class="fa-solid fa-plus mr-1"></i> Nowe zamówienie</h2>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-hidden flex flex-col md:flex-row">

                <!-- LEFT: Product Picker -->
                <div class="flex-1 flex flex-col overflow-hidden border-r border-gray-100">
                    <div class="px-4 pt-4 space-y-2">
                        <!-- Search -->
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Szukaj produktu..."
                                class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>

                        <!-- Category tabs -->
                        <div v-if="!searchQuery" class="flex flex-wrap gap-1.5 pb-1">
                            <button
                                v-if="featuredProducts.length"
                                @click="activeCategory = 'featured'"
                                :class="activeCategory === 'featured' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                            >
                                <i class="fa-solid fa-star mr-1"></i> Polecane
                            </button>
                            <button
                                v-for="cat in categories"
                                :key="cat.id"
                                @click="activeCategory = cat.id"
                                :class="activeCategory === cat.id ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                            >
                                {{ cat.icon ? cat.icon + ' ' : '' }}{{ cat.name }}
                            </button>
                        </div>
                        <p v-else class="text-xs text-gray-500 pb-1">
                            Wyniki dla: <span class="font-medium text-gray-700">{{ searchQuery }}</span>
                            — znaleziono {{ visibleProducts.length }}
                        </p>
                    </div>

                    <!-- Products -->
                    <div class="flex-1 overflow-y-auto p-4">
                        <div v-if="visibleProducts.length === 0" class="text-center py-10 text-gray-400 text-sm">
                            <i class="fa-solid fa-face-meh text-2xl mb-2 block"></i>
                            Brak produktów
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div
                                v-for="product in visibleProducts"
                                :key="product.id"
                                class="bg-white border border-gray-200 rounded-lg p-3 hover:border-blue-400 hover:shadow-sm transition-all"
                            >
                                <p class="font-medium text-gray-900 text-sm mb-1">{{ product.name }}</p>
                                <p v-if="product.description" class="text-xs text-gray-400 line-clamp-1 mb-1">{{ product.description }}</p>
                                <div class="flex flex-wrap gap-1 mb-1.5">
                                    <span
                                        v-for="variant in product.variants"
                                        :key="variant.id"
                                        class="px-1.5 py-0.5 bg-gray-100 text-gray-600 text-xs rounded"
                                    >{{ variant.name }} – {{ variant.price }} zł</span>
                                </div>
                                <div v-if="product.addons?.length" class="mb-1">
                                    <span class="text-xs text-green-600"><i class="fa-solid fa-plus mr-1"></i>{{ product.addons.length }} dodatków</span>
                                </div>
                                <button
                                    @click="selectedProduct = product"
                                    class="w-full py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors"
                                >
                                    <i class="fa-solid fa-plus mr-1"></i> Dodaj
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Cart + Form -->
                <div class="w-full md:w-80 flex flex-col overflow-hidden bg-gray-50">
                    <!-- Cart items -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-2">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Koszyk</h3>
                        <div v-if="cart.length === 0" class="text-center py-8 text-gray-400 text-sm">
                            Wybierz produkty z lewej
                        </div>
                        <div
                            v-for="(item, idx) in cart"
                            :key="idx"
                            class="bg-white rounded-lg border border-gray-200 px-3 py-2"
                        >
                            <div class="flex items-start gap-2">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">{{ item.name }} <span class="text-gray-500 font-normal text-xs">– {{ item.variant_name }}</span></p>
                                    <p v-if="item.addons?.length" class="text-xs text-green-700 mt-0.5">+ {{ item.addons.map(a => a.name).join(', ') }}</p>
                                    <p v-if="item.exclusions?.length" class="text-xs text-red-600 mt-0.5">BEZ: {{ item.exclusions.join(', ') }}</p>
                                    <p v-if="item.item_notes" class="text-xs text-amber-700 italic mt-0.5"><i class="fa-solid fa-comment mr-1"></i>{{ item.item_notes }}</p>
                                </div>
                                <div class="flex items-center gap-1 shrink-0 mt-0.5">
                                    <button @click="decreaseQty(idx)" class="w-6 h-6 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold"><i class="fa-solid fa-minus text-xs"></i></button>
                                    <span class="w-5 text-center text-sm font-semibold">{{ item.quantity }}</span>
                                    <button @click="increaseQty(idx)" class="w-6 h-6 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold">+</button>
                                    <span class="w-14 text-right text-sm text-gray-600">{{ itemTotal(item).toFixed(2) }} zł</span>
                                    <button @click="removeItem(idx)" class="w-6 h-6 rounded-full bg-red-50 hover:bg-red-100 text-red-500 text-xs ml-0.5"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="px-4 py-3 border-t border-gray-200 bg-white flex justify-between font-bold text-gray-900">
                        <span>Suma</span>
                        <span>{{ cartTotal.toFixed(2) }} zł</span>
                    </div>

                    <!-- Form -->
                    <div class="p-4 border-t border-gray-200 bg-white space-y-3 overflow-y-auto">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Typ zamówienia</label>
                            <select v-model="orderForm.type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500">
                                <option value="dine_in">Na miejscu</option>
                                <option value="pickup">Odbiór osobisty</option>
                                <option value="delivery">Dostawa</option>
                            </select>
                        </div>
                        <div v-if="orderForm.type === 'dine_in'">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Stolik</label>
                            <select v-model="orderForm.table_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500">
                                <option :value="null">— bez stolika —</option>
                                <option v-for="table in tables" :key="table.id" :value="table.id">
                                    Stolik #{{ table.number }}{{ table.name ? ' – ' + table.name : '' }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Imię klienta *</label>
                            <input v-model="orderForm.customer_name" type="text" placeholder="Jan Kowalski"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Telefon</label>
                            <input v-model="orderForm.customer_phone" type="tel" placeholder="123 456 789"
                                @blur="orderForm.customer_phone = formatPhone(orderForm.customer_phone)"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div v-if="orderForm.type === 'delivery'">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Adres dostawy *</label>
                            <input v-model="orderForm.delivery_address" type="text" placeholder="ul. Przykładowa 1"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Płatność</label>
                            <select v-model="orderForm.payment_method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500">
                                <option value="cash">Gotówka</option>
                                <option value="card_on_delivery">Karta</option>
                            </select>
                        </div>
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input type="checkbox" v-model="orderForm.is_paid" class="w-4 h-4 rounded text-green-600 border-gray-300 focus:ring-green-500" />
                            <span class="text-sm font-medium text-gray-700">Już zapłacone</span>
                        </label>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Kod rabatowy</label>
                            <input
                                v-model="orderForm.discount_code"
                                type="text"
                                placeholder="np. PROMO10"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500 uppercase"
                                @input="orderForm.discount_code = orderForm.discount_code.toUpperCase()"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Uwagi</label>
                            <textarea v-model="orderForm.notes" rows="2" placeholder="Uwagi do zamówienia..."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        </div>

                        <p v-if="orderError" class="text-red-600 text-sm bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ orderError }}</p>

                        <button
                            @click="submitOrder"
                            :disabled="cart.length === 0 || orderProcessing"
                            class="w-full py-3 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-bold rounded-lg transition-colors"
                        >
                            <template v-if="orderProcessing">Przyjmowanie...</template>
                            <template v-else><i class="fa-solid fa-check mr-1"></i> Przyjmij zamówienie</template>
                        </button>
                    </div>
                </div>
            </div>
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
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import ProductModal from '@/Components/Client/ProductModal.vue'
import { formatPhone } from '@/utils/phone'

const props = defineProps({
    show:       { type: Boolean, default: false },
    categories: { type: Array, default: () => [] },
    tables:     { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'success'])

const activeCategory  = ref(null)
const searchQuery     = ref('')
const cart            = ref([])
const orderProcessing = ref(false)
const orderError      = ref('')
const selectedProduct = ref(null)

const defaultForm = () => ({
    type:             'pickup',
    table_id:         null,
    customer_name:    '',
    customer_phone:   '',
    delivery_address: '',
    payment_method:   'cash',
    is_paid:          false,
    discount_code:    '',
    notes:            '',
})

const orderForm = ref(defaultForm())

// Reset when opened
watch(() => props.show, (val) => {
    if (val) {
        cart.value        = []
        orderError.value  = ''
        orderForm.value   = defaultForm()
        searchQuery.value = ''
        activeCategory.value = featuredProducts.value.length ? 'featured' : (props.categories?.[0]?.id || null)
    }
})

// ── Products ──────────────────────────────────────────────────────────────
const featuredProducts = computed(() => {
    const all = []
    props.categories?.forEach(cat => cat.products?.forEach(p => { if (p.is_featured) all.push(p) }))
    return all
})

const visibleProducts = computed(() => {
    const q = searchQuery.value.trim().toLowerCase()
    if (q) {
        const all = []
        props.categories?.forEach(cat => cat.products?.forEach(p => { if (p.name.toLowerCase().includes(q)) all.push(p) }))
        return all
    }
    if (activeCategory.value === 'featured') return featuredProducts.value
    return props.categories?.find(c => c.id === activeCategory.value)?.products || []
})

// ── Cart ──────────────────────────────────────────────────────────────────
const itemTotal  = (item) => {
    const addonSum = (item.addons || []).reduce((s, a) => s + parseFloat(a.price || 0), 0)
    return (parseFloat(item.price) + addonSum) * item.quantity
}
const cartTotal  = computed(() => cart.value.reduce((sum, i) => sum + itemTotal(i), 0))
const increaseQty = (idx) => { cart.value[idx].quantity++ }
const decreaseQty = (idx) => {
    if (cart.value[idx].quantity <= 1) cart.value.splice(idx, 1)
    else cart.value[idx].quantity--
}
const removeItem = (idx) => { cart.value.splice(idx, 1) }

const addFromModal = ({ product, variant, addons, exclusions, notes, quantity }) => {
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
            product_id:  product.id,
            variant_id:  variant.id,
            name:        product.name,
            variant_name: variant.name,
            price:       parseFloat(variant.price),
            addons,
            exclusions,
            item_notes:  notes,
            quantity,
        })
    }
    selectedProduct.value = null
}

// ── Submit ────────────────────────────────────────────────────────────────
const submitOrder = async () => {
    if (cart.value.length === 0) return
    orderError.value    = ''
    orderProcessing.value = true

    try {
        const payload = {
            type:             orderForm.value.type,
            table_id:         orderForm.value.table_id,
            customer_name:    orderForm.value.customer_name,
            customer_phone:   orderForm.value.customer_phone,
            delivery_address: orderForm.value.delivery_address,
            payment_method:   orderForm.value.payment_method,
            payment_status:   orderForm.value.is_paid ? 'paid' : 'pending',
            discount_code:    orderForm.value.discount_code || null,
            notes:            orderForm.value.notes || null,
            items: cart.value.map(i => ({
                product_id: i.product_id,
                variant_id: i.variant_id,
                quantity:   i.quantity,
                addons:     (i.addons || []).map(a => a.id),
                exclusions: i.exclusions || [],
                notes:      i.item_notes || null,
            })),
        }

        const response = await axios.post(route('tenant.staff.pos.store'), payload)

        if (response.data.success) {
            emit('success', response.data.order_number)
            emit('close')
        }
    } catch (err) {
        orderError.value = err.response?.data?.message || 'Wystąpił błąd. Spróbuj ponownie.'
    } finally {
        orderProcessing.value = false
    }
}
</script>

<style scoped>
.overflow-y-auto::-webkit-scrollbar { width: 4px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f3f4f6; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 2px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
</style>
