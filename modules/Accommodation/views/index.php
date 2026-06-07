
<div class='mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4'>
    <div>
        <h2 class='text-2xl font-bold text-slate-800'>Управление номерным фондом</h2>
        <p class='text-sm text-slate-500 mt-1'>Конфигурация корпусов, удобств и оснащения номеров.</p>
    </div>
    <button onclick="document.getElementById('addRoomModal').classList.remove('hidden')" class='w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-3 px-6 rounded-xl shadow-lg shadow-blue-500/20 transition-all flex items-center justify-center'>
        <i class='fas fa-plus mr-2 text-xs'></i> Добавить номер
    </button>
</div>

<!-- Модальное окно добавления (расширенное) -->
<div id='addRoomModal' class='fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[7000] hidden flex items-center justify-center p-4 overflow-y-auto'>
    <div class='bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl overflow-hidden border border-white/20 my-auto'>
        <div class='bg-slate-900 p-8 text-white flex justify-between items-center'>
            <div>
                <h3 class='text-2xl font-bold'>Конфигуратор номера</h3>
                <p class='text-slate-400 text-sm mt-1'>Детальное описание и оснащение</p>
            </div>
            <button onclick="document.getElementById('addRoomModal').classList.add('hidden')" class='text-slate-400 hover:text-white'>
                <i class='fas fa-times text-xl'></i>
            </button>
        </div>
        <form action='<?= $this->url('/accommodation/add') ?>' method='POST' class='p-8 space-y-8 max-h-[70vh] overflow-y-auto custom-scrollbar'>
            <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                <!-- Основное -->
                <div class='space-y-4'>
                    <h4 class='text-[10px] font-bold text-blue-500 uppercase tracking-widest'>Основная информация</h4>
                    <div>
                        <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Номер комнаты</label>
                        <input type='text' name='number' required placeholder='101' class='w-full border-slate-100 bg-slate-50 rounded-xl p-3 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/10'>
                    </div>
                    <div class='grid grid-cols-2 gap-4'>
                        <div>
                            <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Категория</label>
                            <select name='type' class='w-full border-slate-100 bg-slate-50 rounded-xl p-3 text-sm font-bold outline-none'>
                                <option>Стандарт</option>
                                <option>Люкс</option>
                                <option>Полулюкс</option>
                                <option>Президентский</option>
                                <option>Апартаменты</option>
                            </select>
                        </div>
                        <div>
                            <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Мест</label>
                            <input type='number' name='places' value='1' class='w-full border-slate-100 bg-slate-50 rounded-xl p-3 text-sm font-bold outline-none'>
                        </div>
                    </div>
                    <div class='grid grid-cols-2 gap-4'>
                        <div>
                            <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Этаж</label>
                            <input type='number' name='floor' value='1' class='w-full border-slate-100 bg-slate-50 rounded-xl p-3 text-sm font-bold outline-none'>
                        </div>
                        <div>
                            <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Цена (₽)</label>
                            <input type='number' name='price' value='3500' class='w-full border-slate-100 bg-slate-50 rounded-xl p-3 text-sm font-bold outline-none'>
                        </div>
                    </div>
                </div>

                <!-- Удобства -->
                <div class='space-y-4'>
                    <h4 class='text-[10px] font-bold text-emerald-500 uppercase tracking-widest'>Оснащение и удобства</h4>
                    <div class='grid grid-cols-2 gap-3'>
                        <label class='flex items-center space-x-3 p-3 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors'>
                            <input type='checkbox' name='amenities[]' value='fridge' class='rounded text-blue-600'>
                            <span class='text-xs font-semibold text-slate-600'>Холодильник</span>
                        </label>
                        <label class='flex items-center space-x-3 p-3 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors'>
                            <input type='checkbox' name='amenities[]' value='tv' class='rounded text-blue-600'>
                            <span class='text-xs font-semibold text-slate-600'>ТВ</span>
                        </label>
                        <label class='flex items-center space-x-3 p-3 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors'>
                            <input type='checkbox' name='amenities[]' value='ac' class='rounded text-blue-600'>
                            <span class='text-xs font-semibold text-slate-600'>Кондиционер</span>
                        </label>
                        <label class='flex items-center space-x-3 p-3 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors'>
                            <input type='checkbox' name='amenities[]' value='wifi' class='rounded text-blue-600' checked>
                            <span class='text-xs font-semibold text-slate-600'>Wi-Fi</span>
                        </label>
                        <label class='flex items-center space-x-3 p-3 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors'>
                            <input type='checkbox' name='amenities[]' value='microwave' class='rounded text-blue-600'>
                            <span class='text-xs font-semibold text-slate-600'>Микроволновка</span>
                        </label>
                        <label class='flex items-center space-x-3 p-3 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors'>
                            <input type='checkbox' name='amenities[]' value='safe' class='rounded text-blue-600'>
                            <span class='text-xs font-semibold text-slate-600'>Сейф</span>
                        </label>
                        <label class='flex items-center space-x-3 p-3 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors'>
                            <input type='checkbox' name='amenities[]' value='balcony' class='rounded text-blue-600'>
                            <span class='text-xs font-semibold text-slate-600'>Балкон</span>
                        </label>
                        <label class='flex items-center space-x-3 p-3 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors'>
                            <input type='checkbox' name='amenities[]' value='shower' class='rounded text-blue-600' checked>
                            <span class='text-xs font-semibold text-slate-600'>Санузел</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class='space-y-4'>
                <h4 class='text-[10px] font-bold text-amber-500 uppercase tracking-widest'>Медиа и описание</h4>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Ссылка на изображение</label>
                    <input type='text' name='image' placeholder='https://...' class='w-full border-slate-100 bg-slate-50 rounded-xl p-3 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/10'>
                </div>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Описание номера</label>
                    <textarea name='description' rows='3' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-semibold outline-none focus:ring-2 focus:ring-blue-500/10' placeholder='Опишите преимущества номера...'></textarea>
                </div>
            </div>

            <div class='flex justify-end space-x-4 pt-8 border-t border-slate-50'>
                <button type='button' onclick="document.getElementById('addRoomModal').classList.add('hidden')" class='px-6 py-3 text-slate-400 font-bold text-sm hover:text-slate-600 transition-colors'>Отмена</button>
                <button type='submit' class='px-10 py-3 bg-blue-600 text-white rounded-2xl font-bold shadow-xl shadow-blue-500/20 hover:bg-blue-700 hover:scale-105 transition-all'>Сохранить номер</button>
            </div>
        </form>
    </div>
</div>

<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'>
    <?php if (empty($rooms)): ?>
        <div class='col-span-full py-20 bg-slate-50 rounded-[2.5rem] border-2 border-dashed border-slate-200 text-center'>
            <i class='fas fa-folder-open text-slate-300 text-5xl mb-4'></i>
            <p class='text-slate-400 font-medium'>Список номеров пуст. Начните с создания первого номера.</p>
        </div>
    <?php else: ?>
        <?php foreach ($rooms as $room): ?>
            <?php
                $statusColor = $room['status'] === 'свободен' ? 'emerald' : ($room['status'] === 'занят' ? 'rose' : 'amber');
                $icon = $room['type'] === 'Люкс' ? 'fa-crown' : ($room['type'] === 'Апартаменты' ? 'fa-building' : 'fa-bed');
                $image = !empty($room['image']) ? $room['image'] : 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&q=80&w=800';
            ?>
            <div class='bg-white rounded-[2rem] border border-slate-100 overflow-hidden shadow-sm hover:shadow-xl hover:shadow-slate-200/50 transition-all group'>
                <div class='h-48 overflow-hidden relative'>
                    <img src='<?= $image ?>' class='w-full h-full object-cover group-hover:scale-110 transition-all duration-500' alt='Номер'>
                    <div class='absolute top-4 right-4'>
                         <span class='px-3 py-1.5 bg-white/90 backdrop-blur shadow-sm text-<?= $statusColor ?>-600 text-[10px] font-bold uppercase tracking-widest rounded-full'>
                            <?= $room['status'] ?>
                        </span>
                    </div>
                </div>
                <div class='p-6'>
                    <div class='flex justify-between items-start mb-2'>
                        <div>
                            <h4 class='text-xl font-bold text-slate-800'>№ <?= $room['number'] ?></h4>
                            <p class='text-slate-400 text-[10px] font-bold uppercase tracking-widest'><?= $room['type'] ?> • <?= $room['floor'] ?> этаж</p>
                        </div>
                        <div class='text-right'>
                            <p class='text-lg font-bold text-slate-900'><?= number_format((float)$room['price'], 0, '.', ' ') ?> ₽</p>
                            <p class='text-[9px] text-slate-400 font-bold'>ЗА СУТКИ</p>
                        </div>
                    </div>

                    <div class='flex flex-wrap gap-2 my-4'>
                        <?php if (in_array('fridge', $room['amenities'] ?? [])): ?>
                            <div class='w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400' title='Холодильник'><i class='fas fa-snowflake text-[10px]'></i></div>
                        <?php endif; ?>
                        <?php if (in_array('tv', $room['amenities'] ?? [])): ?>
                            <div class='w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400' title='ТВ'><i class='fas fa-tv text-[10px]'></i></div>
                        <?php endif; ?>
                        <?php if (in_array('ac', $room['amenities'] ?? [])): ?>
                            <div class='w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400' title='Кондиционер'><i class='fas fa-wind text-[10px]'></i></div>
                        <?php endif; ?>
                        <?php if (in_array('wifi', $room['amenities'] ?? [])): ?>
                            <div class='w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400' title='Wi-Fi'><i class='fas fa-wifi text-[10px]'></i></div>
                        <?php endif; ?>
                        <?php if (in_array('shower', $room['amenities'] ?? [])): ?>
                            <div class='w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400' title='Санузел'><i class='fas fa-shower text-[10px]'></i></div>
                        <?php endif; ?>
                    </div>

                    <p class='text-xs text-slate-500 line-clamp-2 mt-4 italic'>
                        <?= $room['description'] ?? 'Комфортабельный номер со всеми удобствами для приятного отдыха.' ?>
                    </p>

                    <div class='mt-6 pt-6 border-t border-slate-50 flex justify-between items-center'>
                         <button class='text-[10px] font-bold text-blue-600 uppercase tracking-widest hover:text-blue-700'>Редактировать</button>
                         <div class='flex space-x-1'>
                             <div class='w-1.5 h-1.5 rounded-full bg-slate-200'></div>
                             <div class='w-1.5 h-1.5 rounded-full bg-slate-200'></div>
                             <div class='w-1.5 h-1.5 rounded-full bg-blue-500'></div>
                         </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
