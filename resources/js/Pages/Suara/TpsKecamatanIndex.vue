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
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Data TPS per Kelurahan</h2>
                    <p class="text-xl text-gray-600 mb-1">Kecamatan {{ kecamatanName }}</p>
                    <p class="text-lg text-gray-500 mb-1">{{ kabupatenName }}</p>
                    <p class="text-lg text-gray-500 mb-4">{{ provinceName }}</p>
                    <div class="w-24 h-1 bg-gradient-to-r from-blue-500 to-indigo-600 mx-auto rounded-full"></div>
                </div>

                <!-- Back Button -->
                <div class="mt-6 text-center">
                    <a :href="`/dpr/kabupaten/${kabupatenId}/kecamatan`"
                       class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Kembali ke Kecamatan
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
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

            <!-- Filter Options -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                <div class="flex flex-wrap gap-4 items-center">
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700">Filter by Kelurahan:</label>
                        <select v-model="selectedKelurahan"
                                class="border border-gray-300 rounded-md px-3 py-1 text-sm">
                            <option value="">Semua Kelurahan</option>
                            <option v-for="kel in uniqueKelurahan" :key="kel.id" :value="kel.id">
                                {{ kel.nama }}
                            </option>
                        </select>
                    </div>
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700">Tampilan:</label>
                        <button @click="showVoteData = !showVoteData"
                                :class="showVoteData ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                                class="px-3 py-1 rounded-md text-sm font-medium transition-colors duration-200">
                            {{ showVoteData ? 'Data Suara' : 'Info TPS' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- TPS Data Table -->
            <div
                class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden"
                v-motion
                :initial="{ opacity: 0, y: 50 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 400 } }"
            >
                <div class="px-8 py-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">📍 Data TPS per Kelurahan</h3>
                    <p class="text-gray-600 mt-1">Daftar lengkap Tempat Pemungutan Suara (TPS) di kecamatan ini</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200">
                                    No
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-left border-r border-gray-200">
                                    Kelurahan/Desa
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200">
                                    No TPS
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-left border-r border-gray-200">
                                    Nama TPS
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-yellow-50">
                                    Total DPT
                                </th>
                                <template v-if="showVoteData">
                                    <th v-for="party in parties" :key="party.nomor_urut"
                                        class="px-2 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-blue-50"
                                        style="writing-mode: vertical-rl; text-orientation: mixed; min-width: 50px;">
                                        {{ party.partai_singkat || party.nama }}
                                    </th>
                                    <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-green-50">
                                        Total Suara
                                    </th>
                                </template>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-purple-50">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="(tps, index) in filteredTpsData"
                                :key="index"
                                class="hover:bg-gray-50 transition-colors duration-200"
                                v-motion
                                :initial="{ opacity: 0, x: -50 }"
                                :enter="{
                                    opacity: 1,
                                    x: 0,
                                    transition: {
                                        duration: 400,
                                        delay: (index * 30) + 600
                                    }
                                }"
                            >
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-xs">
                                        {{ index + 1 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-blue-600 border-r border-gray-200">
                                    {{ tps.kelurahan_nama }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        {{ tps.no_tps }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 border-r border-gray-200">
                                    {{ tps.tps_nama }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ formatNumber(tps.total_dpt || 0) }}
                                    </span>
                                </td>
                                <template v-if="showVoteData">
                                    <td v-for="party in parties" :key="party.nomor_urut"
                                        class="px-2 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                        <span class="inline-flex items-center px-1 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                                              :title="`Data kelurahan ${tps.kelurahan_nama}`">
                                            {{ getSuaraPartaiFromChart(tps.party_vote_data, party.nomor_urut) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                              :title="`Data TPS ${getTpsCode(tps)}`">
                                            {{ getTotalSuaraFromTbl(tps.caleg_vote_data, getTpsCode(tps)) }}
                                        </span>
                                    </td>
                                </template>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <button @click="showTpsDetail(tps)"
                                            class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-indigo-100 text-indigo-800 hover:bg-indigo-200 transition-colors duration-200">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Informasi -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-blue-900 mb-2">Keterangan</h3>
                        <div class="text-sm text-blue-800 space-y-1">
                            <p>• Data TPS diambil dari tabel <code class="bg-blue-100 px-1 rounded">pdpr_wil_tps</code></p>
                            <p>• Data suara partai (per kelurahan) dari kolom <code class="bg-blue-100 px-1 rounded">chart</code> tabel <code class="bg-blue-100 px-1 rounded">hr_dpr_ri_kel</code></p>
                            <p>• Data suara caleg (per TPS) dari kolom <code class="bg-blue-100 px-1 rounded">tbl</code> tabel <code class="bg-blue-100 px-1 rounded">hr_dpr_ri_kel</code></p>
                            <p>• DPT = Daftar Pemilih Tetap per TPS</p>
                            <p>• Kolom partai menampilkan data aggregat per kelurahan, total suara menampilkan data per TPS</p>
                            <p>• Total {{ filteredTpsData.length }} TPS di Kecamatan {{ kecamatanName }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
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

const OfficeIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"></path><path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"></path></svg>`
}

const props = defineProps({
    tpsData: Array,
    parties: Array,
    calegData: Array,
    tpsStats: Object,
    kecamatanName: String,
    kabupatenName: String,
    provinceName: String,
    provinceId: [String, Number],
    kabupatenId: [String, Number],
    kecamatanId: [String, Number],
    title: String,
    error: String
})

// Reactive variables
const selectedKelurahan = ref('')
const showVoteData = ref(true)

// Computed properties
const uniqueKelurahan = computed(() => {
    if (!props.tpsData || !Array.isArray(props.tpsData)) {
        return []
    }

    const kelurahanMap = new Map()
    props.tpsData.forEach(tps => {
        if (tps && tps.kelurahan_id && tps.kelurahan_nama && !kelurahanMap.has(tps.kelurahan_id)) {
            kelurahanMap.set(tps.kelurahan_id, { id: tps.kelurahan_id, nama: tps.kelurahan_nama })
        }
    })
    return Array.from(kelurahanMap.values())
})

const filteredTpsData = computed(() => {
    if (!props.tpsData || !Array.isArray(props.tpsData)) {
        return []
    }

    if (!selectedKelurahan.value) {
        return props.tpsData
    }
    return props.tpsData.filter(tps => tps && tps.kelurahan_id == selectedKelurahan.value)
})

const statistics = computed(() => {
    const stats = props.tpsStats || { total_tps: 0, total_dpt: 0, total_kelurahan: 0 }
    const displayedTps = (props.tpsData && Array.isArray(props.tpsData)) ? props.tpsData.length : 0

    return [
        {
            label: 'Total TPS',
            value: parseInt(stats.total_tps || 0).toLocaleString('id-ID'),
            icon: OfficeIcon,
            colorClass: 'bg-purple-500'
        },
        {
            label: 'Ditampilkan',
            value: displayedTps.toLocaleString('id-ID'),
            icon: LocationIcon,
            colorClass: 'bg-blue-500'
        },
        {
            label: 'Total DPT',
            value: parseInt(stats.total_dpt || 0).toLocaleString('id-ID'),
            icon: UsersIcon,
            colorClass: 'bg-yellow-500'
        },
        {
            label: 'Total Kelurahan',
            value: parseInt(stats.total_kelurahan || 0).toLocaleString('id-ID'),
            icon: ChartIcon,
            colorClass: 'bg-green-500'
        }
    ]
})

// Functions
const formatNumber = (number) => {
    return parseInt(number || 0).toLocaleString('id-ID')
}

const getTpsCode = (tps) => {
    if (tps.no_tps) {
        return tps.no_tps.toString()
    } else if (tps.tps_nama) {
        const match = tps.tps_nama.match(/\d+/)
        if (match) {
            return match[0]
        }
    }
    return '1'
}

// Function to get party votes from chart column
const getSuaraPartaiFromChart = (chartJson, partaiId) => {
    if (!chartJson) return 0
    try {
        const data = JSON.parse(chartJson)
        if (!data || !data[partaiId]) return 0

        return parseInt(data[partaiId].jml_suara_partai || 0)
    } catch (e) {
        return 0
    }
}

// Function to get total votes from tbl column for specific TPS
const getTotalSuaraFromTbl = (tblJson, tpsCode) => {
    if (!tblJson) return 0
    try {
        const data = JSON.parse(tblJson)
        if (!data || !data[tpsCode]) return 0

        const tpsData = data[tpsCode]
        if (!tpsData || typeof tpsData !== 'object') return 0

        let total = 0
        Object.entries(tpsData).forEach(([calegId, votes]) => {
            if (calegId !== 'null' && !isNaN(calegId)) {
                total += parseInt(votes || 0)
            }
        })

        return total
    } catch (e) {
        return 0
    }
}

const showTpsDetail = (tps) => {
    alert(`Detail TPS ${tps.tps_nama} - Fitur detail akan dikembangkan`)
}
</script>