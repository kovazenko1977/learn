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
        $router->addRoute('POST', '/api/ai/ask', [$this, 'ask']);
    }

    public function index($request, $response): string
    {
        return "
        <div class='flex flex-col h-full max-w-2xl mx-auto'>
            <div class='mb-8 text-center'>
                <div class='w-20 h-20 rounded-[2rem] bg-gradient-to-br from-purple-500 to-blue-600 flex items-center justify-center text-white text-3xl shadow-xl mx-auto mb-4'>
                    <i class='fas fa-brain'></i>
                </div>
                <h2 class='text-2xl font-bold text-slate-800 tracking-tight'>AI Ассистент</h2>
                <p class='text-slate-500 text-sm'>Задайте вопрос о загрузке, финансах или гостях.</p>
            </div>

            <div id='chat-history' class='flex-grow overflow-y-auto space-y-4 p-6 bg-slate-50 rounded-[2.5rem] mb-6 custom-scrollbar h-[400px]'>
                <div class='flex items-start space-x-3'>
                    <div class='w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center text-white text-xs'><i class='fas fa-robot'></i></div>
                    <div class='bg-white p-4 rounded-2xl rounded-tl-none shadow-sm max-w-[80%]'>
                        <p class='text-sm text-slate-700 font-medium'>Здравствуйте! Я помогу вам проанализировать работу санатория. Что вас интересует?</p>
                    </div>
                </div>
            </div>

            <div class='relative'>
                <input type='text' id='ai-input' placeholder='Например: какая выручка за месяц?' class='w-full bg-white border border-slate-100 rounded-3xl pl-6 pr-16 py-4 text-sm font-semibold shadow-xl focus:ring-4 focus:ring-blue-500/10 outline-none'>
                <button onclick='askAI()' class='absolute right-2 top-1/2 -translate-y-1/2 w-12 h-12 bg-blue-600 text-white rounded-2xl flex items-center justify-center hover:bg-blue-500 transition-all'>
                    <i class='fas fa-paper-plane text-xs'></i>
                </button>
            </div>

            <script>
                async function askAI() {
                    const input = document.getElementById('ai-input');
                    const history = document.getElementById('chat-history');
                    if (!input.value) return;

                    const userMsg = input.value;
                    input.value = '';

                    // Add user message
                    history.innerHTML += `
                        <div class='flex items-start space-x-3 justify-end'>
                            <div class='bg-blue-600 p-4 rounded-2xl rounded-tr-none shadow-sm max-w-[80%] text-white'>
                                <p class='text-sm font-medium'>\${userMsg}</p>
                            </div>
                        </div>
                    `;

                    // Mock API call
                    setTimeout(() => {
                        let response = 'Я анализирую данные...';
                        if (userMsg.toLowerCase().includes('выручка')) response = 'Общая выручка на текущий момент составляет около 2.4 млн рублей. Это на 12% больше плана.';
                        if (userMsg.toLowerCase().includes('номера')) response = 'На данный момент свободно 14 номеров категории Стандарт и 2 номера Люкс.';

                        history.innerHTML += `
                            <div class='flex items-start space-x-3'>
                                <div class='w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center text-white text-xs'><i class='fas fa-robot'></i></div>
                                <div class='bg-white p-4 rounded-2xl rounded-tl-none shadow-sm max-w-[80%]'>
                                    <p class='text-sm text-slate-700 font-medium'>\${response}</p>
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
