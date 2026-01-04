<?php
/**
 * 세션 관리 함수
 */

// 세션 시작 (중복 방지)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 사용자 로그인 처리
 */
function loginUser(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['logged_in'] = true;

    // 세션 재생성 (세션 고정 공격 방지)
    session_regenerate_id(true);
}

/**
 * 사용자 로그아웃 처리
 */
function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * 로그인 여부 확인
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * 로그인 필수 체크 (리다이렉트)
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * 현재 로그인한 사용자 ID 반환
 */
function getCurrentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * 현재 로그인한 사용자 이름 반환
 */
function getCurrentUserName(): ?string
{
    return $_SESSION['user_name'] ?? null;
}
