jQuery(document).ready(function($) {

    // Flower Action
    $('#mp-public-flower-btn').on('click', function(e) {
        e.preventDefault();
        var memorial_id = $(this).data('id');
        $.post(mp_public_opts.ajax_url, {
            action: 'mp_public_flower',
            nonce: mp_public_opts.nonce,
            memorial_id: memorial_id
        }, function(res) {
            if (res.success) {
                $('#mp-flower-count').text(res.data.count);
                alert('Спасибо! Вы возложили цветок.');
            }
        });
    });

    // Candle Action
    $('#mp-public-candle-btn').on('click', function(e) {
        e.preventDefault();
        var memorial_id = $(this).data('id');
        $.post(mp_public_opts.ajax_url, {
            action: 'mp_public_candle',
            nonce: mp_public_opts.nonce,
            memorial_id: memorial_id
        }, function(res) {
            if (res.success) {
                $('#mp-candle-count').text(res.data.count);
                alert('Спасибо! Вы зажгли свечу памяти.');
            }
        });
    });

    // Toggle Contacts Visibility
    $('#mp-toggle-contacts-btn').on('click', function(e) {
        e.preventDefault();
        var memorial_id = $(this).data('id');
        $('.mp-contacts-hidden-content').slideToggle();

        $.post(mp_public_opts.ajax_url, {
            action: 'mp_public_contact_open',
            nonce: mp_public_opts.nonce,
            memorial_id: memorial_id
        });
    });

    // Share Action
    $('#mp-public-share-btn').on('click', function(e) {
        e.preventDefault();
        var memorial_id = $(this).data('id');
        if (navigator.share) {
            navigator.share({
                title: document.title,
                url: window.location.href
            });
        } else {
            navigator.clipboard.writeText(window.location.href);
            alert('Ссылка скопирована в буфер обмена!');
        }

        $.post(mp_public_opts.ajax_url, {
            action: 'mp_public_share',
            nonce: mp_public_opts.nonce,
            memorial_id: memorial_id
        });
    });

});
