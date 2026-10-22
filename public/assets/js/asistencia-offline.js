/**
 * Motor Offline-First y Persistencia de Sesión Local
 * Sistema de Control de Asistencia SENA
 * 
 * Gestiona:
 * 1. Base de datos IndexedDB local (Directorio de Aprendices, Cola de Marcaciones, Perfil de Sesión).
 * 2. Detección de conectividad en tiempo real (Online / Offline).
 * 3. Encolado de escaneos sin conexión con hora real del dispositivo.
 * 4. Sincronización automática y manual en lotes con el servidor.
 * 5. Revalidación de sesión ante desconexiones prolongadas.
 */

const AsistenciaOffline = (function() {
    'use strict';

    const DB_NAME = 'SenaAsistenciaDB';
    const DB_VERSION = 2;
    let dbInstance = null;

    // =========================================================================
    // 1. INICIALIZACIÓN DE INDEXEDDB
    // =========================================================================
    function abrirDB() {
        return new Promise((resolve, reject) => {
            if (dbInstance) {
                return resolve(dbInstance);
            }

            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = function(event) {
                const db = event.target.result;

                // Almacén 1: Perfil de sesión del instructor
                if (!db.objectStoreNames.contains('perfil_sesion')) {
                    db.createObjectStore('perfil_sesion', { keyPath: 'clave' });
                }

                // Almacén 2: Directorio de aprendices de las fichas del docente
                if (!db.objectStoreNames.contains('directorio_aprendices')) {
                    const storeApr = db.createObjectStore('directorio_aprendices', { keyPath: 'id_aprendiz' });
                    storeApr.createIndex('codigo_rfid', 'codigo_rfid', { unique: false });
                    storeApr.createIndex('numero_documento', 'numero_documento', { unique: false });
                    storeApr.createIndex('fichas_id_ficha', 'fichas_id_ficha', { unique: false });
                }

                // Almacén 3: Cola de marcaciones pendientes
                if (!db.objectStoreNames.contains('cola_asistencias')) {
                    const storeCola = db.createObjectStore('cola_asistencias', { keyPath: 'id_offline', autoIncrement: true });
                    storeCola.createIndex('timestamp', 'timestamp', { unique: false });
                    storeCola.createIndex('codigo', 'codigo', { unique: false });
                }
            };

            request.onsuccess = function(event) {
                dbInstance = event.target.result;
                resolve(dbInstance);
            };

            request.onerror = function(event) {
                console.error('[IndexedDB] Error al abrir base de datos:', event.target.error);
                reject(event.target.error);
            };
        });
    }

    // =========================================================================
    // 2. GESTIÓN DEL PERFIL DE SESIÓN OFFLINE
    // =========================================================================
    async function guardarPerfilSesion(perfil) {
        try {
            const db = await abrirDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction('perfil_sesion', 'readwrite');
                const store = tx.objectStore('perfil_sesion');
                const registro = {
                    clave: 'actual',
                    id_instructor: perfil.id_instructor,
                    id_usuario: perfil.id_usuario,
                    nombre_completo: perfil.nombre_completo,
                    correo: perfil.correo,
                    fichas: perfil.fichas || [],
                    actualizado_en: new Date().toISOString()
                };
                const req = store.put(registro);
                req.onsuccess = () => resolve(registro);
                req.onerror = () => reject(req.error);
            });
        } catch (e) {
            console.warn('[Offline] No se pudo guardar el perfil de sesión en IndexedDB:', e);
        }
    }

    async function obtenerPerfilSesion() {
        try {
            const db = await abrirDB();
            return new Promise((resolve) => {
                const tx = db.transaction('perfil_sesion', 'readonly');
                const store = tx.objectStore('perfil_sesion');
                const req = store.get('actual');
                req.onsuccess = () => resolve(req.result || null);
                req.onerror = () => resolve(null);
            });
        } catch (e) {
            return null;
        }
    }

    // =========================================================================
    // 3. DIRECTORIO DE APRENDICES (BÚSQUEDA SIN RED)
    // =========================================================================
    async function guardarDirectorioAprendices(aprendices) {
        if (!Array.isArray(aprendices) || aprendices.length === 0) return;
        try {
            const db = await abrirDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction('directorio_aprendices', 'readwrite');
                const store = tx.objectStore('directorio_aprendices');
                aprendices.forEach(apr => {
                    store.put(apr);
                });
                tx.oncomplete = () => {
                    console.log(`[Offline] Directorio local sincronizado: ${aprendices.length} aprendices en memoria.`);
                    resolve(aprendices.length);
                };
                tx.onerror = () => reject(tx.error);
            });
        } catch (e) {
            console.warn('[Offline] Error al guardar directorio de aprendices:', e);
        }
    }

    async function buscarAprendizOffline(codigo) {
        if (!codigo) return null;
        const codigoBuscado = String(codigo).trim();
        try {
            const db = await abrirDB();
            return new Promise((resolve) => {
                const tx = db.transaction('directorio_aprendices', 'readonly');
                const store = tx.objectStore('directorio_aprendices');

                // Búsqueda por RFID
                const idxRfid = store.index('codigo_rfid');
                const reqRfid = idxRfid.get(codigoBuscado);

                reqRfid.onsuccess = function() {
                    if (reqRfid.result) {
                        return resolve(reqRfid.result);
                    }

                    // Búsqueda por Documento
                    const idxDoc = store.index('numero_documento');
                    const reqDoc = idxDoc.get(codigoBuscado);

                    reqDoc.onsuccess = function() {
                        if (reqDoc.result) {
                            return resolve(reqDoc.result);
                        }

                        // Búsqueda por ID directo
                        const reqId = store.get(parseInt(codigoBuscado, 10) || 0);
                        reqId.onsuccess = function() {
                            resolve(reqId.result || null);
                        };
                        reqId.onerror = () => resolve(null);
                    };
                    reqDoc.onerror = () => resolve(null);
                };
                reqRfid.onerror = () => resolve(null);
            });
        } catch (e) {
            return null;
        }
    }

    // Actualiza el directorio de aprendices desde el servidor si hay conexión
    async function actualizarDirectorioDesdeServidor() {
        if (!navigator.onLine) return;
        try {
            const resp = await fetch('index.php?page=instructor_ingresos&action=obtener_directorio_offline', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (resp.ok) {
                const data = await resp.json();
                if (data.success && data.aprendices) {
                    await guardarDirectorioAprendices(data.aprendices);
                    if (data.perfil) {
                        await guardarPerfilSesion(data.perfil);
                    }
                }
            }
        } catch (e) {
            // Continúa en silencio si no responde el endpoint
        }
    }

    // =========================================================================
    // 4. COLA DE MARCACIONES SIN CONEXIÓN
    // =========================================================================
    function formatearHora12(hh, mm) {
        const sufijo = hh >= 12 ? 'PM' : 'AM';
        const h12 = hh % 12 || 12;
        return `${String(h12).padStart(2, '0')}:${String(mm).padStart(2, '0')} ${sufijo}`;
    }

    async function encolarMarcacion(codigo, esManual = false, aprendizEncontrado = null) {
        const ahora = new Date();
        const anio = ahora.getFullYear();
        const mes = String(ahora.getMonth() + 1).padStart(2, '0');
        const dia = String(ahora.getDate()).padStart(2, '0');
        const fechaCaptura = `${anio}-${mes}-${dia}`;

        const horas = String(ahora.getHours()).padStart(2, '0');
        const mins  = String(ahora.getMinutes()).padStart(2, '0');
        const segs  = String(ahora.getSeconds()).padStart(2, '0');
        const horaCaptura = `${horas}:${mins}:${segs}`;
        const horaFormateada = formatearHora12(ahora.getHours(), ahora.getMinutes());

        const db = await abrirDB();

        // Control de doble escaneo accidental en menos de 20 segundos
        const colaActual = await obtenerMarcacionesPendientes();
        const duplicadoReciente = colaActual.find(item => 
            item.codigo === codigo && (Date.now() - item.timestamp) < 20000
        );

        if (duplicadoReciente) {
            return {
                duplicado: true,
                message: 'Marcación ya encolada recientemente. Espere unos segundos.'
            };
        }

        const registro = {
            codigo: String(codigo).trim(),
            es_manual: !!esManual,
            fecha_captura: fechaCaptura,
            hora_captura: horaCaptura,
            hora_formateada: horaFormateada,
            timestamp: Date.now(),
            aprendiz_datos: aprendizEncontrado || null
        };

        return new Promise((resolve, reject) => {
            const tx = db.transaction('cola_asistencias', 'readwrite');
            const store = tx.objectStore('cola_asistencias');
            const req = store.add(registro);

            req.onsuccess = function() {
                registro.id_offline = req.result;
                actualizarIndicadorCola();
                resolve({
                    success: true,
                    registro: registro,
                    message: `Marcación guardada localmente sin conexión (${registro.hora_formateada}).`
                });
            };

            req.onerror = function() {
                reject(req.error);
            };
        });
    }

    async function obtenerMarcacionesPendientes() {
        try {
            const db = await abrirDB();
            return new Promise((resolve) => {
                const tx = db.transaction('cola_asistencias', 'readonly');
                const store = tx.objectStore('cola_asistencias');
                const req = store.getAll();
                req.onsuccess = () => resolve(req.result || []);
                req.onerror = () => resolve([]);
            });
        } catch (e) {
            return [];
        }
    }

    async function eliminarMarcacionesSincronizadas(idsSincronizados) {
        if (!Array.isArray(idsSincronizados) || idsSincronizados.length === 0) return;
        try {
            const db = await abrirDB();
            return new Promise((resolve) => {
                const tx = db.transaction('cola_asistencias', 'readwrite');
                const store = tx.objectStore('cola_asistencias');
                idsSincronizados.forEach(id => {
                    store.delete(id);
                });
                tx.oncomplete = () => {
                    actualizarIndicadorCola();
                    resolve();
                };
            });
        } catch (e) {
            console.warn('[Offline] Error al eliminar elementos sincronizados de la cola:', e);
        }
    }

    // =========================================================================
    // 5. MOTOR DE SINCRONIZACIÓN CON EL SERVIDOR
    // =========================================================================
    let sincronizandoActualmente = false;

    async function sincronizarCola(mostrarNotificacionVacia = false) {
        if (sincronizandoActualmente) return;
        if (!navigator.onLine) {
            if (mostrarNotificacionVacia) {
                mostrarAlertaUI('No hay conexión a internet actualmente. Las marcaciones permanecen protegidas en el dispositivo.', 'warning');
            }
            return;
        }

        const cola = await obtenerMarcacionesPendientes();
        if (cola.length === 0) {
            if (mostrarNotificacionVacia) {
                mostrarAlertaUI('Todas las marcaciones están sincronizadas con el servidor.', 'success');
            }
            return;
        }

        sincronizandoActualmente = true;
        const btnSincronizar = document.getElementById('btnSincronizarCola');
        if (btnSincronizar) {
            btnSincronizar.disabled = true;
            btnSincronizar.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Sincronizando...';
        }

        try {
            const payload = {
                lote: cola.map(item => ({
                    id_offline: item.id_offline,
                    codigo: item.codigo,
                    es_manual: item.es_manual ? 1 : 0,
                    fecha: item.fecha_captura,
                    hora: item.hora_captura
                }))
            };

            const response = await fetch('index.php?page=instructor_ingresos&action=sincronizar_offline', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            if (response.status === 401) {
                // Sesión PHP caducada durante la desconexión
                abrirModalReautenticacion();
                return;
            }

            const resultado = await response.json();

            if (resultado.session_expired) {
                abrirModalReautenticacion();
                return;
            }

            if (resultado.success) {
                const idsExitosos = (resultado.procesados || []).map(p => p.id_offline);
                await eliminarMarcacionesSincronizadas(idsExitosos);

                mostrarAlertaUI(`¡Sincronización completada! Se procesaron ${idsExitosos.length} marcaciones tomadas sin conexión.`, 'success');

                // Si alguna generó llamado formal disciplinario
                if (resultado.llamados_generados && resultado.llamados_generados.length > 0) {
                    mostrarAlertaUI(`Atención: Se formalizaron ${resultado.llamados_generados.length} llamado(s) de atención disciplinarios por reincidencia manual.`, 'warning');
                }

                // Si el servidor devolvió la tabla completa actualizada, refrescarla
                if (resultado.ingresos && typeof window.actualizarTablaIngresos === 'function') {
                    window.actualizarTablaIngresos(resultado.ingresos);
                }
            } else {
                mostrarAlertaUI(resultado.message || 'Ocurrió un error al sincronizar con el servidor.', 'danger');
            }
        } catch (error) {
            console.error('[Offline] Error de red durante la sincronización:', error);
            mostrarAlertaUI('Error de conexión al sincronizar. Se reintentará automáticamente al recuperar estabilidad de red.', 'warning');
        } finally {
            sincronizandoActualmente = false;
            actualizarIndicadorCola();
        }
    }

    // =========================================================================
    // 6. UI: BADGES, ESTADOS Y RE-AUTENTICACIÓN
    // =========================================================================
    function actualizarEstadoRedUI(online) {
        const badgeRed = document.getElementById('badgeConectividad');
        if (!badgeRed) return;

        if (online) {
            badgeRed.className = 'badge d-inline-flex align-items-center gap-1 px-2.5 py-1.5 shadow-xs fw-semibold';
            badgeRed.style.background = 'var(--pastel-success-bg, #dcfce7)';
            badgeRed.style.color = 'var(--pastel-success-text, #166534)';
            badgeRed.style.border = '1px solid var(--pastel-success-border, #86efac)';
            badgeRed.innerHTML = '<i class="bi bi-wifi"></i> En Línea';
        } else {
            badgeRed.className = 'badge d-inline-flex align-items-center gap-1 px-2.5 py-1.5 shadow-xs fw-semibold';
            badgeRed.style.background = 'var(--pastel-warning-bg, #fef3c7)';
            badgeRed.style.color = 'var(--pastel-warning-text, #92400e)';
            badgeRed.style.border = '1px solid var(--pastel-warning-border, #fcd34d)';
            badgeRed.innerHTML = '<i class="bi bi-wifi-off"></i> Modo Sin Conexión (Offline)';
        }
    }

    async function actualizarIndicadorCola() {
        const cola = await obtenerMarcacionesPendientes();
        const btnSincronizar = document.getElementById('btnSincronizarCola');
        const badgeContador = document.getElementById('badgeContadorCola');

        if (!btnSincronizar) return;

        if (cola.length > 0) {
            btnSincronizar.classList.remove('d-none');
            btnSincronizar.disabled = false;
            btnSincronizar.innerHTML = `<i class="bi bi-cloud-arrow-up-fill me-1"></i> Sincronizar <span class="badge bg-danger rounded-pill ms-1" id="badgeContadorCola">${cola.length}</span> pendiente${cola.length > 1 ? 's' : ''}`;
        } else {
            btnSincronizar.classList.add('d-none');
        }
    }

    function mostrarAlertaUI(mensaje, tipo = 'info') {
        const contenedor = document.getElementById('contenedorFeedbackScan');
        if (!contenedor) return;

        const iconos = {
            success: 'bi-check-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            danger: 'bi-x-circle-fill',
            info: 'bi-info-circle-fill'
        };

        const icono = iconos[tipo] || iconos.info;

        contenedor.innerHTML = `
            <div class="alert alert-${tipo} border-0 shadow-sm p-3 mb-0" style="border-radius: 10px;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi ${icono} fs-5"></i>
                    <strong style="font-size: 13.5px;">${mensaje}</strong>
                </div>
            </div>
        `;
    }

    function abrirModalReautenticacion() {
        const el = document.getElementById('modalReautenticacionOffline');
        if (el && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(el);
            modal.show();
        }
    }

    // =========================================================================
    // 7. INICIALIZADOR GLOBAL DEL MÓDULO
    // =========================================================================
    function inicializar() {
        // 1. Registrar Service Worker si el navegador lo soporta
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('./public/sw.js')
                .then(reg => {
                    console.log('[ServiceWorker] Registrado con éxito en ámbito:', reg.scope);
                })
                .catch(err => {
                    console.warn('[ServiceWorker] Falló el registro:', err);
                });
        }

        // 2. Establecer estado inicial de conectividad
        actualizarEstadoRedUI(navigator.onLine);
        actualizarIndicadorCola();

        // 3. Escuchar eventos de red en vivo
        window.addEventListener('online', () => {
            actualizarEstadoRedUI(true);
            mostrarAlertaUI('Conexión a internet restablecida. Iniciando sincronización de cola...', 'info');
            actualizarDirectorioDesdeServidor();
            sincronizarCola(false);
        });

        window.addEventListener('offline', () => {
            actualizarEstadoRedUI(false);
            mostrarAlertaUI('Se perdió la conexión a internet. El sistema operará en Modo Offline guardando las marcaciones localmente.', 'warning');
        });

        // 4. Si hay conexión al cargar, refrescar directorio local y sincronizar cola pendiente
        if (navigator.onLine) {
            actualizarDirectorioDesdeServidor();
            sincronizarCola(false);
        }
    }

    // API Pública expuesta
    return {
        inicializar,
        buscarAprendizOffline,
        encolarMarcacion,
        sincronizarCola,
        guardarPerfilSesion,
        obtenerPerfilSesion,
        actualizarIndicadorCola,
        obtenerMarcacionesPendientes
    };
})();

// Auto-inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', AsistenciaOffline.inicializar);
} else {
    AsistenciaOffline.inicializar();
}
