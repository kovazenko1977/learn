</main>

<?php if (\Medical\Core\Auth::isLoggedIn()):
    $currentPage = basename($_SERVER['PHP_SELF']);
?>
    <nav class="md-bottom-nav">
        <a href="index.php" class="nav-item <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
            <div class="nav-icon-container"><i data-lucide="home"></i></div>
            <span>Главная</span>
        </a>
        <a href="patients.php" class="nav-item <?php echo $currentPage === 'patients.php' ? 'active' : ''; ?>">
            <div class="nav-icon-container"><i data-lucide="users"></i></div>
            <span>Пациенты</span>
        </a>
        <a href="attendance.php" class="nav-item <?php echo $currentPage === 'attendance.php' ? 'active' : ''; ?>">
            <div class="nav-icon-container"><i data-lucide="check-square"></i></div>
            <span>Прием</span>
        </a>
    </nav>
<?php endif; ?>

<script>
    lucide.createIcons();
</script>
</body>
</html>
