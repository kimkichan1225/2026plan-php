# 신년계획 관리 웹 서비스

> "계획 → 실행 → 회고" 흐름을 구현한 PHP 8 기반 목표 관리 시스템

## 프로젝트 소개

신년계획 관리 웹 서비스는 사용자가 연간 목표를 설정하고, 이를 분기 및 월 단위로 구조화하여 관리하며, 진행 상황을 추적하고 회고할 수 있는 웹 애플리케이션입니다.

단순한 투두리스트를 넘어서, **목표 분해 → 계획 수립 → 진행 추적 → 회고 작성**이라는 완전한 목표 달성 사이클을 지원합니다.

### 주요 특징

- **자동 계획 분해**: 목표 생성 시 12개월 계획을 자동으로 생성하고 분기별로 그룹화
- **실시간 진행률 계산**: 서버 측에서 완료된 계획을 기반으로 진행률을 자동 계산
- **회고 시스템**: 월별/목표별 회고 작성으로 성찰과 개선 촉진
- **직관적인 대시보드**: 통계 및 시각화를 통한 목표 현황 한눈에 파악

## 기술 스택

### Backend
- **PHP 8.x**: 최신 PHP 문법 활용 (타입 힌팅, 널 병합 연산자 등)
- **MySQL 8.0**: 관계형 데이터베이스 설계 및 트랜잭션 처리
- **PDO**: SQL Injection 방지를 위한 Prepared Statements

### Frontend
- **HTML5/CSS3**: 시맨틱 마크업 및 반응형 디자인
- **Vanilla JavaScript**: 프레임워크 없는 순수 JavaScript
- **AJAX**: 비동기 통신을 통한 UX 개선

### 보안
- `password_hash()` / `password_verify()`: 비밀번호 안전한 해싱
- PDO Prepared Statements: SQL Injection 방지
- `htmlspecialchars()`: XSS 공격 방지
- Session 기반 인증: 보안 세션 관리

## 핵심 기능

### 1. 회원 시스템
- 회원가입 및 로그인 (Session 기반 인증)
- 비밀번호 해싱 처리
- 사용자별 데이터 완전 분리

### 2. 목표 관리
- 목표 생성, 조회, 수정
- 카테고리별 분류 (커리어, 건강, 학습, 재정, 취미, 관계 등)
- 상태 관리 (미시작, 진행중, 완료)

### 3. 계획 분해 시스템
- 목표 생성 시 **자동으로 12개월 계획 생성**
- 분기별 그룹화 (Q1~Q4)
- 월별 계획 작성 및 완료 체크

### 4. 진행률 계산 엔진
```php
// 자동 진행률 계산 로직
진행률 = (완료된 월 계획 수 / 전체 12개월) × 100

// 트리거를 통한 자동 업데이트
계획 완료 체크 → DB 트리거 발동 → 목표 진행률 자동 업데이트
```

### 5. 회고 시스템
- 월별 회고 작성
- 목표별 회고 연결
- 연말 요약 회고 (12월)

### 6. 대시보드
- 전체 목표 통계
- 카테고리별 분포
- 진행 상황 시각화

## 데이터베이스 설계

### ERD 구조
```
users (사용자)
  ↓ 1:N
goals (목표)
  ↓ 1:N
goal_plans (월별 계획)

users ← reflections (회고) → goals
```

### 주요 테이블

#### users
- `id`: 사용자 고유 ID (PK)
- `email`: 이메일 (UNIQUE, 로그인 ID)
- `password_hash`: 해시된 비밀번호

#### goals
- `id`: 목표 고유 ID (PK)
- `user_id`: 사용자 FK
- `year`: 목표 연도
- `category`: 카테고리 (ENUM)
- `status`: 상태 (ENUM: not_started, in_progress, completed)
- `progress_percentage`: 진행률 (자동 계산)

#### goal_plans
- `id`: 계획 고유 ID (PK)
- `goal_id`: 목표 FK
- `quarter`: 분기 (1~4)
- `month`: 월 (1~12)
- `is_completed`: 완료 여부
- **UNIQUE KEY**: (goal_id, month) - 중복 방지

#### reflections
- `id`: 회고 고유 ID (PK)
- `user_id`: 작성자 FK
- `goal_id`: 목표 FK (NULL 허용 - 전체 회고)
- `year`, `month`: 회고 시점
- `content`: 회고 내용

## 핵심 구현 로직

### 1. 로그인 처리 (login.php)
```php
// 이메일/비밀번호 검증
$userModel = new User();
$user = $userModel->authenticate($email, $password);

if ($user) {
    // Session 기반 로그인
    loginUser($user);
    redirect('dashboard.php');
}
```

### 2. 목표 생성 + 자동 계획 생성 (Goal.php)
```php
public function create($userId, $year, $title, $description, $category)
{
    $this->db->beginTransaction();

    // 1. 목표 생성
    $goalId = INSERT INTO goals...

    // 2. 12개월 계획 자동 생성
    for ($month = 1; $month <= 12; $month++) {
        $quarter = ceil($month / 3);
        INSERT INTO goal_plans (goal_id, quarter, month)...
    }

    $this->db->commit();
    return $goalId;
}
```

### 3. 진행률 자동 계산 (Goal.php)
```php
public function updateProgress($goalId)
{
    // 완료된 계획 수 집계
    $result = SELECT
        COUNT(*) as total,
        SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed
    FROM goal_plans
    WHERE goal_id = $goalId;

    // 진행률 계산
    $progress = ($completed / $total) * 100;

    // 상태 자동 결정
    $status = $progress == 0 ? 'not_started'
            : ($progress == 100 ? 'completed' : 'in_progress');

    // 업데이트
    UPDATE goals
    SET progress_percentage = $progress, status = $status
    WHERE id = $goalId;
}
```

### 4. DB 트리거를 통한 자동화 (schema.sql)
```sql
CREATE TRIGGER tr_update_progress_after_plan_update
AFTER UPDATE ON goal_plans
FOR EACH ROW
BEGIN
    -- 진행률 재계산
    UPDATE goals SET progress_percentage = ...
    WHERE id = NEW.goal_id;
END;
```

## 프로젝트 구조

```
php-project/
├── config/
│   └── database.php          # DB 연결 설정
├── includes/
│   ├── session.php           # 세션 관리
│   └── functions.php         # 공통 함수
├── models/
│   ├── User.php              # 사용자 모델
│   ├── Goal.php              # 목표 모델
│   ├── GoalPlan.php          # 계획 모델
│   └── Reflection.php        # 회고 모델
├── views/
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── goal_list.php
│   ├── goal_detail.php
│   └── reflection.php
├── assets/
│   ├── css/style.css
│   └── js/main.js
└── database/
    └── schema.sql            # DB 스키마
```

## 설치 및 실행 방법

### 1. 환경 요구사항
- PHP 8.0 이상
- MySQL 8.0 이상
- Apache 또는 Nginx 웹 서버

### 2. 설치 단계

```bash
# 1. 프로젝트 클론
git clone [repository-url]

# 2. 데이터베이스 생성
mysql -u root -p < database/schema.sql

# 3. DB 연결 설정 (config/database.php)
define('DB_HOST', 'localhost');
define('DB_NAME', 'new_year_goals');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

# 4. 웹 서버 실행
php -S localhost:8000
```

### 3. 접속
브라우저에서 `http://localhost:8000` 접속

### 4. 테스트 계정
```
이메일: test1@test.com
비밀번호: test1234
```

또는 새로운 계정을 회원가입하여 사용할 수 있습니다.

## 개발 시 고려한 점

### 1. 보안
- 모든 사용자 입력에 대한 검증 및 이스케이프
- SQL Injection 방지 (PDO Prepared Statements)
- XSS 공격 방지 (htmlspecialchars)
- 비밀번호 안전한 해싱 (bcrypt)

### 2. 사용자 경험
- 직관적인 UI/UX 설계
- 진행 상황 시각화 (진행률 바, 색상 구분)
- 반응형 디자인 (모바일 대응)

### 3. 코드 품질
- PSR-12 코딩 표준 준수
- 단일 책임 원칙 (모델별 파일 분리)
- DRY 원칙 (함수 재사용)

### 4. 데이터베이스 최적화
- 적절한 인덱스 설정
- 외래키 제약조건으로 데이터 무결성 보장
- 트리거를 통한 자동화

## 확장 가능성

### 단기 개선 사항
- 목표 우선순위 설정
- 목표 태그 시스템
- 통계 차트 (Chart.js)

### 중기 개선 사항
- 다음 해 목표 복사 기능
- 목표 공유 (공개/비공개)
- 이메일 리마인더

### 장기 개선 사항
- REST API 분리
- React/Vue.js 프론트엔드
- PWA 전환

## 포트폴리오 활용 포인트

### 1. 기술 역량 증명
- **PHP 8 최신 문법 활용**: 타입 힌팅, 널 병합 연산자, Arrow Function
- **OOP 설계**: 모델 클래스 기반 구조
- **데이터베이스 설계**: 정규화, 트리거, 뷰 활용

### 2. 비즈니스 로직 구현
- **자동 계획 분해 로직**: 목표를 자동으로 12개월로 분해
- **진행률 계산 엔진**: 실시간 자동 계산 및 상태 관리
- **회고 시스템**: 성찰을 통한 목표 달성 촉진

### 3. 실무 적용 가능
- **보안 고려**: SQL Injection, XSS 방지
- **확장 가능한 구조**: MVC 패턴 적용
- **유지보수성**: 코드 표준 준수, 명확한 네이밍

## 면접 대비 설명 문구

> "신년계획 관리 웹 서비스는 PHP 8과 MySQL을 활용하여 구현한 목표 관리 시스템입니다.
>
> 핵심 차별점은 **서버 측 자동화 로직**에 있습니다. 사용자가 목표를 생성하면, 서버에서 자동으로 12개월 계획을 생성하고 분기별로 그룹화합니다. 또한 DB 트리거를 활용하여 계획 완료 시 자동으로 진행률을 재계산하고 목표 상태를 업데이트합니다.
>
> 보안 측면에서는 PDO Prepared Statements로 SQL Injection을 방지하고, password_hash를 통한 안전한 비밀번호 관리, XSS 방지를 위한 입력 검증을 적용했습니다.
>
> 데이터베이스는 정규화를 통해 설계했으며, users - goals - goal_plans - reflections의 관계를 명확히 했습니다. 특히 goal_plans 테이블에 (goal_id, month) UNIQUE KEY를 설정하여 데이터 무결성을 보장했습니다.
>
> 이 프로젝트를 통해 PHP 백엔드 개발 역량, 데이터베이스 설계 능력, 그리고 사용자 중심의 서비스 기획 능력을 증명할 수 있습니다."

## 라이선스

MIT License

## 개발자

- 개발 기간: 2026년 1월
- 개발자: 김기찬   
- 문의: kimkichan1225@gmail.com

---

**포트폴리오 제출 시 함께 제공할 자료:**
1. 이 README 파일
2. 주요 기능 스크린샷
3. DB ERD 다이어그램
4. 핵심 코드 설명 문서 (PROJECT_DESIGN.md)
