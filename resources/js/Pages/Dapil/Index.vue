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
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Data Dapil {{ provinceName }}</h2>
                    <p class="text-xl text-gray-600 mb-4">Informasi Daerah Pemilihan {{ provinceName }}</p>
                    <div class="w-24 h-1 bg-gradient-to-r from-blue-500 to-indigo-600 mx-auto rounded-full"></div>
                </div>
            </div>

            <!-- Navigation Breadcrumb -->
            <div
                class="bg-white rounded-lg shadow-sm p-4 border border-gray-200"
                v-motion
                :initial="{ opacity: 0, x: -50 }"
                :enter="{ opacity: 1, x: 0, transition: { duration: 500, delay: 200 } }"
            >
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a :href="'/dpr/provinsi'" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                                <HomeIcon class="w-4 h-4 mr-2" />
                                Data Provinsi
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">{{ provinceName }}</span>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                                <span class="ml-1 text-sm font-medium text-blue-600 md:ml-2">Data Dapil</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>

            <!-- Statistics Summary -->
            <div
                class="bg-white rounded-xl shadow-lg p-6 border border-gray-200"
                v-motion
                :initial="{ opacity: 0, scale: 0.95 }"
                :enter="{ opacity: 1, scale: 1, transition: { duration: 600, delay: 300 } }"
            >
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 mb-4">
                            <BuildingIcon class="w-6 h-6 text-blue-600" />
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">{{ dapils.length }}</h3>
                        <p class="text-gray-600">Total Dapil</p>
                    </div>
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 mb-4">
                            <OfficeIcon class="w-6 h-6 text-green-600" />
                        </div>
                        <a :href="`/dpr/provinsi/${provinceId}/kabupaten`"
                           class="block hover:bg-gray-50 rounded-lg p-2 transition-colors duration-200 cursor-pointer">
                            <h3 class="text-2xl font-bold text-gray-900 hover:text-green-600">{{ formatNumber(jumlahKabkota) }}</h3>
                            <p class="text-gray-600">Jumlah Kab/Kota</p>
                        </a>
                    </div>
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-purple-100 mb-4">
                            <MapIcon class="w-6 h-6 text-purple-600" />
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">{{ provinceName }}</h3>
                        <p class="text-gray-600">Provinsi</p>
                    </div>
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-orange-100 mb-4">
                            <DocumentIcon class="w-6 h-6 text-orange-600" />
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">2024</h3>
                        <p class="text-gray-600">Pemilu</p>
                    </div>
                </div>
            </div>

            <!-- Dapil Table -->
            <div
                class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden"
                v-motion
                :initial="{ opacity: 0, y: 50 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 400 } }"
            >
                <div class="px-8 py-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">Tabel Data Dapil</h3>
                    <p class="text-gray-600 mt-1">Daftar lengkap daerah pemilihan di {{ provinceName }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nama Dapil
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kode Dapil
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jumlah Kab/Kota
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="(dapil, index) in dapils"
                                :key="dapil.id"
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
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ dapil.nama_dapil }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ dapil.dapil_kode || '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ formatNumber(dapil.jumlah_kabkota || 0) }}
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
                            Menampilkan <span class="font-medium">{{ dapils.length }}</span> dapil dari
                            <span class="font-medium">{{ provinceName }}</span>
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
import AppLayout from '@/Layouts/AppLayout.vue'

// Icons (using simple SVG paths)
const HomeIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>`
}

const ChevronRightIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>`
}

const MapIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12 1.586l-4 4v12.828l4-4V1.586zM3.707 3.293A1 1 0 002 4v10a1 1 0 00.293.707L6 18.414V5.586L3.707 3.293zM17.707 5.293L14 1.586v12.828l2.293 2.293A1 1 0 0018 16V6a1 1 0 00-.293-.707z" clip-rule="evenodd"></path></svg>`
}

const BuildingIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"></path><path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>`
}

const OfficeIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"></path><path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"></path></svg>`
}

const DocumentIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>`
}

const props = defineProps({
    dapils: Array,
    provinceName: String,
    jumlahKabkota: Number,
    provinceId: Number,
    title: String
})

const formatNumber = (number) => {
    return parseInt(number).toLocaleString('id-ID')
}
</script>