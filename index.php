<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MailSender iOS</title>
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="MailSender">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=SF+Pro+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f2f2f7;
            -webkit-tap-highlight-color: transparent;
        }
        .ios-blur {
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .ios-btn-active:active {
            opacity: 0.6;
        }
        .ios-card {
            background: white;
            border-radius: 10px;
            margin: 16px;
        }
        .ios-item {
            padding: 12px 16px;
            border-bottom: 0.5px solid #c6c6c8;
        }
        .ios-item:last-child {
            border-bottom: none;
        }
        .ios-input {
            width: 100%;
            padding: 12px 16px;
            background: transparent;
            outline: none;
        }
        .tab-btn.active {
            color: #007aff;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        /* PIN Pad Styles */
        .pin-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 1px solid #000;
            margin: 0 8px;
        }
        .pin-dot.filled {
            background-color: #000;
        }
        .keypad-btn {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #e5e5ea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 10px;
        }
        .keypad-btn:active {
            background: #c7c7cc;
        }
    </style>
</head>
<body class="h-screen flex flex-col max-w-md mx-auto bg-gray-100 shadow-2xl relative overflow-hidden">

    <!-- Login Screen -->
    <div id="login-screen" class="absolute inset-0 bg-white z-50 flex flex-col items-center justify-center">
        <h1 class="text-xl font-semibold mb-8">Enter Passcode</h1>
        <div class="flex mb-12">
            <div class="pin-dot" id="dot-0"></div>
            <div class="pin-dot" id="dot-1"></div>
            <div class="pin-dot" id="dot-2"></div>
            <div class="pin-dot" id="dot-3"></div>
            <div class="pin-dot" id="dot-4"></div>
            <div class="pin-dot" id="dot-5"></div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <button class="keypad-btn" onclick="appendPin('1')">1</button>
            <button class="keypad-btn" onclick="appendPin('2')">2</button>
            <button class="keypad-btn" onclick="appendPin('3')">3</button>
            <button class="keypad-btn" onclick="appendPin('4')">4</button>
            <button class="keypad-btn" onclick="appendPin('5')">5</button>
            <button class="keypad-btn" onclick="appendPin('6')">6</button>
            <button class="keypad-btn" onclick="appendPin('7')">7</button>
            <button class="keypad-btn" onclick="appendPin('8')">8</button>
            <button class="keypad-btn" onclick="appendPin('9')">9</button>
            <div></div>
            <button class="keypad-btn" onclick="appendPin('0')">0</button>
            <button class="flex items-center justify-center" onclick="clearPin()">
                <i data-lucide="delete"></i>
            </button>
        </div>
    </div>

    <!-- Main App Structure -->
    <header class="bg-white/80 ios-blur sticky top-0 z-40 px-4 pt-12 pb-2 flex justify-between items-center border-b border-gray-200">
        <h1 id="page-title" class="text-3xl font-bold">MailSender</h1>
        <button id="add-btn" class="text-[#007aff] hidden">
            <i data-lucide="plus"></i>
        </button>
    </header>

    <main class="flex-1 overflow-y-auto pb-20">
        <!-- Emails Tab -->
        <div id="emails-tab" class="tab-content">
            <div class="ios-card">
                <div class="ios-item flex items-center">
                    <i data-lucide="search" class="text-gray-400 mr-2"></i>
                    <input type="text" placeholder="Search emails..." class="ios-input" id="search-emails">
                </div>
            </div>
            <div id="email-list" class="ios-card overflow-hidden">
                <!-- Emails will be loaded here -->
            </div>
        </div>

        <!-- Templates Tab -->
        <div id="templates-tab" class="tab-content">
            <div id="template-list" class="ios-card overflow-hidden">
                <!-- Templates will be loaded here -->
            </div>
        </div>

        <!-- Send Tab -->
        <div id="send-tab" class="tab-content">
            <div class="ios-card overflow-hidden">
                <div class="ios-item">
                    <label class="block text-xs text-gray-500 mb-1">To:</label>
                    <div class="flex items-center">
                        <select id="send-to-select" class="ios-input !p-0">
                            <option value="bulk">All in Database</option>
                        </select>
                    </div>
                </div>
                <div class="ios-item">
                    <label class="block text-xs text-gray-500 mb-1">Template:</label>
                    <div class="flex items-center">
                        <select id="send-template-select" class="ios-input !p-0">
                            <option value="">No Template</option>
                        </select>
                    </div>
                </div>
                <div class="ios-item">
                    <label class="block text-xs text-gray-500 mb-1">Subject:</label>
                    <input type="text" id="send-subject" class="ios-input !p-0" placeholder="Subject">
                </div>
                <div class="ios-item relative">
                    <label class="block text-xs text-gray-500 mb-1">Message:</label>
                    <textarea id="send-body" rows="8" class="ios-input !p-0" placeholder="Your message..."></textarea>
                    <button onclick="startVoiceInput('send-body')" class="absolute right-4 bottom-4 text-[#007aff] ios-btn-active">
                        <i data-lucide="mic"></i>
                    </button>
                </div>
            </div>
            <div class="px-4">
                <button id="send-now-btn" class="w-full bg-[#007aff] text-white py-3 rounded-xl font-semibold ios-btn-active">Send Now</button>
            </div>
        </div>

        <!-- Settings Tab -->
        <div id="settings-tab" class="tab-content">
            <div class="ios-card overflow-hidden">
                <div class="ios-item flex justify-between items-center">
                    <span>Sending Interval (sec)</span>
                    <input type="number" id="setting-interval" class="w-16 text-right outline-none" value="5">
                </div>
                <div class="ios-item">
                    <label class="block text-xs text-gray-500 mb-1">Change PIN (6 digits)</label>
                    <input type="password" id="setting-pin" class="ios-input !p-0" maxlength="6" placeholder="New PIN">
                </div>
            </div>
            <div class="px-4 mb-4">
                <button id="save-settings-btn" class="w-full bg-[#007aff] text-white py-3 rounded-xl font-semibold ios-btn-active">Save Settings</button>
            </div>
            <div class="px-4">
                <button id="logout-btn" class="w-full bg-red-500 text-white py-3 rounded-xl font-semibold ios-btn-active">Logout</button>
            </div>
        </div>
    </main>

    <!-- Navigation Bar -->
    <nav class="fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-white/80 ios-blur border-t border-gray-200 flex justify-around py-2 z-40">
        <button onclick="switchTab('emails')" class="tab-btn flex flex-col items-center text-gray-400" id="tab-emails">
            <i data-lucide="users"></i>
            <span class="text-[10px] mt-1">Emails</span>
        </button>
        <button onclick="switchTab('templates')" class="tab-btn flex flex-col items-center text-gray-400" id="tab-templates">
            <i data-lucide="file-text"></i>
            <span class="text-[10px] mt-1">Templates</span>
        </button>
        <button onclick="switchTab('send')" class="tab-btn flex flex-col items-center text-gray-400" id="tab-send">
            <i data-lucide="send"></i>
            <span class="text-[10px] mt-1">Send</span>
        </button>
        <button onclick="switchTab('settings')" class="tab-btn flex flex-col items-center text-gray-400" id="tab-settings">
            <i data-lucide="settings"></i>
            <span class="text-[10px] mt-1">Settings</span>
        </button>
    </nav>

    <!-- Modals -->
    <div id="modal-backdrop" class="fixed inset-0 bg-black/40 z-[60] hidden flex items-center justify-center p-4">
        <div id="modal-content" class="bg-white rounded-2xl w-full max-w-xs overflow-hidden">
            <!-- Modal content injected by JS -->
        </div>
    </div>

    <script src="public/js/app.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
