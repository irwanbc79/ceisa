<?php

use App\Models\CeisaReference;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Daftar pelabuhan utama Indonesia & internasional (UN/LOCODE) untuk melengkapi dropdown CEISA 4.0.
     *
     * @var array<string, string>
     */
    private array $ports = [
        // Indonesia (Utama & Udara/Laut)
        'IDBLW' => 'IDBLW - Belawan, Indonesia',
        'IDKNO' => 'IDKNO - Kualanamu (Bandara), Indonesia',
        'IDKTJ' => 'IDKTJ - Kuala Tanjung, Indonesia',
        'IDTPP' => 'IDTPP - Tanjung Priok, Jakarta',
        'IDJKT' => 'IDJKT - Jakarta, Indonesia',
        'IDCGK' => 'IDCGK - Soekarno-Hatta (Bandara), Tangerang',
        'IDCDP' => 'IDCDP - Cikarang Dry Port, Indonesia',
        'IDMRK' => 'IDMRK - Merak, Banten',
        'IDCGN' => 'IDCGN - Cigading, Banten',
        'IDBDO' => 'IDBDO - Bandung / Gedebage, Indonesia',
        'IDSRG' => 'IDSRG - Tanjung Emas, Semarang',
        'IDSUB' => 'IDSUB - Tanjung Perak / Juanda, Surabaya',
        'IDMES' => 'IDMES - Medan, Indonesia',
        'IDDUM' => 'IDDUM - Dumai, Riau',
        'IDPKU' => 'IDPKU - Pekanbaru, Riau',
        'IDPER' => 'IDPER - Perawang, Riau',
        'IDBTH' => 'IDBTH - Batu Ampar / Hang Nadim, Batam',
        'IDSEK' => 'IDSEK - Sekupang, Batam',
        'IDKAB' => 'IDKAB - Kabil, Batam',
        'IDTNJ' => 'IDTNJ - Tanjung Pinang, Kepulauan Riau',
        'IDTBK' => 'IDTBK - Tanjung Balai Karimun, Kepulauan Riau',
        'IDPDG' => 'IDPDG - Teluk Bayur, Padang',
        'IDPLM' => 'IDPLM - Boom Baru, Palembang',
        'IDPNJ' => 'IDPNJ - Panjang, Lampung',
        'IDPNK' => 'IDPNK - Pontianak, Kalimantan Barat',
        'IDBDJ' => 'IDBDJ - Trisakti, Banjarmasin',
        'IDBPN' => 'IDBPN - Semayang, Balikpapan',
        'IDSRI' => 'IDSRI - Samarinda, Kalimantan Timur',
        'IDBXT' => 'IDBXT - Bontang, Kalimantan Timur',
        'IDTRK' => 'IDTRK - Tarakan, Kalimantan Utara',
        'IDMAK' => 'IDMAK - Soekarno-Hatta, Makassar',
        'IDBIT' => 'IDBIT - Bitung, Sulawesi Utara',
        'IDMDC' => 'IDMDC - Sam Ratulangi (Bandara), Manado',
        'IDKDI' => 'IDKDI - Kendari, Sulawesi Tenggara',
        'IDPLW' => 'IDPLW - Pantoloan, Palu',
        'IDBOA' => 'IDBOA - Benoa, Bali',
        'IDDPS' => 'IDDPS - Ngurah Rai (Bandara), Bali',
        'IDAMQ' => 'IDAMQ - Ambon, Maluku',
        'IDDJJ' => 'IDDJJ - Jayapura, Papua',
        'IDSOQ' => 'IDSOQ - Sorong, Papua Barat Daya',
        'IDTIM' => 'IDTIM - Amamapare / Timika, Papua Tengah',

        // Asia Tenggara
        'SGSIN' => 'SGSIN - Singapore, Singapore',
        'MYPKG' => 'MYPKG - Port Klang, Malaysia',
        'MYTPP' => 'MYTPP - Tanjung Pelepas, Malaysia',
        'MYPEN' => 'MYPEN - Penang, Malaysia',
        'MYKUL' => 'MYKUL - Kuala Lumpur (KLIA), Malaysia',
        'THLCH' => 'THLCH - Laem Chabang, Thailand',
        'THBKK' => 'THBKK - Bangkok, Thailand',
        'VNSGN' => 'VNSGN - Ho Chi Minh City (Cat Lai), Vietnam',
        'VNHPH' => 'VNHPH - Hai Phong, Vietnam',
        'PHMNL' => 'PHMNL - Manila, Philippines',
        'KHPNH' => 'KHPNH - Phnom Penh, Cambodia',
        'KHSIH' => 'KHSIH - Sihanoukville, Cambodia',
        'MMRGN' => 'MMRGN - Yangon, Myanmar',
        'BNMUA' => 'BNMUA - Muara, Brunei Darussalam',

        // Asia Timur & Selatan
        'HKHKG' => 'HKHKG - Hong Kong, Hong Kong SAR',
        'CNSHA' => 'CNSHA - Shanghai, China',
        'CNNGB' => 'CNNGB - Ningbo-Zhoushan, China',
        'CNSZX' => 'CNSZX - Shenzhen (Yantian/Shekou), China',
        'CNCAN' => 'CNCAN - Guangzhou (Nansha), China',
        'CNTAO' => 'CNTAO - Qingdao, China',
        'CNXMN' => 'CNXMN - Xiamen, China',
        'CNTXG' => 'CNTXG - Tianjin (Xingang), China',
        'CNDLC' => 'CNDLC - Dalian, China',
        'CNPVG' => 'CNPVG - Shanghai Pudong (Air), China',
        'TWKHH' => 'TWKHH - Kaohsiung, Taiwan',
        'TWKEL' => 'TWKEL - Keelung, Taiwan',
        'TWTPE' => 'TWTPE - Taipei / Taoyuan, Taiwan',
        'KRPUS' => 'KRPUS - Busan, South Korea',
        'KRINC' => 'KRINC - Incheon, South Korea',
        'JPTYO' => 'JPTYO - Tokyo, Japan',
        'JPYOK' => 'JPYOK - Yokohama, Japan',
        'JPOSA' => 'JPOSA - Osaka, Japan',
        'JPUKB' => 'JPUKB - Kobe, Japan',
        'JPNGO' => 'JPNGO - Nagoya, Japan',
        'JPFUK' => 'JPFUK - Fukuoka / Hakata, Japan',
        'INNSA' => 'INNSA - Nhava Sheva (Jawaharlal Nehru), India',
        'INMAA' => 'INMAA - Chennai, India',
        'INMUN' => 'INMUN - Mundra, India',
        'INCCU' => 'INCCU - Kolkata, India',
        'LKCMB' => 'LKCMB - Colombo, Sri Lanka',
        'BDCGP' => 'BDCGP - Chattogram (Chittagong), Bangladesh',
        'PKKHI' => 'PKKHI - Karachi, Pakistan',

        // Timur Tengah, Eropa, Amerika, Oseania
        'AEJEA' => 'AEJEA - Jebel Ali, Dubai, UAE',
        'AEDXB' => 'AEDXB - Dubai (Air/Port), UAE',
        'SAJED' => 'SAJED - Jeddah, Saudi Arabia',
        'SADMM' => 'SADMM - Dammam, Saudi Arabia',
        'OMSOH' => 'OMSOH - Sohar, Oman',
        'QAHMD' => 'QAHMD - Hamad Port, Qatar',
        'TRIST' => 'TRIST - Istanbul / Ambarli, Turkey',
        'EGPSD' => 'EGPSD - Port Said, Egypt',
        'NLRTM' => 'NLRTM - Rotterdam, Netherlands',
        'DEHAM' => 'DEHAM - Hamburg, Germany',
        'DEBRV' => 'DEBRV - Bremerhaven, Germany',
        'BEANR' => 'BEANR - Antwerp, Belgium',
        'GBFXT' => 'GBFXT - Felixstowe, United Kingdom',
        'GBSOU' => 'GBSOU - Southampton, United Kingdom',
        'FRLEH' => 'FRLEH - Le Havre, France',
        'ESALG' => 'ESALG - Algeciras, Spain',
        'ESBCN' => 'ESBCN - Barcelona, Spain',
        'ITGOA' => 'ITGOA - Genoa, Italy',
        'USLAX' => 'USLAX - Los Angeles, USA',
        'USLGB' => 'USLGB - Long Beach, USA',
        'USNYC' => 'USNYC - New York / Newark, USA',
        'USSAV' => 'USSAV - Savannah, USA',
        'USHOU' => 'USHOU - Houston, USA',
        'USSEA' => 'USSEA - Seattle, USA',
        'CAVAN' => 'CAVAN - Vancouver, Canada',
        'BRSSZ' => 'BRSSZ - Santos, Brazil',
        'AUSYD' => 'AUSYD - Sydney, Australia',
        'AUMEL' => 'AUMEL - Melbourne, Australia',
        'AUBNE' => 'AUBNE - Brisbane, Australia',
        'AUFRE' => 'AUFRE - Fremantle, Australia',
        'NZAKL' => 'NZAKL - Auckland, New Zealand',
        'ZADUR' => 'ZADUR - Durban, South Africa',
    ];

    public function up(): void
    {
        $now = now();
        $rows = [];
        $sort = 1;

        foreach ($this->ports as $code => $label) {
            $rows[] = [
                'type' => 'pelabuhan',
                'code' => $code,
                'label' => $label,
                'meta' => null,
                'sort' => $sort++,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        CeisaReference::upsert($rows, ['type', 'code'], ['label', 'active', 'updated_at']);
    }

    public function down(): void
    {
        CeisaReference::query()
            ->where('type', 'pelabuhan')
            ->whereIn('code', array_keys($this->ports))
            ->delete();
    }
};
