<!doctype html>
<html lang="es">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>@yield('titulo','Sistema - Consorcios Villegas')</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Consorcios Villegas">

    <!-- Bloquear buscadores -->
    <meta name="robots" content="noindex, nofollow">
    <link rel="shortcut icon" href="{{asset('assets/favicon.ico')}}" type="image/x-icon">
    <!--end::Primary Meta Tags-->
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
    <!-- Custom login styles -->
    <link rel="stylesheet" href="{{ asset('css/login.css') }}" />
  </head>
  <!--end::Head-->
  <!--begin::Body-->
  <body class="bg-login-gradient">
    <div class="login-particles"></div>
    <main class="login-hero">
      <section class="login-hero__inner">
        <div class="login-hero__left">
          <div class="brand-container">
            <div class="brand-icon">
              <img src="{{ asset('assets/favicon.ico') }}" alt="Consorcio Villegas Logo" class="brand-icon-img" />
            </div>
            <div class="brand-text">
              <a href="/" style="text-decoration: none; color: inherit;">
                <h1 class="brand-title">CONSORCIOS VILLEGAS</h1>
                <p class="brand-subtitle">Sistema de Gestión Empresarial</p>
              </a>
            </div>
          </div>
          
          <div class="login-card">
            <div class="login-card-header">
              <div class="login-icon-wrapper">
                <i class="bi bi-shield-lock-fill"></i>
              </div>
              <h3 class="login-title">Bienvenido de nuevo</h3>
              <p class="login-sub">Ingresa tus credenciales para continuar</p>
            </div>
            
            @if(isset($error))
              <div class="alert alert-danger alert-modern">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                {{ $error }}
              </div>
            @endif
            
            <form action="{{ route('login.post') }}" method="post" class="login-form">
              @csrf
              
              <div class="mb-3">
                <label for="loginEmail" class="form-label">Correo Electrónico</label>
                <div class="input-group-simple">
                  <span class="input-icon-simple">
                    <i class="bi bi-envelope-fill"></i>
                  </span>
                  <input id="loginEmail" type="email" name="email" class="form-control form-control-simple" value="{{ old('email') }}" placeholder="usuario@ejemplo.com" required />
                </div>
                @error('email')<div class="error-message"><i class="bi bi-info-circle-fill"></i> {{ $message }}</div>@enderror
              </div>

              <div class="mb-3">
                <label for="loginPassword" class="form-label">Contraseña</label>
                <div class="input-group-simple">
                  <span class="input-icon-simple">
                    <i class="bi bi-lock-fill"></i>
                  </span>
                  <input id="loginPassword" type="password" name="password" class="form-control form-control-simple" placeholder="••••••••" required />
                </div>
                @error('password')<div class="error-message"><i class="bi bi-info-circle-fill"></i> {{ $message }}</div>@enderror
              </div>

              <!-- Opciones de formulario removidas: Recordarme y Recuperar contraseña -->

              <button type="submit" class="btn btn-login w-100">
                <span class="btn-text">Iniciar Sesión</span>
                <i class="bi bi-arrow-right-circle-fill btn-icon"></i>
              </button>
            </form>
          </div>
        </div>

        <div class="login-hero__right">
          <div class="media-wrap">
            <div class="video-overlay"></div>
            <video id="loginSideVideo" class="media-video" preload="auto" autoplay muted loop playsinline>
              <source src="{{ asset('assets/video.mp4') }}" type="video/mp4">
            </video>
            <div class="video-content">
              
              <div class="video-features">
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Control total de ventas</span>
                </div>
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Gestión de inventario</span>
                </div>
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Reportes en tiempo real</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
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
    <script>
      const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
      const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
      };
      document.addEventListener('DOMContentLoaded', function () {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
        if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
          OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
            scrollbars: {
              theme: Default.scrollbarTheme,
              autoHide: Default.scrollbarAutoHide,
              clickScroll: Default.scrollbarClickScroll,
            },
          });
        }
      });
    </script>
    <script>
      // Forzar reproducción del video lateral al cargar
      (function (){
        function initSideVideo(){
          const vid = document.getElementById('loginSideVideo');
          if (!vid) return;
          vid.muted = true;
          vid.play().catch(()=>{
            // Si falla el autoplay, intentar nuevamente en interacción del usuario
            document.body.addEventListener('click', function(){
              vid.play();
            }, {once: true});
          });
        }
        if (document.readyState === 'complete' || document.readyState === 'interactive') initSideVideo(); 
        else document.addEventListener('DOMContentLoaded', initSideVideo);
      })();
    </script>
    <!--end::OverlayScrollbars Configure-->
    <!--end::Script-->
  </body>
  <!--end::Body-->
</html>
