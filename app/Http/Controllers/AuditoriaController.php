<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditoriaEvento;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class AuditoriaController extends Controller
{
    private const MODULOS = [
        'compras' => 'Compras',
        'ventas' => 'Ventas',
        'prestamos' => 'Préstamos',
        'preparadas' => 'Preparadas',
        'nucleo_preparadas' => 'Preparadas de núcleo',
        'cuadre_stocks' => 'Cuadres de stock',
        'venta_entregas' => 'Entregas de venta',
        'empleado_sueldos' => 'Sueldos de empleados',
    ];

    public function __construct()
    {
        $this->middleware('can:auditoria_list');
    }

    public function index(): View
    {
        $hoy = now()->toDateString();

        $metricas = [
            'total' => AuditoriaEvento::query()->count(),
            'hoy' => AuditoriaEvento::query()->whereDate('created_at', $hoy)->count(),
            'anulaciones_hoy' => AuditoriaEvento::query()
                ->where('tipo_evento', AuditoriaEvento::TIPO_ANULACION)
                ->whereDate('created_at', $hoy)
                ->count(),
            'rectificaciones_hoy' => AuditoriaEvento::query()
                ->where('tipo_evento', AuditoriaEvento::TIPO_RECTIFICACION)
                ->whereDate('created_at', $hoy)
                ->count(),
        ];

        $usuarios = User::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('auditoria.index', [
            'metricas' => $metricas,
            'modulos' => self::MODULOS,
            'usuarios' => $usuarios,
        ]);
    }

    public function data(Request $request)
    {
        $request->validate([
            'buscar' => ['nullable', 'string', 'max:150'],
            'user_nombre' => ['nullable', 'string', 'max:100'],
            'tipo_evento' => ['nullable', 'in:anulacion,rectificacion'],
            'modulo' => ['nullable', 'in:'.implode(',', array_keys(self::MODULOS))],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $query = AuditoriaEvento::query()->select('auditoria_eventos.*');

        $buscar = trim((string) $request->input('buscar'));
        if ($buscar !== '') {
            $query->where(function ($subquery) use ($buscar): void {
                $subquery
                    ->where('user_nombre', 'like', "%{$buscar}%")
                    ->orWhere('registro_referencia', 'like', "%{$buscar}%")
                    ->orWhere('modulo', 'like', "%{$buscar}%")
                    ->orWhere('motivo', 'like', "%{$buscar}%");
            });
        }

        $query
            ->when($request->filled('user_nombre'), fn ($q) => $q->where('user_nombre', $request->string('user_nombre')->toString()))
            ->when($request->filled('tipo_evento'), fn ($q) => $q->where('tipo_evento', $request->string('tipo_evento')->toString()))
            ->when($request->filled('modulo'), fn ($q) => $q->where('modulo', $request->string('modulo')->toString()))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('hasta')))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return DataTables::of($query)
            ->addColumn('fecha', fn (AuditoriaEvento $evento): string => $evento->created_at?->format('d/m/Y') ?? '—')
            ->addColumn('hora', fn (AuditoriaEvento $evento): string => $evento->created_at?->format('H:i:s') ?? '—')
            ->addColumn('accion', function (AuditoriaEvento $evento): string {
                $clase = $evento->tipo_evento === AuditoriaEvento::TIPO_ANULACION ? 'audit-badge-cancel' : 'audit-badge-edit';
                $texto = $evento->tipo_evento === AuditoriaEvento::TIPO_ANULACION ? 'Anuló' : 'Rectificó';

                return '<span class="audit-action-badge '.$clase.'">'.$texto.'</span>';
            })
            ->addColumn('modulo_nombre', fn (AuditoriaEvento $evento): string => self::MODULOS[$evento->modulo] ?? str_replace('_', ' ', ucfirst($evento->modulo)))
            ->addColumn('cambio', fn (AuditoriaEvento $evento): string => '<button type="button" class="btn btn-sm btn-outline-primary btn-ver-auditoria" data-id="'.$evento->id.'"><i class="bi bi-eye me-1"></i>Ver cambios</button>')
            ->rawColumns(['accion', 'cambio'])
            ->toJson();
    }

    public function show(AuditoriaEvento $evento): JsonResponse
    {
        return response()->json([
            'id' => $evento->id,
            'tipo_evento' => $evento->tipo_evento,
            'accion' => $evento->tipo_evento === AuditoriaEvento::TIPO_ANULACION ? 'Anulación' : 'Rectificación',
            'modulo' => $evento->modulo,
            'modulo_nombre' => self::MODULOS[$evento->modulo] ?? str_replace('_', ' ', ucfirst($evento->modulo)),
            'numero_rectificacion' => $evento->numero_rectificacion,
            'registro_referencia' => $evento->registro_referencia,
            'user_nombre' => $evento->user_nombre,
            'motivo' => $evento->motivo,
            'fecha' => $evento->created_at?->format('d/m/Y H:i:s'),
            'cambios' => $evento->cambios,
        ]);
    }

    public function pdf(AuditoriaEvento $evento): Response
    {
        $accion = $evento->tipo_evento === AuditoriaEvento::TIPO_ANULACION
            ? 'Anulación'
            : 'Rectificación';
        $moduloNombre = self::MODULOS[$evento->modulo]
            ?? str_replace('_', ' ', ucfirst($evento->modulo));
        $nombreArchivo = implode('_', array_filter([
            'auditoria',
            Str::slug($accion, '_'),
            Str::slug($evento->registro_referencia, '_'),
        ])).'.pdf';

        return Pdf::loadView('auditoria.evento_pdf', [
            'evento' => $evento,
            'accion' => $accion,
            'moduloNombre' => $moduloNombre,
            'empresa' => (object) [
                'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
                'direccion' => 'Carretera Pomalca KM 3',
                'ruc' => '20538937321',
            ],
        ])->setPaper('a4', 'portrait')->addInfo([
            'Title' => 'Auditoría - '.$accion.' - '.$evento->registro_referencia,
            'Subject' => 'Detalle de evento protegido de auditoría',
            'Author' => 'CONSORCIOS VILLEGAS E.I.R.L.',
        ])->stream($nombreArchivo);
    }
}
