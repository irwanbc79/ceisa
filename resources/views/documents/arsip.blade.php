<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col">
            <p class="text-[10px] font-mono uppercase tracking-[0.3em] text-ink-400">Workspace · Arsip Manual</p>
            <h1 class="font-display text-2xl sm:text-3xl font-semibold text-ink-900 tracking-tightest leading-none mt-1">Arsip Dokumen Lama</h1>
            <p class="text-sm text-ink-500 mt-1.5 max-w-2xl">Rekam manual dokumen PIB/PEB yang sudah pernah diajukan di portal Bea Cukai ke riwayat M2B.</p>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <x-flash />

        <div class="rounded-xl bg-gold-50/50 border border-gold-200 p-4 text-sm text-ink-700 flex gap-3">
            <svg class="h-5 w-5 text-gold-700 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
            <p>Fitur ini hanya <strong class="text-ink-900">mencatat</strong> dokumen lama ke database M2B agar muncul di riwayat &amp; Dashboard. Dokumen <strong class="text-ink-900">tidak</strong> dikirim ulang ke CEISA.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-xl bg-white border-l-[3px] border-crimson-500 shadow-soft p-4 text-sm text-ink-700">
                <span class="font-bold">Periksa kembali isian:</span>
                <ul class="mt-2 list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('documents.archive.store') }}" class="card p-6 space-y-6">
            @csrf

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="doc_type" value="Jenis Dokumen" />
                    <select id="doc_type" name="doc_type" class="field" required>
                        <option value="">-- Pilih Jenis --</option>
                        @foreach ($docTypes as $code => $label)
                            <option value="{{ $code }}" @selected(old('doc_type') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="status" value="Status Akhir" />
                    <select id="status" name="status" class="field" required>
                        <option value="">-- Pilih Status --</option>
                        <option value="submitted" @selected(old('status') === 'submitted')>Terkirim / Proses</option>
                        <option value="accepted"  @selected(old('status') === 'accepted')>Diterima (SPPB / Selesai)</option>
                        <option value="rejected"  @selected(old('status') === 'rejected')>Ditolak</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="nomor_aju" value="Nomor Aju / Pengajuan" />
                    <input type="text" id="nomor_aju" name="nomor_aju" value="{{ old('nomor_aju', request('nomor_aju')) }}" placeholder="301012B628EF20260611000001" class="field-mono" required />
                </div>
                <div>
                    <x-input-label for="nomor_daftar" value="Nomor Pendaftaran (Opsional)" />
                    <input type="text" id="nomor_daftar" name="nomor_daftar" value="{{ old('nomor_daftar') }}" class="field-mono" />
                </div>
                <div>
                    <x-input-label for="tanggal_dokumen" value="Tanggal Dokumen (Opsional)" />
                    <input type="date" id="tanggal_dokumen" name="tanggal_dokumen" value="{{ old('tanggal_dokumen') }}" class="field" />
                </div>
                <div>
                    <x-input-label for="jalur" value="Jalur Pemeriksaan (Opsional)" />
                    <select id="jalur" name="jalur" class="field">
                        <option value="">-- Tidak diketahui --</option>
                        <option value="H" @selected(old('jalur') === 'H')>Jalur Hijau</option>
                        <option value="K" @selected(old('jalur') === 'K')>Jalur Kuning</option>
                        <option value="M" @selected(old('jalur') === 'M')>Jalur Merah</option>
                    </select>
                </div>
            </div>

            <div class="border-t border-cream-300 pt-5 grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-input-label for="nama_perusahaan" value="Nama Perusahaan (Eksportir/Importir)" />
                    <input type="text" id="nama_perusahaan" name="nama_perusahaan" value="{{ old('nama_perusahaan') }}" class="field" required />
                </div>
                <div>
                    <x-input-label for="npwp" value="NPWP (Opsional)" />
                    <input type="text" id="npwp" name="npwp" value="{{ old('npwp') }}" class="field-mono" />
                </div>
                <div>
                    <x-input-label for="kantor_pabean" value="Kantor Pabean (Opsional)" />
                    <select id="kantor_pabean" name="kantor_pabean" class="field">
                        <option value="">-- Pilih Kantor --</option>
                        @foreach ($references['kantorMuat'] ?? [] as $k)
                            <option value="{{ $k['code'] }}" @selected(old('kantor_pabean') === $k['code'])>{{ $k['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="kode_valuta" value="Valuta (Opsional)" />
                    <select id="kode_valuta" name="kode_valuta" class="field">
                        <option value="">-- Pilih --</option>
                        @foreach ($references['currencies'] ?? [] as $c)
                            <option value="{{ $c['code'] }}" @selected(old('kode_valuta') === $c['code'])>{{ $c['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="nilai" value="Nilai Pabean / FOB (Opsional)" />
                    <input type="number" step="0.01" min="0" id="nilai" name="nilai" value="{{ old('nilai') }}" class="field" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="uraian" value="Uraian Barang (Opsional)" />
                    <input type="text" id="uraian" name="uraian" value="{{ old('uraian') }}" class="field" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="keterangan" value="Keterangan (Opsional)" />
                    <textarea id="keterangan" name="keterangan" rows="2" class="field">{{ old('keterangan') }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-cream-300 pt-4">
                <a href="{{ route('dashboard') }}" class="text-sm font-bold text-ink-500 hover:text-ink-900 link-gold">Batal</a>
                <button type="submit" class="btn-primary">Simpan ke Arsip</button>
            </div>
        </form>
    </div>
</x-app-layout>
