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

        // 2. Users (Generating 30 users)
        $users = [];
        $users[] = ['id' => 1, 'name' => 'Администратор', 'role' => 'admin', 'code' => '1111', 'service_id' => null];

        $firstNames = ['Иван', 'Петр', 'Сергей', 'Алексей', 'Дмитрий', 'Андрей', 'Николай', 'Михаил', 'Александр', 'Виктор'];
        $lastNames = ['Иванов', 'Петров', 'Сидоров', 'Кузнецов', 'Попов', 'Васильев', 'Соколов', 'Михайлов', 'Новиков', 'Федоров'];

        // 10 Initiators
        for ($i = 2; $i <= 11; $i++) {
            $users[] = [
                'id' => $i,
                'name' => $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)],
                'role' => 'initiator',
                'code' => (string)(2000 + $i),
                'service_id' => null
            ];
        }

        // 12 Performers (3 per service)
        for ($i = 12; $i <= 23; $i++) {
            $svcId = (($i - 12) % 4) + 1;
            $users[] = [
                'id' => $i,
                'name' => $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)],
                'role' => 'performer',
                'code' => (string)(3000 + $i),
                'service_id' => $svcId
            ];
        }

        // 4 Service Leads
        for ($i = 24; $i <= 27; $i++) {
            $svcId = $i - 23;
            $users[] = [
                'id' => $i,
                'name' => $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)],
                'role' => 'service_lead',
                'code' => (string)(4000 + $i),
                'service_id' => $svcId
            ];
        }

        // 2 Controllers
        for ($i = 28; $i <= 29; $i++) {
            $users[] = [
                'id' => $i,
                'name' => $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)],
                'role' => 'controller',
                'code' => (string)(5000 + $i),
                'service_id' => null
            ];
        }

        // 1 Manager
        $users[] = ['id' => 30, 'name' => 'Главврач', 'role' => 'manager', 'code' => '7777', 'service_id' => null];

        $userStore->save($users);

        // 3. Templates
        $templates = [
            ['id' => 1, 'title' => 'Протечка крана', 'description' => 'В палате капает кран, требуется замена прокладки.', 'service_id' => 1],
            ['id' => 2, 'title' => 'Не работает ПК', 'description' => 'Компьютер не включается, черный экран.', 'service_id' => 2],
            ['id' => 3, 'title' => 'Сломана кровать', 'description' => 'Механизм регулировки высоты кровати заклинило.', 'service_id' => 3],
            ['id' => 4, 'title' => 'Замена картриджа', 'description' => 'Закончился тонер в принтере.', 'service_id' => 2],
            ['id' => 5, 'title' => 'Ремонт ИВЛ', 'description' => 'Ошибка датчика потока на аппарате ИВЛ.', 'service_id' => 4],
        ];
        $tmplStore->save($templates);

        // 4. Requests (Generating 120 requests)
        $requests = [];
        $statuses = ['new', 'assigned', 'working', 'checking', 'returned', 'completed', 'closed'];
        $priorities = ['low', 'medium', 'high', 'critical'];
        $problems = [
            'Не работает розетка', 'Протечка трубы', 'Сломался стул', 'Нужен картридж',
            'Ошибка в программе', 'Плохо греет батарея', 'Перегорела лампа', 'Засор в раковине',
            'Скрипит дверь', 'Оторвался плинтус', 'Нужна уборка после ремонта', 'Не работает лифт'
        ];

        for ($i = 1; $i <= 120; $i++) {
            $initiator = $users[array_rand(array_slice($users, 1, 10))];
            $svcId = rand(1, 4);
            $priority = $priorities[array_rand($priorities)];
            $status = $statuses[array_rand($statuses)];
            $createdAt = date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days -' . rand(0, 23) . ' hours'));

            $req = [
                'id' => $i,
                'initiator_id' => $initiator['id'],
                'description' => $problems[array_rand($problems)] . ' (Заявка №' . $i . ')',
                'service_id' => $svcId,
                'location' => [
                    'building' => ['A', 'B', 'C'][rand(0, 2)],
                    'floor' => (string)rand(1, 5),
                    'room' => (string)rand(100, 599)
                ],
                'priority' => $priority,
                'status' => $status,
                'created_at' => $createdAt,
                'history' => [
                    ['timestamp' => $createdAt, 'status' => 'new', 'user_id' => $initiator['id'], 'comment' => 'Создана']
                ]
            ];

            if ($status !== 'new') {
                // Assign a performer for this service
                $possiblePerformers = array_filter($users, fn($u) => $u['role'] === 'performer' && $u['service_id'] === $svcId);
                if (!empty($possiblePerformers)) {
                    $performer = $possiblePerformers[array_rand($possiblePerformers)];
                    $req['performer_id'] = $performer['id'];
                    $req['history'][] = [
                        'timestamp' => date('Y-m-d H:i:s', strtotime($createdAt . ' + ' . rand(1, 5) . ' hours')),
                        'status' => 'assigned',
                        'user_id' => 23 + $svcId, // Service Lead
                        'comment' => 'Назначен исполнитель ' . $performer['name']
                    ];
                }
            }

            if (in_array($status, ['working', 'checking', 'completed', 'closed'])) {
                $req['history'][] = [
                    'timestamp' => date('Y-m-d H:i:s', strtotime($createdAt . ' + ' . rand(6, 12) . ' hours')),
                    'status' => 'working',
                    'user_id' => $req['performer_id'] ?? 12,
                    'comment' => 'Принято в работу'
                ];
            }

            if (in_array($status, ['checking', 'completed', 'closed'])) {
                $req['history'][] = [
                    'timestamp' => date('Y-m-d H:i:s', strtotime($createdAt . ' + ' . rand(13, 24) . ' hours')),
                    'status' => 'checking',
                    'user_id' => $req['performer_id'] ?? 12,
                    'comment' => 'Работы выполнены, прошу проверить'
                ];
            }

            if (in_array($status, ['completed', 'closed'])) {
                $req['history'][] = [
                    'timestamp' => date('Y-m-d H:i:s', strtotime($createdAt . ' + ' . rand(25, 48) . ' hours')),
                    'status' => 'completed',
                    'user_id' => rand(28, 29), // Controller
                    'comment' => 'Проверка пройдена успешно'
                ];
            }

            if ($status === 'closed') {
                $req['history'][] = [
                    'timestamp' => date('Y-m-d H:i:s', strtotime($createdAt . ' + ' . rand(49, 72) . ' hours')),
                    'status' => 'closed',
                    'user_id' => rand(28, 29),
                    'comment' => 'Закрыто в архив'
                ];
            }

            $requests[] = $req;
        }

        $reqStore->save($requests);
    }
}
