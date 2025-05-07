    </main>
    
    <footer class="bg-dracula-currentLine py-6 mt-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p class="text-dracula-comment text-sm">&copy; <?php echo date('Y'); ?> Poll System Admin. All rights reserved.</p>
                </div>
                <div class="flex space-x-4">
                    <a href="index.php" class="text-dracula-comment hover:text-dracula-purple transition-colors text-sm">Dashboard</a>
                    <a href="../index.php" class="text-dracula-comment hover:text-dracula-purple transition-colors text-sm">View Site</a>
                    <a href="logout.php" class="text-dracula-comment hover:text-dracula-red transition-colors text-sm">Logout</a>
                </div>
            </div>
        </div>
    </footer>
    <script>
        // JavaScript for interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Close alert messages after a delay
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