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
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Data Suara per Kelurahan</h2>
                    <p class="text-xl text-gray-600 mb-1">Kecamatan {{ kecamatanName }}</p>
                    <p class="text-lg text-gray-500 mb-1">{{ kabupatenName }}</p>
                    <p class="text-lg text-gray-500 mb-4">{{ provinceName }}</p>
                    <div class="w-24 h-1 bg-gradient-to-r from-green-500 to-emerald-600 mx-auto rounded-full"></div>
                </div>

                <!-- Navigation Buttons -->
                <div class="mt-6 flex justify-center space-x-4">
                    <a :href="`/dpr/kabupaten/${kabupatenId}/kecamatan`"
                       class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Kembali ke Kecamatan
                    </a>
                    <a :href="`/dpr/kecamatan/${kecamatanId}/tps`"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        📍 Lihat Data TPS
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

            <!-- Tabel Data Suara Partai per Kelurahan -->
            <div
                class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden"
                v-motion
                :initial="{ opacity: 0, y: 50 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 400 } }"
            >
                <div class="px-8 py-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">📊 Data Suara Partai per Kelurahan</h3>
                            <p class="text-gray-600 mt-1">Data suara partai di setiap kelurahan/desa</p>
                        </div>
                        <a :href="`/dpr/kecamatan/${kecamatanId}/suara/export-excel`"
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
                                    Kelurahan/Desa
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200">
                                    Jumlah TPS
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-yellow-50">
                                    DPT
                                </th>
                                <th v-for="party in parties" :key="party.nomor_urut"
                                    class="px-2 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-blue-50"
                                    style="writing-mode: vertical-rl; text-orientation: mixed; min-width: 50px;">
                                    {{ party.partai_singkat || party.nama }}
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-green-50">
                                    Total Suara
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-indigo-50">
                                    Suara per TPS
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="(kelData, index) in kelurahanVoteData"
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
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-blue-600 border-r border-gray-200">
                                    {{ kelData.kelurahan_info.nama_kelurahan }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        {{ kelData.vote_data.jumlah_tps }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ formatNumber(kelData.vote_data.total_dpt) }}
                                    </span>
                                </td>
                                <td v-for="party in parties" :key="party.nomor_urut"
                                    class="px-2 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    {{ formatNumber(getSuaraPartai(kelData.vote_data.chart, party.nomor_urut)) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ formatNumber(getTotalSuaraKelurahan(kelData.vote_data.chart)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <button @click="toggleTpsData(kelData)"
                                            class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-indigo-100 text-indigo-800 hover:bg-indigo-200 transition-colors duration-200">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                        Lihat TPS ({{ kelData.tps_data ? kelData.tps_data.length : 0 }})
                                    </button>
                                </td>
                            </tr>

                            <!-- Total Row -->
                            <tr class="bg-green-100 font-bold border-t-2 border-green-300">
                                <td colspan="2" class="px-4 py-4 text-sm font-bold text-gray-900 border-r border-gray-200">
                                    TOTAL {{ kecamatanName.toUpperCase() }}
                                </td>
                                <td class="px-4 py-4 text-sm font-bold text-gray-900 text-center border-r border-gray-200">
                                    {{ totalTPS }}
                                </td>
                                <td class="px-4 py-4 text-sm font-bold text-gray-900 text-center border-r border-gray-200">
                                    {{ formatNumber(totalDPT) }}
                                </td>
                                <td v-for="party in parties" :key="party.nomor_urut"
                                    class="px-2 py-4 text-sm font-bold text-gray-900 text-center border-r border-gray-200">
                                    {{ formatNumber(getTotalPerPartai(party.nomor_urut)) }}
                                </td>
                                <td class="px-4 py-4 text-sm font-bold text-gray-900 text-center border-r border-gray-200">
                                    {{ formatNumber(grandTotalSuara) }}
                                </td>
                                <td class="px-4 py-4 text-sm font-bold text-gray-900 text-center border-r border-gray-200">
                                    -
                                </td>
                            </tr>

                            <!-- TPS Detail Rows -->
                            <template v-for="(kelData, kelIndex) in kelurahanVoteData" :key="`keldata-${kelIndex}`">
                                <template v-if="kelData.showTpsData">
                                    <tr v-for="(tps, tpsIndex) in kelData.tps_data" :key="`tps-${kelIndex}-${tpsIndex}`"
                                        class="bg-blue-50 border-l-4 border-blue-400">
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 text-center border-r border-gray-200">
                                            {{ tps.no_tps }}
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 border-r border-gray-200">
                                            <span class="ml-4">{{ tps.tps_nama }}</span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 text-center border-r border-gray-200">
                                            1
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 text-center border-r border-gray-200">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-200 text-yellow-900">
                                                {{ formatNumber(tps.total_dpt || 0) }}
                                            </span>
                                        </td>
                                        <td v-for="party in parties" :key="`tps-party-${party.nomor_urut}`"
                                            class="px-2 py-2 whitespace-nowrap text-sm text-gray-700 text-center border-r border-gray-200">
                                            {{ formatNumber(getSuaraPartaiPerTps(kelData.vote_data.tbl, getTpsCode(tps), party.nomor_urut)) }}
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 text-center border-r border-gray-200">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-200 text-green-900">
                                                {{ formatNumber(getTotalSuaraTps(kelData.vote_data.tbl, getTpsCode(tps))) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 text-center border-r border-gray-200">
                                            <button @click="showTpsCalegDetail(kelData, tps)"
                                                    class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-800 hover:bg-purple-200 transition-colors duration-200">
                                                Caleg
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel Data Suara Caleg per Kelurahan -->
            <div
                class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden"
                v-motion
                :initial="{ opacity: 0, y: 50 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 600 } }"
            >
                <div class="px-8 py-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">📊 Data Suara Caleg per Kelurahan</h3>
                            <p class="text-gray-600 mt-1">Data suara kandidat legislatif yang memperoleh suara > 0</p>
                        </div>
                        <button @click="showTpsInCalegTable = !showTpsInCalegTable"
                                class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            {{ showTpsInCalegTable ? 'Sembunyikan TPS' : 'Tampilkan per TPS' }}
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200">
                                    No
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200">
                                    No. Urut Partai
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-left border-r border-gray-200">
                                    Partai
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200">
                                    No. Urut
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-left border-r border-gray-200">
                                    Nama Caleg
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200">
                                    L/P
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-green-50">
                                    Total Suara
                                </th>
                                <template v-if="!showTpsInCalegTable">
                                    <th v-for="kelData in kelurahanVoteData" :key="kelData.kelurahan_info.id"
                                        class="px-2 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-blue-50"
                                        style="writing-mode: vertical-rl; text-orientation: mixed; min-width: 50px;">
                                        {{ kelData.kelurahan_info.nama_kelurahan }}
                                    </th>
                                </template>
                                <template v-else>
                                    <template v-for="kelData in kelurahanVoteData" :key="`kel-${kelData.kelurahan_info.id}`">
                                        <th v-for="tps in kelData.tps_data" :key="`tps-header-${tps.id}`"
                                            class="px-1 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-purple-50"
                                            style="writing-mode: vertical-rl; text-orientation: mixed; min-width: 40px;">
                                            {{ tps.tps_nama }}
                                        </th>
                                    </template>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="(caleg, index) in calegWithVotes"
                                :key="caleg.id"
                                :class="[
                                    caleg.is_party_row
                                        ? 'bg-orange-50 font-bold border-l-4 border-orange-400'
                                        : 'hover:bg-gray-50 transition-colors duration-200'
                                ]"
                            >
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-xs">
                                        {{ index + 1 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ caleg.partai_id }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium border-r border-gray-200"
                                    :class="caleg.is_party_row ? 'text-orange-700 bg-orange-100' : 'text-blue-600'">
                                    {{ getPartaiName(caleg.partai_id) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <span v-if="caleg.is_party_row"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-200 text-orange-800">
                                        {{ caleg.nomor_urut }}
                                    </span>
                                    <span v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ caleg.nomor_urut }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm border-r border-gray-200"
                                    :class="[
                                        'text-left',
                                        caleg.is_party_row ? 'text-gray-700 italic' : 'text-gray-900'
                                    ]">
                                    {{ caleg.nama }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    {{ caleg.jenis_kelamin }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    <span v-if="caleg.is_party_row"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-200 text-orange-800">
                                        {{ formatNumber(getTotalSuaraPartaiSaja(caleg.partai_id)) }}
                                    </span>
                                    <span v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ formatNumber(caleg.total_suara) }}
                                    </span>
                                </td>
                                <template v-if="!showTpsInCalegTable">
                                    <td v-for="kelData in kelurahanVoteData" :key="kelData.kelurahan_info.id"
                                        class="px-2 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200"
                                        :class="caleg.is_party_row ? 'bg-orange-100' : ''">
                                        <span v-if="caleg.is_party_row">
                                            {{ formatNumber(getSuaraPartaiSaja(kelData.vote_data.chart, caleg.partai_id)) }}
                                        </span>
                                        <span v-else>
                                            {{ formatNumber(getSuaraCalegPerKelurahan(kelData.vote_data.tbl, caleg.id)) }}
                                        </span>
                                    </td>
                                </template>
                                <template v-else>
                                    <template v-for="kelData in kelurahanVoteData" :key="`kel-data-${kelData.kelurahan_info.id}`">
                                        <td v-for="tps in kelData.tps_data" :key="`tps-data-${tps.id}`"
                                            class="px-1 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200"
                                            :class="caleg.is_party_row ? 'bg-orange-100' : ''">
                                            <span v-if="caleg.is_party_row">
                                                {{ formatNumber(getSuaraPartaiPerTps(kelData.vote_data.tbl, getTpsCode(tps), caleg.partai_id)) }}
                                            </span>
                                            <span v-else>
                                                {{ formatNumber(getSuaraCalegPerTpsDetail(kelData.vote_data.tbl, getTpsCode(tps), caleg.id)) }}
                                            </span>
                                        </td>
                                    </template>
                                </template>
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
                            <p>• Data partai diambil dari tabel <code class="bg-blue-100 px-1 rounded">hr_dpr_ri_kel</code> kolom <code class="bg-blue-100 px-1 rounded">chart</code></p>
                            <p>• Data caleg diambil dari tabel <code class="bg-blue-100 px-1 rounded">hr_dpr_ri_kel</code> kolom <code class="bg-blue-100 px-1 rounded">tbl</code> dan <code class="bg-blue-100 px-1 rounded">dpr_ri_caleg</code></p>
                            <p>• Hanya menampilkan caleg yang memperoleh suara > 0</p>
                            <p>• Total {{ kelurahanVoteData.length }} kelurahan/desa di Kecamatan {{ kecamatanName }}</p>
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
    kelurahanVoteData: Array,
    parties: Array,
    calegData: Array,
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
const showTpsInCalegTable = ref(false)

const getSuaraPartai = (chartJson, partaiId) => {
    if (!chartJson) return 0;
    try {
        const data = JSON.parse(chartJson);
        return data[partaiId]?.jml_suara_total || 0;
    } catch (e) {
        return 0;
    }
}

const getSuaraPartaiSaja = (chartJson, partaiId) => {
    if (!chartJson) return 0;
    try {
        const data = JSON.parse(chartJson);
        return data[partaiId]?.jml_suara_partai || 0;
    } catch (e) {
        return 0;
    }
}

const getTotalSuaraKelurahan = (chartJson) => {
    if (!chartJson) return 0;
    let total = 0;
    props.parties.forEach(party => {
        total += getSuaraPartai(chartJson, party.nomor_urut);
    });
    return total;
}

const getSuaraCaleg = (tblJson) => {
    if (!tblJson) return {};
    try {
        const data = JSON.parse(tblJson);
        const result = {};

        if (data && typeof data === 'object') {
            Object.values(data).forEach(tpsData => {
                if (typeof tpsData === 'object') {
                    Object.entries(tpsData).forEach(([calegId, suara]) => {
                        if (calegId !== 'null' && !isNaN(calegId)) {
                            result[calegId] = (result[calegId] || 0) + parseInt(suara || 0);
                        }
                    });
                }
            });
        }

        return result;
    } catch (e) {
        return {};
    }
}

const getSuaraCalegPerKelurahan = (tblJson, calegId) => {
    const votes = getSuaraCaleg(tblJson);
    return votes[calegId] || 0;
}

const calegWithVotes = computed(() => {
    // Use processed data from controller which already includes party rows
    return props.calegData;
})

const getPartaiName = (partaiId) => {
    const party = props.parties.find(p => p.nomor_urut == partaiId);
    return party ? (party.partai_singkat || party.nama) : `Partai ${partaiId}`;
}

const getTotalSuaraPartaiSaja = (partaiId) => {
    let total = 0;
    props.kelurahanVoteData.forEach(kelData => {
        total += getSuaraPartaiSaja(kelData.vote_data.chart, partaiId);
    });
    return total;
}

const totalTPS = computed(() => {
    return props.kelurahanVoteData.reduce((sum, kelData) => sum + parseInt(kelData.vote_data.jumlah_tps || 0), 0);
})

const totalDPT = computed(() => {
    return props.kelurahanVoteData.reduce((sum, kelData) => sum + parseInt(kelData.vote_data.total_dpt || 0), 0);
})

const getTotalPerPartai = (partaiId) => {
    return props.kelurahanVoteData.reduce((sum, kelData) => {
        return sum + getSuaraPartai(kelData.vote_data.chart, partaiId);
    }, 0);
}

const grandTotalSuara = computed(() => {
    return props.kelurahanVoteData.reduce((sum, kelData) => {
        return sum + getTotalSuaraKelurahan(kelData.vote_data.chart);
    }, 0);
})

const statistics = computed(() => {
    const totalKelurahan = props.kelurahanVoteData.length;
    const totalCaleg = calegWithVotes.value.length;
    const totalPartai = new Set(calegWithVotes.value.map(c => c.partai_id)).size;

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
            value: totalDPT.value.toLocaleString('id-ID'),
            icon: UsersIcon,
            colorClass: 'bg-yellow-500'
        },
        {
            label: 'Total Suara',
            value: grandTotalSuara.value.toLocaleString('id-ID'),
            icon: ChartIcon,
            colorClass: 'bg-green-500'
        }
    ]
})

const formatNumber = (number) => {
    return parseInt(number || 0).toLocaleString('id-ID')
}

const toggleTpsData = (kelData) => {
    kelData.showTpsData = !kelData.showTpsData
}

const getTpsCode = (tps) => {
    // Get TPS code for looking up vote data
    // Try multiple formats since the actual data structure may vary
    if (tps.no_tps) {
        return tps.no_tps.toString()
    } else if (tps.tps_nama) {
        // Extract number from TPS name like "TPS 001"
        const match = tps.tps_nama.match(/\d+/)
        if (match) {
            return match[0]
        }
    }
    return '1'
}

const getSuaraPartaiPerTps = (tblJson, tpsCode, partaiId) => {
    if (!tblJson) return 0
    try {
        const data = JSON.parse(tblJson)
        if (!data || !data[tpsCode]) return 0

        const tpsData = data[tpsCode]
        if (!tpsData || typeof tpsData !== 'object') return 0

        // Sum votes for all caleg of this party in this TPS
        let total = 0
        Object.entries(tpsData).forEach(([calegId, votes]) => {
            if (calegId !== 'null' && !isNaN(calegId)) {
                // Find the caleg to check which party they belong to
                const caleg = props.calegData.find(c => c.id == calegId)
                if (caleg && caleg.partai_id == partaiId) {
                    total += parseInt(votes || 0)
                }
            }
        })

        return total
    } catch (e) {
        return 0
    }
}

const getTotalSuaraTps = (tblJson, tpsCode) => {
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

const showTpsCalegDetail = (kelData, tps) => {
    // This function could open a modal or navigate to detailed TPS caleg page
    alert(`Detail caleg TPS ${tps.tps_nama} - Fitur akan dikembangkan`)
}

const getSuaraCalegPerTpsDetail = (tblJson, tpsCode, calegId) => {
    if (!tblJson) return 0
    try {
        const data = JSON.parse(tblJson)
        if (!data || !data[tpsCode] || !data[tpsCode][calegId]) {
            return 0
        }
        return parseInt(data[tpsCode][calegId] || 0)
    } catch (e) {
        return 0
    }
}
</script>