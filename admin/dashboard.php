<?php
session_start();

// Semak jika admin sudah log masuk
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once '../config/database.php';

$page_title = 'Dashboard';

try {
    // Dapatkan statistik sistem
    $stats = [];
    
    // Jumlah soalan
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM questions WHERE is_active = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_questions'] = $result ? $result['total'] : 0;
    
    // Jumlah pemain
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM players");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_players'] = $result ? $result['total'] : 0;
    
    // Jumlah sesi permainan
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM game_sessions");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_sessions'] = $result ? $result['total'] : 0;
    
    // Jumlah jawapan
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM responses");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_responses'] = $result ? $result['total'] : 0;
    
    // Pemain terkini (5 terbaru)
    $stmt = $pdo->query("SELECT nickname, full_name, created_at FROM players ORDER BY created_at DESC LIMIT 5");
    $recent_players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sesi permainan terkini
    $stmt = $pdo->query("
        SELECT gs.id as session_id, p.nickname, gs.start_time, gs.end_time, gs.correct_answers 
        FROM game_sessions gs 
        JOIN players p ON gs.player_id = p.id 
        ORDER BY gs.start_time DESC 
        LIMIT 5
    ");
    $recent_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top 5 pemain
    $stmt = $pdo->query("
        SELECT p.nickname, p.full_name, w.fastest_answers, w.total_correct_answers 
        FROM winners w 
        JOIN players p ON w.player_id = p.id 
        ORDER BY w.fastest_answers ASC, w.total_correct_answers DESC 
        LIMIT 5
    ");
    $top_players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = 'Ralat mendapatkan data: ' . $e->getMessage();
    // Set default values if there's an error
    $stats = [
        'total_questions' => 0,
        'total_players' => 0,
        'total_sessions' => 0,
        'total_responses' => 0
    ];
    $recent_players = [];
    $recent_sessions = [];
    $top_players = [];
}

include 'layout/header.php';
?>

<!-- Dashboard Content -->
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="bi bi-speedometer2 me-2 text-primary"></i>
                Dashboard
            </h2>
            <div class="text-muted">
                <i class="bi bi-clock me-1"></i>
                <?php echo date('d/m/Y H:i:s'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Statistik Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="small fw-bold text-primary text-uppercase mb-1">Jumlah Soalan</div>
                        <div class="h5 mb-0"><?php echo number_format($stats['total_questions']); ?></div>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-question-circle-fill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-start border-success border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="small fw-bold text-success text-uppercase mb-1">Jumlah Pemain</div>
                        <div class="h5 mb-0"><?php echo number_format($stats['total_players']); ?></div>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-people-fill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="small fw-bold text-info text-uppercase mb-1">Sesi Permainan</div>
                        <div class="h5 mb-0"><?php echo number_format($stats['total_sessions']); ?></div>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-controller fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="small fw-bold text-warning text-uppercase mb-1">Jumlah Jawapan</div>
                        <div class="h5 mb-0"><?php echo number_format($stats['total_responses']); ?></div>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-chat-square-text-fill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content Row -->
<div class="row">
    <!-- Pemain Terkini -->
    <div class="col-xl-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">
                    <i class="bi bi-person-plus me-2"></i>
                    Pemain Terkini
                </h6>
                <a href="players.php" class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_players)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-inbox display-6"></i>
                        <p class="mt-2 mb-0">Tiada pemain lagi</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_players as $player): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-3">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <?php echo strtoupper(substr($player['nickname'], 0, 1)); ?>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?php echo htmlspecialchars($player['nickname']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($player['full_name']); ?></div>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo date('d/m/Y H:i', strtotime($player['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Top Pemain -->
    <div class="col-xl-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">
                    <i class="bi bi-trophy me-2"></i>
                    Top Pemain
                </h6>
                <a href="../winners.php" target="_blank" class="btn btn-sm btn-outline-primary">
                    Lihat Papan Pendahulu
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($top_players)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-trophy display-6"></i>
                        <p class="mt-2 mb-0">Tiada pemenang lagi</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($top_players as $index => $player): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <?php 
                                        $badge_class = ['bg-warning', 'bg-secondary', 'bg-warning'];
                                        $icons = ['trophy', 'award', 'award'];
                                        $class = $badge_class[$index] ?? 'bg-primary';
                                        $icon = $icons[$index] ?? 'star';
                                        ?>
                                        <span class="badge <?php echo $class; ?> rounded-pill">
                                            <i class="bi bi-<?php echo $icon; ?>"></i> <?php echo $index + 1; ?>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?php echo htmlspecialchars($player['nickname']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($player['full_name']); ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success"><?php echo $player['fastest_answers']; ?>s</div>
                                        <div class="text-muted small"><?php echo $player['total_score']; ?> mata</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Sesi Permainan Terkini -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">
                    <i class="bi bi-clock-history me-2"></i>
                    Sesi Permainan Terkini
                </h6>
                <a href="game-sessions.php" class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_sessions)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-controller display-6"></i>
                        <p class="mt-2 mb-0">Tiada sesi permainan lagi</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID Sesi</th>
                                    <th>Pemain</th>
                                    <th>Masa Mula</th>
                                    <th>Masa Tamat</th>
                                    <th>Skor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_sessions as $session): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($session['session_id']); ?></code></td>
                                        <td><?php echo htmlspecialchars($session['nickname']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($session['start_time'])); ?></td>
                                        <td>
                                            <?php if ($session['end_time']): ?>
                                                <?php echo date('d/m/Y H:i', strtotime($session['end_time'])); ?>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Sedang Bermain</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($session['total_score'] !== null): ?>
                                                <span class="badge bg-success"><?php echo $session['total_score']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($session['end_time']): ?>
                                                <span class="badge bg-success">Selesai</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>