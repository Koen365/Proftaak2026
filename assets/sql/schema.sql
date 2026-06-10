-- Database schema for Tower Defense Game

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Scores table
CREATE TABLE scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    map_id INT NOT NULL,
    score INT NOT NULL,
    waves_survived INT NOT NULL,
    time_survived INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (map_id) REFERENCES maps(id)
);

-- Unlockables table
CREATE TABLE unlockables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('tower', 'enemy', 'map', 'minigame') NOT NULL,
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') NOT NULL,
    description TEXT,
    unlocked_by_default BOOLEAN DEFAULT FALSE,
    image_url VARCHAR(255)
);

-- User unlockables (to track which unlockables a user has)
CREATE TABLE user_unlockables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    unlockable_id INT NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (unlockable_id) REFERENCES unlockables(id),
    UNIQUE KEY unique_user_unlockable (user_id, unlockable_id)
);

-- Maps table
CREATE TABLE maps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    grid_width INT NOT NULL,
    grid_height INT NOT NULL,
    background_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Towers table
CREATE TABLE towers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    damage INT NOT NULL,
    attack_speed DECIMAL(3,2) NOT NULL,
    range INT NOT NULL,
    cost INT NOT NULL,
    image_url VARCHAR(255),
    unlocked_by_default BOOLEAN DEFAULT FALSE
);

-- Enemies table
CREATE TABLE enemies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    health INT NOT NULL,
    speed DECIMAL(4,2) NOT NULL,
    reward INT NOT NULL,
    image_url VARCHAR(255),
    unlocked_by_default BOOLEAN DEFAULT FALSE
);

-- Minigames table
CREATE TABLE minigames (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('tower_builder', 'survival_trial') NOT NULL,
    unlocked_by_default BOOLEAN DEFAULT FALSE
);

-- User minigame progress
CREATE TABLE user_minigame_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    minigame_id INT NOT NULL,
    best_score INT DEFAULT 0,
    last_played TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (minigame_id) REFERENCES minigames(id),
    UNIQUE KEY unique_user_minigame (user_id, minigame_id)
);

-- News table
CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- Insert some initial data for unlockables, maps, towers, enemies, minigames
-- Unlockables
INSERT INTO unlockables (name, type, rarity, description, unlocked_by_default, image_url) VALUES
('Basic Tower', 'tower', 'common', 'A simple tower with balanced stats.', TRUE, 'assets/images/towers/basic.png'),
('Sniper Tower', 'tower', 'rare', 'High damage, slow attack speed.', FALSE, 'assets/images/towers/sniper.png'),
('Machine Gun Tower', 'tower', 'epic', 'Low damage, very high attack speed.', FALSE, 'assets/images/towers/machinegun.png'),
('Basic Enemy', 'enemy', 'common', 'A simple enemy with balanced stats.', TRUE, 'assets/images/enemies/basic.png'),
('Fast Enemy', 'enemy', 'uncommon', 'Low health, high speed.', FALSE, 'assets/images/enemies/fast.png'),
('Tank Enemy', 'enemy', 'rare', 'High health, low speed.', FALSE, 'assets/images/enemies/tank.png'),
('Grassland Map', 'map', 'common', 'A simple grassy map for beginners.', TRUE, 'assets/images/maps/grassland.png'),
('Desert Map', 'map', 'uncommon', 'A sandy map with tricky paths.', FALSE, 'assets/images/maps/desert.png'),
('Tower Builder Challenge', 'minigame', 'common', 'Build the best tower defense with limited resources.', TRUE, 'assets/images/minigames/tower_builder.png'),
('Endless Survival Trial', 'minigame', 'rare', 'Survive as long as possible against endless waves.', FALSE, 'assets/images/minigames/survival_trial.png');

-- Maps
INSERT INTO maps (name, description, grid_width, grid_height, background_image) VALUES
('Grassland', 'A simple grassy map for beginners.', 20, 15, 'assets/images/maps/grassland.png'),
('Desert', 'A sandy map with tricky paths.', 25, 20, 'assets/images/maps/desert.png');

-- Towers
INSERT INTO towers (name, description, damage, attack_speed, range, cost, image_url, unlocked_by_default) VALUES
('Basic Tower', 'A simple tower with balanced stats.', 10, 1.0, 100, 100, 'assets/images/towers/basic.png', TRUE),
('Sniper Tower', 'High damage, slow attack speed.', 50, 0.5, 200, 500, 'assets/images/towers/sniper.png', FALSE),
('Machine Gun Tower', 'Low damage, very high attack speed.', 5, 2.5, 80, 300, 'assets/images/towers/machinegun.png', FALSE);

-- Enemies
INSERT INTO enemies (name, description, health, speed, reward, image_url, unlocked_by_default) VALUES
('Basic Enemy', 'A simple enemy with balanced stats.', 100, 1.0, 10, 'assets/images/enemies/basic.png', TRUE),
('Fast Enemy', 'Low health, high speed.', 50, 2.0, 15, 'assets/images/enemies/fast.png', FALSE),
('Tank Enemy', 'High health, low speed.', 300, 0.5, 25, 'assets/images/enemies/tank.png', FALSE);

-- Minigames
INSERT INTO minigames (name, description, type, unlocked_by_default) VALUES
('Tower Builder Challenge', 'Build the best tower defense with limited resources.', 'tower_builder', TRUE),
('Endless Survival Trial', 'Survive as long as possible against endless waves.', 'survival_trial', FALSE);