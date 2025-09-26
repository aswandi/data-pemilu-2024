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
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Data Suara Caleg DPR RI 2024</h2>
                    <p class="text-xl text-gray-600 mb-2">{{ kabupatenName }}</p>
                    <p class="text-lg text-gray-500 mb-4">{{ provinceName }}</p>
                    <div class="w-24 h-1 bg-gradient-to-r from-purple-500 to-indigo-600 mx-auto rounded-full"></div>
                </div>

                <!-- Back Button -->
                <div class="mt-6 text-center">
                    <a :href="`/dpr/provinsi/${provinceId}/kabupaten`"
                       class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Kembali ke Kabupaten
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

            <!-- Kecamatan Tables -->
            <div v-for="(kecamatan, kecIndex) in kecamatanData" :key="kecamatan.id" class="space-y-4">
                <div
                    class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden"
                    v-motion
                    :initial="{ opacity: 0, y: 50 }"
                    :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: kecIndex * 200 } }"
                >
                    <div class="px-8 py-6 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-indigo-50">
                        <h3 class="text-xl font-semibold text-gray-900">📍 Kecamatan {{ kecamatan.nama }}</h3>
                        <p class="text-gray-600 mt-1">Data suara caleg per partai dan kelurahan/desa</p>
                    </div>

                    <div class="overflow-x-auto">
                        <!-- Loop through each party -->
                        <div v-for="party in parties" :key="party.nomor_urut" class="border-b border-gray-100 last:border-b-0">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                                <h4 class="text-sm font-semibold text-gray-700">
                                    {{ party.nomor_urut }}. {{ party.partai_singkat || party.nama }}
                                </h4>
                            </div>

                            <table class="min-w-full">
                                <thead class="bg-blue-50">
                                    <tr>
                                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase text-center border-r border-gray-200">No</th>
                                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase text-left border-r border-gray-200">Partai</th>
                                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase text-center border-r border-gray-200">No. Urut</th>
                                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase text-left border-r border-gray-200">Nama Caleg</th>
                                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase text-center border-r border-gray-200">Total Suara</th>
                                        <th v-for="(kelurahan, kelIndex) in Object.keys(kecamatan.kelurahan)" :key="kelIndex"
                                            class="px-2 py-3 text-xs font-medium text-gray-500 uppercase text-center border-r border-gray-200 min-w-[80px]"
                                            style="writing-mode: vertical-rl; text-orientation: mixed;">
                                            {{ kecamatan.kelurahan[kelurahan].nama }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <!-- Row 1: Party votes (without caleg) -->
                                    <tr class="bg-yellow-50">
                                        <td class="px-4 py-3 text-sm font-medium text-center border-r border-gray-200">1</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 border-r border-gray-200">
                                            {{ party.partai_singkat || party.nama }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-center border-r border-gray-200">-</td>
                                        <td class="px-4 py-3 text-sm font-medium text-purple-600 border-r border-gray-200">
                                            Suara Partai (Tanpa Caleg)
                                        </td>
                                        <td class="px-4 py-3 text-sm font-bold text-center border-r border-gray-200">
                                            {{ formatNumber(kecamatan.partai_total[party.nomor_urut] || 0) }}
                                        </td>
                                        <td v-for="(kelurahan, kelIndex) in Object.keys(kecamatan.kelurahan)" :key="kelIndex"
                                            class="px-2 py-3 text-sm text-center border-r border-gray-200">
                                            {{ formatNumber(kecamatan.kelurahan[kelurahan].votes?.partai?.[party.nomor_urut] || 0) }}
                                        </td>
                                    </tr>

                                    <!-- Rows for each caleg -->
                                    <tr v-for="(caleg, calegIndex) in calegByPartai[party.nomor_urut] || []"
                                        :key="caleg.id"
                                        class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-center border-r border-gray-200">
                                            {{ calegIndex + 2 }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 border-r border-gray-200">
                                            {{ party.partai_singkat || party.nama }}
                                        </td>
                                        <td class="px-4 py-3 text-sm font-medium text-center border-r border-gray-200">
                                            {{ caleg.nomor_urut }}
                                        </td>
                                        <td class="px-4 py-3 text-sm font-medium text-blue-600 border-r border-gray-200">
                                            {{ caleg.nama }}
                                        </td>
                                        <td class="px-4 py-3 text-sm font-bold text-center border-r border-gray-200">
                                            {{ formatNumber(kecamatan.caleg_total[caleg.id] || 0) }}
                                        </td>
                                        <td v-for="(kelurahan, kelIndex) in Object.keys(kecamatan.kelurahan)" :key="kelIndex"
                                            class="px-2 py-3 text-sm text-center border-r border-gray-200">
                                            {{ formatNumber(kecamatan.kelurahan[kelurahan].votes?.caleg?.[caleg.id] || 0) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Information -->
            <div class="bg-purple-50 border border-purple-200 rounded-xl p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-purple-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-purple-900 mb-2">Keterangan</h3>
                        <div class="text-sm text-purple-800 space-y-1">
                            <p>• Setiap kecamatan memiliki tabel terpisah berdasarkan partai</p>
                            <p>• Baris pertama menunjukkan suara partai tanpa caleg per kelurahan/desa</p>
                            <p>• Baris selanjutnya menunjukkan suara masing-masing caleg per kelurahan/desa</p>
                            <p>• Data diambil dari tabel <code class="bg-purple-100 px-1 rounded">hr_dpr_ri_kel</code></p>
                            <p>• Total {{ kecamatanData.length }} kecamatan di {{ kabupatenName }}</p>
                        </div>
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

// Icons
const ChartIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path></svg>`
}

const LocationIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>`
}

const UsersIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path></svg>`
}

const props = defineProps({
    kecamatanData: Array,
    parties: Array,
    calegByPartai: Object,
    kabupatenName: String,
    provinceName: String,
    provinceId: [String, Number],
    kabupatenId: [String, Number],
    title: String,
    error: String
})

const statistics = computed(() => {
    const totalKecamatan = props.kecamatanData.length
    const totalPartai = props.parties.length

    // Count total kelurahan across all kecamatan
    let totalKelurahan = 0
    props.kecamatanData.forEach(kecamatan => {
        totalKelurahan += Object.keys(kecamatan.kelurahan).length
    })

    return [
        {
            label: 'Total Kecamatan',
            value: totalKecamatan.toLocaleString('id-ID'),
            icon: LocationIcon,
            colorClass: 'bg-purple-500'
        },
        {
            label: 'Total Kelurahan/Desa',
            value: totalKelurahan.toLocaleString('id-ID'),
            icon: ChartIcon,
            colorClass: 'bg-indigo-500'
        },
        {
            label: 'Total Partai',
            value: totalPartai.toLocaleString('id-ID'),
            icon: UsersIcon,
            colorClass: 'bg-blue-500'
        }
    ]
})

const formatNumber = (number) => {
    return parseInt(number || 0).toLocaleString('id-ID')
}
</script>