<?php
/**
 * 공통 유틸리티 함수
 */

/**
 * XSS 방지: HTML 이스케이프
 */
function e(?string $string): string
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * 리다이렉트
 */
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

/**
 * JSON 응답 반환
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 에러 JSON 응답
 */
function jsonError(string $message, int $statusCode = 400): void
{
    jsonResponse(['success' => false, 'error' => $message], $statusCode);
}

/**
 * 성공 JSON 응답
 */
function jsonSuccess(array $data = [], string $message = 'Success'): void
{
    jsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
}

/**
 * POST 데이터 검증
 */
function validateRequired(array $fields): array
{
    $errors = [];

    foreach ($fields as $field => $label) {
        if (empty($_POST[$field])) {
            $errors[$field] = "$label 필드는 필수입니다.";
        }
    }

    return $errors;
}

/**
 * 이메일 유효성 검사
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 비밀번호 해시 생성
 */
function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * 비밀번호 검증
 */
function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * 분기 계산 (1~4)
 */
function getQuarter(int $month): int
{
    return (int) ceil($month / 3);
}

/**
 * 분기별 월 배열 반환
 */
function getMonthsInQuarter(int $quarter): array
{
    $quarters = [
        1 => [1, 2, 3],
        2 => [4, 5, 6],
        3 => [7, 8, 9],
        4 => [10, 11, 12],
    ];

    return $quarters[$quarter] ?? [];
}

/**
 * 진행률 퍼센티지 계산
 */
function calculateProgress(int $completed, int $total): float
{
    if ($total === 0) {
        return 0.0;
    }

    return round(($completed / $total) * 100, 2);
}

/**
 * 목표 상태 배지 HTML 생성
 */
function getStatusBadge(string $status): string
{
    $badges = [
        'not_started' => '<span class="badge badge-secondary">미시작</span>',
        'in_progress' => '<span class="badge badge-primary">진행중</span>',
        'completed' => '<span class="badge badge-success">완료</span>',
    ];

    return $badges[$status] ?? '';
}

/**
 * 카테고리 이름 한글화
 */
function getCategoryName(string $category): string
{
    $categories = [
        'career' => '커리어',
        'health' => '건강',
        'study' => '학습',
        'finance' => '재정',
        'hobby' => '취미',
        'relationship' => '관계',
        'other' => '기타',
    ];

    return $categories[$category] ?? $category;
}

/**
 * 진행률 색상 클래스 반환
 */
function getProgressColorClass(float $progress): string
{
    if ($progress >= 75) {
        return 'progress-success';
    } elseif ($progress >= 50) {
        return 'progress-info';
    } elseif ($progress >= 25) {
        return 'progress-warning';
    } else {
        return 'progress-danger';
    }
}

/**
 * 날짜 포맷 (한국어)
 */
function formatDate(?string $date, string $format = 'Y-m-d H:i'): string
{
    if (empty($date)) {
        return '';
    }

    return date($format, strtotime($date));
}

/**
 * 상대 시간 표시 (예: 3일 전)
 */
function timeAgo(string $datetime): string
{
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return '방금 전';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '분 전';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '시간 전';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . '일 전';
    } else {
        return date('Y-m-d', $time);
    }
}
