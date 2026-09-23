        </div> <!-- End section-admin container-fluid -->

        <!-- Footer Copyright Area -->
        <div class="footer-copyright-area mt-5">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="footer-copy-right text-center py-3 border-top border-secondary border-opacity-25">
                            <p class="mb-0 text-secondary" style="font-size:13px;">
                                Sistema de Control de Ingreso de Aprendices SENA &copy; <?= date('Y') ?> | Centro de Comercio y Servicios.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- End all-content-wrapper -->

    <!-- Botón Flotante Volver Arriba -->
    <a id="btnScrollTop" class="sena-btn-scroll-top" href="#" onclick="window.scrollTo({top:0, behavior:'smooth'}); return false;" title="Volver al Inicio">
        <i class="bi bi-arrow-up"></i>
    </a>

    <!-- Scripts Esenciales -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="<?= BASE_URL ?>/public/assets/js/main.js"></script>
    <script src="<?= BASE_URL ?>/public/assets/js/aprendiz-custom.js?v=<?= time() ?>"></script>

    <!-- Control de Scroll y Menú Móvil -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Cierre de menú lateral en versión móvil
            var closeBtnMobile = document.getElementById('sidebarCloseMobile');
            if (closeBtnMobile) {
                closeBtnMobile.addEventListener('click', function () {
                    document.body.classList.remove('sidebar-mobile-open');
                });
            }

            // Barra de progreso y botón Scroll Top
            window.addEventListener('scroll', function() {
                var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
                var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                var scrolled = height > 0 ? (winScroll / height) * 100 : 0;
                
                var progressBar = document.getElementById('scrollProgressBar');
                if (progressBar) progressBar.style.width = scrolled + '%';

                var btnTop = document.getElementById('btnScrollTop');
                if (btnTop) {
                    if (winScroll > 200) {
                        btnTop.classList.add('show');
                    } else {
                        btnTop.classList.remove('show');
                    }
                }
            });
        });
    </script>

    <!-- Inyección de Scripts Específicos de la Vista (DataTables, FullCalendar, etc.) -->
    <?php if (isset($scriptsExtra)) echo $scriptsExtra; ?>
</body>
</html>
