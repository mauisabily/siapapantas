<?php
session_start();
include 'db.php';

// Get overall statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT nickname) as total_players,
    COUNT(*) as total_responses,
    AVG(response_time) as avg_response_time,
    COUNT(CASE WHEN is_correct = 1 THEN 1 END) as correct_answers
    FROM responses";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get top players by fastest answers
$fastest_sql = "SELECT 
    w.nickname, 
    w.fastest_answers,
    p.full_name,
    p.phone_number,
    COUNT(r.id) as total_questions,
    COUNT(CASE WHEN r.is_correct = 1 THEN 1 END) as correct_count,
    AVG(r.response_time) as avg_time
    FROM winners w 
    LEFT JOIN players p ON w.nickname = p.nickname
    LEFT JOIN responses r ON w.nickname = r.nickname
    WHERE w.fastest_answers > 0
    GROUP BY w.nickname
    ORDER BY w.fastest_answers DESC, avg_time ASC 
    LIMIT 10";
$fastest_result = $conn->query($fastest_sql);

// Get top players by accuracy
$accuracy_sql = "SELECT 
    r.nickname,
    p.full_name,
    COUNT(*) as total_answered,
    COUNT(CASE WHEN r.is_correct = 1 THEN 1 END) as correct_answers,
    ROUND((COUNT(CASE WHEN r.is_correct = 1 THEN 1 END) / COUNT(*)) * 100, 1) as accuracy_percentage,
    AVG(r.response_time) as avg_response_time
    FROM responses r
    LEFT JOIN players p ON r.nickname = p.nickname
    GROUP BY r.nickname
    HAVING total_answered >= 5
    ORDER BY accuracy_percentage DESC, avg_response_time ASC
    LIMIT 10";
$accuracy_result = $conn->query($accuracy_sql);

// Get recent activity
$recent_sql = "SELECT 
    r.nickname,
    r.question_id,
    r.is_correct,
    r.response_time,
    r.created_at
    FROM responses r
    ORDER BY r.created_at DESC
    LIMIT 20";
$recent_result = $conn->query($recent_sql);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papan Pemenang - Sistem Kuiz</title>
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
            margin-bottom: 20px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        .winner-card {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            color: #333;
            position: relative;
            overflow: hidden;
        }
        .winner-card.first {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            transform: scale(1.05);
        }
        .winner-card.second {
            background: linear-gradient(45deg, #c0c0c0, #e8e8e8);
        }
        .winner-card.third {
            background: linear-gradient(45deg, #cd7f32, #daa520);
        }
        .stats-card {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .trophy-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .rank-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #333;
        }
        .activity-item {
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 8px;
            background: rgba(248, 249, 250, 0.8);
        }
        .activity-item.correct {
            border-left: 4px solid #28a745;
        }
        .activity-item.wrong {
            border-left: 4px solid #dc3545;
        }
        .header-title {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="text-center mb-4">
            <h1 class="header-title display-4 mb-2">
                <i class="bi bi-trophy-fill text-warning"></i>
                Papan Pemenang
            </h1>
            <p class="text-white">Sistem Kuiz - Keputusan dan Statistik</p>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['total_players']); ?></div>
                    <div class="stats-label">Jumlah Pemain</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['total_responses']); ?></div>
                    <div class="stats-label">Soalan Dijawab</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['correct_answers']); ?></div>
                    <div class="stats-label">Jawapan Betul</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['avg_response_time'], 1); ?>s</div>
                    <div class="stats-label">Purata Masa</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Top Fastest Players -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">
                            <i class="bi bi-lightning-fill text-warning"></i>
                            Top 10 Terpantas
                        </h5>
                        <small class="text-muted">Berdasarkan bilangan jawapan terpantas</small>
                    </div>
                    <div class="card-body">
                        <?php 
                        $rank = 1;
                        while ($row = $fastest_result->fetch_assoc()): 
                            $rank_class = '';
                            $trophy = '';
                            if ($rank == 1) { $rank_class = 'first'; $trophy = '🥇'; }
                            elseif ($rank == 2) { $rank_class = 'second'; $trophy = '🥈'; }
                            elseif ($rank == 3) { $rank_class = 'third'; $trophy = '🥉'; }
                            
                            $accuracy = $row['total_questions'] > 0 ? round(($row['correct_count'] / $row['total_questions']) * 100, 1) : 0;
                        ?>
                            <div class="winner-card <?php echo $rank_class; ?>">
                                <div class="rank-badge"><?php echo $rank; ?></div>
                                <div class="d-flex align-items-center">
                                    <div class="trophy-icon me-3"><?php echo $trophy ?: '🏆'; ?></div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($row['nickname']); ?></h6>
                                        <?php if ($row['full_name']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['full_name']); ?></small><br>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span><strong><?php echo $row['fastest_answers']; ?></strong> jawapan terpantas</span>
                                            <span><?php echo $accuracy; ?>% ketepatan</span>
                                        </div>
                                        <small class="text-muted">Purata: <?php echo number_format($row['avg_time'], 2); ?>s</small>
                                    </div>
                                </div>
                            </div>
                        <?php 
                        $rank++;
                        endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Top Accuracy Players -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">
                            <i class="bi bi-bullseye text-success"></i>
                            Top 10 Ketepatan
                        </h5>
                        <small class="text-muted">Berdasarkan peratusan jawapan betul (min. 5 soalan)</small>
                    </div>
                    <div class="card-body">
                        <?php 
                        $rank = 1;
                        while ($row = $accuracy_result->fetch_assoc()): 
                            $rank_class = '';
                            $trophy = '';
                            if ($rank == 1) { $rank_class = 'first'; $trophy = '🥇'; }
                            elseif ($rank == 2) { $rank_class = 'second'; $trophy = '🥈'; }
                            elseif ($rank == 3) { $rank_class = 'third'; $trophy = '🥉'; }
                        ?>
                            <div class="winner-card <?php echo $rank_class; ?>">
                                <div class="rank-badge"><?php echo $rank; ?></div>
                                <div class="d-flex align-items-center">
                                    <div class="trophy-icon me-3"><?php echo $trophy ?: '🎯'; ?></div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($row['nickname']); ?></h6>
                                        <?php if ($row['full_name']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['full_name']); ?></small><br>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span><strong><?php echo $row['accuracy_percentage']; ?>%</strong> ketepatan</span>
                                            <span><?php echo $row['correct_answers']; ?>/<?php echo $row['total_answered']; ?> betul</span>
                                        </div>
                                        <small class="text-muted">Purata: <?php echo number_format($row['avg_response_time'], 2); ?>s</small>
                                    </div>
                                </div>
                            </div>
                        <?php 
                        $rank++;
                        endwhile; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card mt-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history text-info"></i>
                    Aktiviti Terkini
                </h5>
                <small class="text-muted">20 jawapan terbaru</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php while ($row = $recent_result->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="activity-item <?php echo $row['is_correct'] ? 'correct' : 'wrong'; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['nickname']); ?></strong>
                                        <div class="small text-muted">Soalan <?php echo $row['question_id']; ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="<?php echo $row['is_correct'] ? 'text-success' : 'text-danger'; ?>">
                                            <i class="bi bi-<?php echo $row['is_correct'] ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
                                        </div>
                                        <small class="text-muted"><?php echo number_format($row['response_time'], 1); ?>s</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="text-center mt-4">
            <a href="login.php" class="btn btn-primary btn-lg me-3">
                <i class="bi bi-play-circle-fill me-2"></i>
                Main Semula
            </a>
            <a href="question.php" class="btn btn-outline-light btn-lg me-3">
                <i class="bi bi-arrow-left-circle me-2"></i>
                Kembali ke Kuiz
            </a>
            <a href="logout.php" class="btn btn-outline-light btn-lg">
                <i class="bi bi-box-arrow-right me-2"></i>
                Keluar
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto refresh every 30 seconds
        setTimeout(() => {
            window.location.reload();
        }, 30000);
        
        // Smooth scroll animations
        window.addEventListener('load', function() {
            const cards = document.querySelectorAll('.winner-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>
</body>
</html>
