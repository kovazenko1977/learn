    </div> <!-- desktop-main -->
</div> <!-- xp-container -->

<div class="taskbar">
    <button class="start-button">
        <img src="https://gu-berezina.by/wp-content/uploads/2025/12/Screenshot_1-removebg-preview-1.png" style="height:20px;">
        пуск
    </button>
    <div class="taskbar-items">
        <div class="taskbar-item active">
            <img src="https://gu-berezina.by/wp-content/uploads/2025/12/Screenshot_1-removebg-preview-1.png" style="height:14px; margin-right:5px;">
            Санаторий Березина
        </div>
    </div>
    <div class="system-tray" id="clock-tray" title="Нажмите, чтобы увидеть календарь" onclick="toggleCalendar()">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c2/My_Documents_XP.png/16px-My_Documents_XP.png" style="height:14px; margin-right:5px; opacity:0.7;">
        <span id="taskbar-clock"><?php echo date('H:i'); ?></span>
    </div>
</div>

<!-- XP Style Calendar (Hidden by default) -->
<div id="xp-calendar" style="display: none; position: fixed; bottom: 35px; right: 5px; width: 220px; background: #ece9d8; border: 2px solid #0058e6; border-radius: 4px; box-shadow: -2px -2px 10px rgba(0,0,0,0.3); z-index: 3000;">
    <div class="title-bar" style="height: 20px; font-size: 11px; padding: 2px 5px;">
        <span>Свойства: Дата и время</span>
    </div>
    <div style="padding: 10px; text-align: center;">
        <div style="background: white; border: 1px inset #ccc; padding: 5px; margin-bottom: 5px; font-size: 14px; font-weight: bold;">
            <?php echo date('F Y'); ?>
        </div>
        <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
            <tr style="color: #666;"><th>Пн</th><th>Вт</th><th>Ср</th><th>Чт</th><th>Пт</th><th style="color:red">Сб</th><th style="color:red">Вс</th></tr>
            <?php
            $today = date('j');
            $start = date('w', strtotime(date('Y-m-01'))) - 1;
            if ($start < 0) $start = 6;
            $days = date('t');
            echo "<tr>";
            for ($i = 0; $i < $start; $i++) echo "<td></td>";
            for ($d = 1; $d <= $days; $d++) {
                if (($d + $start - 1) % 7 == 0 && $d > 1) echo "</tr><tr>";
                $style = ($d == $today) ? "background:#316ac5; color:white;" : "";
                echo "<td style='padding:2px; $style'>$d</td>";
            }
            echo "</tr>";
            ?>
        </table>
        <div style="margin-top: 10px; border-top: 1px solid #ccc; padding-top: 5px; font-size: 11px;">
            Текущее время: <span id="calendar-time"><?php echo date('H:i:s'); ?></span>
        </div>
        <button onclick="toggleCalendar()" style="margin-top: 10px; padding: 2px 10px; font-size: 10px;">Закрыть</button>
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
</body>
</html>
