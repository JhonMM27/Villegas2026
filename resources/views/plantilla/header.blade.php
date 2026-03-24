<nav class="app-header navbar navbar-expand bg-body">
    <style>
    /* Header: limpio, ordenado y accesible */
    .app-header {
        border-bottom: 1px solid var(--border-light, #eef0f2);
        background: var(--header-bg, #ffffff);
    }
    .app-header .nav-link {
        color: var(--text-secondary, #58606a);
        padding: .4rem .5rem;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        font-size: var(--font-size-base);
        font-weight: var(--font-weight-medium);
        transition: none;
    }
    .app-header .nav-link:focus,
    .app-header .nav-link:hover {
        color: var(--text-primary, #212529);
        background: var(--hover-bg, rgba(0,0,0,0.03));
        border-radius: .4rem;
        text-decoration: none;
        outline: none;
    }
    .header-btn { 
        padding: .4rem; 
        border-radius: .4rem; 
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .header-icon { 
        width: var(--icon-size-base); 
        height: var(--icon-size-base);
        flex-shrink: 0;
        color: currentColor;
        stroke: currentColor;
    }
    
    .header-icon svg {
        color: inherit;
        stroke: currentColor;
    }

    /* Badges usando variables */
    .badge-counter {
        font-size: var(--font-size-xs);
        font-weight: var(--font-weight-semibold);
        vertical-align: top;
        margin-left: .3rem;
        padding: .2rem .4rem;
        line-height: 1;
    }
    .badge-dot {
        display: inline-block;
        width: 8px; height: 8px; border-radius: 50%;
        background: #dc3545; margin-left: .4rem;
        box-shadow: 0 0 0 2px rgba(220,53,69,0.1);
    }

    /* Avatar and user */
    .avatar-sm { 
        width: 36px; height: 36px; 
        object-fit: cover; 
        border-radius: 50%; 
        border: 2px solid var(--border-light, rgba(0,0,0,0.06)); 
    }
    .user-name { 
        margin-left: .5rem; 
        font-weight: var(--font-weight-semibold); 
        font-size: var(--font-size-base);
        color: var(--text-primary, #212529); 
    }
    @media (max-width: 575px) { .user-name { display: none; } }

    /* Visual separator before user */
    .header-separator { width: 1px; height: 28px; background: var(--border-light, #eef0f2); margin: 0 .5rem; }

    /* Dropdown menu improvements */
    .user-header { background: transparent; }
    .dropdown-menu.user-menu { min-width: 220px; padding: 0; overflow: hidden; }
    .user-footer { display: flex; gap: .5rem; }
    .user-footer .btn { flex: 1; }
    
    /* Dropdown content (messages / notifications) */
    .dropdown-messages, .dropdown-notifications { 
        min-width: 320px; max-width: 380px; 
        font-size: var(--font-size-sm);
        background-color: var(--dropdown-bg, #ffffff);
        border-color: var(--border-color, #dee2e6);
    }
    .dropdown-messages .list-group-item,
    .dropdown-notifications .list-group-item {
        padding: .65rem .85rem;
        cursor: pointer;
        font-size: var(--font-size-sm);
        transition: none;
        background-color: transparent;
        color: var(--text-primary, #212529);
        border-color: var(--border-light, #eef0f2);
    }
    .dropdown-messages .list-group-item:hover,
    .dropdown-notifications .list-group-item:hover {
        background: var(--hover-bg, rgba(0,0,0,0.025));
    }
    .dropdown-messages img { width:36px; height:36px; border-radius: 50%; }
    .dropdown-notifications svg { 
        width: var(--icon-size-md); 
        height: var(--icon-size-md); 
        flex-shrink: 0;
    }
    .dropdown-footer { 
        font-size: var(--font-size-sm);
        background-color: var(--bg-tertiary, #f8f9fa);
        border-top-color: var(--border-light, #eef0f2);
    }
    .dropdown-footer a { 
        text-decoration: none;
        font-weight: var(--font-weight-medium);
        color: var(--text-secondary, #6c757d);
    }
    
    /* User dropdown (estilo tarjeta limpia sin backdrop) */
    .user-dropdown-menu {
        min-width: 280px; max-width: 320px;
        border-radius: .5rem;
        box-shadow: 0 10px 30px var(--shadow-md, rgba(18, 38, 63, 0.15));
        border: 1px solid var(--border-color, rgba(0,0,0,0.08));
        padding: 0; margin-top: .5rem;
        background-color: var(--card-bg, #ffffff);
    }
    .user-dropdown-header {
        padding: 1.25rem 1.5rem 1rem;
        text-align: center;
        border-bottom: 1px solid var(--border-light, rgba(0,0,0,0.06));
        background-color: var(--bg-tertiary, #f8f9fa);
    }
    .user-dropdown-header .avatar-md {
        width: 64px; height: 64px; border-radius: 50%;
        object-fit: cover; margin-bottom: .65rem;
        border: 3px solid var(--bg-tertiary, #f8f9fa);
    }
    .user-dropdown-header strong { 
        display: block; 
        font-size: var(--font-size-h5); 
        font-weight: var(--font-weight-semibold);
        margin-bottom: .15rem;
        color: var(--text-primary, #212529);
    }
    .user-dropdown-header small { 
        display: block; 
        color: var(--text-muted, #6c757d); 
        font-size: var(--font-size-sm); 
    }
    .user-dropdown-menu .dropdown-item {
        padding: .75rem 1.25rem;
        display: flex; align-items: center; gap: .7rem;
        font-size: var(--font-size-base);
        transition: none;
        color: var(--text-secondary, #6c757d);
    }
    .user-dropdown-menu .dropdown-item svg { 
        width: var(--icon-size-base); 
        height: var(--icon-size-base);
        flex-shrink: 0;
    }
    .user-dropdown-menu .dropdown-item:hover { 
        background: var(--hover-bg, rgba(0,0,0,0.03));
        color: var(--text-primary, #212529);
    }
    .user-dropdown-footer { 
        padding: .85rem 1.25rem; 
        border-top: 1px solid var(--border-light, rgba(0,0,0,0.06));
        background-color: var(--bg-tertiary, #f8f9fa);
    }
    .user-dropdown-footer .btn-logout {
        background: var(--btn-primary, #0885c9); 
        border-color: var(--btn-primary, #0885c9); 
        color: var(--btn-primary-text, #fff);
        border-radius: .4rem; padding: .6rem 1rem; width: 100%;
        font-weight: var(--font-weight-medium);
        font-size: var(--font-size-base);
    }
    .user-dropdown-footer .btn-logout:hover { background: #0670ab; border-color: #0670ab; }
    
    /* ========================================
       TEMA DARK - ESTILOS ESPECÍFICOS PARA HEADER
       ======================================== */
    [data-theme="dark"] .app-header {
        border-bottom-color: var(--border-color, #3d4555);
        background: var(--header-bg, #2a3140);
    }
    
    [data-theme="dark"] .app-header .nav-link {
        color: var(--text-secondary, #c5cad1);
    }
    
    [data-theme="dark"] .app-header .nav-link:hover,
    [data-theme="dark"] .app-header .nav-link:focus {
        color: var(--text-primary, #f1f3f5);
        background: var(--hover-bg, rgba(255,255,255,0.08));
    }
    
    [data-theme="dark"] .header-icon {
        color: var(--text-secondary, #c5cad1);
        stroke: var(--text-secondary, #c5cad1);
    }
    
    [data-theme="dark"] .header-icon svg {
        color: var(--text-secondary, #c5cad1);
        stroke: currentColor;
    }
    
    [data-theme="dark"] .header-btn:hover .header-icon,
    [data-theme="dark"] .nav-link:hover .header-icon {
        color: var(--text-primary, #f1f3f5);
        stroke: var(--text-primary, #f1f3f5);
    }
    
    /* Badges en dark mode */
    [data-theme="dark"] .badge-counter {
        background-color: #0885c9;
        color: #ffffff;
    }
    
    [data-theme="dark"] .badge-dot {
        background: #dc3545;
        box-shadow: 0 0 0 2px rgba(220,53,69,0.2);
    }
    
    /* Avatar y user en dark */
    [data-theme="dark"] .avatar-sm {
        border-color: var(--border-color, rgba(255,255,255,0.1));
    }
    
    [data-theme="dark"] .user-name {
        color: var(--text-primary, #f1f3f5);
    }
    
    [data-theme="dark"] .header-separator {
        background: var(--border-color, #3d4555);
    }
    
    /* Dropdowns en dark mode */
    [data-theme="dark"] .dropdown-messages,
    [data-theme="dark"] .dropdown-notifications {
        background-color: var(--card-bg, #2a3140);
        border-color: var(--border-color, #3d4555);
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    
    [data-theme="dark"] .dropdown-messages .p-2,
    [data-theme="dark"] .dropdown-notifications .p-2 {
        background-color: var(--bg-secondary, #232938);
        border-bottom-color: var(--border-color, #3d4555);
        color: var(--text-primary, #f1f3f5);
    }
    
    [data-theme="dark"] .dropdown-messages strong,
    [data-theme="dark"] .dropdown-notifications strong {
        color: var(--text-primary, #f1f3f5);
    }
    
    [data-theme="dark"] .dropdown-messages .list-group-item,
    [data-theme="dark"] .dropdown-notifications .list-group-item {
        background-color: transparent;
        color: var(--text-secondary, #c5cad1);
        border-color: var(--border-color, #3d4555);
    }
    
    [data-theme="dark"] .dropdown-messages .list-group-item:hover,
    [data-theme="dark"] .dropdown-notifications .list-group-item:hover {
        background: var(--hover-bg, rgba(255,255,255,0.08));
        color: var(--text-primary, #f1f3f5);
    }
    
    [data-theme="dark"] .dropdown-messages .list-group-item strong,
    [data-theme="dark"] .dropdown-notifications .list-group-item strong {
        color: var(--text-primary, #f1f3f5);
    }
    
    [data-theme="dark"] .dropdown-footer {
        background-color: var(--bg-secondary, #232938);
        border-top-color: var(--border-color, #3d4555);
    }
    
    [data-theme="dark"] .dropdown-footer a {
        color: var(--text-secondary, #c5cad1);
    }
    
    [data-theme="dark"] .dropdown-footer a:hover {
        color: var(--text-primary, #f1f3f5);
    }
    
    /* User dropdown en dark */
    [data-theme="dark"] .user-dropdown-menu {
        background-color: var(--card-bg, #2a3140);
        border-color: var(--border-color, #3d4555);
        box-shadow: 0 10px 30px rgba(0,0,0,0.6);
    }
    
    [data-theme="dark"] .user-dropdown-header {
        background-color: var(--bg-secondary, #232938);
        border-bottom-color: var(--border-color, #3d4555);
    }
    
    [data-theme="dark"] .user-dropdown-header .avatar-md {
        border-color: var(--bg-secondary, #232938);
    }
    
    [data-theme="dark"] .user-dropdown-header strong {
        color: var(--text-primary, #f1f3f5);
    }
    
    [data-theme="dark"] .user-dropdown-header small {
        color: var(--text-muted, #8b92a0);
    }
    
    [data-theme="dark"] .user-dropdown-menu .dropdown-item {
        color: var(--text-secondary, #c5cad1);
    }
    
    [data-theme="dark"] .user-dropdown-menu .dropdown-item:hover {
        background: var(--hover-bg, rgba(255,255,255,0.08));
        color: var(--text-primary, #f1f3f5);
    }
    
    [data-theme="dark"] .user-dropdown-footer {
        background-color: var(--bg-secondary, #232938);
        border-top-color: var(--border-color, #3d4555);
    }
    
    [data-theme="dark"] .btn-logout {
        background-color: var(--btn-primary, #0885c9);
        border-color: var(--btn-primary, #0885c9);
        color: var(--btn-primary-text, #ffffff);
    }
    
    [data-theme="dark"] .btn-logout:hover {
        background-color: #0670ab;
        border-color: #0670ab;
    }
    
    /* Clases de Bootstrap que necesitan override en dark */
    [data-theme="dark"] .text-muted {
        color: var(--text-muted, #8b92a0) !important;
    }
    
    [data-theme="dark"] .border-bottom,
    [data-theme="dark"] .border-top {
        border-color: var(--border-color, #3d4555) !important;
    }
    
    /* Iconos de colores en dropdowns (mantener colores específicos) */
    [data-theme="dark"] .text-warning {
        color: #ffc107 !important;
    }
    
    [data-theme="dark"] .text-success {
        color: #198754 !important;
    }
    
    [data-theme="dark"] .text-danger {
        color: #dc3545 !important;
    }
    
    [data-theme="dark"] .text-primary {
        color: #4DA8FF !important;
    }
    
    [data-theme="dark"] .text-secondary {
        color: #8b92a0 !important;
    }
    
    /* Asegurar que bg-body use el background correcto */
    [data-theme="dark"] .bg-body {
        background-color: var(--bg-primary, #2a3140) !important;
    }
    </style>
    <!--begin::Container-->
    <div class="container-fluid">
        <!--begin::Start Navbar Links-->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
                    <x-icon name="menu" class="header-icon" />
                </a>
            </li>
            @can('ventas_list')
            <li class="nav-item d-none d-md-block">
              <a href="{{route('ventas.index')}}" class="nav-link">Ventas</a>
            </li>
            @endcan
            @can('venta_provisionales_list')
            <li class="nav-item d-none d-md-block">
              <a href="{{route('venta-provisionales.index')}}" class="nav-link">Provisional Ventas</a>
            </li>
            @endcan
            @can('compras_list')
            <li class="nav-item d-none d-md-block">
              <a href="{{route('compras.index')}}" class="nav-link">Compras</a>
            </li>
            @endcan
            @can('caja_report')
            <li class="nav-item d-none d-md-block">
              <a href="{{route('reportes.caja')}}" class="nav-link">Caja</a>
            </li>
            @endcan
        </ul>
        <!--end::Start Navbar Links-->
        <!--begin::End Navbar Links-->
        <ul class="navbar-nav ms-auto align-items-center">
            <!-- Dark mode toggle -->
            <li class="nav-item d-sm-block">
                <a class="nav-link header-btn" href="#" id="themeToggle" title="Cambiar tema" aria-label="Cambiar tema">
                    <span id="themeIcon">
                        <x-icon name="moon" class="header-icon" />
                    </span>
                </a>
            </li>

            <!-- Notifications (dropdown) -->
            <!--
            <li class="nav-item dropdown">
                <a class="nav-link header-btn position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notificaciones">
                    <x-icon name="bell" class="header-icon" />
                    <span class="badge-dot" id="notifDot" aria-hidden="true"></span>
                </a>                
                <div class="dropdown-menu dropdown-menu-end dropdown-notifications p-0 shadow" aria-labelledby="notifDropdown">
                    <div class="p-2 border-bottom">
                        <strong>Notificaciones</strong>
                    </div>
                    <div class="list-group list-group-flush" style="max-height:260px; overflow:auto;">
                        <a href="#" class="list-group-item list-group-item-action d-flex gap-2 align-items-start">
                            <x-icon name="alert-circle" class="text-warning" />
                            <div class="grow ms-2">
                                <div class="d-flex w-100 justify-content-between">
                                    <div class="text-truncate">Nueva actualización disponible</div>
                                    <small class="text-muted text-xs">10m</small>
                                </div>
                                <div class="text-muted text-sm">Se ha publicado la versión 1.2.3</div>
                            </div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex gap-2 align-items-start">
                            <x-icon name="check-circle" class="text-success" />
                            <div class="grow ms-2">
                                <div class="d-flex w-100 justify-content-between">
                                    <div class="text-truncate">Backup completado</div>
                                    <small class="text-muted text-xs">2h</small>
                                </div>
                                <div class="text-muted text-sm">El respaldo diario finalizó correctamente.</div>
                            </div>
                        </a>
                    </div>
                    <div class="dropdown-footer text-center p-2 border-top">
                        <a href="/notificaciones">Ver todas las notificaciones</a>
                    </div>
                </div>
               
            </li>
             -->
            <!-- Fullscreen Toggle -->
            <li class="nav-item">
                <a class="nav-link header-btn" href="#" data-lte-toggle="fullscreen" title="Pantalla completa" aria-label="Pantalla completa">
                    <span data-lte-icon="maximize">
                        <x-icon name="maximize" class="header-icon" />
                    </span>
                    <span data-lte-icon="minimize" style="display: none">
                        <x-icon name="minimize" class="header-icon" />
                    </span>
                </a>
            </li>
            <!-- User Dropdown -->
            @if(Auth::check())
            <li class="nav-item dropdown">
                <a href="#" class="nav-link d-flex align-items-center header-btn" data-bs-toggle="dropdown" aria-label="Menú de usuario">
                    <img src="{{ Auth::user()->avatar ?? asset('assets/img/avatar.png') }}" alt="avatar" class="avatar-sm">
                    <span class="user-name d-none d-md-inline">{{ Auth::user()->name }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end user-dropdown-menu" aria-label="Opciones de usuario">
                    <div class="user-dropdown-header">
                        <img src="{{ Auth::user()->avatar ?? asset('assets/img/avatar.png') }}" alt="avatar" class="avatar-md">
                        <strong>{{ Auth::user()->name }}</strong>
                        <small>{{ Auth::user()->email }}</small>
                    </div>
                    <div>
                        <a class="dropdown-item" href="{{ route('perfil.edit') }}">
                            <x-icon name="user" class="text-primary" />
                            <span>Perfil</span>
                        </a>
                        <!--
                        <a class="dropdown-item" href="#">
                            <x-icon name="headset" class="text-warning" />
                            <span>Soporte</span>
                        </a>
                        -->
                    </div>
                    <div class="user-dropdown-footer">
                        <form id="logout-form" action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-logout">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </li>
            @endif
            <!--end::User Dropdown-->
        </ul>
        <!--end::End Navbar Links-->
    </div>
    <!--end::Container-->
    <script>
    // Sistema de temas dark/light - OPTIMIZADO PARA CAMBIO INSTANTÁNEO
    (function() {
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const html = document.documentElement;
        
        // Cargar tema guardado o detectar preferencia del sistema
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const currentTheme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
        
        // Aplicar tema inicial de forma INMEDIATA
        function applyTheme(theme, isInitial = false) {
            // Aplicar atributo data-theme INMEDIATAMENTE al HTML
            html.setAttribute('data-theme', theme);
            
            // Actualizar el ícono sin animaciones
            if (themeIcon) {
                const iconName = theme === 'dark' ? 'sun' : 'moon';
                themeIcon.innerHTML = `<svg class="header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    ${theme === 'dark' 
                        ? '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>' 
                        : '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>'
                    }
                </svg>`;
            }
            if (themeToggle) {
                themeToggle.title = theme === 'dark' ? 'Modo claro' : 'Modo oscuro';
            }
        }
        
        // Aplicar tema inicial ANTES de que se renderice la página
        applyTheme(currentTheme, true);
        
        // Toggle theme con aplicación INSTANTÁNEA
        if (themeToggle) {
            themeToggle.addEventListener('click', function(e) {
                e.preventDefault();
                const newTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                
                // Aplicar INMEDIATAMENTE sin esperar animaciones
                applyTheme(newTheme);
                localStorage.setItem('theme', newTheme);
            });
        }
    })();
    
    // Marcar notificaciones/mensajes como leídos visualmente al abrir los dropdowns
    document.addEventListener('DOMContentLoaded', function () {
        try {
            var notifToggle = document.getElementById('notifDropdown');
            var notifDot = document.getElementById('notifDot');
            if (notifToggle && notifDot) {
                notifToggle.addEventListener('show.bs.dropdown', function () {
                    notifDot.style.display = 'none';
                });
            }
            var msgToggle = document.getElementById('messagesDropdown');
            if (msgToggle) {
                msgToggle.addEventListener('show.bs.dropdown', function () {
                    var badge = msgToggle.querySelector('.badge-counter');
                    if (badge) badge.style.display = 'none';
                });
            }
        } catch (e) { console.warn('Header dropdown scripts:', e); }
    });
    </script>
</nav>