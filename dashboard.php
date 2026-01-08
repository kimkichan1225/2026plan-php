<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Goal.php';

requireLogin();

$userId = getCurrentUserId();
$userName = getCurrentUserName();
$currentYear = date('Y');

$goalModel = new Goal();

// 전체 목표 조회
$goals = $goalModel->findByUser($userId, $currentYear);

// 상태별 집계
$statusCounts = $goalModel->countByStatus($userId);
$totalGoals = count($goals);
$completedGoals = $statusCounts['completed'] ?? 0;
$inProgressGoals = $statusCounts['in_progress'] ?? 0;
$notStartedGoals = $statusCounts['not_started'] ?? 0;

// 카테고리별 집계
$categoryCounts = $goalModel->countByCategory($userId);

// 평균 진행률 계산
$totalProgress = 0;
foreach ($goals as $goal) {
    $totalProgress += $goal['progress_percentage'];
}
$avgProgress = $totalGoals > 0 ? round($totalProgress / $totalGoals, 2) : 0;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>대시보드 - 신년계획 관리</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="container">
        <!-- 헤더 -->
        <header class="header">
            <div class="header-content">
                <h1 class="logo">신년계획 관리</h1>
                <nav class="nav">
                    <a href="dashboard.php" class="nav-link active">대시보드</a>
                    <a href="goal_list.php" class="nav-link">목표 관리</a>
                    <a href="reflection.php" class="nav-link">회고</a>
                    <span class="user-info">안녕하세요, <?= e($userName) ?>님</span>
                    <a href="logout.php" class="btn btn-sm btn-secondary">로그아웃</a>
                </nav>
            </div>
        </header>

        <!-- 메인 컨텐츠 -->
        <main class="main-content">
            <div class="page-header">
                <h2><?= $currentYear ?>년 목표 대시보드</h2>
                <a href="goal_list.php?action=create" class="btn btn-primary">새 목표 추가</a>
            </div>

            <!-- 통계 카드 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-total">📊</div>
                    <div class="stat-content">
                        <h3>전체 목표</h3>
                        <p class="stat-number"><?= $totalGoals ?>개</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-complete">✅</div>
                    <div class="stat-content">
                        <h3>완료된 목표</h3>
                        <p class="stat-number"><?= $completedGoals ?>개</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-progress">🚀</div>
                    <div class="stat-content">
                        <h3>진행 중</h3>
                        <p class="stat-number"><?= $inProgressGoals ?>개</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-avg">📈</div>
                    <div class="stat-content">
                        <h3>평균 진행률</h3>
                        <p class="stat-number"><?= $avgProgress ?>%</p>
                    </div>
                </div>
            </div>

            <!-- 차트 섹션 -->
            <?php if (!empty($goals)): ?>
            <div class="charts-grid">
                <!-- 카테고리별 분포 차트 -->
                <div class="chart-card">
                    <h3>카테고리별 목표 분포</h3>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>

                <!-- 상태별 목표 차트 -->
                <div class="chart-card">
                    <h3>상태별 목표 현황</h3>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- 목표별 진행률 차트 -->
                <div class="chart-card chart-card-wide">
                    <h3>목표별 진행률</h3>
                    <div class="chart-container">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 최근 목표 목록 -->
            <div class="dashboard-section">
                <h3>최근 목표</h3>
                <?php if (empty($goals)): ?>
                    <div class="empty-state">
                        <p>아직 목표가 없습니다.</p>
                        <a href="goal_list.php?action=create" class="btn btn-primary">첫 목표 만들기</a>
                    </div>
                <?php else: ?>
                    <div class="goal-list">
                        <?php foreach (array_slice($goals, 0, 5) as $goal): ?>
                            <div class="goal-item">
                                <div class="goal-item-header">
                                    <h4>
                                        <a href="goal_detail.php?id=<?= $goal['id'] ?>">
                                            <?= e($goal['title']) ?>
                                        </a>
                                    </h4>
                                    <div class="goal-meta">
                                        <span class="badge badge-category"><?= e(getCategoryName($goal['category'])) ?></span>
                                        <?= getStatusBadge($goal['status']) ?>
                                    </div>
                                </div>
                                <div class="progress-bar">
                                    <div
                                        class="progress-fill <?= getProgressColorClass($goal['progress_percentage']) ?>"
                                        style="width: <?= $goal['progress_percentage'] ?>%"
                                    ></div>
                                </div>
                                <p class="progress-text"><?= $goal['progress_percentage'] ?>% 완료</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($goals) > 5): ?>
                        <div class="text-center">
                            <a href="goal_list.php" class="btn btn-secondary">모든 목표 보기</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // PHP 데이터를 JavaScript로 전달
        const categoryData = <?= json_encode($categoryCounts) ?>;
        const statusData = {
            'completed': <?= $completedGoals ?>,
            'in_progress': <?= $inProgressGoals ?>,
            'not_started': <?= $notStartedGoals ?>
        };
        const goalsData = <?= json_encode(array_map(function($goal) {
            return [
                'title' => $goal['title'],
                'progress' => $goal['progress_percentage'],
                'category' => $goal['category']
            ];
        }, $goals)) ?>;

        // 카테고리 이름 매핑
        const categoryNames = {
            'career': '커리어',
            'health': '건강',
            'study': '학습',
            'finance': '재정',
            'hobby': '취미',
            'relationship': '관계',
            'other': '기타'
        };

        // 차트가 그려질 때만 실행
        if (goalsData.length > 0) {
            // 1. 카테고리별 분포 도넛 차트
            const categoryCtx = document.getElementById('categoryChart');
            if (categoryCtx) {
                const categoryLabels = Object.keys(categoryData).map(key => categoryNames[key] || key);
                const categoryValues = Object.values(categoryData);

                new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: categoryLabels,
                        datasets: [{
                            data: categoryValues,
                            backgroundColor: [
                                '#FF6384',
                                '#36A2EB',
                                '#FFCE56',
                                '#4BC0C0',
                                '#9966FF',
                                '#FF9F40',
                                '#C9CBCF'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.parsed + '개';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // 2. 상태별 목표 바 차트
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'bar',
                    data: {
                        labels: ['미시작', '진행중', '완료'],
                        datasets: [{
                            label: '목표 수',
                            data: [
                                statusData.not_started,
                                statusData.in_progress,
                                statusData.completed
                            ],
                            backgroundColor: [
                                '#C9CBCF',
                                '#36A2EB',
                                '#4BC0C0'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y + '개';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // 3. 목표별 진행률 수평 바 차트
            const progressCtx = document.getElementById('progressChart');
            if (progressCtx) {
                const progressLabels = goalsData.map(goal => {
                    return goal.title.length > 20 ? goal.title.substring(0, 20) + '...' : goal.title;
                });
                const progressValues = goalsData.map(goal => parseFloat(goal.progress));

                new Chart(progressCtx, {
                    type: 'bar',
                    data: {
                        labels: progressLabels,
                        datasets: [{
                            label: '진행률 (%)',
                            data: progressValues,
                            backgroundColor: progressValues.map(value => {
                                if (value === 0) return '#C9CBCF';
                                if (value < 30) return '#FF6384';
                                if (value < 70) return '#FFCE56';
                                return '#4BC0C0';
                            }),
                            borderWidth: 0
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            x: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return '진행률: ' + context.parsed.x + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    </script>
</body>
</html>
