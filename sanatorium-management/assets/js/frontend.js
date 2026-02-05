jQuery(document).ready(function($) {
    $('#san-booking-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var messageContainer = $('#san-booking-message');
        var submitButton = form.find('button[type="submit"]');

        submitButton.prop('disabled', true).text('Отправка...');
        messageContainer.hide().removeClass('san-success san-error');

        $.ajax({
            url: san_ajax.ajax_url,
            type: 'POST',
            data: form.serialize() + '&action=san_submit_booking',
            success: function(response) {
                if (response.success) {
                    messageContainer.addClass('san-success').text(response.data.message).show();
                    form[0].reset();
                } else {
                    messageContainer.addClass('san-error').text(response.data.message || 'Произошла ошибка').show();
                }
            },
            error: function() {
                messageContainer.addClass('san-error').text('Ошибка соединения с сервером').show();
            },
            complete: function() {
                submitButton.prop('disabled', false).text('Забронировать');
            }
        });
    });
});
