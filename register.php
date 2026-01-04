<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/User.php';

// 이미 로그인된 경우 대시보드로 리다이렉트
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$success = false;

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // 입력 검증
    if (empty($name)) {
        $errors['name'] = '이름을 입력해주세요.';
    }

    if (empty($email)) {
        $errors['email'] = '이메일을 입력해주세요.';
    } elseif (!isValidEmail($email)) {
        $errors['email'] = '올바른 이메일 형식이 아닙니다.';
    } else {
        // 이메일 중복 체크
        $userModel = new User();
        if ($userModel->emailExists($email)) {
            $errors['email'] = '이미 사용 중인 이메일입니다.';
        }
    }

    if (empty($password)) {
        $errors['password'] = '비밀번호를 입력해주세요.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = '비밀번호는 최소 6자 이상이어야 합니다.';
    }

    if ($password !== $passwordConfirm) {
        $errors['password_confirm'] = '비밀번호가 일치하지 않습니다.';
    }

    // 에러가 없으면 회원가입 처리
    if (empty($errors)) {
        try {
            $userModel = new User();
            $userId = $userModel->create($name, $email, $password);

            if ($userId) {
                $success = true;
                // 자동 로그인 처리 (선택적)
                // $user = $userModel->findById($userId);
                // loginUser($user);
                // redirect('dashboard.php');
            }
        } catch (Exception $e) {
            $errors['general'] = '회원가입 중 오류가 발생했습니다.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원가입 - 신년계획 관리</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>회원가입</h1>
                <p>새로운 계정을 만들어보세요</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    회원가입이 완료되었습니다! <a href="login.php">로그인하기</a>
                </div>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-error">
                    <?= e($errors['general']) ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form method="POST" action="register.php" class="auth-form">
                    <div class="form-group">
                        <label for="name">이름</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= e($_POST['name'] ?? '') ?>"
                            required
                            autofocus
                            placeholder="홍길동"
                        >
                        <?php if (isset($errors['name'])): ?>
                            <span class="error-text"><?= e($errors['name']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">이메일</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= e($_POST['email'] ?? '') ?>"
                            required
                            placeholder="example@email.com"
                        >
                        <?php if (isset($errors['email'])): ?>
                            <span class="error-text"><?= e($errors['email']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password">비밀번호</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            placeholder="최소 6자 이상"
                        >
                        <?php if (isset($errors['password'])): ?>
                            <span class="error-text"><?= e($errors['password']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">비밀번호 확인</label>
                        <input
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            required
                            placeholder="비밀번호 재입력"
                        >
                        <?php if (isset($errors['password_confirm'])): ?>
                            <span class="error-text"><?= e($errors['password_confirm']) ?></span>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">회원가입</button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                <p>이미 계정이 있으신가요? <a href="login.php">로그인</a></p>
            </div>
        </div>
    </div>
</body>
</html>
