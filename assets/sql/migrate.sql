-- Migration: bring royaledefense up to date for TowerDefenseHQ
-- Run once: mysql -u root royaledefense < migrate.sql

-- 1. Add role to users
ALTER TABLE users ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user';

-- 2. Patch scores table (add missing columns)
ALTER TABLE scores
  ADD COLUMN map_id        INT     NOT NULL DEFAULT 1,
  ADD COLUMN waves_survived INT    NOT NULL DEFAULT 0,
  ADD COLUMN time_survived  INT    NOT NULL DEFAULT 0,
  ADD COLUMN created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 3. Maps
CREATE TABLE IF NOT EXISTS maps (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(100) NOT NULL,
  description      TEXT,
  grid_width       INT NOT NULL DEFAULT 20,
  grid_height      INT NOT NULL DEFAULT 15,
  background_image VARCHAR(255),
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Towers
CREATE TABLE IF NOT EXISTS towers (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  name                VARCHAR(100) NOT NULL,
  description         TEXT,
  damage              INT          NOT NULL DEFAULT 10,
  attack_speed        DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  `range`             INT          NOT NULL DEFAULT 3,
  cost                INT          NOT NULL DEFAULT 100,
  image_url           VARCHAR(255),
  unlocked_by_default TINYINT(1)   DEFAULT 0
);

-- 5. Enemies
CREATE TABLE IF NOT EXISTS enemies (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  name                VARCHAR(100) NOT NULL,
  description         TEXT,
  health              INT          NOT NULL DEFAULT 100,
  speed               DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  reward              INT          NOT NULL DEFAULT 10,
  image_url           VARCHAR(255),
  unlocked_by_default TINYINT(1)   DEFAULT 0
);

-- 6. Unlockables
CREATE TABLE IF NOT EXISTS unlockables (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  name                VARCHAR(100) NOT NULL,
  type                ENUM('tower','enemy','map','minigame','cosmetic','badge','avatar','title') NOT NULL,
  rarity              ENUM('common','uncommon','rare','epic','legendary') NOT NULL DEFAULT 'common',
  description         TEXT,
  unlocked_by_default TINYINT(1)   DEFAULT 0,
  image_url           VARCHAR(255)
);

-- 7. User unlockables
CREATE TABLE IF NOT EXISTS user_unlockables (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT NOT NULL,
  unlockable_id  INT NOT NULL,
  unlocked_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
  FOREIGN KEY (unlockable_id) REFERENCES unlockables(id) ON DELETE CASCADE,
  UNIQUE KEY uq_user_unlock (user_id, unlockable_id)
);

-- 8. News
CREATE TABLE IF NOT EXISTS news (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(255) NOT NULL,
  content    TEXT         NOT NULL,
  author_id  INT          NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 9. Minigames
CREATE TABLE IF NOT EXISTS minigames (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  name                VARCHAR(100) NOT NULL,
  description         TEXT,
  type                ENUM('tower_builder','survival_trial') NOT NULL,
  unlocked_by_default TINYINT(1) DEFAULT 0
);

-- 10. User minigame progress
CREATE TABLE IF NOT EXISTS user_minigame_progress (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  minigame_id INT NOT NULL,
  best_score  INT DEFAULT 0,
  last_played TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
  FOREIGN KEY (minigame_id) REFERENCES minigames(id) ON DELETE CASCADE,
  UNIQUE KEY uq_user_mg (user_id, minigame_id)
);

-- 11. Seed: Maps
INSERT IGNORE INTO maps (id, name, description, grid_width, grid_height) VALUES
  (1, 'Grassland', 'A beginner-friendly grassy map.', 16, 12),
  (2, 'Desert',    'Sandy paths with tricky bends.',  16, 12);

-- 12. Seed: Towers
INSERT IGNORE INTO towers (id, name, description, damage, attack_speed, `range`, cost, unlocked_by_default) VALUES
  (1, 'Basic Tower',       'Balanced stats — great all-rounder.',         10,  1.00, 3, 100, 1),
  (2, 'Sniper Tower',      'High damage, slow fire rate, long range.',     50,  0.50, 6, 300, 0),
  (3, 'Machine Gun Tower', 'Rapid fire, low damage, short range.',          5,  5.00, 2, 200, 0);

-- 13. Seed: Enemies
INSERT IGNORE INTO enemies (id, name, description, health, speed, reward, unlocked_by_default) VALUES
  (1, 'Basic Enemy', 'Standard balanced enemy.',        100, 1.00, 10, 1),
  (2, 'Fast Enemy',  'Low health but moves very fast.',  40, 3.00, 15, 0),
  (3, 'Tank Enemy',  'Extremely high HP, slow speed.',  350, 0.50, 50, 0);

-- 14. Seed: Unlockables
INSERT IGNORE INTO unlockables (id, name, type, rarity, description, unlocked_by_default) VALUES
  (1,  'Basic Tower',              'tower',    'common',    'The starter tower.',                     1),
  (2,  'Sniper Tower',             'tower',    'rare',      'High-damage specialist.',                0),
  (3,  'Machine Gun Tower',        'tower',    'epic',      'Rapid-fire tower.',                      0),
  (4,  'Basic Enemy',              'enemy',    'common',    'The standard foe.',                      1),
  (5,  'Fast Enemy',               'enemy',    'uncommon',  'Speedy but fragile.',                    0),
  (6,  'Tank Enemy',               'enemy',    'rare',      'Tough and slow.',                        0),
  (7,  'Grassland Map',            'map',      'common',    'Starter map.',                           1),
  (8,  'Desert Map',               'map',      'uncommon',  'Tricky sandy paths.',                    0),
  (9,  'Tower Builder Challenge',  'minigame', 'common',    'Budget tower placement challenge.',       1),
  (10, 'Endless Survival Trial',   'minigame', 'rare',      'Survive as long as possible.',           0),
  (11, 'Commander Badge',          'badge',    'common',    'Awarded for completing registration.',    1),
  (12, 'Legend Badge',             'badge',    'legendary', 'Top 10 on any leaderboard.',             0),
  (13, 'Default Avatar',           'avatar',   'common',    'Your starter avatar.',                   1),
  (14, 'Gold Frame',               'avatar',   'epic',      'Exclusive gold profile border.',         0),
  (15, 'Rookie Title',             'title',    'common',    'Your first title.',                      1),
  (16, 'Tower Master Title',       'title',    'legendary', 'Survive 30+ waves.',                     0);

-- 15. Seed: Minigames
INSERT IGNORE INTO minigames (id, name, description, type, unlocked_by_default) VALUES
  (1, 'Tower Builder Challenge', 'Place towers strategically to survive waves.', 'tower_builder',  1),
  (2, 'Endless Survival Trial',  'Survive as many waves as possible.',           'survival_trial', 0);
