<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TelefonoTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TelefonoTicketService
{
    /** Read current configuration; only a missing table uses the installation fallback. */
    public function current(): array
    {
        $installed = Schema::hasTable('telefonos_ticket');
        $record = $installed ? TelefonoTicket::findOrFail(1) : null;

        return [
            'id' => 1,
            'installed' => $installed,
            'telefono_1' => $record ? $record->telefono_1 : '967984895',
            'telefono_2' => $record ? $record->telefono_2 : '978431737',
            'updated_at' => $record?->updated_at?->format('d/m/Y H:i:s'),
        ];
    }

    /** Update the single configuration record without creating additional rows. */
    public function update(array $data): void
    {
        if (! Schema::hasTable('telefonos_ticket')) {
            throw ValidationException::withMessages(['telefono_1' => 'Falta instalar la tabla Teléfonos Ticket.']);
        }

        DB::transaction(fn () => TelefonoTicket::findOrFail(1)->update($data));
    }
}
