
<div class='max-w-4xl mx-auto'>
    <div class='mb-10'>
        <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>Регистрация гостя</h2>
        <p class='text-slate-500 mt-2'>Оформление проживания и проверка соответствия правилам заезда.</p>
    </div>

    <form action='<?= $this->url('/booking/save') ?>' method='POST' class='space-y-8'>
        <div class='bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm'>
            <h3 class='text-xl font-bold mb-8 text-slate-800 flex items-center'>
                <div class='w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center mr-4'>
                    <i class='fas fa-door-open text-blue-500 text-sm'></i>
                </div>
                Параметры проживания
            </h3>
            <div class='grid grid-cols-1 md:grid-cols-2 gap-8'>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>Выбор номера</label>
                    <select name='room_number' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-semibold focus:ring-2 focus:ring-blue-500/20 transition-all outline-none'>
                        <?php foreach($rooms as $r): ?>
                            <option value='<?= $r['number'] ?>' <?= ($selectedRoom == $r['number']) ? 'selected' : '' ?>>
                                №<?= $r['number'] ?> (<?= $r['type'] ?>) - <?= $r['price'] ?> ₽
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>Тип размещения</label>
                    <select name='type' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-semibold'>
                        <option value='full'>Номер целиком</option>
                        <option value='place'>По местам (подселение)</option>
                    </select>
                </div>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>Дата заезда</label>
                    <input type='date' name='date_from' value='<?= date('Y-m-d') ?>' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-semibold' required>
                </div>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>Дата выезда</label>
                    <input type='date' name='date_to' value='<?= date('Y-m-d', strtotime('+7 days')) ?>' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-semibold' required>
                </div>
            </div>
        </div>

        <div class='bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm'>
            <h3 class='text-xl font-bold mb-8 text-slate-800 flex items-center'>
                <div class='w-10 h-10 rounded-2xl bg-emerald-50 flex items-center justify-center mr-4'>
                    <i class='fas fa-user text-emerald-500 text-sm'></i>
                </div>
                Личные данные
            </h3>
            <div class='grid grid-cols-1 md:grid-cols-2 gap-8'>
                <div class='md:col-span-2'>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>ФИО гостя</label>
                    <input type='text' name='guest_name' placeholder='Введите полное имя...' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-semibold' required>
                </div>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>Пол</label>
                    <select name='guest_gender' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-semibold'>
                        <option value='male'>Мужской</option>
                        <option value='female'>Женский</option>
                    </select>
                </div>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>Возраст</label>
                    <input type='number' name='guest_age' value='30' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-semibold'>
                </div>
                <div>
                    <label class='block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3'>Семейная пара?</label>
                    <select name='is_family' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-4 text-sm font-semibold'>
                        <option value='no'>Нет (обычное подселение)</option>
                        <option value='yes'>Да (совместное проживание)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class='flex items-center justify-between p-8 bg-slate-900 rounded-[2.5rem] shadow-2xl shadow-slate-900/20'>
            <div class='text-white pl-4'>
                <p class='text-[10px] font-bold text-slate-400 uppercase tracking-widest'>К оплате</p>
                <p class='text-3xl font-bold tracking-tight'>Расчет...</p>
            </div>
            <button type='submit' class='px-12 py-5 bg-blue-600 text-white font-bold rounded-3xl shadow-xl shadow-blue-500/30 hover:bg-blue-500 hover:scale-105 transition-all'>
                Завершить регистрацию
            </button>
        </div>
    </form>
</div>
