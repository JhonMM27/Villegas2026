@extends('plantilla.app')
@push('estilos')
<style>
.chart-container {
    position: relative;
    width: 100%;
    height: 300px; /* ajusta si deseas */
}

@media (max-width: 768px) {
    .chart-container {
        height: 240px;
    }
}

.card-body {
    position: relative;
}
</style>
@endpush
@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Dashboard</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    @can('dashboard_estadisticas')
                    <div class="row">
                        <!--begin::Col-->
                        <div class="col-lg-3 col-6">
                            <!--begin::Small Box Widget 1-->
                            <div class="small-box text-bg-primary">
                                <div class="inner">
                                <h3 class="text-white">S/ {{ number_format($totalComprasHoy, 2) }}</h3>

                                <p>Compras Hoy</p>
                                </div>
                                <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.965A60.864 60.864 0 005.68 4.509l-.232-.867A1.875 1.875 0 003.636 2.25H2.25zM3.75 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM16.5 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z"></path>
                                </svg>
                                <a href="{{route('compras.index')}}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                                Compras <i class="bi bi-link-45deg"></i>
                                </a>
                            </div>
                            <!--end::Small Box Widget 1-->
                        </div>
                        <!--end::Col-->
                        <div class="col-lg-3 col-6">
                            <!--begin::Small Box Widget 2-->
                            <div class="small-box text-bg-success">
                                <div class="inner">
                                <h3 class="text-white">S/ {{ number_format($totalVentasHoy, 2) }}</h3>

                                <p>Ventas Hoy</p>
                                </div>
                                <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z"></path>
                                </svg>
                                <a href="{{route('ventas.index')}}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                                Ventas <i class="bi bi-link-45deg"></i>
                                </a>
                            </div>
                            <!--end::Small Box Widget 2-->
                        </div>
                        <!--end::Col-->
                        <div class="col-lg-3 col-6">
                            <!--begin::Small Box Widget 3-->
                            <div class="small-box text-bg-warning">
                                <div class="inner">
                                <h3 class="text-white">{{ $totalClientes }}</h3>

                                <p>Total Clientes</p>
                                </div>
                                <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M6.25 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM3.25 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM19.75 7.5a.75.75 0 00-1.5 0v2.25H16a.75.75 0 000 1.5h2.25v2.25a.75.75 0 001.5 0v-2.25H22a.75.75 0 000-1.5h-2.25V7.5z"></path>
                                </svg>
                                <a href="{{route('clientes.index')}}" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
                                Clientes <i class="bi bi-link-45deg"></i>
                                </a>
                            </div>
                            <!--end::Small Box Widget 3-->
                        </div>
                        <!--end::Col-->
                        <div class="col-lg-3 col-6">
                            <!--begin::Small Box Widget 4-->
                            <div class="small-box text-bg-danger">
                                <div class="inner">
                                <h3 class="text-white">{{ $totalProductos }}</h3>

                                <p>Total Productos</p>
                                </div>
                                <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path clip-rule="evenodd" fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 018.25-8.25.75.75 0 01.75.75v6.75H18a.75.75 0 01.75.75 8.25 8.25 0 01-16.5 0z"></path>
                                <path clip-rule="evenodd" fill-rule="evenodd" d="M12.75 3a.75.75 0 01.75-.75 8.25 8.25 0 018.25 8.25.75.75 0 01-.75.75h-7.5a.75.75 0 01-.75-.75V3z"></path>
                                </svg>
                                <a href="{{route('productos.index')}}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                                Productos <i class="bi bi-link-45deg"></i>
                                </a>
                            </div>
                            <!--end::Small Box Widget 4-->
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--Gráficos compras-->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card card-info card-outline mb-4">
                                <!--begin::Header-->
                                <div class="card-header"><div class="card-title" style="font-size:14px;">Productos más comprados en el mes</div></div>
                                <!--end::Header-->
                                <!--begin::Body-->
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="topProductosChart"></canvas>
                                    </div>
                                </div>
                                <!--end::Body-->
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card card-info card-outline mb-4">
                                <!--begin::Header-->
                                <div class="card-header"><div class="card-title" style="font-size:14px;">Compras en los últimos 15 días</div></div>
                                <!--end::Header-->
                                <!--begin::Body-->
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="comprasLineChart"></canvas>
                                    </div>
                                </div>
                                <!--end::Body-->
                            </div>
                        </div>
                    </div>
                    <!--Gráficos ventas-->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card card-info card-outline mb-4">
                                <!--begin::Header-->
                                <div class="card-header"><div class="card-title" style="font-size:14px;">Productos más vendidos en el mes</div></div>
                                <!--end::Header-->
                                <!--begin::Body-->
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="topProductosVentasChart"></canvas>
                                    </div>
                                </div>
                                <!--end::Body-->
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card card-info card-outline mb-4">
                                <!--begin::Header-->
                                <div class="card-header"><div class="card-title" style="font-size:14px;">Ventas en los últimos 15 días</div></div>
                                <!--end::Header-->
                                <!--begin::Body-->
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="ventasLineChart"></canvas>
                                    </div>
                                </div>
                                <!--end::Body-->
                            </div>
                        </div>
                    </div>
                    @endcan
                    @can('dashboard_productos')
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card card-info card-outline mb-4">
                                <!--begin::Header-->
                                <div class="card-header"><div class="card-title" style="font-size:14px;">Productos con bajo Stock</div></div>
                                <!--end::Header-->
                                <!--begin::Body-->
                                <div class="card-body">
                                    @foreach($alertasProductos as $producto)
                                        <div class="progress-group">
                                            {{ $producto->nombre }}
                                            <small class="text-muted">
                                                ({{ $producto->empaque }} {{ $producto->unidad_codigo }}
                                                | {{ $producto->linea->nombre ?? '-' }})
                                            </small>

                                            <span class="float-end">
                                                <b>{{ $producto->stock_almacen }}</b> / {{ $producto->stock_minimo }}
                                            </span>

                                            <div class="progress progress-sm">
                                                <div class="progress-bar {{ $producto->color_stock }}"
                                                    style="width: {{ $producto->porcentaje_stock }}%">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                </div>
                                <!--end::Body-->
                            </div>
                        </div>
                    </div>
                    @endcan
                </div>
                <!-- /.card-body -->
                <div class="card-footer clearfix">
                    
                </div>
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <!--end::Row-->
</div>
@endsection
@push('scripts')
<script src="{{asset('js/chart.js')}}"></script>
<script>
    fetch("{{ route('reportes.top-productos-mes') }}",{
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {

        const labels = data.map(x => x.producto_nombre);
        const valores = data.map(x => x.total_cantidad);

        const ctx = document.getElementById('topProductosChart').getContext('2d');

        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, "#4F46E5");     // Indigo 600
        gradient.addColorStop(1, "#818CF8");     // Indigo 300

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Unidades Compradas (Último Mes)',
                    data: valores,
                    backgroundColor: gradient,
                    borderSkipped: false,
                }]
            },
            options: {
                plugins: {
                    tooltip: {
                        backgroundColor: "#1E1B4B",
                        borderColor: "#6366F1",
                        borderWidth: 1,
                        padding: 10,
                        titleColor: "#fff",
                        bodyColor: "#fff",
                    },
                    legend: {
                        labels: { color: "#333", font: { size: 14 } }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: "#374151", font: { size: 13 } }
                    },
                    y: {
                        ticks: { color: "#374151", font: { size: 13 } },
                        grid: { color: "rgba(0,0,0,0.05)" }
                    }
                },
                animation: {
                    duration: 1200,
                    easing: "easeOutQuart"
                }
            }
        });
    });

    fetch("{{ route('reportes.compras-ultimos-15') }}", {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {

        const labels = data.map(x => x.fecha);
        const valores = data.map(x => x.total_dia);

        const ctx = document.getElementById('comprasLineChart').getContext('2d');

        const gradientLine = ctx.createLinearGradient(0, 0, 0, 200);
        gradientLine.addColorStop(0, "rgba(16, 185, 129, 0.6)");   // Emerald 500
        gradientLine.addColorStop(1, "rgba(16, 185, 129, 0.05)");

        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: "Total de Compras (Últimos 15 días)",
                    data: valores,
                    fill: true,
                    backgroundColor: gradientLine,
                    borderColor: "#10B981",   // Emerald 500
                    borderWidth: 3,
                    pointRadius: 5,
                    pointBackgroundColor: "#10B981",
                    pointBorderColor: "#fff",
                    pointHoverRadius: 8,
                    tension: 0.35
                }]
            },
            options: {
                plugins: {
                    tooltip: {
                        backgroundColor: "#065F46",
                        borderColor: "#10B981",
                        borderWidth: 1,
                        padding: 10,
                        titleColor: "#fff",
                        bodyColor: "#fff",
                        titleFont: { size: 14, weight: 'bold' }
                    },
                    legend: {
                        labels: { color: "#333", font: { size: 14 } }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: "#374151", font: { size: 13 } }
                    },
                    y: {
                        ticks: { color: "#374151", font: { size: 13 } },
                        grid: { color: "rgba(0,0,0,0.05)" }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: "easeOutQuart"
                }
            }
        });
    });

    fetch("{{ route('reportes.ventas-top-productos-mes') }}",{
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {

        const labels = data.map(x => x.producto_nombre);
        const valores = data.map(x => x.total_cantidad);

        const ctx = document.getElementById('topProductosVentasChart').getContext('2d');

        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, "#0284C7"); // Sky 600
        gradient.addColorStop(1, "#38BDF8"); // Sky 400

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Unidades Vendidas (Último Mes)',
                    data: valores,
                    backgroundColor: gradient,
                    borderSkipped: false,
                }]
            },
            options: {
                plugins: {
                    tooltip: {
                        backgroundColor: "#1E1B4B",
                        borderColor: "#6366F1",
                        borderWidth: 1,
                        padding: 10,
                        titleColor: "#fff",
                        bodyColor: "#fff",
                    },
                    legend: {
                        labels: { color: "#333", font: { size: 14 } }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: "#374151", font: { size: 13 } }
                    },
                    y: {
                        ticks: { color: "#374151", font: { size: 13 } },
                        grid: { color: "rgba(0,0,0,0.05)" }
                    }
                },
                animation: {
                    duration: 1200,
                    easing: "easeOutQuart"
                }
            }
        });
    });

    fetch("{{ route('reportes.ventas-ultimos-15') }}", {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {

        const labels = data.map(x => x.fecha);
        const valores = data.map(x => x.total_dia);

        const ctx = document.getElementById('ventasLineChart').getContext('2d');

        const gradientLine = ctx.createLinearGradient(0, 0, 0, 200);
        gradientLine.addColorStop(0, "rgba(59, 130, 246, 0.6)");  // Blue 500
        gradientLine.addColorStop(1, "rgba(59, 130, 246, 0.05)");

        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: "Total de Ventas (Últimos 15 días)",
                    data: valores,
                    fill: true,
                    backgroundColor: gradientLine,
                    borderColor: "#10B981",   // Emerald 500
                    borderWidth: 3,
                    pointRadius: 5,
                    pointBackgroundColor: "#10B981",
                    pointBorderColor: "#fff",
                    pointHoverRadius: 8,
                    tension: 0.35
                }]
            },
            options: {
                plugins: {
                    tooltip: {
                        backgroundColor: "#065F46",
                        borderColor: "#10B981",
                        borderWidth: 1,
                        padding: 10,
                        titleColor: "#fff",
                        bodyColor: "#fff",
                        titleFont: { size: 14, weight: 'bold' }
                    },
                    legend: {
                        labels: { color: "#333", font: { size: 14 } }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: "#374151", font: { size: 13 } }
                    },
                    y: {
                        ticks: { color: "#374151", font: { size: 13 } },
                        grid: { color: "rgba(0,0,0,0.05)" }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: "easeOutQuart"
                }
            }
        });
    });
    
    document.getElementById('itemDashboard').classList.add('active');
</script>
@endpush