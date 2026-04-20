<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kamar extends Model
{
    use HasFactory;

    protected $table = 'kamar';

    protected $primaryKey = 'id_kamar';

    public $timestamps = false;

    protected $fillable = [
        'no_kamar',
        'tipe_kamar',
        'harga',
        'status_ketersediaan',
        'fasilitias',
        'luas_kamar',
    ];

    public function penyewa()
    {
        return $this->hasMany(Penyewa::class, 'id_kamar', 'id_kamar');
    }
}
