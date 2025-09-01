<?php
session_start();

// Semak jika admin sudah log masuk
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once '../config/database.php';

$page_title = 'Data Pemain';
$success_message = '';
$error_message = '';

// Proses tindakan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_player') {
        $player_id = $_POST['player_id'] ?? 0;
        try {
            // Padam pemain dan data berkaitan
            $pdo->beginTransaction();
            
            // Padam dari responses
            $stmt = $pdo->prepare("DELETE FROM responses WHERE player_id = ?");
            $stmt->execute([$player_id]);
            
            // Padam dari winners
            $stmt = $pdo->prepare("DELETE FROM winners WHERE player_id = ?");
            $stmt->execute([$player_id]);
            
            // Padam dari game_sessions
            $stmt = $pdo->prepare("DELETE FROM game_sessions WHERE player_id = ?");
            $stmt->execute([$player_id]);
            
            // Padam dari player_question_sequence
            $stmt = $pdo->prepare("DELETE FROM player_question_sequence WHERE player_id = ?");
            $stmt->execute([$player_id]);
            
            // Padam pemain
            $stmt = $pdo->prepare("DELETE FROM players WHERE id = ?");
            $stmt->execute([$player_id]);
            
            $pdo->commit();
            $success_message = 'Pemain dan semua data berkaitan berjaya dipadam.';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = 'Ralat memadam pemain: ' . $e->getMessage();
        }
    }
}

// Dapatkan senarai pemain
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.nickname LIKE ? OR p.full_name LIKE ? OR p.phone_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // Dapatkan jumlah pemain
    $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM players p $where_clause");
    $count_stmt->execute($params);
    $total_players = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_players / $limit);
    
    // Dapatkan pemain untuk halaman semasa dengan statistik
    $stmt = $pdo->prepare("
        SELECT p.*, 
               COUNT(DISTINCT gs.id) as total_games,
               COUNT(DISTINCT r.id) as total_responses,
               w.fastest_answers,
               w.total_correct_answers as winner_score
        FROM players p 
        LEFT JOIN game_sessions gs ON p.id = gs.player_id 
        LEFT JOIN responses r ON p.id = r.player_id 
        LEFT JOIN winners w ON p.id = w.player_id 
        $where_clause 
        GROUP BY p.id 
        ORDER BY p.created_at DESC 
        LIMIT $limit OFFSET $offset
     ");
    $stmt->execute($params);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = 'Ralat mendapatkan data: ' . $e->getMessage();
    $players = [];
}

include 'layout/header.php';
?>

<!-- Players Management Content -->
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="bi bi-people me-2 text-primary"></i>
                Data Pemain
            </h2>
            <div class="d-flex gap-2">
                <a href="../index.php" target="_blank" class="btn btn-outline-primary">
                    <i class="bi bi-eye me-2"></i>
                    Lihat Laman Utama
                </a>
                <button type="button" class="btn btn-success" onclick="exportData()">
                    <i class="bi bi-download me-2"></i>
                    Export Data
                </button>
            </div>
        </div>
    </div>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistik Ringkas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="small fw-bold text-primary text-uppercase mb-1">Jumlah Pemain</div>
                        <div class="h5 mb-0"><?php echo number_format($total_players); ?></div>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-people-fill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-start border-success border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="small fw-bold text-success text-uppercase mb-1">Pemain Aktif Hari Ini</div>
                        <div class="h5 mb-0">
                            <?php 
                            try {
                                $today_stmt = $pdo->query("SELECT COUNT(DISTINCT player_id) as count FROM game_sessions WHERE DATE(start_time) = CURDATE()");
                                echo number_format($today_stmt->fetch(PDO::FETCH_ASSOC)['count']);
                            } catch (PDOException $e) {
                                echo '0';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-person-check-fill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="small fw-bold text-info text-uppercase mb-1">Pemain Minggu Ini</div>
                        <div class="h5 mb-0">
                            <?php 
                            try {
                                $week_stmt = $pdo->query("SELECT COUNT(DISTINCT player_id) as count FROM game_sessions WHERE WEEK(start_time) = WEEK(NOW()) AND YEAR(start_time) = YEAR(NOW())");
                                echo number_format($week_stmt->fetch(PDO::FETCH_ASSOC)['count']);
                            } catch (PDOException $e) {
                                echo '0';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-calendar-week-fill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="small fw-bold text-warning text-uppercase mb-1">Pemenang</div>
                        <div class="h5 mb-0">
                            <?php 
                            try {
                                $winners_stmt = $pdo->query("SELECT COUNT(*) as count FROM winners");
                                echo number_format($winners_stmt->fetch(PDO::FETCH_ASSOC)['count']);
                            } catch (PDOException $e) {
                                echo '0';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-trophy-fill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter dan Carian -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <label for="search" class="form-label">Cari Pemain</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Cari berdasarkan nickname, nama penuh, atau nombor telefon...">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search me-1"></i>
                        Cari
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Senarai Pemain -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0">
            <i class="bi bi-list-ul me-2"></i>
            Senarai Pemain (<?php echo number_format($total_players); ?> pemain)
        </h6>
    </div>
    <div class="card-body">
        <?php if (empty($players)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox display-6"></i>
                <p class="mt-3 mb-0">Tiada pemain dijumpai</p>
                <?php if (empty($search)): ?>
                    <p class="text-muted">Pemain akan muncul di sini setelah mereka mendaftar dan bermain.</p>
                    <a href="../index.php" target="_blank" class="btn btn-outline-primary mt-2">
                        <i class="bi bi-eye me-2"></i>
                        Lihat Laman Utama
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Pemain</th>
                            <th>Maklumat Hubungan</th>
                            <th>Statistik Permainan</th>
                            <th>Prestasi</th>
                            <th>Tarikh Daftar</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($players as $player): ?>
                            <tr>
                                <td><?php echo $player['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <?php echo strtoupper(substr($player['nickname'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($player['nickname']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($player['full_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <i class="bi bi-telephone me-1"></i>
                                        <?php echo htmlspecialchars($player['phone_number']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div><i class="bi bi-controller me-1"></i> <?php echo $player['total_games']; ?> permainan</div>
                                        <div><i class="bi bi-chat-square-text me-1"></i> <?php echo $player['total_responses']; ?> jawapan</div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($player['fastest_answers']): ?>
                                        <div class="small">
                                            <div class="text-success">
                                                <i class="bi bi-trophy me-1"></i>
                                                <?php echo $player['fastest_answers']; ?>s
                                            </div>
                                            <div class="text-muted">
                                                <?php echo $player['winner_score']; ?> mata
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Belum menang</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($player['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-info" 
                                                onclick="viewPlayer(<?php echo htmlspecialchars(json_encode($player)); ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="viewPlayerHistory(<?php echo $player['id']; ?>)">
                                            <i class="bi bi-clock-history"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deletePlayer(<?php echo $player['id']; ?>, '<?php echo htmlspecialchars($player['nickname']); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Pagination">
                    <ul class="pagination justify-content-center mt-4">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Lihat Pemain -->
<div class="modal fade" id="viewPlayerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person me-2"></i>
                    Maklumat Pemain
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewPlayerContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sejarah Pemain -->
<div class="modal fade" id="playerHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-clock-history me-2"></i>
                    Sejarah Permainan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="playerHistoryContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewPlayer(player) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Maklumat Peribadi</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>ID Pemain:</strong></div>
                            <div class="col-sm-8">${player.player_id}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Nickname:</strong></div>
                            <div class="col-sm-8">${player.nickname}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Nama Penuh:</strong></div>
                            <div class="col-sm-8">${player.full_name}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Telefon:</strong></div>
                            <div class="col-sm-8">${player.phone_number}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Tarikh Daftar:</strong></div>
                            <div class="col-sm-8">${new Date(player.created_at).toLocaleDateString('ms-MY')}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Statistik Permainan</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-6"><strong>Jumlah Permainan:</strong></div>
                            <div class="col-sm-6"><span class="badge bg-primary">${player.total_games}</span></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6"><strong>Jumlah Jawapan:</strong></div>
                            <div class="col-sm-6"><span class="badge bg-info">${player.total_responses}</span></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6"><strong>Masa Terpantas:</strong></div>
                            <div class="col-sm-6">
                                ${player.fastest_answers ? 
                                    `<span class="badge bg-success">${player.fastest_answers}s</span>` : 
                                    '<span class="text-muted">Belum ada</span>'
                                }
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6"><strong>Skor Tertinggi:</strong></div>
                            <div class="col-sm-6">
                                ${player.winner_score ? 
                                    `<span class="badge bg-warning">${player.winner_score} mata</span>` : 
                                    '<span class="text-muted">Belum ada</span>'
                                }
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('viewPlayerContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('viewPlayerModal')).show();
}

function viewPlayerHistory(playerId) {
    new bootstrap.Modal(document.getElementById('playerHistoryModal')).show();
    
    // Load player history via AJAX
    fetch(`get_player_history.php?player_id=${playerId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('playerHistoryContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('playerHistoryContent').innerHTML = 
                '<div class="alert alert-danger">Ralat memuat sejarah pemain.</div>';
        });
}

function deletePlayer(playerId, nickname) {
    if (confirm(`Adakah anda pasti ingin memadam pemain "${nickname}"?\n\nTindakan ini akan memadam semua data berkaitan termasuk sejarah permainan, jawapan, dan rekod kemenangan.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_player">
            <input type="hidden" name="player_id" value="${playerId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function exportData() {
    // Simple CSV export
    window.open('export_players.php', '_blank');
}
</script>

<?php include 'layout/footer.php'; ?>