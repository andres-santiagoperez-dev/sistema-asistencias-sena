<!-- Token de Seguridad CSRF y Protección Global de Peticiones -->
<meta name="csrf-token" content="<?= Seguridad::tokenCsrf() ?>">
<script>
window.__CSRF_TOKEN__ = "<?= Seguridad::tokenCsrf() ?>";
(function() {
    // 1. Interceptor Global para la API Fetch (Cubre RFID, Sincronización Offline, AJAX)
    if (window.fetch) {
        const originalFetch = window.fetch;
        window.fetch = function(url, options) {
            options = options || {};
            const method = (options.method || 'GET').toUpperCase();
            if (method !== 'GET' && method !== 'HEAD') {
                options.headers = options.headers || {};
                if (options.headers instanceof Headers) {
                    if (!options.headers.has('X-CSRF-TOKEN')) {
                        options.headers.append('X-CSRF-TOKEN', window.__CSRF_TOKEN__);
                    }
                } else if (Array.isArray(options.headers)) {
                    options.headers.push(['X-CSRF-TOKEN', window.__CSRF_TOKEN__]);
                } else {
                    if (!options.headers['X-CSRF-TOKEN'] && !options.headers['x-csrf-token']) {
                        options.headers['X-CSRF-TOKEN'] = window.__CSRF_TOKEN__;
                    }
                }
            }
            return originalFetch.call(this, url, options);
        };
    }

    // 2. Interceptor Global para XMLHttpRequest (compatibilidad con librerías AJAX)
    if (window.XMLHttpRequest) {
        const origOpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function(method) {
            this._csrf_method = (method || 'GET').toUpperCase();
            return origOpen.apply(this, arguments);
        };
        const origSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.send = function() {
            if (this._csrf_method && this._csrf_method !== 'GET' && this._csrf_method !== 'HEAD') {
                try {
                    this.setRequestHeader('X-CSRF-TOKEN', window.__CSRF_TOKEN__);
                } catch (e) {}
            }
            return origSend.apply(this, arguments);
        };
    }

    // 3. Inyección automática en todos los formularios HTML POST al cargar el DOM
    function injectCsrfInputs() {
        document.querySelectorAll('form[method="POST" i], form[method="post"]').forEach(function(form) {
            if (!form.querySelector('input[name="csrf_token"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                input.value = window.__CSRF_TOKEN__;
                form.prepend(input);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectCsrfInputs);
    } else {
        injectCsrfInputs();
    }

    // 4. Delegación de eventos en envío de formularios (cubre modales dinámicos)
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form && form.tagName === 'FORM') {
            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            if (method === 'POST' && !form.querySelector('input[name="csrf_token"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                input.value = window.__CSRF_TOKEN__;
                form.prepend(input);
            }
        }
    }, true);
})();
</script>
