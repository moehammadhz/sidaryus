<?php

// [label, ikon, nama route]. Isi null pada route jika halamannya belum dibuat (hanya tampil untuk admin).
// Menu lain otomatis disembunyikan bagi role yang tidak punya akses ke route-nya.
return [
    'Utama' => [
        ['Dashboard',          'home',      'dashboard'],
        ['Edit Konten',        'edit',      'admin.konten'],
    ],
    'Pendaftaran' => [
        ['Kelola Persyaratan', 'checklist', 'admin.persyaratan.index'],
        ['Dokumen Mahasiswa',  'file',      'admin.dokumen.index'],
        ['Jadwal Pendadaran',  'calendar',  'admin.jadwal.index'],
    ],
    'Yudisium' => [
        ['Data Yudisium',      'cap',       'admin.yudisium.index'],
        ['Periode Yudisium',   'calendar',  'admin.periode.index'],
    ],
    'Pengguna' => [
        ['Data Mahasiswa',     'users',     'admin.mahasiswa.index'],
        ['Data Dosen',         'cap',       'admin.dosen.index'],
        ['Data Petugas',       'userplus',  'admin.petugas.index'],
    ],
    'BAAK' => [
        ['Kelengkapan Mahasiswa', 'clipboard', 'baak.mahasiswa.index'],
    ],
];
