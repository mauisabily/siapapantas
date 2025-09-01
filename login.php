<?php
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim($_POST['nickname'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    
    // Validation
    if (empty($nickname)) {
        $error = 'Nama panggilan adalah wajib!';
    } elseif (strlen($nickname) < 2) {
        $error = 'Nama panggilan mestilah sekurang-kurangnya 2 aksara!';
    } elseif (strlen($nickname) > 50) {
        $error = 'Nama panggilan tidak boleh melebihi 50 aksara!';
    } elseif (!empty($full_name) && strlen($full_name) > 100) {
        $error = 'Nama penuh tidak boleh melebihi 100 aksara!';
    } elseif (!empty($phone_number) && strlen($phone_number) > 20) {
        $error = 'Nombor telefon tidak boleh melebihi 20 aksara!';
    } else {
        // Store player data in session
        $_SESSION['nickname'] = htmlspecialchars($nickname);
        $_SESSION['full_name'] = htmlspecialchars($full_name);
        $_SESSION['phone_number'] = htmlspecialchars($phone_number);
        $_SESSION['current_question'] = 1;
        $_SESSION['start_time'] = time();
        
        // Save to database
        try {
            require_once 'db.php';
            
            // Check if nickname already exists
            $check_nickname = $conn->prepare("SELECT id FROM players WHERE nickname = ?");
            $check_nickname->bind_param("s", $nickname);
            $check_nickname->execute();
            $existing_nickname = $check_nickname->get_result()->fetch_assoc();
            $check_nickname->close();
            
            if ($existing_nickname) {
                $error = 'Nama panggilan sudah digunakan. Sila pilih nama lain.';
            } else {
                // Check if phone number already exists (if provided)
                if (!empty($phone_number)) {
                    $check_phone = $conn->prepare("SELECT id FROM players WHERE phone_number = ? AND phone_number != ''");
                    $check_phone->bind_param("s", $phone_number);
                    $check_phone->execute();
                    $existing_phone = $check_phone->get_result()->fetch_assoc();
                    $check_phone->close();
                    
                    if ($existing_phone) {
                        $error = 'Nombor telefon sudah digunakan. Sila gunakan nombor lain.';
                    }
                }
                
                // If no duplicates found, create new player
                if (empty($error)) {
                    $stmt = $conn->prepare("INSERT INTO players (nickname, full_name, phone_number, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->bind_param("sss", $nickname, $full_name, $phone_number);
                    $stmt->execute();
                    $_SESSION['player_id'] = $conn->insert_id;
                    $stmt->close();
                    
                    // Create randomized question sequence for new player
                    createRandomizedQuestionSequence($conn, $_SESSION['player_id']);
                    
                    header("Location: question.php");
                    exit();
                }
            }
        } catch (Exception $e) {
            $error = 'Ralat sistem. Sila cuba lagi.';
             error_log('Database error in login.php: ' . $e->getMessage());
         }
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pemain - Sistem Kuiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        .form-control {
            border-radius: 15px;
            border: 2px solid #e9ecef;
            padding: 12px 20px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        .game-icon {
            font-size: 4rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .welcome-text {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }
        @media (max-width: 576px) {
            .card {
                margin: 10px;
                border-radius: 15px;
            }
            .game-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid d-flex align-items-center justify-content-center min-vh-100 py-4">
        <div class="row w-100 justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                <div class="card">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-controller game-icon"></i>
                            <h1 class="welcome-text h2 mt-3 mb-2">Selamat Datang!</h1>
                            <p class="text-muted">Daftar untuk mula bermain kuiz</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="login.php" novalidate>
                            <div class="mb-4">
                                <label for="nickname" class="form-label">
                                    <i class="bi bi-person-fill me-2"></i>Nama Panggilan *
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="nickname" 
                                       name="nickname" 
                                       placeholder="Masukkan nama panggilan anda"
                                       value="<?php echo htmlspecialchars($_POST['nickname'] ?? ''); ?>"
                                       required 
                                       maxlength="50"
                                       autocomplete="nickname">
                                <div class="form-text">
                                    <small>Nama yang akan dipaparkan semasa permainan</small>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="full_name" class="form-label">
                                    <i class="bi bi-person-badge-fill me-2"></i>Nama Penuh (Pilihan)
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="full_name" 
                                       name="full_name" 
                                       placeholder="Masukkan nama penuh anda"
                                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                                       maxlength="100"
                                       autocomplete="name">
                                <div class="form-text">
                                    <small>Nama sebenar untuk rekod dan hadiah</small>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="phone_number" class="form-label">
                                    <i class="bi bi-telephone-fill me-2"></i>Nombor Telefon (Pilihan)
                                </label>
                                <input type="tel" 
                                       class="form-control form-control-lg" 
                                       id="phone_number" 
                                       name="phone_number" 
                                       placeholder="01X-XXXXXXX"
                                       value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>"
                                       maxlength="20"
                                       autocomplete="tel">
                                <div class="form-text">
                                    <small>Untuk dihubungi jika menang</small>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mb-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-play-circle-fill me-2"></i>
                                    Mula Bermain
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Dengan mendaftar, anda bersetuju dengan syarat dan terma permainan
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="winners.php" class="text-white text-decoration-none">
                        <i class="bi bi-trophy-fill me-2"></i>
                        Lihat Senarai Pemenang
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Phone number formatting
        document.getElementById('phone_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.substring(0, 3) + '-' + value.substring(3);
                } else {
                    value = value.substring(0, 3) + '-' + value.substring(3, 6) + value.substring(6, 11);
                }
            }
            e.target.value = value;
        });
    </script>
</body>
</html>
