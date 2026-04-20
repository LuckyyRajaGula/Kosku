<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class KoskuUserSeeder extends Seeder
{
    /**
     * Seed akun login default aplikasi KosKu.
     */
    public function run(): void
    {
        $users = [
            [
                'nama' => 'Budi Santoso',
                'username' => 'budi.pemilik',
                'email' => 'pemilik@kosku.com',
                'password' => Hash::make('pemilik123'),
                'no_telpon' => '081234567891',
                'role' => 'pemilik',
                'status_akun' => 'Aktif',
            ],
            [
                'nama' => 'Siti Nurhaliza',
                'username' => 'siti.pengelola',
                'email' => 'pengelola@kosku.com',
                'password' => Hash::make('pengelola123'),
                'no_telpon' => '081234567892',
                'role' => 'pengelola',
                'status_akun' => 'Aktif',
            ],
            [
                'nama' => 'Ahmad Wijaya',
                'username' => 'ahmad.penyewa',
                'email' => 'penyewa@kosku.com',
                'password' => Hash::make('penyewa123'),
                'no_telpon' => '081234567893',
                'role' => 'penyewa',
                'status_akun' => 'Aktif',
            ],
        ];

        DB::table('user')->upsert(
            $users,
            ['email'],
            ['nama', 'username', 'password', 'no_telpon', 'role', 'status_akun']
        );
    }
}
