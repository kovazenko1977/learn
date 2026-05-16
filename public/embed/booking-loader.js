(function() {
    const script = document.currentScript;
    const containerId = script.getAttribute('data-container') || 'sanatorium-booking-root';
    const typeId = script.getAttribute('data-type-id');

    const host = script.src.split('/embed/')[0];
    const baseUrl = host + '/booking.php' + (typeId ? '?type_id=' + typeId : '');

    let container = document.getElementById(containerId);
    if (!container) {
        container = document.createElement('div');
        container.id = containerId;
        script.parentNode.insertBefore(container, script);
    }

    const iframe = document.createElement('iframe');
    iframe.src = baseUrl;
    iframe.style.width = '100%';
    iframe.style.minHeight = '900px';
    iframe.style.border = 'none';
    iframe.style.overflow = 'auto';
    iframe.style.borderRadius = '12px';
    iframe.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';

    container.appendChild(iframe);
})();
