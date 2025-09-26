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
                    <p class="text-xl text-gray-600 mb-2">{{ kelurahanName }}</p>
                    <p class="text-lg text-gray-500 mb-2">Kecamatan {{ kecamatanName }}</p>
                    <p class="text-lg text-gray-500 mb-4">{{ kabupatenName }}, {{ provinceName }}</p>
                    <div class="w-24 h-1 bg-gradient-to-r from-green-500 to-emerald-600 mx-auto rounded-full"></div>
                </div>

                <!-- Back Button -->
                <div class="mt-6 text-center">
                    <a :href="`/dpr/kabupaten/${kabupatenId}/suara`"
                       class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Kembali ke Data Suara
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

            <!-- Data Suara Partai Section -->
            <div
                class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden"
                v-motion
                :initial="{ opacity: 0, y: 50 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 400 } }"
            >
                <div class="px-8 py-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">📊 Data Suara Partai</h3>
                            <p class="text-gray-600 mt-1">Perolehan suara partai di {{ kelurahanName }}</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center">No</th>
                                <th v-for="party in parties" :key="party.nomor_urut"
                                    class="px-2 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center border-r border-gray-200 bg-blue-50"
                                    style="writing-mode: vertical-rl; text-orientation: mixed; min-width: 50px;">
                                    {{ party.partai_singkat || party.nama }}
                                </th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center bg-yellow-50">
                                    Total Suara Partai
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center">1</td>
                                <td v-for="party in parties" :key="party.nomor_urut"
                                    class="px-2 py-3 whitespace-nowrap text-sm text-gray-900 text-center border-r border-gray-200">
                                    {{ formatNumber(getSuaraPartai(party.nomor_urut)) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900 text-center bg-yellow-50">
                                    {{ formatNumber(totalSuaraPartai) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Data Suara Caleg Section -->
            <div
                v-if="calegWithVotes.length > 0"
                class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden"
                v-motion
                :initial="{ opacity: 0, y: 50 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 600 } }"
            >
                <div class="px-8 py-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">👥 Data Suara Detail per Caleg</h3>
                            <p class="text-gray-600 mt-1">Perolehan suara kandidat legislatif di {{ kelurahanName }}</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center">No</th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-left">Partai</th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center">No. Urut</th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-left">Nama Caleg</th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center">L/P</th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center bg-green-50">Total Suara</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template v-for="(caleg, index) in processedCalegData" :key="caleg.id">
                                <!-- Party Suara Row -->
                                <tr v-if="caleg.isPartaiRow" class="bg-yellow-50 font-bold">
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 text-center">{{ caleg.rowNumber }}</td>
                                    <td class="px-4 py-3 text-sm font-bold text-blue-600">{{ caleg.partaiNama }}</td>
                                    <td class="px-4 py-3 text-sm font-bold text-center text-orange-600">PARTAI</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-700 italic">Suara Partai (tanpa caleg)</td>
                                    <td class="px-4 py-3 text-sm text-center">-</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 text-center bg-yellow-100">
                                        {{ formatNumber(getSuaraPartaiSaja(caleg.partaiId)) }}
                                    </td>
                                </tr>
                                <!-- Caleg Row -->
                                <tr v-else :class="caleg.isTotalRow ? 'bg-green-100 font-bold border-t-2 border-green-300' : 'hover:bg-gray-50'"
                                    v-motion
                                    :initial="{ opacity: 0, x: -50 }"
                                    :enter="{
                                        opacity: 1,
                                        x: 0,
                                        transition: {
                                            duration: 400,
                                            delay: (index * 50) + 800
                                        }
                                    }">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center">
                                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-xs">
                                            {{ caleg.rowNumber }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-blue-600">
                                        {{ caleg.partaiNama }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-bold" :class="caleg.isTotalRow ? 'bg-green-200' : 'bg-yellow-100'">
                                        {{ caleg.isTotalRow ? 'TOTAL' : caleg.nomor_urut }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900" :class="caleg.isTotalRow ? 'font-bold italic' : ''">
                                        {{ caleg.isTotalRow ? 'Suara Partai + Suara Caleg' : caleg.nama }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center">
                                        {{ caleg.isTotalRow ? '-' : caleg.jenis_kelamin }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900 text-center" :class="caleg.isTotalRow ? 'bg-green-200' : 'bg-green-50'">
                                        {{ formatNumber(caleg.total_suara) }}
                                    </td>
                                </tr>
                            </template>

                            <!-- Grand Total Caleg -->
                            <tr class="bg-blue-100 font-bold border-t-2 border-blue-300">
                                <td colspan="5" class="px-4 py-4 text-sm font-bold text-gray-900">
                                    TOTAL SUARA CALEG
                                </td>
                                <td class="px-4 py-4 text-sm font-bold text-gray-900 text-center bg-blue-200">
                                    {{ formatNumber(totalSuaraCaleg) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Caleg Statistics -->
                <div class="bg-gray-50 px-8 py-4 border-t border-gray-200">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <p class="text-xs font-medium text-gray-500 uppercase">Total Caleg Aktif</p>
                            <p class="text-lg font-bold text-gray-900">{{ calegWithVotes.length }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-medium text-gray-500 uppercase">Partai Terwakili</p>
                            <p class="text-lg font-bold text-gray-900">{{ uniquePartaiCount }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-medium text-gray-500 uppercase">Caleg Perempuan</p>
                            <p class="text-lg font-bold text-gray-900">{{ calegPerempuanCount }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-medium text-gray-500 uppercase">Caleg Laki-laki</p>
                            <p class="text-lg font-bold text-gray-900">{{ calegLakiLakiCount }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Data Message -->
            <div v-else class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-semibold text-blue-900 mb-2">Informasi</h3>
                        <p class="text-sm text-blue-800">Data suara caleg tidak ditemukan atau belum tersedia untuk kelurahan ini.</p>
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
                            <p>• Hanya menampilkan caleg yang memperoleh suara > 0</p>
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
    calegWithVotes: Array,
    calegMap: Object,
    kelurahanName: String,
    kecamatanName: String,
    kabupatenName: String,
    provinceName: String,
    provinceId: [String, Number],
    kabupatenId: [String, Number],
    kecamatanId: [String, Number],
    kelurahanId: [String, Number],
    title: String,
    error: String
})

// Party mappings
const partaiMap = computed(() => {
    const map = {}
    props.parties.forEach(party => {
        map[party.nomor_urut] = party.partai_singkat || party.nama
    })
    return map
})

const getSuaraPartai = (partaiId) => {
    if (!props.voteData || props.voteData.length === 0) return 0
    try {
        const data = JSON.parse(props.voteData[0].chart || '{}')
        return data[partaiId]?.jml_suara_total || 0
    } catch (e) {
        return 0
    }
}

const getSuaraPartaiSaja = (partaiId) => {
    if (!props.voteData || props.voteData.length === 0) return 0
    try {
        const data = JSON.parse(props.voteData[0].chart || '{}')
        return data[partaiId]?.jml_suara_partai || 0
    } catch (e) {
        return 0
    }
}

const totalSuaraPartai = computed(() => {
    return props.parties.reduce((sum, party) => {
        return sum + getSuaraPartai(party.nomor_urut)
    }, 0)
})

const totalSuaraCaleg = computed(() => {
    return props.calegWithVotes.reduce((sum, caleg) => sum + caleg.total_suara, 0)
})

const uniquePartaiCount = computed(() => {
    const partaiIds = [...new Set(props.calegWithVotes.map(c => c.partai_id))]
    return partaiIds.length
})

const calegPerempuanCount = computed(() => {
    return props.calegWithVotes.filter(c => c.jenis_kelamin === 'P').length
})

const calegLakiLakiCount = computed(() => {
    return props.calegWithVotes.filter(c => c.jenis_kelamin === 'L').length
})

const processedCalegData = computed(() => {
    const result = []
    let rowNumber = 1
    let currentPartai = ''

    props.calegWithVotes.forEach((caleg, index) => {
        const partaiNama = partaiMap.value[caleg.partai_id] || `Partai ${caleg.partai_id}`

        // Add party row if new party
        if (currentPartai !== caleg.partai_id) {
            if (currentPartai !== '') {
                // Add separator
                result.push({
                    id: `separator-${currentPartai}`,
                    isSeparator: true
                })
            }

            // Add party suara row
            result.push({
                id: `partai-${caleg.partai_id}`,
                isPartaiRow: true,
                rowNumber: rowNumber++,
                partaiId: caleg.partai_id,
                partaiNama: partaiNama,
                total_suara: getSuaraPartaiSaja(caleg.partai_id)
            })

            currentPartai = caleg.partai_id
        }

        // Add caleg row
        result.push({
            ...caleg,
            rowNumber: rowNumber++,
            partaiNama: partaiNama
        })

        // Check if this is the last caleg of this party
        const isLastCalegOfParty = (index === props.calegWithVotes.length - 1) ||
                                   (props.calegWithVotes[index + 1]?.partai_id !== caleg.partai_id)

        if (isLastCalegOfParty) {
            // Calculate total for this party (party votes + caleg votes)
            const totalSuaraPartaiCurrent = getSuaraPartaiSaja(caleg.partai_id)
            const totalSuaraCalegCurrent = props.calegWithVotes
                .filter(c => c.partai_id === caleg.partai_id)
                .reduce((sum, c) => sum + c.total_suara, 0)

            // Add total row
            result.push({
                id: `total-${caleg.partai_id}`,
                isTotalRow: true,
                rowNumber: rowNumber++,
                partaiNama: partaiNama,
                total_suara: totalSuaraPartaiCurrent + totalSuaraCalegCurrent
            })
        }
    })

    return result
})

const statistics = computed(() => {
    const totalCaleg = props.calegWithVotes.length
    const totalPartai = uniquePartaiCount.value

    return [
        {
            label: 'Total Caleg Aktif',
            value: totalCaleg.toLocaleString('id-ID'),
            icon: UsersIcon,
            colorClass: 'bg-blue-500'
        },
        {
            label: 'Partai Terwakili',
            value: totalPartai.toLocaleString('id-ID'),
            icon: OfficeIcon,
            colorClass: 'bg-purple-500'
        },
        {
            label: 'Total Suara Partai',
            value: totalSuaraPartai.value.toLocaleString('id-ID'),
            icon: ChartIcon,
            colorClass: 'bg-green-500'
        },
        {
            label: 'Total Suara Caleg',
            value: totalSuaraCaleg.value.toLocaleString('id-ID'),
            icon: LocationIcon,
            colorClass: 'bg-orange-500'
        }
    ]
})

const formatNumber = (number) => {
    return parseInt(number || 0).toLocaleString('id-ID')
}
</script>