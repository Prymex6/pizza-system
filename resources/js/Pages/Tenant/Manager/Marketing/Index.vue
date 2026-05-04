<template>
    <Head title="Marketing" />
    <ManagerLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Marketing – Kampanie Email</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Łącznie klientów z emailem: <strong>{{ customersCount }}</strong>
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex items-center gap-2"
                >
                    <i class="fa-solid fa-plus"></i>
                    Nowa kampania
                </button>
            </div>

            <!-- Campaigns list -->
            <div class="space-y-3">
                <div
                    v-for="campaign in campaigns.data"
                    :key="campaign.id"
                    class="bg-white rounded-lg shadow-sm p-5"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="font-semibold text-gray-900">{{ campaign.name }}</h3>
                                <span :class="statusClass(campaign.status)" class="px-2 py-0.5 rounded-full text-xs font-medium">
                                    {{ statusLabel(campaign.status) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-1">
                                <strong>Temat:</strong> {{ campaign.subject }}
                            </p>
                            <p class="text-sm text-gray-500">
                                <strong>Odbiorcy:</strong> {{ targetLabel(campaign.target) }}
                                <span v-if="campaign.status === 'sent'"> · Wysłano do <strong>{{ campaign.recipients_count }}</strong> osób</span>
                            </p>
                            <p v-if="campaign.sent_at" class="text-xs text-gray-400 mt-1">
                                Wysłano: {{ formatDate(campaign.sent_at) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button
                                v-if="campaign.status !== 'sent'"
                                @click="openEditModal(campaign)"
                                class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                            >
                                Edytuj
                            </button>
                            <button
                                v-if="campaign.status !== 'sent'"
                                @click="confirmSend(campaign)"
                                class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                            >
                                <i class="fa-solid fa-paper-plane mr-1"></i>
                                Wyślij
                            </button>
                            <button
                                v-if="campaign.status !== 'sent'"
                                @click="deleteCampaign(campaign)"
                                class="px-3 py-1.5 text-sm text-red-600 hover:text-red-700 border border-red-200 rounded-lg hover:bg-red-50 transition-colors"
                            >
                                Usuń
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="campaigns.data.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
                    <i class="fa-solid fa-bullhorn text-5xl text-gray-300 mb-4 block"></i>
                    <p class="text-gray-500">Brak kampanii. Utwórz pierwszą kampanię emailową.</p>
                </div>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <div v-if="formModal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        {{ formModal.editing ? 'Edytuj kampanię' : 'Nowa kampania' }}
                    </h3>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nazwa kampanii</label>
                            <input v-model="campaignForm.name" name="name" type="text" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                placeholder="np. Promocja weekendowa" />
                            <p v-if="campaignForm.errors.name" class="mt-1 text-sm text-red-600">{{ campaignForm.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Temat emaila</label>
                            <input v-model="campaignForm.subject" name="subject" type="text" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                placeholder="np. 🍕 Specjalna oferta tylko w ten weekend!" />
                            <p v-if="campaignForm.errors.subject" class="mt-1 text-sm text-red-600">{{ campaignForm.errors.subject }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Odbiorcy</label>
                            <select v-model="campaignForm.target"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="all">Wszyscy klienci ({{ customersCount }})</option>
                                <option value="active">Aktywni (zamówienie w ostatnich 90 dniach)</option>
                                <option value="inactive">Nieaktywni (brak zamówień od 90 dni)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Treść emaila</label>
                            <textarea v-model="campaignForm.content" name="content" rows="8" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 resize-y"
                                placeholder="Napisz treść kampanii..."></textarea>
                            <p v-if="campaignForm.errors.content" class="mt-1 text-sm text-red-600">{{ campaignForm.errors.content }}</p>
                            <p class="text-xs text-gray-400 mt-1">Imię klienta zostanie dodane automatycznie na początku emaila.</p>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="formModal.open = false"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                                Anuluj
                            </button>
                            <button type="submit" :disabled="campaignForm.processing"
                                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50">
                                {{ campaignForm.processing ? 'Zapisywanie...' : 'Zapisz szkic' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Confirm Send Modal -->
        <div v-if="sendConfirm.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Wysłać kampanię?</h3>
                <p class="text-gray-600 mb-1">
                    Kampania <strong>{{ sendConfirm.campaign?.name }}</strong> zostanie wysłana do
                    <strong>{{ targetLabel(sendConfirm.campaign?.target) }}</strong>.
                </p>
                <p class="text-sm text-orange-600 bg-orange-50 rounded-lg px-3 py-2 mb-4">
                    Tej akcji nie można cofnąć. Email zostanie wysłany natychmiast.
                </p>
                <div class="flex gap-3">
                    <button @click="sendConfirm.open = false"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                        Anuluj
                    </button>
                    <button @click="doSend"
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
                        Wyślij kampanię
                    </button>
                </div>
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
    campaigns: Object,
    customersCount: Number,
})

const formModal = reactive({ open: false, editing: false, campaignId: null })
const sendConfirm = reactive({ open: false, campaign: null })

const campaignForm = useForm({
    name: '',
    subject: '',
    content: '',
    target: 'all',
})

const openCreateModal = () => {
    campaignForm.reset()
    formModal.editing = false
    formModal.campaignId = null
    formModal.open = true
}

const openEditModal = (campaign) => {
    campaignForm.name = campaign.name
    campaignForm.subject = campaign.subject
    campaignForm.content = campaign.content
    campaignForm.target = campaign.target
    formModal.editing = true
    formModal.campaignId = campaign.id
    formModal.open = true
}

const submitForm = () => {
    if (formModal.editing) {
        campaignForm.put(route('tenant.manager.marketing.update', formModal.campaignId), {
            onSuccess: () => { formModal.open = false },
        })
    } else {
        campaignForm.post(route('tenant.manager.marketing.store'), {
            onSuccess: () => { formModal.open = false },
        })
    }
}

const confirmSend = (campaign) => {
    sendConfirm.campaign = campaign
    sendConfirm.open = true
}

const doSend = () => {
    router.post(route('tenant.manager.marketing.send', sendConfirm.campaign.id), {}, {
        onSuccess: () => { sendConfirm.open = false },
    })
}

const deleteCampaign = (campaign) => {
    if (confirm(`Usunąć kampanię "${campaign.name}"?`)) {
        router.delete(route('tenant.manager.marketing.destroy', campaign.id))
    }
}

const statusLabel = (s) => ({ draft: 'Szkic', scheduled: 'Zaplanowana', sending: 'Wysyłanie', sent: 'Wysłana', cancelled: 'Anulowana' }[s] || s)
const statusClass = (s) => ({
    draft: 'bg-gray-100 text-gray-700',
    sent: 'bg-green-100 text-green-700',
    sending: 'bg-blue-100 text-blue-700',
    cancelled: 'bg-red-100 text-red-700',
}[s] || 'bg-gray-100 text-gray-700')

const targetLabel = (t) => ({ all: 'Wszyscy klienci', active: 'Aktywni klienci', inactive: 'Nieaktywni klienci' }[t] || t)

const formatDate = (d) => new Date(d).toLocaleDateString('pl-PL', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
</script>
