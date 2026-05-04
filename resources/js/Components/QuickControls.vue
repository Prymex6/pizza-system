<template>
    <!-- Status pill + panel trigger -->
    <div class="relative" ref="container">
        <button
            @click="open = !open"
            class="flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium border transition-colors"
            :class="ordersPaused
                ? 'bg-red-50 border-red-300 text-red-700 hover:bg-red-100'
                : 'bg-green-50 border-green-300 text-green-700 hover:bg-green-100'"
        >
            <span class="w-2 h-2 rounded-full" :class="ordersPaused ? 'bg-red-500' : 'bg-green-500 animate-pulse'"></span>
            <span class="hidden sm:inline">{{ ordersPaused ? 'Zamówienia wstrzymane' : 'Przyjmujemy zamówienia' }}</span>
            <span class="sm:hidden">{{ ordersPaused ? 'Wstrzymane' : 'Otwarte' }}</span>
            <span class="text-xs opacity-60">· {{ prepTime }} min</span>
            <i class="fa-solid fa-chevron-down text-xs ml-0.5" :class="open ? 'rotate-180' : ''" style="transition: transform .15s"></i>
        </button>

        <!-- Dropdown panel -->
        <div
            v-if="open"
            class="absolute right-0 top-full mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-200 z-50 p-4 space-y-4"
        >
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Szybkie sterowanie</p>

            <!-- Orders toggle -->
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-gray-800">Przyjmowanie zamówień</p>
                    <p class="text-xs text-gray-500">Klienci mogą składać zamówienia</p>
                </div>
                <button
                    @click="toggleOrders"
                    :disabled="saving"
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none disabled:opacity-50"
                    :class="!ordersPaused ? 'bg-green-500' : 'bg-gray-300'"
                >
                    <span
                        class="inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200"
                        :class="!ordersPaused ? 'translate-x-5' : 'translate-x-0'"
                    ></span>
                </button>
            </div>

            <hr class="border-gray-100" />

            <!-- Estimated delivery time -->
            <div>
                <label class="text-sm font-medium text-gray-800 block mb-2">
                    Czas przygotowania / dostawy
                </label>
                <div class="flex gap-2 flex-wrap">
                    <button
                        v-for="t in timePresets"
                        :key="t"
                        @click="setTime(t)"
                        :disabled="saving"
                        class="px-3 py-1.5 text-xs rounded-lg border transition-colors disabled:opacity-50"
                        :class="prepTime === t
                            ? 'bg-blue-600 text-white border-blue-600'
                            : 'border-gray-300 text-gray-600 hover:border-blue-400 hover:text-blue-600'"
                    >
                        {{ t }} min
                    </button>
                    <div class="flex items-center gap-1 mt-1 w-full">
                        <input
                            v-model.number="customTime"
                            type="number"
                            min="5"
                            max="180"
                            placeholder="Własny"
                            class="flex-1 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                        />
                        <button
                            @click="setTime(customTime)"
                            :disabled="!customTime || saving"
                            class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-40"
                        >
                            Ustaw
                        </button>
                    </div>
                </div>
            </div>

            <p v-if="savedMsg" class="text-xs text-green-600 font-medium text-center">{{ savedMsg }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

const page    = usePage()
const open    = ref(false)
const saving  = ref(false)
const savedMsg = ref('')
const container = ref(null)

const ordersPaused = ref(page.props.tenant?.orders_paused ?? false)
const prepTime     = ref(page.props.tenant?.estimated_preparation_time ?? 30)
const customTime   = ref(null)

const timePresets = [15, 30, 45, 60, 90]

const save = async (payload) => {
    saving.value = true
    savedMsg.value = ''
    try {
        const res = await axios.post(route('tenant.staff.quick-controls'), payload)
        ordersPaused.value = res.data.orders_paused
        prepTime.value     = res.data.estimated_preparation_time
        savedMsg.value = 'Zapisano!'
        setTimeout(() => { savedMsg.value = '' }, 2000)
    } finally {
        saving.value = false
    }
}

const toggleOrders = () => save({ orders_paused: !ordersPaused.value })

const setTime = (t) => {
    if (!t || t < 5) return
    save({ estimated_preparation_time: t })
    customTime.value = null
}

// Close on outside click
const onClickOutside = (e) => {
    if (container.value && !container.value.contains(e.target)) open.value = false
}
onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>
