<template>
    <AppLayout :title="title">
        <Head :title="title" />

        <div class="space-y-8">
            <!-- Page Header -->
            <div
                class="bg-white rounded-xl shadow-lg p-8 border border-gray-200"
                v-motion
                :initial="{ opacity: 0, y: 50 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 600 } }"
            >
                <div class="text-center">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Data Kelurahan/Desa</h2>
                    <p class="text-xl text-gray-600 mb-2">{{ kecamatanName }}</p>
                    <p class="text-lg text-gray-500 mb-2">{{ kabupatenName }}</p>
                    <p class="text-md text-gray-400 mb-4">{{ provinceName }}</p>
                    <div class="w-24 h-1 bg-gradient-to-r from-blue-500 to-indigo-600 mx-auto rounded-full"></div>
                </div>

                <!-- Navigation Buttons -->
                <div class="mt-6 flex justify-center space-x-3 flex-wrap">
                    <a href="/dpr/provinsi"
                       class="inline-flex items-center px-3 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors duration-200 text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Provinsi
                    </a>
                    <a :href="`/dpr/provinsi/${provinceId}/kabupaten`"
                       class="inline-flex items-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-lg transition-colors duration-200 text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Kabupaten
                    </a>
                    <a :href="`/dpr/kabupaten/${kabupatenId}/kecamatan`"
                       class="inline-flex items-center px-3 py-2 bg-purple-500 hover:bg-purple-600 text-white font-medium rounded-lg transition-colors duration-200 text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Kecamatan
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    v-for="(stat, index) in statistics"
                    :key="index"
                    class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 hover:shadow-xl transition-all duration-300"
                    v-motion
                    :initial="{ opacity: 0, scale: 0.9 }"
                    :enter="{
                        opacity: 1,
                        scale: 1,
                        transition: {
                            duration: 500,
                            delay: index * 100
                        }
                    }"
                >
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div :class="stat.colorClass" class="rounded-lg p-3">
                                <component :is="stat.icon" class="h-6 w-6 text-white" />
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">{{ stat.label }}</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stat.value }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kelurahan Table -->
            <div
                class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden"
                v-motion
                :initial="{ opacity: 0, y: 50 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 400 } }"
            >
                <div class="px-8 py-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">Tabel Data Kelurahan/Desa</h3>
                    <p class="text-gray-600 mt-1">Daftar kelurahan/desa di {{ kecamatanName }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nama Kelurahan/Desa
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jumlah TPS
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jumlah DPT
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="(kel, index) in kelurahan"
                                :key="kel.id"
                                class="hover:bg-gray-50 transition-colors duration-200"
                                v-motion
                                :initial="{ opacity: 0, x: -50 }"
                                :enter="{
                                    opacity: 1,
                                    x: 0,
                                    transition: {
                                        duration: 400,
                                        delay: (index * 50) + 600
                                    }
                                }"
                            >
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold">
                                        {{ index + 1 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ kel.nama_kelurahan }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        {{ formatNumber(kel.jumlah_tps) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ formatNumber(kel.jumlah_dpt) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table Summary -->
                <div class="bg-gray-50 px-8 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-700">
                            Menampilkan <span class="font-medium">{{ kelurahan.length }}</span> kelurahan/desa dari total
                            <span class="font-medium">{{ kelurahan.length }}</span> data
                        </p>
                        <p class="text-sm text-gray-500">
                            Data diperbarui: {{ new Date().toLocaleDateString('id-ID') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

// Icons (using simple SVG paths)
const MapIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12 1.586l-4 4v12.828l4-4V1.586zM3.707 3.293A1 1 0 002 4v10a1 1 0 00.293.707L6 18.414V5.586L3.707 3.293zM17.707 5.293L14 1.586v12.828l2.293 2.293A1 1 0 0018 16V6a1 1 0 00-.293-.707z" clip-rule="evenodd"></path></svg>`
}

const UsersIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path></svg>`
}

const ChartIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path></svg>`
}

const props = defineProps({
    kelurahan: Array,
    kecamatanName: String,
    kabupatenName: String,
    provinceName: String,
    provinceId: [String, Number],
    kabupatenId: [String, Number],
    kecamatanId: [String, Number],
    title: String
})

const statistics = computed(() => {
    const totalTPS = props.kelurahan.reduce((sum, k) => sum + parseInt(k.jumlah_tps || 0), 0)
    const totalDPT = props.kelurahan.reduce((sum, k) => sum + parseInt(k.jumlah_dpt || 0), 0)

    return [
        {
            label: 'Total Kelurahan/Desa',
            value: props.kelurahan.length,
            icon: MapIcon,
            colorClass: 'bg-blue-500'
        },
        {
            label: 'Total TPS',
            value: totalTPS.toLocaleString('id-ID'),
            icon: UsersIcon,
            colorClass: 'bg-purple-500'
        },
        {
            label: 'Total DPT',
            value: totalDPT.toLocaleString('id-ID'),
            icon: ChartIcon,
            colorClass: 'bg-green-500'
        }
    ]
})

const formatNumber = (number) => {
    return parseInt(number).toLocaleString('id-ID')
}
</script>