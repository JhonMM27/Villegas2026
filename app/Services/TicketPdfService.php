<?php

declare(strict_types=1);

namespace App\Services;

use Barryvdh\DomPDF\PDF;
use Dompdf\Frame;
use InvalidArgumentException;

class TicketPdfService
{
    /**
     * Render a receipt at its measured height, bounded by the driver page limit.
     * Each pass owns a fresh renderer; long receipts retain normal pagination.
     */
    public function render(string $view, array $data = []): PDF
    {
        $profile = config('tickets');
        $width = (float) $profile['paper_width_mm'];
        $maximum = (float) $profile['max_height_mm'];
        $minimum = (float) $profile['min_height_mm'];
        $right = $width - $profile['content_width_mm'] - $profile['margin_left_mm'];

        if ($right < 0 || $minimum <= 0 || $maximum < $minimum || $profile['content_width_mm'] <= 0
            || $profile['margin_left_mm'] < 0 || $profile['margin_top_mm'] < 0 || $profile['margin_bottom_mm'] < 0
            || $minimum <= $profile['margin_top_mm'] + $profile['margin_bottom_mm']) {
            throw new InvalidArgumentException('El perfil de impresión del ticket tiene dimensiones inválidas.');
        }

        $data['ticketProfile'] = $profile;
        $data['ticketLayout'] = app(TicketLayoutService::class);
        $bottom = 0.0;
        $measurement = $this->makePdf($view, $data, $width, $maximum);
        $measurement->getDomPDF()->setCallbacks([[
            'event' => 'end_frame',
            'f' => static function (Frame $frame) use (&$bottom): void {
                // The terminal marker includes all preceding flow content, unlike
                // html/body frames whose box can fill the entire configured page.
                if ($frame->get_node() instanceof \DOMElement && $frame->get_node()->getAttribute('id') === 'ticket-end') {
                    $bottom = max($bottom, (float) $frame->get_position('y') + $frame->get_margin_height());
                }
            },
        ]]);
        $measurement->render();

        $height = $maximum;
        if ($measurement->getDomPDF()->getCanvas()->get_page_count() === 1 && $bottom > 0) {
            $height = min($maximum, max($minimum, $bottom * 25.4 / 72 + $profile['margin_bottom_mm'] + 1));
        }

        $pdf = $this->makePdf($view, $data, $width, $height);
        $pdf->render();

        return $pdf;
    }

    private function makePdf(string $view, array $data, float $width, float $height): PDF
    {
        /** @var PDF $pdf */
        $pdf = app('dompdf.wrapper');

        return $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isFontSubsettingEnabled' => true,
            'dpi' => 96,
        ], true)->setPaper([0, 0, $width * 72 / 25.4, $height * 72 / 25.4])
            ->loadView($view, $data);
    }
}
