
<div class='flex flex-col items-center justify-center py-16 text-center'>
    <div class='w-32 h-32 rounded-[3rem] bg-rose-50 flex items-center justify-center mb-10 shadow-inner'>
        <i class='fas fa-shield-virus text-rose-500 text-5xl'></i>
    </div>
    <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>Нарушение протокола размещения</h2>
    <p class='text-slate-500 mt-4 max-w-md font-medium'>Автоматизированная система контроля заблокировала операцию заселения по следующим причинам:</p>

    <div class='mt-10 space-y-3 w-full max-w-lg'>
        <?php foreach ($errors as $error): ?>
            <div class='p-5 bg-rose-50 border border-rose-100 rounded-2xl text-rose-700 text-sm font-black flex items-start text-left'>
                <i class='fas fa-circle-exclamation mt-1 mr-4'></i>
                <span><?= $error ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class='mt-12 flex space-x-4'>
        <button onclick="window.history.back()" class='px-10 py-4 bg-slate-900 text-white font-black rounded-2xl hover:bg-slate-800 transition-all uppercase tracking-widest text-[10px] shadow-2xl shadow-slate-900/20'>
            Вернуться к редактированию
        </button>
        <button onclick="wm.closeWindow(this.closest('.window').id)" class='px-10 py-4 bg-white border border-slate-200 text-slate-400 font-black rounded-2xl hover:text-slate-600 transition-all uppercase tracking-widest text-[10px]'>
            Отменить операцию
        </button>
    </div>
</div>
