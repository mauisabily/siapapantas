</div>
                    <!-- End Page Content -->
                    
                    <!-- Footer -->
                    <footer class="bg-white border-top mt-auto py-3">
                        <div class="container-fluid">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <p class="mb-0 text-muted small">
                                        © <?php echo date('Y'); ?> Sistem Quiz Game. Semua hak terpelihara.
                                    </p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <p class="mb-0 text-muted small">
                                        <i class="bi bi-lightning-charge-fill text-warning me-1"></i>
                                        Powered by <strong class="text-primary">Sabily Enterprise</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </footer>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
                    setTimeout(function() {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }, 5000);
                }
            });
        });
        
        // Confirm delete actions
        function confirmDelete(message) {
            return confirm(message || 'Adakah anda pasti ingin memadam item ini?');
        }
        
        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(function(field) {
                        if (!field.value.trim()) {
                            field.classList.add('is-invalid');
                            isValid = false;
                        } else {
                            field.classList.remove('is-invalid');
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        alert('Sila lengkapkan semua medan yang diperlukan.');
                    }
                });
            }
        }
    </script>
    
    <?php if (isset($additional_js)): ?>
        <?php echo $additional_js; ?>
    <?php endif; ?>
    
</body>
</html>