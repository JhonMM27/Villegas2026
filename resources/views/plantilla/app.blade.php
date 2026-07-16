<!doctype html>
<html lang="en">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>@yield('titulo','Sistema - Consorcios Villegas')</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Consorcios Villegas">

    <!-- Bloquear buscadores -->
    <meta name="robots" content="noindex, nofollow">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!--end::Primary Meta Tags-->
    <!--begin::Theme Script (BLOCKING para evitar FOUC - Flash of Unstyled Content)-->
    <script>
      // Aplicar tema INMEDIATAMENTE antes de renderizar para evitar el "flash"
      (function() {
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const theme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);
      })();
    </script>
    <!--end::Theme Script-->
    <link rel="shortcut icon" href="{{asset('assets/favicon.ico')}}" type="image/x-icon">
    <!--begin::Fonts-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous"
    />
    <!--end::Fonts-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link
      rel="stylesheet"
      href="{{asset('css/overlayscrollbars.min.css')}}"
    />
    <!--end::Third Party Plugin(OverlayScrollbars)-->
    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
      rel="stylesheet"
      href="{{asset('bootstrap-icons-1.13.1/bootstrap-icons.min.css')}}"
    />
    <!--end::Third Party Plugin(Bootstrap Icons)-->
    <!--begin::Required Plugin(AdminLTE con estilos personalizados integrados)-->
    <link rel="stylesheet" href="{{asset('css/adminlte.css')}}" />
    <!--end::Required Plugin(AdminLTE con estilos personalizados integrados)-->
    <!--begin::DataTables Bootstrap5-->
    <link rel="stylesheet" href="{{asset('datatables/dataTables.bootstrap5.css')}}">
    <!--end::DataTables Bootstrap5-->

    <!--begin::Flatpickr (selector de fecha/hora)-->
    <link rel="stylesheet" href="{{asset('css/flatpickr.min.css')}}">
    <!--end::Flatpickr-->
    
    <!--
      NOTA: Todos los estilos personalizados del sidebar ahora están centralizados
      en public/css/adminlte.css al final del archivo (sección "ESTILOS PERSONALIZADOS DEL SIDEBAR")
      para facilitar su mantenimiento y evitar conflictos con !important.
    -->
    
    <!--begin::Optional Page-Specific Styles (usa @stack('estilos') para agregar estilos desde vistas)-->
    <!--end::Optional Page-Specific Styles-->
    <style>
      /* =========================================================
        TABLA BASE
      ========================================================= */

      #listadoTable, 
      .table-app {
          width: 100%;
          border-collapse: collapse !important;
          font-family: "Inter", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
          font-size: 0.90rem;
      }

      /* =========================================================
        LIGHT MODE
      ========================================================= */

      [data-bs-theme="light"] #listadoTable,
      [data-bs-theme="light"] .table-app {
          color: #212529;
      }

      /* =========================================================
        DARK MODE
      ========================================================= */

      [data-theme="dark"] .table-app,
      [data-theme="dark"] #listadoDatatable {
          color: #ffffff;
      }

      [data-theme="dark"] .table-app th,
      [data-theme="dark"] .table-app td {
          color: #ffffff;
      }

      [data-theme="dark"] .table-app thead th {
          color: #ffffff;
          border: 1px solid rgba(var(--bs-primary-rgb), 0.35) !important;
      }

      [data-theme="dark"] .table-app tfoot td {
          background-color: rgba(255, 255, 255, 0.02) !important;
          color: #ffffff !important;
          border: 1px solid rgba(var(--bs-primary-rgb), 0.35) !important;
      }

      /* Texto fuerte */
      [data-theme="dark"] .table-app tfoot strong {
          color: #ffffff;
      }
      /* =========================================================
        THEAD
      ========================================================= */

      #listadoTable thead th, 
      .table-app thead th {
          background-color: rgba(var(--bs-primary-rgb), 0.08);
          font-weight: 600;
          font-size: 0.8rem;
          text-transform: uppercase;
          letter-spacing: 0.04em;

          padding: 0.3rem 0.45rem;
          line-height: 1.1;
          vertical-align: middle;

          border-bottom: 2px solid var(--bs-primary);
      }

      /* =========================================================
        TBODY
      ========================================================= */

      #listadoTable tbody td,
      .table-app tbody td {
          background-color: transparent;
          padding: 0.28rem 0.45rem;
          line-height: 1.15;
          vertical-align: middle;

          border: 1px solid rgba(var(--bs-primary-rgb), 0.35);
      }

      /* =========================================================
        HOVER
      ========================================================= */

      #listadoTable tbody tr,
      .table-app tbody tr {
          transition: background-color 0.12s ease-in-out;
      }

      #listadoTable tbody tr:hover,
      .table-app tbody tr:hover {
          background-color: rgba(var(--bs-primary-rgb), 0.08);
      }

      /* =========================================================
        RESPONSIVE
      ========================================================= */

      @media (max-width: 768px) {
          #listadoTable,
           .table-app {
              font-size: 0.82rem;
          }
      }
      /* DataTables wrapper */
      .dataTables_wrapper {
          margin-top: 0 !important;
      }

      /* Fila superior: Mostrar X registros / Buscar */
      .dataTables_wrapper .row:first-child {
          margin-bottom: 0.4rem !important;
      }

      /* Tabla */
      #listadoTable {
          margin-top: 0 !important;
      }

      /* Card body (si usas Bootstrap card) */
      .card-body {
          padding-top: 0.75rem;
      }
      /* =========================================================
        COMPACTAR ESPACIO ENTRE TOPBAR Y CONTENIDO
        (AdminLTE + app-content-header)
      ========================================================= */

      .app-content-header {
          padding-top: 0.4rem !important;
          padding-bottom: 0.4rem !important;
          margin-bottom: 0 !important;
          min-height: unset !important;
      }

      /* Container vacío dentro del header */
      .app-content-header > .container-fluid {
          padding-top: 0 !important;
          padding-bottom: 0 !important;
          min-height: 0 !important;
      }

      /* Contenido principal */
      .app-content {
          padding-top: 0.75rem;
      }
      /* =========================================================
        CARD HEADER COMPACTO (TÍTULO + BOTÓN)
      ========================================================= */

      .card-header {
          padding-top: 0.6rem;
          padding-bottom: 0.6rem;
      }

      /* Título */
      .card-header .card-title {
          margin: 0;
          font-size: 1.05rem;
          line-height: 1.2;
      }
    </style>
    @stack('estilos')
  </head>
  <!--end::Head-->
  <!--begin::Body-->
  <body class="layout-fixed sidebar-mini sidebar-expand-lg bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
      <!--begin::Header-->
      @include('plantilla.header')
      <!--end::Header-->
      <!--begin::Sidebar-->
      @include('plantilla.menu')
      <!--end::Sidebar-->
      <!--begin::App Main-->
      <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
          <!--begin::Container-->
          <div class="container-fluid">
          </div>
          <!--end::Container-->
        </div>
        <!--end::App Content Header-->
        <!--begin::App Content-->
        <div class="app-content">
          <!--begin::Container-->
          @yield('contenido')
          <!--end::Container-->
        </div>
        <!--end::App Content-->
      </main>
      <!--end::App Main-->
      <!--begin::Footer-->
      @include('plantilla.footer')
      <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    <!--begin::Script-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <script
      src="{{asset('js/overlayscrollbars.browser.es6.min.js')}}"
    ></script>
    <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
    <script
      src="{{asset('js/popper.min.js')}}"
    ></script>
    <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
    <script
      src="{{asset('js/bootstrap.min.js')}}"
    ></script>
    <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
    <script src="{{asset('js/adminlte.js')}}"></script>
    <script src="{{asset('datatables/jquery-3.7.1.js')}}"></script>
    <script src="{{asset('datatables/dataTables.js')}}"></script>
    <script src="{{asset('datatables/dataTables.bootstrap5.js')}}"></script>
    <script src="{{asset('js/sweetalert2.js')}}"></script>
    <script src="{{asset('js/crud.js')}}"></script>
    <!--begin::Flatpickr (selector de fecha/hora)-->
    <script src="{{asset('js/flatpickr.min.js')}}"></script>
    <script src="{{asset('js/flatpickr-es.js')}}"></script>
    <script src="{{asset('js/flatpickr-init.js')}}"></script>
    <!--end::Flatpickr-->
    <!--begin::Custom Zynix JS-->
    <!--<script src="{{asset('js/zynix-custom.js')}}"></script>-->
    <!--end::Custom Zynix JS-->
    @stack('scripts')
    <!--end::Required Plugin(AdminLTE)--><!--begin::OverlayScrollbars Configure-->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      const el = document.querySelector('.sidebar-wrapper');

      if (el && window.OverlayScrollbarsGlobal?.OverlayScrollbars) {
        OverlayScrollbarsGlobal.OverlayScrollbars(el, {
          scrollbars: {
            theme: 'os-theme-light',
            autoHide: 'leave',
            clickScroll: true,
          },
        });
      }
    });
  </script>
    <!--end::OverlayScrollbars Configure-->
    <!--end::Script-->
  </body>
  <!--end::Body-->
</html>
