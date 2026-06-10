package com.towerdefense.game;

import java.util.*;

/**
 * Tower Defense Game Engine - Java backend logic
 * Handles game calculations, enemy AI, tower logic, and scoring
 */
public class GameEngine {
    private static final int BASE_ENEMY_HEALTH = 100;
    private static final int BASE_ENEMY_SPEED = 1;
    private static final int TOWER_BASE_DAMAGE = 25;
    
    private int waveNumber;
    private double difficultyMultiplier;
    private Random random;
    
    public GameEngine() {
        this.waveNumber = 1;
        this.difficultyMultiplier = 1.0;
        this.random = new Random();
    }
    
    /**
     * Calculate enemy stats based on wave number
     */
    public EnemyStats calculateEnemyStats(int wave, String enemyType) {
        double waveMultiplier = 1.0 + (wave * 0.15);
        double healthMultiplier = 1.0 + (wave * 0.1);
        
        switch (enemyType.toLowerCase()) {
            case "basic":
                return new EnemyStats(
                    (int)(BASE_ENEMY_HEALTH * healthMultiplier * waveMultiplier),
                    BASE_ENEMY_SPEED,
                    (int)(10 * waveMultiplier)
                );
            case "fast":
                return new EnemyStats(
                    (int)(50 * healthMultiplier * waveMultiplier),
                    (int)(BASE_ENEMY_SPEED * 2),
                    (int)(15 * waveMultiplier)
                );
            case "tank":
                return new EnemyStats(
                    (int)(300 * healthMultiplier * waveMultiplier),
                    (int)(BASE_ENEMY_SPEED * 0.5),
                    (int)(30 * waveMultiplier)
                );
            default:
                return new EnemyStats(
                    (int)(BASE_ENEMY_HEALTH * healthMultiplier),
                    BASE_ENEMY_SPEED,
                    (int)(10 * waveMultiplier)
                );
        }
    }
    
    /**
     * Calculate tower damage with upgrades
     */
    public int calculateTowerDamage(String towerType, int upgradeLevel) {
        int baseDamage = TOWER_BASE_DAMAGE;
        double damageMultiplier = 1.0 + (upgradeLevel * 0.15);
        
        switch (towerType.toLowerCase()) {
            case "basic":
                return (int)(baseDamage * damageMultiplier);
            case "sniper":
                return (int)(baseDamage * 2.5 * damageMultiplier);
            case "machinegun":
                return (int)(baseDamage * 0.8 * damageMultiplier);
            default:
                return (int)(baseDamage * damageMultiplier);
        }
    }
    
    /**
     * Calculate score from wave completion
     */
    public int calculateWaveScore(int wave, int enemiesKilled, int timeBonus) {
        int baseScore = wave * 100;
        int enemyScore = enemiesKilled * 10;
        int timeScore = (int)(timeBonus * 0.5);
        int comboBonus = wave > 1 ? (wave - 1) * 50 : 0;
        
        return (baseScore + enemyScore + timeScore + comboBonus) * (int)difficultyMultiplier;
    }
    
    /**
     * Calculate resource reward from enemy kill
     */
    public int calculateResourceReward(String enemyType, int wave) {
        int baseReward = 10;
        switch (enemyType.toLowerCase()) {
            case "fast": return (int)(baseReward * 1.2 * wave * 0.5);
            case "tank": return (int)(baseReward * 2 * wave * 0.5);
            default: return (int)(baseReward * wave * 0.5);
        }
    }
    
    /**
     * Generate enemy composition for a wave
     */
    public List<String> generateWaveComposition(int wave) {
        List<String> enemies = new ArrayList<>();
        int totalEnemies = 5 + wave * 2;
        
        for (int i = 0; i < totalEnemies; i++) {
            double roll = random.nextDouble();
            if (wave >= 5 && roll > 0.8) {
                enemies.add("tank");
            } else if (wave >= 3 && roll > 0.6) {
                enemies.add("fast");
            } else {
                enemies.add("basic");
            }
        }
        
        return enemies;
    }
    
    /**
     * Calculate path length for enemy movement timing
     */
    public int calculatePathDistance(int[][] path) {
        return path.length;
    }
    
    /**
     * Enemy Stats inner class
     */
    public static class EnemyStats {
        public final int health;
        public final int speed;
        public final int reward;
        
        public EnemyStats(int health, int speed, int reward) {
            this.health = health;
            this.speed = speed;
            this.reward = reward;
        }
    }
}