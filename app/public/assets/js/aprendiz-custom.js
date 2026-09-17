/**
 * Nalika Admin Dashboard — Main JavaScript
 * Vanilla JS (no jQuery), Bootstrap 5.3.8 compatible
 * Dependencies: Bootstrap 5.3.8, AOS 2.3.4, SimpleBar 6.2.7
 * Optional: Swiper 12, ApexCharts 5.3.6, FullCalendar 6.1.20
 */

'use strict';

/* ─── Inicialización Temprana de Tema (Evitar Flash) ─────────────────── */
(function() {
    var savedTheme = localStorage.getItem('sena_theme') || 'nalika';
    document.documentElement.setAttribute('data-theme', savedTheme);
})();

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

/* ─── Actualización Dinámica del Logo según el Tema ────────────────── */
function updateThemeLogos(themeName) {
    var isLight = (themeName === 'light');
    var logoFile = isLight ? 'logo_sena_negro.svg' : 'logo_sena_verde.svg';
    var logos = document.querySelectorAll('.main-logo, .top-logo img');
    logos.forEach(function(img) {
        var src = img.getAttribute('src') || '';
        var lastSlash = src.lastIndexOf('/');
        var newSrc;
        if (lastSlash !== -1) {
            newSrc = src.substring(0, lastSlash + 1) + logoFile;
        } else {
            newSrc = logoFile;
        }
        img.setAttribute('src', newSrc);
        img.src = newSrc;
        img.style.display = 'block';
        img.style.visibility = 'visible';
    });
}

/* ─── Selector de Temas ──────────────────────────────────────────────── */
function initThemeSwitcher() {
    var themeOptions = document.querySelectorAll('[data-theme-option]');
    themeOptions.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var themeName = this.getAttribute('data-theme-option');
            document.documentElement.setAttribute('data-theme', themeName);
            localStorage.setItem('sena_theme', themeName);
            localStorage.setItem('app_theme', themeName);
            document.cookie = "app_theme=" + themeName + "; path=/; max-age=" + (60*60*24*365);
            document.cookie = "sena_theme=" + themeName + "; path=/; max-age=" + (60*60*24*365);
            updateThemeLogos(themeName);
        });
    });

    var currentTheme = document.documentElement.getAttribute('data-theme') || localStorage.getItem('sena_theme') || 'nalika';
    updateThemeLogos(currentTheme);
}

/* ─── Controlador de Scrollbar Lateral Sin Ratón ─────────────────────── */
function initSidebarScrollController() {
  var container = document.getElementById('sidebarMenuWrap');
  var scrollbar = document.getElementById('sidebarCustomScrollbar');
  if (!container || !scrollbar) return;

  var track = document.getElementById('scrollTrack');
  var thumb = document.getElementById('scrollThumb');
  var btnUp = document.getElementById('scrollArrowUp');
  var btnDown = document.getElementById('scrollArrowDown');
  if (!track || !thumb) return;

  var isDragging = false;
  var startY = 0;
  var startScrollTop = 0;
  var autoScrollInterval = null;

  function updateThumb() {
    var scrollHeight = container.scrollHeight;
    var clientHeight = container.clientHeight;
    var trackHeight = track.clientHeight;

    if (trackHeight <= 0) return;

    if (scrollHeight <= clientHeight) {
      // Si el contenido cabe por completo, el thumb se mantiene visible con tamaño estético
      thumb.style.height = '42px';
      thumb.style.top = '0px';
      thumb.style.opacity = '0.5';
      return;
    }

    thumb.style.opacity = '1';
    var thumbHeight = Math.max(26, Math.round((clientHeight / scrollHeight) * trackHeight));
    thumb.style.height = thumbHeight + 'px';

    var maxScroll = scrollHeight - clientHeight;
    var maxTop = trackHeight - thumbHeight;
    var currentTop = Math.round((container.scrollTop / maxScroll) * maxTop);
    thumb.style.top = Math.max(0, Math.min(maxTop, currentTop)) + 'px';
  }

  // Evento Scroll del contenedor (rueda, touch, teclado)
  container.addEventListener('scroll', updateThumb, { passive: true });
  window.addEventListener('resize', updateThumb);

  // Arrastre del Thumb (Ratón)
  thumb.addEventListener('mousedown', function(e) {
    e.preventDefault();
    e.stopPropagation();
    isDragging = true;
    thumb.classList.add('is-dragging');
    startY = e.clientY;
    startScrollTop = container.scrollTop;
    document.body.style.userSelect = 'none';
  });

  document.addEventListener('mousemove', function(e) {
    if (!isDragging) return;
    var deltaY = e.clientY - startY;
    var trackHeight = track.clientHeight;
    var thumbHeight = thumb.clientHeight;
    var maxTop = trackHeight - thumbHeight;
    var maxScroll = container.scrollHeight - container.clientHeight;

    if (maxTop > 0 && maxScroll > 0) {
      var scrollRatio = deltaY / maxTop;
      container.scrollTop = startScrollTop + (scrollRatio * maxScroll);
    }
  });

  document.addEventListener('mouseup', function() {
    if (isDragging) {
      isDragging = false;
      thumb.classList.remove('is-dragging');
      document.body.style.userSelect = '';
    }
    if (autoScrollInterval) {
      clearInterval(autoScrollInterval);
      autoScrollInterval = null;
    }
  });

  // Soporte Touch para pantallas táctiles (sin ratón)
  thumb.addEventListener('touchstart', function(e) {
    if (!e.touches || !e.touches[0]) return;
    isDragging = true;
    thumb.classList.add('is-dragging');
    startY = e.touches[0].clientY;
    startScrollTop = container.scrollTop;
  }, { passive: true });

  document.addEventListener('touchmove', function(e) {
    if (!isDragging || !e.touches || !e.touches[0]) return;
    var deltaY = e.touches[0].clientY - startY;
    var trackHeight = track.clientHeight;
    var thumbHeight = thumb.clientHeight;
    var maxTop = trackHeight - thumbHeight;
    var maxScroll = container.scrollHeight - container.clientHeight;

    if (maxTop > 0 && maxScroll > 0) {
      var scrollRatio = deltaY / maxTop;
      container.scrollTop = startScrollTop + (scrollRatio * maxScroll);
    }
  }, { passive: true });

  document.addEventListener('touchend', function() {
    if (isDragging) {
      isDragging = false;
      thumb.classList.remove('is-dragging');
    }
    if (autoScrollInterval) {
      clearInterval(autoScrollInterval);
      autoScrollInterval = null;
    }
  });

  // Clic en la pista (Track)
  track.addEventListener('click', function(e) {
    if (e.target === thumb) return;
    var rect = track.getBoundingClientRect();
    var clickY = e.clientY - rect.top;
    var trackHeight = track.clientHeight;
    var thumbHeight = thumb.clientHeight;
    var maxScroll = container.scrollHeight - container.clientHeight;

    if (maxScroll > 0 && trackHeight > thumbHeight) {
      var targetPercent = Math.max(0, Math.min(1, (clickY - thumbHeight / 2) / (trackHeight - thumbHeight)));
      container.scrollTo({
        top: targetPercent * maxScroll,
        behavior: 'smooth'
      });
    }
  });

  // Botón Flecha Abajo (Click y mantener presionado)
  if (btnDown) {
    btnDown.addEventListener('click', function(e) {
      e.preventDefault();
      container.scrollBy({ top: 60, behavior: 'smooth' });
    });

    btnDown.addEventListener('mousedown', function(e) {
      e.preventDefault();
      if (autoScrollInterval) clearInterval(autoScrollInterval);
      autoScrollInterval = setInterval(function() {
        container.scrollTop += 12;
      }, 35);
    });

    btnDown.addEventListener('mouseleave', function() {
      if (autoScrollInterval) {
        clearInterval(autoScrollInterval);
        autoScrollInterval = null;
      }
    });
  }

  // Botón Flecha Arriba (Click y mantener presionado)
  if (btnUp) {
    btnUp.addEventListener('click', function(e) {
      e.preventDefault();
      container.scrollBy({ top: -60, behavior: 'smooth' });
    });

    btnUp.addEventListener('mousedown', function(e) {
      e.preventDefault();
      if (autoScrollInterval) clearInterval(autoScrollInterval);
      autoScrollInterval = setInterval(function() {
        container.scrollTop -= 12;
      }, 35);
    });

    btnUp.addEventListener('mouseleave', function() {
      if (autoScrollInterval) {
        clearInterval(autoScrollInterval);
        autoScrollInterval = null;
      }
    });
  }

  // Actualizar tras el render inicial
  setTimeout(updateThumb, 150);
}

/* ─── Inicialización de Componentes ────────────────────────────────── */

// initAOS(); // Depende de la inclusión de la librería

document.addEventListener('DOMContentLoaded', function () {
  initTooltips();
  initPopovers();
  initSidebar();
  initSidebarNav();
  initStickyHeader();
  initScrollToTop();
  initThemeSwitcher();
  initSidebarScrollController();
});