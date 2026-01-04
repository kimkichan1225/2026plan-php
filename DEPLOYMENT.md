# 배포 가이드

신년계획 관리 웹 서비스를 실제로 배포하는 방법입니다.

## 목차
1. [Railway 배포 (추천)](#1-railway-배포-추천)
2. [InfinityFree 배포 (무료)](#2-infinityfree-배포-무료)
3. [Cafe24 배포 (한국)](#3-cafe24-배포-한국)
4. [AWS EC2 배포 (프로덕션)](#4-aws-ec2-배포-프로덕션)

---

## 1. Railway 배포 (추천)

GitHub 연동으로 가장 쉽고 빠른 배포 방법입니다.

### 장점
- ✅ GitHub 푸시만 하면 자동 배포
- ✅ MySQL 자동 설정
- ✅ HTTPS 자동 적용
- ✅ 무료 크레딧 $5/월

### 배포 단계

#### 1단계: Railway 가입
```
1. https://railway.app 접속
2. "Start a New Project" 클릭
3. GitHub 계정으로 로그인
```

#### 2단계: 프로젝트 연결
```
1. "Deploy from GitHub repo" 선택
2. "kimkichan1225/2026plan-php" 선택
3. "Deploy Now" 클릭
```

#### 3단계: MySQL 추가
```
1. 프로젝트 대시보드에서 "+ New" 클릭
2. "Database" → "Add MySQL" 선택
3. 자동으로 DB 생성됨
```

#### 4단계: 환경 변수 설정
```
프로젝트 → Variables 탭에서 설정:

DB_HOST=<Railway MySQL Host>
DB_NAME=railway
DB_USER=<Railway MySQL User>
DB_PASS=<Railway MySQL Password>
DB_PORT=<Railway MySQL Port>
```

Railway가 자동으로 제공하는 값 사용

#### 5단계: 데이터베이스 마이그레이션
```
1. Railway CLI 설치:
   npm install -g @railway/cli

2. 로그인:
   railway login

3. 프로젝트 연결:
   railway link

4. 데이터베이스 마이그레이션:
   railway run mysql -h $DB_HOST -u $DB_USER -p$DB_PASS < database/schema.sql
```

#### 6단계: 접속
```
Railway가 자동으로 생성한 URL로 접속:
https://your-project.up.railway.app
```

---

## 2. InfinityFree 배포 (무료)

완전 무료 호스팅으로 포트폴리오용으로 적합합니다.

### 배포 단계

#### 1단계: 회원가입
```
1. https://infinityfree.net 접속
2. "Sign Up" 클릭
3. 이메일 인증
```

#### 2단계: 호스팅 계정 생성
```
1. "Create Account" 클릭
2. 도메인 선택 (무료 서브도메인 제공)
   예: mygoals.infinityfreeapp.com
3. 계정 생성 완료
```

#### 3단계: 파일 업로드
```
방법 1: File Manager (웹)
1. Control Panel → File Manager
2. htdocs 폴더로 이동
3. 모든 파일 업로드 (database/ 제외)

방법 2: FTP (추천)
1. FileZilla 설치
2. FTP 정보 입력 (Control Panel에서 확인)
3. htdocs에 파일 업로드
```

#### 4단계: MySQL 데이터베이스 생성
```
1. Control Panel → MySQL Databases
2. "Create Database" 클릭
3. 데이터베이스 이름, 사용자 생성
4. phpMyAdmin으로 접속
5. "Import" 탭에서 schema.sql 업로드
```

#### 5단계: 설정 파일 수정
```
config/database.php 파일을 서버에 맞게 수정:

define('DB_HOST', 'sql123.infinityfree.com');
define('DB_NAME', 'if0_12345678_new_year_goals');
define('DB_USER', 'if0_12345678');
define('DB_PASS', 'your_password');
```

#### 6단계: 접속
```
https://mygoals.infinityfreeapp.com
```

---

## 3. Cafe24 배포 (한국)

한국 서버로 빠른 속도를 원할 때 사용합니다.

### 가격
- 월 3,300원 (LIGHT)
- 월 5,500원 (BASIC)

### 배포 단계

#### 1단계: 호스팅 신청
```
1. https://cafe24.com 접속
2. 호스팅 → 웹호스팅 신청
3. 요금제 선택 (LIGHT 추천)
4. 도메인 선택 또는 구매
```

#### 2단계: FTP 접속
```
1. Cafe24 관리자 페이지 로그인
2. FTP 정보 확인
3. FileZilla로 접속
4. www 폴더에 파일 업로드
```

#### 3단계: MySQL 설정
```
1. 관리자 → MySQL 관리
2. 데이터베이스 생성
3. phpMyAdmin 접속
4. schema.sql Import
```

#### 4단계: 설정 파일 수정
```
config/database.php:

define('DB_HOST', 'localhost');
define('DB_NAME', 'your_cafe24_db');
define('DB_USER', 'your_cafe24_user');
define('DB_PASS', 'your_password');
```

#### 5단계: 접속
```
http://yourdomain.cafe24.com
또는
http://yourdomain.com (구매한 도메인)
```

---

## 4. AWS EC2 배포 (프로덕션)

실제 서비스 운영 시 사용하는 방법입니다.

### 가격
- 프리티어: 1년 무료 (t2.micro)
- 이후: 월 $10~

### 배포 단계

#### 1단계: EC2 인스턴스 생성
```
1. AWS 콘솔 접속
2. EC2 → Launch Instance
3. Ubuntu Server 22.04 LTS 선택
4. t2.micro 선택 (프리티어)
5. 보안 그룹: HTTP(80), HTTPS(443), SSH(22) 열기
6. 키 페어 생성 및 다운로드
```

#### 2단계: SSH 접속
```bash
ssh -i your-key.pem ubuntu@your-ec2-ip
```

#### 3단계: 서버 설정
```bash
# 패키지 업데이트
sudo apt update && sudo apt upgrade -y

# Apache, PHP, MySQL 설치
sudo apt install apache2 php php-mysql mysql-server -y

# MySQL 보안 설정
sudo mysql_secure_installation
```

#### 4단계: 프로젝트 배포
```bash
# Git 클론
cd /var/www/html
sudo git clone https://github.com/kimkichan1225/2026plan-php.git
sudo mv 2026plan-php/* .

# 권한 설정
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
```

#### 5단계: MySQL 설정
```bash
sudo mysql -u root -p

CREATE DATABASE new_year_goals;
CREATE USER 'appuser'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL ON new_year_goals.* TO 'appuser'@'localhost';
FLUSH PRIVILEGES;
USE new_year_goals;
SOURCE /var/www/html/database/schema.sql;
EXIT;
```

#### 6단계: 설정 파일
```bash
sudo nano /var/www/html/config/database.php

# DB 정보 수정
define('DB_HOST', 'localhost');
define('DB_NAME', 'new_year_goals');
define('DB_USER', 'appuser');
define('DB_PASS', 'strong_password');
```

#### 7단계: Apache 재시작
```bash
sudo systemctl restart apache2
```

#### 8단계: 접속
```
http://your-ec2-public-ip
```

---

## 📊 플랫폼 비교표

| 플랫폼 | 가격 | 난이도 | 속도 | 추천 용도 |
|--------|------|--------|------|-----------|
| Railway | 무료~$5 | ⭐ 쉬움 | ⚡ 빠름 | 포트폴리오 |
| InfinityFree | 무료 | ⭐⭐ 보통 | 🐌 느림 | 테스트 |
| Cafe24 | 월 3,300원 | ⭐⭐ 보통 | ⚡ 빠름 | 한국 서비스 |
| AWS EC2 | 월 $10~ | ⭐⭐⭐ 어려움 | ⚡⚡ 매우 빠름 | 프로덕션 |

---

## 🎯 추천 순서

### 1. 포트폴리오 제출용
```
Railway → GitHub 연동 자동 배포
```

### 2. 면접 데모용
```
Cafe24 → 안정적이고 빠름
```

### 3. 실제 서비스
```
AWS EC2 → 완전한 제어
```

---

## 🔒 배포 전 체크리스트

- [ ] `config/database.php` 파일 설정 확인
- [ ] `.gitignore`에 민감한 정보 제외
- [ ] 데이터베이스 스키마 실행
- [ ] 테스트 계정 생성 확인
- [ ] HTTPS 설정 (SSL 인증서)
- [ ] 에러 로그 확인
- [ ] 성능 테스트

---

## 📞 문제 해결

### "Database connection failed"
```
→ config/database.php의 DB 정보 확인
→ MySQL 서버 실행 확인
```

### "Permission denied"
```
→ 파일 권한 확인: chmod 755
→ 소유자 확인: chown www-data:www-data
```

### "404 Not Found"
```
→ .htaccess 파일 확인
→ Apache mod_rewrite 활성화
```

---

**배포 완료 후 README.md에 라이브 데모 링크 추가하세요!**

```markdown
## 🌐 라이브 데모

https://your-deployed-url.com

테스트 계정:
- 이메일: test@test.com
- 비밀번호: test1234
```
