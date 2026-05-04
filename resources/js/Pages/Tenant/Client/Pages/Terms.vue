<template>
    <Head title="Regulamin" />

    <ClientLayout>
        <div class="max-w-3xl mx-auto px-4 py-12">
            <div class="bg-white rounded-lg shadow-md p-8 legal-content" v-html="content"></div>
        </div>
    </ClientLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'

defineProps({
    content: { type: String, default: '' },
})
</script>

<style>
.legal-content h1 { font-size: 1.6rem; font-weight: 700; margin-bottom: 0.4em; color: #111827; }
.legal-content h2 { font-size: 1.15rem; font-weight: 700; margin-top: 2em; margin-bottom: 0.5em; color: #111827; }
.legal-content h3 { font-size: 1rem; font-weight: 600; margin-top: 1.4em; margin-bottom: 0.4em; color: #1f2937; }
.legal-content p  { margin-bottom: 0.85em; line-height: 1.7; color: #374151; }
.legal-content ol, .legal-content ul { padding-left: 1.4em; margin-bottom: 0.85em; }
.legal-content ol { list-style-type: decimal; }
.legal-content ul { list-style-type: disc; }
.legal-content li { margin-bottom: 0.4em; line-height: 1.7; color: #374151; }
.legal-content li ul, .legal-content li ol { margin-top: 0.4em; margin-bottom: 0.2em; }
.legal-content hr { border: none; border-top: 1px solid #e5e7eb; margin: 1.5em 0; }
.legal-content a { color: #2563eb; text-decoration: underline; }
.legal-content strong { font-weight: 600; color: #111827; }
.legal-content table { width: 100%; border-collapse: collapse; margin-bottom: 1em; font-size: 0.9em; }
.legal-content th, .legal-content td { padding: 8px 10px; border: 1px solid #e5e7eb; vertical-align: top; }
.legal-content thead tr { background: #f9fafb; }
</style>
