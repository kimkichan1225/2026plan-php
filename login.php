<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/User.php';

// 이미 로그인된 경우 대시보드로 리다이렉트
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 입력 검증
    if (empty($email) || empty($password)) {
        $error = '이메일과 비밀번호를 입력해주세요.';
    } elseif (!isValidEmail($email)) {
        $error = '올바른 이메일 형식이 아닙니다.';
    } else {
        // 인증 시도
        $userModel = new User();
        $user = $userModel->authenticate($email, $password);

        if ($user) {
            loginUser($user);
            redirect('dashboard.php');
        } else {
            $error = '이메일 또는 비밀번호가 올바르지 않습니다.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 - 신년계획 관리</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>신년계획 관리</h1>
                <p>목표를 설정하고, 실행하고, 회고하세요</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="auth-form">
                <div class="form-group">
                    <label for="email">이메일</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= e($_POST['email'] ?? '') ?>"
                        required
                        autofocus
                        placeholder="example@email.com"
                    >
                </div>

                <div class="form-group">
                    <label for="password">비밀번호</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="비밀번호 입력"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">로그인</button>
            </form>

            <div class="test-account-section">
                <p class="test-account-label">테스트 계정으로 빠른 체험</p>
                <form method="POST" action="login.php">
                    <input type="hidden" name="email" value="test1@test.com">
                    <input type="hidden" name="password" value="test1234">
                    <button type="submit" class="btn btn-secondary btn-block btn-test">
                        테스트 계정으로 로그인
                    </button>
                </form>
            </div>

            <div class="auth-footer">
                <p>계정이 없으신가요? <a href="register.php">회원가입</a></p>
            </div>
        </div>
    </div>
</body>
</html>
