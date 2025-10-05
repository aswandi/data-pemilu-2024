<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <!-- Header -->
        <header class="bg-white shadow-lg border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <Link href="/" class="text-2xl font-bold text-gray-900 hover:text-blue-600 transition-colors duration-200">
                                DATABASE PEMILU 2024
                            </Link>
                        </div>
                    </div>
                    <div class="flex items-center space-x-6">
                        <nav class="hidden md:flex items-center space-x-4">
                            <Link href="/"
                                  class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                Beranda
                            </Link>
                        </nav>
                        <div class="hidden md:block">
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
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-center md:text-left">
                        <p class="text-gray-400">&copy; 2024 Database Pemilu 2024. Sistem Informasi Data Pemilihan Umum Indonesia.</p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <nav class="flex space-x-6">
                            <Link href="/changelog"
                                  class="text-gray-400 hover:text-white transition-colors duration-200 text-sm">
                                Changelog
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
    title: {
        type: String,
        default: 'DATABASE PEMILU 2024'
    }
})
</script>