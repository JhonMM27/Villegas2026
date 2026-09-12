<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\TicketPdfService;
use Tests\Support\TicketFixtures;
use Tests\TestCase;

class TicketPdfServiceTest extends TestCase
{
    public function test_compact_columns_use_font_metrics_and_keep_quantity_with_unit(): void
    {
        $layout = app(\App\Services\TicketLayoutService::class);
        $this->assertTrue($layout->fits('15.00 SACO', 63 * 0.4 - 0.8));
        $this->assertGreaterThan($layout->widthMm('iiii'), $layout->widthMm('WWWW'));
        $this->assertFalse($layout->fits('123,456,789.98', 63 * 0.27 - 0.8));

        $html = view('tickets.item', ['name' => 'MEDIA', 'quantity' => '15.00', 'unit' => 'SACO', 'price' => '77.00', 'amount' => '1,155.00'])->render();
        $this->assertStringContainsString('15.00 SACO</span>', $html);
        $this->assertStringNotContainsString('P.Unit:', $html);
        $this->assertStringNotContainsString('Importe:', $html);
        $this->assertStringNotContainsString('S/', $html);
    }

    public function test_all_ticket_families_render_with_the_printer_profile(): void
    {
        foreach (TicketFixtures::views() as $view) {
            $pdf = app(TicketPdfService::class)->render($view, TicketFixtures::data());
            $this->assertStringStartsWith('%PDF-', $pdf->output(), $view);
            $canvas = $pdf->getDomPDF()->getCanvas();
            $this->assertEqualsWithDelta(76 * 72 / 25.4, $canvas->get_width(), 0.01, $view);
            $this->assertLessThanOrEqual(297 * 72 / 25.4, $canvas->get_height(), $view);
        }
    }

    public function test_short_receipts_are_trimmed_and_long_receipts_paginate(): void
    {
        $service = app(TicketPdfService::class);
        $short = $service->render('ventas.ticket', TicketFixtures::data());
        $long = $service->render('ventas.ticket', TicketFixtures::data(50));
        $this->assertSame(1, $short->getDomPDF()->getCanvas()->get_page_count());
        $this->assertLessThan(297 * 72 / 25.4, $short->getDomPDF()->getCanvas()->get_height());
        $this->assertGreaterThan(1, $long->getDomPDF()->getCanvas()->get_page_count());
        $this->assertNotSame($short->getDomPDF(), $long->getDomPDF());
    }

    public function test_sales_preserve_units_amounts_and_balances(): void
    {
        $data = TicketFixtures::data();
        $data['venta']->detalles->push((object) ['producto_nombre' => 'SAL', 'unidad_codigo' => 'KGM', 'salida_kg' => 40, 'salida_saco' => 1, 'precio_unitario' => 0.4, 'total' => 16]);
        $html = view('ventas.ticket', $data)->render();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));
        $this->assertStringContainsString('8.00 SACO', $text);
        $this->assertStringContainsString('40.00 KG', $text);
        $this->assertStringContainsString('S/ 304.00', $text);
        $this->assertStringContainsString('S/ 16.00', $text);
        $this->assertStringContainsString('SALDO ANTERIOR', $text);
        $this->assertStringContainsString('COBRANZA', $text);
        $this->assertStringContainsString('bold-summary', $html);
    }

    public function test_calibration_requires_authentication(): void
    {
        $this->get(route('tickets.calibracion'))->assertRedirect(route('login'));
    }

    public function test_quotation_kilograms_use_packaging_and_prepared_quantities_are_preserved(): void
    {
        $data = TicketFixtures::data();
        $data['cotizacion']->detalles->first()->unidad_codigo = 'KGM';
        $quotation = preg_replace('/\s+/', ' ', strip_tags(view('cotizaciones.ticket', $data)->render()));
        $this->assertStringContainsString('320.00 KG', $quotation);
        foreach (['preparadas.ticket', 'nucleo-preparadas.ticket'] as $view) {
            $text = preg_replace('/\s+/', ' ', strip_tags(view($view, $data)->render()));
            $this->assertStringContainsString('320.00 KG', $text);
            $this->assertStringContainsString('12,160.00', $text);
        }
    }

    public function test_invalid_profile_is_rejected(): void
    {
        config(['tickets.content_width_mm' => 80]);
        $this->expectException(\InvalidArgumentException::class);
        app(TicketPdfService::class)->render('tickets.calibration');
    }

    public function test_calibration_can_be_downloaded_by_authenticated_users(): void
    {
        $this->actingAs(new \App\Models\User)->get(route('tickets.calibracion'))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}
