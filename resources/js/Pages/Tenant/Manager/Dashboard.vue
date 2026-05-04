<template>
    <ManagerLayout title="Dashboard">
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Przegląd aktywności restauracji
                </p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Dzisiaj -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-3xl text-blue-500"><i class="fa-solid fa-calendar-day"></i></span>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt
                                        class="text-sm font-medium text-gray-500 truncate"
                                    >
                                        Dzisiaj
                                    </dt>
                                    <dd class="flex items-baseline">
                                        <div
                                            class="text-2xl font-semibold text-gray-900"
                                        >
                                            {{ stats.today.orders }} zamówień
                                        </div>
                                    </dd>
                                    <dd class="text-sm text-gray-600">
                                        {{ formatPrice(stats.today.revenue) }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tydzień -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-3xl text-green-500"><i class="fa-solid fa-chart-bar"></i></span>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt
                                        class="text-sm font-medium text-gray-500 truncate"
                                    >
                                        Ten tydzień
                                    </dt>
                                    <dd class="flex items-baseline">
                                        <div
                                            class="text-2xl font-semibold text-gray-900"
                                        >
                                            {{ stats.week.orders }} zamówień
                                        </div>
                                    </dd>
                                    <dd class="text-sm text-gray-600">
                                        {{ formatPrice(stats.week.revenue) }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Miesiąc -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-3xl text-purple-500"><i class="fa-solid fa-chart-line"></i></span>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt
                                        class="text-sm font-medium text-gray-500 truncate"
                                    >
                                        Ten miesiąc
                                    </dt>
                                    <dd class="flex items-baseline">
                                        <div
                                            class="text-2xl font-semibold text-gray-900"
                                        >
                                            {{ stats.month.orders }} zamówień
                                        </div>
                                    </dd>
                                    <dd class="text-sm text-gray-600">
                                        {{ formatPrice(stats.month.revenue) }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu Stats -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Menu</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <div class="text-sm text-gray-500">Produkty</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ menu.products }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Kategorie</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ menu.categories }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Aktywne</div>
                        <div class="text-2xl font-semibold text-green-600">{{ menu.active }}</div>
                    </div>
                </div>
            </div>

            <!-- Date range filter -->
            <div class="bg-white shadow rounded-lg p-4">
                <div class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Od</label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Do</label>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        />
                    </div>
                    <button
                        @click="applyDateFilter"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700"
                    >
                        Zastosuj
                    </button>
                    <button
                        @click="resetDateFilter"
                        class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200"
                    >
                        Ostatnie 30 dni
                    </button>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-1">Przychody – {{ filters.date_from }} → {{ filters.date_to }}</h3>
                <p class="text-sm text-gray-500 mb-4">Tylko opłacone zamówienia</p>
                <div class="flex items-end gap-1 h-40 overflow-x-auto">
                    <div
                        v-for="day in revenueChart"
                        :key="day.date"
                        class="flex flex-col items-center gap-1 flex-shrink-0"
                        style="min-width: 20px;"
                        :title="`${day.label}: ${day.revenue.toFixed(2)} zł (${day.orders} zamówień)`"
                    >
                        <div
                            class="bg-blue-500 hover:bg-blue-600 rounded-t w-full transition-all cursor-pointer"
                            :style="{ height: maxRevenue > 0 ? (day.revenue / maxRevenue * 120) + 'px' : '2px', minHeight: day.revenue > 0 ? '4px' : '1px', opacity: day.revenue > 0 ? 1 : 0.2 }"
                        ></div>
                        <span v-if="revenueChart.indexOf(day) % 5 === 0" class="text-xs text-gray-400" style="writing-mode: vertical-rl; transform: rotate(180deg);">{{ day.label }}</span>
                    </div>
                </div>
                <div class="mt-2 flex justify-between text-xs text-gray-400">
                    <span>{{ revenueChart[0]?.label }}</span>
                    <span>Maks: {{ maxRevenue.toFixed(0) }} zł/dzień</span>
                    <span>{{ revenueChart[revenueChart.length - 1]?.label }}</span>
                </div>
            </div>

            <!-- Hourly Heatmap -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-1">Aktywność wg godzin</h3>
                <p class="text-sm text-gray-500 mb-4">Liczba zamówień na godzinę (ostatnie 30 dni)</p>
                <div class="grid grid-cols-12 gap-1">
                    <div
                        v-for="h in hourlyHeatmap"
                        :key="h.hour"
                        class="rounded aspect-square flex items-center justify-center text-xs font-semibold cursor-default transition-colors"
                        :style="{ backgroundColor: heatmapColor(h.count), color: h.count > maxHourly * 0.6 ? 'white' : '#374151' }"
                        :title="`${h.label}: ${h.count} zamówień`"
                    >
                        {{ h.hour }}
                    </div>
                </div>
                <div class="mt-2 flex gap-2 items-center text-xs text-gray-400">
                    <div class="w-4 h-4 rounded bg-blue-100"></div><span>mało</span>
                    <div class="w-4 h-4 rounded bg-blue-400"></div><span>średnio</span>
                    <div class="w-4 h-4 rounded bg-blue-700"></div><span>dużo</span>
                </div>
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from "@/Layouts/ManagerLayout.vue";

const props = defineProps({
    stats: Object,
    recentOrders: Array,
    popularProducts: Array,
    ordersByType: Object,
    menu: Object,
    revenueChart: { type: Array, default: () => [] },
    hourlyHeatmap: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const dateFrom = ref(props.filters.date_from ?? '')
const dateTo = ref(props.filters.date_to ?? '')

const applyDateFilter = () => {
    router.get(route('tenant.manager.dashboard'), { date_from: dateFrom.value, date_to: dateTo.value }, { preserveState: false })
}

const resetDateFilter = () => {
    dateFrom.value = ''
    dateTo.value = ''
    router.get(route('tenant.manager.dashboard'), {}, { preserveState: false })
}

const formatPrice = (price) => {
    return new Intl.NumberFormat("pl-PL", {
        style: "currency",
        currency: "PLN",
    }).format(price);
};

const maxRevenue = computed(() => Math.max(...props.revenueChart.map(d => d.revenue), 0))
const maxHourly = computed(() => Math.max(...props.hourlyHeatmap.map(h => h.count), 1))

function heatmapColor(count) {
    if (count === 0) return '#f3f4f6'
    const intensity = Math.round((count / maxHourly.value) * 255)
    const blue = 255 - intensity
    return `rgb(${59 - Math.round(intensity * 0.1)}, ${130 - Math.round(intensity * 0.3)}, ${intensity + blue > 200 ? 235 : 200})`
}
</script>
