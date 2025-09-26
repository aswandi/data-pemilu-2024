<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <!-- Header -->
        <header class="bg-white shadow-lg border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <h1 class="text-2xl font-bold text-gray-900">DATABASE PEMILU 2024</h1>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <span class="text-gray-600 text-sm">Sistem Informasi Data Pemilihan Umum Indonesia</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white mt-16">
            <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="text-gray-400">&copy; 2024 Database Pemilu 2024. Sistem Informasi Data Pemilihan Umum Indonesia.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
    title: {
        type: String,
        default: 'DATABASE PEMILU 2024'
    }
})
</script>