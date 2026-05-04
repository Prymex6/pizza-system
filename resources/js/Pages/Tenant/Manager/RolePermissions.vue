<template>
    <ManagerLayout title="Uprawnienia ról">
        <div class="space-y-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Uprawnienia ról</h1>
                <p class="text-sm text-gray-500 mt-1">Skonfiguruj co każda rola pracownika może robić w systemie.</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <form @submit.prevent="save">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-gray-700">Uprawnienie</th>
                                <th v-for="role in roles" :key="role" class="px-4 py-3 text-center font-semibold text-gray-700 capitalize">
                                    {{ roleLabel(role) }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(label, perm) in permissions" :key="perm" class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-800">{{ label }}</td>
                                <td v-for="role in roles" :key="role" class="px-4 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        v-model="form[role][perm]"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded cursor-pointer"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="px-5 py-4 border-t border-gray-100 flex justify-end">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold text-sm rounded-lg transition-colors"
                        >
                            {{ processing ? 'Zapisywanie...' : 'Zapisz uprawnienia' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
    permissions: Object,
    roles: Array,
    currentPermissions: Object,
})

const processing = ref(false)

// Build reactive form object: form[role][permission] = bool
const form = ref({})
for (const role of props.roles) {
    form.value[role] = {}
    for (const perm of Object.keys(props.permissions)) {
        form.value[role][perm] = (props.currentPermissions[role] || []).includes(perm)
    }
}

const save = () => {
    processing.value = true
    router.put(route('tenant.manager.role-permissions.update'), { permissions: form.value }, {
        onFinish: () => { processing.value = false },
    })
}

const roleLabel = (role) => {
    const labels = { chef: 'Kucharz', waiter: 'Kelner', driver: 'Kierowca', cashier: 'Kasjer' }
    return labels[role] || role
}
</script>
