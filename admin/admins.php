<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_admin':
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Validation
            if (empty($username) || empty($password) || empty($full_name)) {
                $error = 'Username, kata laluan dan nama penuh adalah wajib!';
            } else {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = 'Username sudah wujud!';
                } else {
                    // Hash password and insert
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, full_name, email, is_active) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([$username, $password_hash, $full_name, $email, $is_active])) {
                        $message = 'Admin berjaya ditambah!';
                    } else {
                        $error = 'Ralat menambah admin!';
                    }
                }
            }
            break;
            
        case 'edit_admin':
            $admin_id = $_POST['admin_id'];
            $username = trim($_POST['username']);
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $password = $_POST['password'] ?? '';
            
            // Validation
            if (empty($username) || empty($full_name)) {
                $error = 'Username dan nama penuh adalah wajib!';
            } else {
                // Check if username already exists for other admin
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
                $stmt->execute([$username, $admin_id]);
                if ($stmt->fetch()) {
                    $error = 'Username sudah wujud!';
                } else {
                    // Update admin
                    if (!empty($password)) {
                        // Update with new password
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE admins SET username = ?, password_hash = ?, full_name = ?, email = ?, is_active = ? WHERE id = ?");
                        $result = $stmt->execute([$username, $password_hash, $full_name, $email, $is_active, $admin_id]);
                    } else {
                        // Update without changing password
                        $stmt = $pdo->prepare("UPDATE admins SET username = ?, full_name = ?, email = ?, is_active = ? WHERE id = ?");
                        $result = $stmt->execute([$username, $full_name, $email, $is_active, $admin_id]);
                    }
                    
                    if ($result) {
                        $message = 'Admin berjaya dikemaskini!';
                    } else {
                        $error = 'Ralat mengemaskini admin!';
                    }
                }
            }
            break;
            
        case 'delete_admin':
            $admin_id = $_POST['admin_id'];
            
            // Prevent deleting self
            if ($admin_id == $_SESSION['admin_id']) {
                $error = 'Anda tidak boleh memadam akaun anda sendiri!';
            } else {
                $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
                if ($stmt->execute([$admin_id])) {
                    $message = 'Admin berjaya dipadam!';
                } else {
                    $error = 'Ralat memadam admin!';
                }
            }
            break;
    }
}

// Get all admins
$stmt = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC");
$admins = $stmt->fetchAll();

// Get total count
$stmt = $pdo->query("SELECT COUNT(*) as total FROM admins");
$total_admins = $stmt->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengurusan Admin - Sistem Kuiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-people-fill"></i> Pengurusan Admin</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                        <i class="bi bi-plus-circle"></i> Tambah Admin
                    </button>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Senarai Admin (<?php echo $total_admins; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Nama Penuh</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Log Masuk Terakhir</th>
                                        <th>Dicipta</th>
                                        <th>Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $admin): ?>
                                        <tr>
                                            <td><?php echo $admin['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                                <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                                    <span class="badge bg-info ms-1">Anda</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($admin['email'] ?? '-'); ?></td>
                                            <td>
                                                <?php if ($admin['is_active']): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Tidak Aktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $admin['last_login'] ? date('d/m/Y H:i', strtotime($admin['last_login'])) : '-'; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($admin['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="editAdmin(<?php echo $admin['id']; ?>, <?php echo json_encode($admin['username']); ?>, <?php echo json_encode($admin['full_name']); ?>, <?php echo json_encode($admin['email']); ?>, <?php echo $admin['is_active']; ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="deleteAdmin(<?php echo $admin['id']; ?>, <?php echo json_encode($admin['username']); ?>)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Admin Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Admin Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_admin">
                        
                        <div class="mb-3">
                            <label for="add_username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="add_username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="add_password" class="form-label">Kata Laluan *</label>
                            <input type="password" class="form-control" id="add_password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="add_full_name" class="form-label">Nama Penuh *</label>
                            <input type="text" class="form-control" id="add_full_name" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="add_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="add_email" name="email">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="add_is_active" name="is_active" checked>
                                <label class="form-check-label" for="add_is_active">
                                    Aktif
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tambah Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Admin Modal -->
    <div class="modal fade" id="editAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_admin">
                        <input type="hidden" id="edit_admin_id" name="admin_id">
                        
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Kata Laluan Baru</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                            <div class="form-text">Biarkan kosong jika tidak mahu mengubah kata laluan</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_full_name" class="form-label">Nama Penuh *</label>
                            <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    Aktif
                                </label>
                            </div>
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
    
    <!-- Delete Admin Modal -->
    <div class="modal fade" id="deleteAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Padam Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_admin">
                        <input type="hidden" id="delete_admin_id" name="admin_id">
                        
                        <p>Adakah anda pasti mahu memadam admin <strong id="delete_admin_name"></strong>?</p>
                        <p class="text-danger"><small>Tindakan ini tidak boleh dibatalkan.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Padam</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'layout/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editAdmin(id, username, fullName, email, isActive) {
            document.getElementById('edit_admin_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_full_name').value = fullName;
            document.getElementById('edit_email').value = email || '';
            document.getElementById('edit_is_active').checked = isActive == 1;
            document.getElementById('edit_password').value = '';
            
            new bootstrap.Modal(document.getElementById('editAdminModal')).show();
        }
        
        function deleteAdmin(id, username) {
            document.getElementById('delete_admin_id').value = id;
            document.getElementById('delete_admin_name').textContent = username;
            
            new bootstrap.Modal(document.getElementById('deleteAdminModal')).show();
        }
    </script>
</body>
</html>