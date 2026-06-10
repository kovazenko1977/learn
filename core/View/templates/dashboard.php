<div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">
    <div class="bg-gradient-to-br from-blue-500 to-blue-700 p-8 rounded-[40px] shadow-2xl shadow-blue-200 text-white relative overflow-hidden">
        <div class="relative z-10">
            <p class="text-xs font-bold uppercase tracking-widest opacity-80 mb-4 text-blue-100">Гости сейчас</p>
            <p class="text-5xl font-black mb-2">1,248</p>
            <p class="text-sm font-bold text-blue-200"><i class="fas fa-arrow-up mr-1"></i> 12% за неделю</p>
        </div>
        <i class="fas fa-users absolute -bottom-4 -right-4 text-8xl opacity-10 rotate-12"></i>
    </div>
    <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 p-8 rounded-[40px] shadow-2xl shadow-emerald-200 text-white relative overflow-hidden">
        <div class="relative z-10">
            <p class="text-xs font-bold uppercase tracking-widest opacity-80 mb-4 text-emerald-100">Свободно мест</p>
            <p class="text-5xl font-black mb-2">142</p>
            <p class="text-sm font-bold text-emerald-200">28% от фонда</p>
        </div>
        <i class="fas fa-door-open absolute -bottom-4 -right-4 text-8xl opacity-10 rotate-12"></i>
    </div>
    <div class="bg-gradient-to-br from-indigo-500 to-indigo-700 p-8 rounded-[40px] shadow-2xl shadow-indigo-200 text-white relative overflow-hidden">
        <div class="relative z-10">
            <p class="text-xs font-bold uppercase tracking-widest opacity-80 mb-4 text-indigo-100">Процедуры</p>
            <p class="text-5xl font-black mb-2">384</p>
            <p class="text-sm font-bold text-indigo-200">85% загрузка</p>
        </div>
        <i class="fas fa-notes-medical absolute -bottom-4 -right-4 text-8xl opacity-10 rotate-12"></i>
    </div>
    <div class="bg-gradient-to-br from-orange-500 to-rose-600 p-8 rounded-[40px] shadow-2xl shadow-rose-200 text-white relative overflow-hidden">
        <div class="relative z-10">
            <p class="text-xs font-bold uppercase tracking-widest opacity-80 mb-4 text-rose-100">Выручка (мес)</p>
            <p class="text-5xl font-black mb-2">12.8М</p>
            <p class="text-sm font-bold text-rose-100"><i class="fas fa-ruble-sign mr-1"></i> План выполнен</p>
        </div>
        <i class="fas fa-wallet absolute -bottom-4 -right-4 text-8xl opacity-10 rotate-12"></i>
    </div>
</div>

<div class="mb-10">
    <div class="flex items-center justify-between mb-8">
        <h3 class="text-2xl font-extrabold text-gray-900">Быстрый запуск</h3>
        <div class="flex space-x-2">
            <button class="w-10 h-10 bg-white rounded-xl shadow-sm border border-gray-100 text-gray-400 flex items-center justify-center hover:text-blue-500"><i class="fas fa-th-large"></i></button>
            <button class="w-10 h-10 bg-white rounded-xl shadow-sm border border-gray-100 text-gray-400 flex items-center justify-center hover:text-blue-500"><i class="fas fa-list"></i></button>
        </div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <?php
            $modulesConfig = $config->get('modules', []);
            $modulesPath = __DIR__ . '/../../../modules';
            $dirs = array_diff(scandir($modulesPath), ['.', '..']);
            $icons = ['fas fa-utensils', 'fas fa-tshirt', 'fas fa-tools', 'fas fa-film', 'fas fa-swimmer', 'fas fa-spa', 'fas fa-book', 'fas fa-dumbbell'];
            $i = 0;
            foreach ($dirs as $dir) {
                $isEnabled = !isset($modulesConfig[$dir]) || $modulesConfig[$dir] === true;
                if (!$isEnabled) continue;
                $icon = $icons[$i % count($icons)];
                $i++;
                if ($i > 12) break;
                echo "<a href='" . $this->url('/' . strtolower($dir)) . "' class='bg-white p-6 rounded-[32px] border border-gray-50 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all text-center group'>
                    <div class='w-12 h-12 bg-gray-50 text-gray-400 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-600 group-hover:text-white transition-all'>
                        <i class='$icon text-lg'></i>
                    </div>
                    <p class='text-xs font-bold text-gray-800 tracking-tight'>$dir</p>
                </a>";
            }
        ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <div class="bg-white p-8 rounded-[40px] shadow-sm border border-gray-100">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Динамика заездов</h3>
        <canvas id="occupancyChart" height="200"></canvas>
    </div>
    <div class="bg-white p-8 rounded-[40px] shadow-sm border border-gray-100">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Популярные услуги</h3>
        <canvas id="servicesChart" height="200"></canvas>
    </div>
</div>

<script>
    const ctx1 = document.getElementById("occupancyChart").getContext("2d");
    new Chart(ctx1, {
        type: "line",
        data: {
            labels: ["Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"],
            datasets: [{
                label: "Загрузка %",
                data: [65, 72, 68, 75, 82, 90, 88],
                borderColor: "#3b82f6",
                tension: 0.4,
                fill: true,
                backgroundColor: "rgba(59, 130, 246, 0.1)"
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } }
    });

    const ctx2 = document.getElementById("servicesChart").getContext("2d");
    new Chart(ctx2, {
        type: "doughnut",
        data: {
            labels: ["Грязелечение", "Массаж", "Бассейн", "Диета"],
            datasets: [{
                data: [30, 25, 20, 25],
                backgroundColor: ["#3b82f6", "#10b981", "#f59e0b", "#8b5cf6"]
            }]
        },
        options: { plugins: { legend: { position: "bottom" } } }
    });
</script>
