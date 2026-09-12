<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TicketPdfService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TicketCalibrationCommand extends Command
{
    protected $signature = 'tickets:calibracion {--output=output/pdf/calibracion-epson-tm-u220.pdf}';

    protected $description = 'Genera un PDF de calibración de la Epson TM-U220 sin consultar ventas';

    public function handle(TicketPdfService $tickets): int
    {
        $path = $this->option('output');
        File::ensureDirectoryExists(dirname($path));
        $tickets->render('tickets.calibration')->save($path);
        $this->info("PDF de calibración: {$path}. Imprima al 100 %, en rollo de 76 mm.");

        return self::SUCCESS;
    }
}
