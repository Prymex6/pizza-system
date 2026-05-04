<template>
    <Head title="Kampanie lojalnościowe" />
    <ManagerLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Kampanie lojalnościowe</h1>
                    <p class="text-sm text-gray-500 mt-1">Mnożniki punktów w wybranych dniach lub kategoriach</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('tenant.manager.loyalty.index')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">
                        Powrót
                    </Link>
                    <button @click="openModal()" class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600">
                        + Dodaj kampanię
                    </button>
                </div>
            </div>

            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                {{ $page.props.flash.success }}
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kampania</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mnożnik</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zakres</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dni tygodnia</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="campaign in campaigns" :key="campaign.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ campaign.name }}</div>
                                <div v-if="campaign.valid_from || campaign.valid_until" class="text-xs text-gray-400 mt-0.5">
                                    {{ campaign.valid_from ? fmtDate(campaign.valid_from) : '...' }}
                                    →
                                    {{ campaign.valid_until ? fmtDate(campaign.valid_until) : 'bezterminowo' }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-lg font-bold text-amber-600">×{{ campaign.multiplier }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ appliesLabel(campaign) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ campaign.day_of_week !== null ? dayName(campaign.day_of_week) : 'Każdy dzień' }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="campaign.is_active ? 'text-green-600 bg-green-50' : 'text-gray-500 bg-gray-100'"
                                    class="px-2 py-1 rounded text-xs font-medium">
                                    {{ campaign.is_active ? 'Aktywna' : 'Nieaktywna' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button @click="openModal(campaign)" class="text-sm text-blue-600 hover:text-blue-700">Edytuj</button>
                                <button @click="deleteCampaign(campaign)" class="text-sm text-red-500 hover:text-red-600">Usuń</button>
                            </td>
                        </tr>
                        <tr v-if="campaigns.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                Brak kampanii. Kliknij "+ Dodaj kampanię", aby dodać pierwszą.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="modal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        {{ modal.campaign ? 'Edytuj kampanię' : 'Dodaj kampanię' }}
                    </h3>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nazwa kampanii</label>
                            <input v-model="form.name" name="name" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mnożnik punktów</label>
                                <input v-model.number="form.multiplier" name="multiplier" type="number" min="1" max="10" step="0.1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                                <p class="text-xs text-gray-400 mt-0.5">np. 2.0 = podwójne punkty</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dotyczy</label>
                                <select v-model="form.applies_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="all">Wszystkich produktów</option>
                                    <option value="category">Kategorii</option>
                                    <option value="product">Produktu</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="form.applies_to === 'category'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategoria</label>
                            <select v-model.number="form.target_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option :value="null" disabled>Wybierz kategorię...</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>

                        <div v-else-if="form.applies_to === 'product'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Produkt</label>
                            <select v-model.number="form.target_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option :value="null" disabled>Wybierz produkt...</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dzień tygodnia (puste = każdy)</label>
                            <select v-model="form.day_of_week" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option :value="null">Każdy dzień</option>
                                <option :value="0">Niedziela</option>
                                <option :value="1">Poniedziałek</option>
                                <option :value="2">Wtorek</option>
                                <option :value="3">Środa</option>
                                <option :value="4">Czwartek</option>
                                <option :value="5">Piątek</option>
                                <option :value="6">Sobota</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ważna od</label>
                                <input v-model="form.valid_from" type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ważna do</label>
                                <input v-model="form.valid_until" type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                            </div>
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="h-4 w-4 text-blue-600 rounded" />
                            <span class="text-sm text-gray-700">Kampania aktywna</span>
                        </label>

                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="modal.open = false" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                                Anuluj
                            </button>
                            <button type="submit" :disabled="form.processing" class="flex-1 px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 disabled:opacity-50">
                                {{ form.processing ? 'Zapisywanie...' : 'Zapisz' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({ campaigns: Array, products: Array, categories: Array })

const modal = reactive({ open: false, campaign: null })

const form = useForm({
    name: '',
    multiplier: 2.0,
    applies_to: 'all',
    target_id: null,
    day_of_week: null,
    valid_from: null,
    valid_until: null,
    is_active: true,
})

const openModal = (campaign = null) => {
    modal.campaign = campaign
    modal.open = true
    if (campaign) {
        form.name = campaign.name
        form.multiplier = campaign.multiplier
        form.applies_to = campaign.applies_to
        form.target_id = campaign.target_id
        form.day_of_week = campaign.day_of_week
        form.valid_from = campaign.valid_from ? campaign.valid_from.slice(0, 16) : null
        form.valid_until = campaign.valid_until ? campaign.valid_until.slice(0, 16) : null
        form.is_active = campaign.is_active
    } else {
        form.reset()
        form.multiplier = 2.0
        form.applies_to = 'all'
        form.is_active = true
    }
}

const submitForm = () => {
    if (modal.campaign) {
        form.put(route('tenant.manager.loyalty.campaigns.update', modal.campaign.id), {
            onSuccess: () => { modal.open = false },
        })
    } else {
        form.post(route('tenant.manager.loyalty.campaigns.store'), {
            onSuccess: () => { modal.open = false },
        })
    }
}

watch(() => form.applies_to, () => { form.target_id = null })

const deleteCampaign = (campaign) => {
    if (!confirm(`Usunąć kampanię "${campaign.name}"?`)) return
    router.delete(route('tenant.manager.loyalty.campaigns.destroy', campaign.id))
}

const dayNames = ['Niedziela', 'Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota']
const dayName = (d) => dayNames[d] || d

const appliesLabel = (c) => {
    if (c.applies_to === 'all') return 'Wszystkie produkty'
    if (c.applies_to === 'category') {
        const cat = props.categories?.find(x => x.id === c.target_id)
        return cat ? `Kategoria: ${cat.name}` : `Kategoria #${c.target_id}`
    }
    const prod = props.products?.find(x => x.id === c.target_id)
    return prod ? `Produkt: ${prod.name}` : `Produkt #${c.target_id}`
}

const fmtDate = (d) => d ? d.slice(0, 10) : ''
</script>
