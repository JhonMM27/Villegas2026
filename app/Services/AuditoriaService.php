<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditoriaEvento;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class AuditoriaService
{
    public function __construct(
        private readonly RectificacionAuditoriaService $comparador
    ) {}

    public function capturar(string $modulo, Model $registro): array
    {
        return $this->comparador->capturar($modulo, $registro);
    }

    public function registrarRectificacion(
        string $modulo,
        Model $registro,
        int $numero,
        array $anteriores,
        array $nuevos,
        ?string $motivo,
        ?User $user = null
    ): AuditoriaEvento {
        if ($numero < 1 || $numero > 3) {
            throw new InvalidArgumentException('El número de rectificación debe estar entre 1 y 3.');
        }

        return $this->registrar(
            AuditoriaEvento::TIPO_RECTIFICACION,
            $modulo,
            $registro,
            $anteriores,
            $nuevos,
            $motivo,
            $numero,
            $user
        );
    }

    public function registrarAnulacion(
        string $modulo,
        Model $registro,
        array $anteriores,
        array $nuevos,
        ?string $motivo,
        ?User $user = null
    ): AuditoriaEvento {
        return $this->registrar(
            AuditoriaEvento::TIPO_ANULACION,
            $modulo,
            $registro,
            $anteriores,
            $nuevos,
            $motivo,
            null,
            $user
        );
    }

    private function registrar(
        string $tipoEvento,
        string $modulo,
        Model $registro,
        array $anteriores,
        array $nuevos,
        ?string $motivo,
        ?int $numeroRectificacion,
        ?User $user
    ): AuditoriaEvento {
        $motivo = filled($motivo) ? trim((string) $motivo) : null;

        $user ??= auth()->user();

        return AuditoriaEvento::create([
            'tipo_evento' => $tipoEvento,
            'modulo' => $modulo,
            'registro_id' => (int) $registro->getKey(),
            'numero_rectificacion' => $numeroRectificacion,
            'registro_referencia' => $this->comparador->referencia($modulo, $registro),
            'user_id' => $user?->getKey(),
            'user_nombre' => (string) ($user?->name ?? 'Sistema'),
            'motivo' => $motivo,
            'datos_anteriores' => $anteriores,
            'datos_nuevos' => $nuevos,
            'cambios' => $this->comparador->comparar($anteriores, $nuevos),
        ]);
    }
}
