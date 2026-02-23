<?php include 'includes/header.php'; ?>

<div class="window">
    <div class="title-bar">
        <div class="title-bar-text">Цены и путевки - Блокнот</div>
        <div class="title-bar-controls">
            <button aria-label="Minimize"></button>
            <button aria-label="Maximize"></button>
            <button aria-label="Close" onclick="window.location.href='index.php'"></button>
        </div>
    </div>

    <div class="window-body" style="padding: 20px; overflow-y: auto;">
        <p>Цены на путевки в санатории «Березина» на текущий период. Пожалуйста, уточняйте наличие мест по телефону +375 177 92-99-69.</p>

        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <thead>
                <tr style="background: #f0f0f0; border-bottom: 2px solid #999;">
                    <th style="padding: 10px; text-align: left; border: 1px solid #ccc;">Тип путевки</th>
                    <th style="padding: 10px; text-align: left; border: 1px solid #ccc;">Длительность</th>
                    <th style="padding: 10px; text-align: left; border: 1px solid #ccc;">Особенности</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ccc;">С лечением</td>
                    <td style="padding: 10px; border: 1px solid #ccc;">от 15 дней</td>
                    <td style="padding: 10px; border: 1px solid #ccc;">Полный комплекс процедур, питание, проживание.</td>
                </tr>
                <tr style="background: #fafafa;">
                    <td style="padding: 10px; border: 1px solid #ccc;">Оздоровительная</td>
                    <td style="padding: 10px; border: 1px solid #ccc;">от 7 дней</td>
                    <td style="padding: 10px; border: 1px solid #ccc;">Проживание, питание, базовые процедуры.</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ccc;">Тур выходного дня</td>
                    <td style="padding: 10px; border: 1px solid #ccc;">2-3 дня</td>
                    <td style="padding: 10px; border: 1px solid #ccc;">Релакс, бассейн, сауна, фитотерапия.</td>
                </tr>
                <tr style="background: #fafafa;">
                    <td style="padding: 10px; border: 1px solid #ccc;">Курсовка</td>
                    <td style="padding: 10px; border: 1px solid #ccc;">от 1 дня</td>
                    <td style="padding: 10px; border: 1px solid #ccc;">Только лечение без проживания и питания.</td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 25px; background: #e1f5fe; padding: 15px; border: 1px solid #03a9f4;">
            <p><strong>Примечание:</strong></p>
            <ul>
                <li>Ставка курортного сбора составляет 5% от стоимости путевки.</li>
                <li>Для граждан РФ расчет производится по курсу НБРБ на день оплаты.</li>
                <li>Наличие санаторно-курортной карты обязательно для путевок с лечением.</li>
            </ul>
        </div>

        <div style="margin-top: 20px; text-align: center;">
            <button onclick="window.location.href='booking.php'" style="padding: 10px 20px; font-weight: bold; cursor: pointer;">Перейти к бронированию</button>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
