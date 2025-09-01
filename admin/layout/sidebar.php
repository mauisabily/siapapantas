<?php
// Dapatkan nama fail semasa untuk menentukan menu aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="nav flex-column py-3">
    <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
        <i class="bi bi-speedometer2 me-2"></i>
        Dashboard
    </a>
    
    <a class="nav-link <?php echo ($current_page == 'questions.php') ? 'active' : ''; ?>" href="questions.php">
        <i class="bi bi-question-circle me-2"></i>
        Pengurusan Soalan
    </a>
    
    <a class="nav-link <?php echo ($current_page == 'players.php') ? 'active' : ''; ?>" href="players.php">
        <i class="bi bi-people me-2"></i>
        Data Pemain
    </a>
    
    <a class="nav-link <?php echo ($current_page == 'game-sessions.php') ? 'active' : ''; ?>" href="game-sessions.php">
        <i class="bi bi-controller me-2"></i>
        Sesi Permainan
    </a>
    
    <a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>" href="reports.php">
        <i class="bi bi-graph-up me-2"></i>
        Laporan
    </a>
    
    <hr class="border-light mx-3">
    
    <a class="nav-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>" href="settings.php">
        <i class="bi bi-gear me-2"></i>
        Tetapan Sistem
    </a>
    
    <a class="nav-link <?php echo ($current_page == 'admins.php') ? 'active' : ''; ?>" href="admins.php">
        <i class="bi bi-shield-lock me-2"></i>
        Pengurusan Admin
    </a>
    
    <hr class="border-light mx-3">
    
    <a class="nav-link" href="../index.php" target="_blank">
        <i class="bi bi-eye me-2"></i>
        Lihat Laman Utama
    </a>
    
    <a class="nav-link text-warning" href="logout.php">
        <i class="bi bi-box-arrow-right me-2"></i>
        Log Keluar
    </a>
</nav>