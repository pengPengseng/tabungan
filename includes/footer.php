    </main>

    <!-- Footer Banner -->
    <footer class="mt-auto border-t border-outline-variant py-4 px-6 text-center text-xs text-on-surface-variant bg-surface-container-lowest">
        <p>&copy; <?= date('Y'); ?> <strong>Keuangan App</strong> — Website Pencatatan Keuangan Bulanan & Usaha.</p>
    </footer>
</div>

<script>
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('mobileSidebar');
        if (sidebar.classList.contains('hidden')) {
            sidebar.classList.remove('hidden');
        } else {
            sidebar.classList.add('hidden');
        }
    }
</script>

<script src="assets/js/script.js"></script>
</body>
</html>
