
<div class='mb-10 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-6'>
    <div>
        <h2 class='text-3xl font-black text-slate-800 tracking-tight'>График размещения (Шахматка)</h2>
        <p class='text-sm text-slate-500 mt-2 font-medium'>Оперативное управление заездами и мониторинг текущей загрузки.</p>
    </div>
    <div class='flex bg-slate-100 p-1.5 rounded-2xl'>
        <button class='px-6 py-2 bg-white shadow-sm rounded-xl text-xs font-bold text-slate-800'>Сегодня</button>
        <button class='px-6 py-2 text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors'>Неделя</button>
        <button class='px-6 py-2 text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors'>Месяц</button>
    </div>
</div>

<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8'>
    <?php if (empty($rooms)): ?>
        <div class='col-span-full py-24 bg-slate-50 rounded-[3rem] border-4 border-dashed border-slate-100 text-center'>
            <i class='fas fa-calendar-xmark text-slate-200 text-[6rem] mb-6'></i>
            <p class='text-slate-400 font-bold text-xl'>Нет доступных номеров. Сначала заполните Реестр.</p>
        </div>
    <?php else: ?>
        <?php foreach ($rooms as $room): ?>
            <?php
                $places = $room['places'] ?? 1;
                $occupied = $room['occupied_places'] ?? 0;
                $progress = ($occupied / $places) * 100;
                $barColor = $progress >= 100 ? 'rose' : ($progress > 0 ? 'amber' : 'emerald');
                $building = $room['building'] ?? '1';
            ?>
            <div class='bg-white rounded-[2.5rem] border border-slate-100 overflow-hidden shadow-sm hover:shadow-2xl hover:shadow-slate-200/50 transition-all duration-500'>
                <div class='p-8 border-b border-slate-50 bg-slate-50/30 flex justify-between items-center'>
                    <div>
                        <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]'><?= $room['type'] ?></p>
                        <h4 class='text-2xl font-black text-slate-800 tracking-tighter'>№ <?= $room['number'] ?></h4>
                    </div>
                    <div class='w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-slate-800 font-bold border border-slate-100'>
                        К<?= $building ?>
                    </div>
                </div>
                <div class='p-8'>
                    <div class='flex items-center justify-between mb-4'>
                        <span class='text-[10px] font-black text-slate-500 uppercase tracking-widest'>Места: <?= $occupied ?> из <?= $places ?></span>
                        <div class='w-20 h-2 bg-slate-100 rounded-full overflow-hidden'>
                            <div class='h-full bg-<?= $barColor ?>-500' style='width: <?= $progress ?>%'></div>
                        </div>
                    </div>

                    <div class='grid grid-cols-4 gap-3 mb-10'>
                        <?php for ($i = 1; $i <= $places; $i++): ?>
                            <?php $isOccupied = $i <= $occupied; ?>
                            <div class='h-14 rounded-2xl <?= $isOccupied ? 'bg-rose-50 text-rose-500 border-rose-100' : 'bg-slate-50 text-slate-300 border-slate-100 hover:bg-emerald-50 hover:text-emerald-500 hover:border-emerald-100' ?> border-2 flex items-center justify-center transition-all cursor-pointer' title='Место <?= $i ?>'>
                                <i class='fas <?= $isOccupied ? 'fa-user-tie' : 'fa-bed' ?> text-lg'></i>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <a href='<?= $this->url('/booking/create?room=' . $room['number']) ?>' class='block w-full py-4 bg-slate-900 text-white text-center rounded-[1.2rem] text-xs font-extrabold uppercase tracking-[0.15em] hover:bg-blue-600 transition-all shadow-xl shadow-slate-900/10 active:scale-95'>
                        Оформить заезд
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
