(function() {
    const script = document.currentScript;
    const containerId = script.getAttribute('data-container') || 'sanatorium-booking-root';
    const baseUrl = script.src.split('/embed/')[0] + '/public/index.php';

    const container = document.getElementById(containerId);
    if (!container) {
        console.error('Sanatorium Booking: Container #' + containerId + ' not found.');
        return;
    }

    const iframe = document.createElement('iframe');
    iframe.src = baseUrl;
    iframe.style.width = '100%';
    iframe.style.height = '700px';
    iframe.style.border = 'none';
    iframe.style.overflow = 'hidden';

    container.appendChild(iframe);
})();
