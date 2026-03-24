/**
 * ZYNIX CUSTOM JS - Sidebar Menu Behavior
 * Manejo de items activos y toggle de submenús
 */

document.addEventListener('DOMContentLoaded', function() {
  // PASO 1: Cerrar TODOS los menús al iniciar (limpieza)
  document.querySelectorAll('.sidebar-menu .nav-item').forEach(item => {
    item.classList.remove('menu-open');
  });
  
  // PASO 2: Marcar item activo y abrir su menú padre si es necesario
  const currentUrl = window.location.href;
  const menuLinks = document.querySelectorAll('.sidebar-menu .nav-link');
  
  menuLinks.forEach(link => {
    if (link.href === currentUrl) {
      link.classList.add('active');
      
      // Si es un submenu item, expandir SOLO el padre
      const parentTreeview = link.closest('.nav-treeview');
      if (parentTreeview) {
        const parentNavItem = parentTreeview.closest('.nav-item');
        if (parentNavItem) {
          // Abrir directamente sin click para evitar conflictos
          parentNavItem.classList.add('menu-open');
          
          // NO marcar el link del padre como 'active' para que no herede
          // el estilo visual del subitem. Solo dejamos la clase menu-open
          // en el nav-item para mantener el submenú desplegado.
        }
      }
    }
  });
  
  // PASO 3: Persistir estado del sidebar colapsado
  const sidebarToggle = document.querySelector('[data-lte-toggle="sidebar"]');
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function() {
      setTimeout(() => {
        const isCollapsed = document.body.classList.contains('sidebar-collapse');
        localStorage.setItem('sidebar-collapsed', isCollapsed);
      }, 100);
    });
    
    // Restaurar estado
    const wasCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
    if (wasCollapsed) {
      document.body.classList.add('sidebar-collapse');
    }
  }
});
