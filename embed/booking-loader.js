(function() {
    const script = document.currentScript;
    const containerId = script.getAttribute('data-container') || 'sanatorium-booking-root';
    const type = script.getAttribute('data-type') || 'rooms';

    const host = script.src.split('/embed/')[0];
    const page = (type === 'sauna') ? 'booking_sauna.php' : 'booking_rooms.php';
    const baseUrl = host + '/' + page;

    let container = document.getElementById(containerId);
    if (!container) {
        container = document.createElement('div');
        container.id = containerId;
        script.parentNode.insertBefore(container, script);
    }

    const iframe = document.createElement('iframe');
    iframe.src = baseUrl;
    iframe.style.width = '100%';
    iframe.style.minHeight = (type === 'sauna') ? '900px' : '850px';
    iframe.style.border = 'none';
    iframe.style.overflow = 'auto';
    iframe.style.borderRadius = '12px';
    iframe.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';

    container.appendChild(iframe);
})();
