<?php

declare(strict_types=1);

namespace App\Modules\PDF;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/pdf/generate/{type}/{id}', [$this, 'generate']);
    }

    public function generate($request, $response, $type, $id): void
    {
        $storage = $this->container->get(\App\Storage\StorageManager::class);

        $template = "
            <div style='font-family: sans-serif; padding: 40px; border: 1px solid #eee;'>
                <h1 style='text-align: center;'>{{TITLE}}</h1>
                <p style='text-align: right;'>Дата: {{DATE}}</p>
                <hr>
                <div style='margin-top: 30px;'>
                    {{CONTENT}}
                </div>
                <div style='margin-top: 50px; text-align: right;'>
                    <p>Подпись: _________________</p>
                    <p style='font-size: 10px; color: #888;'>Сгенерировано в системе Sanatorium 2.0</p>
                </div>
            </div>
        ";

        $data = [];
        if ($type === 'booking') {
            $booking = $storage->findOne('bookings', ['id' => $id]);
            if ($booking) {
                $data = [
                    '{{TITLE}}' => 'Договор на оказание услуг проживания №' . $booking['id'],
                    '{{DATE}}' => date('d.m.Y'),
                    '{{CONTENT}}' => "
                        <p>Исполнитель: Санаторий 'Солнечный'</p>
                        <p>Заказчик: <strong>{$booking['guest_name']}</strong></p>
                        <p>Номер: {$booking['room_number']}</p>
                        <p>Период: с {$booking['date_from']} по {$booking['date_to']}</p>
                        <br>
                        <p>Настоящий документ подтверждает факт бронирования и размещения гостя.</p>
                    "
                ];
            }
        }

        $html = str_replace(array_keys($data), array_values($data), $template);

        // For demo, we output as HTML with a print trigger
        echo $html;
        echo "<script>window.print();</script>";
    }
}
