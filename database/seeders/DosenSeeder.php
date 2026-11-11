<?php

namespace Database\Seeders;

use App\Models\Dosen;
use Illuminate\Database\Seeder;

class DosenSeeder extends Seeder
{
    public function run(): void
    {
        // [NIDN, nama, program studi]
        $data = [
            [null,          'rinib',                           'Teknik Mesin'],
            ['0014573830',  'Jati Murti Nastiti',              'Teknik Sipil'],
            ['00099888777', 'Jemmy Edwin Bororing,S.T., M.Eng.', 'Informatika'],
            ['11223344',    'Elida Budi Aningtyas',            'Teknik Sipil'],
        ];

        foreach ($data as [$nidn, $nama, $prodi]) {
            Dosen::updateOrCreate(['nama' => $nama], ['nidn' => $nidn, 'program_studi' => $prodi]);
        }
    }
}