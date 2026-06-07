
<div class='mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4'>
    <div>
        <h2 class='text-2xl font-bold text-slate-800'>Реестр номерного фонда</h2>
        <p class='text-sm text-slate-500 mt-1'>Управление корпусами, категориями и инвентаризацией номеров.</p>
    </div>
    <button onclick="document.getElementById('addRoomModal').classList.remove('hidden')" class='w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-3.5 px-8 rounded-2xl shadow-lg shadow-blue-500/20 transition-all flex items-center justify-center'>
        <i class='fas fa-plus mr-3 text-xs'></i> Добавить новый номер
    </button>
</div>

<!-- Модальное окно добавления -->
<div id='addRoomModal' class='fixed inset-0 bg-slate-900/70 backdrop-blur-md z-[7000] hidden flex items-center justify-center p-4 overflow-y-auto'>
    <div class='bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl overflow-hidden border border-white/20 my-auto'>
        <div class='bg-slate-900 p-10 text-white flex justify-between items-center relative overflow-hidden'>
            <div class='absolute top-0 right-0 p-10 opacity-10'>
                <i class='fas fa-hotel text-[8rem]'></i>
            </div>
            <div class='relative z-10'>
                <h3 class='text-2xl font-bold'>Параметры номера</h3>
                <p class='text-slate-400 text-sm mt-1'>Техническая карта и оснащение</p>
            </div>
            <button onclick="document.getElementById('addRoomModal').classList.add('hidden')" class='relative z-10 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors'>
                <i class='fas fa-times text-lg'></i>
            </button>
        </div>
        <form action='<?= $this->url('/accommodation/add') ?>' method='POST' class='p-10 space-y-10 max-h-[70vh] overflow-y-auto custom-scrollbar'>
            <div class='grid grid-cols-1 md:grid-cols-2 gap-8'>
                <!-- Основное -->
                <div class='space-y-6'>
                    <h4 class='text-[10px] font-bold text-blue-500 uppercase tracking-[0.2em]'>Локация и тип</h4>
                    <div>
                        <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>Корпус / Здание</label>
                        <select name='building' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-bold outline-none focus:ring-4 focus:ring-blue-500/10'>
                            <option value='1'>Корпус №1 «Главный»</option>
                            <option value='2'>Корпус №2 «Лесной»</option>
                            <option value='3'>Корпус №3 «Прибрежный»</option>
                            <option value='vip'>VIP Коттедж</option>
                        </select>
                    </div>
                    <div class='grid grid-cols-2 gap-4'>
                        <div>
                            <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>Номер</label>
                            <input type='text' name='number' required placeholder='101' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-bold outline-none focus:ring-4 focus:ring-blue-500/10'>
                        </div>
                        <div>
                            <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>Этаж</label>
                            <input type='number' name='floor' value='1' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-bold outline-none'>
                        </div>
                    </div>
                    <div>
                        <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>Категория комфорта</label>
                        <select name='type' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-bold outline-none'>
                            <option>Стандарт Одноместный</option>
                            <option>Стандарт Двухместный</option>
                            <option>Полулюкс (Studio)</option>
                            <option>Люкс Двухкомнатный</option>
                            <option>Апартаменты Family</option>
                            <option>Президентский Сьют</option>
                        </select>
                    </div>
                </div>

                <!-- Удобства -->
                <div class='space-y-6'>
                    <h4 class='text-[10px] font-bold text-emerald-500 uppercase tracking-[0.2em]'>Техническое оснащение</h4>
                    <div class='grid grid-cols-1 gap-2'>
                        <label class='flex items-center space-x-4 p-3 bg-slate-50 rounded-2xl cursor-pointer hover:bg-slate-100 transition-all border border-transparent hover:border-slate-200'>
                            <input type='checkbox' name='amenities[]' value='fridge' class='w-5 h-5 rounded-lg text-blue-600 border-slate-200 focus:ring-blue-500/20'>
                            <span class='text-xs font-bold text-slate-600'>Холодильник / Мини-бар</span>
                        </label>
                        <label class='flex items-center space-x-4 p-3 bg-slate-50 rounded-2xl cursor-pointer hover:bg-slate-100 transition-all border border-transparent hover:border-slate-200'>
                            <input type='checkbox' name='amenities[]' value='tv' class='w-5 h-5 rounded-lg text-blue-600 border-slate-200 focus:ring-blue-500/20'>
                            <span class='text-xs font-bold text-slate-600'>Плазменный ТВ</span>
                        </label>
                        <label class='flex items-center space-x-4 p-3 bg-slate-50 rounded-2xl cursor-pointer hover:bg-slate-100 transition-all border border-transparent hover:border-slate-200'>
                            <input type='checkbox' name='amenities[]' value='ac' class='w-5 h-5 rounded-lg text-blue-600 border-slate-200 focus:ring-blue-500/20'>
                            <span class='text-xs font-bold text-slate-600'>Кондиционер</span>
                        </label>
                        <label class='flex items-center space-x-4 p-3 bg-slate-50 rounded-2xl cursor-pointer hover:bg-slate-100 transition-all border border-transparent hover:border-slate-200'>
                            <input type='checkbox' name='amenities[]' value='wifi' class='w-5 h-5 rounded-lg text-blue-600 border-slate-200 focus:ring-blue-500/20' checked>
                            <span class='text-xs font-bold text-slate-600'>Высокоскоростной Wi-Fi</span>
                        </label>
                        <label class='flex items-center space-x-4 p-3 bg-slate-50 rounded-2xl cursor-pointer hover:bg-slate-100 transition-all border border-transparent hover:border-slate-200'>
                            <input type='checkbox' name='amenities[]' value='safe' class='w-5 h-5 rounded-lg text-blue-600 border-slate-200 focus:ring-blue-500/20'>
                            <span class='text-xs font-bold text-slate-600'>Сейф в номере</span>
                        </label>
                        <label class='flex items-center space-x-4 p-3 bg-slate-50 rounded-2xl cursor-pointer hover:bg-slate-100 transition-all border border-transparent hover:border-slate-200'>
                            <input type='checkbox' name='amenities[]' value='shower' class='w-5 h-5 rounded-lg text-blue-600 border-slate-200 focus:ring-blue-500/20' checked>
                            <span class='text-xs font-bold text-slate-600'>Санузел / Душевая</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class='grid grid-cols-1 md:grid-cols-3 gap-6'>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>Кол-во мест</label>
                    <input type='number' name='places' value='1' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-bold outline-none'>
                </div>
                <div class='md:col-span-2'>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>Базовая цена за сутки (₽)</label>
                    <input type='number' name='price' value='3500' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-bold outline-none focus:ring-4 focus:ring-emerald-500/10 text-emerald-600'>
                </div>
            </div>

            <div class='space-y-6'>
                <h4 class='text-[10px] font-bold text-amber-500 uppercase tracking-[0.2em]'>Презентация и описание</h4>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>URL-адрес фотографии</label>
                    <input type='text' name='image' placeholder='https://images.unsplash.com/...' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-bold outline-none'>
                </div>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>Краткое описание для карточки</label>
                    <textarea name='description' rows='3' class='w-full border-slate-100 bg-slate-50 rounded-[2rem] p-6 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-500/10' placeholder='Опишите преимущества номера...'></textarea>
                </div>
            </div>

            <div class='flex flex-col sm:flex-row justify-end gap-4 pt-10 border-t border-slate-50'>
                <button type='button' onclick="document.getElementById('addRoomModal').classList.add('hidden')" class='px-8 py-4 text-slate-400 font-bold text-sm hover:text-slate-600 transition-colors uppercase tracking-widest'>Отменить</button>
                <button type='submit' class='px-12 py-4 bg-slate-900 text-white rounded-[1.5rem] font-bold shadow-2xl shadow-slate-900/30 hover:bg-blue-600 hover:scale-105 transition-all uppercase tracking-widest text-xs'>Внести в реестр</button>
            </div>
        </form>
    </div>
</div>

<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8'>
    <?php if (empty($rooms)): ?>
        <div class='col-span-full py-24 bg-slate-50 rounded-[3rem] border-4 border-dashed border-slate-100 text-center'>
            <i class='fas fa-folder-plus text-slate-200 text-[6rem] mb-6'></i>
            <p class='text-slate-400 font-bold text-xl'>Реестр пуст. Добавьте первый номер.</p>
        </div>
    <?php else: ?>
        <?php foreach ($rooms as $room): ?>
            <?php
                $statusColor = $room['status'] === 'свободен' ? 'emerald' : ($room['status'] === 'занят' ? 'rose' : 'amber');
                $image = !empty($room['image']) ? $room['image'] : 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&q=80&w=800';
                $building = $room['building'] ?? '1';
                $buildingName = "Корпус №$building";
                if ($building === 'vip') $buildingName = "VIP Коттедж";
            ?>
            <div class='bg-white rounded-[2.5rem] border border-slate-100 overflow-hidden shadow-sm hover:shadow-2xl hover:shadow-slate-200/60 transition-all duration-500 group'>
                <div class='h-56 overflow-hidden relative'>
                    <img src='<?= $image ?>' class='w-full h-full object-cover group-hover:scale-110 transition-all duration-700' alt='Фото номера'>
                    <div class='absolute top-6 right-6'>
                         <span class='px-4 py-2 bg-white/95 backdrop-blur-xl shadow-xl text-<?= $statusColor ?>-600 text-[10px] font-extrabold uppercase tracking-widest rounded-2xl border border-<?= $statusColor ?>-100'>
                            <?= $room['status'] ?>
                        </span>
                    </div>
                    <div class='absolute bottom-6 left-6'>
                         <span class='px-4 py-2 bg-slate-900/60 backdrop-blur-md text-white text-[10px] font-bold uppercase tracking-widest rounded-xl'>
                            <?= $buildingName ?>
                        </span>
                    </div>
                </div>
                <div class='p-8'>
                    <div class='flex justify-between items-start mb-4'>
                        <div>
                            <h4 class='text-2xl font-black text-slate-800 tracking-tight'>№ <?= $room['number'] ?></h4>
                            <p class='text-slate-400 text-[10px] font-bold uppercase tracking-[0.15em] mt-1'><?= $room['type'] ?></p>
                        </div>
                        <div class='text-right'>
                            <p class='text-2xl font-black text-slate-900 tabular-nums'><?= number_format((float)$room['price'], 0, '.', ' ') ?> <span class='text-xs font-bold text-slate-400 ml-1'>₽</span></p>
                            <p class='text-[9px] text-slate-400 font-bold uppercase tracking-widest'>Суточный тариф</p>
                        </div>
                    </div>

                    <div class='flex flex-wrap gap-2.5 my-6'>
                        <?php if (in_array('fridge', $room['amenities'] ?? [])): ?>
                            <div class='w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100' title='Холодильник'><i class='fas fa-snowflake text-xs'></i></div>
                        <?php endif; ?>
                        <?php if (in_array('tv', $room['amenities'] ?? [])): ?>
                            <div class='w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100' title='Телевизор'><i class='fas fa-desktop text-xs'></i></div>
                        <?php endif; ?>
                        <?php if (in_array('ac', $room['amenities'] ?? [])): ?>
                            <div class='w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100' title='Кондиционер'><i class='fas fa-wind text-xs'></i></div>
                        <?php endif; ?>
                        <?php if (in_array('wifi', $room['amenities'] ?? [])): ?>
                            <div class='w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100' title='Wi-Fi'><i class='fas fa-wifi text-xs'></i></div>
                        <?php endif; ?>
                        <?php if (in_array('shower', $room['amenities'] ?? [])): ?>
                            <div class='w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100' title='Санузел'><i class='fas fa-bath text-xs'></i></div>
                        <?php endif; ?>
                    </div>

                    <p class='text-sm text-slate-500 leading-relaxed line-clamp-2 italic'>
                        <?= $room['description'] ?? 'Уютный номер, подготовленный к приему гостей. Оснащен всем необходимым для длительного проживания.' ?>
                    </p>

                    <div class='mt-8 pt-8 border-t border-slate-50 flex justify-between items-center'>
                         <button class='text-[10px] font-bold text-blue-600 uppercase tracking-widest hover:text-blue-800 transition-colors flex items-center'>
                            <i class='fas fa-edit mr-2'></i> Изменить карту
                         </button>
                         <div class='flex items-center text-slate-300 space-x-4'>
                             <div class='flex items-center'><i class='fas fa-stairs text-xs mr-2'></i> <span class='text-xs font-bold tabular-nums'><?= $room['floor'] ?></span></div>
                             <div class='flex items-center'><i class='fas fa-user-group text-xs mr-2'></i> <span class='text-xs font-bold tabular-nums'><?= $room['places'] ?></span></div>
                         </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
