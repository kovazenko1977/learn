<?php
/**
 * Frontend Booking Widget Template
 * Multi-step cascading layout:
 * Step 1: Select Month -> Select Date -> Select Time Slot
 * Step 2: Fill Custom Fields (Form Builder) & Personal Details
 * Step 3: Confirmation Ticket with QR code & Add to Calendar
 */

if (!defined('ABSPATH') && !defined('WPEB_STANDALONE')) {
    exit;
}

$primaryColor = $settings['primary_color'] ?? '#3b82f6';
$borderRadius = ($settings['border_radius'] ?? '12') . 'px';
$customCss = $settings['custom_css'] ?? '';
?>

<style>
:root {
  --wpeb-primary: <?php echo esc_attr($primaryColor); ?>;
  --wpeb-radius: <?php echo esc_attr($borderRadius); ?>;
}
<?php if (!empty($customCss)) { echo $customCss; } ?>
</style>

<div class="wpeb-wrapper" data-event-id="<?php echo esc_attr($event['id']); ?>">
    <!-- Event Banner Header -->
    <div class="wpeb-header">
        <h2 class="wpeb-title"><?php echo esc_html($event['title']); ?></h2>
        <div class="wpeb-meta">
            <?php if (!empty($event['location'])): ?>
                <span class="wpeb-meta-item">📍 <?php echo esc_html($event['location']); ?></span>
            <?php endif; ?>
            <span class="wpeb-meta-item wpeb-price-tag">
                💰 <?php echo $event['price'] > 0 ? esc_html($event['price'] . ' ' . $event['currency']) : 'Бесплатно'; ?>
            </span>
        </div>
    </div>

    <!-- Multi-Step Indicators -->
    <div class="wpeb-steps-bar">
        <div class="wpeb-step-tab active" data-step="1">
            <span class="wpeb-step-num">1</span>
            <span>Дата и время</span>
        </div>
        <div class="wpeb-step-tab" data-step="2">
            <span class="wpeb-step-num">2</span>
            <span>Данные гостя</span>
        </div>
        <div class="wpeb-step-tab" data-step="3">
            <span class="wpeb-step-num">3</span>
            <span>Билет</span>
        </div>
    </div>

    <div class="wpeb-body">
        <!-- STEP 1: CASCADING DATE & TIME PICKER -->
        <div class="wpeb-step-panel" data-step="1">
            <!-- 1. Month Picker -->
            <div class="wpeb-section-title">
                <span>📅 Шаг 1: Выберите месяц</span>
            </div>
            <div class="wpeb-month-grid" id="wpebMonthList">
                <!-- Dynamically populated via AJAX -->
            </div>

            <!-- 2. Date Picker -->
            <div id="wpebDateSection" class="wpeb-date-container" style="display:none;">
                <div class="wpeb-section-title">
                    <span>🗓 Шаг 2: Выберите дату</span>
                </div>
                <div class="wpeb-date-grid" id="wpebDateList">
                    <!-- Dynamically populated via AJAX -->
                </div>
            </div>

            <!-- 3. Time Slots Picker -->
            <div id="wpebTimeSection" class="wpeb-time-container" style="display:none;">
                <div class="wpeb-section-title">
                    <span>⏰ Шаг 3: Выберите свободный сеанс</span>
                </div>
                <div class="wpeb-time-grid" id="wpebTimeList">
                    <!-- Dynamically populated via AJAX; full slots are hidden automatically -->
                </div>
            </div>

            <div class="wpeb-actions">
                <div></div>
                <button type="button" class="wpeb-btn wpeb-btn-primary" id="wpebToFormBtn" disabled>
                    Продолжить запись →
                </button>
            </div>
        </div>

        <!-- STEP 2: USER DETAILS & CUSTOM FIELDS FORM -->
        <div class="wpeb-step-panel" id="wpebStepForm" data-step="2" style="display:none;">
            <div class="wpeb-section-title">
                <span>📝 Заполнение анкеты участника</span>
            </div>

            <!-- Summary pill -->
            <div style="background:#f1f5f9; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <strong>Выбранное время: </strong>
                    <span id="wpebSummaryDate"></span> в <span id="wpebSummaryTime" style="font-weight:700; color:var(--wpeb-primary);"></span>
                </div>
                <button type="button" class="wpeb-btn wpeb-btn-secondary" id="wpebBackToDateTimeBtn" style="padding:6px 12px; font-size:12px;">Изменить</button>
            </div>

            <div id="wpebFormError" style="display:none; background:#fee2e2; border:1px solid #f87171; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:16px;"></div>

            <form id="wpebBookingForm">
                <div class="wpeb-form-grid">
                    <!-- Basic contact fields -->
                    <div class="wpeb-form-group">
                        <label class="wpeb-label">Ваше Имя и Фамилия <span class="wpeb-required">*</span></label>
                        <input type="text" name="client_name" class="wpeb-input" placeholder="Иван Иванов" required />
                    </div>

                    <div class="wpeb-form-group">
                        <label class="wpeb-label">Контактный телефон <span class="wpeb-required">*</span></label>
                        <input type="tel" name="client_phone" class="wpeb-input" placeholder="+7 (999) 000-00-00" required />
                    </div>

                    <div class="wpeb-form-group">
                        <label class="wpeb-label">Электронная почта <span class="wpeb-required">*</span></label>
                        <input type="email" name="client_email" class="wpeb-input" placeholder="ivan@example.com" required />
                    </div>

                    <div class="wpeb-form-group">
                        <label class="wpeb-label">Количество мест (билетов)</label>
                        <div class="wpeb-qty-box">
                            <button type="button" class="wpeb-qty-btn" id="wpebQtyMinus">-</button>
                            <span class="wpeb-qty-display" id="wpebQtyVal">1</span>
                            <button type="button" class="wpeb-qty-btn" id="wpebQtyPlus">+</button>
                            <input type="hidden" name="ticket_count" id="wpebTicketCount" value="1" />
                        </div>
                    </div>

                    <!-- DYNAMIC FIELDS FROM FORM BUILDER -->
                    <?php if (!empty($fields)): ?>
                        <?php foreach ($fields as $field):
                            $req = !empty($field['is_required']);
                            $key = 'custom_fields[' . esc_attr($field['field_key']) . ']';
                            $colClass = in_array($field['field_type'], ['textarea', 'radio', 'checkbox']) ? 'wpeb-form-col-full' : '';
                        ?>
                            <div class="wpeb-form-group <?php echo $colClass; ?>">
                                <label class="wpeb-label">
                                    <?php echo esc_html($field['field_label']); ?>
                                    <?php if ($req): ?><span class="wpeb-required">*</span><?php endif; ?>
                                </label>

                                <?php if ($field['field_type'] === 'text'): ?>
                                    <input type="text" name="<?php echo $key; ?>" class="wpeb-input" placeholder="<?php echo esc_attr($field['placeholder']); ?>" <?php echo $req ? 'required' : ''; ?> />

                                <?php elseif ($field['field_type'] === 'number'): ?>
                                    <input type="number" name="<?php echo $key; ?>" class="wpeb-input" placeholder="<?php echo esc_attr($field['placeholder']); ?>" <?php echo $req ? 'required' : ''; ?> />

                                <?php elseif ($field['field_type'] === 'textarea'): ?>
                                    <textarea name="<?php echo $key; ?>" class="wpeb-textarea" placeholder="<?php echo esc_attr($field['placeholder']); ?>" <?php echo $req ? 'required' : ''; ?>></textarea>

                                <?php elseif ($field['field_type'] === 'select'):
                                    $opts = array_filter(array_map('trim', explode("\n", $field['field_options'] ?? '')));
                                ?>
                                    <select name="<?php echo $key; ?>" class="wpeb-select" <?php echo $req ? 'required' : ''; ?>>
                                        <option value="">-- Выберите вариант --</option>
                                        <?php foreach ($opts as $opt): ?>
                                            <option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                <?php elseif ($field['field_type'] === 'radio'):
                                    $opts = array_filter(array_map('trim', explode("\n", $field['field_options'] ?? '')));
                                ?>
                                    <div class="wpeb-radio-group">
                                        <?php foreach ($opts as $idx => $opt): ?>
                                            <label class="wpeb-choice-label">
                                                <input type="radio" name="<?php echo $key; ?>" value="<?php echo esc_attr($opt); ?>" <?php echo ($idx === 0 && $req) ? 'checked' : ''; ?> />
                                                <span><?php echo esc_html($opt); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>

                                <?php elseif ($field['field_type'] === 'checkbox'):
                                    $opts = array_filter(array_map('trim', explode("\n", $field['field_options'] ?? '')));
                                ?>
                                    <div class="wpeb-checkbox-group">
                                        <?php foreach ($opts as $opt): ?>
                                            <label class="wpeb-choice-label">
                                                <input type="checkbox" name="<?php echo $key; ?>[]" value="<?php echo esc_attr($opt); ?>" />
                                                <span><?php echo esc_html($opt); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="wpeb-actions">
                    <button type="button" class="wpeb-btn wpeb-btn-secondary" onclick="document.querySelector('#wpebBackToDateTimeBtn').click()">← Назад</button>
                    <button type="submit" class="wpeb-btn wpeb-btn-success" id="wpebSubmitBtn">Подтвердить запись ✓</button>
                </div>
            </form>
        </div>

        <!-- STEP 3: TICKET CONFIRMATION -->
        <div class="wpeb-step-panel" id="wpebStepSuccess" data-step="3" style="display:none;">
            <div class="wpeb-success-box">
                <div class="wpeb-check-icon">✓</div>
                <h3 style="font-size:22px; margin:0 0 10px 0;">Вы успешно записаны!</h3>
                <p style="color:#64748b; margin:0 0 20px 0;">Подтверждение и билет отправлены на ваш электронный адрес.</p>

                <div class="wpeb-ticket-card">
                    <div style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:700;">Электронный билет #</div>
                    <div class="wpeb-ticket-code" id="wpebSuccessCode">A1B2C3D4</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:14px; margin-top:14px;">
                        <div>
                            <span style="color:#64748b; display:block; font-size:12px;">Участник:</span>
                            <strong id="wpebSuccessName"></strong>
                        </div>
                        <div>
                            <span style="color:#64748b; display:block; font-size:12px;">Билетов:</span>
                            <strong id="wpebSuccessTickets">1</strong>
                        </div>
                        <div>
                            <span style="color:#64748b; display:block; font-size:12px;">Дата:</span>
                            <strong id="wpebSuccessDate"></strong>
                        </div>
                        <div>
                            <span style="color:#64748b; display:block; font-size:12px;">Время:</span>
                            <strong id="wpebSuccessTime" style="color:var(--wpeb-primary);"></strong>
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:center; gap:12px; margin-top:20px; flex-wrap:wrap;">
                    <a href="#" target="_blank" class="wpeb-btn wpeb-btn-secondary" id="wpebGoogleCalBtn">📅 Добавить в Google Календарь</a>
                    <button type="button" class="wpeb-btn wpeb-btn-primary" onclick="window.print()">🖨 Распечатать талон</button>
                </div>
            </div>
        </div>
    </div>
</div>
