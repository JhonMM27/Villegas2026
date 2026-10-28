<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TelefonoTicket;
use App\Services\TelefonoTicketService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TelefonoTicketServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Schema::dropIfExists('telefonos_ticket');
        parent::tearDown();
    }

    public function test_uses_requested_fallback_before_table_is_installed(): void
    {
        $settings = app(TelefonoTicketService::class)->current();

        $this->assertFalse($settings['installed']);
        $this->assertSame('967984895', $settings['telefono_1']);
        $this->assertSame('978431737', $settings['telefono_2']);
    }

    public function test_reads_and_updates_the_single_installed_record(): void
    {
        Schema::create('telefonos_ticket', function (Blueprint $table): void {
            $table->id();
            $table->string('telefono_1', 25);
            $table->string('telefono_2', 25)->nullable();
            $table->timestamps();
        });
        TelefonoTicket::create(['id' => 1, 'telefono_1' => '967984895', 'telefono_2' => '978431737']);

        $service = app(TelefonoTicketService::class);
        $service->update(['telefono_1' => '+51 999-111-222', 'telefono_2' => null]);
        $settings = $service->current();

        $this->assertTrue($settings['installed']);
        $this->assertSame('+51 999-111-222', $settings['telefono_1']);
        $this->assertNull($settings['telefono_2']);
        $this->assertSame(1, TelefonoTicket::query()->count());
    }
}
