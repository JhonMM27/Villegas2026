<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\FontMetrics;

class TicketLayoutService
{
    private ?FontMetrics $metrics = null;

    /** Check the actual PDF font width, reserving space between columns. */
    public function fits(string $text, float $widthMm, float $size = 10): bool
    {
        return $this->widthMm($text, $size) <= $widthMm;
    }

    /** Measure text with the same embedded font used by the receipt. */
    public function widthMm(string $text, float $size = 10, bool $bold = false): float
    {
        $this->metrics ??= app('dompdf')->getFontMetrics();
        $font = $this->metrics->getFont('DejaVu Sans', $bold ? 'bold' : 'normal');

        return $this->metrics->getTextWidth($text, $font, $size) * 25.4 / 72;
    }
}
