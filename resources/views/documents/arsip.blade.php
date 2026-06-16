<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 tracking-tight">Arsip Dokumen Lama</h2>
                <p class="text-sm text-slate-500 mt-1">Rekam manual dokumen PIB/PEB yang sudah pernah diajukan di portal Bea Cukai ke riwayat M2B</p>
            </div>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">&larr; Dashboard</a>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="rounded-xl bg-indigo-50 border border-indigo-100 p-4 text-xs text-indigo-900">
                Fitur ini hanya <strong>mencatat</strong> dokumen lama ke database M2B agar muncul di riwayat &amp; Dashboard.
                Dokumen <strong>tidak</strong> dikirim ulang ke CEISA.
            </div>

            @if ($errors->any())
                <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                    <span class="font-semibold">Periksa kembali isian:</span>
                    <ul class="mt-2 list-disc pl-5 space-y-0.5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('documents.archive.store') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-6">
                @csrf

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="doc_type" value="Jenis Dokumen" />
                        <select id="doc_type" name="doc_type" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required>
                            <option value="">-- Pilih Jenis --</option>
                            @foreach ($docTypes as $code => $label)
                                <option value="{{ $code }}" @selected(old('doc_type') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="status" value="Status Akhir" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="submitted" @selected(old('status') === 'submitted')>Terkirim / Proses</option>
                            <option value="accepted" @selected(old('status') === 'accepted')>Diterima (SPPB / Selesai)</option>
                            <option value="rejected" @selected(old('status') === 'rejected')>Ditolak</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="nomor_aju" value="Nomor Aju / Pengajuan" />
                        <input type="text" id="nomor_aju" name="nomor_aju" value="{{ old('nomor_aju', request('nomor_aju')) }}" placeholder="mis. 301012B628EF20260611000001" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm font-mono" required />
                    </div>
                    <div>
                        <x-input-label for="nomor_daftar" value="Nomor Pendaftaran (Opsional)" />
                        <input type="text" id="nomor_daftar" name="nomor_daftar" value="{{ old('nomor_daftar') }}" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm font-mono" />
                    </div>

                    <div>
                        <x-input-label for="tanggal_dokumen" value="Tanggal Dokumen (Opsional)" />
                        <input type="date" id="tanggal_dokumen" name="tanggal_dokumen" value="{{ old('tanggal_dokumen') }}" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                    </div>
                    <div>
                        <x-input-label for="jalur" value="Jalur Pemeriksaan (Opsional)" />
                        <select id="jalur" name="jalur" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                            <option value="">-- Tidak diketahui --</option>
                            <option value="H" @selected(old('jalur') === 'H')>Jalur Hijau</option>
                            <option value="K" @selected(old('jalur') === 'K')>Jalur Kuning</option>
                            <option value="M" @selected(old('jalur') === 'M')>Jalur Merah</option>
                        </select>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-5 grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="nama_perusahaan" value="Nama Perusahaan (Eksportir/Importir)" />
                        <input type="text" id="nama_perusahaan" name="nama_perusahaan" value="{{ old('nama_perusahaan') }}" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required />
                    </div>
                    <div>
                        <x-input-label for="npwp" value="NPWP (Opsional)" />
                        <input type="text" id="npwp" name="npwp" value="{{ old('npwp') }}" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                    </div>
                    <div>
                        <x-input-label for="kantor_pabean" value="Kantor Pabean (Opsional)" />
                        <select id="kantor_pabean" name="kantor_pabean" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                            <option value="">-- Pilih Kantor --</option>
                            @foreach ($references['kantorMuat'] ?? [] as $k)
                                <option value="{{ $k['code'] }}" @selected(old('kantor_pabean') === $k['code'])>{{ $k['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="kode_valuta" value="Valuta (Opsional)" />
                        <select id="kode_valuta" name="kode_valuta" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                            <option value="">-- Pilih --</option>
                            @foreach ($references['currencies'] ?? [] as $c)
                                <option value="{{ $c['code'] }}" @selected(old('kode_valuta') === $c['code'])>{{ $c['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="nilai" value="Nilai Pabean / FOB (Opsional)" />
                        <input type="number" step="0.01" min="0" id="nilai" name="nilai" value="{{ old('nilai') }}" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="uraian" value="Uraian Barang (Opsional)" />
                        <input type="text" id="uraian" name="uraian" value="{{ old('uraian') }}" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="keterangan" value="Keterangan (Opsional)" />
                        <textarea id="keterangan" name="keterangan" rows="2" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">{{ old('keterangan') }}</textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                    <a href="{{ route('dashboard') }}" class="text-slate-500 hover:text-slate-800 text-sm">Batal</a>
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-indigo-100">
                        Simpan ke Arsip
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
