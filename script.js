document.addEventListener('DOMContentLoaded', () => {
    const gameWorld = document.getElementById('game-world');
    const player = document.getElementById('player');
    const background = document.getElementById('background');
    const ground = document.getElementById('ground');
    const scoreText = document.getElementById('score');
    const bottlesText = document.getElementById('bottles');
    const highscoreText = document.getElementById('highscore');
    const startScreen = document.getElementById('start-screen');
    const startBtn = document.getElementById('start-btn');
    const gameOverOverlay = document.getElementById('game-over');
    const finalScoreText = document.getElementById('final-score');
    const restartBtn = document.getElementById('restart-btn');
    const jumpBtn = document.getElementById('jump-btn');

    let score = 0;
    let bottlesCount = 0;
    let highscore = localStorage.getItem('dinoHighscore') || 0;

    const groundLevel = 30;
    const gravity = 0.8;
    const jumpInitialVelocity = 12;
    const jumpHoldBoost = 0.4;
    const maxJumpHoldFrames = 15;

    let isGameStarted = false;
    let isGameOver = false;
    let gameSpeed = 6;
    let jumpVelocity = 0;
    let jumpHoldCounter = 0;
    let isJumpButtonPressed = false;

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

    function startJump() {
        if (!isGameStarted || isGameOver) return;
        if (!isJumping) {
            isJumping = true;
            jumpVelocity = jumpInitialVelocity;
            jumpHoldCounter = 0;
            player.classList.remove('running');
            player.classList.add('jumping');
        }
        isJumpButtonPressed = true;
    }

    let isJumping = false; // Moved here for scope but it was already used

    function endJump() {
        isJumpButtonPressed = false;
    }

    function spawnObject() {
        const rand = Math.random();
        let obj;
        if (rand < 0.3) {
            obj = document.createElement('div');
            obj.className = 'game-object obstacle';
            obj.type = 'obstacle';
        } else {
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
        if (!isGameStarted || isGameOver) {
            lastTime = 0; // Reset lastTime so deltaTime doesn't jump
            return;
        }

        if (!lastTime) lastTime = time;
        const deltaTime = time - lastTime;
        lastTime = time;

        const timeStep = deltaTime / 16;

        distance += gameSpeed * timeStep;
        gameSpeed = 6 + (distance / 5000);

        const bgX = (distance * 0.2) % 800;
        background.style.backgroundPosition = `-${bgX}px 0`;

        const grX = (distance) % 30;
        ground.style.transform = `translateX(-${grX}px)`;

        let bottom = parseFloat(player.style.bottom || groundLevel);
        if (isJumping) {
            if (isJumpButtonPressed && jumpHoldCounter < maxJumpHoldFrames) {
                jumpVelocity += jumpHoldBoost;
                jumpHoldCounter++;
            }

            bottom += jumpVelocity * timeStep;
            jumpVelocity -= gravity * timeStep;

            if (bottom <= groundLevel) {
                bottom = groundLevel;
                isJumping = false;
                player.classList.remove('jumping');
                player.classList.add('running');
            }
        }
        player.style.bottom = `${bottom}px`;

        spawnTimer += deltaTime;
        if (spawnTimer > spawnInterval) {
            spawnObject();
            spawnTimer = 0;
            spawnInterval = Math.max(700, 1500 - (distance / 100));
        }

        for (let i = gameObjects.length - 1; i >= 0; i--) {
            const obj = gameObjects[i];
            obj.x -= gameSpeed * timeStep;
            obj.el.style.left = `${obj.x}px`;

            const pRect = player.getBoundingClientRect();
            const oRect = obj.el.getBoundingClientRect();

            const padding = 15;
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
            } else if (obj.x < -150) {
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
        popup.style.left = `${player.offsetLeft}px`;
        popup.style.bottom = `${parseFloat(player.style.bottom) + 120}px`;
        gameWorld.appendChild(popup);
        setTimeout(() => popup.remove(), 700);

        updateUI();
    }

    function updateUI() {
        scoreText.textContent = Math.floor(score).toString().padStart(6, '0');
        bottlesText.textContent = `x${bottlesCount.toString().padStart(2, '0')}`;
    }

    function endGame() {
        isGameOver = true;
        gameOverOverlay.style.display = 'flex';
        jumpBtn.style.display = 'none';
        finalScoreText.textContent = `СЧЕТ: ${Math.floor(score)}`;
        player.classList.remove('running');
        player.classList.remove('jumping');
        if (score > highscore) {
            highscore = Math.floor(score);
            localStorage.setItem('dinoHighscore', highscore);
            highscoreText.textContent = highscore.toString().padStart(6, '0');
        }
    }

    function resetGame() {
        isGameOver = false;
        isGameStarted = true;
        score = 0;
        bottlesCount = 0;
        gameSpeed = 6;
        distance = 0;
        spawnTimer = 0;
        lastTime = 0;
        isJumping = false;
        jumpVelocity = 0;
        gameObjects.forEach(obj => obj.el.remove());
        gameObjects = [];
        gameOverOverlay.style.display = 'none';
        jumpBtn.style.display = 'block';
        player.classList.add('running');
        player.style.bottom = `${groundLevel}px`;
        updateUI();
        requestAnimationFrame(gameLoop);
    }

    function startGame() {
        isGameStarted = true;
        startScreen.style.display = 'none';
        jumpBtn.style.display = 'block';
        player.classList.add('running');
        requestAnimationFrame(gameLoop);
    }

    startBtn.addEventListener('click', startGame);
    restartBtn.addEventListener('click', resetGame);

    jumpBtn.addEventListener('mousedown', startJump);
    jumpBtn.addEventListener('mouseup', endJump);
    jumpBtn.addEventListener('mouseleave', endJump);
    jumpBtn.addEventListener('touchstart', (e) => { e.preventDefault(); startJump(); });
    jumpBtn.addEventListener('touchend', (e) => { e.preventDefault(); endJump(); });

    document.addEventListener('keydown', (e) => {
        if (e.code === 'Space' || e.code === 'ArrowUp') {
            e.preventDefault();
            startJump();
        }
    });
    document.addEventListener('keyup', (e) => {
        if (e.code === 'Space' || e.code === 'ArrowUp') {
            endJump();
        }
    });

    player.classList.remove('running');
    updateUI();
});
