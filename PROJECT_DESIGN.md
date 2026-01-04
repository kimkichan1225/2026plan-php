# 신년계획 관리 웹 서비스 설계 문서

## 1. 전체 서비스 구조

### 1.1 서비스 개요
"계획 → 실행 → 회고"의 명확한 흐름을 가진 신년계획 관리 시스템으로, 사용자가 연간 목표를 설정하고 분기/월 단위로 계획을 수립하며, 진행 상황과 회고를 체계적으로 관리할 수 있는 웹 서비스입니다.

### 1.2 핵심 차별점
- 단순 투두리스트가 아닌 **목표 분해 및 추적 시스템**
- 서버 중심의 **진행률 자동 계산** 로직
- **분기/월 단위 계획 자동 생성** 구조
- **회고 시스템**을 통한 성찰 기능

### 1.3 기술 아키텍처
```
┌─────────────────────────────────────────┐
│          Frontend (UI Layer)            │
│   HTML + CSS + Vanilla JavaScript       │
│   - 사용자 인터랙션                       │
│   - 동적 UI 렌더링                        │
└─────────────────────────────────────────┘
                   ↓ HTTP Request
┌─────────────────────────────────────────┐
│      Backend (Business Logic)           │
│           PHP 8 + MVC Pattern           │
│   - Session 기반 인증                     │
│   - 목표/계획 CRUD                        │
│   - 진행률 계산 로직                       │
│   - 회고 데이터 처리                       │
└─────────────────────────────────────────┘
                   ↓ SQL Query
┌─────────────────────────────────────────┐
│         Database (Data Layer)           │
│              MySQL 8.0+                 │
│   - users, goals, goal_plans,           │
│     reflections 테이블                   │
└─────────────────────────────────────────┘
```

### 1.4 디렉토리 구조
```
php-project/
│
├── config/
│   └── database.php         # DB 연결 설정
│
├── includes/
│   ├── auth.php             # 인증 함수
│   ├── functions.php        # 공통 함수
│   └── session.php          # 세션 관리
│
├── models/
│   ├── User.php             # 사용자 모델
│   ├── Goal.php             # 목표 모델
│   ├── GoalPlan.php         # 계획 모델
│   └── Reflection.php       # 회고 모델
│
├── controllers/
│   ├── AuthController.php   # 인증 컨트롤러
│   ├── GoalController.php   # 목표 컨트롤러
│   └── ReflectionController.php
│
├── views/
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── goal_list.php
│   ├── goal_detail.php
│   └── reflection.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
│
├── api/                     # AJAX 요청 처리
│   ├── goal_create.php
│   ├── goal_update.php
│   └── progress_update.php
│
├── database/
│   └── schema.sql           # DB 스키마
│
└── index.php                # 진입점
```

## 2. 페이지 흐름도

```
┌──────────────┐
│  index.php   │  (랜딩/리다이렉트)
└──────┬───────┘
       │
       ├─→ [비로그인] ─→ login.php ───→ register.php
       │                    │
       │                    ↓ (로그인 성공)
       └─→ [로그인됨] ─→ dashboard.php
                            │
                ┌───────────┼───────────┐
                │           │           │
         goal_list.php  reflection.php  │
                │                       │
         goal_detail.php ←──────────────┘
         (목표 상세/계획 수정)
```

### 페이지별 주요 기능

#### login.php
- 이메일/비밀번호 입력
- Session 기반 인증 처리
- 로그인 실패 시 에러 메시지

#### register.php
- 회원가입 폼 (이름, 이메일, 비밀번호, 비밀번호 확인)
- 비밀번호 해시 처리 (password_hash)
- 중복 이메일 검증

#### dashboard.php
- 전체 목표 개수
- 진행 중 / 완료 목표 수
- 카테고리별 목표 분포 (차트 또는 테이블)
- 월별 진행률 요약

#### goal_list.php
- 연도별 목표 목록
- 필터링 (카테고리, 상태)
- 목표 추가 버튼

#### goal_detail.php
- 목표 정보 (제목, 설명, 카테고리)
- 분기별 계획 표시 (Q1~Q4)
- 각 분기의 월별 계획 (1~12월)
- 체크박스로 완료 여부 토글
- 실시간 진행률 표시

#### reflection.php
- 월별 회고 작성/조회
- 연말 요약 뷰
- 달력 형태 네비게이션

## 3. 핵심 로직 설계

### 3.1 목표 생성 시 자동 계획 분해
```
목표 생성 (year=2026)
    ↓
서버에서 자동 생성:
    - Q1: 1~3월
    - Q2: 4~6월
    - Q3: 7~9월
    - Q4: 10~12월
    ↓
goal_plans 테이블에 12개 레코드 INSERT
    - month: 1~12
    - quarter: 1~4
    - is_completed: 0 (기본값)
```

### 3.2 진행률 계산 로직
```php
진행률 = (완료된 월 계획 수 / 전체 월 계획 수) × 100

예시:
- 전체 12개월 중 3개월 완료 → 25%
- 완료된 계획을 체크하면 즉시 재계산
```

### 3.3 회고 시스템
```
reflections 테이블:
- user_id, goal_id (nullable)
- year, month
- content (TEXT)
- created_at

조회 방식:
- 특정 월의 모든 회고 조회
- 특정 목표에 대한 회고만 필터링
- 연말(12월) 회고 = 전체 요약
```

## 4. 보안 및 모범 사례

### 4.1 보안
- 비밀번호: `password_hash()` + `password_verify()`
- SQL Injection 방지: PDO prepared statements
- XSS 방지: `htmlspecialchars()`
- CSRF 방지: Session token (선택)

### 4.2 코드 품질
- PSR-12 코딩 스타일 준수
- 단일 책임 원칙 (모델별 파일 분리)
- DRY 원칙 (공통 함수 재사용)

### 4.3 데이터베이스 설계 원칙
- 정규화 (3NF)
- 외래키 제약조건
- 인덱스 최적화 (user_id, goal_id)
- Soft Delete 대신 Hard Delete (단순화)

## 5. 확장 가능성

### 단기 개선
- 목표 우선순위 설정
- 목표 태그 시스템
- 간단한 통계 차트 (Chart.js)

### 중기 개선
- 다음 해 목표 복사 기능
- 목표 공유 (공개/비공개)
- 이메일 리마인더

### 장기 개선
- REST API 분리
- 모바일 반응형
- PWA 전환
