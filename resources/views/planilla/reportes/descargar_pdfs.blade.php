@extends('plantilla.app')

@push('estilos')
<style>
    .pdf-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s;
    }
    .pdf-item:hover {
        background-color: #f8f9fa;
    }
    .pdf-item:last-child {
        border-bottom: none;
    }
    .pdf-name {
        font-weight: 500;
        color: #333;
    }
    .pdf-count {
        background: #e9ecef;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 12px;
        color: #666;
    }
    .btn-download-all {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-download-all:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
    }
    .download-counter {
        font-size: 14px;
        color: #666;
        margin-bottom: 15px;
    }
</style>
@endpush

@section('contenido')
<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-pdf"></i> Descargar Reportes por Empleado</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <p class="download-counter">Se generarán {{ count($pdfUrls) }} reportes en formato PDF</p>
                        <button type="button" class="btn-download-all" onclick="downloadAllPdfs()">
                            <i class="bi bi-download"></i> DESCARGAR TODOS
                        </button>
                    </div>

                    <div class="mt-3">
                        @foreach($pdfUrls as $index => $pdf)
                            <div class="pdf-item">
                                <div class="d-flex align-items-center">
                                    <span class="pdf-count me-3">{{ $index + 1 }}</span>
                                    <span class="pdf-name">{{ $pdf['name'] }}</span>
                                </div>
                                <a href="{{ $pdf['url'] }}" class="btn btn-outline-primary btn-sm download-btn" data-filename="{{ $pdf['name'] }}">
                                    <i class="bi bi-download"></i> Descargar
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">Los PDFs se descargarán directamente a tu carpeta de descargas</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let downloadedCount = 0;
const totalPdfs = {{ count($pdfUrls) }};
const pdfData = @json($pdfUrls);

async function downloadAllPdfs() {
    if (downloadedCount >= totalPdfs) {
        downloadedCount = 0;
    }

    for (let i = downloadedCount; i < totalPdfs; i++) {
        try {
            const response = await fetch(pdfData[i].url);
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = pdfData[i].name;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            downloadedCount++;

            if (i < totalPdfs - 1) {
                await new Promise(resolve => setTimeout(resolve, 300));
            }
        } catch (e) {
            console.error('Error downloading:', pdfData[i].name, e);
        }
    }

    Swal.fire({
        icon: 'success',
        title: 'Descarga completada',
        text: 'Se descargaron ' + totalPdfs + ' archivos PDF',
        timer: 2000,
        showConfirmButton: false
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('mnuPlanilla')?.classList.add('menu-open');
    document.getElementById('itemReportes')?.classList.add('active');
});
</script>
@endpush