<?php
namespace Hop\Core;

class DemoDataLoader {
    public function load(JsonStore $svcStore, JsonStore $userStore, JsonStore $reqStore, JsonStore $tmplStore): void {
        // 1. Services
        $services = [
            ['id' => 1, 'name' => 'Техническая служба (Сантехника/Электрика)'],
            ['id' => 2, 'name' => 'ИТ-отдел'],
            ['id' => 3, 'name' => 'Хозяйственная служба (Мебель/Уборка)'],
            ['id' => 4, 'name' => 'Медтехника']
        ];
        $svcStore->save($services);

        // 2. Users
        $users = [
            ['id' => 1, 'name' => 'Администратор', 'role' => 'admin', 'code' => '1111', 'service_id' => null],
            ['id' => 2, 'name' => 'Иванов Иван (Медсестра)', 'role' => 'initiator', 'code' => '2222', 'service_id' => null],
            ['id' => 3, 'name' => 'Петров Петр (Электрик)', 'role' => 'performer', 'code' => '2222', 'service_id' => 1],
            ['id' => 4, 'name' => 'Сидоров Сидор (ИТ-специалист)', 'role' => 'performer', 'code' => '3333', 'service_id' => 2],
            ['id' => 5, 'name' => 'Козлов К. (Завхоз)', 'role' => 'service_lead', 'code' => '4444', 'service_id' => 1],
            ['id' => 6, 'name' => 'Смирнова С. (Контролер)', 'role' => 'controller', 'code' => '5555', 'service_id' => null],
            ['id' => 7, 'name' => 'Главврач', 'role' => 'manager', 'code' => '7777', 'service_id' => null],
        ];
        $userStore->save($users);

        // 3. Templates
        $templates = [
            ['id' => 1, 'title' => 'Протечка крана', 'description' => 'В палате капает кран, требуется замена прокладки.', 'service_id' => 1],
            ['id' => 2, 'title' => 'Не работает ПК', 'description' => 'Компьютер не включается, черный экран.', 'service_id' => 2],
            ['id' => 3, 'title' => 'Сломана кровать', 'description' => 'Механизм регулировки высоты кровати заклинило.', 'service_id' => 3],
        ];
        $tmplStore->save($templates);

        // 4. Requests
        $requests = [
            [
                'id' => 1,
                'initiator_id' => 2,
                'description' => 'Замена лампочки в коридоре 2 этажа',
                'service_id' => 1,
                'location' => ['building' => 'A', 'floor' => '2', 'room' => 'Коридор'],
                'priority' => 'medium',
                'status' => 'completed',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'performer_id' => 3,
                'history' => [
                    ['timestamp' => date('Y-m-d H:i:s', strtotime('-2 days')), 'status' => 'new', 'user_id' => 2, 'comment' => 'Создана'],
                    ['timestamp' => date('Y-m-d H:i:s', strtotime('-2 days +1h')), 'status' => 'assigned', 'user_id' => 5, 'comment' => 'Назначен Петров'],
                    ['timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')), 'status' => 'completed', 'user_id' => 3, 'comment' => 'Заменено']
                ]
            ],
            [
                'id' => 2,
                'initiator_id' => 2,
                'description' => 'Протечка крана в 204 палате',
                'service_id' => 1,
                'location' => ['building' => 'Корп. А', 'floor' => '2', 'room' => '204'],
                'priority' => 'high',
                'status' => 'new',
                'created_at' => date('Y-m-d H:i:s'),
                'history' => [
                    ['timestamp' => date('Y-m-d H:i:s'), 'status' => 'new', 'user_id' => 2, 'comment' => 'Создана через шаблон']
                ]
            ]
        ];
        $reqStore->save($requests);
    }
}
