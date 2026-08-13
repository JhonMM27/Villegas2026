<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\NumericStringOrder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NumericStringOrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('numeric_receipts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('numero_recibo')->nullable();
            $table->date('fecha');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('numeric_receipts');

        parent::tearDown();
    }

    public function test_orders_numeric_receipts_descending_independently_of_date(): void
    {
        $this->insertReceipt('9', '2026-08-31');
        $this->insertReceipt('100', '2026-01-01');
        $this->insertReceipt('10', '2026-12-31');

        $this->assertSame(['100', '10', '9'], $this->orderedReceipts('desc'));
    }

    public function test_leaves_invalid_receipts_last_and_uses_descending_id_as_tie_breaker(): void
    {
        $firstDuplicateId = $this->insertReceipt('10', '2026-08-01');
        $secondDuplicateId = $this->insertReceipt('10', '2026-07-01');
        $this->insertReceipt(null, '2026-09-01');
        $invalidId = $this->insertReceipt('LEGACY', '2026-10-01');

        $rows = $this->orderedRows('desc');

        $this->assertSame([$secondDuplicateId, $firstDuplicateId, $invalidId], [
            $rows[0]->id,
            $rows[1]->id,
            $rows[2]->id,
        ]);
        $this->assertNull($rows[3]->numero_recibo);
    }

    public function test_index_views_request_their_own_receipt_column_descending(): void
    {
        $this->assertViewOrder('venta-entregas/index.blade.php', 3);
        $this->assertViewOrder('gastos/index.blade.php', 8);
        $this->assertViewOrder('costos/index.blade.php', 8);
    }

    private function insertReceipt(?string $receipt, string $date): int
    {
        return (int) DB::table('numeric_receipts')->insertGetId([
            'numero_recibo' => $receipt,
            'fecha' => $date,
        ]);
    }

    /**
     * @return list<string|null>
     */
    private function orderedReceipts(string $direction): array
    {
        return array_map(
            static fn ($value) => $value === null ? null : (string) $value,
            array_column($this->orderedRows($direction), 'numero_recibo')
        );
    }

    /**
     * @return list<object>
     */
    private function orderedRows(string $direction): array
    {
        $query = DB::table('numeric_receipts')->select(['id', 'numero_recibo']);

        NumericStringOrder::apply(
            $query,
            'numeric_receipts.numero_recibo',
            'numeric_receipts.id',
            $direction
        );

        return $query->get()->all();
    }

    private function assertViewOrder(string $view, int $column): void
    {
        $contents = file_get_contents(resource_path("views/{$view}"));

        $this->assertIsString($contents);
        $this->assertMatchesRegularExpression(
            "/order:\\s*\\[\\[{$column},\\s*'desc'\\]\\]/",
            $contents
        );
    }
}
