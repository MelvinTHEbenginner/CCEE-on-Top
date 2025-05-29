    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Auto-hide flash messages
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessages = document.querySelectorAll('.flash-messages .alert');
            flashMessages.forEach(function(message) {
                setTimeout(function() {
                    message.remove();
                }, 5500);
            });
        });
    </script>
</body>
</html> 