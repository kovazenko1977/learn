document.addEventListener('DOMContentLoaded', function() {
    const startButton = document.querySelector('.start-button');
    const sidebar = document.getElementById('sidebar');

    if (startButton && sidebar) {
        startButton.addEventListener('click', function(e) {
            sidebar.classList.toggle('active');
            e.stopPropagation();
        });
    }

    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('active') && !sidebar.contains(e.target) && e.target !== startButton) {
            sidebar.classList.remove('active');
        }
    });
});
