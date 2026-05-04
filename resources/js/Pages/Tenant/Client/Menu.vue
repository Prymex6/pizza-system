<template>
    <Head>
        <title>{{ tenantName }} – Menu</title>
        <meta name="description" :content="tenant?.description || ('Sprawdź menu ' + tenantName + ' i zamów online.')" />
    </Head>

    <ClientLayout>
        <!-- Closed banner ABOVE hero -->
        <div v-if="!tenant?.is_open_now && !tenant?.vacation_mode" class="bg-orange-50 border-b border-orange-200 py-4 px-4">
            <div class="container mx-auto max-w-4xl flex items-center justify-center gap-3 text-center flex-wrap">
                <i class="fa-solid fa-lock text-2xl text-orange-700"></i>
                <div>
                    <p class="font-bold text-orange-800 text-lg">Restauracja jest teraz zamknięta</p>
                    <p v-if="nextOpenTime" class="text-orange-700 text-sm mt-0.5">Zamawianie online możliwe od {{ nextOpenTime }}</p>
                    <p v-else class="text-orange-700 text-sm mt-0.5">Sprawdź godziny otwarcia lub zadzwoń do nas</p>
                </div>
            </div>
        </div>

        <!-- Hero Section -->
        <section
            class="relative flex items-center justify-center bg-cover bg-center" style="height: calc(100vh - 4rem)"
            :style="{ backgroundImage: heroBackground }"
        >
            <div
                class="absolute inset-0 bg-gradient-to-b from-black/30 to-black/70"
            ></div>
            <div
                class="relative z-10 text-center text-white px-4 max-w-5xl mx-auto"
            >
                <h1
                    class="text-3xl sm:text-5xl md:text-7xl font-extrabold mb-4 leading-tight"
                >
                    {{ heroTitle }}
                </h1>
                <p
                    v-if="heroSubtitle"
                    class="text-base sm:text-xl md:text-2xl mb-7 text-gray-100 max-w-3xl mx-auto font-light"
                >
                    {{ heroSubtitle }}
                </p>
                <div class="flex flex-col sm:grid sm:grid-cols-2 gap-3 sm:gap-5 mb-8 w-full max-w-sm sm:max-w-none mx-auto">
                    <a
                        href="#menu"
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 sm:py-4 px-6 sm:px-10 rounded-full text-base sm:text-lg shadow-2xl transition transform hover:scale-105 flex items-center justify-center"
                    >
                        <i class="fa-solid fa-book-open mr-2"></i>
                        Zobacz menu
                    </a>
                    <button
                        @click="cartStore.toggleCart()"
                        class="bg-white hover:bg-gray-100 text-red-700 font-bold py-3 sm:py-4 px-6 sm:px-10 rounded-full text-base sm:text-lg shadow-2xl transition transform hover:scale-105 flex items-center justify-center"
                    >
                        <i class="fa-solid fa-cart-shopping mr-2"></i>
                        Zamów online
                    </button>

                    <template v-if="!customer">
                        <Link
                            :href="route('tenant.client.register')"
                            class="border-2 border-white/80 text-white hover:bg-white hover:text-red-700 font-bold py-2.5 px-6 sm:px-10 rounded-full text-base sm:text-lg shadow-2xl transition transform hover:scale-105 flex items-center justify-center backdrop-blur-sm"
                        >
                            Zarejestruj się
                        </Link>
                        <Link
                            :href="route('tenant.client.login')"
                            class="border-2 border-white/80 text-white hover:bg-white hover:text-red-700 font-bold py-2.5 px-6 sm:px-10 rounded-full text-base sm:text-lg shadow-2xl transition transform hover:scale-105 flex items-center justify-center backdrop-blur-sm"
                        >
                            Zaloguj
                        </Link>
                    </template>
                </div>
            </div>

            <!-- Scroll indicator - positioned at very bottom -->
            <div
                class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce hidden md:block"
            >
                <a href="#menu" class="text-white/80 hover:text-white">
                    <i class="fa-solid fa-chevron-down text-3xl"></i>
                </a>
            </div>
        </section>

        <!-- Orders paused banner BELOW hero -->
        <div v-if="tenant?.orders_paused && !tenant?.vacation_mode" class="bg-orange-50 border-b border-orange-200 py-4 px-4">
            <div class="container mx-auto max-w-4xl flex items-center justify-center gap-3 text-center flex-wrap">
                <i class="fa-solid fa-pause-circle text-2xl text-orange-700"></i>
                <div>
                    <p class="font-bold text-orange-800 text-lg">Przyjmowanie zamówień wstrzymane</p>
                    <p class="text-orange-700 text-sm mt-0.5">Restauracja chwilowo nie przyjmuje zamówień online. Zapraszamy wkrótce!</p>
                </div>
            </div>
        </div>

        <!-- Closed restaurant banner BELOW hero -->
        <div v-if="!tenant?.is_open_now && !tenant?.vacation_mode && !tenant?.orders_paused" class="bg-orange-50 border-b border-orange-200 py-4 px-4">
            <div class="container mx-auto max-w-4xl flex items-center justify-center gap-3 text-center flex-wrap">
                <i class="fa-solid fa-lock text-2xl text-orange-700"></i>
                <div>
                    <p class="font-bold text-orange-800 text-lg">Restauracja jest teraz zamknięta</p>
                    <p v-if="nextOpenTime" class="text-orange-700 text-sm mt-0.5">Zamawianie online możliwe od {{ nextOpenTime }}</p>
                    <p v-else class="text-orange-700 text-sm mt-0.5">Sprawdź godziny otwarcia lub zadzwoń do nas</p>
                </div>
            </div>
        </div>

        <!-- Homepage Blocks (Level C) -->
        <template v-for="block in activeBlocks" :key="block.id">
            <!-- Announcement block -->
            <section
                v-if="block.type === 'announcement'"
                class="py-4 px-4 text-center"
                :style="{ backgroundColor: block.bg_color || '#fef3c7' }"
            >
                <div class="container mx-auto max-w-4xl">
                    <p v-if="block.title" class="font-bold text-lg text-gray-900">{{ block.title }}</p>
                    <p v-if="block.content" class="text-gray-700 mt-1">{{ block.content }}</p>
                </div>
            </section>

            <!-- Promo text block -->
            <section
                v-else-if="block.type === 'promo_text'"
                class="py-16 bg-gray-50"
            >
                <div class="container mx-auto px-4 lg:px-8 max-w-3xl text-center">
                    <h2 v-if="block.title" class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{{ block.title }}</h2>
                    <p v-if="block.content" class="text-gray-600 text-lg leading-relaxed whitespace-pre-line">{{ block.content }}</p>
                </div>
            </section>

            <!-- CTA block -->
            <section
                v-else-if="block.type === 'cta'"
                class="py-12 bg-white border-t border-gray-100 text-center"
            >
                <div class="container mx-auto px-4">
                    <p v-if="block.title" class="text-2xl font-bold text-gray-900 mb-6">{{ block.title }}</p>
                    <a
                        v-if="block.link_url"
                        :href="block.link_url"
                        class="inline-block text-white font-bold py-4 px-10 rounded-full text-lg shadow-lg transition transform hover:scale-105 theme-primary-bg"
                    >
                        {{ block.link_text || block.title || 'Kliknij tutaj' }}
                    </a>
                </div>
            </section>
        </template>

        <!-- About Us Section -->
        <section
            v-if="tenant?.about_enabled && tenant?.about_text"
            class="py-10 sm:py-20 bg-white"
        >
            <div class="container mx-auto px-4 lg:px-8">
                <div
                    class="max-w-6xl mx-auto"
                    :class="
                        tenant.about_image_url
                            ? 'grid md:grid-cols-2 gap-8 md:gap-12 items-center'
                            : ''
                    "
                >
                    <div>
                        <span
                            class="text-red-600 font-semibold text-sm uppercase tracking-[0.25em]"
                            >Poznaj nas</span
                        >
                        <h2 class="text-2xl sm:text-4xl md:text-5xl font-bold mt-3 mb-4 sm:mb-6">
                            {{ tenant.about_title || "O nas" }}
                        </h2>
                        <div
                            class="text-gray-600 text-lg leading-relaxed whitespace-pre-line"
                        >
                            {{ tenant.about_text }}
                        </div>
                    </div>
                    <div
                        v-if="tenant.about_image_url"
                        class="rounded-3xl overflow-hidden shadow-xl"
                    >
                        <img
                            :src="tenant.about_image_url"
                            alt="O nas"
                            class="w-full h-80 object-cover"
                            loading="lazy"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- Menu Section -->
        <section id="menu" class="py-10 sm:py-20 bg-gray-50">
            <div class="container mx-auto px-3 sm:px-4 lg:px-8">
                <div class="text-center mb-8 sm:mb-16">
                    <span
                        class="text-red-600 font-semibold text-sm uppercase tracking-[0.25em]"
                        >Nasze specjały</span
                    >
                    <h2 class="text-3xl md:text-5xl font-bold mt-3 mb-4">
                        Menu
                    </h2>
                    <p class="text-gray-600 max-w-2xl mx-auto text-base sm:text-lg">
                        Wybierz swoją ulubioną pozycję - każde danie możesz
                        dodać do koszyka jednym kliknięciem.
                    </p>
                    <!-- Delivery time badge (#30) -->
                    <div v-if="tenant?.estimated_preparation_time && tenant?.is_open_now" class="mt-4 inline-flex items-center gap-2 bg-green-50 text-green-800 px-4 py-2 rounded-full text-sm font-medium">
                        <i class="fa-solid fa-clock text-green-600"></i>
                        Czas dostawy: ok. {{ tenant.estimated_preparation_time }} min
                    </div>
                </div>

                <!-- Search -->
                <div class="mb-8 max-w-lg mx-auto">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Szukaj produktu..."
                            class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 bg-white shadow-sm focus:ring-2 focus:ring-red-500 focus:border-transparent text-gray-800"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        ><i class="fa-solid fa-xmark"></i></button>
                    </div>
                </div>

                <!-- Featured Products -->
                <div v-if="featuredProducts.length" class="mb-10 sm:mb-20">
                    <div
                        class="flex items-center gap-3 mb-5 sm:mb-8 border-b-2 border-red-200 pb-3"
                    >
                        <i class="fa-solid fa-star text-2xl sm:text-4xl text-yellow-400"></i>
                        <h3
                            class="text-2xl md:text-4xl font-bold text-gray-800"
                        >
                            Polecane
                        </h3>
                    </div>
                    <div class="space-y-3">
                        <ProductCard
                            v-for="product in featuredProducts"
                            :key="product.id"
                            :product="product"
                            @click="openProductModal(product)"
                        />
                    </div>
                </div>

                <!-- Categories & Products (accordion #16) -->
                <div
                    v-for="(category, catIdx) in filteredCategories"
                    :key="category.id"
                    class="mb-4 border border-gray-200 rounded-xl overflow-hidden"
                >
                    <!-- Accordion header -->
                    <button
                        type="button"
                        @click="toggleCategory(category.id)"
                        class="w-full flex items-center justify-between gap-3 px-4 sm:px-6 py-3 sm:py-4 bg-white hover:bg-gray-50 transition-colors text-left"
                    >
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span v-if="category.icon" class="text-2xl sm:text-3xl">{{ category.icon }}</span>
                            <h3 class="text-lg sm:text-2xl font-bold text-gray-800">{{ category.name }}</h3>
                            <span class="text-sm text-gray-400 font-normal">({{ category.products.length }})</span>
                        </div>
                        <i
                            class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-300"
                            :class="openCategories.has(category.id) ? 'rotate-180' : ''"
                        ></i>
                    </button>

                    <!-- Accordion content -->
                    <div v-show="openCategories.has(category.id)" class="px-3 sm:px-6 pb-4 sm:pb-6 pt-2 border-t border-gray-100">
                        <div
                            v-if="category.products.length"
                            class="space-y-3"
                        >
                            <ProductCard
                                v-for="product in category.products"
                                :key="product.id"
                                :product="product"
                                :category="category"
                                @click="openProductModal(product, category)"
                            />
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            Brak produktów w tej kategorii
                        </div>
                    </div>
                </div>

                <!-- Empty State (search no results) -->
                <div
                    v-if="searchQuery && !filteredCategories.some(c => c.products.length)"
                    class="text-center py-16"
                >
                    <div class="text-6xl mb-4"><i class="fa-solid fa-magnifying-glass text-gray-400"></i></div>
                    <h3 class="text-2xl font-semibold text-gray-900 mb-2">
                        Brak wyników
                    </h3>
                    <p class="text-gray-600">
                        Nie znaleziono produktów pasujących do „{{ searchQuery }}"
                    </p>
                    <button @click="searchQuery = ''" class="mt-4 text-red-600 hover:text-red-700 font-medium">
                        Wyczyść wyszukiwanie
                    </button>
                </div>

                <!-- Empty State -->
                <div
                    v-else-if="
                        !filteredCategories.length && !featuredProducts.length
                    "
                    class="text-center py-16"
                >
                    <div class="text-6xl mb-4"><i class="fa-solid fa-utensils text-orange-400"></i></div>
                    <h3 class="text-2xl font-semibold text-gray-900 mb-2">
                        Menu w przygotowaniu
                    </h3>
                    <p class="text-gray-600">
                        Wkrótce dostępne będą pyszne dania!
                    </p>
                </div>
            </div>
        </section>

        <!-- Gallery Section -->
        <section
            v-if="tenant?.gallery_enabled && galleryImages.length"
            class="py-20 bg-white"
        >
            <div class="container mx-auto px-4 lg:px-8">
                <div class="text-center mb-14">
                    <span
                        class="text-red-600 font-semibold text-sm uppercase tracking-[0.25em]"
                        >Zdjęcia</span
                    >
                    <h2 class="text-4xl md:text-5xl font-bold mt-3">
                        {{ tenant.gallery_title || "Galeria" }}
                    </h2>
                </div>
                <div
                    class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
                >
                    <div
                        v-for="(img, index) in galleryImages"
                        :key="index"
                        class="rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition cursor-pointer group"
                        :class="
                            index === 0 ? 'md:col-span-2 md:row-span-2' : ''
                        "
                        @click="openLightbox(index)"
                    >
                        <img
                            :src="img"
                            alt="Galeria"
                            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                            :class="index === 0 ? 'h-64 md:h-full' : 'h-48'"
                            loading="lazy"
                        />
                    </div>
                </div>
            </div>

            <!-- Lightbox -->
            <Teleport to="body">
                <div
                    v-if="lightboxOpen"
                    class="fixed inset-0 z-[100] bg-black/90 flex items-center justify-center p-4"
                    @click.self="lightboxOpen = false"
                >
                    <button
                        @click="lightboxOpen = false"
                        class="absolute top-6 right-6 text-white text-3xl hover:text-gray-300"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <button
                        v-if="lightboxIndex > 0"
                        @click="lightboxIndex--"
                        class="absolute left-4 text-white text-4xl hover:text-gray-300"
                    >
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button
                        v-if="lightboxIndex < galleryImages.length - 1"
                        @click="lightboxIndex++"
                        class="absolute right-4 text-white text-4xl hover:text-gray-300"
                    >
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <img
                        :src="galleryImages[lightboxIndex]"
                        class="max-h-[85vh] max-w-[90vw] object-contain rounded-lg"
                    />
                </div>
            </Teleport>
        </section>

        <!-- Social Proof Section (Google Reviews) -->
        <section
            v-if="googleReviews.length"
            id="opinie"
            class="py-20 bg-gray-50"
        >
            <div class="container mx-auto px-4 lg:px-8">
                <div class="text-center mb-14">
                    <span
                        class="text-red-600 font-semibold text-sm uppercase tracking-[0.25em]"
                        >Opinie</span
                    >
                    <h2 class="text-4xl md:text-5xl font-bold mt-3">
                        Co mówią nasi goście
                    </h2>
                    <div
                        v-if="googleRating"
                        class="mt-4 flex items-center justify-center gap-3"
                    >
                        <div class="flex gap-0.5">
                            <span v-for="n in 5" :key="'overall-' + n" class="relative inline-block text-lg text-gray-300">
                                <i class="fa-solid fa-star"></i>
                                <span v-if="starFill(googleRating, n) > 0" class="absolute inset-0 overflow-hidden text-yellow-400" :style="{ width: starFill(googleRating, n) + '%' }">
                                    <i class="fa-solid fa-star"></i>
                                </span>
                            </span>
                        </div>
                        <span class="text-2xl font-bold text-gray-800">{{
                            googleRating.toFixed(1)
                        }}</span>
                        <span
                            v-if="googleTotalReviews"
                            class="text-gray-500 text-sm"
                            >({{ googleTotalReviews }} opinii w Google)</span
                        >
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 max-w-7xl mx-auto">
                    <div
                        v-for="(review, index) in googleReviews"
                        :key="index"
                        class="bg-white p-5 rounded-2xl shadow-md border border-gray-100 hover:shadow-xl transition flex flex-col"
                    >
                        <div class="flex items-center mb-3">
                            <div class="flex gap-0.5 text-sm">
                                <span v-for="n in 5" :key="'star-' + n" class="relative inline-block text-gray-300">
                                    <i class="fa-solid fa-star"></i>
                                    <span v-if="starFill(review.rating, n) > 0" class="absolute inset-0 overflow-hidden text-yellow-400" :style="{ width: starFill(review.rating, n) + '%' }">
                                        <i class="fa-solid fa-star"></i>
                                    </span>
                                </span>
                            </div>
                            <span
                                class="ml-2 text-xs px-2 py-0.5 rounded-full font-semibold bg-green-100 text-green-800"
                            >
                                Google
                            </span>
                        </div>
                        <p class="text-gray-700 italic text-sm line-clamp-4 flex-1">
                            {{ review.text }}
                        </p>
                        <div class="mt-4 flex items-center gap-2 pt-3 border-t border-gray-100">
                            <img
                                v-if="review.profile_photo_url"
                                :src="review.profile_photo_url"
                                :alt="review.author_name"
                                class="w-8 h-8 rounded-full object-cover"
                                referrerpolicy="no-referrer"
                            />
                            <div
                                v-else
                                class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold text-sm"
                            >
                                {{
                                    review.author_name?.charAt(0)?.toUpperCase()
                                }}
                            </div>
                            <div>
                                <span class="font-medium block">{{
                                    review.author_name
                                }}</span>
                                <span
                                    v-if="review.relative_time_description"
                                    class="text-xs text-gray-400"
                                    >{{
                                        review.relative_time_description
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Product Modal -->
        <ProductModal
            :show="showProductModal"
            :product="selectedProduct"
            :category="selectedCategory"
            @close="showProductModal = false"
        />
    </ClientLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { useCartStore } from "@/Stores/cartStore";
import ClientLayout from "@/Layouts/ClientLayout.vue";
import ProductCard from "@/Components/Client/ProductCard.vue";
import ProductModal from "@/Components/Client/ProductModal.vue";

const page = usePage();
const cartStore = useCartStore();

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    featuredProducts: {
        type: Array,
        default: () => [],
    },
    googleReviews: {
        type: Array,
        default: () => [],
    },
    googleRating: {
        type: Number,
        default: null,
    },
    googleTotalReviews: {
        type: Number,
        default: 0,
    },
    structuredData: {
        type: Object,
        default: null,
    },
});

const tenant = computed(() => page.props.tenant);
const tenantName = computed(() => tenant.value?.name || "Restauracja");
const customer = computed(() => page.props.auth?.customer);

// Accordion categories (#16) - first category open by default
const openCategories = ref(new Set(props.categories.length > 0 ? [props.categories[0].id] : []))
const toggleCategory = (id) => {
    const next = new Set(openCategories.value)
    if (next.has(id)) {
        next.delete(id)
    } else {
        next.add(id)
    }
    openCategories.value = next
}

const heroTitle = computed(() => tenant.value?.hero_title || tenantName.value);
const heroSubtitle = computed(
    () => tenant.value?.hero_subtitle || tenant.value?.description || 'Świeże składniki, niepowtarzalne smaki – zamów już teraz!',
);

// Next opening time for closed banner
const nextOpenTime = computed(() => {
    const hours = tenant.value?.opening_hours
    if (!hours) return null
    const parsed = typeof hours === 'string' ? JSON.parse(hours) : hours
    const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']
    const dayLabels = ['Niedz', 'Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob']
    const now = new Date()
    const todayIdx = now.getDay()
    const currentMinutes = now.getHours() * 60 + now.getMinutes()
    const toMin = (t) => { const [h, m] = (t || '00:00').split(':'); return +h * 60 + +m }

    for (let i = 0; i < 7; i++) {
        const idx = (todayIdx + i) % 7
        const day = parsed[dayNames[idx]]
        // Only treat as open if enabled is explicitly true and not closed
        const isOpen = day && day.enabled === true && !day.closed
        if (!isOpen) continue
        // If checking today, only show if opening time hasn't passed yet
        if (i === 0 && currentMinutes >= toMin(day.open)) continue
        if (i === 0) return `dziś ${day.open}`
        // Only say "jutro" if the very next open day is actually tomorrow (i===1)
        const tomorrowIdx = (todayIdx + 1) % 7
        if (idx === tomorrowIdx) return `jutro ${day.open}`
        return `${dayLabels[idx]} ${day.open}`
    }
    return null
})

// Homepage blocks (Level C)
const activeBlocks = computed(() => {
    const raw = tenant.value?.homepage_blocks
    let blocks = []
    if (!raw) return []
    if (Array.isArray(raw)) blocks = raw
    else if (typeof raw === 'string') {
        try { blocks = JSON.parse(raw) } catch { return [] }
    }
    return blocks.filter(b => b.enabled)
})

const heroBackground = computed(() => {
    const url =
        tenant.value?.hero_image_url ||
        "https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?q=80&w=1920&auto=format&fit=crop";
    return `url('${url}')`;
});

const showProductModal = ref(false);
const selectedProduct = ref(null);
const selectedCategory = ref(null);

// Gallery lightbox
const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

const galleryImages = computed(() => {
    const imgs = tenant.value?.gallery_images;
    if (!imgs) return [];
    if (typeof imgs === "string") {
        try {
            return JSON.parse(imgs);
        } catch {
            return [];
        }
    }
    return Array.isArray(imgs) ? imgs.filter(Boolean) : [];
});

const openLightbox = (index) => {
    lightboxIndex.value = index;
    lightboxOpen.value = true;
};

const searchQuery = ref('')

const filteredCategories = computed(() => {
    const q = searchQuery.value.trim().toLowerCase()
    return props.categories
        .map((category) => ({
            ...category,
            products: category.products.filter((product) => {
                if (!q) return true
                return (
                    product.name.toLowerCase().includes(q) ||
                    (product.description && product.description.toLowerCase().includes(q))
                )
            }),
        }))
        .filter((category) => !q || category.products.length > 0)
});

const hasAnyImage = (products) => {
    return products.some((p) => p.image);
};

const starFill = (rating, n) => {
    if (n <= Math.floor(rating)) return 100
    if (n === Math.ceil(rating)) return Math.round((rating % 1) * 100)
    return 0
}

const openProductModal = (product, category = null) => {
    selectedProduct.value = product;
    selectedCategory.value = category ?? product.category ?? null;
    showProductModal.value = true;
};

// JSON-LD structured data
let jsonLdScript = null;

onMounted(() => {
    if (props.structuredData) {
        jsonLdScript = document.createElement("script");
        jsonLdScript.type = "application/ld+json";
        jsonLdScript.textContent = JSON.stringify(props.structuredData);
        document.head.appendChild(jsonLdScript);
    }

});

onUnmounted(() => {
    if (jsonLdScript) {
        jsonLdScript.remove();
    }
});
</script>
