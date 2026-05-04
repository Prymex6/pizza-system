<template>
    <ManagerLayout title="Licencja">
        <div class="space-y-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Licencja i subskrypcja</h1>
                <p class="text-sm text-gray-500 mt-1">Informacje o Twojej subskrypcji {{ $page.props.app_name }}.</p>
            </div>

            <!-- License Info Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Status -->
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Status</p>
                        <span
                            :class="{
                                'bg-green-100 text-green-800': tenantData?.status === 'active',
                                'bg-blue-100 text-blue-800': tenantData?.status === 'trial',
                                'bg-red-100 text-red-800': tenantData?.status === 'suspended',
                            }"
                            class="inline-block px-3 py-1 rounded-full text-sm font-semibold capitalize"
                        >
                            {{ statusLabel }}
                        </span>
                    </div>

                    <!-- License validity -->
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">
                            {{ tenantData?.status === 'trial' ? 'Koniec okresu próbnego' : 'Licencja ważna do' }}
                        </p>
                        <p v-if="tenantData?.license_ends_at" class="text-lg font-semibold" :class="isExpiringSoon ? 'text-red-600' : 'text-gray-900'">
                            {{ formatDate(tenantData.license_ends_at) }}
                        </p>
                        <p v-else class="text-lg font-semibold text-gray-400">Bezterminowo</p>
                        <p v-if="isExpiringSoon" class="text-xs text-red-500 mt-0.5">Licencja wygasa wkrótce!</p>
                    </div>

                    <!-- Orders this month -->
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Zamówienia w tym miesiącu</p>
                        <p class="text-lg font-bold text-gray-900">
                            {{ ordersThisMonth }}
                            <span class="text-sm text-gray-400">(nielimitowane)</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Renewal CTA -->
            <div v-if="tenantData?.status === 'trial' || isExpiringSoon" class="bg-orange-50 border border-orange-200 rounded-xl p-5 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-orange-900">Odnów lub aktywuj licencję</p>
                    <p class="text-sm text-orange-700 mt-1">Skontaktuj się z nami, aby kontynuować korzystanie z {{ $page.props.app_name }}.</p>
                </div>
                <a href="mailto:support@roveto.pl" class="shrink-0 ml-4 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-lg transition-colors">
                    Skontaktuj się
                </a>
            </div>

            <!-- No data fallback -->
            <div v-if="!tenantData" class="bg-gray-50 border border-gray-200 rounded-xl p-6 text-center text-gray-400">
                <i class="fa-solid fa-circle-info text-3xl mb-2"></i>
                <p>Nie można pobrać danych licencji. Skontaktuj się z administratorem.</p>
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { computed } from 'vue'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
    tenantData: { type: Object, default: null },
    ordersThisMonth: { type: Number, default: 0 },
})

const statusLabel = computed(() => {
    const map = { active: 'Aktywna', trial: 'Okres próbny', suspended: 'Zawieszona' }
    return map[props.tenantData?.status] || props.tenantData?.status || '-'
})

const isExpiringSoon = computed(() => {
    if (!props.tenantData?.license_ends_at) return false
    const days = (new Date(props.tenantData.license_ends_at) - new Date()) / 86400000
    return days < 7
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('pl-PL', { year: 'numeric', month: 'long', day: 'numeric' })
}
</script>
