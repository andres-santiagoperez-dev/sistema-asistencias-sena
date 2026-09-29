/**
 * Outlook-Inspired Calendar System - JavaScript Helpers
 * Sistema de Control de Asistencia SENA
 * 
 * Funcionalidades:
 * - Mini Calendario interactivo sincronizado bidireccionalmente con FullCalendar.
 * - Formateador de títulos de rango de fechas estilo Outlook.
 * - Control de alternancia de vistas (Día, Semana Laboral, Semana, Mes, Agenda).
 * - Sincronización automática ante cambios de tema (Nalika, Dark, Light).
 */

class OutlookCalendarHelper {
    /**
     * @param {Object} config
     * @param {FullCalendar.Calendar} config.mainCalendar
     * @param {HTMLElement} config.miniCalendarEl
     * @param {HTMLElement} config.titleEl
     * @param {HTMLElement} config.railSidebarEl
     */
    constructor(config) {
        this.mainCalendar = config.mainCalendar;
        this.miniCalendarEl = config.miniCalendarEl;
        this.titleEl = config.titleEl;
        this.railSidebarEl = config.railSidebarEl;
        
        // Estado del mini calendario
        this.miniCurrentDate = new Date();
        this.selectedDate = new Date();
        this.currentViewType = 'workWeek'; // 'workWeek', 'timeGridWeek', 'timeGridDay', 'dayGridMonth', 'listWeek'
        
        this.init();
    }

    init() {
        this.renderMiniCalendar();
        this.setupThemeObserver();
    }

    /**
     * Renderiza el mini calendario del riel lateral estilo Outlook
     */
    renderMiniCalendar() {
        if (!this.miniCalendarEl) return;

        const year = this.miniCurrentDate.getFullYear();
        const month = this.miniCurrentDate.getMonth();

        const monthNames = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];

        // Primer día del mes y total de días
        const firstDayOfMonth = new Date(year, month, 1);
        const lastDayOfMonth = new Date(year, month + 1, 0);
        const daysInMonth = lastDayOfMonth.getDate();

        // Día de la semana en que inicia el mes (0: Domingo, 1: Lunes... 6: Sábado)
        const startDayOfWeek = firstDayOfMonth.getDay();

        // Días del mes anterior para rellenar
        const prevMonthLastDay = new Date(year, month, 0).getDate();

        let html = `
            <div class="outlook-mini-header">
                <span class="outlook-mini-title">${monthNames[month]} ${year}</span>
                <div class="outlook-mini-nav">
                    <button type="button" class="outlook-mini-nav-btn" id="btnMiniPrev" title="Mes Anterior">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button type="button" class="outlook-mini-nav-btn" id="btnMiniNext" title="Mes Siguiente">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
            <div class="outlook-mini-weekdays">
                <span>D</span><span>L</span><span>M</span><span>M</span><span>J</span><span>V</span><span>S</span>
            </div>
            <div class="outlook-mini-grid">
        `;

        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];
        const selectedStr = this.selectedDate ? this.selectedDate.toISOString().split('T')[0] : '';

        // Calcular rango de la semana activa actualmente en el calendario principal
        let activeRange = null;
        if (this.mainCalendar && this.mainCalendar.view) {
            activeRange = {
                start: this.mainCalendar.view.activeStart,
                end: this.mainCalendar.view.activeEnd
            };
        }

        // 1. Días del mes anterior
        for (let i = startDayOfWeek - 1; i >= 0; i--) {
            const dayNum = prevMonthLastDay - i;
            const dateObj = new Date(year, month - 1, dayNum);
            const dateStr = dateObj.toISOString().split('T')[0];
            html += `<div class="outlook-mini-day other-month" data-date="${dateStr}">${dayNum}</div>`;
        }

        // 2. Días del mes actual
        for (let d = 1; d <= daysInMonth; d++) {
            const dateObj = new Date(year, month, d);
            const dateStr = dateObj.toISOString().split('T')[0];
            const dayOfWeek = dateObj.getDay();

            let classes = ['outlook-mini-day'];
            if (dateStr === todayStr) classes.push('today');
            if (dateStr === selectedStr) classes.push('selected');

            // Resaltar semana activa si aplica
            if (activeRange && dateObj >= activeRange.start && dateObj < activeRange.end) {
                classes.push('in-selected-week');
                if (dayOfWeek === 1 || d === 1) classes.push('week-start');
                if (dayOfWeek === 5 || dayOfWeek === 6 || d === daysInMonth) classes.push('week-end');
            }

            html += `<div class="${classes.join(' ')}" data-date="${dateStr}">${d}</div>`;
        }

        // 3. Días del mes siguiente para completar la cuadrícula (hasta 35 o 42 celdas)
        const totalRendered = startDayOfWeek + daysInMonth;
        const remainingCells = (totalRendered <= 35 ? 35 : 42) - totalRendered;
        for (let n = 1; n <= remainingCells; n++) {
            const dateObj = new Date(year, month + 1, n);
            const dateStr = dateObj.toISOString().split('T')[0];
            html += `<div class="outlook-mini-day other-month" data-date="${dateStr}">${n}</div>`;
        }

        html += `</div>`;
        this.miniCalendarEl.innerHTML = html;

        // Enlazar eventos del mini calendario
        const btnPrev = this.miniCalendarEl.querySelector('#btnMiniPrev');
        const btnNext = this.miniCalendarEl.querySelector('#btnMiniNext');

        if (btnPrev) {
            btnPrev.addEventListener('click', (e) => {
                e.stopPropagation();
                this.miniCurrentDate.setMonth(this.miniCurrentDate.getMonth() - 1);
                this.renderMiniCalendar();
            });
        }

        if (btnNext) {
            btnNext.addEventListener('click', (e) => {
                e.stopPropagation();
                this.miniCurrentDate.setMonth(this.miniCurrentDate.getMonth() + 1);
                this.renderMiniCalendar();
            });
        }

        // Clic en cualquier celda de fecha para navegar
        const dayCells = this.miniCalendarEl.querySelectorAll('.outlook-mini-day');
        dayCells.forEach(cell => {
            cell.addEventListener('click', () => {
                const dateStr = cell.getAttribute('data-date');
                if (dateStr) {
                    const targetDate = new Date(dateStr + 'T00:00:00');
                    this.selectedDate = targetDate;
                    if (this.mainCalendar) {
                        this.mainCalendar.gotoDate(targetDate);
                    }
                    this.renderMiniCalendar();
                }
            });
        });
    }

    /**
     * Sincroniza el mini calendario y el título cuando el calendario principal cambia fechas
     */
    syncFromMainCalendar(view) {
        if (!view) return;

        // Sincronizar mes visible en mini si la fecha central difiere
        const midDate = new Date((view.activeStart.getTime() + view.activeEnd.getTime()) / 2);
        this.miniCurrentDate = new Date(midDate.getFullYear(), midDate.getMonth(), 1);
        this.selectedDate = new Date(this.mainCalendar.getDate());

        this.renderMiniCalendar();
        this.updateOutlookTitle(view);
    }

    /**
     * Genera el texto del encabezado estilo Outlook:
     * Ej: "28 de Septiembre – 2 de Octubre de 2026"
     */
    updateOutlookTitle(view) {
        if (!this.titleEl || !view) return;

        const months = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];

        const start = view.activeStart;
        // activeEnd es exclusivo en FC, restamos 1 segundo
        const end = new Date(view.activeEnd.getTime() - 1000);

        let titleStr = '';

        if (view.type === 'dayGridMonth') {
            titleStr = `${months[start.getMonth()]} de ${start.getFullYear()}`;
        } else if (view.type === 'timeGridDay' || (start.toDateString() === end.toDateString())) {
            titleStr = `${start.getDate()} de ${months[start.getMonth()]} de ${start.getFullYear()}`;
        } else {
            // Rango de semana
            const startDay = start.getDate();
            const endDay = end.getDate();
            const startMonth = months[start.getMonth()];
            const endMonth = months[end.getMonth()];
            const startYear = start.getFullYear();
            const endYear = end.getFullYear();

            if (startMonth === endMonth && startYear === endYear) {
                titleStr = `${startDay} – ${endDay} de ${startMonth} de ${startYear}`;
            } else if (startYear === endYear) {
                titleStr = `${startDay} de ${startMonth} – ${endDay} de ${endMonth} de ${startYear}`;
            } else {
                titleStr = `${startDay} de ${startMonth} de ${startYear} – ${endDay} de ${endMonth} de ${endYear}`;
            }
        }

        this.titleEl.innerHTML = `${titleStr} <i class="bi bi-chevron-down ms-1" style="font-size:0.75rem; opacity:0.6;"></i>`;
    }

    /**
     * Cambia entre las vistas estilo Outlook:
     * - 'workWeek': Lunes a Viernes (Semana Laboral)
     * - 'week': 7 días (Lunes a Domingo)
     * - 'day': Día individual
     * - 'month': Mes completo
     * - 'list': Agenda
     */
    cambiarVista(tipo) {
        if (!this.mainCalendar) return;

        this.currentViewType = tipo;

        // Actualizar estado activo en los botones de vista
        const btns = document.querySelectorAll('.outlook-btn-view');
        btns.forEach(b => {
            if (b.getAttribute('data-view') === tipo) {
                b.classList.add('active');
            } else {
                b.classList.remove('active');
            }
        });

        if (tipo === 'workWeek') {
            this.mainCalendar.setOption('hiddenDays', [0, 6]); // Ocultar Domingo y Sábado
            this.mainCalendar.changeView('timeGridWeek');
        } else if (tipo === 'week') {
            this.mainCalendar.setOption('hiddenDays', []); // Mostrar todos los días
            this.mainCalendar.changeView('timeGridWeek');
        } else if (tipo === 'day') {
            this.mainCalendar.setOption('hiddenDays', []);
            this.mainCalendar.changeView('timeGridDay');
        } else if (tipo === 'month') {
            this.mainCalendar.setOption('hiddenDays', []);
            this.mainCalendar.changeView('dayGridMonth');
        } else if (tipo === 'list') {
            this.mainCalendar.setOption('hiddenDays', []);
            this.mainCalendar.changeView('listWeek');
        }
    }

    /**
     * Alterna la visibilidad del panel lateral rail
     */
    toggleRail() {
        if (!this.railSidebarEl) return;
        this.railSidebarEl.classList.toggle('collapsed');
    }

    /**
     * Observa cambios dinámicos en el tema HTML (Nalika, Dark, Light)
     */
    setupThemeObserver() {
        const observer = new MutationObserver(() => {
            if (this.mainCalendar) {
                this.mainCalendar.render();
            }
            this.renderMiniCalendar();
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme']
        });
    }

    /**
     * Generador de celdas de encabezado de día estilo Outlook:
     * Número arriba grande + Día de la semana abajo
     */
    static renderHeaderCell(arg) {
        const dayNum = arg.date.getDate();
        const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        const dayName = dayNames[arg.date.getDay()];

        return {
            html: `
                <div class="outlook-header-day-cell">
                    <span class="outlook-header-day-num">${dayNum}</span>
                    <span class="outlook-header-day-name">${dayName}</span>
                </div>
            `
        };
    }

    /**
     * Renderizador de contenido de eventos estilo Outlook:
     * Franja vertical de color, título en negrita, horario y etiqueta
     */
    static renderEventContent(eventInfo) {
        const title = eventInfo.event.title || 'Formación';
        const start = eventInfo.event.start;
        const end = eventInfo.event.end;
        
        let timeStr = '';
        if (start) {
            timeStr = start.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
            if (end) {
                timeStr += ' - ' + end.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
            }
        }

        const color = eventInfo.event.backgroundColor || '#39a900';
        const badge = eventInfo.event.extendedProps.ficha ? `Ficha ${eventInfo.event.extendedProps.ficha}` : (eventInfo.event.extendedProps.isNota ? 'Observación' : '');

        return {
            html: `
                <div class="outlook-event-card" style="border-left-color: ${color}; background-color: ${hexToRgba(color, 0.18)};">
                    <span class="outlook-event-title">${escapeHtml(title)}</span>
                    ${timeStr ? `<span class="outlook-event-time"><i class="bi bi-clock me-1"></i>${timeStr}</span>` : ''}
                    ${badge ? `<span class="outlook-event-badge">${escapeHtml(badge)}</span>` : ''}
                </div>
            `
        };
    }
}

// Funciones Utilitarias
function hexToRgba(hex, alpha) {
    hex = (hex || '#39a900').replace('#', '');
    if (hex.length === 3) {
        hex = hex.split('').map(c => c + c).join('');
    }
    const r = parseInt(hex.substring(0, 2), 16) || 57;
    const g = parseInt(hex.substring(2, 4), 16) || 169;
    const b = parseInt(hex.substring(4, 6), 16) || 0;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
