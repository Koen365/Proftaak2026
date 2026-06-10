// Tower Defense Game Companion - Minigame Logic

class TowerBuilderGame {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.gridSize = 10;
        this.budget = 500;
        this.wave = 1;
        this.score = 0;
        this.gameOver = false;
        this.towers = [];
        this.enemies = [];
        this.pathPoints = [];
        this.resources = 500;
        this.init();
    }
    
    init() {
        this.createGrid();
        this.setupUI();
        this.generatePath();
    }
    
    createGrid() {
        const grid = document.createElement('div');
        grid.className = 'minigame-grid';
        grid.id = 'game-grid';
        
        for (let y = 0; y < this.gridSize; y++) {
            for (let x = 0; x < this.gridSize; x++) {
                const cell = document.createElement('div');
                cell.className = 'minigame-cell';
                cell.dataset.x = x;
                cell.dataset.y = y;
                cell.addEventListener('click', () => this.handleCellClick(x, y, cell));
                grid.appendChild(cell);
            }
        }
        
        this.container.appendChild(grid);
    }
    
    setupUI() {
        const ui = document.createElement('div');
        ui.className = 'minigame-ui';
        ui.innerHTML = `
            <div class="resource-display">Resources: <span id="resources">${this.resources}</span></div>
            <div class="wave-display">Wave: <span id="wave">${this.wave}</span></div>
            <div class="score-display">Score: <span id="score">${this.score}</span></div>
            <div class="tower-selector">
                <button class="tower-btn" data-tower="basic" data-cost="100">Basic ($100)</button>
                <button class="tower-btn" data-tower="sniper" data-cost="200">Sniper ($200)</button>
                <button class="tower-btn" data-tower="machinegun" data-cost="150">MG ($150)</button>
            </div>
            <button id="start-wave">Start Wave</button>
            <button id="reset-game">Reset</button>
        `;
        this.container.appendChild(ui);
        
        this.setupButtonEvents();
    }
    
    setupButtonEvents() {
        document.querySelectorAll('.tower-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.selectedTower = btn.dataset.tower;
                this.towerCost = parseInt(btn.dataset.cost);
            });
        });
        
        document.getElementById('start-wave').addEventListener('click', () => this.startWave());
        document.getElementById('reset-game').addEventListener('click', () => this.reset());
    }
    
    generatePath() {
        // Simple L-shaped path
        this.pathPoints = [];
        for (let x = 0; x < 5; x++) {
            this.pathPoints.push({x: x, y: 0});
        }
        for (let y = 0; y < 5; y++) {
            this.pathPoints.push({x: 4, y: y});
        }
        for (let x = 4; x < 10; x++) {
            this.pathPoints.push({x: x, y: 4});
        }
        this.drawPath();
    }
    
    drawPath() {
        this.pathPoints.forEach(point => {
            const cell = document.querySelector(`[data-x="${point.x}"][data-y="${point.y}"]`);
            if (cell) cell.classList.add('path');
        });
    }
    
    handleCellClick(x, y, cell) {
        if (this.pathPoints.some(p => p.x === x && p.y === y)) return;
        
        if (this.resources >= this.towerCost && this.selectedTower) {
            this.placeTower(x, y, cell);
        } else if (this.resources < this.towerCost) {
            showToast('Not enough resources!', 'error');
        } else {
            showToast('Select a tower first!', 'info');
        }
    }
    
    placeTower(x, y, cell) {
        cell.classList.add('tower', `tower-${this.selectedTower}`);
        this.resources -= this.towerCost;
        this.towers.push({x, y, type: this.selectedTower});
        this.updateUI();
    }
    
    startWave() {
        if (this.gameOver) return;
        
        // Spawn enemies
        this.spawnEnemies();
        
        // Run wave simulation
        this.runWave();
    }
    
    spawnEnemies() {
        const count = Math.min(5 + this.wave, 20);
        for (let i = 0; i < count; i++) {
            this.enemies.push({
                id: Date.now() + i,
                x: 0,
                y: 0,
                health: 100,
                maxHealth: 100
            });
        }
    }
    
    async runWave() {
        const interval = setInterval(() => {
            this.moveEnemies();
            this.checkCollisions();
            
            if (this.enemies.length === 0) {
                clearInterval(interval);
                this.waveComplete();
            }
        }, 300);
    }
    
    moveEnemies() {
        this.enemies.forEach(enemy => {
            const pathIndex = Math.floor(enemy.x + enemy.y);
            const target = this.pathPoints[pathIndex] || this.pathPoints[this.pathPoints.length - 1];
            
            if (target && (enemy.x !== target.x || enemy.y !== target.y)) {
                if (enemy.x < target.x) enemy.x++;
                if (enemy.x > target.x) enemy.x--;
                if (enemy.y < target.y) enemy.y++;
                if (enemy.y > target.y) enemy.y--;
            }
        });
        
        // Remove enemies that reached the end
        this.enemies = this.enemies.filter(e => {
            if (e.x === this.pathPoints[this.pathPoints.length - 1].x && 
                e.y === this.pathPoints[this.pathPoints.length - 1].y) {
                this.gameOver = true;
                return false;
            }
            return true;
        });
    }
    
    checkCollisions() {
        // Simple collision detection
        this.towers.forEach(tower => {
            const towerCell = document.querySelector(`[data-x="${tower.x}"][data-y="${tower.y}"]`);
            // Tower attacks nearest enemy
            const nearest = this.findNearestEnemy(tower.x, tower.y);
            if (nearest) {
                nearest.health -= 20;
                this.score += 10;
                this.updateUI();
                
                if (nearest.health <= 0) {
                    this.enemies = this.enemies.filter(e => e.id !== nearest.id);
                    this.resources += 20;
                    this.score += 50;
                    this.updateUI();
                }
            }
        });
    }
    
    findNearestEnemy(x, y) {
        let nearest = null;
        let minDist = Infinity;
        
        this.enemies.forEach(enemy => {
            const dist = Math.abs(enemy.x - x) + Math.abs(enemy.y - y);
            if (dist < minDist) {
                minDist = dist;
                nearest = enemy;
            }
        });
        
        return nearest;
    }
    
    waveComplete() {
        this.wave++;
        this.resources += 100;
        this.updateUI();
        showToast(`Wave ${this.wave - 1} complete!`, 'success');
    }
    
    updateUI() {
        document.getElementById('resources').textContent = this.resources;
        document.getElementById('wave').textContent = this.wave;
        document.getElementById('score').textContent = this.score;
    }
    
    reset() {
        this.wave = 1;
        this.score = 0;
        this.resources = 500;
        this.towers = [];
        this.enemies = [];
        this.gameOver = false;
        this.updateUI();
        this.container.querySelector('.minigame-grid').remove();
        this.init();
    }
}

// Endless Survival Trial
class SurvivalTrialGame {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.time = 0;
        this.score = 0;
        this.multiplier = 1;
        this.gameOver = false;
        this.init();
    }
    
    init() {
        this.createUI();
        this.startGameLoop();
    }
    
    createUI() {
        this.container.innerHTML = `
            <div class="survival-ui">
                <div>Time: <span id="survival-time">0</span>s</div>
                <div>Score: <span id="survival-score">0</span></div>
                <div>Combo: <span id="combo-multiplier">x1</span></div>
            </div>
            <div class="survival-grid" id="survival-grid"></div>
            <div class="survival-controls">
                <button id="pause-btn">Pause</button>
            </div>
        `;
        
        this.createSurvivalGrid();
    }
    
    createSurvivalGrid() {
        const grid = document.getElementById('survival-grid');
        grid.innerHTML = '';
        
        for (let i = 0; i < 50; i++) {
            const cell = document.createElement('div');
            cell.className = 'survival-cell';
            cell.dataset.index = i;
            grid.appendChild(cell);
        }
    }
    
    startGameLoop() {
        setInterval(() => {
            if (!this.gameOver) {
                this.time++;
                this.score += this.multiplier;
                this.updateDisplay();
                
                // Increase difficulty
                if (this.time % 30 === 0) {
                    this.multiplier += 0.5;
                    showToast(`Difficulty increased! Multiplier: x${this.multiplier.toFixed(1)}`, 'info');
                }
            }
        }, 1000);
    }
    
    updateDisplay() {
        document.getElementById('survival-time').textContent = this.time;
        document.getElementById('survival-score').textContent = Math.floor(this.score);
        document.getElementById('combo-multiplier').textContent = `x${this.multiplier.toFixed(1)}`;
    }
}

// Initialize games
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('tower-builder-container')) {
        window.towerBuilder = new TowerBuilderGame('tower-builder-container');
    }
    
    if (document.getElementById('survival-trial-container')) {
        window.survivalTrial = new SurvivalTrialGame('survival-trial-container');
    }
});