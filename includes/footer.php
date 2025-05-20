<?php
// Verificar se o usuário está logado
$is_logged_in = isset($_SESSION['user_id']);

// Verificar se é a página de login
$is_login_page = basename($_SERVER['PHP_SELF']) === 'index.php' && !$is_logged_in;
?>

<?php if ($is_login_page): ?>
                        </div>
                        <div class="login-footer">
                            <p class="mb-0 small text-muted">
                                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($config['nome_aplicacao']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php if ($is_logged_in): ?>
            </div>
        </div>
        
        <!-- Mobile Sidebar Toggle Button -->
        <button class="btn btn-primary rounded-circle position-fixed d-lg-none" id="mobileSidebarToggle" style="bottom: 20px; right: 20px; width: 50px; height: 50px; z-index: 1030;">
            <i class="bi bi-list"></i>
        </button>
    <?php endif; ?>
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.getElementById('sidebarClose');
        const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                document.body.classList.toggle('sidebar-collapsed');
                
                // Save state to cookie
                const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=31536000`;
            });
        }
        
        if (sidebarClose) {
            sidebarClose.addEventListener('click', function() {
                document.body.classList.remove('sidebar-expanded');
            });
        }
        
        if (mobileSidebarToggle) {
            mobileSidebarToggle.addEventListener('click', function() {
                document.body.classList.toggle('sidebar-expanded');
            });
        }
        
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>

</body>
</html>
