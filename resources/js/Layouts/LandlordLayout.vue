<template>
    <Head :title="title ? `${title} — ${appName} Admin` : `${appName} Admin`" />

    <div class="min-h-screen bg-gray-100">
        <FlashMessage />

        <!-- Contact notification toast -->
        <div v-if="contactAlert"
            class="fixed top-4 right-4 z-50 bg-green-600 text-white px-5 py-3 rounded-xl shadow-lg flex items-center gap-3 max-w-sm">
            <i class="fa-solid fa-envelope text-lg"></i>
            <span class="text-sm font-medium">{{ contactAlert }}</span>
            <button @click="contactAlert = null" class="ml-auto text-green-200 hover:text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Support notification toast -->
        <div v-if="supportAlert"
            class="fixed top-4 right-4 z-50 bg-indigo-600 text-white px-5 py-3 rounded-xl shadow-lg flex items-center gap-3 max-w-sm">
            <i class="fa-solid fa-headset text-lg"></i>
            <span class="text-sm font-medium">{{ supportAlert }}</span>
            <button @click="supportAlert = null" class="ml-auto text-indigo-200 hover:text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-full px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex-shrink-0 flex items-center">
                        <Link :href="route('landlord.dashboard')" class="text-xl font-bold text-indigo-600">
                            <img src="/images/logo.png" alt="Logo" class="h-7 w-auto inline-block mr-1 align-middle" /> {{ appName }} Admin
                        </Link>
                    </div>

                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">{{ auth?.name }}</span>
                        <Link
                            :href="route('landlord.logout')"
                            method="post"
                            as="button"
                            class="text-red-600 hover:text-red-700 text-sm font-medium"
                        >
                            Wyloguj
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex">
            <!-- Sidebar Navigation -->
            <aside class="w-64 bg-white shadow-sm min-h-screen">
                <nav class="mt-5 px-4">
                    <div class="space-y-1">
                        <Link
                            :href="route('landlord.dashboard')"
                            :class="[
                                isActive('dashboard')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-chart-line mr-3 w-5 text-center"></i>
                            Dashboard
                        </Link>

                        <Link
                            :href="route('landlord.tenants.index')"
                            :class="[
                                isActive('tenants')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-store mr-3 w-5 text-center"></i>
                            Restauracje
                        </Link>

                        <Link
                            :href="route('landlord.modifications.index')"
                            :class="[
                                isActive('modifications')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-screwdriver-wrench mr-3 w-5 text-center"></i>
                            Modyfikacje
                        </Link>

                        <Link
                            :href="route('landlord.support.index')"
                            :class="[
                                isActive('support')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-headset mr-3 w-5 text-center"></i>
                            Wsparcie
                            <span v-if="newSupportCount > 0"
                                class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full min-w-[20px] text-center">
                                {{ newSupportCount }}
                            </span>
                        </Link>

                        <Link
                            :href="route('landlord.contacts.index')"
                            :class="[
                                isActive('contacts')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-envelope mr-3 w-5 text-center"></i>
                            Kontakty
                            <span v-if="newContactCount > 0" class="ml-auto bg-red-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5 min-w-[20px] text-center">{{ newContactCount }}</span>
                        </Link>

                        <Link
                            :href="route('landlord.restaurant-search.index')"
                            :class="[
                                isActive('restaurant-search')
                                    ? 'bg-blue-50 border-blue-500 text-blue-700'
                                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-3 py-2 text-sm font-medium border-l-4 transition-colors'
                            ]"
                        >
                            <i class="fa-solid fa-magnifying-glass-location mr-3 w-5 text-center"></i>
                            Wyszukaj restauracje
                        </Link>
                    </div>

                    <div class="mt-6">
                        <button @click="playNotification" class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-400 hover:text-gray-600 border border-dashed border-gray-200 rounded-lg transition-colors">
                            <i class="fa-solid fa-volume-high w-4 text-center"></i>
                            Test dźwięku
                        </button>
                    </div>

                </nav>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 p-8">
                <h1 v-if="title" class="text-2xl font-bold text-gray-900 mb-6">{{ title }}</h1>
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import FlashMessage from '@/Components/FlashMessage.vue'

defineProps({
    title: String,
})

const page = usePage()
const auth = page.props.auth?.user
const appName = page.props.app_name ?? 'Roveto'
const supportAlert = ref(null)
const newSupportCount = ref(page.props.landlordUnreadSupportCount ?? parseInt(localStorage.getItem('landlord_support_count') || '0'))
const contactAlert = ref(null)
const newContactCount = ref(parseInt(localStorage.getItem('landlord_contact_count') || '0'))
let echoSupport = null
let echoContact = null

// Audio — Web Audio API beep (no file, no SW issues)
let audioCtx = null

const getAudioCtx = () => {
    if (!audioCtx) {
        audioCtx = new AudioContext()
    }
    return audioCtx
}

const playNotification = () => {
    try {
        const ctx = getAudioCtx()
        const oscillator = ctx.createOscillator()
        const gain = ctx.createGain()
        oscillator.connect(gain)
        gain.connect(ctx.destination)
        oscillator.type = 'sine'
        oscillator.frequency.setValueAtTime(880, ctx.currentTime)
        oscillator.frequency.setValueAtTime(660, ctx.currentTime + 0.1)
        gain.gain.setValueAtTime(0.4, ctx.currentTime)
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4)
        oscillator.start(ctx.currentTime)
        oscillator.stop(ctx.currentTime + 0.4)
    } catch {}
}


const updateTitle = () => {
    const total = newContactCount.value + newSupportCount.value
    const base = document.title.replace(/^\(\d+\)\s*/, '')
    document.title = total > 0 ? `(${total}) ${base}` : base
}

const isActive = (section) => {
    const url = page.url
    if (section === 'dashboard') return url === '/admin' || url === '/admin/' || url.includes('/admin/dashboard')
    return url.includes(`/admin/${section}`)
}

onMounted(() => {
    // Clear counts if already on the relevant page (e.g. direct URL or refresh)
    if (page.url.includes('/admin/contacts')) {
        newContactCount.value = 0
        localStorage.removeItem('landlord_contact_count')
    }
    if (page.url.includes('/admin/support')) {
        newSupportCount.value = 0
        localStorage.removeItem('landlord_support_count')
    }
    updateTitle()
    window.Pusher = Pusher
    if (!window.Echo) {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/admin/broadcasting/auth',
            auth: {
                headers: {
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
                },
            },
        })
    }

    echoContact = window.Echo.private('contact-admin')
        .listen('.contact.inquiry', (e) => {
            contactAlert.value = `Nowa wiadomość od: ${e.name}`
            setTimeout(() => { contactAlert.value = null }, 7000)
            playNotification()
            if (page.url.includes('/admin/contacts')) {
                router.reload()
            } else {
                newContactCount.value++
                localStorage.setItem('landlord_contact_count', newContactCount.value)
                updateTitle()
            }
        })

    echoSupport = window.Echo.private('support-admin')
        .listen('.support.message', (e) => {
            const label = e.author_type === 'tenant'
                ? `Nowe zgłoszenie: ${e.subject}`
                : `Odpowiedź klienta: ${e.subject}`
            supportAlert.value = label
            setTimeout(() => { supportAlert.value = null }, 7000)
            playNotification()
            if (page.url.includes('/admin/support')) {
                router.reload()
            } else {
                newSupportCount.value++
                localStorage.setItem('landlord_support_count', newSupportCount.value)
                updateTitle()
            }
        })
})

watch(() => page.props.landlordUnreadSupportCount, (val) => {
    if (val !== undefined) {
        newSupportCount.value = val
        updateTitle()
    }
})

watch(() => page.url, (url) => {
    if (url.includes('/admin/support')) {
        newSupportCount.value = 0
        localStorage.removeItem('landlord_support_count')
        updateTitle()
    }
    if (url.includes('/admin/contacts')) {
        newContactCount.value = 0
        localStorage.removeItem('landlord_contact_count')
        updateTitle()
    }
})

onUnmounted(() => {
    if (echoContact) window.Echo?.leave('contact-admin')
    if (echoSupport) window.Echo?.leave('support-admin')
})
</script>
