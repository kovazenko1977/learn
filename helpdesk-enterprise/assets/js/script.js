jQuery(document).ready(function($) {
    // Create request
    $(document).on('submit', '#hd-create-form', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'hd_create_request');
        formData.append('nonce', hd_vars.nonce);

        $.ajax({
            url: hd_vars.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data);
                }
            }
        });
    });

    // View request details
    $(document).on('click', '.hd-view-request', function() {
        var id = $(this).data('id');
        $.post(hd_vars.ajax_url, {
            action: 'hd_get_request_details',
            id: id,
            nonce: hd_vars.nonce
        }, function(response) {
            if (response.success) {
                $('#hd-request-details').html(response.data.html);
                $('#hd-modal').show();
            } else {
                alert(response.data);
            }
        });
    });

    // Close modal
    $(document).on('click', '.hd-close', function() {
        $('#hd-modal').hide();
    });

    // Save user settings
    $(document).on('submit', '#hd-user-settings-form', function(e) {
        e.preventDefault();
        var data = $(this).serialize() + '&action=hd_save_user_settings&nonce=' + hd_vars.nonce;
        $.post(hd_vars.ajax_url, data, function(response) {
            if (response.success) {
                alert('Настройки сохранены');
            }
        });
    });

    // Logout
    $(document).on('click', '#hd-logout-btn', function() {
        $.post(hd_vars.ajax_url, { action: 'hd_logout' }, function() {
            location.reload();
        });
    });

    // Add comment
    $(document).on('click', '#hd-submit-comment', function() {
        var id = $(this).data('id');
        var content = $('#hd-comment-content').val();
        if (!content) return;

        $.post(hd_vars.ajax_url, {
            action: 'hd_add_comment',
            request_id: id,
            content: content,
            nonce: hd_vars.nonce
        }, function(response) {
            if (response.success) {
                // Refresh details
                $('.hd-view-request[data-id="' + id + '"]').click();
            }
        });
    });

    // Update status
    $(document).on('click', '#hd-update-status-btn', function() {
        var select = $('#hd-status-change');
        var id = select.data('id');
        var status = select.val();

        $.post(hd_vars.ajax_url, {
            action: 'hd_update_status',
            request_id: id,
            status: status,
            nonce: hd_vars.nonce
        }, function(response) {
            if (response.success) {
                alert('Статус обновлен');
                location.reload();
            }
        });
    });

    // Assign executor
    $(document).on('click', '#hd-assign-executor-btn', function() {
        var select = $('#hd-executor-change');
        var id = select.data('id');
        var executorId = select.val();

        $.post(hd_vars.ajax_url, {
            action: 'hd_assign_executor',
            request_id: id,
            executor_id: executorId,
            nonce: hd_vars.nonce
        }, function(response) {
            if (response.success) {
                alert('Исполнитель назначен');
                location.reload();
            }
        });
    });

    // Update deadline
    $(document).on('click', '#hd-update-deadline-btn', function() {
        var id = $(this).data('id');
        var deadline = $('#hd-deadline-change').val();

        $.post(hd_vars.ajax_url, {
            action: 'hd_update_deadline',
            request_id: id,
            deadline: deadline,
            nonce: hd_vars.nonce
        }, function(response) {
            if (response.success) {
                alert('Срок изменен');
                location.reload();
            }
        });
    });

    // Delete photo
    // Add photo to existing request
    $(document).on('submit', '#hd-add-photo-form', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'hd_add_photo');
        formData.append('nonce', hd_vars.nonce);
        var requestId = $(this).find('input[name="request_id"]').val();

        $.ajax({
            url: hd_vars.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('.hd-view-request[data-id="' + requestId + '"]').click();
                } else {
                    alert('Ошибка при загрузке фото');
                }
            }
        });
    });

    $(document).on('click', '.hd-delete-photo', function() {
        if (!confirm('Удалить это фото?')) return;
        var id = $(this).data('id');
        var requestId = $(this).data('request-id');

        $.post(hd_vars.ajax_url, {
            action: 'hd_delete_photo',
            id: id,
            nonce: hd_vars.nonce
        }, function(response) {
            if (response.success) {
                $('.hd-view-request[data-id="' + requestId + '"]').click();
            }
        });
    });

    // Delete request
    $(document).on('click', '#hd-delete-request-btn', function() {
        if (!confirm('Удалить заявку навсегда? Это действие необратимо.')) return;
        var id = $(this).data('id');

        $.post(hd_vars.ajax_url, {
            action: 'hd_delete_request',
            id: id,
            nonce: hd_vars.nonce
        }, function(response) {
            if (response.success) {
                alert('Заявка удалена');
                location.reload();
            }
        });
    });

    // Full Reset Logic (v7.0)
    $(document).on('click', '#hd-full-reset-btn', function() {
        if (!confirm('ВНИМАНИЕ! Это действие удалит ВСЕ таблицы и данные системы. Продолжить?')) return;
        if (!confirm('ПОСЛЕДНЕЕ ПРЕДУПРЕЖДЕНИЕ! Все настройки, пользователи и заявки будут стерты. Вы точно уверены?')) return;

        var $btn = $(this);
        var $container = $('#hd-reset-progress-container');
        var $status = $('#hd-reset-status');
        var $percent = $('#hd-reset-percent');
        var $bar = $('#hd-reset-bar');

        $btn.hide();
        $container.show();

        const steps = [
            { id: 'drop', label: 'Удаление всех таблиц...', weight: 33 },
            { id: 'create', label: 'Пересоздание структуры БД...', weight: 66 },
            { id: 'init', label: 'Инициализация системы...', weight: 100 }
        ];

        let currentStep = 0;

        function runStep() {
            if (currentStep >= steps.length) {
                $status.text('Обнуление завершено! Перенаправление...');
                setTimeout(function() {
                    location.reload();
                }, 2000);
                return;
            }

            var step = steps[currentStep];
            $status.text(step.label);
            $bar.css('width', step.weight + '%');
            $percent.text(step.weight + '%');

            $.post(hd_vars.ajax_url, {
                action: 'hd_full_reset',
                step: step.id,
                nonce: hd_vars.nonce
            }, function(response) {
                if (response.success) {
                    currentStep++;
                    setTimeout(runStep, 800); // Small delay for visual effect
                } else {
                    $status.text('Ошибка: ' + response.data);
                    $status.css('color', 'var(--hd-danger)');
                    $bar.css('background', 'var(--hd-danger)');
                }
            });
        }

        runStep();
    });
});
