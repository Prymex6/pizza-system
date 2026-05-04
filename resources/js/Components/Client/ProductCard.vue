<template>
    <!-- List row -->
    <div
        @click="$emit('click', product)"
        class="bg-white rounded-2xl border border-gray-100 px-5 py-4 cursor-pointer hover:shadow-md hover:border-red-100 transition group flex items-center gap-4"
        :class="{ 'opacity-50': !product.is_available }"
    >
        <!-- Thumbnail or icon -->
        <div class="shrink-0 w-12 h-12 rounded-xl overflow-hidden bg-red-50 flex items-center justify-center text-2xl">
            <img
                v-if="product.image"
                :src="product.image.startsWith('/') ? product.image : '/storage/' + product.image"
                :alt="product.name"
                class="w-full h-full object-cover"
                loading="lazy"
            />
            <template v-else>
                <span v-if="product.category?.icon || category?.icon">{{ product.category?.icon || category?.icon }}</span>
                <i v-else class="fa-solid fa-pizza-slice text-orange-400"></i>
            </template>
        </div>

        <!-- Info -->
        <div class="flex-1 min-w-0">
            <div class="flex items-baseline gap-2">
                <h4 class="text-base font-bold text-gray-900 truncate">{{ product.name }}</h4>
                <span
                    v-if="product.is_featured"
                    class="shrink-0 text-yellow-500 text-xs font-semibold"
                ><i class="fa-solid fa-star"></i><span class="hidden sm:inline"> Polecane</span></span>
                <span
                    v-if="!product.is_available"
                    class="shrink-0 text-red-500 text-xs font-semibold"
                >Niedostępne</span>
            </div>
            <p v-if="product.description" class="text-gray-400 text-sm truncate mt-0.5">
                {{ product.description }}
            </p>
            <div v-if="productTags.length" class="flex gap-1 mt-1">
                <span
                    v-for="tag in productTags"
                    :key="tag.label"
                    :class="tag.class"
                    class="px-2 py-0.5 rounded-full text-[10px] leading-tight"
                >
                    {{ tag.label }}
                </span>
            </div>
        </div>

        <!-- Price + button -->
        <div class="shrink-0 flex flex-col sm:flex-row items-end sm:items-center gap-1 sm:gap-3">
            <span class="text-red-600 font-bold text-sm sm:text-lg whitespace-nowrap">{{ priceLabel }}</span>
            <button
                v-if="product.is_available"
                class="bg-red-600 hover:bg-red-700 text-white text-xs sm:text-sm px-2.5 py-1.5 sm:px-4 sm:py-2 rounded-full shadow transition font-medium"
                @click.stop="$emit('click', product)"
            >
                <i class="fa-solid fa-plus sm:hidden"></i>
                <span class="hidden sm:inline">Dodaj</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    category: {
        type: Object,
        default: null,
    },
})

defineEmits(['click'])

const minPrice = computed(() => {
    if (!props.product.variants || props.product.variants.length === 0) return 0
    return Math.min(...props.product.variants.map(v => parseFloat(v.price)))
})

const maxPrice = computed(() => {
    if (!props.product.variants || props.product.variants.length === 0) return 0
    return Math.max(...props.product.variants.map(v => parseFloat(v.price)))
})

const priceLabel = computed(() => {
    const fmt = (p) => p.toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' zł'
    if (minPrice.value === maxPrice.value) return fmt(minPrice.value)
    return fmt(minPrice.value) + ' – ' + fmt(maxPrice.value)
})


const TAG_STYLES = {
    wege:      { label: 'wege',      class: 'bg-green-100 text-green-800' },
    vegan:     { label: 'vegan',     class: 'bg-green-100 text-green-800' },
    bezgluten: { label: 'bezgluten', class: 'bg-yellow-100 text-yellow-800' },
    ostra:     { label: 'ostra',     class: 'bg-red-100 text-red-800' },
}

const productTags = computed(() =>
    (props.product.tags || []).map(t => TAG_STYLES[t]).filter(Boolean)
)
</script>
