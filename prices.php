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

        <div style="display: flex; gap: 20px; margin-top: 25px;">
            <div style="flex: 1; background: #e1f5fe; padding: 15px; border: 1px solid #03a9f4;">
                <p><strong>Примечание:</strong></p>
                <ul style="font-size: 12px;">
                    <li>Ставка курортного сбора составляет 5% от стоимости путевки.</li>
                    <li>Для граждан РФ расчет производится по курсу НБРБ на день оплаты.</li>
                    <li>Наличие санаторно-курортной карты обязательно для путевок с лечением.</li>
                </ul>
            </div>

            <div style="width: 320px; background: #ece9d8; border: 2px solid #0058e6; padding: 10px; border-radius: 4px; box-shadow: 2px 2px 5px rgba(0,0,0,0.2);">
                <h4 style="margin-top: 0; color: #003399; display: flex; align-items: center; gap: 5px;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/1a/Money_Cash.svg/20px-Money_Cash.svg.png">
                    Калькулятор путевки
                </h4>
                <div style="display: flex; flex-direction: column; gap: 10px; font-size: 11px;">
                    <label>Категория проживания:
                        <select id="roomType" style="width: 100%;" onchange="calcVoucher()">
                            <option value="65">1-комнатный (65 BYN/сут)</option>
                            <option value="95">2-комнатный Люкс (95 BYN/сут)</option>
                            <option value="120">3-комнатный Люкс (120 BYN/сут)</option>
                        </select>
                    </label>
                    <label>Количество суток:
                        <input type="number" id="days" value="12" min="1" style="width: 100%;" oninput="calcVoucher()">
                    </label>
                    <div style="background: white; border: 1px inset #ccc; padding: 10px; text-align: center;">
                        <span style="font-size: 10px; color: #666;">Ориентировочная стоимость:</span><br>
                        <span id="totalPrice" style="color: #e60000; font-size: 20px; font-weight: bold;">780</span> <span style="font-weight: bold;">BYN</span>
                    </div>
                    <button onclick="window.location.href='booking.php'" class="xp-btn-large" style="padding: 5px; font-size: 12px;">Забронировать сейчас</button>
                </div>
            </div>
        </div>

        <script>
        function calcVoucher() {
            const price = document.getElementById('roomType').value;
            const days = document.getElementById('days').value;
            document.getElementById('totalPrice').innerText = price * days;
        }
        </script>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
