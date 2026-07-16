<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // public function __construct(){
    //    $this->middleware('can:dashboard')->only(['index']);
    // }

    public function index(Request $request)
    {
        $hoy = today();

        $totalVentasHoy = Venta::whereDate('fecha_venta', $hoy)->sum('total');
        $totalComprasHoy = Compra::whereDate('fecha_compra', $hoy)->sum('total');
        $alertasProductos = Producto::with('linea')
            ->where('activo', true)
            ->where('stock_almacen', '>=', 0)
            ->where('activo', 1)
            // ->where('linea_id', 2)
            ->where('stock_minimo', '>', 0)
            ->whereColumn('stock_almacen', '<=', 'stock_minimo')
            ->orderBy('stock_almacen')
            ->get()
            ->map(function ($producto) {

                $producto->porcentaje_stock = $producto->stock_minimo > 0
                    ? min(100, round(($producto->stock_almacen / $producto->stock_minimo) * 100))
                    : 0;

                $producto->color_stock = match (true) {
                    $producto->stock_almacen <= $producto->stock_minimo * 0.5 => 'text-bg-danger',
                    $producto->stock_almacen <= $producto->stock_minimo => 'text-bg-warning',
                    default => 'text-bg-success',
                };

                return $producto;
            });

        $productosNegativos = Producto::with('linea')
            ->where('activo', true)
            ->where('stock_almacen', '<', 0)
            ->where('nombre', '!=', 'SERVICIO MEZCLADO')
            ->orderBy('stock_almacen')
            ->get()
            ->map(function ($producto) {
                $producto->porcentaje_stock = $producto->stock_minimo > 0
                    ? min(100, round(abs($producto->stock_almacen / $producto->stock_minimo) * 100))
                    : 0;
                $producto->color_stock = 'text-bg-danger';

                return $producto;
            });

        $totalClientes = Cliente::count();
        $totalProductos = Producto::count();

        return view('dashboard.index', compact(
            'totalVentasHoy',
            'totalComprasHoy',
            'totalClientes',
            'totalProductos',
            'alertasProductos',
            'productosNegativos'
        ));
    }
}
