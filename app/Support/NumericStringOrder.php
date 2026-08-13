<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;

final class NumericStringOrder
{
    /**
     * Order a numeric string column numerically, leaving legacy invalid values last.
     */
    public static function apply(
        Builder|EloquentBuilder $query,
        string $column,
        string $idColumn,
        string $direction
    ): void {
        self::assertSafeIdentifier($column);
        self::assertSafeIdentifier($idColumn);

        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $isNumeric = "TRIM(COALESCE({$column}, '')) <> '' AND {$column} NOT GLOB '*[^0-9]*'";
            $numericValue = "CAST({$column} AS INTEGER)";
        } else {
            $isNumeric = "TRIM(COALESCE({$column}, '')) REGEXP '^[0-9]+$'";
            $numericValue = "CAST({$column} AS UNSIGNED)";
        }

        $query
            ->orderByRaw("CASE WHEN {$isNumeric} THEN 0 ELSE 1 END ASC")
            ->orderByRaw("{$numericValue} {$direction}")
            ->orderBy($idColumn, 'desc');
    }

    private static function assertSafeIdentifier(string $identifier): void
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $identifier) !== 1) {
            throw new InvalidArgumentException('Invalid SQL identifier.');
        }
    }
}
