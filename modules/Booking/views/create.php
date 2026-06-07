
<div class='max-w-4xl mx-auto'>
    <div class='mb-12'>
        <h2 class='text-4xl font-black text-slate-800 tracking-tighter'>Регистрация прибытия</h2>
        <p class='text-slate-500 mt-3 font-medium text-lg'>Внесение данных гостя и автоматическая проверка условий проживания.</p>
    </div>

    <form action='<?= $this->url('/booking/save') ?>' method='POST' class='space-y-10'>
        <div class='bg-white p-12 rounded-[3rem] border border-slate-100 shadow-sm'>
            <h3 class='text-xl font-bold mb-10 text-slate-800 flex items-center'>
                <div class='w-12 h-12 rounded-[1.2rem] bg-blue-50 flex items-center justify-center mr-5 shadow-inner'>
                    <i class='fas fa-door-open text-blue-500'></i>
                </div>
                Условия проживания
            </h3>
            <div class='grid grid-cols-1 md:grid-cols-2 gap-10'>
                <div class='md:col-span-2'>
                    <label class='block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4'>Выбранный номер в реестре</label>
                    <select name='room_number' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-5 text-sm font-bold focus:ring-4 focus:ring-blue-500/10 transition-all outline-none'>
                        <?php foreach($rooms as $r): ?>
                            <option value='<?= $r['number'] ?>' <?= ($selectedRoom == $r['number']) ? 'selected' : '' ?>>
                                №<?= $r['number'] ?> (<?= $r['type'] ?>) — Корпус <?= $r['building'] ?? '1' ?> — <?= $r['price'] ?> ₽
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class='block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4'>Дата заезда (Check-in)</label>
                    <input type='date' name='date_from' value='<?= date('Y-m-d') ?>' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-5 text-sm font-bold' required>
                </div>
                <div>
                    <label class='block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4'>Дата выезда (Check-out)</label>
                    <input type='date' name='date_to' value='<?= date('Y-m-d', strtotime('+12 days')) ?>' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-5 text-sm font-bold' required>
                </div>
            </div>
        </div>

        <div class='bg-white p-12 rounded-[3rem] border border-slate-100 shadow-sm'>
            <h3 class='text-xl font-bold mb-10 text-slate-800 flex items-center'>
                <div class='w-12 h-12 rounded-[1.2rem] bg-emerald-50 flex items-center justify-center mr-5 shadow-inner'>
                    <i class='fas fa-passport text-emerald-500'></i>
                </div>
                Персональные и паспортные данные
            </h3>
            <div class='grid grid-cols-1 md:grid-cols-2 gap-10'>
                <div class='md:col-span-2'>
                    <label class='block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4'>ФИО гостя полностью</label>
                    <input type='text' name='guest_name' placeholder='Фамилия Имя Отчество' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-5 text-sm font-bold' required>
                </div>
                <div>
                    <label class='block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4'>Пол</label>
                    <select name='guest_gender' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-5 text-sm font-bold'>
                        <option value='male'>Мужской (М)</option>
                        <option value='female'>Женский (Ж)</option>
                    </select>
                </div>
                <div>
                    <label class='block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4'>Возраст (полных лет)</label>
                    <input type='number' name='guest_age' value='35' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-5 text-sm font-bold'>
                </div>
                <div class='md:col-span-2 grid grid-cols-3 gap-6'>
                    <div class='col-span-1'>
                        <label class='block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4'>Сер. Паспорта</label>
                        <input type='text' name='passport_series' placeholder='00 00' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-5 text-sm font-bold'>
                    </div>
                    <div class='col-span-2'>
                        <label class='block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4'>Номер паспорта</label>
                        <input type='text' name='passport_number' placeholder='000000' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-5 text-sm font-bold'>
                    </div>
                </div>
                <div class='md:col-span-2'>
                    <label class='block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4'>Адрес регистрации</label>
                    <input type='text' name='guest_address' placeholder='Город, улица, дом, кв...' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-5 text-sm font-bold'>
                </div>
                <div>
                    <label class='block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4'>Тип поселения</label>
                    <select name='is_family' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-5 text-sm font-bold'>
                        <option value='no'>Обычное (без подселения противоположного пола)</option>
                        <option value='yes'>Семейное (совместное проживание разрешено)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class='flex flex-col sm:flex-row items-center justify-between p-10 bg-slate-900 rounded-[3rem] shadow-2xl shadow-slate-900/30 gap-8'>
            <div class='text-white text-center sm:text-left'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-1'>Предварительный расчет</p>
                <p class='text-3xl font-black tracking-tight'>Ожидание данных...</p>
            </div>
            <button type='submit' class='w-full sm:w-auto px-16 py-6 bg-blue-600 text-white font-black rounded-3xl shadow-xl shadow-blue-600/20 hover:bg-blue-500 hover:scale-105 active:scale-95 transition-all uppercase tracking-widest text-xs'>
                Подтвердить и заселить
            </button>
        </div>
    </form>
</div>
