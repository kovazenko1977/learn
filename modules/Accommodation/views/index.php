
<div class='mb-8 flex justify-between items-end'>
    <div>
        <h2 class='text-2xl font-bold text-slate-800'>Управление номерным фондом</h2>
        <p class='text-sm text-slate-500 mt-1'>Конфигурация корпусов, категорий и мест.</p>
    </div>
    <button onclick="document.getElementById('addRoomModal').classList.remove('hidden')" class='bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2.5 px-6 rounded-xl shadow-lg shadow-blue-500/20 transition-all flex items-center'>
        <i class='fas fa-plus mr-2 text-xs'></i> Добавить номер
    </button>
</div>

<div id='addRoomModal' class='fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[3000] hidden flex items-center justify-center p-4 transition-all'>
    <div class='bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-white/20 transform scale-100'>
        <div class='bg-slate-900 p-8 text-white relative overflow-hidden'>
            <div class='absolute top-0 right-0 p-8 opacity-10'>
                <i class='fas fa-door-open text-8xl'></i>
            </div>
            <h3 class='text-2xl font-bold relative z-10'>Новый номер</h3>
            <p class='text-slate-400 text-sm mt-1 relative z-10'>Введите параметры жилого помещения</p>
        </div>
        <form action='<?= $this->url('/accommodation/add') ?>' method='POST' class='p-8 space-y-6'>
            <div>
                <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Номер комнаты</label>
                <input type='text' name='number' required placeholder='Например: 101' class='w-full border-slate-100 bg-slate-50 rounded-xl focus:ring-blue-500 p-3 text-sm font-semibold'>
            </div>
            <div class='grid grid-cols-2 gap-4'>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Тип</label>
                    <select name='type' class='w-full border-slate-100 bg-slate-50 rounded-xl p-3 text-sm font-semibold'>
                        <option>Стандарт</option>
                        <option>Люкс</option>
                        <option>Полулюкс</option>
                        <option>Апартаменты</option>
                    </select>
                </div>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Этаж</label>
                    <input type='number' name='floor' value='1' class='w-full border-slate-100 bg-slate-50 rounded-xl p-3 text-sm font-semibold'>
                </div>
            </div>
            <div class='grid grid-cols-2 gap-4'>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Мест</label>
                    <input type='number' name='places' value='1' class='w-full border-slate-100 bg-slate-50 rounded-xl p-3 text-sm font-semibold'>
                </div>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Цена (₽)</label>
                    <input type='number' name='price' value='3500' class='w-full border-slate-100 bg-slate-50 rounded-xl p-3 text-sm font-semibold'>
                </div>
            </div>
            <div class='flex justify-end space-x-3 pt-6'>
                <button type='button' onclick="document.getElementById('addRoomModal').classList.add('hidden')" class='px-6 py-3 text-slate-400 font-bold text-sm hover:text-slate-600 transition-colors'>Отмена</button>
                <button type='submit' class='px-8 py-3 bg-blue-600 text-white rounded-xl font-bold shadow-xl shadow-blue-500/20 hover:bg-blue-700 transition-all'>Создать</button>
            </div>
        </form>
    </div>
</div>

<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'>
    <?php if (empty($rooms)): ?>
        <div class='col-span-full py-20 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200 text-center'>
            <i class='fas fa-folder-open text-slate-300 text-5xl mb-4'></i>
            <p class='text-slate-400 font-medium'>Список номеров пуст</p>
        </div>
    <?php else: ?>
        <?php foreach ($rooms as $room): ?>
            <?php
                $statusColor = $room['status'] === 'свободен' ? 'emerald' : ($room['status'] === 'занят' ? 'rose' : 'amber');
                $icon = $room['type'] === 'Люкс' ? 'fa-crown' : ($room['type'] === 'Апартаменты' ? 'fa-building' : 'fa-bed');
            ?>
            <div class='bg-white rounded-3xl border border-slate-100 p-6 shadow-sm hover:shadow-xl hover:shadow-slate-200/50 transition-all group'>
                <div class='flex justify-between items-start mb-6'>
                    <div class='w-12 h-12 rounded-2xl bg-<?= $statusColor ?>-50 flex items-center justify-center transition-colors group-hover:bg-<?= $statusColor ?>-100'>
                        <i class='fas <?= $icon ?> text-<?= $statusColor ?>-500 text-xl'></i>
                    </div>
                    <span class='px-3 py-1 bg-<?= $statusColor ?>-50 text-<?= $statusColor ?>-600 text-[10px] font-bold uppercase tracking-widest rounded-full'>
                        <?= $room['status'] ?>
                    </span>
                </div>
                <div>
                    <h4 class='text-xl font-bold text-slate-800'>№ <?= $room['number'] ?></h4>
                    <p class='text-slate-400 text-sm font-medium'><?= $room['type'] ?> • <?= $room['floor'] ?> этаж</p>
                </div>
                <div class='mt-6 pt-6 border-t border-slate-50 flex justify-between items-center'>
                    <p class='text-lg font-bold text-slate-900'><?= number_format((float)$room['price'], 0, '.', ' ') ?> <span class='text-xs font-medium text-slate-400'>₽ / сут</span></p>
                    <button class='w-10 h-10 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-blue-50 hover:text-blue-600 transition-all'>
                        <i class='fas fa-ellipsis-v text-xs'></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
