<nav id="sidebar" class="nav-drawer">
    <div style="padding: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <span style="font-weight: 600; font-size: 14px;">Закрепленные</span>
            <button style="background: white; border: 1px solid #ccc; border-radius: 4px; font-size: 12px; padding: 2px 8px; cursor: pointer;">Все приложения ></button>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px;">
            <a href="index.php" style="text-decoration: none; color: #333; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                <img src="https://img.icons8.com/fluency/48/000000/home.png" style="width: 32px;">
                <span style="font-size: 11px;">Главная</span>
            </a>
            <a href="procedures.php" style="text-decoration: none; color: #333; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                <img src="https://img.icons8.com/fluency/48/000000/syringe.png" style="width: 32px;">
                <span style="font-size: 11px;">Лечение</span>
            </a>
            <a href="rooms.php" style="text-decoration: none; color: #333; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                <img src="https://img.icons8.com/fluency/48/000000/bedroom.png" style="width: 32px;">
                <span style="font-size: 11px;">Номера</span>
            </a>
            <a href="prices.php" style="text-decoration: none; color: #333; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                <img src="https://img.icons8.com/fluency/48/000000/money-bag.png" style="width: 32px;">
                <span style="font-size: 11px;">Цены</span>
            </a>
            <a href="booking.php" style="text-decoration: none; color: #333; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                <img src="https://img.icons8.com/fluency/48/000000/calendar.png" style="width: 32px;">
                <span style="font-size: 11px;">Бронирование</span>
            </a>
            <a href="search.php" style="text-decoration: none; color: #333; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                <img src="https://img.icons8.com/fluency/48/000000/search.png" style="width: 32px;">
                <span style="font-size: 11px;">Поиск</span>
            </a>
        </div>

        <div style="font-weight: 600; font-size: 14px; margin-bottom: 15px;">Рекомендуем</div>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <a href="infrastructure.php" style="display: flex; align-items: center; gap: 15px; text-decoration: none; color: #333;">
                <img src="https://img.icons8.com/fluency/48/000000/park.png" style="width: 32px;">
                <div>
                    <div style="font-size: 12px; font-weight: 600;">Территория санатория</div>
                    <div style="font-size: 10px; opacity: 0.6;">Прогулки по сосновому бору</div>
                </div>
            </a>
            <a href="dining.php" style="display: flex; align-items: center; gap: 15px; text-decoration: none; color: #333;">
                <img src="https://img.icons8.com/fluency/48/000000/restaurant.png" style="width: 32px;">
                <div>
                    <div style="font-size: 12px; font-weight: 600;">Наше питание</div>
                    <div style="font-size: 10px; opacity: 0.6;">5-разовое заказное меню</div>
                </div>
            </a>
        </div>
    </div>

    <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 15px 30px; background: rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; gap: 10px;">
            <img src="https://img.icons8.com/fluency/48/000000/user-male-circle.png" style="width: 32px;">
            <span style="font-size: 12px; font-weight: 600;">Гость Санатория</span>
        </div>
        <img src="https://img.icons8.com/fluency/48/000000/shutdown.png" style="width: 20px; cursor: pointer;">
    </div>
</nav>
