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
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Data Wilayah Indonesia</h2>
                    <p class="text-xl text-gray-600 mb-4">Informasi Data Provinsi Pemilu 2024</p>
                    <div class="w-24 h-1 bg-gradient-to-r from-blue-500 to-indigo-600 mx-auto rounded-full"></div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <div
                    v-for="(stat, index) in statisticsCards"
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

            <!-- Provinces Table -->
            <div
                class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden"
                v-motion
                :initial="{ opacity: 0, y: 50 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 400 } }"
            >
                <div class="px-8 py-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">Tabel Data Provinsi</h3>
                    <p class="text-gray-600 mt-1">Daftar lengkap data provinsi di Indonesia</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nama Provinsi
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jumlah Dapil
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jumlah Kabupaten/Kota
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jumlah Kecamatan
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jumlah Kelurahan
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jumlah TPS
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total DPT
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="(province, index) in provinces"
                                :key="province.id"
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
                                    <div class="text-sm font-medium">
                                        <a :href="`/dpr/provinsi/${province.id}/kabupaten`"
                                           class="text-blue-600 hover:text-blue-800 hover:underline cursor-pointer transition-colors duration-200">
                                            {{ province.nama_provinsi }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <a :href="`/dpr/provinsi/${province.id}/dapil`"
                                       class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 hover:text-blue-900 cursor-pointer transition-all duration-200 transform hover:scale-105">
                                        {{ formatNumber(province.jumlah_dapil) }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ formatNumber(province.jumlah_kabkota) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                        {{ formatNumber(province.jumlah_kecamatan) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-800">
                                        {{ formatNumber(province.jumlah_kelurahan) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        {{ formatNumber(province.jumlah_tps) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ formatNumber(province.jumlah_dpt) }}
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
                            Menampilkan <span class="font-medium">{{ provinces.length }}</span> provinsi dari total
                            <span class="font-medium">{{ provinces.length }}</span> data
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

// Icons (using simple SVG paths for now)
const MapIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12 1.586l-4 4v12.828l4-4V1.586zM3.707 3.293A1 1 0 002 4v10a1 1 0 00.293.707L6 18.414V5.586L3.707 3.293zM17.707 5.293L14 1.586v12.828l2.293 2.293A1 1 0 0018 16V6a1 1 0 00-.293-.707z" clip-rule="evenodd"></path></svg>`
}

const BuildingIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"></path><path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>`
}

const OfficeIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"></path><path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"></path></svg>`
}

const UsersIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path></svg>`
}

const props = defineProps({
    provinces: Array,
    statistics: Object,
    title: String
})

const statisticsCards = computed(() => {
    // Use real statistics from database instead of calculating from displayed provinces
    const stats = props.statistics || {
        total_provinces: props.provinces.length,
        total_dapil: 0,
        total_kabkota: 0,
        total_kecamatan: 0,
        total_kelurahan: 0,
        total_tps: 0,
        total_dpt: 0
    }

    return [
        {
            label: 'Total Provinsi',
            value: stats.total_provinces.toLocaleString('id-ID'),
            icon: MapIcon,
            colorClass: 'bg-blue-500'
        },
        {
            label: 'Total Kab/Kota',
            value: stats.total_kabkota.toLocaleString('id-ID'),
            icon: BuildingIcon,
            colorClass: 'bg-green-500'
        },
        {
            label: 'Total Kecamatan',
            value: stats.total_kecamatan.toLocaleString('id-ID'),
            icon: OfficeIcon,
            colorClass: 'bg-indigo-500'
        },
        {
            label: 'Total Kelurahan',
            value: stats.total_kelurahan.toLocaleString('id-ID'),
            icon: UsersIcon,
            colorClass: 'bg-pink-500'
        },
        {
            label: 'Total TPS',
            value: stats.total_tps.toLocaleString('id-ID'),
            icon: UsersIcon,
            colorClass: 'bg-purple-500'
        }
    ]
})

const formatNumber = (number) => {
    return parseInt(number).toLocaleString('id-ID')
}
</script>