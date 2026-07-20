<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class KardexCorregirAperturaCommand extends Command
{
    protected $signature = 'kardex:corregir-apertura';

    protected $description = 'Comando obsoleto; use kardex:reconciliar-apertura con el CSV autoritativo.';

    public function handle(): int
    {
        $this->error('Este comando fue deshabilitado porque usaba una apertura incompleta y podia danar el stock.');
        $this->line('Use primero el modo seguro:');
        $this->line('php artisan kardex:reconciliar-apertura database/data/kardex_apertura_20260531.csv --fecha=2026-05-31 --dry-run');

        return self::FAILURE;
    }
}
