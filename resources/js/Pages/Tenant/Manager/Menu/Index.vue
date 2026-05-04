<template>
    <ManagerLayout title="Menu">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Zarządzanie Menu</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Zarządzaj kategoriami, produktami i dodatkami
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="showImportModal = true"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition"
                    >
                        <i class="fa-solid fa-file-import mr-2 text-white"></i>
                        Import CSV
                    </button>
                    <Link
                        :href="route('tenant.manager.menu.products.create')"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150"
                    >
                        <i class="fa-solid fa-plus mr-2 text-white"></i>
                        Dodaj Produkt
                    </Link>
                </div>
            </div>

            <!-- Categories and Products -->
            <div class="space-y-4">
                <div v-for="category in categories" :key="category.id" class="bg-white shadow rounded-lg overflow-hidden">
                    <!-- Category Header -->
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div class="flex items-center space-x-3">
                            <span class="text-2xl">{{ category.icon || '📁' }}</span>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">{{ category.name }}</h2>
                                <p class="text-sm text-gray-500">{{ category.products.length }} produktów</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button
                                @click="editCategory(category)"
                                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                            >
                                Edytuj
                            </button>
                            <button
                                @click="deleteCategory(category)"
                                class="text-red-600 hover:text-red-800 text-sm font-medium"
                            >
                                Usuń
                            </button>
                        </div>
                    </div>

                    <!-- Products List -->
                    <div class="divide-y divide-gray-200">
                        <div
                            v-for="product in category.products"
                            :key="product.id"
                            class="px-6 py-4 hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-base font-medium text-gray-900">
                                            {{ product.name }}
                                        </h3>
                                        <span
                                            v-if="product.is_featured"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                                        >
                                            <i class="fa-solid fa-star mr-1 text-yellow-400"></i> Polecane
                                        </span>
                                        <span
                                            v-if="!product.is_available"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                        >
                                            Niedostępne
                                        </span>
                                        <span
                                            v-if="product.track_stock"
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                product.stock_quantity <= 0
                                                    ? 'bg-red-100 text-red-800'
                                                    : product.stock_quantity <= 5
                                                    ? 'bg-orange-100 text-orange-800'
                                                    : 'bg-blue-100 text-blue-800'
                                            ]"
                                        >
                                            <i class="fa-solid fa-box mr-1 text-indigo-400"></i> {{ product.stock_quantity ?? 0 }} szt.
                                        </span>
                                    </div>
                                    <p v-if="product.description" class="mt-1 text-sm text-gray-600">
                                        {{ product.description }}
                                    </p>

                                    <!-- Variants -->
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span
                                            v-for="variant in product.variants"
                                            :key="variant.id"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700"
                                        >
                                            {{ variant.name }}: {{ formatPrice(variant.price) }}
                                        </span>
                                    </div>

                                    <!-- Addons -->
                                    <div v-if="product.addons && product.addons.length > 0" class="mt-2">
                                        <p class="text-xs text-gray-500">
                                            Dodatki: {{ product.addons.map(a => a.name).join(', ') }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center space-x-2 ml-4">
                                    <button
                                        @click="toggleAvailability(product)"
                                        :class="[
                                            product.is_available
                                                ? 'bg-green-100 text-green-800 hover:bg-green-200'
                                                : 'bg-gray-100 text-gray-800 hover:bg-gray-200',
                                            'px-3 py-1 rounded-md text-sm font-medium transition-colors'
                                        ]"
                                    >
                                        {{ product.is_available ? 'Dostępny' : 'Niedostępny' }}
                                    </button>
                                    <Link
                                        :href="route('tenant.manager.menu.products.edit', product.id)"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                    >
                                        Edytuj
                                    </Link>
                                    <button
                                        @click="deleteProduct(product)"
                                        class="text-red-600 hover:text-red-800 text-sm font-medium"
                                    >
                                        Usuń
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="category.products.length === 0" class="px-6 py-8 text-center text-gray-500">
                            Brak produktów w tej kategorii
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="categories.length === 0" class="bg-white shadow rounded-lg p-8 text-center">
                    <p class="text-gray-500">Nie masz jeszcze żadnych kategorii. Dodaj pierwszą kategorię, aby rozpocząć.</p>
                </div>
            </div>

            <!-- Add Category Button -->
            <div class="bg-white shadow rounded-lg p-6">
                <button
                    @click="showCategoryModal = true"
                    class="w-full flex items-center justify-center px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:border-gray-400 hover:bg-gray-50 transition-colors"
                >
                    <i class="fa-solid fa-plus mr-2 text-blue-500"></i>
                    Dodaj Nową Kategorię
                </button>
            </div>

            <!-- Addons Section -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Dodatki</h2>
                    <button
                        @click="showAddonModal = true"
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                    >
                        <i class="fa-solid fa-plus mr-1 text-blue-500"></i> Dodaj Dodatek
                    </button>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    <div
                        v-for="addon in addons"
                        :key="addon.id"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                    >
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ addon.name }}</p>
                            <p class="text-xs text-gray-600">{{ formatPrice(addon.price) }}</p>
                        </div>
                        <button
                            @click="deleteAddon(addon)"
                            class="text-red-600 hover:text-red-800 text-xs"
                        >
                            <i class="fa-solid fa-xmark text-red-400"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Modal -->
        <div
            v-if="showCategoryModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            @click.self="showCategoryModal = false"
        >
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingCategory ? 'Edytuj Kategorię' : 'Nowa Kategoria' }}
                </h3>
                <form @submit.prevent="saveCategory">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nazwa</label>
                            <input
                                v-model="categoryForm.name"
                                name="name"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ikona (emoji)</label>
                            <div class="flex items-center gap-2 mt-1">
                                <input
                                    v-model="categoryForm.icon"
                                    name="icon"
                                    type="text"
                                    maxlength="4"
                                    placeholder="🍕"
                                    class="block w-20 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-center text-xl"
                                >
                                <div class="flex flex-wrap gap-1">
                                    <button
                                        v-for="emoji in foodEmojis"
                                        :key="emoji"
                                        type="button"
                                        @click="categoryForm.icon = emoji"
                                        :class="categoryForm.icon === emoji ? 'ring-2 ring-blue-500' : ''"
                                        class="w-8 h-8 rounded hover:bg-gray-100 text-xl flex items-center justify-center"
                                    >{{ emoji }}</button>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kolejność</label>
                            <input
                                v-model.number="categoryForm.sort_order"
                                type="number"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>
                        <div v-if="editingCategory" class="flex items-center">
                            <input
                                v-model="categoryForm.is_active"
                                type="checkbox"
                                id="category-active"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                            <label for="category-active" class="ml-2 block text-sm text-gray-900">
                                Aktywna
                            </label>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeCategoryModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                        >
                            Anuluj
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                        >
                            Zapisz
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- CSV Import Modal -->
        <div
            v-if="showImportModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            @click.self="showImportModal = false"
        >
            <div class="relative top-20 mx-auto p-6 border w-[480px] shadow-lg rounded-md bg-white">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Import menu z CSV</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Plik CSV powinien zawierać kolumny: <code class="bg-gray-100 px-1 rounded">kategoria, nazwa, opis, wariant, cena, polecany, dostepny, skladniki, alergeny, kolejnosc</code>
                </p>
                <a
                    :href="route('tenant.manager.menu.import.template')"
                    class="text-sm text-blue-600 hover:underline mb-4 inline-block"
                >
                    Pobierz szablon CSV
                </a>
                <form @submit.prevent="submitImport" enctype="multipart/form-data">
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plik CSV</label>
                        <input
                            type="file"
                            accept=".csv,.txt"
                            @change="importFile = $event.target.files[0]"
                            required
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                        >
                    </div>
                    <div class="mt-5 flex justify-end gap-3">
                        <button
                            type="button"
                            @click="showImportModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                        >
                            Anuluj
                        </button>
                        <button
                            type="submit"
                            :disabled="importLoading"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 disabled:opacity-50"
                        >
                            {{ importLoading ? 'Importowanie...' : 'Importuj' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Addon Modal -->
        <div
            v-if="showAddonModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            @click.self="showAddonModal = false"
        >
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Nowy Dodatek</h3>
                <form @submit.prevent="saveAddon">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nazwa</label>
                            <input
                                v-model="addonForm.name"
                                name="name"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cena (PLN)</label>
                            <input
                                v-model.number="addonForm.price"
                                name="price"
                                type="number"
                                step="0.01"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="showAddonModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                        >
                            Anuluj
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                        >
                            Zapisz
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
    categories: Array,
    addons: Array,
})

const showCategoryModal = ref(false)
const showAddonModal = ref(false)
const showImportModal = ref(false)
const importFile = ref(null)
const importLoading = ref(false)

const submitImport = () => {
    if (!importFile.value) return
    importLoading.value = true
    const formData = new FormData()
    formData.append('file', importFile.value)
    router.post(route('tenant.manager.menu.import'), formData, {
        forceFormData: true,
        onSuccess: () => {
            showImportModal.value = false
            importFile.value = null
        },
        onFinish: () => { importLoading.value = false },
    })
}

// Emoji picker (#18)
const foodEmojis = ['🍕', '🍔', '🌮', '🌯', '🥙', '🥗', '🍜', '🍝', '🍲', '🍛', '🍣', '🍱', '🥩', '🍗', '🍖', '🥪', '🥞', '🧆', '🥚', '🍳', '🧀', '🥨', '🥐', '🥖', '🍞', '🧁', '🎂', '🍰', '🍩', '🍪', '🍦', '🍧', '🍨', '🍮', '🍫', '🍬', '🍭', '☕', '🧃', '🥤', '🍹', '🍸', '🍷', '🍻', '🧋', '🫖', '🥂', '🍺']
const editingCategory = ref(null)

const categoryForm = ref({
    name: '',
    icon: '',
    sort_order: 0,
    is_active: true,
})

const addonForm = ref({
    name: '',
    price: 0,
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN',
    }).format(price)
}

const editCategory = (category) => {
    editingCategory.value = category
    categoryForm.value = {
        name: category.name,
        icon: category.icon,
        sort_order: category.sort_order,
        is_active: category.is_active,
    }
    showCategoryModal.value = true
}

const closeCategoryModal = () => {
    showCategoryModal.value = false
    editingCategory.value = null
    categoryForm.value = {
        name: '',
        icon: '',
        sort_order: 0,
        is_active: true,
    }
}

const saveCategory = () => {
    if (editingCategory.value) {
        router.patch(route('tenant.manager.menu.categories.update', editingCategory.value.id), categoryForm.value, {
            onSuccess: () => closeCategoryModal()
        })
    } else {
        router.post(route('tenant.manager.menu.categories.store'), categoryForm.value, {
            onSuccess: () => closeCategoryModal()
        })
    }
}

const deleteCategory = (category) => {
    if (confirm(`Czy na pewno chcesz usunąć kategorię "${category.name}"?`)) {
        router.delete(route('tenant.manager.menu.categories.destroy', category.id))
    }
}

const deleteProduct = (product) => {
    if (confirm(`Czy na pewno chcesz usunąć produkt "${product.name}"?`)) {
        router.delete(route('tenant.manager.menu.products.destroy', product.id))
    }
}

const toggleAvailability = (product) => {
    router.post(route('tenant.manager.menu.products.toggle-availability', product.id))
}

const saveAddon = () => {
    router.post(route('tenant.manager.menu.addons.store'), addonForm.value, {
        onSuccess: () => {
            showAddonModal.value = false
            addonForm.value = { name: '', price: 0 }
        }
    })
}

const deleteAddon = (addon) => {
    if (confirm(`Czy na pewno chcesz usunąć dodatek "${addon.name}"?`)) {
        router.delete(route('tenant.manager.menu.addons.destroy', addon.id))
    }
}
</script>
