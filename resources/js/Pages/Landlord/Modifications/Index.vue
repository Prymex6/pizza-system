<template>
    <LandlordLayout title="Modyfikacje">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Header -->
                <div class="flex justify-between items-start mb-8">
                    <p class="text-sm text-gray-500 mt-1 max-w-lg">
                        System modyfikacji podobny do OpenCart OCMod. Każda modyfikacja definiuje reguły
                        find/replace nakładane na pliki PHP/Blade wybranej restauracji bez zmiany oryginalnego kodu.
                    </p>
                    <Link
                        :href="route('landlord.modifications.create')"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                    >
                        + Nowa modyfikacja
                    </Link>
                </div>

                <!-- Flash -->
                <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ $page.props.flash.success }}</div>
                <div v-if="$page.props.flash?.error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ $page.props.flash.error }}</div>

                <!-- Apply panel -->
                <div class="bg-white shadow rounded-lg p-5 mb-8 border-l-4 border-blue-500">
                    <h2 class="text-sm font-semibold text-gray-700 mb-3">Zastosuj modyfikacje dla restauracji</h2>
                    <div class="flex gap-3 items-end flex-wrap">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Wybierz restaurację</label>
                            <select v-model="selectedTenant" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="">– wybierz –</option>
                                <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }} ({{ t.id }})</option>
                            </select>
                        </div>
                        <Link
                            v-if="selectedTenant"
                            :href="route('landlord.modifications.apply')"
                            method="post"
                            as="button"
                            :data="{ tenant_id: selectedTenant }"
                            preserve-scroll
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"
                        >
                            ▶ Zastosuj modyfikacje
                        </Link>
                        <Link
                            v-if="selectedTenant"
                            :href="route('landlord.modifications.clear')"
                            method="post"
                            as="button"
                            :data="{ tenant_id: selectedTenant }"
                            preserve-scroll
                            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-lg transition-colors"
                        >
                            <i class="fa-solid fa-xmark mr-1"></i> Wyczyść cache
                        </Link>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">
                        Odpowiednik: <code class="bg-gray-100 px-1 rounded">php artisan tenant:mod:apply {tenant_id}</code>
                    </p>
                </div>

                <!-- Modifications list -->
                <div class="bg-white shadow overflow-hidden rounded-lg">
                    <div v-if="modifications.length === 0" class="p-12 text-center text-gray-400">
                        <i class="fa-solid fa-wrench text-4xl mb-3 text-gray-400"></i>
                        <p class="text-lg font-medium">Brak modyfikacji</p>
                        <p class="text-sm mt-1">Kliknij "Nowa modyfikacja" aby stworzyć pierwszą</p>
                    </div>

                    <table v-else class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nazwa / Kod</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Restauracje</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pliki</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Wersja</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="mod in modifications" :key="mod.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ mod.name }}</div>
                                    <div class="text-xs font-mono text-gray-400 mt-0.5">{{ mod.code }}</div>
                                    <div v-if="mod.description" class="text-xs text-gray-500 mt-1 max-w-xs truncate">{{ mod.description }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <span v-if="mod.tenant_count === 'wszystkie'" class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">Wszystkie</span>
                                    <span v-else class="text-gray-700">{{ mod.tenant_count }} restauracji</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ mod.patch_count }} pliki
                                </td>
                                <td class="px-6 py-4 text-sm font-mono text-gray-500">
                                    v{{ mod.version }}
                                    <span v-if="mod.author" class="block text-xs text-gray-400">{{ mod.author }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <Link
                                        :href="route('landlord.modifications.toggle', mod.id)"
                                        method="post"
                                        as="button"
                                        preserve-scroll
                                        :class="mod.status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                                        class="px-3 py-1 rounded-full text-xs font-semibold transition-colors"
                                    >
                                        {{ mod.status ? 'Aktywna' : 'Nieaktywna' }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <Link :href="route('landlord.modifications.edit', mod.id)" class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                        Edytuj
                                    </Link>
                                    <Link
                                        :href="route('landlord.modifications.destroy', mod.id)"
                                        method="delete"
                                        as="button"
                                        class="text-red-600 hover:text-red-900 text-sm font-medium"
                                        @click.prevent="confirmDelete(mod)"
                                    >
                                        Usuń
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Info box -->
                <div class="mt-6 bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                    <p class="font-semibold mb-2">Jak działa system modyfikacji?</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs">
                        <li>Tworzysz modyfikację z regułami JSON (które pliki → co zmienić)</li>
                        <li>Przypisujesz do wybranej restauracji lub do wszystkich</li>
                        <li>Klikasz "Zastosuj" lub uruchamiasz: <code class="bg-amber-100 px-1 rounded font-mono">php artisan tenant:mod:apply {id}</code></li>
                        <li>System generuje zmodyfikowane kopie plików w <code class="bg-amber-100 px-1 rounded font-mono">storage/modifications/{tenant_id}/</code></li>
                        <li>Autoloader PHP wykrywa te kopie i używa ich zamiast oryginałów dla tej restauracji</li>
                        <li>Oryginalne pliki NIE są zmieniane – pozostałe restauracje działają normalnie</li>
                    </ol>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'

defineProps({
    modifications: Array,
    tenants: Array,
})

const selectedTenant = ref('')

const confirmDelete = (mod) => {
    if (confirm(`Usunąć modyfikację "${mod.name}"?`)) {
        router.delete(route('landlord.modifications.destroy', mod.id))
    }
}
</script>
