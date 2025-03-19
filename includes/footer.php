    </main>
    <footer class="bg-dracula-currentLine py-6 mt-auto">
        <div class="container mx-auto px-4">
            <div class="flex flex-col sm:flex-row items-center justify-between">
                <div class="mb-4 sm:mb-0 text-center sm:text-left">
                    <p class="text-dracula-comment">&copy; <?php echo date('Y'); ?> Silk Spectre Polling System</p>
                </div>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="#" class="text-dracula-cyan hover:text-dracula-pink transition-colors duration-300">Privacy Policy</a>
                    <a href="#" class="text-dracula-cyan hover:text-dracula-pink transition-colors duration-300">Terms of Service</a>
                    <a href="#" class="text-dracula-cyan hover:text-dracula-pink transition-colors duration-300">Contact Us</a>
                </div>
            </div>
        </div>
    </footer>
    <script>
        // JavaScript for interactive elements can be added here
        document.addEventListener('DOMContentLoaded', function() {
            // Close alert messages when the close button is clicked
            const alerts = document.querySelectorAll('[role="alert"]');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('opacity-0', 'transition-opacity', 'duration-500');
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html> 