document.addEventListener('DOMContentLoaded', function() {
    // Check if the current page is the exam page
    const isExamPage = window.location.pathname.includes('exam.php');

    if (isExamPage) {
        // Prevent copy-paste on exam page
        document.addEventListener('copy', (e) => e.preventDefault());
        document.addEventListener('paste', (e) => e.preventDefault());
        document.addEventListener('cut', (e) => e.preventDefault());

        // Detect tab/window change on exam page
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                fetch('report.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=tab_change'
                });
                alert('Tab change detected! This has been reported.');
            }
        });

        // Prevent window close on exam page
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = 'Are you sure you want to leave? Your progress will be saved.';
        });

        // Save progress periodically on exam page
        const examForm = document.getElementById('examForm');
        if (examForm) {
            setInterval(() => {
                const formData = new FormData(examForm);
                const answers = {};
                for (let [key, value] of formData.entries()) {
                    if (key.startsWith('question[')) {
                        const id = key.match(/\[(\d+)\]/)[1];
                        answers[id] = value;
                    }
                }
                fetch('save_progress.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(answers)
                });
            }, 30000); // Save every 30 seconds
        }
    }
});