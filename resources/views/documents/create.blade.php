<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Buat Dokumen BC 3.0 — PEB Ekspor') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <x-flash />

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    Terdapat {{ $errors->count() }} kesalahan input. Periksa kembali isian di bawah.
                </div>
            @endif

            <form method="POST" action="{{ route('documents.store') }}"
                  x-data="pebForm()" class="space-y-6">
                @csrf
                <input type="hidden" name="doc_type" value="BC30" />

                {{-- Data Eksportir --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Data Eksportir</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="nama_eksportir" value="Nama Eksportir" />
                            <x-text-input id="nama_eksportir" name="nama_eksportir" class="mt-1 block w-full" :value="old('nama_eksportir')" required />
                            <x-input-error :messages="$errors->get('nama_eksportir')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="npwp_eksportir" value="NPWP Eksportir" />
                            <x-text-input id="npwp_eksportir" name="npwp_eksportir" class="mt-1 block w-full" :value="old('npwp_eksportir')" required />
                            <x-input-error :messages="$errors->get('npwp_eksportir')" class="mt-2" />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="alamat_eksportir" value="Alamat Eksportir" />
                            <textarea id="alamat_eksportir" name="alamat_eksportir" rows="2"
                                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>{{ old('alamat_eksportir') }}</textarea>
                            <x-input-error :messages="$errors->get('alamat_eksportir')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Penerima & Tujuan --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Penerima &amp; Tujuan</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="nama_penerima" value="Nama Penerima (Consignee)" />
                            <x-text-input id="nama_penerima" name="nama_penerima" class="mt-1 block w-full" :value="old('nama_penerima')" required />
                            <x-input-error :messages="$errors->get('nama_penerima')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="negara_tujuan" value="Kode Negara Tujuan (ISO 2, mis. SG)" />
                            <x-text-input id="negara_tujuan" name="negara_tujuan" maxlength="2" class="mt-1 block w-full uppercase" :value="old('negara_tujuan')" required />
                            <x-input-error :messages="$errors->get('negara_tujuan')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="pelabuhan_muat" value="Kode Pelabuhan Muat" />
                            <x-text-input id="pelabuhan_muat" name="pelabuhan_muat" class="mt-1 block w-full" :value="old('pelabuhan_muat')" required />
                            <x-input-error :messages="$errors->get('pelabuhan_muat')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="pelabuhan_bongkar" value="Kode Pelabuhan Bongkar (opsional)" />
                            <x-text-input id="pelabuhan_bongkar" name="pelabuhan_bongkar" class="mt-1 block w-full" :value="old('pelabuhan_bongkar')" />
                            <x-input-error :messages="$errors->get('pelabuhan_bongkar')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Nilai Transaksi --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Nilai Transaksi</h3>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="kode_valuta" value="Valuta (ISO 4217, mis. USD)" />
                            <x-text-input id="kode_valuta" name="kode_valuta" maxlength="3" class="mt-1 block w-full uppercase" :value="old('kode_valuta', 'USD')" required />
                            <x-input-error :messages="$errors->get('kode_valuta')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="nilai_fob" value="Nilai FOB" />
                            <x-text-input id="nilai_fob" name="nilai_fob" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('nilai_fob')" required />
                            <x-input-error :messages="$errors->get('nilai_fob')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="cara_pembayaran" value="Cara Pembayaran (opsional)" />
                            <x-text-input id="cara_pembayaran" name="cara_pembayaran" class="mt-1 block w-full" :value="old('cara_pembayaran')" />
                            <x-input-error :messages="$errors->get('cara_pembayaran')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Barang --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-900">Detail Barang</h3>
                        <button type="button" @click="addItem()"
                                class="text-sm px-3 py-1.5 bg-indigo-600 text-white rounded-md hover:bg-indigo-500">
                            + Tambah Barang
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('barang')" class="mb-2" />

                    <div class="space-y-4">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="border border-gray-200 rounded-md p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-medium text-gray-600">Barang #<span x-text="index + 1"></span></span>
                                    <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                            class="text-xs text-red-600 hover:underline">Hapus</button>
                                </div>
                                <div class="grid md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs text-gray-500">Kode HS</label>
                                        <input :name="`barang[${index}][hs_code]`" x-model="item.hs_code"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs text-gray-500">Uraian Barang</label>
                                        <input :name="`barang[${index}][uraian]`" x-model="item.uraian"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500">Jumlah Satuan</label>
                                        <input type="number" step="0.01" min="0" :name="`barang[${index}][jumlah_satuan]`" x-model="item.jumlah_satuan"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500">Kode Satuan (mis. PCE, KGM)</label>
                                        <input :name="`barang[${index}][kode_satuan]`" x-model="item.kode_satuan"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500">Netto (kg)</label>
                                        <input type="number" step="0.01" min="0" :name="`barang[${index}][netto]`" x-model="item.netto"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500">Nilai FOB Barang</label>
                                        <input type="number" step="0.01" min="0" :name="`barang[${index}][nilai_fob]`" x-model="item.nilai_fob"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:underline">Batal</a>
                    <x-primary-button>Simpan &amp; Kirim ke CEISA</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function pebForm() {
            return {
                items: [{ hs_code: '', uraian: '', jumlah_satuan: '', kode_satuan: '', netto: '', nilai_fob: '' }],
                addItem() {
                    this.items.push({ hs_code: '', uraian: '', jumlah_satuan: '', kode_satuan: '', netto: '', nilai_fob: '' });
                },
                removeItem(i) {
                    this.items.splice(i, 1);
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
