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
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Data Suara DPR RI 2024</h2>
                    <p class="text-xl text-gray-600 mb-2">{{ kabupatenName }}</p>
                    <p class="text-lg text-gray-500 mb-4">{{ provinceName }}</p>
                    <div class="w-24 h-1 bg-gradient-to-r from-green-500 to-emerald-600 mx-auto rounded-full"></div>
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

            <!-- Tabel Data Suara Partai -->
            <div
                class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden"
                v-motion
                :initial="{ opacity: 0, y: 50 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 400 } }"
            >
                <div class="px-8 py-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">📊 Tabel Suara Partai per Kelurahan/Desa</h3>
                            <p class="text-gray-600 mt-1">Data suara semua partai dan caleg DPR RI di {{ kabupatenName }}</p>
                        </div>
                        <a :href="`/dpr/kabupaten/${kabupatenId}/suara/export-excel`"
                           class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            📥 Export Excel
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200">
                                    No
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-left border-r border-gray-200">
                                    Kecamatan
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-left border-r border-gray-200">
                                    Kelurahan/Desa
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200">
                                    Jumlah TPS
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-yellow-50">
                                    DPT
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-red-50">
                                    Suara Tidak Sah
                                </th>
                                <th v-for="party in parties" :key="party.nomor_urut"
                                    class="px-2 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-blue-50"
                                    style="writing-mode: vertical-rl; text-orientation: mixed; min-width: 50px;">
                                    {{ party.partai_singkat || party.nama }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="(vote, index) in voteData"
                                :key="index"
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
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-xs">
                                        {{ index + 1 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-200">
                                    {{ vote.kec_nama }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-blue-600 border-r border-gray-200">
                                    {{ vote.kel_nama }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        {{ vote.jumlah_tps }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ formatNumber(getDPTCount(vote.tbl)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ formatNumber(getSuaraTidakSah(vote.tbl)) }}
                                    </span>
                                </td>
                                <td v-for="party in parties" :key="party.nomor_urut"
                                    class="px-2 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    {{ formatNumber(getSuaraPartai(vote.chart, party.nomor_urut)) }}
                                </td>
                            </tr>

                            <!-- Total Row -->
                            <tr class="bg-green-100 font-bold border-t-2 border-green-300">
                                <td colspan="3" class="px-4 py-4 text-sm font-bold text-gray-900 border-r border-gray-200">
                                    TOTAL {{ kabupatenName.toUpperCase() }}
                                </td>
                                <td class="px-4 py-4 text-sm font-bold text-gray-900 text-center border-r border-gray-200">
                                    {{ totalTPS }}
                                </td>
                                <td class="px-4 py-4 text-sm font-bold text-gray-900 text-center border-r border-gray-200">
                                    {{ formatNumber(getTotalDPT()) }}
                                </td>
                                <td class="px-4 py-4 text-sm font-bold text-gray-900 text-center border-r border-gray-200">
                                    {{ formatNumber(getTotalSuaraTidakSah()) }}
                                </td>
                                <td v-for="party in parties" :key="party.nomor_urut"
                                    class="px-2 py-4 text-sm font-bold text-gray-900 text-center border-r border-gray-200">
                                    {{ formatNumber(getTotalPerPartai(party.nomor_urut)) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table Summary -->
                <div class="bg-gray-50 px-8 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-700">
                            Menampilkan <span class="font-medium">{{ voteData.length }}</span> kelurahan/desa dari total
                            <span class="font-medium">{{ voteData.length }}</span> data
                        </p>
                        <p class="text-sm text-gray-500">
                            Data diperbarui: {{ new Date().toLocaleDateString('id-ID') }}
                        </p>
                    </div>
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
                            <p>• Data partai diambil dari tabel <code class="bg-blue-100 px-1 rounded">hr_dpr_ri_kel</code> kolom <code class="bg-blue-100 px-1 rounded">chart</code></p>
                            <p>• Data caleg diambil dari tabel <code class="bg-blue-100 px-1 rounded">hr_dpr_ri_kel</code> kolom <code class="bg-blue-100 px-1 rounded">tbl</code> dan <code class="bg-blue-100 px-1 rounded">dpr_ri_caleg</code></p>
                            <p>• Jumlah TPS dihitung dari data JSON dalam kolom <code class="bg-blue-100 px-1 rounded">tbl</code></p>
                            <p>• Partai yang ditampilkan: {{ parties.map(p => p.partai_singkat || p.nama).join(', ') }}</p>
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

const OfficeIcon = {
    template: `<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"></path><path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"></path></svg>`
}

const props = defineProps({
    voteData: Array,
    parties: Array,
    kabupatenName: String,
    provinceName: String,
    provinceId: [String, Number],
    kabupatenId: [String, Number],
    title: String,
    error: String
})

const getSuaraPartai = (chartJson, partaiId) => {
    if (!chartJson) return 0;
    try {
        const data = JSON.parse(chartJson);
        return data[partaiId]?.jml_suara_total || 0;
    } catch (e) {
        return 0;
    }
}

const getTotalSuara = (chartJson) => {
    if (!chartJson) return 0;
    let total = 0;
    props.parties.forEach(party => {
        total += getSuaraPartai(chartJson, party.nomor_urut);
    });
    return total;
}

const totalTPS = computed(() => {
    return props.voteData.reduce((sum, vote) => sum + parseInt(vote.jumlah_tps), 0);
})

const getTotalPerPartai = (partaiId) => {
    return props.voteData.reduce((sum, vote) => {
        return sum + getSuaraPartai(vote.chart, partaiId);
    }, 0);
}

const grandTotal = computed(() => {
    return props.voteData.reduce((sum, vote) => {
        return sum + getTotalSuara(vote.chart);
    }, 0);
})

const statistics = computed(() => {
    const totalKelurahan = props.voteData.length;
    const totalPartai = props.parties.length;
    const totalDPT = getTotalDPT();
    const totalTidakSah = getTotalSuaraTidakSah();

    return [
        {
            label: 'Total Kelurahan',
            value: totalKelurahan.toLocaleString('id-ID'),
            icon: LocationIcon,
            colorClass: 'bg-blue-500'
        },
        {
            label: 'Total TPS',
            value: totalTPS.value.toLocaleString('id-ID'),
            icon: OfficeIcon,
            colorClass: 'bg-purple-500'
        },
        {
            label: 'Total DPT',
            value: totalDPT.toLocaleString('id-ID'),
            icon: UsersIcon,
            colorClass: 'bg-yellow-500'
        },
        {
            label: 'Suara Tidak Sah',
            value: totalTidakSah.toLocaleString('id-ID'),
            icon: ChartIcon,
            colorClass: 'bg-red-500'
        }
    ]
})

const getDPTCount = (tblJson) => {
    if (!tblJson) return 0;
    try {
        const data = JSON.parse(tblJson);
        let total = 0;
        Object.values(data).forEach(tpsData => {
            if (tpsData && typeof tpsData === 'object' && tpsData.dpt) {
                total += parseInt(tpsData.dpt) || 0;
            }
        });
        return total;
    } catch (e) {
        return 0;
    }
}

const getSuaraTidakSah = (tblJson) => {
    if (!tblJson) return 0;
    try {
        const data = JSON.parse(tblJson);
        let total = 0;
        Object.values(data).forEach(tpsData => {
            if (tpsData && typeof tpsData === 'object') {
                if (tpsData.suara_tidak_sah) {
                    total += parseInt(tpsData.suara_tidak_sah) || 0;
                } else if (tpsData.tidak_sah) {
                    total += parseInt(tpsData.tidak_sah) || 0;
                }
            }
        });
        return total;
    } catch (e) {
        return 0;
    }
}

const getTotalDPT = () => {
    return props.voteData.reduce((sum, vote) => {
        return sum + getDPTCount(vote.tbl);
    }, 0);
}

const getTotalSuaraTidakSah = () => {
    return props.voteData.reduce((sum, vote) => {
        return sum + getSuaraTidakSah(vote.tbl);
    }, 0);
}

const formatNumber = (number) => {
    return parseInt(number || 0).toLocaleString('id-ID')
}
</script>