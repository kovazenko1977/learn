document.addEventListener('DOMContentLoaded', () => {
    const characterImg = document.getElementById('character-img');
    const patrolBtn = document.getElementById('patrol-btn');
    const bountyBtn = document.getElementById('bounty-btn');
    const statusText = document.getElementById('status').querySelector('.highlight');
    const rewardText = document.getElementById('reward-amount');
    const eventLog = document.getElementById('event-log');

    let isPatrolling = false;
    let gold = 5000;

    const patrolEvents = [
        "Заметил перекати-поле.",
        "Поправил шляпу.",
        "Проверил салун на наличие нарушителей.",
        "Посмотрел на солнце.",
        "Сдул пылинку со звезды.",
        "Запугал кактус-самозванец."
    ];

    function updateEvent(text) {
        eventLog.textContent = text;
    }

    patrolBtn.addEventListener('click', () => {
        isPatrolling = !isPatrolling;

        if (isPatrolling) {
            patrolBtn.textContent = 'ОСТАНОВИТЬ ПАТРУЛЬ';
            statusText.textContent = 'Патрулирует город';
            characterImg.classList.add('patrolling-anim');
            bountyBtn.disabled = true;

            // Random events during patrol
            const interval = setInterval(() => {
                if (!isPatrolling) {
                    clearInterval(interval);
                    return;
                }
                const randomEvent = patrolEvents[Math.floor(Math.random() * patrolEvents.length)];
                updateEvent(randomEvent);
            }, 2000);

            updateEvent("Начал обход территории...");
        } else {
            patrolBtn.textContent = 'ОТПРАВИТЬ В ПАТРУЛЬ';
            statusText.textContent = 'Охраняет город';
            characterImg.classList.remove('patrolling-anim');
            bountyBtn.disabled = false;
            updateEvent("");
        }
    });

    bountyBtn.addEventListener('click', () => {
        const reward = Math.floor(Math.random() * 500) + 100;
        gold += reward;
        rewardText.textContent = gold.toLocaleString();

        statusText.textContent = `Получено +${reward} золота!`;
        updateEvent("Шериф доволен наградой.");

        characterImg.classList.add('celebrate-anim');
        bountyBtn.disabled = true;
        patrolBtn.disabled = true;

        setTimeout(() => {
            characterImg.classList.remove('celebrate-anim');
            statusText.textContent = 'Охраняет город';
            updateEvent("");
            bountyBtn.disabled = false;
            patrolBtn.disabled = false;
        }, 2000);
    });
});
