document.addEventListener('DOMContentLoaded', () => {
    const character = document.getElementById('character-container');
    const actionBtn = document.getElementById('action-btn');
    const autoBtn = document.getElementById('auto-btn');
    const block = document.getElementById('block');
    const scoreText = document.getElementById('score');
    const coinsText = document.getElementById('coins');
    const eventLog = document.getElementById('event-log');

    let score = 0;
    let coins = 0;
    let isJumping = false;
    let isAutoPlaying = false;
    let autoInterval = null;

    const gameMessages = [
        "СУПЕР ПРЫЖОК!",
        "НАШЕЛ СЕКРЕТ!",
        "ВПЕРЕД, ШЕРИФ!",
        "ПОЧТИ У ЦЕЛИ!",
        "КОЛЮЧИЙ ГЕРОЙ!",
        "БОНУС ПОЛУЧЕН!"
    ];

    function updateScore(amount) {
        score += amount;
        scoreText.textContent = score.toString().padStart(6, '0');
    }

    function addCoin() {
        coins++;
        coinsText.textContent = `x${coins.toString().padStart(2, '0')}`;
        updateScore(200);

        // Visual feedback for coin
        const coinEffect = document.createElement('div');
        coinEffect.className = 'coin-popup';
        coinEffect.textContent = '+200';
        coinEffect.style.left = `${block.offsetLeft}px`;
        coinEffect.style.top = `${block.offsetTop - 20}px`;
        document.body.appendChild(coinEffect);
        setTimeout(() => coinEffect.remove(), 500);
    }

    function showMessage() {
        const msg = gameMessages[Math.floor(Math.random() * gameMessages.length)];
        eventLog.textContent = msg;
    }

    function jump() {
        if (isJumping) return;

        isJumping = true;
        character.classList.add('jump-animation');

        // Trigger block bump if timed right
        setTimeout(() => {
            block.classList.add('block-bump');
            addCoin();
            showMessage();
            setTimeout(() => block.classList.remove('block-bump'), 100);
        }, 200);

        setTimeout(() => {
            character.classList.remove('jump-animation');
            isJumping = false;
        }, 400);
    }

    actionBtn.addEventListener('click', jump);

    // Support for physical spacebar
    document.addEventListener('keydown', (e) => {
        if (e.code === 'Space') {
            e.preventDefault();
            jump();
        }
    });

    autoBtn.addEventListener('click', () => {
        isAutoPlaying = !isAutoPlaying;

        if (isAutoPlaying) {
            autoBtn.textContent = 'СТОП';
            autoBtn.style.backgroundColor = '#ff4444';
            autoBtn.style.color = '#fff';
            eventLog.textContent = "АВТО-РЕЖИМ ВКЛЮЧЕН";

            autoInterval = setInterval(() => {
                if (!isJumping) jump();
            }, 800);
        } else {
            autoBtn.textContent = 'АВТО-ИГРА';
            autoBtn.style.backgroundColor = '#ffcc00';
            autoBtn.style.color = '#000';
            eventLog.textContent = "АВТО-РЕЖИМ ВЫКЛЮЧЕН";
            clearInterval(autoInterval);
        }
    });
});
