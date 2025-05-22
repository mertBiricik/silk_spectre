    </main>
    <!--
    <footer class="bg-dracula-currentLine py-4 mt-8 rounded-t-lg">
        <div class="container mx-auto px-4 text-center text-dracula-comment">
            <p>&copy; <?php echo date('Y'); ?> Silk Spectre Polling System</p>
        </div>
    </footer>
    -->

    <!-- Page transition script -->
    <script>
        // Handle link clicks for page transitions
        document.addEventListener('DOMContentLoaded', () => {
            const isInternalLink = (href) => {
                return href && (
                    href.startsWith(window.location.origin) || 
                    (href.startsWith('/') && !href.startsWith('//')) ||
                    (!href.startsWith('http://') && !href.startsWith('https://') && !href.startsWith('//'))
                );
            };

            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (link && isInternalLink(link.href) && !link.target && !link.hasAttribute('download') && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    document.body.classList.add('opacity-0');
                    document.body.classList.remove('opacity-100');
                    
                    setTimeout(() => {
                        window.location.href = link.href;
                    }, 300); // Match the transition duration
                }
            });
        });
    </script>
</body>
</html> 