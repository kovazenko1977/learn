<?php
require_once __DIR__ . '/Storage.php';

class Mailer {
    public static function send($to, $subject, $body) {
        $settings = Storage::read('settings');
        $from = $settings['smtp_from'] ?? 'noreply@' . $_SERVER['HTTP_HOST'];
        $app_name = "Личный кабинет";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: $app_name <$from>" . "\r\n";

        $html_body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e6e6f2; border-radius: 12px;'>
                    <h2 style='color: #7360f2;'>$app_name</h2>
                    <div style='padding: 20px 0;'>$body</div>
                    <hr style='border: 0; border-top: 1px solid #e6e6f2;'>
                    <p style='font-size: 12px; color: #6e6e80;'>
                        Это автоматическое уведомление. Пожалуйста, не отвечайте на него.
                    </p>
                </div>
            </body>
            </html>
        ";

        if (isset($settings['smtp_enabled']) && $settings['smtp_enabled']) {
            return @mail($to, $subject, $html_body, $headers);
        }

        return false;
    }

    public static function notifyNewDocument($client_id, $doc_name) {
        $user = Storage::findOne('users', ['id' => $client_id]);
        if (!$user || empty($user['email'])) return;

        $subject = "Новый документ: " . $doc_name;
        $body = "
            <p>Здравствуйте, <b>" . ($user['company_name'] ?: $user['username']) . "</b>!</p>
            <p>В вашем личном кабинете доступен новый документ: <b>$doc_name</b>.</p>
            <p>Вы можете скачать его, перейдя по ссылке:</p>
            <a href='https://" . $_SERVER['HTTP_HOST'] . "' style='display:inline-block; padding:10px 20px; background:#7360f2; color:white; text-decoration:none; border-radius:8px;'>Перейти в кабинет</a>
        ";

        return self::send($user['email'], $subject, $body);
    }

    public static function notifyNewMessage($client_id, $sender_name) {
        $user = Storage::findOne('users', ['id' => $client_id]);
        if (!$user || empty($user['email'])) return;

        $subject = "Новое сообщение в чате";
        $body = "
            <p>Здравствуйте, <b>" . ($user['company_name'] ?: $user['username']) . "</b>!</p>
            <p>У вас новое сообщение от <b>$sender_name</b>.</p>
            <p>Вы можете прочитать его и ответить, перейдя в чат:</p>
            <a href='https://" . $_SERVER['HTTP_HOST'] . "' style='display:inline-block; padding:10px 20px; background:#7360f2; color:white; text-decoration:none; border-radius:8px;'>Открыть чат</a>
        ";

        return self::send($user['email'], $subject, $body);
    }

    public static function notifyNewRegistration($username, $company_name) {
        $settings = Storage::read('settings');
        $admin_email = $settings['admin_notification_email'] ?? '';
        if (!$admin_email) {
            $admins = Storage::read('users');
            foreach ($admins as $adm) {
                if (($adm['role'] ?? '') === 'superadmin' && !empty($adm['email'])) {
                    $admin_email = $adm['email'];
                    break;
                }
            }
        }
        if (!$admin_email) return;

        $subject = "Новая регистрация в личном кабинете";
        $body = "
            <p>В системе зарегистрировался новый пользователь:</p>
            <ul>
                <li>Логин: <b>$username</b></li>
                <li>Компания: <b>$company_name</b></li>
            </ul>
            <p>Пожалуйста, войдите в панель управления, чтобы одобрить или отклонить заявку.</p>
        ";
        return self::send($admin_email, $subject, $body);
    }

    public static function notifyNewOrder($client_name, $order_id, $items_html) {
        $settings = Storage::read('settings');
        $admin_email = $settings['admin_notification_email'] ?? '';
        if (!$admin_email) {
            $admins = Storage::read('users');
            foreach ($admins as $adm) {
                if (($adm['role'] ?? '') === 'superadmin' && !empty($adm['email'])) {
                    $admin_email = $adm['email'];
                    break;
                }
            }
        }
        if (!$admin_email) return;

        $subject = "Новый заказ №" . $order_id;
        $body = "
            <p>Клиент <b>$client_name</b> оформил новый заказ <b>№$order_id</b>.</p>
            <h3>Состав заказа:</h3>
            <table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>
                <thead>
                    <tr style='background: #f4f4f9;'>
                        <th>Наименование</th>
                        <th>Количество</th>
                    </tr>
                </thead>
                <tbody>
                    $items_html
                </tbody>
            </table>
            <p>Просмотреть детали заказа можно в личном кабинете.</p>
        ";
        return self::send($admin_email, $subject, $body);
    }
}
