<template>
    <ManagerLayout title="Strefy dostaw">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Strefy Dostawy</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Zarządzaj strefami dostawy i opłatami
                    </p>
                </div>
                <button
                    v-if="googleMapsConfigured"
                    @click="openModal()"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150"
                >
                    <i class="fa-solid fa-plus mr-2"></i>
                    Dodaj Strefę
                </button>
            </div>

            <!-- Google Maps Warning -->
            <div v-if="!googleMapsConfigured" class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-triangle-exclamation text-amber-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Google Maps API nie jest skonfigurowane.</strong>
                            Skontaktuj się z administratorem, aby włączyć funkcję stref dostawy.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Zones List -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="zone in zones"
                    :key="zone.id"
                    class="bg-white shadow rounded-lg p-5 hover:shadow-lg transition-shadow"
                >
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center space-x-2">
                            <div
                                class="w-4 h-4 rounded-full"
                                :style="{ backgroundColor: zone.color || '#3B82F6' }"
                            ></div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ zone.name }}</h3>
                        </div>
                        <span
                            :class="zone.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                            class="px-2 py-1 text-xs font-semibold rounded-full"
                        >
                            {{ zone.is_active ? 'Aktywna' : 'Nieaktywna' }}
                        </span>
                    </div>

                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Opłata za dostawę:</span>
                            <span class="font-medium">{{ formatPrice(zone.delivery_fee) }}</span>
                        </div>
                        <div v-if="zone.min_order_value" class="flex justify-between text-sm">
                            <span class="text-gray-600">Min. wartość zamówienia:</span>
                            <span class="font-medium">{{ formatPrice(zone.min_order_value) }}</span>
                        </div>
                        <div v-if="zone.estimated_time" class="flex justify-between text-sm">
                            <span class="text-gray-600">Szacowany czas:</span>
                            <span class="font-medium">{{ zone.estimated_time }} min</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Punkty wielokąta:</span>
                            <span class="font-medium">{{ zone.polygon.length }}</span>
                        </div>
                    </div>

                    <div class="flex space-x-2">
                        <button
                            v-if="googleMapsConfigured"
                            @click="openModal(zone)"
                            class="flex-1 text-sm text-blue-600 hover:text-blue-800 font-medium py-2 px-3 border border-blue-600 rounded-md hover:bg-blue-50"
                        >
                            Edytuj
                        </button>
                        <button
                            @click="deleteZone(zone)"
                            class="flex-1 text-sm text-red-600 hover:text-red-800 font-medium py-2 px-3 border border-red-600 rounded-md hover:bg-red-50"
                        >
                            Usuń
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="zones.length === 0" class="col-span-full bg-white shadow rounded-lg p-12 text-center">
                    <i class="fa-solid fa-map text-6xl mb-4 block text-green-600"></i>
                    <p class="text-gray-500 mb-4">Nie masz jeszcze żadnych stref dostawy</p>
                    <button
                        v-if="googleMapsConfigured"
                        @click="openModal()"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                    >
                        Dodaj Pierwszą Strefę
                    </button>
                </div>
            </div>
        </div>

        <!-- Zone Modal with Map -->
        <div
            v-if="showModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            @click.self="closeModal"
        >
            <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white mb-10">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingZone ? 'Edytuj Strefę Dostawy' : 'Nowa Strefa Dostawy' }}
                </h3>

                <form @submit.prevent="saveZone">
                    <div class="grid grid-cols-2 gap-6">
                        <!-- Form Fields -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nazwa strefy *</label>
                                <input
                                    v-model="form.name"
                                    name="name"
                                    type="text"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="np. Centrum"
                                >
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Opłata za dostawę (PLN) *</label>
                                <input
                                    v-model.number="form.delivery_fee"
                                    name="delivery_fee"
                                    type="number"
                                    step="0.01"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                <p v-if="form.errors.delivery_fee" class="mt-1 text-sm text-red-600">{{ form.errors.delivery_fee }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Min. wartość zamówienia (PLN)</label>
                                <input
                                    v-model.number="form.min_order_value"
                                    type="number"
                                    step="0.01"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Opcjonalne"
                                >
                                <p v-if="form.errors.min_order_value" class="mt-1 text-sm text-red-600">{{ form.errors.min_order_value }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Szacowany czas dostawy (minuty)</label>
                                <input
                                    v-model.number="form.estimated_time"
                                    type="number"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="np. 30"
                                >
                                <p v-if="form.errors.estimated_time" class="mt-1 text-sm text-red-600">{{ form.errors.estimated_time }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Kolor strefy</label>
                                <input
                                    v-model="form.color"
                                    type="color"
                                    class="mt-1 block w-full h-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                <p v-if="form.errors.color" class="mt-1 text-sm text-red-600">{{ form.errors.color }}</p>
                            </div>

                            <div v-if="editingZone" class="flex items-center">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    id="zone-active"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                <label for="zone-active" class="ml-2 block text-sm text-gray-900">
                                    Strefa aktywna
                                </label>
                            </div>
                        </div>

                        <!-- Map -->
                        <div>
                            <!-- Zone mode selector -->
                            <div class="flex gap-2 mb-3">
                                <button
                                    type="button"
                                    @click="zoneMode = 'polygon'"
                                    :class="zoneMode === 'polygon' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="flex-1 py-2 px-3 text-sm font-medium rounded-md border transition-colors"
                                >
                                    <i class="fa-solid fa-draw-polygon mr-1 text-green-600"></i> Ręcznie
                                </button>
                                <button
                                    type="button"
                                    @click="zoneMode = 'radius'"
                                    :class="zoneMode === 'radius' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="flex-1 py-2 px-3 text-sm font-medium rounded-md border transition-colors"
                                >
                                    <i class="fa-solid fa-circle-dot mr-1"></i> Promień
                                </button>
                                <button
                                    type="button"
                                    @click="zoneMode = 'city'"
                                    :class="zoneMode === 'city' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="flex-1 py-2 px-3 text-sm font-medium rounded-md border transition-colors"
                                >
                                    <i class="fa-solid fa-city mr-1"></i> Całe miasto
                                </button>
                            </div>

                            <!-- City controls -->
                            <div v-if="zoneMode === 'city'" class="flex gap-2 mb-2 items-center">
                                <input
                                    id="city-search"
                                    v-model="cityName"
                                    type="text"
                                    placeholder="np. Warszawa"
                                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    autocomplete="off"
                                />
                                <button
                                    type="button"
                                    @click="fetchCityBoundary"
                                    :disabled="!cityName || cityGenerating"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md disabled:opacity-50 transition-colors whitespace-nowrap"
                                >
                                    <i v-if="cityGenerating" class="fa-solid fa-spinner fa-spin mr-1"></i>
                                    {{ cityGenerating ? 'Pobieranie...' : 'Pobierz granice' }}
                                </button>
                            </div>

                            <!-- Radius controls -->
                            <div v-if="zoneMode === 'radius'" class="space-y-2 mb-2">
                                <div class="flex gap-2 items-center">
                                    <input
                                        id="radius-city-search"
                                        v-model="radiusCity"
                                        type="text"
                                        placeholder="Miasto lub adres centrum strefy"
                                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                        autocomplete="off"
                                    />
                                </div>
                                <div class="flex gap-2 items-center">
                                    <input
                                        v-model.number="radiusKm"
                                        type="number"
                                        min="0.1"
                                        max="100"
                                        step="0.1"
                                        placeholder="np. 3"
                                        class="w-28 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    />
                                    <span class="text-sm text-gray-600">km od centrum</span>
                                    <button
                                        type="button"
                                        @click="generateRadius"
                                        :disabled="!radiusKm || radiusGenerating"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md disabled:opacity-50 transition-colors"
                                    >
                                        <i v-if="radiusGenerating" class="fa-solid fa-spinner fa-spin mr-1"></i>
                                        {{ radiusGenerating ? 'Generowanie...' : 'Generuj strefę' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Address search (polygon mode) -->
                            <div v-if="zoneMode === 'polygon'" class="flex gap-2 mb-2">
                                <input
                                    id="map-search"
                                    type="text"
                                    placeholder="Wyszukaj adres lub miejsce..."
                                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                />
                                <button
                                    type="button"
                                    @click="searchAddress"
                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm font-medium text-gray-700 border border-gray-300"
                                >
                                    <i class="fa-solid fa-magnifying-glass text-gray-500"></i>
                                </button>
                            </div>

                            <div
                                id="map"
                                class="w-full h-96 rounded-lg border border-gray-300"
                            ></div>
                            <p class="mt-2 text-xs text-gray-500">
                                <template v-if="zoneMode === 'polygon'">Kliknij na mapę, aby zaznaczyć punkty wielokąta. Minimum 3 punkty.</template>
                                <template v-else-if="zoneMode === 'radius'">Podaj promień i kliknij „Generuj strefę" — okrąg zostanie narysowany automatycznie.</template>
                                <template v-else>Wpisz nazwę miasta i kliknij „Pobierz granice" — granice administracyjne zostaną pobrane z OpenStreetMap.</template>
                            </p>
                            <p v-if="radiusError" class="mt-1 text-sm text-red-600">{{ radiusError }}</p>
                            <p v-if="form.errors.polygon" class="mt-1 text-sm text-red-600">{{ form.errors.polygon }}</p>
                            <div v-if="form.polygon.length > 0" class="mt-2">
                                <button
                                    type="button"
                                    @click="clearPolygon"
                                    class="text-sm text-red-600 hover:text-red-800 font-medium"
                                >
                                    Wyczyść strefę ({{ form.polygon.length }} punktów)
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                        >
                            Anuluj
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing || form.polygon.length < 3"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Zapisywanie...' : 'Zapisz strefę' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ManagerLayout>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
    zones: Array,
    googleMapsConfigured: Boolean,
    restaurantAddress: String,
})

const showModal = ref(false)
const editingZone = ref(null)
const zoneMode = ref('polygon')
const radiusKm = ref(null)
const radiusCity = ref('')
const radiusGenerating = ref(false)
const radiusError = ref('')
const cityName = ref('')
const cityGenerating = ref(false)
let map = null
let polygon = null
let markers = []
let autocomplete = null
let cityAutocomplete = null
let radiusCityAutocomplete = null
let geocoder = null

const form = useForm({
    name: '',
    delivery_fee: 0,
    min_order_value: null,
    estimated_time: null,
    color: '#3B82F6',
    is_active: true,
    polygon: [],
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN',
    }).format(price)
}

const openModal = (zone = null) => {
    if (zone) {
        editingZone.value = zone
        form.name = zone.name
        form.delivery_fee = zone.delivery_fee
        form.min_order_value = zone.min_order_value
        form.estimated_time = zone.estimated_time
        form.color = zone.color || '#3B82F6'
        form.is_active = zone.is_active
        form.polygon = JSON.parse(JSON.stringify(zone.polygon))
    } else {
        editingZone.value = null
        form.reset()
        form.clearErrors()
        form.color = '#3B82F6'
    }
    // Pre-fill city from restaurant address
    if (props.restaurantAddress) {
        const parts = props.restaurantAddress.trim().split(',')
        const city = parts[parts.length - 1]?.trim() || parts[0]?.trim() || ''
        cityName.value = city
        radiusCity.value = city
    }

    showModal.value = true

    // Initialize map after modal is shown
    setTimeout(() => {
        initMap()
    }, 100)
}

const closeModal = () => {
    showModal.value = false
    editingZone.value = null
    zoneMode.value = 'polygon'
    radiusKm.value = null
    radiusCity.value = ''
    radiusError.value = ''
    cityName.value = ''
    cityAutocomplete = null
    radiusCityAutocomplete = null
    form.reset()
    form.clearErrors()

    if (map) {
        map = null
        polygon = null
        markers = []
    }
}

const initMap = () => {
    if (!window.google || !props.googleMapsConfigured) return

    const mapElement = document.getElementById('map')
    if (!mapElement) return

    const defaultCenter = { lat: 52.229676, lng: 21.012229 } // Warsaw fallback

    map = new google.maps.Map(mapElement, {
        zoom: 13,
        center: defaultCenter,
        mapTypeControl: false,
        streetViewControl: false,
    })

    geocoder = new google.maps.Geocoder()

    // Center on restaurant address if available
    if (props.restaurantAddress) {
        geocoder.geocode({ address: props.restaurantAddress }, (results, status) => {
            if (status === 'OK' && results[0]) {
                map.setCenter(results[0].geometry.location)
            }
        })
    }

    // If editing, draw existing polygon and fit bounds
    if (form.polygon.length > 0) {
        drawPolygon()
        const bounds = new google.maps.LatLngBounds()
        form.polygon.forEach(([lat, lng]) => bounds.extend({ lat, lng }))
        map.fitBounds(bounds)
    }

    // Setup Places Autocomplete based on current mode
    if (zoneMode.value === 'polygon') {
        const searchInput = document.getElementById('map-search')
        if (searchInput && window.google.maps.places) {
            autocomplete = new google.maps.places.Autocomplete(searchInput, {
                fields: ['geometry', 'name'],
                types: ['geocode', 'establishment'],
            })
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace()
                if (place.geometry?.location) {
                    map.setCenter(place.geometry.location)
                    map.setZoom(14)
                }
            })
        }
    } else if (zoneMode.value === 'city') {
        setupCityAutocomplete()
    } else if (zoneMode.value === 'radius') {
        setupRadiusCityAutocomplete()
    }

    // Add click listener to add points (only in polygon mode)
    map.addListener('click', (event) => {
        if (zoneMode.value === 'polygon') {
            addPoint(event.latLng)
        }
    })
}

const searchAddress = () => {
    const searchInput = document.getElementById('map-search')
    if (!searchInput?.value || !geocoder) return
    geocoder.geocode({ address: searchInput.value }, (results, status) => {
        if (status === 'OK' && results[0]) {
            map.setCenter(results[0].geometry.location)
            map.setZoom(14)
        }
    })
}

const setupCityAutocomplete = () => {
    if (!window.google?.maps?.places) return
    const input = document.getElementById('city-search')
    if (!input || cityAutocomplete) return
    cityAutocomplete = new google.maps.places.Autocomplete(input, {
        fields: ['name', 'geometry'],
        types: ['(cities)'],
    })
    cityAutocomplete.addListener('place_changed', () => {
        const place = cityAutocomplete.getPlace()
        if (place.name) cityName.value = place.name
        if (place.geometry?.location && map) {
            map.setCenter(place.geometry.location)
            map.setZoom(12)
        }
    })
}

const setupRadiusCityAutocomplete = () => {
    if (!window.google?.maps?.places) return
    const input = document.getElementById('radius-city-search')
    if (!input || radiusCityAutocomplete) return
    radiusCityAutocomplete = new google.maps.places.Autocomplete(input, {
        fields: ['name', 'geometry'],
        types: ['(cities)', 'geocode'],
    })
    radiusCityAutocomplete.addListener('place_changed', () => {
        const place = radiusCityAutocomplete.getPlace()
        if (place.name) radiusCity.value = place.name
        if (place.geometry?.location && map) {
            map.setCenter(place.geometry.location)
            map.setZoom(12)
        }
    })
}

watch(zoneMode, async (mode) => {
    cityAutocomplete = null
    radiusCityAutocomplete = null
    await nextTick()
    if (!map) return
    if (mode === 'city') {
        setupCityAutocomplete()
    } else if (mode === 'radius') {
        setupRadiusCityAutocomplete()
    } else if (mode === 'polygon') {
        autocomplete = null
        const searchInput = document.getElementById('map-search')
        if (searchInput && window.google?.maps?.places) {
            autocomplete = new google.maps.places.Autocomplete(searchInput, {
                fields: ['geometry', 'name'],
                types: ['geocode', 'establishment'],
            })
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace()
                if (place.geometry?.location) {
                    map.setCenter(place.geometry.location)
                    map.setZoom(14)
                }
            })
        }
    }
})

const generateRadius = () => {
    if (!radiusKm.value || !geocoder) return
    radiusError.value = ''
    radiusGenerating.value = true

    const address = radiusCity.value || props.restaurantAddress || 'Warszawa'
    geocoder.geocode({ address }, (results, status) => {
        radiusGenerating.value = false
        if (status !== 'OK' || !results[0]) {
            radiusError.value = 'Nie udało się ustalić lokalizacji. Sprawdź wpisane miasto lub ustaw adres w Ustawieniach.'
            return
        }

        const center = results[0].geometry.location
        const lat = center.lat()
        const lng = center.lng()
        const km = radiusKm.value
        const points = 48 // circle approximation with 48 points

        clearPolygon()

        const newPolygon = []
        for (let i = 0; i < points; i++) {
            const angle = (2 * Math.PI * i) / points
            const dLat = (km / 111.32) * Math.cos(angle)
            const dLng = (km / (111.32 * Math.cos((lat * Math.PI) / 180))) * Math.sin(angle)
            newPolygon.push([lat + dLat, lng + dLng])
        }

        form.polygon = newPolygon

        // Draw on map with markers at every 6th point to avoid clutter
        const bounds = new google.maps.LatLngBounds()
        newPolygon.forEach(([pLat, pLng], idx) => {
            const pos = { lat: pLat, lng: pLng }
            bounds.extend(pos)
            if (idx % 6 === 0) {
                const marker = new google.maps.Marker({ position: pos, map })
                markers.push(marker)
            }
        })

        drawPolygon()
        map.fitBounds(bounds)
        map.setCenter(center)
    })
}

const fetchCityBoundary = async () => {
    if (!cityName.value) return
    radiusError.value = ''
    cityGenerating.value = true

    try {
        const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(cityName.value)}&polygon_geojson=1&format=json&limit=3&featuretype=city&accept-language=pl`
        const res = await fetch(url, { headers: { 'Accept-Language': 'pl' } })
        const data = await res.json()

        // Find the best result: prefer place_rank 16 (city) or 12 (county), filter out too-small results
        const result = data.find(r => r.geojson && (r.place_rank <= 16 || r.type === 'city' || r.type === 'administrative')) || data.find(r => r.geojson)

        if (!result?.geojson) {
            radiusError.value = `Nie znaleziono granic dla "${cityName.value}". Spróbuj innej nazwy.`
            return
        }

        // Extract coordinates from GeoJSON (Polygon or MultiPolygon)
        let coords = []
        if (result.geojson.type === 'Polygon') {
            coords = result.geojson.coordinates[0]
        } else if (result.geojson.type === 'MultiPolygon') {
            // Take the largest polygon
            const largest = result.geojson.coordinates.reduce((a, b) => a[0].length >= b[0].length ? a : b)
            coords = largest[0]
        }

        if (coords.length < 3) {
            radiusError.value = 'Pobrane dane nie zawierają wystarczającej liczby punktów.'
            return
        }

        // Simplify: max 200 points to avoid backend issues
        const step = Math.max(1, Math.floor(coords.length / 200))
        const simplified = coords.filter((_, i) => i % step === 0)

        clearPolygon()
        form.polygon = simplified.map(([lng, lat]) => [lat, lng]) // GeoJSON is [lng, lat]

        // Draw bounds
        const bounds = new google.maps.LatLngBounds()
        form.polygon.forEach(([lat, lng]) => bounds.extend({ lat, lng }))
        drawPolygon()
        map.fitBounds(bounds)
    } catch (e) {
        radiusError.value = 'Błąd podczas pobierania granic miasta. Sprawdź połączenie z internetem.'
    } finally {
        cityGenerating.value = false
    }
}

const addPoint = (latLng) => {
    // Add to form polygon
    form.polygon.push([latLng.lat(), latLng.lng()])

    // Add marker
    const marker = new google.maps.Marker({
        position: latLng,
        map: map,
        label: String(form.polygon.length),
    })
    markers.push(marker)

    // Redraw polygon
    drawPolygon()
}

const drawPolygon = () => {
    // Remove old polygon
    if (polygon) {
        polygon.setMap(null)
    }

    if (form.polygon.length < 2) return

    // Create new polygon
    const paths = form.polygon.map(([lat, lng]) => ({ lat, lng }))

    polygon = new google.maps.Polygon({
        paths: paths,
        strokeColor: form.color,
        strokeOpacity: 0.8,
        strokeWeight: 2,
        fillColor: form.color,
        fillOpacity: 0.35,
        map: map,
    })
}

const clearPolygon = () => {
    form.polygon = []

    // Remove markers
    markers.forEach(marker => marker.setMap(null))
    markers = []

    // Remove polygon
    if (polygon) {
        polygon.setMap(null)
        polygon = null
    }
}

// Watch color changes to update polygon
watch(() => form.color, () => {
    if (polygon) {
        drawPolygon()
    }
})

const saveZone = () => {
    if (editingZone.value) {
        form.put(route('tenant.manager.delivery-zones.update', editingZone.value.id), {
            onSuccess: () => closeModal()
        })
    } else {
        form.post(route('tenant.manager.delivery-zones.store'), {
            onSuccess: () => closeModal()
        })
    }
}

const deleteZone = (zone) => {
    if (confirm(`Czy na pewno chcesz usunąć strefę "${zone.name}"?`)) {
        router.delete(route('tenant.manager.delivery-zones.destroy', zone.id))
    }
}

// Load Google Maps API if configured
onMounted(() => {
    if (props.googleMapsConfigured && !window.google) {
        const script = document.createElement('script')
        script.src = `https://maps.googleapis.com/maps/api/js?key=${import.meta.env.VITE_GOOGLE_MAPS_API_KEY}&libraries=geometry,drawing,places`
        script.async = true
        script.defer = true
        document.head.appendChild(script)
    }
})
</script>
