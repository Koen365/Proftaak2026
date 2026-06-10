-- Unlock system migration
-- Run: Get-Content ... | mysql -u root royaledefense

-- 1. Add unlock condition columns to unlockables
ALTER TABLE unlockables
  ADD COLUMN unlock_type   ENUM('default','score','waves','lootbox','both') NOT NULL DEFAULT 'lootbox' AFTER unlocked_by_default,
  ADD COLUMN unlock_score  INT          NULL COMMENT 'Min total score needed (from tower defense play)',
  ADD COLUMN unlock_waves  INT          NULL COMMENT 'Min waves survived in one game',
  ADD COLUMN loot_weight   INT          NOT NULL DEFAULT 0 COMMENT '0=not in loot pool. Higher=more common. 1=legendary rarity',
  ADD COLUMN loot_pool     TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1=available in loot box spins';

-- 2. Update existing unlockables with conditions
-- Default unlocked items
UPDATE unlockables SET unlock_type='default', loot_pool=0, loot_weight=0 WHERE unlocked_by_default=1;

-- Score-based unlocks (playing tower defense)
UPDATE unlockables SET unlock_type='score',  unlock_score=1000,  loot_pool=0 WHERE name='Sniper Tower';
UPDATE unlockables SET unlock_type='waves',  unlock_waves=10,    loot_pool=0 WHERE name='Machine Gun Tower';
UPDATE unlockables SET unlock_type='score',  unlock_score=500,   loot_pool=0 WHERE name='Desert Map';
UPDATE unlockables SET unlock_type='score',  unlock_score=2000,  loot_pool=0 WHERE name='Endless Survival Trial';
UPDATE unlockables SET unlock_type='waves',  unlock_waves=5,     loot_pool=0 WHERE name='Fast Enemy';
UPDATE unlockables SET unlock_type='waves',  unlock_waves=15,    loot_pool=0 WHERE name='Tank Enemy';

-- Loot box only items (badges, avatars, cosmetics, titles)
UPDATE unlockables SET unlock_type='lootbox', loot_pool=1, loot_weight=500  WHERE name='Commander Badge';
UPDATE unlockables SET unlock_type='lootbox', loot_pool=1, loot_weight=1    WHERE name='Legend Badge';
UPDATE unlockables SET unlock_type='lootbox', loot_pool=1, loot_weight=400  WHERE name='Default Avatar';
UPDATE unlockables SET unlock_type='lootbox', loot_pool=1, loot_weight=5    WHERE name='Gold Frame';
UPDATE unlockables SET unlock_type='lootbox', loot_pool=1, loot_weight=300  WHERE name='Rookie Title';
UPDATE unlockables SET unlock_type='lootbox', loot_pool=1, loot_weight=1    WHERE name='Tower Master Title';

-- 3. Add more loot box items (legendary, epic, rare rewards)
INSERT IGNORE INTO unlockables (name, type, rarity, description, unlocked_by_default, unlock_type, loot_pool, loot_weight) VALUES
-- Towers (loot box only — super rare)
('Frost Tower',       'tower',    'legendary', 'Slows all enemies in range. 1-in-1000 drop.',  0, 'lootbox', 1, 1),
('Tesla Tower',       'tower',    'epic',      'Chains lightning between enemies.',             0, 'lootbox', 1, 8),
('Cannon Tower',      'tower',    'rare',      'Area damage — hurts groups.',                   0, 'lootbox', 1, 40),
('Poison Tower',      'tower',    'uncommon',  'Damage over time on hit.',                      0, 'lootbox', 1, 120),
-- Cosmetics
('Neon Skin',         'cosmetic', 'epic',      'Glowing neon tower skins.',                     0, 'lootbox', 1, 8),
('Pixel Skin',        'cosmetic', 'rare',      'Retro pixel art style.',                        0, 'lootbox', 1, 35),
('Dark Theme',        'cosmetic', 'uncommon',  'Dark red UI theme.',                            0, 'lootbox', 1, 100),
('Gold Theme',        'cosmetic', 'legendary', 'Full gold UI theme.',                           0, 'lootbox', 1, 1),
-- Badges
('Survivor Badge',    'badge',    'rare',      'Survived 20+ waves in one run.',                0, 'lootbox', 1, 30),
('Lucky Badge',       'badge',    'epic',      'Won a 1/100 loot box roll.',                    0, 'lootbox', 1, 6),
('Mythic Badge',      'badge',    'legendary', 'Won a 1/1000 loot box roll.',                   0, 'lootbox', 1, 1),
-- Avatars
('Knight Avatar',     'avatar',   'rare',      'A knight in tower defense armor.',              0, 'lootbox', 1, 30),
('Dragon Avatar',     'avatar',   'epic',      'A dragon guarding your towers.',                0, 'lootbox', 1, 6),
('Phoenix Avatar',    'avatar',   'legendary', 'The mythical phoenix.',                         0, 'lootbox', 1, 1),
-- Titles
('Veteran Title',     'title',    'rare',      'Awarded to dedicated players.',                 0, 'lootbox', 1, 25),
('Mythic Title',      'title',    'legendary', 'For the truly lucky ones.',                     0, 'lootbox', 1, 1);

-- 4. Loot spin history
CREATE TABLE IF NOT EXISTS loot_spins (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  unlockable_id INT NOT NULL,
  spin_cost   INT NOT NULL DEFAULT 100 COMMENT 'Coins spent',
  spun_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)       REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (unlockable_id) REFERENCES unlockables(id) ON DELETE CASCADE
);

-- 5. User coin balance (earned by playing)
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS coins INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS total_score INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS total_waves  INT NOT NULL DEFAULT 0;
