<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelefonoTicket extends Model
{
    protected $table = 'telefonos_ticket';

    protected $fillable = ['telefono_1', 'telefono_2'];
}
