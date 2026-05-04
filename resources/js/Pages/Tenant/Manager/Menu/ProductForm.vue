<template>
    <ManagerLayout title="Produkt">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div>
                <Link
                    :href="route('tenant.manager.menu.index')"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium mb-4 inline-block"
                >
                    ← Powrót do menu
                </Link>
                <h1 class="text-3xl font-bold text-gray-900">
                    {{ product ? 'Edytuj Produkt' : 'Nowy Produkt' }}
                </h1>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info -->
                <div class="bg-white shadow rounded-lg p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Podstawowe informacje</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Zdjęcie produktu</label>
                        <ImageUpload v-model="form.image" field="product_image" preview-class="w-full h-40 object-cover" />
                        <p class="mt-1 text-xs text-gray-500">Zalecany format: kwadrat lub 4:3, min. 400x400px</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kategoria *</label>
                        <select
                            v-model="form.category_id"
                            name="category_id"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">Wybierz kategorię</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-600">{{ form.errors.category_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nazwa produktu *</label>
                        <input
                            v-model="form.name"
                            name="name"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="np. Pizza Margherita"
                        >
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Opis</label>
                        <textarea
                            v-model="form.description"
                            name="description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Krótki opis produktu..."
                        ></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="flex items-center">
                                <input
                                    v-model="form.is_featured"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                <span class="ml-2 text-sm text-gray-900"><i class="fa-solid fa-star mr-1 text-yellow-500"></i> Produkt polecany</span>
                            </label>
                        </div>
                        <div v-if="product">
                            <label class="flex items-center">
                                <input
                                    v-model="form.is_available"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                <span class="ml-2 text-sm text-gray-900">Dostępny</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Variants -->
                <div class="bg-white shadow rounded-lg p-6 space-y-4">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-900">Warianty (rozmiary) *</h2>
                        <button
                            type="button"
                            @click="addVariant"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                        >
                            <i class="fa-solid fa-plus mr-1 text-blue-500"></i> Dodaj wariant
                        </button>
                    </div>

                    <div v-for="(variant, index) in form.variants" :key="index" class="flex gap-3 items-start">
                        <div class="flex-1">
                            <input
                                v-model="variant.name"
                                :name="`variants[${index}][name]`"
                                type="text"
                                placeholder="np. Mała (30cm)"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>
                        <div class="w-32">
                            <input
                                v-model.number="variant.price"
                                :name="`variants[${index}][price]`"
                                type="number"
                                step="0.01"
                                placeholder="Cena"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>
                        <button
                            v-if="form.variants.length > 1"
                            type="button"
                            @click="removeVariant(index)"
                            class="text-red-600 hover:text-red-800 px-2 py-2"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <p v-if="form.errors.variants" class="text-sm text-red-600">{{ form.errors.variants }}</p>
                </div>

                <!-- Ingredients -->
                <div class="bg-white shadow rounded-lg p-6 space-y-4">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-900">Składniki</h2>
                        <button
                            type="button"
                            @click="addIngredient"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                        >
                            <i class="fa-solid fa-plus mr-1 text-blue-500"></i> Dodaj składnik
                        </button>
                    </div>

                    <div v-for="(ingredient, index) in form.ingredients" :key="index" class="flex gap-3 items-center">
                        <input
                            v-model="form.ingredients[index]"
                            type="text"
                            placeholder="np. Mozzarella"
                            class="flex-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        <button
                            type="button"
                            @click="removeIngredient(index)"
                            class="text-red-600 hover:text-red-800 px-2 py-2"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <p class="text-xs text-gray-500">
                        Składniki będą widoczne dla klientów. Pozwalają wykluczać składniki z zamówienia.
                    </p>
                </div>

                <!-- Allergens -->
                <div class="bg-white shadow rounded-lg p-6 space-y-4">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-900">Alergeny</h2>
                        <button
                            type="button"
                            @click="addAllergen"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                        >
                            <i class="fa-solid fa-plus mr-1 text-blue-500"></i> Dodaj alergen
                        </button>
                    </div>

                    <div v-for="(allergen, index) in form.allergens" :key="index" class="flex gap-3 items-center">
                        <input
                            v-model="form.allergens[index]"
                            type="text"
                            placeholder="np. Gluten"
                            class="flex-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        <button
                            type="button"
                            @click="removeAllergen(index)"
                            class="text-red-600 hover:text-red-800 px-2 py-2"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <p class="text-xs text-gray-500">
                        Informacja o alergenach pomoże klientom z alergiami pokarmowymi.
                    </p>
                </div>

                <!-- Tags -->
                <div class="bg-white shadow rounded-lg p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Etykiety produktu</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <label
                            v-for="tag in [
                                { value: 'wege',      label: '🌿 Wegetariańskie' },
                                { value: 'vegan',     label: '🌱 Wegańskie' },
                                { value: 'bezgluten', label: '🌾 Bez glutenu' },
                                { value: 'ostra',     label: '🌶️ Ostra potrawa' },
                            ]"
                            :key="tag.value"
                            class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer"
                        >
                            <input
                                v-model="form.tags"
                                type="checkbox"
                                :value="tag.value"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                            <span class="ml-2 text-sm text-gray-900">{{ tag.label }}</span>
                        </label>
                    </div>
                </div>

                <!-- Addons -->
                <div class="bg-white shadow rounded-lg p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Dostępne dodatki</h2>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <label
                            v-for="addon in addons"
                            :key="addon.id"
                            class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer"
                        >
                            <input
                                v-model="form.addon_ids"
                                type="checkbox"
                                :value="addon.id"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                            <span class="ml-2 text-sm text-gray-900">
                                {{ addon.name }} (+{{ formatPrice(addon.price) }})
                            </span>
                        </label>
                    </div>

                    <p class="text-xs text-gray-500">
                        Wybierz dodatki, które będą dostępne dla tego produktu.
                    </p>
                </div>

                <!-- Stock Management -->
                <div class="bg-white shadow rounded-lg p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Zarządzanie zapasami</h2>

                    <label class="flex items-center">
                        <input
                            v-model="form.track_stock"
                            type="checkbox"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-900">Śledź stan magazynowy</span>
                    </label>

                    <div v-if="form.track_stock">
                        <label class="block text-sm font-medium text-gray-700">Ilość w magazynie (sztuki)</label>
                        <input
                            v-model.number="form.stock_quantity"
                            type="number"
                            min="0"
                            class="mt-1 block w-40 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        <p class="mt-1 text-xs text-gray-500">
                            Produkt zostanie automatycznie oznaczony jako niedostępny gdy stan osiągnie 0.
                        </p>
                    </div>
                </div>

                <!-- Advanced -->
                <div class="bg-white shadow rounded-lg p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Zaawansowane</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kolejność sortowania</label>
                        <input
                            v-model.number="form.sort_order"
                            type="number"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        <p class="mt-1 text-xs text-gray-500">
                            Mniejsza liczba = wyższa pozycja na liście
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <Link
                        :href="route('tenant.manager.menu.index')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                    >
                        Anuluj
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Zapisywanie...' : 'Zapisz produkt' }}
                    </button>
                </div>
            </form>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import ImageUpload from '@/Components/ImageUpload.vue'

const props = defineProps({
    product: Object,
    categories: Array,
    addons: Array,
})

const form = useForm({
    category_id: props.product?.category_id || '',
    name: props.product?.name || '',
    description: props.product?.description || '',
    image: props.product?.image || '',
    ingredients: props.product?.ingredients || [],
    allergens: props.product?.allergens || [],
    tags: props.product?.tags || [],
    is_featured: props.product?.is_featured || false,
    is_available: props.product?.is_available ?? true,
    sort_order: props.product?.sort_order || 0,
    track_stock: props.product?.track_stock || false,
    stock_quantity: props.product?.stock_quantity ?? null,
    variants: props.product?.variants?.map(v => ({
        id: v.id,
        name: v.name,
        price: v.price
    })) || [{ name: '', price: 0 }],
    addon_ids: props.product?.addons?.map(a => a.id) || [],
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN',
    }).format(price)
}

const addVariant = () => {
    form.variants.push({ name: '', price: 0 })
}

const removeVariant = (index) => {
    form.variants.splice(index, 1)
}

const addIngredient = () => {
    form.ingredients.push('')
}

const removeIngredient = (index) => {
    form.ingredients.splice(index, 1)
}

const addAllergen = () => {
    form.allergens.push('')
}

const removeAllergen = (index) => {
    form.allergens.splice(index, 1)
}

const submit = () => {
    if (props.product) {
        form.patch(route('tenant.manager.menu.products.update', props.product.id))
    } else {
        form.post(route('tenant.manager.menu.products.store'))
    }
}
</script>
