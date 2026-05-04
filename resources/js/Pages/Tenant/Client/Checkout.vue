<template>
    <ClientLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Finalizacja zamówienia</h1>

            <!-- Removed items notice -->
            <div v-if="removedItemsNotice.length > 0" class="mb-6 p-4 bg-amber-50 border border-amber-300 rounded-lg text-amber-800">
                <p class="font-semibold mb-1"><i class="fa-solid fa-triangle-exclamation mr-2"></i>Część produktów została usunięta z koszyka, ponieważ nie są już dostępne:</p>
                <ul class="list-disc list-inside text-sm mt-1">
                    <li v-for="name in removedItemsNotice" :key="name">{{ name }}</li>
                </ul>
            </div>

            <div v-if="cartStore.items.length === 0 && !selectedFreeProductReward" class="text-center py-12 bg-white rounded-lg shadow">
                <div class="text-6xl mb-4"><i class="fa-solid fa-cart-shopping text-blue-400"></i></div>
                <h2 class="text-2xl font-semibold text-gray-900 mb-2">Koszyk jest pusty</h2>
                <p class="text-gray-600 mb-6">Dodaj produkty do koszyka, aby złożyć zamówienie</p>
                <a
                    :href="route('tenant.menu')"
                    class="inline-block bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold"
                >
                    Wróć do menu
                </a>
            </div>

            <form v-else-if="cartStore.items.length > 0 || selectedFreeProductReward" @submit.prevent="submitOrder" class="space-y-6">
                <!-- Order Type -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Metoda odbioru</h2>
                    <div class="grid grid-cols-3 gap-4">
                        <button
                            v-if="tenant?.delivery_enabled !== false"
                            type="button"
                            @click="changeOrderType('delivery')"
                            :disabled="!!qrTableId"
                            class="border-2 rounded-lg p-4 text-center transition-all"
                            :class="{
                                'border-red-600 bg-red-50': form.type === 'delivery',
                                'border-gray-300 hover:border-red-300': form.type !== 'delivery' && !qrTableId,
                                'opacity-40 cursor-not-allowed': !!qrTableId,
                            }"
                        >
                            <div class="text-3xl mb-2"><i class="fa-solid fa-truck text-blue-500"></i></div>
                            <div class="font-semibold">Dostawa</div>
                        </button>
                        <button
                            v-if="tenant?.pickup_enabled !== false"
                            type="button"
                            @click="changeOrderType('pickup')"
                            :disabled="!!qrTableId"
                            class="border-2 rounded-lg p-4 text-center transition-all"
                            :class="{
                                'border-red-600 bg-red-50': form.type === 'pickup',
                                'border-gray-300 hover:border-red-300': form.type !== 'pickup' && !qrTableId,
                                'opacity-40 cursor-not-allowed': !!qrTableId,
                            }"
                        >
                            <div class="text-3xl mb-2"><i class="fa-solid fa-store text-orange-500"></i></div>
                            <div class="font-semibold">Odbiór osobisty</div>
                        </button>
                        <button
                            v-if="tenant?.dine_in_enabled || qrTableId"
                            type="button"
                            @click="!qrTableId && changeOrderType('dine_in')"
                            class="border-2 rounded-lg p-4 text-center transition-all relative"
                            :class="{
                                'border-red-600 bg-red-50': form.type === 'dine_in',
                                'border-gray-300 hover:border-red-300': form.type !== 'dine_in' && !qrTableId,
                                'cursor-default': !!qrTableId,
                            }"
                        >
                            <div class="text-3xl mb-1"><i class="fa-solid fa-utensils text-orange-400"></i></div>
                            <div class="font-semibold">Na miejscu</div>
                            <div v-if="qrTableId" class="text-xs text-red-600 font-medium mt-0.5">Stolik nr {{ qrTableId }}</div>
                            <button
                                v-if="qrTableId"
                                type="button"
                                @click.stop="clearQrTable"
                                class="absolute top-1 right-1.5 text-gray-400 hover:text-gray-600 text-xs leading-none"
                                title="Anuluj"
                            ><i class="fa-solid fa-xmark"></i></button>
                        </button>
                    </div>
                </div>

                <!-- Customer Details -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Dane kontaktowe</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Imię i nazwisko *
                            </label>
                            <input
                                v-model="form.customer_name"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Telefon *
                            </label>
                            <input
                                v-model="form.customer_phone"
                                type="tel"
                                required
                                placeholder="123 456 789"
                                @blur="form.customer_phone = formatPhone(form.customer_phone)"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email
                            </label>
                            <input
                                v-model="form.customer_email"
                                type="email"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            />
                        </div>
                    </div>
                </div>

                <!-- Delivery Address (only for delivery) -->
                <div v-if="form.type === 'delivery'" class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Adres dostawy</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Adres (ulica, numer, miasto) *
                            </label>
                            <input
                                ref="addressInput"
                                v-model="form.delivery_address"
                                type="text"
                                required
                                placeholder="ul. Kwiatowa 15/3, Kraków"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                :class="{
                                    'border-gray-300': !addressValidation.error,
                                    'border-red-500': addressValidation.error,
                                    'border-green-500': addressValidation.valid,
                                }"
                                @blur="validateDeliveryAddress"
                            />
                            <p v-if="addressValidation.validating" class="text-sm text-red-600 mt-1">
                                Sprawdzanie adresu...
                            </p>
                            <div v-if="addressValidation.error" class="mt-1">
                                <p class="text-sm text-red-600">{{ addressValidation.error }}</p>
                                <button
                                    v-if="props.deliveryZones?.length"
                                    type="button"
                                    @click="showZonesModal = true"
                                    class="mt-1 text-sm text-blue-600 hover:text-blue-800 underline"
                                >
                                    Zobacz strefy dostawy na mapie
                                </button>
                            </div>
                            <div v-if="addressValidation.valid && deliveryZoneInfo" class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                                <p class="text-sm text-green-800 font-medium"><i class="fa-solid fa-check mr-1"></i> Adres w strefie dostawy: {{ deliveryZoneInfo.name }}</p>
                                <p class="text-xs text-green-700 mt-1">{{ deliveryZoneInfo.delivery_info }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Metoda płatności</h2>
                    <div class="space-y-3">
                        <label
                            v-for="method in props.paymentMethods"
                            :key="method.value"
                            :class="[
                                'flex items-center p-4 border-2 rounded-lg cursor-pointer transition',
                                form.payment_method === method.value
                                    ? 'border-red-500 bg-red-50'
                                    : 'border-gray-200 hover:bg-gray-50'
                            ]"
                        >
                            <input
                                v-model="form.payment_method"
                                type="radio"
                                :value="method.value"
                                class="w-5 h-5 text-red-600"
                            />
                            <div class="ml-3 flex-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="method.type === 'online'"
                                        class="text-xs font-bold px-2 py-0.5 rounded text-white"
                                        :style="{ backgroundColor: method.color || '#333' }"
                                    >{{ method.icon }}</span>
                                    <i v-else :class="`fa-solid ${method.icon} text-gray-600`"></i>
                                    <span class="font-medium text-gray-900">{{ method.label }}</span>
                                    <span v-if="method.type === 'online'" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Online</span>
                                </div>
                                <p v-if="method.description" class="text-xs text-gray-500 mt-0.5 ml-1">{{ method.description }}</p>
                            </div>
                        </label>

                        <p v-if="!props.paymentMethods?.length" class="text-sm text-red-600">
                            Brak skonfigurowanych metod płatności. Skontaktuj się z restauracją.
                        </p>
                    </div>
                </div>

                <!-- Order Notes -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Uwagi do zamówienia</h2>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        placeholder="Dodatkowe informacje dla kuriera lub restauracji..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    ></textarea>
                </div>

                <!-- Order Summary -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Podsumowanie zamówienia</h2>

                    <!-- Cart Items -->
                    <div class="mb-4 pb-4 border-b space-y-3">
                        <div
                            v-for="item in cartStore.items"
                            :key="item.id"
                            class="flex gap-3"
                        >
                            <img
                                v-if="item.product.image"
                                :src="item.product.image.startsWith('/') ? item.product.image : '/storage/' + item.product.image"
                                :alt="item.product.name"
                                class="w-14 h-14 object-cover rounded-lg shrink-0"
                            />
                            <div v-else class="w-14 h-14 bg-gray-100 rounded-lg shrink-0 flex items-center justify-center text-2xl"><i class="fa-solid fa-pizza-slice text-orange-400"></i></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900 truncate">{{ item.product.name }}</p>
                                        <p class="text-sm text-gray-500">{{ item.variant.name }}</p>
                                        <p v-if="item.selectedAddons?.length" class="text-xs text-gray-500">
                                            + {{ item.selectedAddons.map(a => a.name).join(', ') }}
                                        </p>
                                        <p v-if="item.exclusions?.length" class="text-xs text-red-500">
                                            Bez: {{ item.exclusions.join(', ') }}
                                        </p>
                                        <p v-if="item.notes" class="text-xs text-gray-400 italic">{{ item.notes }}</p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1 shrink-0">
                                        <p class="text-sm font-semibold text-gray-900">
                                            {{ formatPrice(itemLinePrice(item)) }}
                                        </p>
                                        <button
                                            type="button"
                                            @click="cartStore.removeItem(item.id)"
                                            class="text-xs text-red-400 hover:text-red-600 leading-none"
                                            title="Usuń"
                                        >Usuń</button>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <button
                                        type="button"
                                        @click="cartStore.updateQuantity(item.id, item.quantity - 1)"
                                        class="w-6 h-6 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-100 flex items-center justify-center text-sm leading-none"
                                    ><i class="fa-solid fa-minus"></i></button>
                                    <span class="text-sm font-medium w-4 text-center">{{ item.quantity }}</span>
                                    <button
                                        type="button"
                                        @click="cartStore.updateQuantity(item.id, item.quantity + 1)"
                                        class="w-6 h-6 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-100 flex items-center justify-center text-sm leading-none"
                                    >+</button>
                                </div>
                            </div>
                        </div>

                        <!-- Free product reward row -->
                        <div v-if="selectedFreeProductReward" class="flex gap-3 pt-3 border-t border-dashed border-blue-200">
                            <img
                                v-if="selectedFreeProductReward.product_image"
                                :src="selectedFreeProductReward.product_image.startsWith('/') ? selectedFreeProductReward.product_image : '/storage/' + selectedFreeProductReward.product_image"
                                :alt="selectedFreeProductReward.product_name"
                                class="w-14 h-14 object-cover rounded-lg shrink-0"
                            />
                            <div v-else class="w-14 h-14 bg-blue-50 rounded-lg shrink-0 flex items-center justify-center text-2xl"><i class="fa-solid fa-gift text-blue-500"></i></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900 truncate">{{ selectedFreeProductReward.product_name }}</p>
                                        <p v-if="selectedFreeProductReward.variant_name" class="text-sm text-gray-500">{{ selectedFreeProductReward.variant_name }}</p>
                                        <p class="text-xs text-blue-600 font-medium">Nagroda lojalnościowa</p>
                                    </div>
                                    <p class="text-sm font-semibold text-blue-600 shrink-0">0,00 zł</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Discount Code Input -->
                    <div class="mb-4 pb-4 border-b">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Kod rabatowy
                        </label>
                        <div class="flex gap-2">
                            <input
                                v-model="discountCodeInput"
                                type="text"
                                placeholder="Wpisz kod"
                                :disabled="discountApplied"
                                class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent uppercase"
                                :class="{
                                    'border-gray-300': !discountValidation.error && !discountApplied,
                                    'border-red-500': discountValidation.error,
                                    'border-green-500 bg-green-50': discountApplied,
                                }"
                                @keyup.enter="validateDiscountCode"
                            />
                            <button
                                v-if="!discountApplied"
                                type="button"
                                @click="validateDiscountCode"
                                :disabled="!discountCodeInput || discountValidation.validating"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
                            >
                                {{ discountValidation.validating ? 'Sprawdzam...' : 'Zastosuj' }}
                            </button>
                            <button
                                v-else
                                type="button"
                                @click="removeDiscountCode"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                            >
                                Usuń
                            </button>
                        </div>
                        <p v-if="discountValidation.error" class="text-sm text-red-600 mt-1">
                            {{ discountValidation.error }}
                        </p>
                        <p v-if="discountApplied" class="text-sm text-green-600 mt-1">
                            <i class="fa-solid fa-check mr-1 text-green-600"></i> {{ discountValidation.message }}
                        </p>
                    </div>

                    <!-- Loyalty Section -->
                    <div v-if="canUseLoyalty" class="mb-4 pb-4 border-b">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Program lojalnościowy</label>
                            <span class="text-xs text-blue-600 font-semibold">{{ loyaltyOptions.points_balance }} pkt</span>
                        </div>

                        <!-- Reward selection -->
                        <div v-if="loyaltyOptions.available_rewards?.length && loyaltyOptions.redeem_mode !== 'points_to_pln'" class="space-y-2 mb-3">
                            <div
                                v-for="reward in loyaltyOptions.available_rewards"
                                :key="reward.id"
                                class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors"
                                :class="form.loyalty_reward_id === reward.id ? 'border-blue-500 bg-blue-50' : reward.affordable ? 'border-gray-200 hover:border-blue-300' : 'border-gray-200 opacity-50 cursor-not-allowed'"
                                @click="reward.affordable && toggleReward(reward.id)"
                            >
                                <input type="radio" :value="reward.id" v-model="form.loyalty_reward_id" :disabled="!reward.affordable" class="text-blue-600" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800">{{ reward.name }}</p>
                                    <p class="text-xs text-gray-500">
                                        <span v-if="reward.type === 'fixed_discount'" class="text-green-600">-{{ reward.value }} PLN</span>
                                        <span v-else-if="reward.type === 'percent_discount'" class="text-green-600">-{{ reward.value }}%</span>
                                        <span v-else-if="reward.type === 'free_delivery'" class="text-green-600">Darmowa dostawa</span>
                                        <span v-else class="text-purple-600">{{ reward.product_name }}{{ reward.variant_name ? ` (${reward.variant_name})` : '' }}</span>
                                    </p>
                                </div>
                                <span class="text-xs font-bold text-blue-600 shrink-0">{{ reward.cost_points }} pkt</span>
                            </div>
                        </div>

                        <!-- Points to PLN -->
                        <div v-if="loyaltyOptions.redeem_mode === 'points_to_pln' || loyaltyOptions.redeem_mode === 'both'">
                            <div class="flex items-center gap-2">
                                <input
                                    v-model.number="form.loyalty_points_redeem"
                                    type="number"
                                    min="0"
                                    :max="loyaltyOptions.points_balance"
                                    :step="loyaltyOptions.points_per_pln || 1"
                                    placeholder="Ile punktów wymienić?"
                                    :disabled="!!form.loyalty_reward_id"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 disabled:opacity-40"
                                    @input="form.loyalty_reward_id = null"
                                />
                                <span class="text-xs text-gray-500 shrink-0">= {{ loyaltyPoints2Pln }} PLN</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">{{ loyaltyOptions.points_per_pln || 1 }} pkt = 1 PLN</p>
                        </div>
                    </div>

                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-gray-600">
                            <span>Suma produktów:</span>
                            <span>{{ formatPrice(cartStore.subtotal) }}</span>
                        </div>
                        <div v-if="form.type === 'delivery'" class="flex justify-between text-gray-600">
                            <span>Dostawa:</span>
                            <span>{{ formatPrice(deliveryFee) }}</span>
                        </div>
                        <div v-if="discountAmount > 0" class="flex justify-between text-green-600 font-semibold">
                            <span>Rabat:</span>
                            <span>-{{ formatPrice(discountAmount) }}</span>
                        </div>
                        <div v-if="loyaltyDiscount > 0" class="flex justify-between text-blue-600 font-semibold">
                            <span>Punkty lojalnościowe:</span>
                            <span>-{{ formatPrice(loyaltyDiscount) }}</span>
                        </div>
                        <div class="border-t pt-2 flex justify-between text-xl font-bold">
                            <span>Razem:</span>
                            <span class="text-red-600">{{ formatPrice(totalAmount) }}</span>
                        </div>
                    </div>

                    <!-- Terms acceptance -->
                    <div class="flex items-start gap-3 text-sm text-gray-600">
                        <input
                            id="terms_accepted"
                            v-model="form.terms_accepted"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 shrink-0"
                        />
                        <label for="terms_accepted">
                            Akceptuję
                            <a :href="route('tenant.terms')" target="_blank" class="text-red-600 hover:underline">Regulamin</a>
                            i
                            <a :href="route('tenant.privacy')" target="_blank" class="text-red-600 hover:underline">Politykę prywatności</a>
                            restauracji.
                        </label>
                    </div>
                    <p v-if="termsError" class="text-red-600 text-sm">{{ termsError }}</p>

                    <!-- Submit error -->
                    <div v-if="submitError" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                        {{ submitError }}
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="processing"
                        class="mt-4 w-full bg-red-600 hover:bg-red-700 text-white py-4 rounded-lg text-lg font-semibold transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                    >
                        <span v-if="processing">Przetwarzanie...</span>
                        <span v-else-if="form.type === 'delivery' && addressValidation.validating">Sprawdzanie adresu...</span>
                        <span v-else>Złóż zamówienie</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Delivery Zones Modal -->
        <div
            v-if="showZonesModal"
            class="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4"
            @click.self="showZonesModal = false"
        >
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-semibold">Strefy dostawy</h3>
                    <button type="button" @click="showZonesModal = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                </div>
                <div class="overflow-y-auto p-4 flex-1">
                    <div v-if="props.googleMapsConfigured" ref="zonesMapEl" class="w-full h-80 rounded-lg bg-gray-100 mb-4"></div>
                    <div class="space-y-3">
                        <div
                            v-for="zone in props.deliveryZones"
                            :key="zone.id"
                            class="flex items-start gap-3 p-3 rounded-lg border"
                        >
                            <div
                                class="w-4 h-4 rounded-full mt-0.5 shrink-0"
                                :style="{ backgroundColor: zone.color || '#3B82F6' }"
                            ></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900">{{ zone.name }}</p>
                                <p class="text-sm text-gray-600">
                                    Dostawa: {{ zone.delivery_fee > 0 ? formatPrice(zone.delivery_fee) : 'Gratis' }}
                                    <span v-if="zone.min_order_value"> · Min. zamówienie: {{ formatPrice(zone.min_order_value) }}</span>
                                    <span v-if="zone.estimated_time"> · ~{{ zone.estimated_time }} min</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { router, Head, usePage } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { useCartStore } from '@/Stores/cartStore'
import { formatPhone } from '@/utils/phone'
import axios from 'axios'

const props = defineProps({
    deliveryZones: {
        type: Array,
        default: () => [],
    },
    googleMapsConfigured: {
        type: Boolean,
        default: false,
    },
    paymentMethods: {
        type: Array,
        default: () => [
            { value: 'cash', label: 'Gotówka przy odbiorze', icon: 'fa-money-bill-wave', type: 'offline' },
        ],
    },
    loyaltyOptions: {
        type: Object,
        default: null,
    },
})

const page = usePage()
const customer = computed(() => page.props.auth?.customer)
const tenant = computed(() => page.props.tenant)

const cartStore = useCartStore()
const processing = ref(false)
const submitError = ref('')
const removedItemsNotice = ref([])
const addressInput = ref(null)
const autocomplete = ref(null)

const googleMapsApiKey = ref(import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '')

// Table QR code session
const qrTableId = ref(localStorage.getItem('qr_table_id') || null)

const clearQrTable = () => {
    localStorage.removeItem('qr_table_id')
    qrTableId.value = null
    form.value.type = getDefaultOrderType()
}

// Determine default order type — use first enabled type (or dine_in if from QR)
const getDefaultOrderType = () => {
    if (qrTableId.value) return 'dine_in'
    if (tenant.value?.delivery_enabled !== false) return 'delivery'
    if (tenant.value?.pickup_enabled !== false) return 'pickup'
    if (tenant.value?.dine_in_enabled !== false) return 'dine_in'
    return 'delivery'
}

const form = ref({
    type: getDefaultOrderType(),
    customer_name: customer.value?.name || '',
    customer_email: customer.value?.email || '',
    customer_phone: customer.value?.phone || '',
    delivery_address: customer.value?.delivery_address
        ? `${customer.value.delivery_address}${customer.value.delivery_city ? ', ' + customer.value.delivery_city : ''}`
        : '',
    payment_method: props.paymentMethods?.[0]?.value || 'cash',
    notes: '',
    discount_code: '',
    loyalty_reward_id: null,
    loyalty_points_redeem: null,
    terms_accepted: false,
})

// Restore non-sensitive fields from localStorage (persists on refresh, cleared on success)
const CHECKOUT_STORAGE_KEY = 'checkout_form'
const CHECKOUT_PERSIST_FIELDS = ['type', 'customer_name', 'customer_email', 'customer_phone', 'delivery_address', 'payment_method', 'notes']

try {
    const saved = localStorage.getItem(CHECKOUT_STORAGE_KEY)
    if (saved) {
        const parsed = JSON.parse(saved)
        // Only restore fields that aren't already filled by logged-in customer data
        for (const field of CHECKOUT_PERSIST_FIELDS) {
            if (parsed[field] !== undefined) {
                // Don't overwrite fields already populated from customer profile
                if (field === 'customer_name' && form.value.customer_name) continue
                if (field === 'customer_email' && form.value.customer_email) continue
                if (field === 'customer_phone' && form.value.customer_phone) continue
                if (field === 'delivery_address' && form.value.delivery_address) continue
                form.value[field] = parsed[field]
            }
        }
    }
} catch (_) {}

watch(form, (val) => {
    try {
        const toSave = {}
        for (const field of CHECKOUT_PERSIST_FIELDS) {
            toSave[field] = val[field]
        }
        localStorage.setItem(CHECKOUT_STORAGE_KEY, JSON.stringify(toSave))
    } catch (_) {}
}, { deep: true })

const termsError = ref('')

const addressValidation = ref({
    valid: false,
    validating: false,
    error: null,
})

const deliveryZoneInfo = ref(null)
const showZonesModal = ref(false)
const zonesMapEl = ref(null)

const discountCodeInput = ref('')
const discountValidation = ref({
    valid: false,
    validating: false,
    error: null,
    message: null,
})
const discountApplied = ref(false)
const discountAmount = ref(0)

const deliveryFee = computed(() => {
    if (form.value.type !== 'delivery') return 0
    return deliveryZoneInfo.value ? parseFloat(deliveryZoneInfo.value.delivery_fee) || 0 : 0
})

const loyaltyDiscount = computed(() => {
    if (form.value.loyalty_reward_id) {
        const r = props.loyaltyOptions?.available_rewards?.find(x => x.id === form.value.loyalty_reward_id)
        if (!r) return 0
        if (r.type === 'fixed_discount') return Math.min(parseFloat(r.value), cartStore.subtotal)
        if (r.type === 'percent_discount') return Math.round(cartStore.subtotal * r.value / 100 * 100) / 100
        if (r.type === 'free_delivery') return deliveryFee.value
        return 0
    }
    if (form.value.loyalty_points_redeem) {
        const ptsPerPln = props.loyaltyOptions?.points_per_pln || 1
        return Math.min(Math.floor(form.value.loyalty_points_redeem / ptsPerPln), cartStore.subtotal)
    }
    return 0
})

const totalAmount = computed(() => {
    return Math.max(0, cartStore.subtotal + deliveryFee.value - discountAmount.value - loyaltyDiscount.value)
})

const loyaltyPoints2Pln = computed(() => {
    const pts = form.value.loyalty_points_redeem || 0
    const ptsPerPln = props.loyaltyOptions?.points_per_pln || 1
    return Math.floor(pts / ptsPerPln)
})

const toggleReward = (id) => {
    form.value.loyalty_reward_id = form.value.loyalty_reward_id === id ? null : id
    form.value.loyalty_points_redeem = null
}

const canUseLoyalty = computed(() => {
    if (!props.loyaltyOptions) return false
    const { redeem_mode, available_rewards, points_balance, points_per_pln } = props.loyaltyOptions
    const hasAffordableReward = available_rewards?.some(r => r.affordable)
    const canRedeemPoints = (redeem_mode === 'points_to_pln' || redeem_mode === 'both')
        && points_balance >= (points_per_pln || 1)
    return hasAffordableReward || canRedeemPoints
})

const selectedFreeProductReward = computed(() => {
    if (!form.value.loyalty_reward_id) return null
    const r = props.loyaltyOptions?.available_rewards?.find(x => x.id === form.value.loyalty_reward_id)
    return r?.type === 'free_product' ? r : null
})

const validateDiscountCode = async () => {
    if (!discountCodeInput.value.trim()) {
        return
    }

    discountValidation.value = {
        valid: false,
        validating: true,
        error: null,
        message: null,
    }

    try {
        const response = await axios.post(route('tenant.checkout.validate-discount'), {
            code: discountCodeInput.value.trim(),
            subtotal: cartStore.subtotal,
        })

        if (response.data.valid) {
            discountValidation.value = {
                valid: true,
                validating: false,
                error: null,
                message: response.data.message,
            }
            discountApplied.value = true
            discountAmount.value = response.data.discount.amount
            form.value.discount_code = response.data.discount.code
        }
    } catch (error) {
        discountValidation.value = {
            valid: false,
            validating: false,
            error: error.response?.data?.message || 'Nieprawidłowy kod rabatowy',
            message: null,
        }
        discountApplied.value = false
        discountAmount.value = 0
        form.value.discount_code = ''
    }
}

const removeDiscountCode = () => {
    discountCodeInput.value = ''
    discountValidation.value = {
        valid: false,
        validating: false,
        error: null,
        message: null,
    }
    discountApplied.value = false
    discountAmount.value = 0
    form.value.discount_code = ''
}

const itemLinePrice = (item) => {
    let price = item.variant.price
    if (item.selectedAddons?.length) {
        price += item.selectedAddons.reduce((sum, a) => sum + parseFloat(a.price), 0)
    }
    return price * item.quantity
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN',
    }).format(price)
}

const changeOrderType = (type) => {
    form.value.type = type

    // Reset delivery-related fields when changing away from delivery
    if (type !== 'delivery') {
        addressValidation.value = { valid: false, validating: false, error: null }
        deliveryZoneInfo.value = null
    }
}

const loadGoogleMapsScript = () => {
    return new Promise((resolve, reject) => {
        // Check if already loaded
        if (window.google && window.google.maps) {
            resolve();
            return;
        }

        // Check if script is already being loaded
        if (document.querySelector('script[src*="maps.googleapis.com"]')) {
            // Wait for it to load
            const checkInterval = setInterval(() => {
                if (window.google && window.google.maps) {
                    clearInterval(checkInterval);
                    resolve();
                }
            }, 100);
            setTimeout(() => {
                clearInterval(checkInterval);
                reject(new Error('Google Maps script loading timeout'));
            }, 10000);
            return;
        }

        // Load the script
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${googleMapsApiKey.value}&libraries=places&language=pl`;
        script.async = true;
        script.defer = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load Google Maps script'));
        document.head.appendChild(script);
    });
};

const initGoogleAutocomplete = async () => {
    if (!props.googleMapsConfigured || !addressInput.value) return

    try {
        await loadGoogleMapsScript();
    } catch (error) {
        console.error('Google Maps loading error:', error);
        return;
    }

    // Google Maps API is loaded
    const checkGoogleMaps = setInterval(() => {
        if (window.google && window.google.maps && window.google.maps.places) {
            clearInterval(checkGoogleMaps)

            autocomplete.value = new window.google.maps.places.Autocomplete(addressInput.value, {
                componentRestrictions: { country: 'pl' },
                fields: ['formatted_address', 'geometry'],
                types: ['address'],
            })

            autocomplete.value.addListener('place_changed', () => {
                const place = autocomplete.value.getPlace()

                if (place.formatted_address) {
                    form.value.delivery_address = place.formatted_address
                    validateDeliveryAddress()
                }
            })
        }
    }, 100)

    // Stop checking after 10 seconds
    setTimeout(() => clearInterval(checkGoogleMaps), 10000)
}

const validateDeliveryAddress = async () => {
    if (!form.value.delivery_address || form.value.type !== 'delivery') {
        return
    }

    addressValidation.value = {
        valid: false,
        validating: true,
        error: null,
    }

    try {
        const response = await axios.post(route('tenant.checkout.validate-address'), {
            address: form.value.delivery_address,
            subtotal: cartStore.subtotal,
        })

        if (response.data.valid) {
            addressValidation.value = {
                valid: true,
                validating: false,
                error: null,
            }
            deliveryZoneInfo.value = response.data.zone
        } else {
            addressValidation.value = {
                valid: false,
                validating: false,
                error: response.data.error || 'Adres poza strefą dostawy',
            }
            deliveryZoneInfo.value = null
        }
    } catch (error) {
        addressValidation.value = {
            valid: false,
            validating: false,
            error: error.response?.data?.error || 'Nie można zweryfikować adresu',
        }
        deliveryZoneInfo.value = null
    }
}

// Watch delivery address changes
watch(() => form.value.delivery_address, (newValue, oldValue) => {
    if (newValue !== oldValue) {
        // Reset validation when address changes
        addressValidation.value = { valid: false, validating: false, error: null }
        deliveryZoneInfo.value = null
    }
})

const submitOrder = async () => {
    if (processing.value) return

    submitError.value = ''

    // Validate terms acceptance
    if (!form.value.terms_accepted) {
        termsError.value = 'Musisz zaakceptować Regulamin i Politykę prywatności.'
        return
    }
    termsError.value = ''

    // For delivery: always re-validate address before submitting (catches stale valid=true)
    if (form.value.type === 'delivery') {
        await validateDeliveryAddress()
        if (!addressValidation.value.valid) {
            return
        }
    }

    processing.value = true

    try {
        const response = await axios.post(route('tenant.checkout.store'), {
            ...form.value,
            items: cartStore.getCheckoutData(),
            subtotal: cartStore.subtotal,
            table_id: qrTableId.value ? parseInt(qrTableId.value) : null,
        })

        if (response.data.success) {
            cartStore.clearCart()
            localStorage.removeItem('qr_table_id')
            localStorage.removeItem(CHECKOUT_STORAGE_KEY)

            if (response.data.redirect_to_payment) {
                window.location.href = response.data.payment_url
            } else {
                const trackingUrl = route('tenant.order.tracking', response.data.order_number)
                    + (response.data.tracking_token ? `?token=${response.data.tracking_token}` : '')
                router.visit(trackingUrl)
            }
        }
    } catch (error) {
        const status = error.response?.status
        const data = error.response?.data
        if (status === 429) {
            submitError.value = 'Zbyt wiele prób składania zamówienia. Odczekaj chwilę i spróbuj ponownie.'
        } else if (data?.delivery_zone_error) {
            addressValidation.value = { valid: false, validating: false, error: data.message }
            deliveryZoneInfo.value = null
        } else if (data?.errors) {
            const firstErrors = Object.values(data.errors).flat()
            submitError.value = firstErrors.join(' ')
        } else {
            submitError.value = data?.message || 'Wystąpił błąd podczas składania zamówienia. Spróbuj ponownie.'
        }
    } finally {
        processing.value = false
    }
}

onMounted(async () => {
    // Validate cart items against current DB state — remove deleted/unavailable products
    const removed = await cartStore.validateCart()
    if (removed.length > 0) {
        removedItemsNotice.value = removed
    }

    if (form.value.type === 'delivery' && props.googleMapsConfigured) {
        initGoogleAutocomplete()
    }

    // Auto-validate pre-filled address (logged-in user with saved address)
    if (form.value.type === 'delivery' && form.value.delivery_address) {
        validateDeliveryAddress()
    }

    // Pre-select reward from URL ?reward_id=X (e.g. redirected from /moje-konto)
    const urlParams = new URLSearchParams(window.location.search)
    const rewardId = parseInt(urlParams.get('reward_id'))
    if (rewardId && props.loyaltyOptions?.available_rewards) {
        const reward = props.loyaltyOptions.available_rewards.find(r => r.id === rewardId && r.affordable)
        if (reward) {
            form.value.loyalty_reward_id = reward.id
        }
    }
})

// Re-validate address when cart subtotal changes (e.g. min order value check)
watch(() => cartStore.subtotal, () => {
    if (form.value.type === 'delivery' && form.value.delivery_address && addressValidation.value.valid !== null) {
        validateDeliveryAddress()
    }
})

// Reinit Google Autocomplete when user switches to delivery type
watch(() => form.value.type, (newType) => {
    if (newType === 'delivery' && props.googleMapsConfigured) {
        // Wait for addressInput to be mounted in DOM
        setTimeout(() => initGoogleAutocomplete(), 50)
    }
})

// Init delivery zones map when modal opens
watch(showZonesModal, async (open) => {
    if (!open || !props.googleMapsConfigured || !props.deliveryZones?.length) return

    await nextTick()

    // Ensure Google Maps is loaded
    try {
        await loadGoogleMapsScript()
    } catch (e) {
        return
    }

    if (!zonesMapEl.value) return

    const map = new window.google.maps.Map(zonesMapEl.value, {
        zoom: 12,
        center: { lat: 52.2297, lng: 21.0122 },
        disableDefaultUI: true,
        zoomControl: true,
    })

    const bounds = new window.google.maps.LatLngBounds()

    props.deliveryZones.forEach((zone) => {
        if (!zone.polygon?.length) return

        const paths = zone.polygon.map(([lat, lng]) => ({ lat, lng }))

        const poly = new window.google.maps.Polygon({
            paths,
            strokeColor: zone.color || '#3B82F6',
            strokeOpacity: 0.9,
            strokeWeight: 2,
            fillColor: zone.color || '#3B82F6',
            fillOpacity: 0.25,
            map,
        })

        paths.forEach((pt) => bounds.extend(pt))

        // Info window on click
        const infoWindow = new window.google.maps.InfoWindow()
        poly.addListener('click', (e) => {
            const fee = zone.delivery_fee > 0 ? `${zone.delivery_fee} zł` : 'Gratis'
            const min = zone.min_order_value ? `<br>Min. zamówienie: ${zone.min_order_value} zł` : ''
            const time = zone.estimated_time ? `<br>Czas dostawy: ~${zone.estimated_time} min` : ''
            infoWindow.setContent(`<strong>${zone.name}</strong><br>Dostawa: ${fee}${min}${time}`)
            infoWindow.setPosition(e.latLng)
            infoWindow.open(map)
        })
    })

    if (!bounds.isEmpty()) {
        map.fitBounds(bounds)
    }
})
</script>
