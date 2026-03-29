<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocMailer - Document Distribution</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="light-theme">
    <div id="login-screen" style="display: none;">
        <div class="card login-card">
            <div class="logo">DocMailer</div>
            <p>Please enter your access passcode</p>
            <input type="password" id="passcode-input" placeholder="Passcode">
            <button class="btn" id="login-btn" style="width: 100%;">Access Application</button>
            <p id="login-error" style="color: var(--error-color); margin-top: 10px; display: none;">Invalid passcode</p>
        </div>
    </div>

    <div id="app" style="display: none;">
        <header>
            <div class="logo">DocMailer <span>v1.0</span></div>
            <nav>
                <button class="nav-btn active" data-view="dashboard">Dashboard</button>
                <button class="nav-btn" data-view="recipients">Recipients</button>
                <button class="nav-btn" data-view="mailing">Mailing</button>
                <button class="nav-btn" data-view="settings">Settings</button>
                <button class="btn btn-secondary" id="logout-btn">Logout</button>
            </nav>
        </header>

        <main id="main-content">
            <!-- Content will be injected by app.js -->
        </main>

        <div id="toast-container"></div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
