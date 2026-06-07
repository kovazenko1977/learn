
<div class='flex flex-col items-center justify-center py-12 text-center'>
    <div class='w-24 h-24 rounded-[2.5rem] bg-rose-50 flex items-center justify-center mb-8'>
        <i class='fas fa-shield-halved text-rose-500 text-4xl'></i>
    </div>
    <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>Нарушение правил размещения</h2>
    <p class='text-slate-500 mt-4 max-w-md'>Система заблокировала операцию, так как она противоречит установленным правилам санатория:</p>

    <div class='mt-8 space-y-3 w-full max-w-md'>
        <?php foreach ($errors as $error): ?>
            <div class='p-4 bg-rose-50 border border-rose-100 rounded-2xl text-rose-700 text-sm font-semibold flex items-center'>
                <i class='fas fa-circle-exclamation mr-3'></i>
                <?= $error ?>
            </div>
        <?php endforeach; ?>
    </div>

    <button onclick="window.history.back()" class='mt-10 px-8 py-4 bg-slate-900 text-white font-bold rounded-2xl hover:bg-slate-800 transition-all'>
        Вернуться и исправить
    </button>
</div>
