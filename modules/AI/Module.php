<?php

declare(strict_types=1);

namespace App\Modules\AI;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/ai-chat', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        return "
        <div class='flex flex-col h-full max-w-2xl mx-auto'>
            <div class='mb-10 text-center'>
                <div class='w-24 h-24 rounded-[2.5rem] bg-gradient-to-br from-purple-500 to-blue-600 flex items-center justify-center text-white text-4xl shadow-2xl mx-auto mb-6 border-4 border-white'>
                    <i class='fas fa-brain'></i>
                </div>
                <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>AI Ассистент руководителя</h2>
                <p class='text-slate-500 font-medium'>Интеллектуальный анализ данных санатория в режиме реального времени.</p>
            </div>

            <div id='chat-history' class='flex-grow overflow-y-auto space-y-6 p-8 bg-slate-50 rounded-[3rem] mb-8 custom-scrollbar h-[420px] shadow-inner border border-slate-100'>
                <div class='flex items-start space-x-4'>
                    <div class='w-10 h-10 rounded-2xl bg-blue-600 flex items-center justify-center text-white shadow-lg'><i class='fas fa-robot text-sm'></i></div>
                    <div class='bg-white p-5 rounded-[1.5rem] rounded-tl-none shadow-sm border border-slate-100 max-w-[85%]'>
                        <p class='text-sm text-slate-700 font-bold leading-relaxed'>Добро пожаловать в систему Sanatorium AI. Я проанализировал текущие показатели: заезд сегодня составляет 12 человек, выручка в норме. Какой отчет вас интересует?</p>
                        <div class='mt-4 flex flex-wrap gap-2'>
                            <button onclick=\"document.getElementById('ai-input').value='Выручка за неделю'; askAI()\" class='px-3 py-1.5 bg-slate-50 hover:bg-blue-50 text-[10px] font-black text-slate-500 hover:text-blue-600 rounded-lg border border-slate-200 transition-all uppercase'>Выручка</button>
                            <button onclick=\"document.getElementById('ai-input').value='Свободные номера'; askAI()\" class='px-3 py-1.5 bg-slate-50 hover:bg-blue-50 text-[10px] font-black text-slate-500 hover:text-blue-600 rounded-lg border border-slate-200 transition-all uppercase'>Свободные места</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class='relative group'>
                <div class='absolute -inset-1 bg-gradient-to-r from-blue-600 to-purple-600 rounded-[2rem] blur opacity-20 group-focus-within:opacity-40 transition duration-500'></div>
                <div class='relative bg-white rounded-[2rem] shadow-2xl flex items-center p-2 border border-slate-100'>
                    <input type='text' id='ai-input' placeholder='Напишите запрос (напр. \"создай отчет по питанию\")' class='flex-grow bg-transparent pl-6 pr-4 py-4 text-sm font-black text-slate-800 outline-none'>
                    <button onclick='askAI()' class='w-14 h-14 bg-slate-900 text-white rounded-[1.5rem] flex items-center justify-center hover:bg-blue-600 transition-all shadow-xl active:scale-95'>
                        <i class='fas fa-paper-plane text-xs'></i>
                    </button>
                </div>
            </div>

            <script>
                async function askAI() {
                    const input = document.getElementById('ai-input');
                    const history = document.getElementById('chat-history');
                    if (!input.value) return;

                    const userMsg = input.value;
                    input.value = '';

                    history.innerHTML += `
                        <div class='flex items-start space-x-4 justify-end'>
                            <div class='bg-slate-900 p-5 rounded-[1.5rem] rounded-tr-none shadow-xl max-w-[85%] text-white'>
                                <p class='text-sm font-bold leading-relaxed'>\${userMsg}</p>
                            </div>
                        </div>
                    `;

                    setTimeout(() => {
                        let response = 'К сожалению, я не смог обработать этот запрос в текущей версии. Попробуйте запросить данные по выручке или номерному фонду.';
                        const text = userMsg.toLowerCase();
                        if (text.includes('выручка')) response = 'Валовая выручка за последние 7 дней составила **480 200 ₽**. Прогноз до конца месяца: **2.1 млн ₽**.';
                        if (text.includes('номера') || text.includes('места')) response = 'На данный момент в Реестре **4 свободных номера** (2 Стандарта, 1 Люкс и 1 Апартаменты).';

                        history.innerHTML += `
                            <div class='flex items-start space-x-4'>
                                <div class='w-10 h-10 rounded-2xl bg-blue-600 flex items-center justify-center text-white shadow-lg'><i class='fas fa-robot text-sm'></i></div>
                                <div class='bg-white p-5 rounded-[1.5rem] rounded-tl-none shadow-sm border border-slate-100 max-w-[85%]'>
                                    <p class='text-sm text-slate-700 font-bold leading-relaxed'>\${response}</p>
                                </div>
                            </div>
                        `;
                        history.scrollTop = history.scrollHeight;
                    }, 800);
                }
            </script>
        </div>
        ";
    }
}
