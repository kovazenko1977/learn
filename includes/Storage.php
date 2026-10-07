<?php

class Storage {
    private string $dataDir;

    public function __construct(?string $dataDir = null) {
        $this->dataDir = $dataDir ?? __DIR__ . '/../data';
        if (!file_exists($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
        $this->ensureHtaccess();
        $this->seedDefaultDataIfNeeded();
    }

    private function ensureHtaccess(): void {
        $htaccessPath = $this->dataDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Order deny,allow\nDeny from all\n");
        }
    }

    private function getFilePath(string $filename): string {
        return $this->dataDir . '/' . $filename;
    }

    private function readJson(string $filename, array $default = []): array {
        $filepath = $this->getFilePath($filename);
        if (!file_exists($filepath)) {
            return $default;
        }

        $fp = fopen($filepath, 'r');
        if (!$fp) {
            return $default;
        }

        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        if (empty($content)) {
            return $default;
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : $default;
    }

    private function writeJson(string $filename, array $data): bool {
        $filepath = $this->getFilePath($filename);
        $fp = fopen($filepath, 'c+');
        if (!$fp) {
            return false;
        }

        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        }

        fclose($fp);
        return false;
    }

    public function getPoints(): array {
        return $this->readJson('points.json', []);
    }

    public function savePoints(array $points): bool {
        return $this->writeJson('points.json', $points);
    }

    public function getPoint(string $id): ?array {
        $points = $this->getPoints();
        foreach ($points as $p) {
            if ($p['id'] === $id) {
                return $p;
            }
        }
        return null;
    }

    public function addPoint(array $point): array {
        $points = $this->getPoints();
        if (empty($point['id'])) {
            $point['id'] = 'pt_' . time() . '_' . rand(100, 999);
        }
        $point['created_at'] = date('Y-m-d H:i:s');
        $points[] = $point;
        $this->savePoints($points);
        return $point;
    }

    public function updatePoint(string $id, array $data): ?array {
        $points = $this->getPoints();
        $updated = null;
        foreach ($points as &$p) {
            if ($p['id'] === $id) {
                $p = array_merge($p, $data, ['id' => $id]);
                $updated = $p;
                break;
            }
        }
        if ($updated) {
            $this->savePoints($points);
        }
        return $updated;
    }

    public function deletePoint(string $id): bool {
        $points = $this->getPoints();
        $filtered = array_filter($points, fn($p) => $p['id'] !== $id);
        if (count($filtered) !== count($points)) {
            return $this->savePoints(array_values($filtered));
        }
        return false;
    }

    public function getVisits(): array {
        return $this->readJson('visits.json', []);
    }

    public function clearAllVisits(): bool {
        return $this->writeJson('visits.json', []);
    }

    public function cancelLatestVisit(string $pointId): bool {
        $visits = $this->getVisits();
        $targetIndex = null;
        $latestTime = null;

        foreach ($visits as $index => $v) {
            if (($v['point_id'] ?? '') === $pointId) {
                $vTime = strtotime($v['visited_at'] ?? '');
                if ($latestTime === null || $vTime > $latestTime) {
                    $latestTime = $vTime;
                    $targetIndex = $index;
                }
            }
        }

        if ($targetIndex !== null) {
            array_splice($visits, $targetIndex, 1);
            return $this->writeJson('visits.json', $visits);
        }

        return false;
    }

    public function addVisit(array $visit): array {
        $visits = $this->getVisits();
        $record = [
            'id' => 'v_' . time() . '_' . rand(100, 999),
            'point_id' => $visit['point_id'] ?? '',
            'sim_number' => $visit['sim_number'] ?? '',
            'point_name' => $visit['point_name'] ?? '',
            'visited_at' => $visit['visited_at'] ?? date('Y-m-d H:i:s'),
            'has_defects' => !empty($visit['has_defects']),
            'defects_description' => $visit['defects_description'] ?? '',
            'notes' => $visit['notes'] ?? '',
            'technician' => $visit['technician'] ?? 'Инженер ТО'
        ];
        array_unshift($visits, $record);
        $this->writeJson('visits.json', $visits);
        return $record;
    }

    public function getSettings(): array {
        return $this->readJson('settings.json', [
            'interval_days' => 30,
            'interval_mode' => 'days', // 'days' or 'calendar_month'
            'technician_name' => 'Иванов Алексей Петрович',
            'company_name' => 'ООО «СпецПожОхрана»',
            'theme' => 'dark'
        ]);
    }

    public function saveSettings(array $settings): bool {
        $current = $this->getSettings();
        $updated = array_merge($current, $settings);
        return $this->writeJson('settings.json', $updated);
    }

    public function seedDefaultDataIfNeeded(): void {
        $pointsFile = $this->getFilePath('points.json');
        if (!file_exists($pointsFile) || count($this->getPoints()) === 0) {
            $defaultPoints = [
                [
                    'id' => 'pt_001',
                    'name' => 'ТЦ «Гранит» — Главный вент узел',
                    'address' => 'ул. Центральная, д. 15',
                    'sim_number' => '+7 (901) 100-20-01',
                    'contract_number' => 'Д-2025/01-А',
                    'equipment_type' => 'Пожарная сигнализация (АПС)',
                    'contact_person' => 'Петров В.С.',
                    'contact_phone' => '+7 (911) 222-33-44',
                    'notes' => 'Доступ через пост охраны, ключ №12'
                ],
                [
                    'id' => 'pt_002',
                    'name' => 'Складской комплекс «Логистик-Юг»',
                    'address' => 'Промзона №3, проезд Индустриальный, д. 8',
                    'sim_number' => '+7 (901) 100-20-02',
                    'equipment_type' => 'Охранная сигнализация и КТС',
                    'contact_person' => 'Сидоров М.И.',
                    'contact_phone' => '+7 (921) 333-44-55',
                    'notes' => 'При въезде предьявить удостоверение'
                ],
                [
                    'id' => 'pt_003',
                    'name' => 'Аптечная сеть «Здоровье» — Офис',
                    'address' => 'просп. Мира, д. 102',
                    'sim_number' => '+7 (901) 100-20-03',
                    'equipment_type' => 'Комплекс АПС и СОУЭ',
                    'contact_person' => 'Егорова Н.В.',
                    'contact_phone' => '+7 (931) 444-55-66',
                    'notes' => 'Рабочее время: 08:00 - 20:00'
                ],
                [
                    'id' => 'pt_004',
                    'name' => 'Детский сад №42 «Сказка»',
                    'address' => 'ул. Лесная, д. 24',
                    'sim_number' => '+7 (901) 100-20-04',
                    'equipment_type' => 'Пожарная сигнализация и молниезащита',
                    'contact_person' => 'Завхоз Анна Павловна',
                    'contact_phone' => '+7 (941) 555-66-77',
                    'notes' => 'Посещение во время тихого часа недопустимо (13:00-15:00)'
                ],
                [
                    'id' => 'pt_005',
                    'name' => 'Бизнес Центр «Авангард»',
                    'address' => 'ул. Советская, д. 50, корпус 2',
                    'sim_number' => '+7 (901) 100-20-05',
                    'equipment_type' => 'Охранно-пожарная система (ОПС)',
                    'contact_person' => 'Диспетчер БЦ',
                    'contact_phone' => '+7 (951) 666-77-88',
                    'notes' => 'Главный пульт на 1 этаже'
                ],
                [
                    'id' => 'pt_006',
                    'name' => 'Автосервис «Форсаж»',
                    'address' => 'Шоссе Энтузиастов, д. 77',
                    'sim_number' => '+7 (901) 100-20-06',
                    'equipment_type' => 'КТС (Кнопка тревожной сигнализации)',
                    'contact_person' => 'Мастер цеха Сергей',
                    'contact_phone' => '+7 (961) 777-88-99',
                    'notes' => 'Проверка КТС с согласованием с дежурным ПЦО'
                ]
            ];
            $this->savePoints($defaultPoints);
        }

        $settingsFile = $this->getFilePath('settings.json');
        if (!file_exists($settingsFile)) {
            $this->getSettings(); // init settings file
        }

        $visitsFile = $this->getFilePath('visits.json');
        if (!file_exists($visitsFile)) {
            // Seed 1-2 historical visits if needed or leave empty
            $this->writeJson('visits.json', [
                [
                    'id' => 'v_sample_1',
                    'point_id' => 'pt_001',
                    'sim_number' => '+7 (901) 100-20-01',
                    'point_name' => 'ТЦ «Гранит» — Главный вент узел',
                    'visited_at' => date('Y-m-d H:i:s', strtotime('-40 days')),
                    'has_defects' => false,
                    'defects_description' => '',
                    'notes' => 'Плановое ТО прошло успешно, датчики очищены',
                    'technician' => 'Иванов Алексей Петрович'
                ]
            ]);
        }
    }
}
