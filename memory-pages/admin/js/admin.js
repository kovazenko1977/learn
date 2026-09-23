jQuery(document).ready(function($) {

    // Save Memorial Form AJAX
    $('#mp-memorial-form').on('submit', function(e) {
        e.preventDefault();
        var formData = {};
        $(this).serializeArray().forEach(function(item) {
            formData[item.name] = item.value;
        });

        // Trigger TinyMCE save if present
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('mp_biography')) {
            formData['biography'] = tinyMCE.get('mp_biography').getContent();
        }

        $.post(mp_admin_opts.ajax_url, {
            action: 'mp_save_memorial',
            nonce: mp_admin_opts.nonce,
            data: formData
        }, function(response) {
            if (response.success) {
                alert('Страница памяти успешно сохранена!');
                if (!formData.id && response.data.id) {
                    window.location.href = 'admin.php?page=memory-pages-add&id=' + response.data.id;
                } else {
                    location.reload();
                }
            } else {
                alert('Ошибка при сохранении: ' + (response.data || 'Неизвестная ошибка'));
            }
        });
    });

    // Main Photo Uploader
    $('#mp-upload-main-photo-btn').on('click', function(e) {
        e.preventDefault();
        var frame = wp.media({
            title: 'Выберите главное фото',
            button: { text: 'Использовать это фото' },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#mp_main_photo_id').val(attachment.id);
            $('#mp_main_photo_url').val(attachment.url);
            $('#mp-main-photo-preview').attr('src', attachment.url).show();
        });

        frame.open();
    });

    // Gallery Add Photos
    $('#mp-add-gallery-photos-btn').on('click', function(e) {
        e.preventDefault();
        var memorial_id = $('#mp_memorial_id').val();
        if (!memorial_id || memorial_id === '0') {
            alert('Сначала сохраните основную информацию мемориала!');
            return;
        }

        var frame = wp.media({
            title: 'Добавить фотографии в галерею',
            button: { text: 'Добавить выбранные' },
            multiple: true
        });

        frame.on('select', function() {
            var selection = frame.state().get('selection');
            selection.each(function(attachment) {
                var json = attachment.toJSON();
                $.post(mp_admin_opts.ajax_url, {
                    action: 'mp_save_photo',
                    nonce: mp_admin_opts.nonce,
                    memorial_id: memorial_id,
                    attachment_id: json.id,
                    photo_url: json.url,
                    caption: json.caption || '',
                    is_main: 0
                }, function(res) {
                    if (res.success) {
                        location.reload();
                    }
                });
            });
        });

        frame.open();
    });

    // Delete Photo
    $(document).on('click', '.mp-delete-photo-btn', function(e) {
        e.preventDefault();
        if (!confirm('Удалить эту фотографию?')) return;
        var photo_id = $(this).data('id');
        $.post(mp_admin_opts.ajax_url, {
            action: 'mp_delete_photo',
            nonce: mp_admin_opts.nonce,
            photo_id: photo_id
        }, function(res) {
            if (res.success) {
                $('#mp-photo-item-' + photo_id).remove();
            }
        });
    });

    // Add Relative AJAX
    $('#mp-save-relative-btn').on('click', function(e) {
        e.preventDefault();
        var memorial_id = $('#mp_memorial_id').val();
        if (!memorial_id || memorial_id === '0') {
            alert('Сначала сохраните основную информацию мемориала!');
            return;
        }

        $.post(mp_admin_opts.ajax_url, {
            action: 'mp_save_relative',
            nonce: mp_admin_opts.nonce,
            memorial_id: memorial_id,
            full_name: $('#mp_rel_name').val(),
            kinship_degree: $('#mp_rel_kinship').val(),
            phone: $('#mp_rel_phone').val(),
            email: $('#mp_rel_email').val(),
            telegram: $('#mp_rel_tg').val(),
            show_contact_to_visitor: $('#mp_rel_show').is(':checked') ? 1 : 0
        }, function(res) {
            if (res.success) {
                alert('Родственник добавлен');
                location.reload();
            }
        });
    });

    // Delete Relative AJAX
    $(document).on('click', '.mp-delete-rel-btn', function(e) {
        e.preventDefault();
        if (!confirm('Удалить родственника?')) return;
        var rel_id = $(this).data('id');
        $.post(mp_admin_opts.ajax_url, {
            action: 'mp_delete_relative',
            nonce: mp_admin_opts.nonce,
            relative_id: rel_id
        }, function(res) {
            if (res.success) {
                $('#mp-rel-row-' + rel_id).remove();
            }
        });
    });

    // Generate QR Code
    $('#mp-generate-qr-btn').on('click', function(e) {
        e.preventDefault();
        var memorial_id = $('#mp_memorial_id').val();
        if (!memorial_id || memorial_id === '0') return;

        $.post(mp_admin_opts.ajax_url, {
            action: 'mp_generate_qr',
            nonce: mp_admin_opts.nonce,
            memorial_id: memorial_id
        }, function(res) {
            if (res.success && res.data.qr_url) {
                $('#mp-qr-container').html('<img src="' + res.data.qr_url + '" width="150" height="150" /><br><a href="' + res.data.qr_url + '" download class="button button-small" style="margin-top:5px;">Скачать QR</a>');
            }
        });
    });

});
