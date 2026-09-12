<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TicketPdfService;
use Illuminate\Http\Response;

class TicketCalibrationController extends Controller
{
    /** Generate a calibration receipt without accessing business records. */
    public function __invoke(TicketPdfService $tickets): Response
    {
        return $tickets->render('tickets.calibration')->stream('calibracion-epson-tm-u220.pdf');
    }
}
