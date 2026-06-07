
<div class='mb-8'>
    <h2 class='text-2xl font-bold text-slate-800'>Шахматка размещения</h2>
    <p class='text-sm text-slate-500 mt-1'>Визуальный контроль и быстрое заселение гостей.</p>
</div>

<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6'>
    <?php if (empty($rooms)): ?>
        <div class='col-span-full py-20 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200 text-center'>
            <i class='fas fa-calendar-xmark text-slate-300 text-5xl mb-4'></i>
            <p class='text-slate-400 font-medium'>Нет доступных номеров для бронирования</p>
        </div>
    <?php else: ?>
        <?php foreach ($rooms as $room): ?>
            <?php
                $places = $room['places'] ?? 1;
                $occupied = $room['occupied_places'] ?? 0;
                $progress = ($occupied / $places) * 100;
                $barColor = $progress >= 100 ? 'rose' : ($progress > 0 ? 'amber' : 'emerald');
            ?>
            <div class='bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm hover:shadow-xl hover:shadow-slate-200/50 transition-all'>
                <div class='p-6 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center'>
                    <div>
                        <p class='text-[10px] font-bold text-slate-400 uppercase tracking-widest'><?= $room['type'] ?></p>
                        <h4 class='text-xl font-bold text-slate-800'>№ <?= $room['number'] ?></h4>
                    </div>
                    <div class='text-right'>
                        <span class='text-lg font-bold text-slate-900'><?= $room['price'] ?> ₽</span>
                    </div>
                </div>
                <div class='p-6'>
                    <div class='flex items-center justify-between mb-4'>
                        <span class='text-xs font-bold text-slate-500 uppercase tracking-tighter'>Заполнение: <?= $occupied ?> / <?= $places ?></span>
                        <div class='w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden'>
                            <div class='h-full bg-<?= $barColor ?>-500' style='width: <?= $progress ?>%'></div>
                        </div>
                    </div>

                    <div class='grid grid-cols-4 gap-2 mb-8'>
                        <?php for ($i = 1; $i <= $places; $i++): ?>
                            <?php $isOccupied = $i <= $occupied; ?>
                            <div class='h-12 rounded-xl <?= $isOccupied ? 'bg-rose-50 text-rose-500' : 'bg-slate-50 text-slate-300 hover:bg-emerald-50 hover:text-emerald-500' ?> flex items-center justify-center transition-all cursor-pointer' title='Место <?= $i ?>'>
                                <i class='fas <?= $isOccupied ? 'fa-user' : 'fa-bed' ?> text-sm'></i>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <a href='<?= $this->url('/booking/create?room=' . $room['number']) ?>' class='block w-full py-3 bg-slate-900 text-white text-center rounded-xl text-xs font-bold hover:bg-blue-600 transition-all shadow-lg shadow-slate-900/10'>
                        Регистрация заезда
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
