<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Penyewa;
use App\Models\TagihanPembayaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Sprint1And2Test extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    protected function clearJwtState(): void
    {
        \Tymon\JWTAuth\Facades\JWTAuth::unsetToken();
        $this->app->forgetInstance('tymon.jwt.auth');
        $this->app->forgetInstance('tymon.jwt');
        $this->app->forgetInstance(\Tymon\JWTAuth\JWTAuth::class);
        $this->app->forgetInstance(\Tymon\JWTAuth\JWT::class);
        \Illuminate\Support\Facades\Auth::forgetGuards();
    }

    /**
     * PBI-001: Autentikasi Pengguna & JWT
     */
    public function test_pbi_001_authentication_flow(): void
    {
        // 1. Web Login: Tampilan Halaman Login
        $response = $this->get('/login');
        $response->assertStatus(200);

        // 2. Web Login: Aksi Login Sukses
        $response = $this->post('/login', [
            'username' => 'budi.pemilik',
            'password' => 'pemilik123',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertTrue(session()->has('kosku_user'));

        // 3. Web Login: Aksi Login Gagal
        $response = $this->post('/login', [
            'username' => 'budi.pemilik',
            'password' => 'passwordsalah',
        ]);
        $response->assertRedirect('/login');
        $response->assertSessionHas('error');

        // 4. API JWT Login: Sukses
        $response = $this->postJson('/api/auth/login', [
            'username' => 'budi.pemilik',
            'password' => 'pemilik123',
        ]);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user'
            ]);

        $token = $response->json('access_token');
        $this->clearJwtState();

        // 5. API JWT Proteksi Rute: Tanpa Token (401)
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);

        // 6. API JWT Proteksi Rute: Dengan Token (200)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');
        
        $response->assertStatus(200)
            ->assertJsonPath('user.username', 'budi.pemilik');
    }

    /**
     * PBI-014: Manajemen Akun Pengelola
     */
    public function test_pbi_014_manager_account_management(): void
    {
        // Login sebagai Pemilik (Owner) untuk mendapatkan token JWT
        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'budi.pemilik',
            'password' => 'pemilik123',
        ]);
        $ownerToken = $loginResponse->json('access_token');
        $this->clearJwtState();

        // Login sebagai Pengelola untuk mendapatkan token JWT (untuk tes otorisasi)
        $loginResponse2 = $this->postJson('/api/auth/login', [
            'username' => 'siti.pengelola',
            'password' => 'pengelola123',
        ]);
        $managerToken = $loginResponse2->json('access_token');
        $this->clearJwtState();

        // 1. Buat Pengelola Baru (Sukses oleh Pemilik)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $ownerToken,
        ])->postJson('/api/auth/owner/pengelola', [
            'nama' => 'Joko Slamet',
            'username' => 'joko.pengelola',
            'email' => 'joko@kosku.com',
            'password' => 'jokopass123',
            'no_telpon' => '08987654321',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.username', 'joko.pengelola');

        $managerId = $response->json('data.id_user');

        // 2. Baca Daftar Pengelola (Sukses oleh Pemilik)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $ownerToken,
        ])->getJson('/api/auth/owner/pengelola');

        $response->assertStatus(200)
            ->assertJsonFragment(['username' => 'joko.pengelola']);

        // 3. Edit Data Pengelola (Sukses oleh Pemilik)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $ownerToken,
        ])->putJson("/api/auth/owner/pengelola/{$managerId}", [
            'nama' => 'Joko Slamet Santoso',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.nama', 'Joko Slamet Santoso');

        // 4. Nonaktifkan Akun Pengelola (Sukses oleh Pemilik)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $ownerToken,
        ])->patchJson("/api/auth/owner/pengelola/{$managerId}/status", [
            'status_akun' => 'Nonaktif',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status_akun', 'Nonaktif');

        $this->clearJwtState();

        // 5. Proteksi Rute: Coba akses oleh Pengelola (Harus 403 Forbidden)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $managerToken,
        ])->getJson('/api/auth/owner/pengelola');

        $response->assertStatus(403);
    }

    /**
     * PBI-005: Manajemen Data Kamar
     */
    public function test_pbi_005_room_management(): void
    {
        $user = User::query()->where('role', 'pengelola')->first();
        $userSession = [
            'id' => $user->id_user,
            'id_user' => $user->id_user,
            'nama' => $user->nama,
            'role' => $user->role,
        ];

        // 1. Tambah Kamar Baru
        $response = $this->withSession(['kosku_user' => $userSession])
            ->post('/kamar', [
                'no_kamar' => 'X999',
                'tipe_kamar' => 'AC',
                'harga' => 1500000,
                'status_ketersediaan' => 'Kosong',
                'fasilitias' => ['AC', 'WiFi'],
                'luas_kamar' => '3x4 m',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kamar', ['no_kamar' => 'X999']);

        $kamar = Kamar::query()->where('no_kamar', 'X999')->first();

        // 2. Ubah Data Kamar
        $response = $this->withSession(['kosku_user' => $userSession])
            ->put("/kamar/{$kamar->id_kamar}", [
                'no_kamar' => 'X999',
                'tipe_kamar' => 'AC',
                'harga' => 1750000,
                'status_ketersediaan' => 'Maintenance',
                'fasilitias' => ['AC', 'WiFi', 'TV'],
                'luas_kamar' => '3x4 m',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kamar', [
            'id_kamar' => $kamar->id_kamar,
            'harga' => 1750000,
            'status_ketersediaan' => 'Maintenance',
        ]);

        // 3. Hapus Kamar
        $response = $this->withSession(['kosku_user' => $userSession])
            ->delete("/kamar/{$kamar->id_kamar}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('kamar', ['id_kamar' => $kamar->id_kamar]);
    }

    /**
     * PBI-006: Manajemen Data Penyewa & Kontrak (termasuk Unggah Dokumen KTP & Kontrak)
     */
    public function test_pbi_006_tenant_management_and_uploads(): void
    {
        $user = User::query()->where('role', 'pengelola')->first();
        $userSession = [
            'id' => $user->id_user,
            'id_user' => $user->id_user,
            'nama' => $user->nama,
            'role' => $user->role,
        ];

        // Buat kamar kosong untuk ditempati penyewa baru
        $kamar = Kamar::query()->create([
            'no_kamar' => 'K999',
            'tipe_kamar' => 'AC',
            'harga' => 1200000,
            'status_ketersediaan' => 'Kosong',
        ]);

        $ktpFile = UploadedFile::fake()->image('ktp.jpg');
        $contractFile = UploadedFile::fake()->create('contract.pdf', 500);

        // 1. Daftarkan Penyewa Baru + Unggah Dokumen KTP & Kontrak
        $response = $this->withSession(['kosku_user' => $userSession])
            ->post('/penyewa', [
                'nama' => 'Rian Kurnia',
                'username' => 'rian.penyewa',
                'email' => 'rian@gmail.com',
                'password' => 'rianpass123',
                'no_telpon' => '08771234567',
                'id_kamar' => $kamar->id_kamar,
                'ktp' => '3273123456789001',
                'kontrak' => '1 Tahun',
                'tanggal_masuk' => '2026-05-01',
                'tanggal_keluar' => '2027-05-01',
                'dokumen_ktp' => $ktpFile,
                'dokumen_kontrak' => $contractFile,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('penyewa', ['nama' => 'Rian Kurnia']);

        $penyewa = Penyewa::query()->where('nama', 'Rian Kurnia')->first();
        $this->assertNotNull($penyewa->dokumen_kontrak);

        // 2. Ubah Data Penyewa
        $response = $this->withSession(['kosku_user' => $userSession])
            ->put("/penyewa/{$penyewa->id_penyewa}", [
                'nama' => 'Rian Kurnia Pratama',
                'username' => 'rian.penyewa',
                'email' => 'rian@gmail.com',
                'no_telpon' => '08777777777',
                'id_kamar' => $kamar->id_kamar,
                'ktp' => '3273123456789001',
                'kontrak' => '1 Tahun',
                'tanggal_masuk' => '2026-05-01',
                'tanggal_keluar' => '2027-05-01',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('penyewa', ['nama' => 'Rian Kurnia Pratama']);

        // 3. Checkout Penyewa (Status Kamar berubah kembali menjadi Kosong)
        $response = $this->withSession(['kosku_user' => $userSession])
            ->patch("/penyewa/{$penyewa->id_penyewa}/checkout");

        $response->assertRedirect();
        $this->assertDatabaseHas('penyewa', [
            'id_penyewa' => $penyewa->id_penyewa,
            'tanggal_selesai' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('kamar', [
            'id_kamar' => $kamar->id_kamar,
            'status_ketersediaan' => 'Kosong',
        ]);
    }

    /**
     * PBI-007 & PBI-008: Validasi & Pencatatan Pembayaran
     */
    public function test_pbi_007_008_payment_flow(): void
    {
        $pengelola = User::query()->where('role', 'pengelola')->first();
        $pengelolaSession = [
            'id' => $pengelola->id_user,
            'id_user' => $pengelola->id_user,
            'nama' => $pengelola->nama,
            'role' => $pengelola->role,
        ];

        // Buat data kamar dan penyewa untuk diuji
        $kamar = Kamar::query()->create([
            'no_kamar' => 'P999',
            'tipe_kamar' => 'AC',
            'harga' => 1500000,
            'status_ketersediaan' => 'Terisi',
        ]);

        // Buat user untuk penyewa agar sinkron dengan session 'id'
        $tenantUser = User::query()->create([
            'nama' => 'Bayu Samudra',
            'username' => 'bayu.penyewa',
            'email' => 'bayu@gmail.com',
            'password' => Hash::make('bayupass123'),
            'no_telpon' => '087711223344',
            'role' => 'penyewa',
            'status_akun' => 'Aktif',
        ]);

        $penyewa = Penyewa::query()->create([
            'id_user' => $tenantUser->id_user,
            'nama' => 'Bayu Samudra',
            'id_kamar' => $kamar->id_kamar,
            'ktp' => '1234567890123456',
            'kontrak' => '6 Bulan',
        ]);

        // 1. Pengelola Membuat Tagihan Baru (PBI-007 Backend)
        $response = $this->withSession(['kosku_user' => $pengelolaSession])
            ->post('/pembayaran', [
                'id_penyewa' => $penyewa->id_penyewa,
                'periode' => 'Mei 2026',
                'nominal' => 1500000,
                'tanggal_jatuh_tempo' => '2026-05-05',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tagihan_pembayaran', [
            'id_penyewa' => $penyewa->id_penyewa,
            'periode' => 'Mei 2026',
            'status' => 'Belum Bayar',
        ]);

        $tagihan = TagihanPembayaran::query()->where('id_penyewa', $penyewa->id_penyewa)->first();

        // 2. Penyewa Mengunggah Bukti Pembayaran (PBI-008 Fitur Unggah Bukti)
        $buktiFile = UploadedFile::fake()->image('receipt.jpg');
        $response = $this->withSession(['kosku_user' => [
            'id' => $tenantUser->id_user,
            'id_user' => $tenantUser->id_user,
            'nama' => 'Bayu Samudra',
            'role' => 'penyewa',
        ]])->post("/pembayaran/{$tagihan->id_tagihan}/upload-bukti", [
            'bukti_bayar' => $buktiFile,
            'metode_pembayaran' => 'Transfer Bank',
        ]);

        $response->assertRedirect();
        
        $tagihanUpdated = TagihanPembayaran::find($tagihan->id_tagihan);
        $this->assertNotNull($tagihanUpdated->bukti_bayar);

        // 3. Pengelola Memvalidasi dan Menandai Lunas (PBI-007 Validasi Pembayaran)
        $response = $this->withSession(['kosku_user' => $pengelolaSession])
            ->patch("/pembayaran/{$tagihan->id_tagihan}/tandai-lunas");

        $response->assertRedirect();
        $this->assertDatabaseHas('tagihan_pembayaran', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status' => 'Lunas',
        ]);
    }

    /**
     * PBI-009: Pembangkitan Tagihan Bulanan Otomatis (Console Command)
     */
    public function test_pbi_009_automatic_billing_generation(): void
    {
        // Bersihkan data penyewa agar pengetesan konsisten
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            DB::table('penyewa')->truncate();
            DB::table('tagihan_pembayaran')->truncate();
            DB::table('kamar')->truncate();
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('penyewa')->truncate();
            DB::table('tagihan_pembayaran')->truncate();
            DB::table('kamar')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // Buat kamar
        $kamar = Kamar::query()->create([
            'no_kamar' => 'A111',
            'tipe_kamar' => 'AC',
            'harga' => 2000000,
            'status_ketersediaan' => 'Terisi',
        ]);

        // Buat penyewa aktif (belum checkout/belum ada tanggal_selesai)
        Penyewa::query()->create([
            'nama' => 'Penyewa Aktif A',
            'id_kamar' => $kamar->id_kamar,
            'ktp' => '1111111111111111',
            'kontrak' => '1 Tahun',
            'tanggal_masuk' => '2026-01-01',
            'tanggal_keluar' => '2027-01-01',
        ]);

        // Jalankan perintah console pembangkitan tagihan otomatis
        $exitCode = Artisan::call('tagihan:generate', [
            '--bulan' => 5,
            '--tahun' => 2026,
            '--jatuh-tempo' => 5,
        ]);

        $this->assertEquals(0, $exitCode);

        // Pastikan tagihan otomatis terbuat di database
        $this->assertDatabaseHas('tagihan_pembayaran', [
            'periode' => 'Mei 2026',
            'nominal' => 2000000,
            'tanggal_jatuh_tempo' => '2026-05-05',
            'status' => 'Belum Bayar',
        ]);
    }
}
