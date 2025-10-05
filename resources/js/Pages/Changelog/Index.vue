<template>
    <AppLayout :title="title">
        <Head :title="title" />

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-8">
                <h1 class="text-3xl font-bold text-white">Changelog</h1>
                <p class="text-blue-100 mt-2">Riwayat Perubahan dan Fitur Database Pemilu 2024</p>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="space-y-8">
                    <!-- Version Entry Template -->
                    <div v-for="entry in changelogEntries" :key="entry.version" class="border-l-4 border-blue-500 pl-6">
                        <div class="flex items-center mb-2">
                            <h2 class="text-xl font-semibold text-gray-900">{{ entry.version }}</h2>
                            <span class="ml-4 px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">{{ entry.date }}</span>
                            <span v-if="entry.type" class="ml-2 px-2 py-1 text-xs rounded-full"
                                  :class="getTypeClass(entry.type)">{{ entry.type }}</span>
                        </div>
                        <p class="text-gray-600 mb-4">{{ entry.description }}</p>

                        <!-- Features List -->
                        <div class="space-y-3">
                            <div v-for="(items, category) in entry.changes" :key="category">
                                <h4 class="font-medium text-gray-900 mb-2">{{ getCategoryTitle(category) }}</h4>
                                <ul class="space-y-1">
                                    <li v-for="item in items" :key="item" class="flex items-start">
                                        <span class="text-green-500 mr-2 mt-1">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                        <span class="text-gray-700">{{ item }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
    title: {
        type: String,
        default: 'Changelog'
    }
})

const changelogEntries = [
    {
        version: "v1.3.4",
        date: "5 Oktober 2025",
        type: "Major Update",
        description: "Peningkatan besar pada export Excel dengan data suara caleg per desa/kelurahan dan visual enhancement.",
        changes: {
            features: [
                "Export Excel menampilkan data suara caleg per desa/kelurahan (bukan per kecamatan)",
                "Data diambil dari tabel hr_dpr_ri_kec dengan parsing JSON untuk memecah suara per desa",
                "Header Excel 4-row structure: Kecamatan → Kode Kecamatan → Kode Desa → Nama Desa",
                "Auto-split multi-sheet dengan 50 desa per sheet untuk readability",
                "Sheet terakhir dilengkapi dengan kolom 'Total Suara' untuk summary",
                "Warna highlight untuk baris PARTAI (background biru muda + teks biru gelap)",
                "Warna highlight untuk baris TOTAL KESELURUHAN (background kuning + teks coklat)"
            ],
            improvements: [
                "Header kecamatan dengan merge cells untuk desa dalam kecamatan yang sama",
                "Kode kecamatan merged untuk visualisasi hierarki yang lebih baik",
                "Format angka dengan ribuan separator (1.389, 1.825) untuk kemudahan pembacaan",
                "Freeze panes di row 5 dan column J untuk navigasi yang lebih baik",
                "Optimasi query dengan JOIN untuk mendapatkan semua desa beserta vote data",
                "Filename export diubah menjadi 'Data_Suara_Caleg_per_Desa_[Kabupaten]_[Timestamp].xlsx'"
            ],
            technical: [
                "Mapping JSON structure: key = kel_kode, value = votes per caleg",
                "Parsing data dari hr_dpr_ri_kec.tbl field untuk extract vote per desa",
                "Grouping desa by kecamatan untuk header merging yang efficient",
                "Memory optimization untuk handle 150+ desa dalam satu kabupaten",
                "Testing terhadap multiple kabupaten: Aceh Barat Daya (152 desa, 4 sheet) & Jakarta Selatan (65 desa, 2 sheet)"
            ]
        }
    },
    {
        version: "v1.3.3",
        date: "4 Oktober 2025",
        type: "Feature Update",
        description: "Perbaikan filter data aktif dan optimasi tampilan statistik provinsi.",
        changes: {
            features: [
                "Filter provinsi berdasarkan status aktif (active='1') - menghilangkan provinsi 'Luar Negeri'",
                "Statistik section hanya menampilkan data dari provinsi yang aktif",
                "Penghapusan card 'Total DPT' dari section statistik provinsi"
            ],
            improvements: [
                "Query database dioptimasi untuk hanya menampilkan 38 provinsi aktif",
                "Total Kabupaten/Kota (515) hanya dari provinsi aktif dengan JOIN filter",
                "Total Dapil (84) hanya dari provinsi aktif dengan JOIN filter",
                "Total Kecamatan, Kelurahan, dan TPS hanya dari provinsi dengan active='1'",
                "Layout grid statistik disesuaikan dari 6 kolom menjadi 5 kolom"
            ],
            fixes: [
                "Perbaikan filter ENUM active menggunakan string '1' bukan integer 1",
                "Cache Laravel dibersihkan untuk memastikan perubahan diterapkan",
                "Build ulang asset untuk memperbarui tampilan frontend"
            ]
        }
    },
    {
        version: "v1.3.2",
        date: "28 September 2024",
        type: "Feature Update",
        description: "Penambahan kolom suara partai pada TPS export dan perbaikan struktur data.",
        changes: {
            features: [
                "Penambahan kolom 'Suara Partai' sebelum kolom suara caleg di setiap partai pada TPS export",
                "Data suara partai diambil dari hs_dpr_ri_tps.chart field jml_suara_partai",
                "Struktur export TPS sekarang: Suara Partai → Suara Caleg 1 → Suara Caleg 2 untuk setiap partai",
                "Menghapus limit 100 pada export TPS - sekarang menampilkan semua data TPS di kecamatan"
            ],
            improvements: [
                "Pemisahan yang lebih jelas antara suara partai dan suara kandidat individual",
                "Header Excel dengan format 'Suara Partai\\nNama Partai' untuk identifikasi yang lebih baik",
                "Export TPS lengkap tanpa batasan record untuk analisis data yang komprehensif"
            ]
        }
    },
    {
        version: "v1.3.1",
        date: "28 September 2024",
        type: "Hotfix",
        description: "Perbaikan bug pada Excel export dan JavaScript errors.",
        changes: {
            fixes: [
                "Perbaikan KecamatanTpsPartaiSheet untuk menggunakan jml_suara_total dari hs_dpr_ri_tps.chart",
                "Perbaikan TypeError: t.route is not a function pada AppLayout.vue",
                "Perbaikan TypeError: s(...) is not a function dengan menggunakan URL statis untuk changelog link"
            ],
            improvements: [
                "Data suara partai di Excel sekarang menggunakan field yang benar (jml_suara_total)",
                "Link changelog di footer menggunakan URL langsung untuk stabilitas"
            ]
        }
    },
    {
        version: "v1.3.0",
        date: "27 September 2024",
        type: "Major Update",
        description: "Peningkatan besar pada sistem export dan optimasi database untuk performa yang lebih baik.",
        changes: {
            features: [
                "Multi-sheet Excel export untuk data kabupaten dengan sheet terpisah per kecamatan",
                "Export TPS dengan data suara partai yang dioptimasi menggunakan tabel hs_dpr_ri_tps",
                "Implementasi 3-row header structure untuk export caleg (Partai, Nomor Urut, Nama Caleg)",
                "Penambahan filter caleg berdasarkan dapil (pdpr_wil_kec.dapil_id = dpr_ri_caleg.dapil_id)",
                "Menampilkan semua kandidat dalam dapil termasuk yang memiliki 0 suara"
            ],
            improvements: [
                "Optimasi database dengan penambahan index pada tabel hs_dpr_ri_tps",
                "Peningkatan performa query untuk akses data TPS",
                "Penanganan data kosong yang lebih baik (menampilkan sel kosong jika tidak ada data)",
                "Memory optimization untuk mencegah error pada dataset besar",
                "Peningkatan kecepatan loading export Excel"
            ],
            fixes: [
                "Perbaikan HTTP 500 error pada export TPS dengan dataset besar",
                "Perbaikan mapping vote data untuk TPS dengan kel_kode yang tepat",
                "Perbaikan struktur header Excel yang lebih informatif"
            ]
        }
    },
    {
        version: "v1.2.0",
        date: "26 September 2024",
        type: "Feature Update",
        description: "Penambahan fitur export Excel dan peningkatan antarmuka pengguna.",
        changes: {
            features: [
                "Export data suara kabupaten ke format Excel",
                "Export data suara kecamatan ke format Excel",
                "Export data TPS per kecamatan ke format Excel",
                "Styling dan formatting Excel dengan warna dan border yang professional",
                "Implementasi total dan summary pada export Excel"
            ],
            improvements: [
                "Peningkatan tampilan tabel dengan styling yang lebih baik",
                "Optimasi struktur data untuk export",
                "Penambahan informasi partisipasi pemilih"
            ]
        }
    },
    {
        version: "v1.1.0",
        date: "25 September 2024",
        type: "Content Update",
        description: "Penambahan halaman data suara dan navigasi yang lebih lengkap.",
        changes: {
            features: [
                "Halaman data suara per kabupaten dengan breakdown per kelurahan",
                "Halaman data suara per kecamatan dengan detail TPS",
                "Halaman data caleg dengan informasi lengkap kandidat",
                "Navigasi breadcrumb untuk kemudahan navigasi",
                "Tampilan statistik dan ringkasan data"
            ],
            improvements: [
                "Peningkatan responsive design untuk mobile",
                "Animasi loading dan transition yang smooth",
                "Optimasi query database untuk performa lebih baik"
            ]
        }
    },
    {
        version: "v1.0.0",
        date: "24 September 2024",
        type: "Initial Release",
        description: "Peluncuran awal Database Pemilu 2024 dengan fitur dasar untuk menampilkan data pemilu Indonesia.",
        changes: {
            features: [
                "Tampilan data provinsi dengan statistik lengkap",
                "Halaman data kabupaten/kota per provinsi",
                "Halaman data kecamatan per kabupaten",
                "Halaman data kelurahan per kecamatan",
                "Integrasi dengan database pemilu Indonesia",
                "Design responsive dengan Tailwind CSS",
                "Implementasi Vue.js 3 dan Inertia.js"
            ],
            technical: [
                "Framework Laravel 11/12 sebagai backend",
                "Vue.js 3 dengan Composition API",
                "Inertia.js untuk SPA experience",
                "Tailwind CSS untuk styling modern",
                "VueUse Motion untuk animasi smooth",
                "Database MySQL dengan struktur optimal"
            ]
        }
    }
]

const getCategoryTitle = (category) => {
    const titles = {
        features: '✨ Fitur Baru',
        improvements: '🚀 Peningkatan',
        fixes: '🔧 Perbaikan',
        technical: '⚙️ Teknis'
    }
    return titles[category] || category
}

const getTypeClass = (type) => {
    const classes = {
        'Major Update': 'bg-red-100 text-red-800',
        'Feature Update': 'bg-blue-100 text-blue-800',
        'Content Update': 'bg-green-100 text-green-800',
        'Initial Release': 'bg-purple-100 text-purple-800',
        'Hotfix': 'bg-orange-100 text-orange-800'
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}
</script>