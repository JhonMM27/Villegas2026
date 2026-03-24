<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SunatCertificado extends Model
{
    use HasFactory;

    protected $table = 'sunat_certificados';

    protected $fillable = [
        'nombre',
        'certificado_path',
        'password',
        'activo',
        'expires_at'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'expires_at' => 'datetime',
    ];
}
