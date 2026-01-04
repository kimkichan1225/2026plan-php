@echo off
chcp 65001 > nul
echo ========================================
echo MySQL 포트 충돌 자동 해결
echo ========================================
echo.

echo 관리자 권한 확인 중...
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo ❌ 관리자 권한이 필요합니다!
    echo.
    echo 이 파일을 우클릭 후 "관리자 권한으로 실행"을 선택하세요.
    echo.
    pause
    exit /b 1
)

echo ✓ 관리자 권한 확인 완료
echo.

echo [1/3] 포트 3306 사용 중인 프로세스 확인...
echo.
netstat -ano | findstr :3306
echo.

echo [2/3] 기존 MySQL 서비스 중지 중...
echo.

net stop MySQL 2>nul
if %errorlevel% equ 0 echo ✓ MySQL 서비스 중지됨

net stop MySQL80 2>nul
if %errorlevel% equ 0 echo ✓ MySQL80 서비스 중지됨

net stop MySQL57 2>nul
if %errorlevel% equ 0 echo ✓ MySQL57 서비스 중지됨

echo.
echo [3/3] 완료!
echo.
echo ========================================
echo ✅ 이제 XAMPP에서 MySQL을 시작하세요
echo ========================================
echo.
echo XAMPP Control Panel에서:
echo 1. MySQL 옆의 'Start' 버튼 클릭
echo 2. 초록색으로 변하면 성공!
echo.
pause
