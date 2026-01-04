/**
 * 신년계획 관리 - 메인 JavaScript
 */

// DOM 로드 완료 후 실행
document.addEventListener('DOMContentLoaded', function() {
    // 계획 완료 토글 처리
    initPlanToggle();

    // 계획 편집 모달
    initPlanEditModal();
});

/**
 * 계획 완료 체크박스 토글
 */
function initPlanToggle() {
    const toggles = document.querySelectorAll('.plan-toggle');

    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const planId = this.dataset.planId;
            const isChecked = this.checked;

            // 서버에 요청
            fetch('goal_detail.php?id=' + getGoalIdFromUrl(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'toggle_complete',
                    plan_id: planId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // UI 업데이트
                    const planItem = this.closest('.plan-item');
                    if (isChecked) {
                        planItem.classList.add('completed');
                    } else {
                        planItem.classList.remove('completed');
                    }

                    // 페이지 새로고침하여 진행률 업데이트
                    setTimeout(() => {
                        location.reload();
                    }, 300);
                } else {
                    alert('오류가 발생했습니다.');
                    this.checked = !isChecked;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('오류가 발생했습니다.');
                this.checked = !isChecked;
            });
        });
    });
}

/**
 * 계획 편집 모달 초기화
 */
function initPlanEditModal() {
    const modal = document.getElementById('editPlanModal');
    if (!modal) return;

    const editButtons = document.querySelectorAll('.btn-edit-plan');
    const closeButtons = modal.querySelectorAll('.modal-close');
    const form = document.getElementById('editPlanForm');

    // 편집 버튼 클릭
    editButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const planId = this.dataset.planId;
            const planTitle = this.dataset.planTitle;
            const planDescription = this.dataset.planDescription;

            document.getElementById('edit_plan_id').value = planId;
            document.getElementById('edit_plan_title').value = planTitle;
            document.getElementById('edit_plan_description').value = planDescription;

            modal.classList.add('active');
        });
    });

    // 모달 닫기
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            modal.classList.remove('active');
        });
    });

    // 모달 외부 클릭 시 닫기
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });

    // 폼 제출
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            formData.append('action', 'update_plan');

            fetch('goal_detail.php?id=' + getGoalIdFromUrl(), {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modal.classList.remove('active');
                    location.reload();
                } else {
                    alert('저장 중 오류가 발생했습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('저장 중 오류가 발생했습니다.');
            });
        });
    }
}

/**
 * URL에서 goal_id 추출
 */
function getGoalIdFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}
