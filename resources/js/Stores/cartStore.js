import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useCartStore = defineStore('cart', () => {
    // State
    const items = ref([])
    const isCartOpen = ref(false)

    // Getters
    const itemCount = computed(() => {
        return items.value.reduce((sum, item) => sum + item.quantity, 0)
    })

    const subtotal = computed(() => {
        return items.value.reduce((sum, item) => {
            let itemTotal = item.variant.price * item.quantity

            // Add addons price
            if (item.selectedAddons && item.selectedAddons.length > 0) {
                const addonsTotal = item.selectedAddons.reduce(
                    (addonSum, addon) => addonSum + parseFloat(addon.price),
                    0
                )
                itemTotal += addonsTotal * item.quantity
            }

            return sum + itemTotal
        }, 0)
    })

    const total = computed(() => {
        // For now, just return subtotal. Delivery fee will be added in checkout
        return subtotal.value
    })

    // Actions
    function addItem(product, variant, selectedAddons = [], exclusions = [], notes = '', quantity = 1) {
        // Check if identical item already exists
        const existingItemIndex = items.value.findIndex(item =>
            item.product.id === product.id &&
            item.variant.id === variant.id &&
            JSON.stringify(item.selectedAddons) === JSON.stringify(selectedAddons) &&
            JSON.stringify(item.exclusions) === JSON.stringify(exclusions) &&
            item.notes === notes
        )

        if (existingItemIndex !== -1) {
            // Increase quantity of existing item
            items.value[existingItemIndex].quantity += quantity
        } else {
            // Add new item
            items.value.push({
                id: Date.now(), // Temporary unique ID
                product: {
                    id: product.id,
                    name: product.name,
                    image: product.image,
                    slug: product.slug,
                },
                variant: {
                    id: variant.id,
                    name: variant.name,
                    price: parseFloat(variant.price),
                },
                selectedAddons: selectedAddons.map(addon => ({
                    id: addon.id,
                    name: addon.name,
                    price: parseFloat(addon.price),
                })),
                exclusions: exclusions || [],
                notes: notes || '',
                quantity: quantity,
            })
        }

        saveToLocalStorage()
    }

    function removeItem(itemId) {
        items.value = items.value.filter(item => item.id !== itemId)
        saveToLocalStorage()
    }

    function updateQuantity(itemId, quantity) {
        const item = items.value.find(item => item.id === itemId)
        if (item) {
            if (quantity <= 0) {
                removeItem(itemId)
            } else {
                item.quantity = quantity
                saveToLocalStorage()
            }
        }
    }

    function clearCart() {
        items.value = []
        saveToLocalStorage()
    }

    function toggleCart() {
        isCartOpen.value = !isCartOpen.value
    }

    function openCart() {
        isCartOpen.value = true
    }

    function closeCart() {
        isCartOpen.value = false
    }

    function saveToLocalStorage() {
        localStorage.setItem('cart', JSON.stringify(items.value))
    }

    function loadFromLocalStorage() {
        const saved = localStorage.getItem('cart')
        if (saved) {
            try {
                items.value = JSON.parse(saved)
            } catch (e) {
                console.error('Failed to load cart from localStorage:', e)
                items.value = []
            }
        }
    }

    function getCheckoutData() {
        return items.value.map(item => ({
            product_id: item.product.id,
            variant_id: item.variant.id,
            quantity: item.quantity,
            addons: item.selectedAddons.map(addon => addon.id),
            exclusions: item.exclusions,
            notes: item.notes,
        }))
    }

    /**
     * Validates cart items against current DB state.
     * Removes items that are deleted or unavailable.
     * Returns names of removed products (for user notification).
     */
    async function validateCart() {
        if (items.value.length === 0) return []

        try {
            const cartItems = items.value.map(item => ({
                product_id: item.product.id,
                variant_id: item.variant.id,
            }))

            const response = await axios.post(route('tenant.checkout.validate-cart'), { items: cartItems })
            const { invalid_items } = response.data

            if (!invalid_items || invalid_items.length === 0) return []

            const invalidIds = new Set(
                invalid_items.map(i => `${i.product_id}_${i.variant_id}`)
            )
            const removedNames = []

            items.value = items.value.filter(item => {
                const key = `${item.product.id}_${item.variant.id}`
                if (invalidIds.has(key)) {
                    removedNames.push(item.product.name)
                    return false
                }
                return true
            })

            if (removedNames.length > 0) {
                saveToLocalStorage()
            }

            return removedNames
        } catch (e) {
            // Sieć niedostępna lub błąd serwera — nie blokujemy koszyka
            return []
        }
    }

    // Initialize from localStorage
    loadFromLocalStorage()

    return {
        // State
        items,
        isCartOpen,
        // Getters
        itemCount,
        subtotal,
        total,
        // Actions
        addItem,
        removeItem,
        updateQuantity,
        clearCart,
        toggleCart,
        openCart,
        closeCart,
        getCheckoutData,
        validateCart,
    }
})
