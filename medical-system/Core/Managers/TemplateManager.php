<?php
namespace Medical\Core\Managers;

use Medical\Core\JsonStore;

class TemplateManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('templates');
        if (empty($this->store->getAll())) {
            $this->initDefaults();
        }
    }

    public function getAll() {
        return $this->store->getAll();
    }

    public function get($id) {
        $all = $this->getAll();
        return $all[$id] ?? '';
    }

    public function save($id, $content) {
        $all = $this->getAll();
        $all[$id] = $content;
        return $this->store->save($all);
    }

    private function initDefaults() {
        $defaults = [
            'schedule' => '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Карта процедур - {{patient_name}}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        body { font-family: \'Segoe UI\', Tahoma, sans-serif; color: #333; line-height: 1.4; }
        .header { border-bottom: 3px solid #0078d4; padding-bottom: 10px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; }
        .header h1 { margin: 0; color: #0078d4; font-size: 24pt; }
        .patient-info { font-size: 12pt; }
        .day-section { margin-bottom: 25px; break-inside: avoid; }
        .day-title { background: #f3f3f3; padding: 8px 15px; font-weight: bold; border-left: 5px solid #0078d4; margin-bottom: 10px; font-size: 14pt; }
        .proc-item { padding: 8px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .proc-item:last-child { border-bottom: none; }
        .proc-time { font-weight: 600; color: #0078d4; font-size: 12pt; }
        .proc-name { font-weight: 500; }
        .proc-cabinet { text-align: right; color: #666; }
        .footer { margin-top: 50px; font-size: 9pt; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <div>
            <h1>Карта процедур</h1>
            <div class="patient-info">Пациент: <strong>{{patient_name}}</strong></div>
        </div>
        <div style="text-align: right; font-size: 10pt;">
            {{org_name}}<br>
            Дата: {{print_date}}
        </div>
    </div>

    {{content}}

    <div class="footer">
        Пожалуйста, приходите за 5 минут до начала процедуры. Желаем приятного отдыха и скорейшего выздоровления!
    </div>
</body>
</html>',
            'contract' => '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Договор на платные услуги</title>
    <style>
        body { font-family: serif; line-height: 1.6; padding: 50px; }
        .title { text-align: center; font-weight: bold; margin-bottom: 20px; }
    </style>
</head>
<body onload="window.print()">
    <div class="title">ДОГОВОР № {{id}} ОБ ОКАЗАНИИ ПЛАТНЫХ МЕДИЦИНСКИХ УСЛУГ</div>
    <p>г. {{org_city}}, "{{day}}" {{month}} {{year}} г.</p>
    <p>{{org_name}}, именуемый в дальнейшем "Исполнитель", с одной стороны, и
       <strong>{{patient_name}}</strong>, именуемый в дальнейшем "Заказчик", с другой стороны, заключили настоящий договор...</p>
    <p><strong>Предмет договора:</strong> Оказание услуги "{{procedure_name}}".</p>
    <p><strong>Стоимость услуги:</strong> {{price}} ₽.</p>

    <div style="margin-top: 50px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
        <div>
            <strong>Исполнитель:</strong><br>
            {{org_name}}<br>
            Адрес: {{org_address}}<br>
            УНП/ИНН: {{org_unp}}<br>
            Банк: {{org_bank}}<br>
            Р/с: {{org_account}}<br><br>
            ___________ / {{org_director}} /
        </div>
        <div>
            <strong>Заказчик:</strong><br>
            {{patient_name}}<br><br><br>
            ___________ / {{patient_name}} /
        </div>
    </div>
</body>
</html>',
            'epicrisis' => '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Выписной эпикриз - {{patient_name}}</title>
    <style>
        body { font-family: sans-serif; padding: 50px; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #000; margin-bottom: 30px; }
        h2 { margin-top: 30px; border-bottom: 1px solid #ccc; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>ВЫПИСНОЙ ЭПИКРИЗ</h1>
        <p>{{org_name}}</p>
    </div>

    <p><strong>Пациент:</strong> {{patient_name}}</p>
    <p><strong>Дата рождения:</strong> {{birth_date}}</p>
    <p><strong>Период пребывания:</strong> {{stay_start}} — {{stay_end}}</p>

    <h2>Диагноз при выписке</h2>
    <p>{{diagnosis}}</p>

    <h2>Проведенное лечение</h2>
    <ul>
        {{treatment_list}}
    </ul>

    <h2>Рекомендации</h2>
    <p>Рекомендовано наблюдение у врача по месту жительства, продолжение курса лечебной физкультуры, рациональное питание.</p>

    <div style="margin-top: 50px; display: flex; justify-content: space-between;">
        <div>Лечащий врач: _______________</div>
        <div>М.П.</div>
    </div>
</body>
</html>'
        ];
        $this->store->save($defaults);
    }
}
