<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Orion Config Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="bg-gray-100 text-gray-900 overflow-x-hidden font-sans">
    <div id="app" class="flex flex-col min-h-screen">
        <!-- Header -->
        <header class="bg-slate-800 text-white p-4 sticky top-0 z-50 flex justify-between items-center safe-top shadow-lg">
            <div class="flex items-center space-x-3">
                <h1 class="text-xl font-bold tracking-tight">Orion Config Pro</h1>
                <div id="demoBadge" class="hidden bg-red-600 text-[10px] px-2 py-0.5 rounded-full font-bold animate-pulse uppercase tracking-wider">Demo</div>
            </div>
            <div class="flex items-center space-x-2">
                <button id="demoBtn" class="bg-slate-700 hover:bg-slate-600 text-xs px-4 py-1.5 rounded-full transition-all border border-slate-600 shadow-sm font-medium">Demo Mode</button>
                <button id="menuBtn" class="p-2 md:hidden rounded-full hover:bg-slate-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
            </div>
        </header>

        <div class="flex flex-1 relative">
            <!-- Sidebar -->
            <nav id="sidebar" class="fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-300 ease-in-out z-40 w-64 bg-slate-700 text-white p-6 shadow-xl">
                <ul class="space-y-4">
                    <li><a href="#" data-section="devices" class="nav-item flex items-center space-x-3 p-2 bg-slate-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg> <span>Устройства</span></a></li>
                    <li><a href="#" data-section="zones" class="nav-item flex items-center space-x-3 p-2 hover:bg-slate-600 rounded-lg opacity-70"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16m-7 6h7"></path></svg> <span>Разделы</span></a></li>
                    <li><a href="#" data-section="relays" class="nav-item flex items-center space-x-3 p-2 hover:bg-slate-600 rounded-lg opacity-70"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> <span>Реле</span></a></li>
                    <li><a href="#" data-section="scenarios" class="nav-item flex items-center space-x-3 p-2 hover:bg-slate-600 rounded-lg opacity-70"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg> <span>Сценарии</span></a></li>
                    <li><a href="#" data-section="log" class="nav-item flex items-center space-x-3 p-2 hover:bg-slate-600 rounded-lg opacity-70"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> <span>Журнал</span></a></li>
                </ul>
            </nav>

            <!-- Main Content -->
            <main class="flex-1 p-4 md:p-8 pb-24 overflow-y-auto max-h-[calc(100vh-64px)]">
                <div id="content" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Cards will be injected here -->
                </div>

                <!-- Event Log (Collapsible on mobile) -->
                <section class="mt-12 bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="bg-slate-50 p-4 border-b flex justify-between items-center">
                        <h2 class="font-bold text-slate-700 flex items-center"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"></path></svg> Журнал событий</h2>
                        <span id="eventCount" class="text-xs bg-slate-200 px-2 py-0.5 rounded-full text-slate-500">0</span>
                    </div>
                    <div id="eventLog" class="max-h-64 overflow-y-auto text-sm p-2 space-y-1">
                        <div class="text-center py-4 text-gray-400 italic">Событий пока нет...</div>
                    </div>
                </section>
            </main>
        </div>

        <!-- Bottom Mobile Nav -->
        <div class="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-md border-t flex justify-around p-3 md:hidden safe-bottom shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            <button class="flex flex-col items-center text-[10px] font-medium text-blue-600">
                <svg class="w-6 h-6 mb-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                <span>Устройства</span>
            </button>
            <button class="flex flex-col items-center text-[10px] font-medium text-gray-400">
                <svg class="w-6 h-6 mb-1" fill="currentColor" viewBox="0 0 20 20"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span>Разделы</span>
            </button>
            <button class="flex flex-col items-center text-[10px] font-medium text-gray-400">
                <svg class="w-6 h-6 mb-1" fill="currentColor" viewBox="0 0 20 20"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Журнал</span>
            </button>
        </div>
    </div>
    <script src="/js/api.js"></script>
    <script src="/js/store.js"></script>
    <script src="/js/components.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
