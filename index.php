<?php
require_once __DIR__ . '/includes/session.php';

// 로그인 여부에 따라 리다이렉트
if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
