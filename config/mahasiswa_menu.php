<?php

// [label, ikon, nama route]. Isi null pada route jika halamannya belum dibuat.
return [
    'Menu' => [
        ['Dashboard',                'home',     'mahasiswa.dashboard'],
        ['Upload Dokumen',           'upload',   'mahasiswa.dokumen.index'],
        ['Upload Jadwal Pendadaran', 'calendar', 'mahasiswa.jadwal.index'],
        ['Upload Bukti Pembayaran',  'upload',   'mahasiswa.pembayaran.index'],
        ['Undangan Jadwal Ujian',    'file',     'mahasiswa.undangan.index'],
    ],
];
