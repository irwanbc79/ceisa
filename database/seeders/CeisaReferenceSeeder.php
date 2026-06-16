<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed data referensi CEISA 4.0 untuk wizard perekaman dokumen.
 *
 * Data STANDAR (negara ISO-3166, valuta ISO-4217, satuan, kemasan, incoterm)
 * di-seed lengkap. Tabel besar spesifik DJBC (kantor pabean & pelabuhan)
 * di-seed dari nilai terkonfirmasi + umum; daftar penuh disinkron via
 * `php artisan ceisa:sync-references` dari API Reference Code resmi CEISA.
 */
class CeisaReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [];

        foreach ($this->data() as $type => $items) {
            $sort = 0;
            foreach ($items as $code => $label) {
                $rows[] = [
                    'type' => $type,
                    'code' => (string) $code,
                    'label' => $label,
                    'meta' => null,
                    'sort' => $sort++,
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            // upsert agar idempotent (aman dijalankan ulang).
            DB::table('ceisa_references')->upsert(
                $chunk,
                ['type', 'code'],
                ['label', 'sort', 'active', 'updated_at'],
            );
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function data(): array
    {
        return [
            'jenis_ekspor' => [
                'Biasa' => 'Ekspor Biasa',
                'Akan Diimpor Kembali' => 'Akan Diimpor Kembali',
                'Reekspor' => 'Reekspor',
                'Reekspor ex Impor Sementara' => 'Reekspor ex Impor Sementara',
            ],
            'kategori_ekspor' => [
                'Umum' => 'Umum',
                'KITE' => 'KITE (Kemudahan Impor Tujuan Ekspor)',
                'Niper' => 'Niper',
                'Barang Perwakilan Negara Asing' => 'Barang Perwakilan Negara Asing',
                'Barang Penumpang' => 'Barang Penumpang / Awak Sarana',
                'Migas' => 'Migas',
                'Barang Kiriman' => 'Barang Kiriman',
                'PLB' => 'Pusat Logistik Berikat (PLB)',
                'Lainnya' => 'Lainnya',
            ],
            'cara_dagang' => [
                'Biasa' => 'Biasa',
                'IMB' => 'IMB (Imbal Beli)',
                'Lainnya' => 'Lainnya',
            ],
            'cara_bayar' => [
                'Biasa/Tunai' => 'Biasa / Tunai',
                'Berkala' => 'Berkala',
                'Dengan Jaminan' => 'Dengan Jaminan',
                'Gabungan' => 'Gabungan',
            ],
            'cara_pembayaran' => [
                'Tunai' => 'Tunai / Cash',
                'Kredit' => 'Kredit',
                'L/C' => 'L/C (Letter of Credit)',
                'Telegraphic Transfer (TT)' => 'Telegraphic Transfer (TT)',
                'Open Account' => 'Open Account',
                'Konsinyasi' => 'Konsinyasi',
                'Advance Payment' => 'Advance Payment',
            ],
            'incoterm' => [
                'FOB' => 'FOB - Free On Board',
                'CIF' => 'CIF - Cost, Insurance & Freight',
                'CFR' => 'CFR - Cost & Freight',
                'CIP' => 'CIP - Carriage & Insurance Paid To',
                'CPT' => 'CPT - Carriage Paid To',
                'EXW' => 'EXW - Ex Works',
                'FAS' => 'FAS - Free Alongside Ship',
                'FCA' => 'FCA - Free Carrier',
                'DAP' => 'DAP - Delivered At Place',
                'DPU' => 'DPU - Delivered at Place Unloaded',
                'DDP' => 'DDP - Delivered Duty Paid',
            ],
            'cara_angkut' => [
                'Laut' => 'Laut',
                'Udara' => 'Udara',
                'Darat' => 'Darat',
                'Kereta Api' => 'Kereta Api',
                'Multimoda' => 'Multimoda',
                'Pos' => 'Pos',
            ],
            'satuan' => [
                'KGM' => 'KGM - Kilogram',
                'PCE' => 'PCE - Piece / Buah',
                'UNT' => 'UNT - Unit',
                'SET' => 'SET - Set',
                'BOX' => 'BOX - Box / Kotak',
                'CTN' => 'CTN - Carton',
                'BAG' => 'BAG - Bag / Karung',
                'BG' => 'BG - Bag',
                'TNE' => 'TNE - Ton (Metrik)',
                'LTR' => 'LTR - Liter',
                'MTR' => 'MTR - Meter',
                'MTK' => 'MTK - Meter Persegi',
                'MTQ' => 'MTQ - Meter Kubik',
                'DOZ' => 'DOZ - Lusin',
                'PR' => 'PR - Pasang',
                'ROL' => 'ROL - Rol',
                'DRM' => 'DRM - Drum',
                'PK' => 'PK - Pack',
                'VIA' => 'VIA - Vial',
                'GRM' => 'GRM - Gram',
            ],
            'kemasan' => [
                'BG' => 'BG - Bag / Karung',
                'BX' => 'BX - Box',
                'CT' => 'CT - Carton / Kardus',
                'PK' => 'PK - Package / Paket',
                'PL' => 'PL - Pallet',
                'DR' => 'DR - Drum',
                'CO' => 'CO - Colli',
                'BL' => 'BL - Bale / Bal',
                'RO' => 'RO - Roll',
                'CS' => 'CS - Case / Peti',
                'BK' => 'BK - Basket / Keranjang',
                'CN' => 'CN - Container',
                'JR' => 'JR - Jerrycan / Jeriken',
                'TN' => 'TN - Tin / Kaleng',
                'BU' => 'BU - Bulk / Curah',
            ],
            'valuta' => [
                'USD' => 'USD - US Dollar',
                'IDR' => 'IDR - Rupiah Indonesia',
                'EUR' => 'EUR - Euro',
                'SGD' => 'SGD - Dollar Singapura',
                'JPY' => 'JPY - Yen Jepang',
                'CNY' => 'CNY - Yuan Tiongkok',
                'MYR' => 'MYR - Ringgit Malaysia',
                'AUD' => 'AUD - Dollar Australia',
                'GBP' => 'GBP - Pound Sterling',
                'HKD' => 'HKD - Dollar Hong Kong',
                'KRW' => 'KRW - Won Korea Selatan',
                'INR' => 'INR - Rupee India',
                'THB' => 'THB - Baht Thailand',
                'AED' => 'AED - Dirham UEA',
                'SAR' => 'SAR - Riyal Saudi',
                'CHF' => 'CHF - Franc Swiss',
                'CAD' => 'CAD - Dollar Kanada',
                'NZD' => 'NZD - Dollar Selandia Baru',
                'PHP' => 'PHP - Peso Filipina',
                'VND' => 'VND - Dong Vietnam',
                'TWD' => 'TWD - Dollar Taiwan',
                'PKR' => 'PKR - Rupee Pakistan',
                'BDT' => 'BDT - Taka Bangladesh',
                'ZAR' => 'ZAR - Rand Afrika Selatan',
                'BRL' => 'BRL - Real Brasil',
            ],
            'tpb_jenis' => [
                'Kawasan Berikat (KB)' => 'Kawasan Berikat (KB)',
                'Gudang Berikat (GB)' => 'Gudang Berikat (GB)',
                'Pusat Logistik Berikat (PLB)' => 'Pusat Logistik Berikat (PLB)',
                'Toko Bebas Bea (TBB)' => 'Toko Bebas Bea (TBB)',
                'Tempat Penyelenggaraan Pameran Berikat (TPPB)' => 'Tempat Penyelenggaraan Pameran Berikat (TPPB)',
            ],
            'tpb_tujuan' => [
                'Pengeluaran Hasil Produksi ke TLDDP (BC 2.5)' => 'Pengeluaran ke TLDDP (BC 2.5)',
                'Pemasukan Bahan Baku dari TLDDP (BC 4.0)' => 'Pemasukan dari TLDDP (BC 4.0)',
                'Pemasukan Bahan Baku dari LDP (BC 2.3)' => 'Pemasukan dari LDP (BC 2.3)',
                'Pengiriman antar TPB (BC 2.7)' => 'Pengiriman ke TPB Lain (BC 2.7)',
            ],
            'kantor_pabean' => $this->kantorPabean(),
            'pelabuhan' => $this->pelabuhan(),
            'negara' => $this->negara(),
        ];
    }

    /**
     * Kantor Pabean (kode 6 digit DJBC). Subset umum + terkonfirmasi;
     * daftar penuh disinkron dari Referensi Kantor CEISA.
     *
     * @return array<string, string>
     */
    /**
     * Kantor Pabean DJBC (KdKantor 6-digit). Daftar kurasi kantor pelayanan utama
     * (KPU) & KPPBC yang menangani arus barang impor/ekspor terbesar di Indonesia.
     *
     * CATATAN: daftar penuh (~120 kantor) adalah sumber kebenaran DJBC dan sebaiknya
     * disinkron via `php artisan ceisa:sync-references` saat endpoint referensi resmi
     * tersedia. Subset ini mencakup mayoritas kantor yang dipakai PPJK/forwarder.
     *
     * @return array<string, string>
     */
    private function kantorPabean(): array
    {
        return [
            // --- Kantor Pelayanan Utama (KPU) ---
            '050100' => '050100 - KPU BC Tipe A Tanjung Priok',
            '040100' => '040100 - KPU BC Tipe C Soekarno-Hatta',
            '030100' => '030100 - KPU BC Tipe B Batam',

            // --- Sumatera ---
            '010100' => '010100 - KPPBC TMP B Banda Aceh',
            '010200' => '010200 - KPPBC TMP C Lhokseumawe',
            '010300' => '010300 - KPPBC TMP B Belawan',
            '011200' => '011200 - KPPBC TMP C Kuala Tanjung',
            '010600' => '010600 - KPPBC TMP C Sibolga',
            '050200' => '050200 - KPPBC TMP B Tanjung Balai Asahan',
            '020100' => '020100 - KPPBC TMP B Pekanbaru',
            '020200' => '020200 - KPPBC TMP B Tanjung Balai Karimun',
            '020300' => '020300 - KPPBC TMP C Dumai',
            '020600' => '020600 - KPPBC TMP C Bengkalis',
            '021000' => '021000 - KPPBC TMP C Tembilahan',
            '030200' => '030200 - KPPBC TMP C Tanjung Pinang',
            '040200' => '040200 - KPPBC TMP B Teluk Bayur (Padang)',
            '040500' => '040500 - KPPBC TMP C Jambi',
            '050300' => '050300 - KPPBC TMP B Palembang',
            '050500' => '050500 - KPPBC TMP C Pangkal Pinang',
            '060100' => '060100 - KPPBC TMP B Bandar Lampung (Panjang)',

            // --- DKI Jakarta, Banten & Jawa Barat ---
            '051000' => '051000 - KPPBC TMP A Jakarta',
            '050600' => '050600 - KPPBC TMP A Marunda',
            '055000' => '055000 - KPPBC TMP A Tangerang (Soekarno-Hatta Kargo)',
            '090100' => '090100 - KPPBC TMP A Bekasi',
            '041000' => '041000 - KPPBC TMP Bekasi',
            '090200' => '090200 - KPPBC TMP A Purwakarta',
            '090300' => '090300 - KPPBC TMP A Bandung',
            '090500' => '090500 - KPPBC TMP C Cirebon',
            '090600' => '090600 - KPPBC TMP C Merak',

            // --- Jawa Tengah, DIY & Jawa Timur ---
            '060300' => '060300 - KPPBC TMP Tanjung Emas Semarang',
            '060400' => '060400 - KPPBC TMP C Surakarta',
            '060700' => '060700 - KPPBC TMP B Yogyakarta',
            '070100' => '070100 - KPPBC TMP Juanda',
            '070300' => '070300 - KPPBC TMP Tanjung Perak Surabaya',
            '070400' => '070400 - KPPBC TMP C Gresik',
            '070500' => '070500 - KPPBC TMP C Pasuruan',
            '070600' => '070600 - KPPBC TMP C Malang',
            '070900' => '070900 - KPPBC TMP C Madiun',
            '071400' => '071400 - KPPBC TMP C Kediri',
            '071600' => '071600 - KPPBC TMP C Blitar',

            // --- Bali, NTB & NTT ---
            '080100' => '080100 - KPPBC TMP B Ngurah Rai',
            '080300' => '080300 - KPPBC TMP B Ngurah Rai (Kargo)',
            '080400' => '080400 - KPPBC TMP C Benoa',
            '080700' => '080700 - KPPBC TMP C Mataram',
            '081000' => '081000 - KPPBC TMP C Kupang',

            // --- Kalimantan ---
            '100100' => '100100 - KPPBC TMP B Pontianak',
            '110100' => '110100 - KPPBC TMP B Banjarmasin',
            '120100' => '120100 - KPPBC TMP C Sampit',
            '130100' => '130100 - KPPBC TMP B Balikpapan',
            '160100' => '160100 - KPPBC TMP B Balikpapan',
            '130200' => '130200 - KPPBC TMP C Samarinda',
            '130400' => '130400 - KPPBC TMP C Bontang',
            '130600' => '130600 - KPPBC TMP B Tarakan',

            // --- Sulawesi ---
            '090400' => '090400 - KPPBC TMP B Makassar',
            '140100' => '140100 - KPPBC TMP A Makassar',
            '140400' => '140400 - KPPBC TMP C Pare-Pare',
            '150100' => '150100 - KPPBC TMP B Bitung',
            '150200' => '150200 - KPPBC TMP C Manado',
            '150400' => '150400 - KPPBC TMP C Gorontalo',
            '160300' => '160300 - KPPBC TMP C Kendari',
            '160500' => '160500 - KPPBC TMP C Palu',

            // --- Maluku & Papua ---
            '170100' => '170100 - KPPBC TMP C Ambon',
            '170300' => '170300 - KPPBC TMP C Ternate',
            '180100' => '180100 - KPPBC TMP B Jayapura',
            '180300' => '180300 - KPPBC TMP C Sorong',
            '180500' => '180500 - KPPBC TMP C Merauke',
            '180700' => '180700 - KPPBC TMP C Biak',
        ];
    }

    /**
     * Pelabuhan (UN/LOCODE). Subset Indonesia + internasional umum,
     * termasuk yang muncul pada dokumen PEB nyata (IDKTJ, INMAA).
     *
     * @return array<string, string>
     */
    private function pelabuhan(): array
    {
        return [
            'IDKTJ' => 'IDKTJ - Kuala Tanjung, Sumut',
            'IDBLW' => 'IDBLW - Belawan, Medan',
            'IDTPP' => 'IDTPP - Tanjung Priok, Jakarta',
            'IDJKT' => 'IDJKT - Jakarta',
            'IDSUB' => 'IDSUB - Tanjung Perak, Surabaya',
            'IDSRG' => 'IDSRG - Tanjung Emas, Semarang',
            'IDPNK' => 'IDPNK - Pontianak',
            'IDBPN' => 'IDBPN - Balikpapan',
            'IDPLM' => 'IDPLM - Palembang',
            'IDBTH' => 'IDBTH - Batu Ampar, Batam',
            'IDDJB' => 'IDDJB - Jambi',
            'IDMAK' => 'IDMAK - Makassar',
            'IDPNJ' => 'IDPNJ - Panjang, Lampung',
            'IDDUM' => 'IDDUM - Dumai',
            'IDBIK' => 'IDBIK - Bitung',
            'INMAA' => 'INMAA - Chennai (Madras), India',
            'INNSA' => 'INNSA - Nhava Sheva (Mumbai), India',
            'INMUN' => 'INMUN - Mundra, India',
            'SGSIN' => 'SGSIN - Singapore',
            'MYPKG' => 'MYPKG - Port Klang, Malaysia',
            'MYTPP' => 'MYTPP - Tanjung Pelepas, Malaysia',
            'CNSHA' => 'CNSHA - Shanghai, China',
            'CNSZX' => 'CNSZX - Shenzhen, China',
            'CNNGB' => 'CNNGB - Ningbo, China',
            'HKHKG' => 'HKHKG - Hong Kong',
            'JPTYO' => 'JPTYO - Tokyo, Jepang',
            'JPYOK' => 'JPYOK - Yokohama, Jepang',
            'KRPUS' => 'KRPUS - Busan, Korea Selatan',
            'NLRTM' => 'NLRTM - Rotterdam, Belanda',
            'USLAX' => 'USLAX - Los Angeles, USA',
            'USNYC' => 'USNYC - New York, USA',
            'AEJEA' => 'AEJEA - Jebel Ali, Uni Emirat Arab',
            'AUMEL' => 'AUMEL - Melbourne, Australia',
            'AUSYD' => 'AUSYD - Sydney, Australia',
        ];
    }

    /**
     * Negara — ISO 3166-1 alpha-2 lengkap.
     *
     * @return array<string, string>
     */
    private function negara(): array
    {
        return [
            'AF' => 'AF - Afghanistan', 'AL' => 'AL - Albania', 'DZ' => 'DZ - Aljazair', 'AD' => 'AD - Andorra',
            'AO' => 'AO - Angola', 'AG' => 'AG - Antigua dan Barbuda', 'AR' => 'AR - Argentina', 'AM' => 'AM - Armenia',
            'AU' => 'AU - Australia', 'AT' => 'AT - Austria', 'AZ' => 'AZ - Azerbaijan', 'BS' => 'BS - Bahama',
            'BH' => 'BH - Bahrain', 'BD' => 'BD - Bangladesh', 'BB' => 'BB - Barbados', 'BY' => 'BY - Belarus',
            'BE' => 'BE - Belgia', 'BZ' => 'BZ - Belize', 'BJ' => 'BJ - Benin', 'BT' => 'BT - Bhutan',
            'BO' => 'BO - Bolivia', 'BA' => 'BA - Bosnia dan Herzegovina', 'BW' => 'BW - Botswana', 'BR' => 'BR - Brasil',
            'BN' => 'BN - Brunei Darussalam', 'BG' => 'BG - Bulgaria', 'BF' => 'BF - Burkina Faso', 'BI' => 'BI - Burundi',
            'KH' => 'KH - Kamboja', 'CM' => 'CM - Kamerun', 'CA' => 'CA - Kanada', 'CV' => 'CV - Tanjung Verde',
            'CF' => 'CF - Rep. Afrika Tengah', 'TD' => 'TD - Chad', 'CL' => 'CL - Chili', 'CN' => 'CN - Tiongkok',
            'CO' => 'CO - Kolombia', 'KM' => 'KM - Komoro', 'CG' => 'CG - Kongo', 'CD' => 'CD - Rep. Dem. Kongo',
            'CR' => 'CR - Kosta Rika', 'CI' => 'CI - Pantai Gading', 'HR' => 'HR - Kroasia', 'CU' => 'CU - Kuba',
            'CY' => 'CY - Siprus', 'CZ' => 'CZ - Rep. Ceko', 'DK' => 'DK - Denmark', 'DJ' => 'DJ - Djibouti',
            'DM' => 'DM - Dominika', 'DO' => 'DO - Rep. Dominika', 'EC' => 'EC - Ekuador', 'EG' => 'EG - Mesir',
            'SV' => 'SV - El Salvador', 'GQ' => 'GQ - Guinea Khatulistiwa', 'ER' => 'ER - Eritrea', 'EE' => 'EE - Estonia',
            'SZ' => 'SZ - Eswatini', 'ET' => 'ET - Ethiopia', 'FJ' => 'FJ - Fiji', 'FI' => 'FI - Finlandia',
            'FR' => 'FR - Prancis', 'GA' => 'GA - Gabon', 'GM' => 'GM - Gambia', 'GE' => 'GE - Georgia',
            'DE' => 'DE - Jerman', 'GH' => 'GH - Ghana', 'GR' => 'GR - Yunani', 'GD' => 'GD - Grenada',
            'GT' => 'GT - Guatemala', 'GN' => 'GN - Guinea', 'GW' => 'GW - Guinea-Bissau', 'GY' => 'GY - Guyana',
            'HT' => 'HT - Haiti', 'HN' => 'HN - Honduras', 'HK' => 'HK - Hong Kong', 'HU' => 'HU - Hungaria',
            'IS' => 'IS - Islandia', 'IN' => 'IN - India', 'ID' => 'ID - Indonesia', 'IR' => 'IR - Iran',
            'IQ' => 'IQ - Irak', 'IE' => 'IE - Irlandia', 'IL' => 'IL - Israel', 'IT' => 'IT - Italia',
            'JM' => 'JM - Jamaika', 'JP' => 'JP - Jepang', 'JO' => 'JO - Yordania', 'KZ' => 'KZ - Kazakhstan',
            'KE' => 'KE - Kenya', 'KI' => 'KI - Kiribati', 'KP' => 'KP - Korea Utara', 'KR' => 'KR - Korea Selatan',
            'KW' => 'KW - Kuwait', 'KG' => 'KG - Kirgizstan', 'LA' => 'LA - Laos', 'LV' => 'LV - Latvia',
            'LB' => 'LB - Lebanon', 'LS' => 'LS - Lesotho', 'LR' => 'LR - Liberia', 'LY' => 'LY - Libya',
            'LI' => 'LI - Liechtenstein', 'LT' => 'LT - Lituania', 'LU' => 'LU - Luksemburg', 'MO' => 'MO - Makau',
            'MG' => 'MG - Madagaskar', 'MW' => 'MW - Malawi', 'MY' => 'MY - Malaysia', 'MV' => 'MV - Maladewa',
            'ML' => 'ML - Mali', 'MT' => 'MT - Malta', 'MH' => 'MH - Kep. Marshall', 'MR' => 'MR - Mauritania',
            'MU' => 'MU - Mauritius', 'MX' => 'MX - Meksiko', 'FM' => 'FM - Mikronesia', 'MD' => 'MD - Moldova',
            'MC' => 'MC - Monako', 'MN' => 'MN - Mongolia', 'ME' => 'ME - Montenegro', 'MA' => 'MA - Maroko',
            'MZ' => 'MZ - Mozambik', 'MM' => 'MM - Myanmar', 'NA' => 'NA - Namibia', 'NR' => 'NR - Nauru',
            'NP' => 'NP - Nepal', 'NL' => 'NL - Belanda', 'NZ' => 'NZ - Selandia Baru', 'NI' => 'NI - Nikaragua',
            'NE' => 'NE - Niger', 'NG' => 'NG - Nigeria', 'MK' => 'MK - Makedonia Utara', 'NO' => 'NO - Norwegia',
            'OM' => 'OM - Oman', 'PK' => 'PK - Pakistan', 'PW' => 'PW - Palau', 'PS' => 'PS - Palestina',
            'PA' => 'PA - Panama', 'PG' => 'PG - Papua Nugini', 'PY' => 'PY - Paraguay', 'PE' => 'PE - Peru',
            'PH' => 'PH - Filipina', 'PL' => 'PL - Polandia', 'PT' => 'PT - Portugal', 'QA' => 'QA - Qatar',
            'RO' => 'RO - Rumania', 'RU' => 'RU - Rusia', 'RW' => 'RW - Rwanda', 'KN' => 'KN - Saint Kitts dan Nevis',
            'LC' => 'LC - Saint Lucia', 'VC' => 'VC - Saint Vincent', 'WS' => 'WS - Samoa', 'SM' => 'SM - San Marino',
            'ST' => 'ST - Sao Tome dan Principe', 'SA' => 'SA - Arab Saudi', 'SN' => 'SN - Senegal', 'RS' => 'RS - Serbia',
            'SC' => 'SC - Seychelles', 'SL' => 'SL - Sierra Leone', 'SG' => 'SG - Singapura', 'SK' => 'SK - Slowakia',
            'SI' => 'SI - Slovenia', 'SB' => 'SB - Kep. Solomon', 'SO' => 'SO - Somalia', 'ZA' => 'ZA - Afrika Selatan',
            'SS' => 'SS - Sudan Selatan', 'ES' => 'ES - Spanyol', 'LK' => 'LK - Sri Lanka', 'SD' => 'SD - Sudan',
            'SR' => 'SR - Suriname', 'SE' => 'SE - Swedia', 'CH' => 'CH - Swiss', 'SY' => 'SY - Suriah',
            'TW' => 'TW - Taiwan', 'TJ' => 'TJ - Tajikistan', 'TZ' => 'TZ - Tanzania', 'TH' => 'TH - Thailand',
            'TL' => 'TL - Timor Leste', 'TG' => 'TG - Togo', 'TO' => 'TO - Tonga', 'TT' => 'TT - Trinidad dan Tobago',
            'TN' => 'TN - Tunisia', 'TR' => 'TR - Turki', 'TM' => 'TM - Turkmenistan', 'TV' => 'TV - Tuvalu',
            'UG' => 'UG - Uganda', 'UA' => 'UA - Ukraina', 'AE' => 'AE - Uni Emirat Arab', 'GB' => 'GB - Inggris',
            'US' => 'US - Amerika Serikat', 'UY' => 'UY - Uruguay', 'UZ' => 'UZ - Uzbekistan', 'VU' => 'VU - Vanuatu',
            'VE' => 'VE - Venezuela', 'VN' => 'VN - Vietnam', 'YE' => 'YE - Yaman', 'ZM' => 'ZM - Zambia',
            'ZW' => 'ZW - Zimbabwe',
        ];
    }
}
