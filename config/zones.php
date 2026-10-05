<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 8 Zona Pembinaan — Program Kerja Kesiswaan 2026-2027
    |--------------------------------------------------------------------------
    | Range diurutkan dari bawah (threshold terendah).
    | getZone() return zona pertama yang match dari bawah.
    */

    'zones' => [
        [
            'name'  => 'Intervensi Khusus',
            'min'   => null,
            'max'   => 1499,
            'responsible' => 'Kepala Sekolah',
            'action' => 'Rapat dewan guru',
            'color'  => 'danger',
        ],
        [
            'name'  => 'Pembinaan 4',
            'min'   => 1500,
            'max'   => 1599,
            'responsible' => 'Tim Kesiswaan',
            'action' => 'Pendampingan langsung',
            'color'  => 'warning',
        ],
        [
            'name'  => 'Pembinaan 3',
            'min'   => 1600,
            'max'   => 1699,
            'responsible' => 'Guru + BK + Orang Tua',
            'action' => 'Pemanggilan orang tua',
            'color'  => 'warning',
        ],
        [
            'name'  => 'Pembinaan 2',
            'min'   => 1700,
            'max'   => 1799,
            'responsible' => 'Guru + BK',
            'action' => 'Pendampingan & pembinaan',
            'color'  => 'warning',
        ],
        [
            'name'  => 'Pembinaan 1',
            'min'   => 1800,
            'max'   => 1899,
            'responsible' => 'Guru',
            'action' => 'Pendampingan & pembinaan',
            'color'  => 'info',
        ],
        [
            'name'  => 'Normal',
            'min'   => 1900,
            'max'   => 2199,
            'responsible' => null,
            'action' => null,
            'color'  => 'success',
        ],
        [
            'name'  => 'Luar Biasa',
            'min'   => 2200,
            'max'   => 2499,
            'responsible' => null,
            'action' => null,
            'color'  => 'success',
        ],
        [
            'name'  => 'Istimewa',
            'min'   => 2500,
            'max'   => null,
            'responsible' => null,
            'action' => 'Sertifikat "Anugerah Genep Waluya Utama"',
            'color'  => 'primary',
        ],
    ],

    'default_balance' => 2000,
];
