document.addEventListener('DOMContentLoaded', () => {
    const character = document.getElementById('character-container');
    const actionBtn = document.getElementById('action-btn');
    const autoBtn = document.getElementById('auto-btn');
    const gameWorld = document.querySelector('.game-world');
    const block = document.getElementById('block');
    const bottleImg = document.getElementById('bottle-img');
    const scoreText = document.getElementById('score');
    const coinsText = document.getElementById('coins');
    const eventLog = document.getElementById('event-log');

    let score = 0;
    let coins = 0;
    let isJumping = false;
    let isAutoPlaying = false;
    let autoInterval = null;

    const flavors = [
        { name: "ЧИНАЗЕС ЛИМОН", img: "assets/bottle_lemon.png" },
        { name: "ЧИНАЗЕС ГРУША", img: "assets/bottle_pear.png" },
        { name: "ЧИНАЗЕС ВИШНЯ", img: "assets/bottle_cherry.png" }
    ];

    let currentFlavor = flavors[0];

    const gameMessages = [
        "СУПЕР ПРЫЖОК!",
        "КАЙФАНУЛ!",
        "ВПЕРЕД, ШЕРИФ!",
        "ПОЧТИ У ЦЕЛИ!",
        "КОЛЮЧИЙ ГЕРОЙ!",
        "БОНУС ПОЛУЧЕН!"
    ];

    function updateScore(amount) {
        score += amount;
        scoreText.textContent = score.toString().padStart(6, '0');
    }

    function hitBottle() {
        coins++;
        coinsText.textContent = `x${coins.toString().padStart(2, '0')}`;
        updateScore(500);

        // Visual feedback for flavor
        const popup = document.createElement('div');
        popup.className = 'coin-popup';
        popup.textContent = currentFlavor.name;
        popup.style.left = `${block.offsetLeft + block.offsetWidth / 2}px`;
        popup.style.top = `${block.offsetTop}px`;
        gameWorld.appendChild(popup);

        // Trigger bottle animation
        bottleImg.classList.add('bottle-collect');

        setTimeout(() => {
            popup.remove();
        }, 800);

        setTimeout(() => {
            bottleImg.classList.remove('bottle-collect');
            spawnNewBottle();
        }, 400);
    }

    function spawnNewBottle() {
        const newFlavor = flavors[Math.floor(Math.random() * flavors.length)];
        currentFlavor = newFlavor;
        bottleImg.src = currentFlavor.img;
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
            hitBottle();
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
