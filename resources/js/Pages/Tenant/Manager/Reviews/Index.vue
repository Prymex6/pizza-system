<template>
    <Head title="Opinie klientów" />

    <ManagerLayout title="Opinie klientów">
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Opinie klientów</h1>
                    <p class="mt-1 text-sm text-gray-600">Opinie klientów restauracji</p>
                </div>
                <button
                    @click="showForm = true; editingReview = null; resetForm()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium"
                >
                    + Dodaj opinię
                </button>
            </div>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success" class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ $page.props.flash.success }}
            </div>

            <!-- Add/Edit Form -->
            <div v-if="showForm" class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">{{ editingReview ? 'Edytuj opinię' : 'Nowa opinia' }}</h2>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Autor</label>
                            <input
                                v-model="form.author_name"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="np. Jan K."
                            />
                            <p v-if="form.errors.author_name" class="text-sm text-red-600 mt-1">{{ form.errors.author_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Źródło</label>
                            <select
                                v-model="form.source"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">-- brak --</option>
                                <option value="google">Google</option>
                                <option value="facebook">Facebook</option>
                                <option value="tripadvisor">TripAdvisor</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ocena</label>
                            <div class="flex gap-1 mt-1">
                                <button
                                    v-for="n in 5"
                                    :key="n"
                                    type="button"
                                    @click="form.rating = n"
                                    class="text-2xl transition"
                                    :class="n <= form.rating ? 'text-yellow-400' : 'text-gray-300'"
                                >
                                    <i class="fa-solid fa-star"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Treść opinii</label>
                        <textarea
                            v-model="form.content"
                            required
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Treść opinii klienta..."
                        ></textarea>
                        <p v-if="form.errors.content" class="text-sm text-red-600 mt-1">{{ form.errors.content }}</p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium disabled:opacity-50"
                        >
                            {{ form.processing ? 'Zapisywanie...' : (editingReview ? 'Zapisz zmiany' : 'Dodaj opinię') }}
                        </button>
                        <button
                            type="button"
                            @click="showForm = false"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg text-sm font-medium"
                        >
                            Anuluj
                        </button>
                    </div>
                </form>
            </div>

            <!-- Reviews list -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table v-if="reviews.data.length" class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Autor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Treść</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ocena</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Źródło</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Zamówienie</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="review in reviews.data" :key="review.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ review.author_name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">{{ review.content }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-0.5 text-yellow-400">
                                    <i v-for="n in review.rating" :key="n" class="fa-solid fa-star text-sm"></i>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="review.source" class="px-2 py-1 rounded-full text-xs font-medium" :class="sourceClass(review.source)">
                                    {{ review.source }}
                                </span>
                                <span v-else class="text-gray-400 text-xs">-</span>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">
                                <span v-if="review.order">{{ review.order.order_number }}</span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button @click="deleteReview(review)" class="text-red-600 hover:text-red-800 text-sm">
                                        Usuń
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-else class="p-8 text-center text-gray-500">
                    <p class="text-lg mb-2">Brak opinii</p>
                    <p class="text-sm">Dodaj opinie klientów, aby wyświetlić je na stronie restauracji.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="reviews.links && reviews.last_page > 1" class="flex justify-center gap-1 mt-6">
                <template v-for="link in reviews.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                        :class="[
                            'px-3 py-2 text-sm rounded transition-colors',
                            link.active
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                        ]"
                    />
                </template>
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
    reviews: Object,
})

const showForm = ref(false)
const editingReview = ref(null)

const form = useForm({
    author_name: '',
    content: '',
    rating: 5,
    source: '',
})

const resetForm = () => {
    form.author_name = ''
    form.content = ''
    form.rating = 5
    form.source = ''
    form.clearErrors()
}

const submitForm = () => {
    if (editingReview.value) {
        form.put(route('tenant.manager.reviews.update', editingReview.value.id), {
            onSuccess: () => {
                showForm.value = false
                editingReview.value = null
            },
        })
    } else {
        form.post(route('tenant.manager.reviews.store'), {
            onSuccess: () => {
                showForm.value = false
                resetForm()
            },
        })
    }
}

const editReview = (review) => {
    editingReview.value = review
    form.author_name = review.author_name
    form.content = review.content
    form.rating = review.rating
    form.source = review.source || ''
    showForm.value = true
}

const deleteReview = (review) => {
    if (confirm('Czy na pewno chcesz usunąć tę opinię?')) {
        router.delete(route('tenant.manager.reviews.destroy', review.id))
    }
}

const sourceClass = (source) => {
    const classes = {
        google: 'bg-green-100 text-green-800',
        facebook: 'bg-blue-100 text-blue-800',
        tripadvisor: 'bg-emerald-100 text-emerald-800',
    }
    return classes[source] || 'bg-gray-100 text-gray-800'
}
</script>
