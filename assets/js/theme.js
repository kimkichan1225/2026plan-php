/**
 * 다크 모드 테마 전환
 */

(function() {
    const THEME_KEY = 'theme-preference';

    // 저장된 테마 또는 시스템 설정 가져오기
    function getPreferredTheme() {
        const savedTheme = localStorage.getItem(THEME_KEY);
        if (savedTheme) {
            return savedTheme;
        }

        // 시스템 다크 모드 설정 확인
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }

        return 'light';
    }

    // 테마 적용
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(THEME_KEY, theme);
        updateToggleButton(theme);
    }

    // 토글 버튼 아이콘 업데이트
    function updateToggleButton(theme) {
        const toggle = document.getElementById('themeToggle');
        if (toggle) {
            const icon = toggle.querySelector('.icon');
            if (theme === 'dark') {
                icon.textContent = '🌙';
                toggle.setAttribute('aria-label', '라이트 모드로 전환');
            } else {
                icon.textContent = '☀️';
                toggle.setAttribute('aria-label', '다크 모드로 전환');
            }
        }
    }

    // 테마 토글
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        setTheme(newTheme);
    }

    // 초기 테마 적용 (페이지 로드 전 깜빡임 방지)
    const initialTheme = getPreferredTheme();
    setTheme(initialTheme);

    // DOM 로드 완료 후 이벤트 리스너 추가
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('themeToggle');
        if (toggle) {
            toggle.addEventListener('click', toggleTheme);
        }

        // 시스템 다크 모드 변경 감지
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (!localStorage.getItem(THEME_KEY)) {
                    setTheme(e.matches ? 'dark' : 'light');
                }
            });
        }
    });
})();
