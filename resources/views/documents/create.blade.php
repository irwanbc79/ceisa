<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 tracking-tight">
                    {{ isset($editDocument) ? __('Ubah Dokumen CEISA 4.0') : __('Perekaman Dokumen CEISA 4.0') }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    @isset($editDocument)
                        Mengubah dokumen {{ $editDocument->nomor_aju ?? 'draft #'.$editDocument->id }} — Host-to-Host (H2H) M2B
                    @else
                        Sistem Input Kepabeanan Host-to-Host (H2H) M2B
                    @endisset
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                    Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen" x-data="documentWizard()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-flash />

            @if ($errors->any())
                <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 shadow-sm text-sm text-rose-800 flex items-start gap-3">
                    <svg class="h-5 w-5 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <div>
                        <span class="font-semibold">Kesalahan Validasi ({{ $errors->count() }}):</span> perbaiki isian berikut lalu kirim ulang.
                        <ul class="mt-2 list-disc pl-5 space-y-0.5">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Client-side validation banner (Alpine) --}}
            <div x-show="formError" x-transition style="display:none"
                 class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 shadow-sm text-sm text-rose-800 flex items-start gap-3">
                <svg class="h-5 w-5 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <div><span class="font-semibold">Belum lengkap:</span> <span x-text="formError"></span></div>
            </div>

            {{-- Stepper Progress Bar --}}
            <div class="mb-8 bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <div class="relative flex items-center justify-between">
                    {{-- Progress Line Background --}}
                    <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-1 bg-slate-100 rounded"></div>
                    {{-- Active Progress Line --}}
                    <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-indigo-600 rounded transition-all duration-500"
                         :style="`width: ${((step - 1) / (steps.length - 1)) * 100}%`"></div>

                    {{-- Steps --}}
                    <template x-for="(s, idx) in steps" :key="idx">
                        <button type="button" @click="goToStep(idx + 1)"
                                :disabled="idx + 1 > step && !isStepValid(step)"
                                class="relative z-10 flex flex-col items-center group focus:outline-none disabled:cursor-not-allowed">
                            <div class="h-10 w-10 rounded-full flex items-center justify-center font-semibold text-sm border-2 transition-all duration-300"
                                 :class="step === idx + 1 
                                    ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-100 scale-110' 
                                    : (step > idx + 1 
                                        ? 'bg-emerald-500 border-emerald-500 text-white' 
                                        : 'bg-white border-slate-200 text-slate-400 group-hover:border-slate-300')">
                                <span x-show="step <= idx + 1" x-text="idx + 1"></span>
                                <svg x-show="step > idx + 1" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <span class="text-xs font-semibold mt-2 transition-colors duration-300"
                                  :class="step === idx + 1 ? 'text-indigo-600 font-bold' : (step > idx + 1 ? 'text-emerald-600' : 'text-slate-400')"
                                  x-text="s.title"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Main Layout: Form & JSON Preview --}}
            <div class="grid lg:grid-cols-3 gap-8 items-start">
                
                {{-- Form Section --}}
                <div class="lg:col-span-2 space-y-6">
                    <form method="POST" action="{{ isset($editDocument) ? route('documents.update', $editDocument) : route('documents.store') }}" id="ceisaDocForm" novalidate>
                        @csrf
                        @isset($editDocument)
                            @method('PUT')
                        @endisset
                        <input type="hidden" name="doc_type" :value="doc_type" />
                        <input type="hidden" name="submit_action" id="submit_action" value="submit" />

                        {{-- Step 1: Portal Selection --}}
                        <div x-show="step === 1" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 transition-all duration-300">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">Pilih Jenis Layanan Portal</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Tentukan jenis dokumen kepabeanan yang ingin Anda rekam</p>
                                </div>
                                <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-full border border-indigo-100">Tahap 1 dari 5</span>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <template x-for="item in docTypes" :key="item.code">
                                    <button type="button" @click="selectDocType(item.code)"
                                            class="flex items-start text-left p-5 border-2 rounded-2xl transition-all duration-300 hover:shadow-md group relative overflow-hidden"
                                            :class="doc_type === item.code 
                                                ? 'border-indigo-600 bg-indigo-50/50 shadow-sm ring-1 ring-indigo-600' 
                                                : 'border-slate-100 bg-white hover:border-slate-300'">
                                        <div class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                                             :class="doc_type === item.code ? 'bg-indigo-600 text-white' : 'bg-slate-50 text-slate-500 group-hover:bg-slate-100'">
                                            <span x-html="item.icon"></span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="font-bold text-slate-800 flex items-center gap-2">
                                                <span x-text="item.label"></span>
                                                <span class="text-[10px] uppercase px-1.5 py-0.5 font-extrabold rounded-md tracking-wider transition-colors"
                                                      :class="item.badgeClass" x-text="item.code"></span>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-1" x-text="item.description"></p>
                                        </div>
                                        
                                        {{-- Glow Effect --}}
                                        <div class="absolute -right-10 -bottom-10 h-24 w-24 rounded-full bg-indigo-100/30 blur-2xl transition-opacity duration-300 opacity-0 group-hover:opacity-100"
                                             x-show="doc_type === item.code"></div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        @include('documents.partials._step-entities')

                        @include('documents.partials._step-logistics')
                        @include('documents.partials._step-items')

                        @include('documents.partials._step-review')
                    </form>
                </div>

                @include('documents.partials._json-preview')

            </div>
        </div>

        {{-- =====================================================================
             MODAL: PREVIEW DRAFT DOKUMEN — Bergaya Dokumen Resmi Bea Cukai
             (harus berada DI DALAM scope x-data agar Alpine memprosesnya)
             ===================================================================== --}}
    @include('documents.partials._draft-modal')
    </div>{{-- /x-data documentWizard --}}

    @push('scripts')

    <script>
        function documentWizard() {
            return {
                step: 1,
                doc_type: 'BC30',
                showJson: false,
                showDraftModal: false,
                formError: '',
                steps: [
                    { title: 'Portal Layanan' },
                    { title: 'Data Entitas' },
                    { title: 'Logistik & Valuta' },
                    { title: 'Pos Barang' },
                    { title: 'Review & Submit' }
                ],
                docTypes: [
                    {
                        code: 'BC30',
                        label: 'BC 3.0 (Ekspor)',
                        description: 'Pemberitahuan Ekspor Barang (PEB) untuk pelaporan komoditas ke luar negeri.',
                        badgeClass: 'bg-indigo-100 text-indigo-800',
                        icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                               </svg>`
                    },
                    {
                        code: 'BC20',
                        label: 'BC 2.0 (Impor)',
                        description: 'Pemberitahuan Impor Barang (PIB) untuk penyelesaian kewajiban pabean barang masuk.',
                        badgeClass: 'bg-emerald-100 text-emerald-800',
                        icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" />
                               </svg>`
                    },
                    {
                        code: 'BC24',
                        label: 'BC 2.4 (Impor TPB)',
                        description: 'Impor Barang yang dimasukkan untuk ditimbun di Tempat Penimbunan Berikat.',
                        badgeClass: 'bg-blue-100 text-blue-800',
                        icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                               </svg>`
                    },
                    {
                        code: 'TPB',
                        label: 'Portal TPB',
                        description: 'Perekaman dokumen TPB (BC 2.3, BC 2.5, BC 2.7, BC 4.0) khusus logistik berikat.',
                        badgeClass: 'bg-violet-100 text-violet-800',
                        icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h18" />
                               </svg>`
                    },
                    {
                        code: 'RUSH',
                        label: 'Rush Handling',
                        description: 'Pengajuan persetujuan pengeluaran barang segera karena sifatnya yang mendesak.',
                        badgeClass: 'bg-rose-100 text-rose-800',
                        icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                               </svg>`
                    }
                ],

                // Referensi diambil dari tabel ceisa_references (server-side; dapat disinkron dari API CEISA).
                references: @json($references ?? new \stdClass()),
                
                // Form Fields Data Model
                formData: {
                    // BC30 — Header klasifikasi ekspor (CEISA 4.0)
                    kantor_muat: '',
                    jenis_ekspor: 'Biasa',
                    kategori_ekspor: 'Umum',
                    cara_dagang: 'Biasa',
                    cara_bayar: 'Biasa/Tunai',
                    komoditi: 'NON_MIGAS',
                    curah: 'NON_CURAH',
                    // BC30 — Entitas
                    nama_eksportir: '',
                    npwp_eksportir: '',
                    alamat_eksportir: '',
                    nama_penerima: '',
                    negara_tujuan: '',
                    alamat_penerima: '',
                    // BC30 — Pengangkut
                    cara_angkut: 'Laut',
                    nama_sarana: '',
                    voy_flight: '',
                    pelabuhan_tujuan: '',
                    tanggal_ekspor: '',
                    // BC30 — Transaksi tambahan
                    ndpbm: '',
                    incoterm: 'FOB',
                    freight: '',
                    asuransi_jenis: 'DN',
                    nilai_asuransi: '',
                    bruto: '',
                    bank_devisa: '',
                    // BC30 — Pernyataan
                    pernyataan_nama: '',
                    pernyataan_jabatan: '',
                    pernyataan_kota: '',

                    // BC20 / BC24
                    nama_importir: '',
                    npwp_importir: '',
                    alamat_importir: '',
                    nama_pemasok: '',
                    negara_pemasok: '',
                    nib_importir: '',
                    jenis_api: '',
                    jenis_impor: '',
                    cara_bayar: '',
                    kode_bendera: '',
                    kode_tps: '',
                    tanggal_tiba: '',

                    // TPB
                    nama_tpb: '',
                    npwp_tpb: '',
                    alamat_tpb: '',
                    jenis_tpb: '',
                    tujuan_tpb: '',
                    dokumen_referensi: '',

                    // RUSH
                    nama_pemohon: '',
                    npwp_pemohon: '',
                    alamat_pemohon: '',
                    nama_sarana_pengangkut: '',
                    nomor_flight: '',
                    nomor_awb_bl: '',
                    tanggal_awb_bl: '',
                    alasan_segera: '',
                    jumlah_kemasan: 1,
                    jenis_kemasan: '',

                    // Common
                    nomor_aju: '',
                    kode_kantor: '',
                    pelabuhan_muat: '',
                    pelabuhan_bongkar: '',
                    kode_valuta: 'USD',
                    nilai_fob: '',
                    nilai_cif: '',
                    nilai_barang: '',
                    cara_pembayaran: '',
                    
                    // Pos Barang
                    barang: [
                        { hs_code: '', uraian: '', merk: '', tipe: '', ukuran: '', negara_asal: '', daerah_asal: '', jumlah_satuan: '', kode_satuan: '', jumlah_kemasan: '', kode_kemasan: '', netto: '', volume: '', nilai_fob: '', nilai_cif: '', nilai_barang: '' }
                    ]
                },

                // Repopulasi dari input lama bila submit gagal validasi server,
                // agar data tidak hilang & user langsung melihat daftar error.
                init() {
                    // Mode edit: pre-fill dari payload dokumen yang ada.
                    const editData = @json($editData ?? null);
                    if (editData) {
                        if (editData.doc_type) this.doc_type = editData.doc_type;
                        if (Array.isArray(editData.barang) && editData.barang.length) {
                            this.formData.barang = editData.barang.map(b => ({ ...this.formData.barang[0], ...b }));
                        }
                        for (const k in this.formData) {
                            if (k === 'barang') continue;
                            if (editData[k] !== undefined && editData[k] !== null && editData[k] !== '') {
                                this.formData[k] = editData[k];
                            }
                        }
                    }

                    const old = @json(old());
                    if (old && Object.keys(old).length) {
                        if (old.doc_type) this.doc_type = old.doc_type;
                        for (const k in this.formData) {
                            if (old[k] !== undefined && old[k] !== null && old[k] !== '') {
                                this.formData[k] = old[k];
                            }
                        }
                        @if ($errors->any())
                            const errorKeys = @json($errors->keys());
                            const stepMap = {
                                'kantor_muat': 2, 'jenis_ekspor': 2, 'kategori_ekspor': 2, 'cara_dagang': 2, 'cara_bayar': 2, 'komoditi': 2, 'curah': 2,
                                'nama_eksportir': 2, 'npwp_eksportir': 2, 'alamat_eksportir': 2, 'nama_penerima': 2, 'negara_tujuan': 2, 'alamat_penerima': 2,
                                'nama_importir': 2, 'npwp_importir': 2, 'alamat_importir': 2, 'nama_pemasok': 2, 'negara_pemasok': 2,
                                'nama_tpb': 2, 'npwp_tpb': 2, 'alamat_tpb': 2, 'jenis_tpb': 2, 'tujuan_tpb': 2, 'dokumen_referensi': 2,
                                'nama_pemohon': 2, 'npwp_pemohon': 2, 'alamat_pemohon': 2, 'alasan_segera': 2,
                                
                                'cara_angkut': 3, 'nama_sarana': 3, 'voy_flight': 3, 'pelabuhan_muat': 3, 'pelabuhan_bongkar': 3, 'pelabuhan_tujuan': 3, 'tanggal_ekspor': 3,
                                'kode_valuta': 3, 'ndpbm': 3, 'incoterm': 3, 'nilai_fob': 3, 'freight': 3, 'asuransi_jenis': 3, 'nilai_asuransi': 3, 'bruto': 3, 'bank_devisa': 3,
                                'nilai_cif': 3, 'nilai_barang': 3, 'cara_pembayaran': 3,
                                'nama_sarana_pengangkut': 3, 'nomor_flight': 3, 'nomor_awb_bl': 3, 'tanggal_awb_bl': 3, 'jumlah_kemasan': 3, 'jenis_kemasan': 3,
                                
                                'barang': 4,
                                
                                'pernyataan_nama': 5, 'pernyataan_jabatan': 5, 'pernyataan_kota': 5
                            };
                            
                            let targetStep = 5;
                            for (const key of errorKeys) {
                                if (key.startsWith('barang.')) {
                                    targetStep = 4;
                                    break;
                                }
                                if (stepMap[key]) {
                                    targetStep = stepMap[key];
                                    break;
                                }
                            }
                            this.step = targetStep;
                            
                            const firstErrorKey = errorKeys.find(k => !k.startsWith('barang.'));
                            if (firstErrorKey) {
                                setTimeout(() => {
                                    const element = document.getElementById(firstErrorKey);
                                    if (element) {
                                        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                        element.focus({ preventScroll: true });
                                    }
                                }, 300);
                            }
                        @endif
                    }
                },

                selectDocType(code) {
                    this.doc_type = code;
                    // Reset or adapt validation states as needed
                },

                goToStep(s) {
                    if (s < this.step || this.isStepValid(this.step)) {
                        this.step = s;
                    }
                },

                nextStep() {
                    if (this.isStepValid(this.step)) {
                        this.step++;
                    }
                },

                prevStep() {
                    if (this.step > 1) {
                        this.step--;
                    }
                },

                isStepValid(s) {
                    if (s === 2) {
                        if (this.doc_type === 'BC30') {
                            return this.formData.kantor_muat && this.formData.jenis_ekspor && this.formData.kategori_ekspor && this.formData.cara_bayar
                                && this.formData.nama_eksportir && this.formData.npwp_eksportir && this.formData.alamat_eksportir
                                && this.formData.nama_penerima && this.formData.negara_tujuan;
                        }
                        if (this.doc_type === 'BC20' || this.doc_type === 'BC24') {
                            return this.formData.nama_importir && this.formData.npwp_importir && this.formData.nama_pemasok && this.formData.negara_pemasok;
                        }
                        if (this.doc_type === 'TPB') {
                            return this.formData.nama_tpb && this.formData.npwp_tpb && this.formData.jenis_tpb;
                        }
                        if (this.doc_type === 'RUSH') {
                            return this.formData.nama_pemohon && this.formData.npwp_pemohon && this.formData.alasan_segera;
                        }
                    }
                    if (s === 3) {
                        if (this.doc_type === 'BC30') {
                            return this.formData.pelabuhan_muat && this.formData.pelabuhan_tujuan && this.formData.kode_valuta
                                && this.formData.ndpbm && this.formData.incoterm && this.formData.nilai_fob && this.formData.bruto;
                        }
                        if (this.doc_type === 'BC20' || this.doc_type === 'BC24') {
                            return this.formData.pelabuhan_muat && this.formData.pelabuhan_bongkar && this.formData.nilai_cif;
                        }
                        if (this.doc_type === 'TPB') {
                            return this.formData.nilai_barang;
                        }
                        if (this.doc_type === 'RUSH') {
                            return this.formData.nama_sarana_pengangkut && this.formData.nomor_awb_bl;
                        }
                    }
                    return true;
                },

                addItem() {
                    this.formData.barang.push({
                        hs_code: '', uraian: '', merk: '', tipe: '', ukuran: '', negara_asal: '', daerah_asal: '', jumlah_satuan: '', kode_satuan: '', jumlah_kemasan: '', kode_kemasan: '', netto: '', volume: '', nilai_fob: '', nilai_cif: '', nilai_barang: ''
                    });
                },

                copyItem(idx) {
                    const cloned = JSON.parse(JSON.stringify(this.formData.barang[idx]));
                    this.formData.barang.splice(idx + 1, 0, cloned);
                },

                removeItem(idx) {
                    this.formData.barang.splice(idx, 1);
                },

                calculateTotalNetto() {
                    return this.formData.barang.reduce((acc, curr) => acc + (parseFloat(curr.netto) || 0), 0);
                },

                calculateTotalValue() {
                    let field = 'nilai_barang';
                    if (this.doc_type === 'BC30') field = 'nilai_fob';
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') field = 'nilai_cif';
                    
                    const total = this.formData.barang.reduce((acc, curr) => acc + (parseFloat(curr[field]) || 0), 0);
                    
                    // Sync total value back to header
                    if (this.doc_type === 'BC30') this.formData.nilai_fob = total;
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') this.formData.nilai_cif = total;
                    if (this.doc_type === 'TPB' || this.doc_type === 'RUSH') this.formData.nilai_barang = total;
                    
                    return total.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                // Generate CEISA Payload for real-time JSON display
                generateLivePayload() {
                    const docType = this.doc_type;
                    const f = this.formData;
                    
                    if (docType === 'BC30') {
                        return {
                            header: {
                                kantor_muat: f.kantor_muat,
                                jenis_ekspor: f.jenis_ekspor,
                                kategori_ekspor: f.kategori_ekspor,
                                cara_dagang: f.cara_dagang || null,
                                cara_bayar: f.cara_bayar,
                                komoditi: f.komoditi,
                                curah: f.curah,
                                eksportir: { nama: f.nama_eksportir, npwp: f.npwp_eksportir, alamat: f.alamat_eksportir },
                                penerima: { nama: f.nama_penerima, negara: (f.negara_tujuan || '').toUpperCase(), alamat: f.alamat_penerima || null },
                                pengangkutan: {
                                    cara_angkut: f.cara_angkut || null,
                                    sarana_angkut: f.nama_sarana || null,
                                    voy_flight: f.voy_flight || null,
                                    pelabuhan_muat: f.pelabuhan_muat,
                                    pelabuhan_bongkar: f.pelabuhan_bongkar || null,
                                    pelabuhan_tujuan: f.pelabuhan_tujuan,
                                    tanggal_ekspor: f.tanggal_ekspor || null
                                },
                                valuta: (f.kode_valuta || '').toUpperCase(),
                                ndpbm: parseFloat(f.ndpbm) || 0.0,
                                incoterm: (f.incoterm || '').toUpperCase(),
                                nilai_fob: parseFloat(f.nilai_fob) || 0.0,
                                freight: f.freight !== '' ? (parseFloat(f.freight) || 0.0) : null,
                                asuransi: { jenis: f.asuransi_jenis || null, nilai: f.nilai_asuransi !== '' ? (parseFloat(f.nilai_asuransi) || 0.0) : null },
                                bruto: parseFloat(f.bruto) || 0.0,
                                bank_devisa: f.bank_devisa || null,
                                cara_pembayaran: f.cara_pembayaran || null,
                                pernyataan: { nama: f.pernyataan_nama, jabatan: f.pernyataan_jabatan, kota: f.pernyataan_kota || null }
                            },
                            barang: f.barang.map((b, i) => {
                                const fob = parseFloat(b.nilai_fob) || 0.0;
                                const qty = parseFloat(b.jumlah_satuan) || 0.0;
                                return {
                                    seri: i + 1,
                                    hs_code: b.hs_code,
                                    uraian: b.uraian,
                                    merk: b.merk || null,
                                    tipe: b.tipe || null,
                                    ukuran: b.ukuran || null,
                                    negara_asal: b.negara_asal ? b.negara_asal.toUpperCase() : null,
                                    daerah_asal: b.daerah_asal || null,
                                    jumlah_satuan: qty,
                                    kode_satuan: b.kode_satuan,
                                    jumlah_kemasan: b.jumlah_kemasan !== '' ? (parseFloat(b.jumlah_kemasan) || 0.0) : null,
                                    kode_kemasan: b.kode_kemasan || null,
                                    netto: parseFloat(b.netto) || 0.0,
                                    volume: b.volume !== '' ? (parseFloat(b.volume) || 0.0) : null,
                                    nilai_fob: fob,
                                    harga_satuan: qty > 0 ? Math.round((fob / qty) * 10000) / 10000 : 0.0
                                };
                            })
                        };
                    } else if (docType === 'BC20' || docType === 'BC24') {
                        return {
                            header: {
                                importir: { nama: f.nama_importir, npwp: f.npwp_importir, alamat: f.alamat_importir },
                                pemasok: { nama: f.nama_pemasok, negara: (f.negara_pemasok || '').toUpperCase() },
                                pengangkutan: { pelabuhan_muat: f.pelabuhan_muat, pelabuhan_bongkar: f.pelabuhan_bongkar },
                                valuta: (f.kode_valuta || '').toUpperCase(),
                                nilai_cif: parseFloat(f.nilai_cif) || 0.0,
                                cara_pembayaran: f.cara_pembayaran || null
                            },
                            barang: f.barang.map((b, i) => ({
                                seri: i + 1,
                                hs_code: b.hs_code,
                                uraian: b.uraian,
                                jumlah_satuan: parseFloat(b.jumlah_satuan) || 0.0,
                                kode_satuan: b.kode_satuan,
                                netto: parseFloat(b.netto) || 0.0,
                                nilai_cif: parseFloat(b.nilai_cif) || 0.0
                            }))
                        };
                    } else if (docType === 'TPB') {
                        return {
                            header: {
                                pengusaha_tpb: { nama: f.nama_tpb, npwp: f.npwp_tpb, alamat: f.alamat_tpb },
                                jenis_tpb: f.jenis_tpb,
                                tujuan_pengiriman: f.tujuan_tpb,
                                dokumen_referensi: f.dokumen_referensi,
                                valuta: (f.kode_valuta || '').toUpperCase(),
                                nilai_barang: parseFloat(f.nilai_barang) || 0.0
                            },
                            barang: f.barang.map((b, i) => ({
                                seri: i + 1,
                                hs_code: b.hs_code,
                                uraian: b.uraian,
                                jumlah_satuan: parseFloat(b.jumlah_satuan) || 0.0,
                                kode_satuan: b.kode_satuan,
                                netto: parseFloat(b.netto) || 0.0,
                                nilai_barang: parseFloat(b.nilai_barang) || 0.0
                            }))
                        };
                    } else if (docType === 'RUSH') {
                        return {
                            header: {
                                pemohon: { nama: f.nama_pemohon, npwp: f.npwp_pemohon, alamat: f.alamat_pemohon },
                                pengangkutan: { sarana: f.nama_sarana_pengangkut, flight_no: f.nomor_flight },
                                dokumen_pengangkutan: { awb_bl: f.nomor_awb_bl, tanggal: f.tanggal_awb_bl },
                                alasan_rush_handling: f.alasan_segera,
                                kemasan: { jumlah: parseInt(f.jumlah_kemasan) || 1, jenis: f.jenis_kemasan }
                            },
                            barang: f.barang.map((b, i) => ({
                                seri: i + 1,
                                hs_code: b.hs_code,
                                uraian: b.uraian,
                                jumlah_satuan: parseFloat(b.jumlah_satuan) || 0.0,
                                kode_satuan: b.kode_satuan,
                                netto: parseFloat(b.netto) || 0.0,
                                nilai_barang: parseFloat(b.nilai_barang) || 0.0
                            }))
                        };
                    }
                },

                // Mock Data Injector
                loadSampleData() {
                    const docType = this.doc_type;
                    
                    if (docType === 'BC30') {
                        this.formData.kantor_muat = '050100';
                        this.formData.jenis_ekspor = 'Biasa';
                        this.formData.kategori_ekspor = 'Umum';
                        this.formData.cara_dagang = 'Biasa';
                        this.formData.cara_bayar = 'Biasa/Tunai';
                        this.formData.komoditi = 'NON_MIGAS';
                        this.formData.curah = 'NON_CURAH';
                        this.formData.nama_eksportir = 'PT Mora Multi Berkah';
                        this.formData.npwp_eksportir = '012345678901000';
                        this.formData.alamat_eksportir = 'Jl. Kemang Timur No. 45, Mampang Prapatan, Jakarta Selatan';
                        this.formData.nama_penerima = 'Global Trade Logistics Pte Ltd';
                        this.formData.negara_tujuan = 'SG';
                        this.formData.alamat_penerima = '8 Marina Boulevard, Singapore 018981';
                        this.formData.cara_angkut = 'Laut';
                        this.formData.nama_sarana = 'MV Sinar Bintang';
                        this.formData.voy_flight = 'V-1024E';
                        this.formData.pelabuhan_muat = 'IDJKT';
                        this.formData.pelabuhan_bongkar = 'SGSIN';
                        this.formData.pelabuhan_tujuan = 'SGSIN';
                        this.formData.tanggal_ekspor = '2026-06-20';
                        this.formData.kode_valuta = 'USD';
                        this.formData.ndpbm = 15800;
                        this.formData.incoterm = 'FOB';
                        this.formData.nilai_fob = 12500.00;
                        this.formData.freight = 350.00;
                        this.formData.asuransi_jenis = 'DN';
                        this.formData.nilai_asuransi = 125.00;
                        this.formData.bruto = 135.5;
                        this.formData.bank_devisa = 'Bank Mandiri';
                        this.formData.cara_pembayaran = 'L/C';
                        this.formData.pernyataan_nama = 'Irwan';
                        this.formData.pernyataan_jabatan = 'Direktur';
                        this.formData.pernyataan_kota = 'Jakarta';
                        this.formData.barang = [
                            { hs_code: '6109100000', uraian: 'Kaos Katun Premium Polos M2B', merk: 'M2B', tipe: 'Round Neck', ukuran: 'All Size', negara_asal: 'ID', daerah_asal: 'Jawa Barat', jumlah_satuan: 500, kode_satuan: 'PCE', jumlah_kemasan: 20, kode_kemasan: 'CT', netto: 120, volume: 2.5, nilai_fob: 12500.00 }
                        ];
                    } else if (docType === 'BC20') {
                        this.formData.nama_importir = 'PT Mora Multi Berkah';
                        this.formData.npwp_importir = '012345678901000';
                        this.formData.alamat_importir = 'Jl. Kemang Timur No. 45, Mampang Prapatan, Jakarta Selatan';
                        this.formData.nama_pemasok = 'Tokyo Machinery Industrial Corp';
                        this.formData.negara_pemasok = 'JP';
                        this.formData.pelabuhan_muat = 'JPTYO';
                        this.formData.pelabuhan_bongkar = 'IDTPP';
                        this.formData.kode_valuta = 'JPY';
                        this.formData.nilai_cif = 1800000.00;
                        this.formData.cara_pembayaran = 'Telegraphic Transfer (TT)';
                        this.formData.barang = [
                            { hs_code: '8471302000', uraian: 'Unit Laptop Kantor Core i7 16GB RAM', jumlah_satuan: 15, kode_satuan: 'UNT', netto: 35, nilai_cif: 1800000.00 }
                        ];
                    } else if (docType === 'BC24') {
                        this.formData.nama_importir = 'PT Mora Multi Berkah';
                        this.formData.npwp_importir = '012345678901000';
                        this.formData.alamat_importir = 'Jl. Kemang Timur No. 45, Mampang Prapatan, Jakarta Selatan';
                        this.formData.nama_pemasok = 'Shenzhen Electronic Components Ltd';
                        this.formData.negara_pemasok = 'CN';
                        this.formData.pelabuhan_muat = 'CNSZN';
                        this.formData.pelabuhan_bongkar = 'IDTPP';
                        this.formData.kode_valuta = 'USD';
                        this.formData.nilai_cif = 42500.00;
                        this.formData.cara_pembayaran = 'Open Account';
                        this.formData.barang = [
                            { hs_code: '8541410000', uraian: 'Modul LED Display P3.9 Outdoor', jumlah_satuan: 1200, kode_satuan: 'PCE', netto: 180, nilai_cif: 42500.00 }
                        ];
                    } else if (docType === 'TPB') {
                        this.formData.nama_tpb = 'PT Mora Multi Berkah (Kawasan Berikat)';
                        this.formData.npwp_tpb = '012345678901000';
                        this.formData.alamat_tpb = 'Kawasan Industri Jababeka Tahap II Blok B-12, Cikarang, Bekasi';
                        this.formData.jenis_tpb = 'Kawasan Berikat (KB)';
                        this.formData.tujuan_tpb = 'Pengeluaran Hasil Produksi ke TLDDP (BC 2.5)';
                        this.formData.dokumen_referensi = 'Kontrak Penjualan No. KB-889/M2B/VI/2026';
                        this.formData.kode_valuta = 'USD';
                        this.formData.nilai_barang = 31500.00;
                        this.formData.barang = [
                            { hs_code: '3926909990', uraian: 'Plastic Parts Injection Molded Automotive', jumlah_satuan: 10000, kode_satuan: 'PCE', netto: 250, nilai_barang: 31500.00 }
                        ];
                    } else if (docType === 'RUSH') {
                        this.formData.nama_pemohon = 'PT Mora Multi Berkah';
                        this.formData.npwp_pemohon = '012345678901000';
                        this.formData.alamat_pemohon = 'Jl. Kemang Timur No. 45, Mampang Prapatan, Jakarta Selatan';
                        this.formData.nama_sarana_pengangkut = 'Singapore Airlines';
                        this.formData.nomor_flight = 'SQ-956';
                        this.formData.nomor_awb_bl = 'AWB-618-9920188';
                        this.formData.tanggal_awb_bl = '2026-06-15';
                        this.formData.alasan_segera = 'Vaksin / Serum / Obat-obatan Kritis';
                        this.formData.jumlah_kemasan = 4;
                        this.formData.jenis_kemasan = 'CO';
                        this.formData.barang = [
                            { hs_code: '3002200000', uraian: 'Vaksin Influenza Impor (Suhu Dingin)', jumlah_satuan: 2000, kode_satuan: 'VIA', netto: 12, nilai_barang: 8500.00 }
                        ];
                    }
                },

                // Field nilai transaksi per jenis dokumen.
                valueField() {
                    if (this.doc_type === 'BC30') return 'nilai_fob';
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') return 'nilai_cif';
                    return 'nilai_barang';
                },

                // Validasi ringan sisi klien. Mengembalikan {step, message} bila ada
                // yang belum lengkap, atau null bila valid. Hanya memeriksa field
                // yang relevan dengan doc_type (field tersembunyi diabaikan).
                firstInvalidStep() {
                    if (!this.isStepValid(2)) {
                        return { step: 2, message: 'Lengkapi data identitas entitas (Tahap 2) terlebih dahulu.' };
                    }
                    if (!this.isStepValid(3)) {
                        return { step: 3, message: 'Lengkapi data pengangkutan & nilai transaksi (Tahap 3).' };
                    }
                    if (!this.formData.barang.length) {
                        return { step: 4, message: 'Tambahkan minimal satu pos barang (Tahap 4).' };
                    }
                    const vf = this.valueField();
                    for (let i = 0; i < this.formData.barang.length; i++) {
                        const b = this.formData.barang[i];
                        if (!b.hs_code || !b.uraian || !b.jumlah_satuan || !b.kode_satuan || !b.netto || !b[vf]) {
                            return { step: 4, message: `Lengkapi seluruh isian pada Pos Barang #${i + 1} (Tahap 4).` };
                        }
                    }
                    if (this.doc_type === 'BC30' && (!this.formData.pernyataan_nama || !this.formData.pernyataan_jabatan)) {
                        return { step: 5, message: 'Lengkapi Pernyataan Penanggung Jawab (nama & jabatan) di Tahap 5.' };
                    }
                    return null;
                },

                // Buka modal preview (read-only) — selalu merespons.
                openDraftPreview() {
                    this.formError = '';
                    this.showDraftModal = true;
                },

                // Simpan sebagai draft (dari dalam modal preview).
                confirmSaveDraft() {
                    this.submitForm('draft');
                },

                // Submit form: validasi relevan dulu, lalu kirim ke server.
                submitForm(action = 'submit') {
                    const invalid = this.firstInvalidStep();
                    if (invalid) {
                        this.showDraftModal = false;
                        this.step = invalid.step;
                        this.formError = invalid.message;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }
                    this.formError = '';
                    document.getElementById('submit_action').value = action;
                    document.getElementById('ceisaDocForm').submit();
                },

                // Get party name for display in modal
                getPartyName() {
                    const f = this.formData;
                    if (this.doc_type === 'BC30') return f.nama_eksportir || '—';
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') return f.nama_importir || '—';
                    if (this.doc_type === 'TPB') return f.nama_tpb || '—';
                    if (this.doc_type === 'RUSH') return f.nama_pemohon || '—';
                    return '—';
                },

                getPartyNPWP() {
                    const f = this.formData;
                    if (this.doc_type === 'BC30') return f.npwp_eksportir || '—';
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') return f.npwp_importir || '—';
                    if (this.doc_type === 'TPB') return f.npwp_tpb || '—';
                    if (this.doc_type === 'RUSH') return f.npwp_pemohon || '—';
                    return '—';
                },

                getCounterPartyName() {
                    const f = this.formData;
                    if (this.doc_type === 'BC30') return f.nama_penerima || '—';
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') return f.nama_pemasok || '—';
                    return '—';
                },

                getDocTypeLabel() {
                    const map = { BC30: 'BC 3.0 — Pemberitahuan Ekspor Barang', BC20: 'BC 2.0 — Pemberitahuan Impor Barang', BC24: 'BC 2.4 — Impor Barang TPB', TPB: 'Portal TPB', RUSH: 'Rush Handling' };
                    return map[this.doc_type] || this.doc_type;
                },

                getTotalValue() {
                    let field = 'nilai_barang';
                    if (this.doc_type === 'BC30') field = 'nilai_fob';
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') field = 'nilai_cif';
                    return this.formData.barang.reduce((acc, b) => acc + (parseFloat(b[field]) || 0), 0).toLocaleString('id-ID', {minimumFractionDigits: 2});
                },

                getValueLabel() {
                    if (this.doc_type === 'BC30') return 'Nilai FOB Total';
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') return 'Nilai CIF Total';
                    return 'Nilai Barang Total';
                },

                todayFormatted() {
                    return new Date().toLocaleDateString('id-ID', {day:'2-digit', month:'2-digit', year:'numeric'});
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
