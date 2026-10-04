/* Memory Pages WordPress Public JS Handlers */
jQuery(document).ready(function($) {
    // Candle lighting AJAX
    $(document).on('click', '.mp-light-candle-btn', function(e) {
        e.preventDefault();
        var pageId = $(this).data('page-id');
        var btn = $(this);

        $.post(MemoryPagesObj.ajax_url, {
            action: 'mp_light_candle',
            nonce: MemoryPagesObj.nonce,
            page_id: pageId
        }, function(response) {
            if (response.success) {
                $('.mp-candle-count-num').text(response.data.count);
                alert('🕯️ Спасибо! Ваша свеча памяти зажжена.');
            } else {
                alert(response.data || 'Ошибка зажигания свечи');
            }
        });
    });

    // Condolence submit AJAX
    $(document).on('submit', '.mp-condolence-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = form.serialize() + '&action=mp_add_condolence&nonce=' + MemoryPagesObj.nonce;

        $.post(MemoryPagesObj.ajax_url, formData, function(response) {
            if (response.success) {
                alert('🕊️ Ваше соболезнование успешно добавлено.');
                location.reload();
            } else {
                alert(response.data || 'Ошибка отправки соболезнования');
            }
        });
    });
});
