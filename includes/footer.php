    </div> <!-- desktop-main -->
</div> <!-- xp-container -->

<!-- Android Style Bottom Navigation -->
<nav class="mobile-nav">
    <a href="index.php" class="mobile-nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
        <img src="https://img.icons8.com/fluency/48/000000/home.png">
        <span>Главная</span>
    </a>
    <a href="procedures.php" class="mobile-nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'procedures.php') ? 'active' : ''; ?>">
        <img src="https://img.icons8.com/fluency/48/000000/syringe.png">
        <span>Услуги</span>
    </a>
    <a href="booking.php" class="mobile-nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'booking.php') ? 'active' : ''; ?>">
        <img src="https://img.icons8.com/fluency/48/000000/calendar.png">
        <span>Запись</span>
    </a>
    <a href="contact.php" class="mobile-nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : ''; ?>">
        <img src="https://img.icons8.com/fluency/48/000000/address-book.png">
        <span>Инфо</span>
    </a>
</nav>

<!-- PWA Install Banner -->
<div id="install-banner">
    <span>Установить как приложение?</span>
    <div style="display: flex; gap: 8px;">
        <button id="install-btn">Установить</button>
        <button onclick="this.parentElement.parentElement.style.display='none'" style="background:transparent; border:1px solid white;">Позже</button>
    </div>
</div>

<div class="taskbar">
    <div class="taskbar-center-group">
        <button class="start-button" title="Пуск">
            <img src="https://img.icons8.com/fluency/48/000000/windows-11.png" style="height:32px;">
        </button>
        <div class="taskbar-item" onclick="window.location.href='search.php'" title="Поиск">
            <img src="https://img.icons8.com/fluency/48/000000/search.png" style="height:24px;">
        </div>
        <div class="taskbar-items">
            <div class="taskbar-item active" title="Санаторий Березина">
                <img src="https://img.icons8.com/fluency/48/000000/hospital-sign.png" style="height:24px;">
            </div>
        </div>
        <div class="taskbar-item" onclick="window.location.href='procedures.php'" title="Процедуры">
            <img src="https://img.icons8.com/fluency/48/000000/syringe.png" style="height:24px;">
        </div>
    </div>
    <div class="system-tray" id="clock-tray" title="Нажмите, чтобы увидеть календарь" onclick="toggleCalendar()">
        <div style="text-align: right; line-height: 1.2;">
            <div id="taskbar-clock" style="font-weight: 500;"><?php echo date('H:i'); ?></div>
            <div style="font-size: 10px; opacity: 0.8;"><?php echo date('d.m.Y'); ?></div>
        </div>
    </div>
</div>

<!-- Windows 11 Style Calendar -->
<div id="xp-calendar" style="display: none; position: fixed; bottom: 60px; right: 10px; width: 320px; background: var(--win-mica); backdrop-filter: blur(40px); border: var(--win-border); border-radius: 12px; box-shadow: var(--win-shadow); z-index: 3000; padding: 20px;">
    <div style="text-align: left; margin-bottom: 20px;">
        <div style="font-size: 24px; font-weight: 300;" id="calendar-time"><?php echo date('H:i:s'); ?></div>
        <div style="font-size: 14px; color: var(--win-accent); font-weight: 600;"><?php echo date('l, d F'); ?></div>
    </div>
    <div style="padding: 10px; border-top: 1px solid rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <span style="font-weight: 600; font-size: 14px;"><?php echo date('F Y'); ?></span>
            <div style="display: flex; gap: 10px;">
                <span style="cursor: pointer;"></span>
                <span style="cursor: pointer;"></span>
            </div>
        </div>
        <table style="width: 100%; font-size: 12px; border-collapse: collapse; text-align: center;">
            <tr style="opacity: 0.6; font-size: 10px;"><th>ПН</th><th>ВТ</th><th>СР</th><th>ЧТ</th><th>ПТ</th><th>СБ</th><th>ВС</th></tr>
            <?php
            $today = date('j');
            $start = date('w', strtotime(date('Y-m-01'))) - 1;
            if ($start < 0) $start = 6;
            $days = date('t');
            echo "<tr>";
            for ($i = 0; $i < $start; $i++) echo "<td></td>";
            for ($d = 1; $d <= $days; $d++) {
                if (($d + $start - 1) % 7 == 0 && $d > 1) echo "</tr><tr>";
                $isToday = ($d == $today);
                $style = $isToday ? "background: var(--win-accent); color: white; border-radius: 50%;" : "";
                echo "<td style='padding: 8px 0; cursor: pointer; transition: background 0.2s;' onmouseover=\"if(!this.style.background)this.style.background='rgba(0,0,0,0.05)'\" onmouseout=\"if(!this.style.color)this.style.background=''\"><span style='display:inline-block; width:28px; height:28px; line-height:28px; $style'>$d</span></td>";
            }
            echo "</tr>";
            ?>
        </table>
    </div>
</div>

<script>
function toggleCalendar() {
    const cal = document.getElementById('xp-calendar');
    cal.style.display = cal.style.display === 'none' ? 'block' : 'none';
}

// Update clock every minute
setInterval(() => {
    const now = new Date();
    const timeStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    const fullTimeStr = timeStr + ':' + now.getSeconds().toString().padStart(2, '0');
    if (document.getElementById('taskbar-clock')) document.getElementById('taskbar-clock').innerText = timeStr;
    if (document.getElementById('calendar-time')) document.getElementById('calendar-time').innerText = fullTimeStr;
}, 1000);
</script>

<script src="assets/js/app.js"></script>
<!-- Automated Chatbot Widget Integration -->
<script src="assets/js/loader.js"></script>
</body>
</html>
