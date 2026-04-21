document.addEventListener('DOMContentLoaded', () => {
    const gameWorld = document.getElementById('game-world');
    const player = document.getElementById('player');
    const background = document.getElementById('background');
    const ground = document.getElementById('ground');
    const scoreText = document.getElementById('score');
    const bottlesText = document.getElementById('bottles');
    const highscoreText = document.getElementById('highscore');
    const gameOverOverlay = document.getElementById('game-over');
    const finalScoreText = document.getElementById('final-score');
    const restartBtn = document.getElementById('restart-btn');
    const jumpBtn = document.getElementById('jump-btn');

    let score = 0;
    let bottlesCount = 0;
    let highscore = localStorage.getItem('dinoHighscore') || 0;
    let isJumping = false;
    let isGameOver = false;
    let gameSpeed = 6;
    let jumpVelocity = 0;
    const gravity = 0.8;
    const groundLevel = 30;

    let gameObjects = [];
    let lastTime = 0;
    let spawnTimer = 0;
    let spawnInterval = 1500;
    let distance = 0;

    highscoreText.textContent = highscore.toString().padStart(6, '0');

    const flavors = [
        { name: "ЛИМОН", img: "assets/bottle_lemon.png", type: 'good' },
        { name: "ГРУША", img: "assets/bottle_pear.png", type: 'good' },
        { name: "ВИШНЯ", img: "assets/bottle_cherry.png", type: 'good' },
        { name: "ПУСТАЯ", img: "assets/bottle_lemon.png", type: 'bad' }
    ];

    function jump() {
        if (isJumping || isGameOver) return;
        isJumping = true;
        jumpVelocity = 18;
        player.classList.remove('running');
    }

    function spawnObject() {
        const rand = Math.random();
        let obj;
        if (rand < 0.3) {
            // Obstacle (cactus-like box)
            obj = document.createElement('div');
            obj.className = 'game-object obstacle';
            obj.type = 'obstacle';
        } else {
            // Bottle
            const flavor = flavors[Math.floor(Math.random() * flavors.length)];
            obj = document.createElement('div');
            obj.className = 'game-object bottle' + (flavor.type === 'bad' ? ' empty-bottle' : '');
            const img = document.createElement('img');
            img.src = flavor.img;
            obj.appendChild(img);
            obj.type = flavor.type;
            obj.flavorName = flavor.name;
        }

        const startX = gameWorld.offsetWidth;
        obj.style.left = `${startX}px`;
        gameWorld.appendChild(obj);
        gameObjects.push({
            el: obj,
            x: startX,
            type: obj.type,
            flavorName: obj.flavorName
        });
    }

    function gameLoop(time) {
        if (isGameOver) return;

        if (!lastTime) lastTime = time;
        const deltaTime = time - lastTime;
        lastTime = time;

        // Increase distance and speed
        distance += gameSpeed * (deltaTime / 16);
        gameSpeed = 6 + (distance / 5000);

        // Background & Ground Scrolling
        const bgX = (distance * 0.2) % 300;
        background.style.backgroundPosition = `-${bgX}px 0`;

        const grX = (distance) % 40;
        ground.style.transform = `translateX(-${grX}px)`;

        // Player Physics
        let bottom = parseFloat(player.style.bottom || groundLevel);
        if (isJumping) {
            bottom += jumpVelocity;
            jumpVelocity -= gravity;

            if (bottom <= groundLevel) {
                bottom = groundLevel;
                isJumping = false;
                player.classList.add('running');
            }
        }
        player.style.bottom = `${bottom}px`;

        // Spawning
        spawnTimer += deltaTime;
        if (spawnTimer > spawnInterval) {
            spawnObject();
            spawnTimer = 0;
            spawnInterval = Math.max(700, 1500 - (distance / 100));
        }

        // Object Movement & Collision
        for (let i = gameObjects.length - 1; i >= 0; i--) {
            const obj = gameObjects[i];
            obj.x -= gameSpeed * (deltaTime / 16);
            obj.el.style.left = `${obj.x}px`;

            // Collision check (tightened boxes)
            const pRect = player.getBoundingClientRect();
            const oRect = obj.el.getBoundingClientRect();

            // Padding for collision to feel more fair
            const padding = 10;
            if (
                pRect.left + padding < oRect.right - padding &&
                pRect.right - padding > oRect.left + padding &&
                pRect.top + padding < oRect.bottom - padding &&
                pRect.bottom - padding > oRect.top + padding
            ) {
                if (obj.type === 'good') {
                    collectBottle(obj);
                    gameObjects.splice(i, 1);
                } else {
                    endGame();
                }
            } else if (obj.x < -100) {
                obj.el.remove();
                gameObjects.splice(i, 1);
                score += 10;
                updateUI();
            }
        }

        requestAnimationFrame(gameLoop);
    }

    function collectBottle(obj) {
        bottlesCount++;
        score += 500;
        obj.el.remove();

        const popup = document.createElement('div');
        popup.className = 'flavor-popup';
        popup.textContent = obj.flavorName;
        popup.style.left = `${player.offsetLeft + 20}px`;
        popup.style.bottom = `${parseFloat(player.style.bottom) + 100}px`;
        gameWorld.appendChild(popup);
        setTimeout(() => popup.remove(), 600);

        updateUI();
    }

    function updateUI() {
        scoreText.textContent = Math.floor(score).toString().padStart(6, '0');
        bottlesText.textContent = `x${bottlesCount.toString().padStart(2, '0')}`;
    }

    function endGame() {
        isGameOver = true;
        gameOverOverlay.style.display = 'flex';
        finalScoreText.textContent = `СЧЕТ: ${Math.floor(score)}`;
        player.classList.remove('running');
        if (score > highscore) {
            highscore = Math.floor(score);
            localStorage.setItem('dinoHighscore', highscore);
            highscoreText.textContent = highscore.toString().padStart(6, '0');
        }
    }

    function resetGame() {
        isGameOver = false;
        score = 0;
        bottlesCount = 0;
        gameSpeed = 6;
        distance = 0;
        spawnTimer = 0;
        lastTime = 0;
        gameObjects.forEach(obj => obj.el.remove());
        gameObjects = [];
        gameOverOverlay.style.display = 'none';
        player.classList.add('running');
        updateUI();
        requestAnimationFrame(gameLoop);
    }

    restartBtn.addEventListener('click', resetGame);
    jumpBtn.addEventListener('click', jump);
    // Global touch/click jump
    gameWorld.addEventListener('touchstart', (e) => {
        if (e.target !== restartBtn) {
            e.preventDefault();
            jump();
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.code === 'Space' || e.code === 'ArrowUp') {
            e.preventDefault();
            jump();
        }
    });

    // Start
    requestAnimationFrame(gameLoop);
});
