<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penyewa extends Model
{
    use HasFactory;

    protected $table = 'penyewa';

    protected $primaryKey = 'id_penyewa';

    public $timestamps = false;

    protected $fillable = [
        'id_user',
        'id_kamar',
        'nama',
        'ktp',
        'kontrak',
        'dokumen_kontrak',
        'tanggal_masuk',
        'tanggal_keluar',
        'tanggal_selesai',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function kamar()
    {
        return $this->belongsTo(Kamar::class, 'id_kamar', 'id_kamar');
    }
}
