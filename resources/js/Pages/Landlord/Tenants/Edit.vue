<template>
    <LandlordLayout :title="`Edytuj: ${tenant.name}`">
        <div class="max-w-3xl mx-auto space-y-6">

            <p class="text-sm text-gray-500 -mt-4">{{ tenant.subdomain }}.{{ baseDomain }}</p>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Dane restauracji -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Dane restauracji</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nazwa restauracji <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subdomena</label>
                            <div class="flex rounded-lg shadow-sm">
                                <input
                                    :value="tenant.subdomain"
                                    type="text"
                                    disabled
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg text-sm bg-gray-100 text-gray-400 cursor-not-allowed"
                                />
                                <span class="inline-flex items-center px-4 border border-l-0 border-gray-300 rounded-r-lg bg-gray-50 text-gray-400 text-sm font-medium">
                                    .{{ baseDomain }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Subdomeny nie można zmienić.</p>
                        </div>
                    </div>
                </div>

                <!-- Konfiguracja -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Konfiguracja systemu</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                                <select
                                    v-model="form.status"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="active">Aktywny</option>
                                    <option value="suspended">Nieaktywny</option>
                                </select>
                                <p v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Wersja systemu <span class="text-red-500">*</span></label>
                                <select
                                    v-model="form.version"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="stable">Stabilna</option>
                                    <option value="test">Testowa</option>
                                </select>
                                <p v-if="form.errors.version" class="mt-1 text-xs text-red-600">{{ form.errors.version }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Licencja ważna do</label>
                            <input
                                v-model="form.license_ends_at"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :class="isExpired ? 'border-red-400 bg-red-50' : ''"
                            />
                            <div class="flex gap-2 mt-2">
                                <button type="button" @click="setLicense(21)" class="text-xs px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full transition-colors">+3 tygodnie</button>
                                <button type="button" @click="setLicense(180)" class="text-xs px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full transition-colors">+6 miesięcy</button>
                                <button type="button" @click="setLicense(365)" class="text-xs px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full transition-colors">+1 rok</button>
                                <button type="button" @click="form.license_ends_at = ''" class="text-xs px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full transition-colors">Bezterminowo</button>
                            </div>
                            <p v-if="isExpired" class="mt-1 text-xs text-red-600 font-medium">Licencja wygasła!</p>
                            <p v-else-if="!form.license_ends_at" class="mt-1 text-xs text-gray-400">Bezterminowo — brak daty wygaśnięcia.</p>
                            <p v-if="form.errors.license_ends_at" class="mt-1 text-xs text-red-600">{{ form.errors.license_ends_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Dane wewnętrzne (tylko super admin) -->
                <div class="bg-white rounded-xl shadow-sm border border-amber-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-amber-100 bg-amber-50">
                        <h2 class="text-sm font-semibold text-amber-800 uppercase tracking-wide flex items-center gap-2">
                            <i class="fa-solid fa-lock text-amber-500 text-xs"></i>
                            Dane wewnętrzne — widoczne tylko dla super admina
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Imię i nazwisko właściciela</label>
                                <input
                                    v-model="form.billing_owner_name"
                                    type="text"
                                    placeholder="np. Jan Kowalski"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400"
                                />
                                <p v-if="form.errors.billing_owner_name" class="mt-1 text-xs text-red-600">{{ form.errors.billing_owner_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kwota abonamentu (PLN / rok)</label>
                                <div class="relative">
                                    <input
                                        v-model="form.billing_amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="np. 300"
                                        class="w-full px-3 py-2 pr-14 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400"
                                    />
                                    <span class="absolute right-3 top-2 text-gray-400 text-sm">PLN</span>
                                </div>
                                <p v-if="form.errors.billing_amount" class="mt-1 text-xs text-red-600">{{ form.errors.billing_amount }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notatki wewnętrzne</label>
                            <textarea
                                v-model="form.internal_notes"
                                rows="4"
                                placeholder="Dodatkowe informacje, historia płatności, uwagi techniczne..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 resize-none"
                            ></textarea>
                            <p v-if="form.errors.internal_notes" class="mt-1 text-xs text-red-600">{{ form.errors.internal_notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-between pt-2">
                    <Link
                        :href="route('landlord.tenants.index')"
                        class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                    >
                        Anuluj
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Zapisywanie...' : 'Zapisz zmiany' }}
                    </button>
                </div>
            </form>
        </div>
    </LandlordLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'

const props = defineProps({
    tenant: Object,
})

const baseDomain = window.location.hostname

const formatDate = (dt) => {
    if (!dt) return ''
    return new Date(dt).toISOString().split('T')[0]
}

const form = useForm({
    name:               props.tenant.name,
    status:             ['active', 'suspended'].includes(props.tenant.status) ? props.tenant.status : 'active',
    version:            props.tenant.version ?? 'stable',
    license_ends_at:    formatDate(props.tenant.license_ends_at),
    billing_owner_name: props.tenant.data?.billing_owner_name ?? '',
    billing_amount:     props.tenant.data?.billing_amount ?? '',
    internal_notes:     props.tenant.data?.internal_notes ?? '',
})

const isExpired = computed(() => {
    if (!form.license_ends_at) return false
    return new Date(form.license_ends_at) < new Date()
})

function setLicense(days) {
    const d = new Date()
    d.setDate(d.getDate() + days)
    form.license_ends_at = d.toISOString().split('T')[0]
}

const submit = () => {
    form.put(route('landlord.tenants.update', props.tenant.id))
}
</script>
