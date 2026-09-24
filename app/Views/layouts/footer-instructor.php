        </div> <!-- End container-fluid -->
    </div> <!-- End all-content-wrapper -->

    <!-- Back to Top Button -->
    <a id="btnScrollTop" class="sena-btn-scroll-top" href="#" onclick="window.scrollTo({top:0, behavior:'smooth'}); return false;">
        <i class="bi bi-arrow-up"></i>
    </a>

    <!-- Scripts -->
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SimpleBar for Custom Scrollbars -->
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@5.3.6/dist/apexcharts.min.js"></script>
    <!-- Original Main JS -->
    <script src="<?= BASE_URL ?>/public/assets/js/main.js"></script>
    <!-- Custom UI/UX Script for Aprendiz/Instructor -->
    <script src="<?= BASE_URL ?>/public/assets/js/aprendiz-custom.js"></script>

    <!-- UI/UX Clock & Scroll Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            

            // Mobile Sidebar Close Button Listener
            var closeBtnMobile = document.getElementById('sidebarCloseMobile');
            if (closeBtnMobile) {
                closeBtnMobile.addEventListener('click', function () {
                    document.body.classList.remove('sidebar-mobile-open');
                });
            }

            // Scroll Progress Bar & Scroll To Top
            window.addEventListener('scroll', function() {
                var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
                var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                var scrolled = (winScroll / height) * 100;
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

    <?php if (isset($scriptsExtra)) echo $scriptsExtra; ?>
</body>
</html>

