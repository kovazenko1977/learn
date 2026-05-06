(function() {
    const script = document.currentScript;
    const containerId = script.getAttribute('data-container') || 'sanatorium-booking-root';
    const baseUrl = script.src.split('/embed/')[0] + '/index.php';

    let container = document.getElementById(containerId);
    if (!container) {
        container = document.createElement('div');
        container.id = containerId;
        script.parentNode.insertBefore(container, script);
    }

    const iframe = document.createElement('iframe');
    iframe.src = baseUrl;
    iframe.style.width = '100%';
    iframe.style.minHeight = '800px';
    iframe.style.border = 'none';
    iframe.style.overflow = 'auto';
    iframe.style.borderRadius = '8px';
    iframe.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';

    container.appendChild(iframe);
})();
