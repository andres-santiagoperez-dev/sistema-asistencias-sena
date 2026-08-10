/**
 * Nalika Admin Dashboard — Main JavaScript
 * Vanilla JS (no jQuery), Bootstrap 5.3.8 compatible
 * Dependencies: Bootstrap 5.3.8, AOS 2.3.4, SimpleBar 6.2.7
 * Optional: Swiper 12, ApexCharts 5.3.6, FullCalendar 6.1.20
 */

'use strict';

/* ─── Global Toggle Sidebar Handler (Sin duplicación de eventos) ──────── */

window.toggleSidebarGlobal = function (e) {
  if (e) {
    e.preventDefault();
  }
  var sidebar = document.getElementById('sidebar');
  var body = document.body;
  if (!sidebar) return;

  if (window.innerWidth < 992) {
    body.classList.toggle('sidebar-mobile-open');
  } else {
    sidebar.classList.toggle('active');
    body.classList.toggle('mini-navbar');
  }
};

/* ─── Bootstrap Tooltips & Popovers ──────────────────────────────────── */

function initTooltips() {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    new bootstrap.Tooltip(el);
  });
}

function initPopovers() {
  document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
    new bootstrap.Popover(el);
  });
}

function initSidebar() {
  var toggle = document.getElementById('sidebarCollapse');
  var sidebar = document.getElementById('sidebar');
  if (!sidebar) return;

  // Backdrop para overlay móvil
  var backdrop = document.querySelector('.sidebar-backdrop');
  if (!backdrop) {
    backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);
  }

  function isMobile() {
    return window.innerWidth < 992;
  }

  // Vincular event listener solo si no existe onclick en HTML
  if (toggle && !toggle.getAttribute('onclick')) {
    toggle.addEventListener('click', window.toggleSidebarGlobal);
  }

  // Cerrar menú móvil al hacer clic en el fondo translúcido
  backdrop.addEventListener('click', function () {
    document.body.classList.remove('sidebar-mobile-open');
  });

  // Cerrar menú móvil al cambiar tamaño a pantalla de escritorio
  window.addEventListener('resize', function () {
    if (!isMobile()) {
      document.body.classList.remove('sidebar-mobile-open');
    }
  });

  // Toggle de submenús
  sidebar.querySelectorAll('ul li').forEach(function (li) {
    li.addEventListener('click', function () {
      var icon = this.querySelector('i.bi.indicator-mn');
      if (icon) {
        icon.classList.toggle('bi-plus');
        icon.classList.toggle('bi-dash');
      }
    });
  });
}

/* ─── Navegación del Menú Lateral ────────────────────────────────────── */

function initSidebarNav() {
  var menu = document.getElementById('menu1');
  if (!menu) return;

  menu.querySelectorAll('.has-arrow').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      var parent = this.parentElement;
      var submenu = parent.querySelector('.submenu-angle');
      if (!submenu) return;

      var siblings = parent.parentElement.children;
      for (var i = 0; i < siblings.length; i++) {
        if (siblings[i] !== parent) {
          siblings[i].classList.remove('active');
          var sub = siblings[i].querySelector('.submenu-angle');
          if (sub) sub.style.display = 'none';
        }
      }

      parent.classList.toggle('active');
      if (submenu.style.display === 'block') {
        submenu.style.display = 'none';
      } else {
        submenu.style.display = 'block';
      }
    });
  });

  var currentPage = window.location.pathname.split('/').pop() || 'index.html';
  menu.querySelectorAll('a[href="' + currentPage + '"]').forEach(function (link) {
    var li = link.closest('li.active, li');
    if (li) li.classList.add('active');
    var parentSubmenu = link.closest('.submenu-angle');
    if (parentSubmenu) {
      parentSubmenu.style.display = 'block';
      var parentLi = parentSubmenu.parentElement;
      if (parentLi) parentLi.classList.add('active');
    }
  });
}

/* ─── Sticky Header ──────────────────────────────────────────────────── */

function initStickyHeader() {
  var stickyEl = document.querySelector('.sicker-menu');
  if (!stickyEl) return;

  var offsetTop = stickyEl.offsetTop;

  function handleScroll() {
    if (window.scrollY >= offsetTop) {
      stickyEl.classList.add('is-sticky');
      stickyEl.style.position = 'fixed';
      stickyEl.style.top = '0';
      stickyEl.style.width = '100%';
      stickyEl.style.zIndex = '9999';
    } else {
      stickyEl.classList.remove('is-sticky');
      stickyEl.style.position = '';
      stickyEl.style.top = '';
      stickyEl.style.width = '';
      stickyEl.style.zIndex = '';
    }
  }

  window.addEventListener('scroll', handleScroll, { passive: true });
}

/* ─── Scroll to Top Oculto ───────────────────────────────────────────── */

function initScrollToTop() {
  var btn = document.createElement('a');
  btn.id = 'scrollUp';
  btn.href = '#';
  btn.innerHTML = '<i class="bi bi-chevron-up"></i>';
  btn.style.cssText = 'display:none;position:fixed;bottom:20px;right:20px;z-index:9999;' +
    'width:40px;height:40px;line-height:40px;text-align:center;border-radius:4px;' +
    'background:#ec4445;color:#fff;font-size:18px;text-decoration:none;transition:opacity 0.3s;';
  document.body.appendChild(btn);
}

/* ─── Inicialización de Componentes ────────────────────────────────── */

initAOS();

document.addEventListener('DOMContentLoaded', function () {
  initTooltips();
  initPopovers();
  initSidebar();
  initSidebarNav();
  initStickyHeader();
  initScrollToTop();
});