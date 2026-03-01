<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';

// A simple script to render the official proposal with the app's theme
include 'includes/header.php';
?>

<div class="container" style="max-width: 900px; margin-bottom: 80px;">
    <div class="card mica" style="padding: 40px; line-height: 1.8; font-size: 15px;">
        <div style="text-align: center; margin-bottom: 40px; border-bottom: 2px solid var(--win-accent); padding-bottom: 20px;">
            <h1 style="color: var(--win-accent); margin-bottom: 10px;">Официальное предложение</h1>
            <p style="text-transform: uppercase; letter-spacing: 1px; font-weight: bold; font-size: 12px; color: var(--win-text-secondary);">Внедрение системы «ХОП» в медицинские учреждения</p>
        </div>

        <div class="proposal-content">
            <p><strong>Отправитель:</strong> Коваженко Сергей Борисович, заведующий хозяйством (завхоз).</p>
            <p><strong>Тема:</strong> Автоматизация и цифровизация службы АХЧ и технических подразделений медицинского учреждения.</p>

            <hr style="margin: 30px 0; opacity: 0.1;">

            <h3 style="color: var(--win-accent);">Уважаемые коллеги и руководство!</h3>
            <p>Добрый день! Меня зовут Сергей Борисович Коваженко. Работая заведующим хозяйством в больнице, я каждый день сталкиваюсь с колоссальным объемом оперативных задач: от перегоревшей лампочки в операционной до срочного ремонта сантехники в приемном покое.</p>
            <p>Годы практики показали, что традиционные методы управления — записи в журналах, телефонные звонки, устные просьбы — безнадежно устарели. Информация теряется, сроки нарушаются, а оценить реальную нагрузку сотрудников и эффективность служб практически невозможно.</p>
            <p>Исходя из этого опыта, я разработал специализированное программное обеспечение <strong>«ХОП» (Хозяйственно-Оперативные Поручения)</strong>. Это не просто «еще одно приложение», это рабочий инструмент, созданный «от земли» для решения конкретных болей медицинского персонала и администрации.</p>

            <h3 style="margin-top: 40px; color: var(--win-accent);">1. Концепция и ключевые преимущества</h3>
            <p>Система «ХОП» — это веб-приложение, работающее по принципу <strong>Mobile-First</strong>. Это значит, что оно идеально работает на смартфонах, которые всегда в кармане у санитарок, инженеров и мастеров.</p>
            <ul>
                <li><strong>Прозрачность:</strong> Каждая заявка имеет автора, исполнителя, историю изменений и фотофиксацию.</li>
                <li><strong>Скорость:</strong> Передача заявки от отделения до мастера занимает 2 секунды.</li>
                <li><strong>Контроль:</strong> Система автоматически подсвечивает просроченные задачи и присылает уведомления.</li>
                <li><strong>Автономность:</strong> Программа не требует дорогостоящих серверов и баз данных.</li>
            </ul>

            <h3 style="margin-top: 40px; color: var(--win-accent);">2. Функциональные возможности</h3>
            <p>В программе реализовано 6 уровней доступа, что обеспечивает строгую дисциплину: от Инициатора (врача) до Руководителя. Каждое поручение проходит через четкую цепочку статусов, исключая путаницу.</p>
            <p>К каждой заявке можно прикрепить фото проблемы (ДО) и результата (ПОСЛЕ). Внутри каждой задачи есть встроенный чат для оперативного уточнения деталей без лишних звонков.</p>

            <h3 style="margin-top: 40px; color: var(--win-accent);">3. Визуальный интерфейс и работа в системе</h3>
            <p>Программа выполнена в современном стиле <strong>Windows 11 (Mica)</strong>, что делает её привычной и понятной для любого пользователя.</p>

            <div style="margin: 30px 0; text-align: center;">
                <img src="final_dashboard_compact.png" style="max-width: 100%; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid var(--win-border);" alt="Мобильный интерфейс">
                <p style="font-size: 13px; color: var(--win-text-secondary); margin-top: 10px;">Рис 1. Мобильный интерфейс и навигация одним пальцем</p>
            </div>

            <p>Для руководства предусмотрена мощная аналитическая панель. Вы в реальном времени видите нагрузку по службам, рейтинг эффективности сотрудников (KPI) и среднее время выполнения задач.</p>

            <div style="margin: 30px 0; text-align: center;">
                <img src="final_analytics.png" style="max-width: 100%; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid var(--win-border);" alt="Панель аналитики">
                <p style="font-size: 13px; color: var(--win-text-secondary); margin-top: 10px;">Рис 2. Аналитическая панель контроля и отчетности</p>
            </div>

            <p>Система также формирует персональные карточки эффективности для каждого сотрудника, что позволяет объективно оценивать вклад каждого члена команды.</p>

            <div style="margin: 30px 0; text-align: center;">
                <img src="final_staff_report.png" style="max-width: 100%; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid var(--win-border);" alt="Отчет по сотруднику">
                <p style="font-size: 13px; color: var(--win-text-secondary); margin-top: 10px;">Рис 3. Персональный KPI и отчетность сотрудника</p>
            </div>

            <h3 style="margin-top: 40px; color: var(--win-accent);">4. Экономический и социальный эффект</h3>
            <ol>
                <li><strong>Экономия времени:</strong> Сокращение времени на передачу и уточнение заявок на 40%.</li>
                <li><strong>Удлинение срока службы оборудования:</strong> За счет своевременного обслуживания.</li>
                <li><strong>Повышение лояльности персонала:</strong> Врачи не тратят время на бытовые проблемы.</li>
                <li><strong>Дисциплина:</strong> Персональная ответственность каждого исполнителя.</li>
            </ol>

            <hr style="margin: 50px 0; opacity: 0.1;">

            <div style="background: rgba(0,0,0,0.02); padding: 30px; border-radius: 20px; text-align: center; border: 1px dashed var(--win-border);">
                <p style="margin: 0; font-size: 16px;">Я готов лично провести презентацию, обучить сотрудников и адаптировать систему под специфику вашего отделения.</p>
                <p style="margin-top: 20px; font-weight: bold; font-size: 18px;">С уважением, Коваженко С.Б.</p>
                <p style="font-size: 13px; color: var(--win-text-secondary);">Заведующий хозяйством. Разработано для профессионалов.</p>
            </div>
        </div>

        <div style="margin-top: 40px; text-align: center;">
            <a href="help.php" class="btn-secondary">Вернуться в Центр поддержки</a>
        </div>
    </div>
</div>

<style>
.proposal-content h3 { border-left: 4px solid var(--win-accent); padding-left: 15px; margin-bottom: 20px; }
.proposal-content p { margin-bottom: 15px; }
.proposal-content ul, .proposal-content ol { margin-bottom: 20px; padding-left: 30px; }
.proposal-content li { margin-bottom: 8px; }
</style>

<?php include 'includes/footer.php'; ?>
