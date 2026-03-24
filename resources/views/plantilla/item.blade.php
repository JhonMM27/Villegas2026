@extends('plantilla.app')
@push('estilos')
<link rel="stylesheet" href="{{asset('datatables/dataTables.bootstrap5.css')}}">
@endpush
@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Listado</h3>
                    <button type="button"class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUpdate">
                        <i class="bi bi-plus-circle"></i> Nuevo
                    </button>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaListado" class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="eliminar()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                    <td>NIU</td>
                                    <td>Unidades</td>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td>NIU</td>
                                    <td>Unidades</td>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td>NIU</td>
                                    <td>Unidades</td>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td>NIU</td>
                                    <td>Unidades</td>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td>NIU</td>
                                    <td>Unidades</td>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td>NIU</td>
                                    <td>Unidades</td>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td>NIU</td>
                                    <td>Unidades</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
@include('plantilla.action')
@endsection
@push('scripts')
<script src="{{asset('datatables/jquery-3.7.1.js')}}"></script>
<script src="{{asset('datatables/dataTables.js')}}"></script>
<script src="{{asset('datatables/dataTables.bootstrap5.js')}}"></script>
<script src="{{asset('js/sweetalert2.js')}}"></script>
<script>
    new DataTable('#tablaListado');
    function eliminar(){
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
            }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                title: "Deleted!",
                text: "Your file has been deleted.",
                icon: "success"
                });
            }
        });
    }

</script>
@endpush