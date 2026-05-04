<template>
    <ManagerLayout title="Wsparcie">
        <div class="space-y-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Wsparcie techniczne</h1>
                    <p class="text-sm text-gray-500 mt-1">Zgłoś problem lub zadaj pytanie zespołowi {{ $page.props.app_name }}.</p>
                </div>
                <button
                    type="button"
                    @click="showForm = !showForm"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg"
                >
                    + Nowe zgłoszenie
                </button>
            </div>

            <!-- New ticket form -->
            <div v-if="showForm" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Nowe zgłoszenie</h2>
                <form @submit.prevent="submitTicket" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Temat *</label>
                        <input v-model="form.subject" name="subject" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priorytet</label>
                        <select v-model="form.priority" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="low">Niski</option>
                            <option value="normal">Normalny</option>
                            <option value="high">Wysoki</option>
                            <option value="urgent">Pilny</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Opis problemu *</label>
                        <textarea v-model="form.message" name="message" rows="5" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 resize-none" required></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm">
                            Wyślij zgłoszenie
                        </button>
                        <button type="button" @click="showForm = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm">
                            Anuluj
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tickets list -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div v-if="tickets.length === 0" class="p-8 text-center text-gray-400">
                    <i class="fa-solid fa-headset text-3xl mb-2"></i>
                    <p>Brak zgłoszeń. Kliknij "Nowe zgłoszenie" aby wysłać wiadomość do wsparcia.</p>
                </div>

                <div v-for="ticket in tickets" :key="ticket.id" class="border-b border-gray-100 last:border-0">
                    <Link :href="route('tenant.manager.support.show', ticket.id)" class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <span class="text-gray-400 text-sm">#{{ ticket.id }}</span>
                            <div>
                                <p class="font-medium text-gray-900">{{ ticket.subject }}</p>
                                <p v-if="ticket.latest_message" class="text-sm text-gray-500 truncate max-w-xs">
                                    {{ ticket.latest_message.message }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span :class="priorityClass(ticket.priority)" class="px-2 py-1 rounded-full text-xs font-semibold">
                                {{ priorityLabel(ticket.priority) }}
                            </span>
                            <span :class="statusClass(ticket.status)" class="px-2 py-1 rounded-full text-xs font-semibold">
                                {{ statusLabel(ticket.status) }}
                            </span>
                            <span class="text-xs text-gray-400">{{ formatDate(ticket.created_at) }}</span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

defineProps({
    tickets: { type: Array, default: () => [] },
})

const showForm = ref(false)
const form = reactive({ subject: '', priority: 'normal', message: '' })

function submitTicket() {
    router.post(route('tenant.manager.support.store'), form, {
        onSuccess: () => { showForm.value = false; form.subject = ''; form.message = '' },
    })
}

function priorityClass(p) {
    return { urgent: 'bg-red-100 text-red-800', high: 'bg-orange-100 text-orange-800', normal: 'bg-blue-100 text-blue-800', low: 'bg-gray-100 text-gray-600' }[p] || ''
}
function priorityLabel(p) {
    return { urgent: 'Pilny', high: 'Wysoki', normal: 'Normalny', low: 'Niski' }[p] || p
}
function statusClass(s) {
    return { open: 'bg-yellow-100 text-yellow-800', in_progress: 'bg-blue-100 text-blue-800', resolved: 'bg-green-100 text-green-800', closed: 'bg-gray-100 text-gray-600' }[s] || ''
}
function statusLabel(s) {
    return { open: 'Otwarte', in_progress: 'W toku', resolved: 'Rozwiązane', closed: 'Zamknięte' }[s] || s
}
function formatDate(d) {
    return new Date(d).toLocaleDateString('pl-PL', { day: 'numeric', month: 'short' })
}
</script>
