<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Komplain extends Model
{
    use HasFactory;

    protected $table = 'komplain';

    protected $primaryKey = 'id_komplain';

    public $timestamps = false;

    protected $fillable = [
        'id_penyewa',
        'jenis_komplain',
        'deskripsi',
        'bukti_foto',
        'tanggal',
        'status_penanganan',
        'respon',
        'tanggal_selesai',
    ];

    public function penyewa()
    {
        return $this->belongsTo(Penyewa::class, 'id_penyewa', 'id_penyewa');
    }
}
