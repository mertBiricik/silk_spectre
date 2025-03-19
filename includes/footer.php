    </main>
    <footer class="bg-dracula-currentLine text-dracula-foreground">
        <div class="container-custom py-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p>&copy; <?php echo date('Y'); ?> Silk Spectre. All rights reserved.</p>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="hover:text-dracula-cyan">About</a>
                    <a href="#" class="hover:text-dracula-cyan">Privacy</a>
                    <a href="#" class="hover:text-dracula-cyan">Terms</a>
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