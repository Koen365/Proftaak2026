// Tower Defense Game Companion - Map Preview & Drag-Drop

let draggedTower = null;
let currentMap = null;

document.addEventListener('DOMContentLoaded', function() {
    initMapPreview();
    initDragAndDrop();
});

function initMapPreview() {
    const mapGrid = document.getElementById('map-grid');
    if (!mapGrid) return;
    
    // Get map data from data attributes
    const width = parseInt(mapGrid.dataset.width) || 20;
    const height = parseInt(mapGrid.dataset.height) || 15;
    
    // Create grid cells
    mapGrid.style.gridTemplateColumns = `repeat(${width}, 1fr)`;
    mapGrid.style.gridTemplateRows = `repeat(${height}, 1fr)`;
    
    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            const cell = document.createElement('div');
            cell.className = 'grid-cell';
            cell.dataset.x = x;
            cell.dataset.y = y;
            cell.addEventListener('dragover', handleDragOver);
            cell.addEventListener('drop', handleDrop);
            cell.addEventListener('click', handleCellClick);
            mapGrid.appendChild(cell);
        }
    }
}

function initDragAndDrop() {
    const towerItems = document.querySelectorAll('.tower-item');
    
    towerItems.forEach(item => {
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
    });
}

function handleDragStart(e) {
    draggedTower = this;
    this.style.opacity = '0.5';
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', this.dataset.towerId);
}

function handleDragEnd(e) {
    this.style.opacity = '1';
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    
    // Visual feedback
    const cell = e.currentTarget;
    const towerType = draggedTower?.dataset.towerType;
    
    if (isPlacementValid(cell, towerType)) {
        cell.classList.add('placement-valid');
        cell.classList.remove('placement-invalid');
    } else {
        cell.classList.add('placement-invalid');
        cell.classList.remove('placement-valid');
    }
}

function handleDrop(e) {
    e.preventDefault();
    
    const cell = e.currentTarget;
    cell.classList.remove('placement-valid', 'placement-invalid');
    
    const towerType = draggedTower?.dataset.towerType;
    
    if (isPlacementValid(cell, towerType)) {
        placeTower(cell, towerType);
        showToast('Tower placed successfully!', 'success');
    } else {
        showToast('Invalid placement!', 'error');
    }
}

function handleCellClick(e) {
    const cell = e.currentTarget;
    if (cell.classList.contains('tower')) {
        removeTower(cell);
    }
}

function isPlacementValid(cell, towerType) {
    // Cannot place on path
    if (cell.classList.contains('path')) {
        return false;
    }
    
    // Cannot place on existing tower
    if (cell.classList.contains('tower')) {
        return false;
    }
    
    return true;
}

function placeTower(cell, towerType) {
    cell.classList.add('tower');
    cell.dataset.towerType = towerType;
    
    // Add tower indicator
    const indicator = document.createElement('div');
    indicator.className = 'tower-indicator';
    indicator.textContent = getTowerSymbol(towerType);
    cell.appendChild(indicator);
}

function removeTower(cell) {
    cell.classList.remove('tower');
    delete cell.dataset.towerType;
    cell.innerHTML = '';
}

function getTowerSymbol(towerType) {
    const symbols = {
        'basic': '🔫',
        'sniper': '🎯',
        'machinegun': '⚡',
        'cannon': '💥'
    };
    return symbols[towerType] || '🗼';
}

// Draw path on map
function drawPath(pathData) {
    const cells = document.querySelectorAll('.grid-cell');
    cells.forEach(cell => {
        const x = parseInt(cell.dataset.x);
        const y = parseInt(cell.dataset.y);
        
        if (pathData.some(p => p.x === x && p.y === y)) {
            cell.classList.add('path');
        }
    });
}

// Clear all towers
function clearTowers() {
    document.querySelectorAll('.grid-cell.tower').forEach(cell => {
        cell.classList.remove('tower');
        delete cell.dataset.towerType;
        cell.innerHTML = '';
    });
}

// Get current tower placements for save/export
function getTowerPlacements() {
    const placements = [];
    document.querySelectorAll('.grid-cell.tower').forEach(cell => {
        placements.push({
            x: parseInt(cell.dataset.x),
            y: parseInt(cell.dataset.y),
            type: cell.dataset.towerType
        });
    });
    return placements;
}