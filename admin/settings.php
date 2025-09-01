<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_setting':
                $setting_key = $_POST['setting_key'];
                $setting_value = $_POST['setting_value'];
                
                $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW(), updated_by = ? WHERE setting_key = ?");
                if ($stmt->execute([$setting_value, $_SESSION['admin_id'], $setting_key])) {
                    $message = 'Tetapan berjaya dikemaskini!';
                    $message_type = 'success';
                } else {
                    $message = 'Ralat semasa mengemaskini tetapan.';
                    $message_type = 'danger';
                }
                break;
                
            case 'add_setting':
                $setting_key = $_POST['new_setting_key'];
                $setting_value = $_POST['new_setting_value'];
                $description = $_POST['new_description'];
                
                $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, description, created_at, updated_at, updated_by) VALUES (?, ?, ?, NOW(), NOW(), ?)");
                if ($stmt->execute([$setting_key, $setting_value, $description, $_SESSION['admin_id']])) {
                    $message = 'Tetapan baharu berjaya ditambah!';
                    $message_type = 'success';
                } else {
                    $message = 'Ralat semasa menambah tetapan baharu.';
                    $message_type = 'danger';
                }
                break;
                
            case 'delete_setting':
                $setting_id = $_POST['setting_id'];
    
    $stmt = $pdo->prepare("DELETE FROM system_settings WHERE id = ?");
                if ($stmt->execute([$setting_id])) {
                    $message = 'Tetapan berjaya dipadam!';
                    $message_type = 'success';
                } else {
                    $message = 'Ralat semasa memadam tetapan.';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get all settings
$stmt = $pdo->query("SELECT s.*, a.username as updated_by_username FROM system_settings s LEFT JOIN admins a ON s.updated_by = a.id ORDER BY s.setting_key");
$settings = $stmt->fetchAll();

// Get system statistics
$stats = [];

// Total questions
$stmt = $pdo->query("SELECT COUNT(*) as total FROM questions WHERE is_active = 1");
$stats['total_questions'] = $stmt->fetch()['total'];

// Total players
$stmt = $pdo->query("SELECT COUNT(*) as total FROM players");
$stats['total_players'] = $stmt->fetch()['total'];

// Total game sessions
$stmt = $pdo->query("SELECT COUNT(*) as total FROM game_sessions");
$stats['total_sessions'] = $stmt->fetch()['total'];

// Database size
$stmt = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
$stats['db_size'] = $stmt->fetch()['size_mb'];

include 'layout/header.php';
?>

        <main class="container-fluid">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Tetapan Sistem</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSettingModal">
                        <i class="bi bi-plus-circle"></i> Tambah Tetapan
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- System Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-question-circle text-primary" style="font-size: 2rem;"></i>
                            <h5 class="card-title mt-2"><?php echo number_format($stats['total_questions']); ?></h5>
                            <p class="card-text text-muted">Jumlah Soalan</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-people text-success" style="font-size: 2rem;"></i>
                            <h5 class="card-title mt-2"><?php echo number_format($stats['total_players']); ?></h5>
                            <p class="card-text text-muted">Jumlah Pemain</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-controller text-warning" style="font-size: 2rem;"></i>
                            <h5 class="card-title mt-2"><?php echo number_format($stats['total_sessions']); ?></h5>
                            <p class="card-text text-muted">Sesi Permainan</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-hdd text-info" style="font-size: 2rem;"></i>
                            <h5 class="card-title mt-2"><?php echo $stats['db_size']; ?> MB</h5>
                            <p class="card-text text-muted">Saiz Pangkalan Data</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Senarai Tetapan Sistem</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Kunci Tetapan</th>
                                    <th>Nilai</th>
                                    <th>Keterangan</th>
                                    <th>Dikemaskini Oleh</th>
                                    <th>Tarikh Kemaskini</th>
                                    <th>Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($settings as $setting): ?>
                                    <tr>
                                        <td>
                                            <code><?php echo htmlspecialchars($setting['setting_key']); ?></code>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($setting['setting_value']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($setting['description']); ?>
                                        </td>
                                        <td>
                                            <?php echo $setting['updated_by_username'] ? htmlspecialchars($setting['updated_by_username']) : '-'; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo $setting['updated_at'] ? date('d/m/Y H:i', strtotime($setting['updated_at'])) : '-'; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="editSetting(<?php echo json_encode($setting['setting_key']); ?>, <?php echo json_encode($setting['setting_value']); ?>, <?php echo json_encode($setting['description']); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteSetting(<?php echo $setting['id']; ?>, <?php echo json_encode($setting['setting_key']); ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

<!-- Modal Tambah Tetapan -->
<div class="modal fade" id="addSettingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Tetapan Baharu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_setting">
                    <div class="mb-3">
                        <label for="new_setting_key" class="form-label">Kunci Tetapan</label>
                        <input type="text" class="form-control" id="new_setting_key" name="new_setting_key" required>
                        <div class="form-text">Gunakan format seperti: game.time_limit, system.max_players</div>
                    </div>
                    <div class="mb-3">
                        <label for="new_setting_value" class="form-label">Nilai</label>
                        <input type="text" class="form-control" id="new_setting_value" name="new_setting_value" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_description" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="new_description" name="new_description" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Tetapan -->
<div class="modal fade" id="editSettingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Tetapan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_setting">
                    <input type="hidden" id="edit_setting_key" name="setting_key">
                    <div class="mb-3">
                        <label for="edit_setting_key_display" class="form-label">Kunci Tetapan</label>
                        <input type="text" class="form-control" id="edit_setting_key_display" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="edit_setting_value" class="form-label">Nilai</label>
                        <input type="text" class="form-control" id="edit_setting_value" name="setting_value" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description_display" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="edit_description_display" rows="3" readonly></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Kemaskini</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Padam Tetapan -->
<div class="modal fade" id="deleteSettingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Padam Tetapan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_setting">
                    <input type="hidden" id="delete_setting_id" name="setting_id">
                    <p>Adakah anda pasti ingin memadam tetapan <strong id="delete_setting_name"></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Tindakan ini tidak boleh dibatalkan!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Padam</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editSetting(key, value, description) {
    document.getElementById('edit_setting_key').value = key;
    document.getElementById('edit_setting_key_display').value = key;
    document.getElementById('edit_setting_value').value = value;
    document.getElementById('edit_description_display').value = description;
    
    var modal = new bootstrap.Modal(document.getElementById('editSettingModal'));
    modal.show();
}

function deleteSetting(settingId, settingKey) {
    document.getElementById('delete_setting_id').value = settingId;
    document.getElementById('delete_setting_name').textContent = settingKey;
    
    var modal = new bootstrap.Modal(document.getElementById('deleteSettingModal'));
    modal.show();
}
</script>

<?php include 'layout/footer.php'; ?>