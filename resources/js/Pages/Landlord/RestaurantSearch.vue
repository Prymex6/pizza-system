<template>
    <LandlordLayout title="Wyszukiwarka restauracji">
        <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Search form -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Miasto</label>
                        <input
                            v-model="city"
                            type="text"
                            placeholder="np. Kraków"
                            @keydown.enter="doSearch"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Promień</label>
                        <select v-model="radius" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="1000">1 km</option>
                            <option value="2000">2 km</option>
                            <option value="5000">5 km</option>
                            <option value="10000">10 km</option>
                            <option value="20000">20 km</option>
                            <option value="50000">50 km</option>
                        </select>
                    </div>
                    <button
                        @click="doSearch"
                        :disabled="loading || !city.trim()"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium rounded-lg text-sm transition-colors"
                    >
                        <template v-if="loading">Szukam…</template>
                        <template v-else><i class="fa-solid fa-magnifying-glass mr-1"></i> Szukaj</template>
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-3">
                    Dane z OpenStreetMap. Dla restauracji bez danych kontaktowych system automatycznie szuka ich w internecie.
                    Użyj <strong>Google</strong> w kolumnie Akcje, aby ręcznie sprawdzić wyniki.
                </p>
            </div>

            <!-- Error -->
            <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-red-700 text-sm">
                {{ error }}
            </div>

            <!-- Results summary -->
            <div v-if="results !== null" class="flex flex-wrap items-center gap-3 mb-4">
                <div class="text-sm text-gray-600 flex-1">
                    Znaleziono <strong>{{ results.length }}</strong> miejsc w promieniu
                    <strong>{{ radiusLabel }}</strong> od <strong>{{ foundCity }}</strong>
                    &nbsp;·&nbsp;
                    <span class="text-green-600 font-medium">{{ withContact }} z danymi w OSM</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <select v-model="typeFilter" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
                        <option value="">Wszystkie typy</option>
                        <option value="Restauracja">Restauracja</option>
                        <option value="Kawiarnia">Kawiarnia</option>
                        <option value="Fast food">Fast food</option>
                    </select>
                    <button
                        @click="contactOnly = !contactOnly"
                        :class="contactOnly ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'"
                        class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                    >
                        <i v-if="contactOnly" class="fa-solid fa-check mr-1"></i> Tylko z danymi
                    </button>
                    <button
                        @click="withEmailOnly = !withEmailOnly"
                        :class="withEmailOnly ? 'bg-green-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'"
                        class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                    >
                        <i v-if="withEmailOnly" class="fa-solid fa-check mr-1"></i> Tylko z emailem
                    </button>
                </div>
            </div>

            <!-- Results table -->
            <div v-if="results !== null" class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nazwa</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adres</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Strona / FB</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">E-mail</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telefon</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template v-for="r in filtered" :key="r.id">
                            <!-- Main row -->
                            <tr :class="r.has_contact ? 'bg-white' : 'bg-gray-50'">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 text-sm">{{ r.name }}</div>
                                    <div class="text-xs text-gray-400">{{ r.type }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 max-w-xs">{{ r.address || '—' }}</td>
                                <td class="px-4 py-3 text-xs space-y-1">
                                    <!-- OSM data or manual override -->
                                    <template v-if="!editing[r.id]">
                                        <a v-if="r._website ?? r.website" :href="r._website ?? r.website" target="_blank" rel="noopener"
                                            class="flex items-center gap-1 text-blue-600 hover:underline truncate max-w-[180px]">
                                            <i class="fa-solid fa-globe"></i> {{ r._website ?? r.website }}
                                        </a>
                                        <a v-if="r._facebook ?? r.facebook" :href="r._facebook ?? r.facebook" target="_blank" rel="noopener"
                                            class="flex items-center gap-1 text-blue-600 hover:underline">
                                            <i class="fa-brands fa-facebook"></i> Facebook
                                        </a>
                                        <span v-if="!r._website && !r.website && !r._facebook && !r.facebook" class="text-gray-300 italic text-xs">brak</span>
                                    </template>
                                    <!-- Edit mode -->
                                    <template v-else>
                                        <input v-model="editData[r.id].website" type="url" placeholder="https://..." class="w-full px-2 py-1 border rounded text-xs mb-1" />
                                        <input v-model="editData[r.id].facebook" type="url" placeholder="Facebook URL..." class="w-full px-2 py-1 border rounded text-xs" />
                                    </template>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <template v-if="!editing[r.id]">
                                        <a v-if="r._email ?? r.email" :href="`mailto:${r._email ?? r.email}`" class="text-blue-600 hover:underline">{{ r._email ?? r.email }}</a>
                                        <span v-else class="text-gray-300 italic">brak</span>
                                    </template>
                                    <input v-else v-model="editData[r.id].email" type="email" placeholder="email@..." class="w-full px-2 py-1 border rounded text-xs" />
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-700">
                                    <template v-if="!editing[r.id]">
                                        {{ r._phone ?? r.phone ?? '—' }}
                                    </template>
                                    <input v-else v-model="editData[r.id].phone" type="text" placeholder="+48..." class="w-full px-2 py-1 border rounded text-xs" />
                                </td>
                                <td class="px-4 py-3 text-xs whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <!-- Send email -->
                                        <button v-if="r._email ?? r.email"
                                            @click="sendEmail(r)"
                                            :disabled="sendingEmail[r.id]"
                                            class="text-green-600 hover:text-green-800 font-medium text-left disabled:opacity-50">
                                            <i class="fa-solid fa-envelope"></i>
                                            {{ sentEmail[r.id] ? 'Wysłano!' : (sendingEmail[r.id] ? 'Wysyłam…' : 'Wyślij email') }}
                                        </button>
                                        <!-- Facebook Messenger -->
                                        <a v-if="r._facebook ?? r.facebook"
                                            :href="messengerUrl(r._facebook ?? r.facebook)"
                                            target="_blank" rel="noopener"
                                            @click="onMessengerClick(r)"
                                            class="text-blue-600 hover:text-blue-800 font-medium">
                                            <i class="fa-brands fa-facebook-messenger"></i>
                                            {{ copiedMarketing === r.id ? 'Skopiowano!' : 'Messenger' }}
                                        </a>
                                        <!-- Google search -->
                                        <a :href="`https://www.google.com/search?q=${encodeURIComponent(r.name + ' ' + foundCity + ' restauracja')}`"
                                            target="_blank" rel="noopener"
                                            class="text-orange-600 hover:text-orange-800 font-medium">
                                            <i class="fa-solid fa-magnifying-glass"></i> Google
                                        </a>
                                        <!-- Google Maps -->
                                        <a v-if="r.maps_url" :href="r.maps_url" target="_blank" rel="noopener"
                                            class="text-green-600 hover:text-green-800 font-medium">
                                            <i class="fa-solid fa-location-dot"></i> Maps
                                        </a>
                                        <!-- Copy marketing message -->
                                        <button @click="copyMarketing(r)"
                                            class="text-purple-600 hover:text-purple-800 font-medium text-left">
                                            <i class="fa-solid fa-copy"></i>
                                            {{ copiedMarketing === r.id ? 'Skopiowano!' : 'Kopiuj' }}
                                        </button>
                                        <!-- Contacted toggle -->
                                        <button @click="toggleContacted(r)"
                                            :class="r.contacted_at ? 'text-green-600 hover:text-green-800' : 'text-gray-400 hover:text-gray-600'"
                                            class="font-medium text-left"
                                            :title="r.contacted_at ? 'Wysłano: ' + formatContacted(r.contacted_at) : 'Oznacz jako wysłane'"
                                        >
                                            <i :class="r.contacted_at ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle'"></i>
                                            {{ r.contacted_at ? 'Wysłano ' + formatContacted(r.contacted_at) : 'Wysłałem' }}
                                        </button>
                                        <!-- searching indicator -->
                                        <span v-if="autoLoading[r.id]" class="text-gray-400 text-xs"><i class="fa-solid fa-magnifying-glass"></i> szukam…</span>
                                        <!-- Edit / Save -->
                                        <button v-if="!editing[r.id]" @click="startEdit(r)"
                                            class="text-blue-600 hover:text-blue-800 font-medium text-left">
                                            <i class="fa-solid fa-pen"></i> Edytuj
                                        </button>
                                        <template v-else>
                                            <button @click="saveEdit(r)" class="text-green-600 hover:text-green-800 font-medium text-left"><i class="fa-solid fa-check"></i> Zapisz</button>
                                            <button @click="cancelEdit(r.id)" class="text-gray-400 hover:text-gray-600 text-left"><i class="fa-solid fa-xmark"></i> Anuluj</button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="filtered.length === 0">
                            <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                                {{ contactOnly ? 'Brak wyników z danymi w OSM' : 'Brak wyników' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
        </div>
    </LandlordLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import axios from 'axios'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'

const city          = ref('')
const radius        = ref('5000')
const loading       = ref(false)
const error         = ref(null)
const results       = ref(null)
const foundCity     = ref('')
const contactOnly   = ref(false)
const withEmailOnly = ref(false)
const typeFilter    = ref('')

// Per-row edit state
const editing         = reactive({})
const editData        = reactive({})
const autoLoading     = reactive({})
const copiedMarketing = ref(null)
const sendingEmail    = reactive({})
const sentEmail       = reactive({})

const marketingMessage = (name) => `Dzień dobry, zwracam się do ${name} 👋

Zauważyłem, że nie korzystają Państwo jeszcze z systemu zamówień online — chciałem przedstawić Roveto, platformę którą stworzyłem z myślą o restauracjach takich jak Wasza.

Co oferuje Roveto:
✅ Własna strona z zamówieniami online (bez prowizji!)
✅ Menu online z wariantami, składnikami, alergenami
✅ Śledzenie zamówień na żywo przez klienta
✅ Panel managera — zarządzanie menu, zamówieniami, raportami
✅ Strefy dostaw, kody rabatowe, program lojalnościowy
✅ Powiadomienia dla kuchni i kierowców w czasie rzeczywistym

Pierwsze 14 dni bezpłatnie — bez zobowiązań, bez płatności.

Więcej informacji: roveto.pl
Chętnie odpowiem na pytania lub umówię prezentację 🙂`

const copyMarketing = (r) => {
    navigator.clipboard.writeText(marketingMessage(r.name)).then(() => {
        copiedMarketing.value = r.id
        setTimeout(() => { copiedMarketing.value = null }, 2500)
    })
}

const toggleContacted = async (r) => {
    try {
        const res = await axios.post(route('landlord.restaurant-search.toggle-contacted'), { osm_id: r.id })
        r.contacted_at = res.data.contacted_at
    } catch {}
}

const formatContacted = (iso) => {
    if (!iso) return ''
    return new Date(iso).toLocaleDateString('pl-PL', { day: 'numeric', month: 'short' })
}

const radiusLabel = computed(() => {
    const map = { '1000': '1 km', '2000': '2 km', '5000': '5 km', '10000': '10 km', '20000': '20 km', '50000': '50 km' }
    return map[radius.value] ?? radius.value
})

const withContact = computed(() => results.value?.filter(r => r.has_contact || r._website || r._facebook || r._email).length ?? 0)

const filtered = computed(() => {
    if (!results.value) return []
    const list = results.value.filter(r => {
        if (contactOnly.value && !r.has_contact && !r._website && !r._facebook && !r._email && !r._phone) return false
        if (withEmailOnly.value && !r._email && !r.email) return false
        if (typeFilter.value && r.type !== typeFilter.value) return false
        return true
    })
    // Contacted go to bottom
    return [...list.filter(r => !r.contacted_at), ...list.filter(r => r.contacted_at)]
})

const onMessengerClick = (r) => {
    copyMarketing(r)
    if (!r.contacted_at) toggleContacted(r)
}

const messengerUrl = (fbUrl) => {
    // Extract page slug from FB URL and open messenger
    const match = fbUrl.match(/facebook\.com\/([a-zA-Z0-9.\-_]+)\/?/)
    if (match) return `https://m.me/${match[1]}`
    return fbUrl
}

const sendEmail = async (r) => {
    const email = r._email ?? r.email
    if (!email || sendingEmail[r.id]) return
    sendingEmail[r.id] = true
    try {
        await axios.post(route('landlord.restaurant-search.send-email'), {
            osm_id:  r.id,
            email:   email,
            name:    r.name,
            message: marketingMessage(r.name),
        })
        sentEmail[r.id] = true
        r.contacted_at = new Date().toISOString()
        setTimeout(() => { sentEmail[r.id] = false }, 4000)
    } catch (e) {
        alert('Błąd wysyłki: ' + (e.response?.data?.message ?? e.message))
    } finally {
        sendingEmail[r.id] = false
    }
}

const startEdit = (r) => {
    editData[r.id] = {
        website:  r._website  ?? r.website  ?? '',
        facebook: r._facebook ?? r.facebook ?? '',
        email:    r._email    ?? r.email    ?? '',
        phone:    r._phone    ?? r.phone    ?? '',
    }
    editing[r.id] = true
}

const saveEdit = (r) => {
    const d = editData[r.id]
    r._website  = d.website  || null
    r._facebook = d.facebook || null
    r._email    = d.email    || null
    r._phone    = d.phone    || null
    editing[r.id] = false
}

const cancelEdit = (id) => {
    editing[id] = false
}

// Token to cancel sequential search when a new search starts
let searchToken = 0

const autoFind = async (r) => {
    autoLoading[r.id] = true
    try {
        const res = await axios.get(route('landlord.restaurant-search.find-contact'), {
            params: { name: r.name, city: foundCity.value },
        })
        const d = res.data
        if (d.website)  r._website  = d.website
        if (d.facebook) r._facebook = d.facebook
        if (d.email)    r._email    = d.email
    } catch {
        // silently skip on error
    } finally {
        autoLoading[r.id] = false
    }
}

const autoFindSequential = async (items, token) => {
    for (let i = 0; i < items.length; i++) {
        if (searchToken !== token) return  // new search started, abort
        await autoFind(items[i])
        if (i < items.length - 1 && searchToken === token) {
            await new Promise(resolve => setTimeout(resolve, 900))
        }
    }
}

const doSearch = async () => {
    if (!city.value.trim() || loading.value) return
    loading.value = true
    error.value   = null
    results.value = null
    searchToken++  // cancel any in-progress sequential search
    const token = searchToken

    try {
        const res = await axios.get(route('landlord.restaurant-search.search'), {
            params: { city: city.value, radius: radius.value },
        })
        results.value = res.data.results
        foundCity.value = city.value
        // Auto-find contact sequentially (900ms apart) for restaurants missing data
        const toSearch = results.value.filter(r => !r.website && !r.facebook && !r.email)
        autoFindSequential(toSearch, token)
    } catch (e) {
        error.value = e.response?.data?.error ?? 'Wystąpił nieoczekiwany błąd.'
    } finally {
        loading.value = false
    }
}
</script>
