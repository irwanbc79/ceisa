    <div x-show="showDraftModal" x-cloak style="display: none;" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/75 backdrop-blur-sm"
         @keydown.escape.window="showDraftModal = false">

        <div x-show="showDraftModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="bg-white text-slate-900 w-full max-w-5xl max-h-[94vh] overflow-y-auto rounded-2xl shadow-2xl border border-slate-300"
             @click.stop>

            {{-- Modal Header --}}
            <div class="sticky top-0 z-20 bg-slate-900 text-white px-5 py-3.5 flex items-center justify-between border-b border-slate-700">
                <div>
                    <h3 class="font-bold text-sm tracking-tight">Preview Formulir Resmi (Draft Lokal)</h3>
                    <p class="text-[11px] text-slate-300 mt-0.5">Format diselaraskan dengan cetakan resmi CEISA 4.0 (PEB BC 3.0 / PIB BC 2.0)</p>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-500/20 text-amber-300 text-[11px] font-bold rounded-full border border-amber-400/30">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                        DRAFT — Belum Terdaftar di DJBC
                    </span>
                    <button type="button" onclick="window.print()" class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 bg-white/10 hover:bg-white/20 text-white text-[11px] font-semibold rounded-lg transition-colors">
                        Cetak
                    </button>
                    <button type="button" @click="showDraftModal = false" class="text-slate-300 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            {{-- BODY: FORMULIR RESMI --}}
            <div class="p-4 sm:p-6 bg-slate-100 space-y-6 font-sans text-[10px] leading-snug text-slate-900">

                {{-- ============================================================
                     1. FORMAT RESMI BC 3.0 — PEMBERITAHUAN EKSPOR BARANG (PEB)
                     ============================================================ --}}
                <template x-if="doc_type === 'BC30'">
                    <div class="space-y-5">
                        <div class="bg-white border-2 border-slate-900 shadow-sm">
                            {{-- Top Title Bar --}}
                            <div class="grid grid-cols-12 border-b-2 border-slate-900">
                                <div class="col-span-2 border-r-2 border-slate-900 px-3 py-2 font-bold text-sm flex items-center justify-center">BC 3.0</div>
                                <div class="col-span-8 px-3 py-2 font-bold text-sm sm:text-base tracking-wide flex items-center justify-center text-center">PEMBERITAHUAN EKSPOR BARANG</div>
                                <div class="col-span-2 px-3 py-2 text-right text-[10px] flex items-center justify-end">Halaman ke-1</div>
                            </div>

                            {{-- HEADER + H. KOLOM KHUSUS BEA DAN CUKAI --}}
                            <div class="grid grid-cols-12 border-b-2 border-slate-900">
                                <div class="col-span-1 bg-slate-50 border-r border-slate-900 flex items-center justify-center font-bold tracking-widest text-[10px]" style="writing-mode: vertical-rl; transform: rotate(180deg);">HEADER</div>
                                <div class="col-span-6 border-r border-slate-900 p-2.5 space-y-1">
                                    <div class="grid grid-cols-12"><span class="col-span-5">Nomor Pengajuan</span><span class="col-span-7 font-mono font-bold">: <span x-text="formData.nomor_aju || '[DRAFT — Otomatis saat kirim]'"></span></span></div>
                                    <div class="font-bold pt-0.5">A. KANTOR PABEAN</div>
                                    <div class="grid grid-cols-12 pl-3"><span class="col-span-5">1. Kantor Pabean Pemuatan</span><span class="col-span-7 font-semibold">: <span x-text="formData.kantor_muat || formData.kode_kantor || '-'"></span></span></div>
                                    <div class="grid grid-cols-12 pl-3"><span class="col-span-5">2. Kantor Pabean Ekspor</span><span class="col-span-7 font-semibold">: <span x-text="formData.kantor_ekspor || formData.kantor_pendaftaran || formData.kode_kantor || '-'"></span></span></div>
                                    <div class="grid grid-cols-12"><span class="col-span-5 font-bold">B. JENIS EKSPOR</span><span class="col-span-7 font-semibold">: <span x-text="formData.jenis_ekspor || '-'"></span></span></div>
                                    <div class="grid grid-cols-12"><span class="col-span-5 font-bold">C. KATEGORI EKSPOR</span><span class="col-span-7 font-semibold">: <span x-text="formData.kategori_ekspor || '-'"></span></span></div>
                                    <div class="grid grid-cols-12"><span class="col-span-5 font-bold">D. CARA PERDAGANGAN</span><span class="col-span-7 font-semibold">: <span x-text="formData.cara_dagang || '-'"></span></span></div>
                                    <div class="grid grid-cols-12"><span class="col-span-5 font-bold">E. CARA PEMBAYARAN</span><span class="col-span-7 font-semibold">: <span x-text="formData.cara_bayar || formData.cara_pembayaran || '-'"></span></span></div>
                                </div>
                                <div class="col-span-5 p-2.5 space-y-1">
                                    <div class="font-bold">H. KOLOM KHUSUS BEA DAN CUKAI</div>
                                    <div class="grid grid-cols-12 pl-2"><span class="col-span-5">1. Nomor Pendaftaran</span><span class="col-span-7">: <span class="text-slate-400">[Diisi DJBC]</span></span></div>
                                    <div class="grid grid-cols-12 pl-5"><span class="col-span-5">Tanggal</span><span class="col-span-7">: -</span></div>
                                    <div class="grid grid-cols-12 pl-2 pt-1"><span class="col-span-5">2. Nomor BC 1.1</span><span class="col-span-7">: -</span></div>
                                    <div class="grid grid-cols-12 pl-5"><span class="col-span-5">Tanggal</span><span class="col-span-7">: -</span></div>
                                    <div class="grid grid-cols-12 pl-5"><span class="col-span-5">Pos/Sub Pos</span><span class="col-span-7">: -</span></div>
                                </div>
                            </div>

                            {{-- ENTITAS: 3 KOLOM (EKSPORTIR / PEMILIK / PEMBELI | PPJK / KONSOLIDASI / PENERIMA) --}}
                            <div class="grid grid-cols-12 border-b-2 border-slate-900">
                                <div class="col-span-1 border-r border-slate-900"></div>
                                <div class="col-span-11">
                                    <div class="grid grid-cols-3 border-b border-slate-900">
                                        <div class="p-2 border-r border-slate-900 space-y-1">
                                            <div class="font-bold border-b border-slate-300 pb-0.5">EKSPORTIR</div>
                                            <div>1. Identitas : <span class="font-mono font-semibold" x-text="formData.npwp_eksportir || '-'"></span></div>
                                            <div>2. Nama : <span class="font-bold uppercase" x-text="formData.nama_eksportir || '-'"></span></div>
                                            <div>3. Alamat : <span x-text="formData.alamat_eksportir || '-'"></span></div>
                                            <div>4. Status : <span x-text="formData.kategori_ekspor || 'LAINNYA'"></span></div>
                                        </div>
                                        <div class="p-2 border-r border-slate-900 space-y-1">
                                            <div class="font-bold border-b border-slate-300 pb-0.5">PEMILIK BARANG</div>
                                            <div>5. Identitas : <span class="font-mono" x-text="formData.npwp_eksportir || '-'"></span></div>
                                            <div>6. Nama : <span class="font-semibold uppercase" x-text="formData.nama_eksportir || '-'"></span></div>
                                            <div>7. Alamat : <span x-text="formData.alamat_eksportir || '-'"></span></div>
                                        </div>
                                        <div class="p-2 space-y-1">
                                            <div class="font-bold border-b border-slate-300 pb-0.5">PEMBELI</div>
                                            <div>15. Nama : <span class="font-bold uppercase" x-text="formData.nama_penerima || '-'"></span></div>
                                            <div>16. Alamat : <span x-text="formData.alamat_penerima || '-'"></span></div>
                                            <div>17. Negara : <span class="font-semibold" x-text="formData.negara_tujuan || '-'"></span></div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3">
                                        <div class="p-2 border-r border-slate-900 space-y-1">
                                            <div class="font-bold border-b border-slate-300 pb-0.5">PPJK</div>
                                            <div>8. NPWP : <span class="font-mono">0960208833125000</span></div>
                                            <div>9. Nama : <span class="font-bold">PT. MORA MULTI BERKAH</span></div>
                                            <div>10. Alamat : JL. KAPTEN SUMARSONO KOMPLEK GRAHA METROPOLITAN BLOK G NO. 24</div>
                                        </div>
                                        <div class="p-2 border-r border-slate-900 space-y-1">
                                            <div class="font-bold border-b border-slate-300 pb-0.5">PIHAK YANG MELAKUKAN KONSOLIDASI</div>
                                            <div>11. Kategori : -</div>
                                            <div>12. NPWP : -</div>
                                            <div>13. Nama : -</div>
                                            <div>14. Alamat : -</div>
                                        </div>
                                        <div class="p-2 space-y-1">
                                            <div class="font-bold border-b border-slate-300 pb-0.5">PENERIMA</div>
                                            <div>18. Nama : <span class="font-bold uppercase" x-text="formData.nama_penerima || '-'"></span></div>
                                            <div>19. Alamat : <span x-text="formData.alamat_penerima || '-'"></span></div>
                                            <div>20. Negara : <span class="font-semibold" x-text="formData.negara_tujuan || '-'"></span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- F. DATA PERDAGANGAN --}}
                            <div class="grid grid-cols-12 border-b-2 border-slate-900">
                                <div class="col-span-1 bg-slate-50 border-r border-slate-900 flex items-center justify-center font-bold tracking-widest text-[10px]" style="writing-mode: vertical-rl; transform: rotate(180deg);">F. DATA PERDAGANGAN</div>
                                <div class="col-span-11 divide-y divide-slate-900">
                                    {{-- Pengangkutan & Pelabuhan --}}
                                    <div class="grid grid-cols-2">
                                        <div class="p-2 border-r border-slate-900 space-y-0.5">
                                            <div class="font-bold border-b border-slate-300 pb-0.5 mb-1">DATA PENGANGKUTAN</div>
                                            <div>21. Cara Pengangkutan : <span class="font-semibold" x-text="formData.cara_angkut || '-'"></span></div>
                                            <div>22. Nama &amp; Bendera Sarana : <span class="font-semibold" x-text="(formData.nama_sarana || '-') + (formData.kode_bendera ? ' (' + formData.kode_bendera + ')' : '')"></span></div>
                                            <div>23. No. Pengangkut (Voy/Flight) : <span class="font-semibold" x-text="formData.voy_flight || '-'"></span></div>
                                            <div>24. Tgl Perkiraan Ekspor : <span class="font-semibold" x-text="formData.tanggal_ekspor || '-'"></span></div>
                                        </div>
                                        <div class="p-2 space-y-0.5">
                                            <div class="font-bold border-b border-slate-300 pb-0.5 mb-1">DATA PELABUHAN/TEMPAT MUAT EKSPOR</div>
                                            <div>25. Pelabuhan Muat Asal : <span class="font-semibold" x-text="formData.pelabuhan_muat || '-'"></span></div>
                                            <div>26. Pelabuhan Muat Ekspor : <span class="font-semibold" x-text="formData.pelabuhan_ekspor || formData.pelabuhan_muat || '-'"></span></div>
                                            <div>27. Tempat Penimbunan : <span x-text="formData.kode_lokasi || '-'"></span></div>
                                            <div>28. Pelabuhan Tujuan : <span class="font-semibold" x-text="formData.pelabuhan_tujuan || formData.pelabuhan_bongkar || '-'"></span></div>
                                            <div>29. Negara Tujuan Ekspor : <span class="font-semibold" x-text="formData.negara_tujuan || '-'"></span></div>
                                        </div>
                                    </div>

                                    {{-- Dokumen Pelengkap & Pemeriksaan / Penyerahan --}}
                                    <div class="grid grid-cols-2">
                                        <div class="p-2 border-r border-slate-900 space-y-0.5">
                                            <div class="font-bold border-b border-slate-300 pb-0.5 mb-1">DOKUMEN PELENGKAP PABEAN</div>
                                            <div>30. No &amp; Tgl Invoice : <span x-text="(formData.dokumen.find(d => d.kode_dokumen === '380')?.nomor_dokumen || '-') + ' tgl. ' + (formData.dokumen.find(d => d.kode_dokumen === '380')?.tanggal_dokumen || '-')"></span></div>
                                            <div>31. No &amp; Tgl Packing List : <span x-text="(formData.dokumen.find(d => d.kode_dokumen === '217')?.nomor_dokumen || '-') + ' tgl. ' + (formData.dokumen.find(d => d.kode_dokumen === '217')?.tanggal_dokumen || '-')"></span></div>
                                            <div>32. Jumlah Dok. Pelengkap : <span class="font-semibold" x-text="formData.dokumen.length + ' dokumen (lihat lembar lanjutan)'"></span></div>
                                        </div>
                                        <div class="p-2 space-y-1">
                                            <div class="font-bold border-b border-slate-300 pb-0.5">DATA TEMPAT PEMERIKSAAN &amp; PENYERAHAN</div>
                                            <div>33. Lokasi Pemeriksaan : <span x-text="formData.kode_lokasi || '-'"></span></div>
                                            <div>34. Kantor Pabean Pemeriksaan : <span x-text="formData.kantor_muat || formData.kode_kantor || '-'"></span></div>
                                            <div>35. Cara Penyerahan Barang (Incoterm) : <span class="font-bold" x-text="formData.incoterm || 'FOB'"></span></div>
                                        </div>
                                    </div>

                                    {{-- Transaksi Ekspor --}}
                                    <div class="p-2">
                                        <div class="font-bold border-b border-slate-300 pb-0.5 mb-1">DATA TRANSAKSI EKSPOR</div>
                                        <div class="grid grid-cols-2 gap-x-4 gap-y-0.5">
                                            <div>36. Bank Devisa Hasil Ekspor : <span class="font-semibold" x-text="formData.bank_devisa || '-'"></span></div>
                                            <div>39. Biaya Pengangkutan (Freight) : <span class="font-mono" x-text="formData.freight || '0.00'"></span></div>
                                            <div>37. Jenis Valuta : <span class="font-bold" x-text="formData.kode_valuta || 'USD'"></span></div>
                                            <div>40. Asuransi (LN/DN) : <span class="font-mono" x-text="formData.nilai_asuransi || '0.00'"></span></div>
                                            <div>38. Jumlah Nilai Ekspor : <span class="font-mono font-bold text-blue-700" x-text="getTotalValue()"></span></div>
                                            <div>41. Nilai Maklon (Jika Ada) : 0</div>
                                        </div>
                                    </div>

                                    {{-- Peti Kemas & Kemasan --}}
                                    <div class="grid grid-cols-2">
                                        <div class="p-2 border-r border-slate-900 space-y-0.5">
                                            <div class="font-bold border-b border-slate-300 pb-0.5">DATA PETI KEMAS</div>
                                            <div>42. Jumlah Peti Kemas : <span class="font-bold" x-text="formData.kontainer.length"></span></div>
                                            <div>43. No, Ukuran, Tipe : <span class="font-mono" x-text="formData.kontainer[0] ? (formData.kontainer[0].nomor_kontainer + ' / ' + (formData.kontainer[0].kode_ukuran || '-') + ' FEET / ' + (formData.kontainer[0].kode_status || '-')) : '-'"></span></div>
                                        </div>
                                        <div class="p-2 space-y-0.5">
                                            <div class="font-bold border-b border-slate-300 pb-0.5">DATA KEMASAN</div>
                                            <div>44. Jenis, Jumlah dan Merek Kemasan : <span class="font-semibold" x-text="(formData.barang[0]?.jumlah_kemasan || 0) + ' ' + (formData.barang[0]?.kode_kemasan || '-') + ' / ' + (formData.barang[0]?.merk_kemasan || '-')"></span></div>
                                        </div>
                                    </div>

                                    {{-- Berat Kotor & Berat Bersih + Tabel Barang (Kolom 47-54) --}}
                                    <div>
                                        <div class="grid grid-cols-2 border-b border-slate-900 bg-slate-50/70 px-2 py-1.5 font-semibold">
                                            <div>45. Berat Kotor (kg) : <span class="font-mono" x-text="formData.bruto || '0.0000'"></span></div>
                                            <div>46. Berat Bersih (kg) : <span class="font-mono" x-text="formData.barang.reduce((s, b) => s + (parseFloat(b.netto)||0), 0).toLocaleString('id-ID')"></span></div>
                                        </div>
                                        <table class="w-full border-collapse text-[9px]">
                                            <thead>
                                                <tr class="bg-slate-50 border-b border-slate-900 text-left align-top">
                                                    <th class="p-1.5 border-r border-slate-900 w-8">47. No.</th>
                                                    <th class="p-1.5 border-r border-slate-900">48. - Pos Tarif/HS<br>- Uraian Jenis Barang, Merk, Tipe, Ukuran</th>
                                                    <th class="p-1.5 border-r border-slate-900 w-24">49. Perizinan Ekspor</th>
                                                    <th class="p-1.5 border-r border-slate-900 w-20">50. HE &amp; Tarif BK</th>
                                                    <th class="p-1.5 border-r border-slate-900 w-28">51. Satuan, Netto (kg), Kemasan</th>
                                                    <th class="p-1.5 border-r border-slate-900 w-24">52. Negara Asal<br>53. Daerah Asal</th>
                                                    <th class="p-1.5 text-right w-24">54. Nilai Ekspor</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-300">
                                                <template x-for="(item, idx) in formData.barang" :key="idx">
                                                    <tr class="align-top">
                                                        <td class="p-1.5 border-r border-slate-900 text-center font-bold" x-text="idx + 1"></td>
                                                        <td class="p-1.5 border-r border-slate-900">
                                                            <div class="font-mono font-bold" x-text="item.hs_code || '-'"></div>
                                                            <div x-text="(item.uraian || '-') + ', Merk: ' + (item.merk || '-') + ', Tipe: ' + (item.tipe || '-') + ', Ukuran: ' + (item.ukuran || '-')"></div>
                                                        </td>
                                                        <td class="p-1.5 border-r border-slate-900">-</td>
                                                        <td class="p-1.5 border-r border-slate-900">HE: 0<br>BK: 0</td>
                                                        <td class="p-1.5 border-r border-slate-900">
                                                            <div x-text="(item.jumlah_satuan || 0) + ' ' + (item.kode_satuan || '-')"></div>
                                                            <div x-text="(item.netto || 0) + ' Kg'"></div>
                                                            <div x-text="'Kemasan: ' + (item.jumlah_kemasan || 0) + ' ' + (item.kode_kemasan || '-')"></div>
                                                        </td>
                                                        <td class="p-1.5 border-r border-slate-900">
                                                            <div x-text="item.negara_asal || 'ID'"></div>
                                                            <div x-text="item.daerah_asal || '-'"></div>
                                                        </td>
                                                        <td class="p-1.5 text-right font-mono font-bold" x-text="(parseFloat(item.nilai_fob)||0).toLocaleString('id-ID')"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- Kurs & Penerimaan Negara --}}
                                    <div class="grid grid-cols-2 border-t border-slate-900">
                                        <div class="p-2 border-r border-slate-900">55. Nilai Tukar Mata Uang (NDPBM) : <span class="font-mono font-bold" x-text="formData.ndpbm ? ('Rp ' + formData.ndpbm) : '-'"></span></div>
                                        <div class="p-2 space-y-0.5">
                                            <div class="font-bold">DATA PENERIMAAN NEGARA</div>
                                            <div class="flex justify-between"><span>56. Nilai Bea Keluar</span><span>: Rp. 0</span></div>
                                            <div class="flex justify-between"><span>57. PPh Pasal 22 Ekspor</span><span>: Rp. 0</span></div>
                                            <div class="flex justify-between"><span>58. Dana Perkebunan</span><span>: Rp. 0</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- G. TANDA TANGAN EKSPORTIR / PPJK --}}
                            <div class="p-3 grid grid-cols-12 gap-4">
                                <div class="col-span-8">
                                    <div class="font-bold mb-1">G. TANDA TANGAN EKSPORTIR / PPJK</div>
                                    <p class="text-[9px] text-slate-700 leading-relaxed">Dengan ini saya menyatakan bertanggung jawab atas kebenaran hal-hal yang diberitahukan dalam dokumen ini dan keabsahan dokumen pelengkap pabean yang menjadi dasar pembuatan dokumen ini, serta bersedia dikenakan sanksi sesuai dengan ketentuan di bidang kepabeanan apabila terdapat kesalahan.</p>
                                </div>
                                <div class="col-span-4 text-center flex flex-col justify-between pt-1">
                                    <div><span x-text="(formData.pernyataan_kota || 'MEDAN') + ', ' + todayFormatted()"></span><br>Eksportir/PPJK</div>
                                    <div class="mt-6 font-bold uppercase underline" x-text="formData.pernyataan_nama || '[NAMA PENANDATANGAN]'"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- ============================================================
                     2. FORMAT RESMI BC 2.0 / BC 2.4 — PEMBERITAHUAN IMPOR BARANG (PIB)
                     ============================================================ --}}
                <template x-if="doc_type === 'BC20' || doc_type === 'BC24'">
                    <div class="space-y-5">
                        <div class="bg-white border-2 border-slate-900 shadow-sm">
                            <div class="grid grid-cols-12 border-b-2 border-slate-900 px-3 py-2 items-center">
                                <div class="col-span-10 text-center font-bold text-sm sm:text-base tracking-wide">PEMBERITAHUAN IMPOR BARANG (PIB)</div>
                                <div class="col-span-2 text-right font-bold text-sm" x-text="doc_type === 'BC24' ? 'BC 2.4' : 'BC 2.0'"></div>
                            </div>

                            {{-- Header Atas PIB --}}
                            <div class="grid grid-cols-12 border-b-2 border-slate-900 p-2.5 gap-2">
                                <div class="col-span-7 space-y-1">
                                    <div>Kantor Pabean : <span class="font-bold" x-text="formData.kode_kantor || '-'"></span></div>
                                    <div>Nomor Pengajuan : <span class="font-mono font-bold" x-text="formData.nomor_aju || '[DRAFT — Otomatis saat kirim]'"></span></div>
                                    <div>A. JENIS PIB : <span class="font-semibold">1. Biasa</span> &nbsp;|&nbsp; B. JENIS IMPOR : <span class="font-semibold" x-text="formData.jenis_impor || '1. Untuk Dipakai'"></span></div>
                                    <div>C. CARA PEMBAYARAN : <span class="font-semibold" x-text="formData.cara_pembayaran || '1. Biasa/Tunai'"></span></div>
                                </div>
                                <div class="col-span-5 text-right space-y-1">
                                    <div>Halaman ke-1</div>
                                    <div>Tanggal Pengajuan : <span class="font-semibold" x-text="todayFormatted()"></span></div>
                                </div>
                            </div>

                            {{-- D. DATA: KIRI (ENTITAS) & KANAN (G + LOGISTIK + NILAI) --}}
                            <div class="grid grid-cols-12 border-b-2 border-slate-900">
                                <div class="col-span-6 border-r-2 border-slate-900 divide-y divide-slate-900">
                                    <div class="p-2 space-y-0.5">
                                        <div class="font-bold">PENGIRIM</div>
                                        <div>1. Nama, Alamat : <span class="font-bold uppercase" x-text="formData.nama_pemasok || '-'"></span></div>
                                        <div x-text="(formData.alamat_pemasok || '-') + ' (' + (formData.negara_pemasok || '-') + ')'"></div>
                                    </div>
                                    <div class="p-2 space-y-0.5">
                                        <div class="font-bold">PENJUAL</div>
                                        <div>1a. Nama, Alamat : <span class="font-semibold uppercase" x-text="formData.nama_pemasok || '-'"></span></div>
                                    </div>
                                    <div class="p-2 space-y-0.5">
                                        <div class="font-bold">IMPORTIR</div>
                                        <div>2. Identitas (NPWP) : <span class="font-mono font-semibold" x-text="formData.npwp_importir || '-'"></span></div>
                                        <div>3. Nama, Alamat : <span class="font-bold uppercase" x-text="formData.nama_importir || '-'"></span></div>
                                        <div x-text="formData.alamat_importir || '-'"></div>
                                        <div>4. Status : <span x-text="formData.status_importir || 'LAINNYA'"></span> &nbsp;|&nbsp; 5. NIB : <span class="font-mono" x-text="formData.nib_importir || '-'"></span></div>
                                    </div>
                                    <div class="p-2 space-y-0.5">
                                        <div class="font-bold">PEMILIK BARANG</div>
                                        <div>2a. Identitas : <span class="font-mono" x-text="formData.npwp_importir || '-'"></span></div>
                                        <div>3a. Nama : <span class="font-semibold uppercase" x-text="formData.nama_importir || '-'"></span></div>
                                    </div>
                                    <div class="p-2 space-y-0.5">
                                        <div class="font-bold">PPJK</div>
                                        <div>6. NPWP : <span class="font-mono">0960208833125000</span></div>
                                        <div>7. Nama, Alamat : <span class="font-bold">PT. MORA MULTI BERKAH</span> — JL. KAPTEN SUMARSONO BLOK G NO. 24</div>
                                    </div>
                                </div>

                                <div class="col-span-6 divide-y divide-slate-900">
                                    <div class="p-2 bg-slate-50/60">
                                        <div class="font-bold">G. Nomor dan Tanggal Pendaftaran : <span class="font-normal text-slate-400">[Diisi DJBC]</span></div>
                                    </div>
                                    <div class="p-2 space-y-0.5">
                                        <div>9. Cara Pengangkutan : <span class="font-semibold" x-text="formData.cara_angkut || '-'"></span></div>
                                        <div>10. Sarana Pengangkut &amp; Voy/Flight : <span class="font-semibold" x-text="(formData.nama_sarana || formData.nama_sarana_pengangkut || '-') + ' ' + (formData.voy_flight || '')"></span></div>
                                        <div>11. Perkiraan Tgl Tiba : <span x-text="formData.tanggal_tiba || '-'"></span></div>
                                        <div>12. Pelabuhan Muat : <span class="font-semibold" x-text="formData.pelabuhan_muat || '-'"></span></div>
                                        <div>14. Pelabuhan Tujuan : <span class="font-semibold" x-text="formData.pelabuhan_bongkar || '-'"></span></div>
                                    </div>
                                    <div class="p-2 space-y-0.5">
                                        <div>15. Invoice : <span x-text="(formData.dokumen.find(d => d.kode_dokumen === '380')?.nomor_dokumen || '-') + ' Tgl. ' + (formData.dokumen.find(d => d.kode_dokumen === '380')?.tanggal_dokumen || '-')"></span></div>
                                        <div>17. House-BL/AWB : <span x-text="(formData.dokumen.find(d => d.kode_dokumen === '705' || d.kode_dokumen === '740')?.nomor_dokumen || '-') + ' Tgl. ' + (formData.dokumen.find(d => d.kode_dokumen === '705' || d.kode_dokumen === '740')?.tanggal_dokumen || '-')"></span></div>
                                        <div>18. BC 1.1/1.2 : <span x-text="formData.dokumen.find(d => d.kode_dokumen === '217')?.nomor_dokumen || '-'"></span></div>
                                        <div>20. Tempat Penimbunan : <span class="font-semibold" x-text="formData.kode_tps || '-'"></span></div>
                                    </div>
                                    <div class="p-2 grid grid-cols-2 gap-1">
                                        <div>21. Valuta : <span class="font-bold" x-text="formData.kode_valuta || 'USD'"></span></div>
                                        <div>22. NDPBM : <span class="font-mono font-semibold" x-text="formData.ndpbm || '-'"></span></div>
                                        <div>23. Nilai CIF : <span class="font-mono font-bold text-blue-700" x-text="getTotalValue()"></span></div>
                                        <div>24. Asuransi : <span class="font-mono" x-text="formData.nilai_asuransi || '0.00'"></span></div>
                                        <div>25. Freight : <span class="font-mono" x-text="formData.freight || '0.00'"></span></div>
                                        <div>26. Nilai Pabean : <span class="font-mono font-bold" x-text="getTotalValue()"></span></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Baris 27-30 --}}
                            <div class="grid grid-cols-12 border-b-2 border-slate-900 divide-x divide-slate-900">
                                <div class="col-span-4 p-2"><span class="font-bold">27. Peti Kemas:</span> <span class="font-mono" x-text="formData.kontainer[0] ? (formData.kontainer[0].nomor_kontainer + ' ' + (formData.kontainer[0].kode_ukuran||'') + ' ' + (formData.kontainer[0].kode_status||'')) : '-'"></span></div>
                                <div class="col-span-4 p-2"><span class="font-bold">28. Kemasan:</span> <span x-text="(formData.jumlah_kemasan_impor || formData.barang[0]?.jumlah_kemasan || 0) + ' ' + (formData.kode_kemasan_impor || formData.barang[0]?.kode_kemasan || '-') + ' ' + (formData.merk_kemasan_impor || '')"></span></div>
                                <div class="col-span-2 p-2"><span class="font-bold">29. Bruto (Kg):</span> <span class="font-mono" x-text="formData.bruto || '0.0000'"></span></div>
                                <div class="col-span-2 p-2"><span class="font-bold">30. Netto (Kg):</span> <span class="font-mono" x-text="formData.barang.reduce((s, b) => s + (parseFloat(b.netto)||0), 0).toLocaleString('id-ID')"></span></div>
                            </div>

                            {{-- Tabel Barang PIB (Kolom 31-36) --}}
                            <table class="w-full border-collapse text-[9px] border-b-2 border-slate-900">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-900 text-left align-top">
                                        <th class="p-1.5 border-r border-slate-900 w-8">31. No.</th>
                                        <th class="p-1.5 border-r border-slate-900">32. - Pos Tarif HS<br>- Uraian Jenis Barang, Merk, Tipe, Spesifikasi<br>- Negara Asal Barang</th>
                                        <th class="p-1.5 border-r border-slate-900 w-24">33. Keterangan &amp; Fasilitas</th>
                                        <th class="p-1.5 border-r border-slate-900 w-24">34. Tarif &amp; Fasilitas</th>
                                        <th class="p-1.5 border-r border-slate-900 w-28">35. Satuan, Netto (Kg), Kemasan</th>
                                        <th class="p-1.5 text-right w-24">36. Nilai Pabean</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-300">
                                    <template x-for="(item, idx) in formData.barang" :key="idx">
                                        <tr class="align-top">
                                            <td class="p-1.5 border-r border-slate-900 text-center font-bold" x-text="idx + 1"></td>
                                            <td class="p-1.5 border-r border-slate-900">
                                                <div class="font-mono font-bold" x-text="'Pos Tarif : ' + (item.hs_code || '-')"></div>
                                                <div x-text="'Uraian : ' + (item.uraian || '-')"></div>
                                                <div x-text="'Merk: ' + (item.merk || '-') + ', Tipe: ' + (item.tipe || '-') + ', Negara: ' + (item.negara_asal || '-')"></div>
                                            </td>
                                            <td class="p-1.5 border-r border-slate-900">-</td>
                                            <td class="p-1.5 border-r border-slate-900">
                                                <div x-text="'BM ' + (item.tarif_bm || '0') + '%'"></div>
                                                <div x-text="'PPN ' + (item.tarif_ppn || '11') + '%'"></div>
                                                <div x-text="'PPh ' + (item.tarif_pph || '2.5') + '%'"></div>
                                            </td>
                                            <td class="p-1.5 border-r border-slate-900">
                                                <div x-text="(item.jumlah_satuan || 0) + ' ' + (item.kode_satuan || '-')"></div>
                                                <div x-text="(item.netto || 0) + ' Kg'"></div>
                                                <div x-text="(item.jumlah_kemasan || 0) + ' ' + (item.kode_kemasan || '-')"></div>
                                            </td>
                                            <td class="p-1.5 text-right font-mono font-bold" x-text="(parseFloat(item.nilai_cif)||0).toLocaleString('id-ID')"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>

                            {{-- Pernyataan F + E --}}
                            <div class="grid grid-cols-12">
                                <div class="col-span-7 p-2.5 border-r border-slate-900 space-y-1">
                                    <div class="font-bold">F. Dengan ini saya menyatakan :</div>
                                    <p class="text-[9px]">a. Bertanggung jawab atas kebenaran hal-hal yang diberitahukan dalam dokumen ini dan keabsahan dokumen pelengkap pabean yang menjadi dasar pembuatan dokumen ini; dan</p>
                                    <p class="text-[9px]">b. Sanggup menyiapkan dan menyerahkan barang impor untuk diperiksa, serta menyaksikan pemeriksaan fisik.</p>
                                    <div class="text-center pt-2">
                                        <span x-text="(formData.pernyataan_kota || 'BELAWAN') + ', ' + todayFormatted()"></span> — Importir/PPJK<br>
                                        <div class="mt-4 font-bold uppercase underline" x-text="formData.pernyataan_nama || 'PT. MORA MULTI BERKAH'"></div>
                                    </div>
                                </div>
                                <div class="col-span-5 p-2.5 space-y-1">
                                    <div class="font-bold">E. UNTUK PEMBAYARAN DAN JAMINAN :</div>
                                    <div>a. Pembayaran : [ ] 1. Bank &nbsp; [ ] 2. Post &nbsp; [ ] 3. Kantor Pabean</div>
                                    <div>b. Jaminan : [ ] 1. Tunai &nbsp; [ ] 2. Bank Garansi &nbsp; [ ] 3. Customs Bond &nbsp; [ ] 4. Lainnya</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- ============================================================
                     3. FORMAT RINGKAS UNTUK TPB & RUSH HANDLING
                     ============================================================ --}}
                <template x-if="doc_type !== 'BC30' && doc_type !== 'BC20' && doc_type !== 'BC24'">
                    <div class="bg-white border-2 border-slate-900 shadow-sm p-4 space-y-4">
                        <div class="flex items-center justify-between border-b-2 border-slate-900 pb-2">
                            <div>
                                <div class="text-[10px] font-bold uppercase text-slate-500">Kementerian Keuangan RI — Direktorat Jenderal Bea dan Cukai</div>
                                <div class="text-base font-bold uppercase" x-text="getDocTypeLabel()"></div>
                            </div>
                            <span class="px-2.5 py-1 border border-slate-900 font-bold" x-text="doc_type"></span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="font-bold border-b border-slate-300 pb-0.5 mb-1">PIHAK UTAMA</div>
                                <div>Nama : <span class="font-bold" x-text="getPartyName()"></span></div>
                                <div>NPWP : <span class="font-mono" x-text="getPartyNPWP()"></span></div>
                                <div>PPJK : <span class="font-bold">PT. MORA MULTI BERKAH</span></div>
                            </div>
                            <div>
                                <div class="font-bold border-b border-slate-300 pb-0.5 mb-1">LOGISTIK &amp; NILAI</div>
                                <div>Kantor Pabean : <span x-text="formData.kode_kantor || '-'"></span></div>
                                <div>Valuta / Nilai : <span class="font-bold" x-text="getTotalValue() + ' ' + (formData.kode_valuta || 'USD')"></span></div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- LEMBAR LANJUTAN DOKUMEN PELENGKAP (SEMUA JENIS) --}}
                <template x-if="formData.dokumen && formData.dokumen.length > 0">
                    <div class="bg-white border-2 border-slate-900 shadow-sm p-3 space-y-2">
                        <div class="text-center font-bold text-xs uppercase border-b border-slate-900 pb-1">
                            LEMBAR LANJUTAN DOKUMEN PELENGKAP PABEAN (<span x-text="doc_type"></span>)
                        </div>
                        <table class="w-full border-collapse text-[9px] border border-slate-900">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="border border-slate-900 p-1 w-8">No.</th>
                                    <th class="border border-slate-900 p-1 w-20">Kode</th>
                                    <th class="border border-slate-900 p-1 text-left">Nomor Dokumen</th>
                                    <th class="border border-slate-900 p-1 w-28">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(dok, i) in formData.dokumen" :key="i">
                                    <tr>
                                        <td class="border border-slate-900 p-1 text-center" x-text="i + 1"></td>
                                        <td class="border border-slate-900 p-1 text-center font-mono font-bold" x-text="dok.kode_dokumen || '-'"></td>
                                        <td class="border border-slate-900 p-1" x-text="dok.nomor_dokumen || '-'"></td>
                                        <td class="border border-slate-900 p-1 text-center" x-text="dok.tanggal_dokumen || '-'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>

            </div>

            {{-- Action Buttons --}}
            <div class="sticky bottom-0 bg-panel border-t border-slate-200 px-6 py-3.5 flex items-center justify-between gap-3">
                <button type="button" @click="showDraftModal = false"
                        class="inline-flex items-center gap-2 px-5 py-2.5 border border-slate-200 text-slate-700 text-sm font-bold rounded-xl bg-panel hover:bg-slate-50 transition-colors shadow-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    Edit Kembali
                </button>
                <div class="flex items-center gap-3">
                    <button type="button" @click="submitForm('submit')"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-all shadow-md shadow-slate-900/10">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                        Kirim ke CEISA Sekarang
                    </button>
                    <button type="button" @click="confirmSaveDraft()"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-ink-800 hover:bg-ink-950 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-slate-900/10">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" /></svg>
                        Konfirmasi Simpan Draft
                    </button>
                </div>
            </div>
        </div>
    </div>
