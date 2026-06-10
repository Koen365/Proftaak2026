package com.towerdefense.game;

import java.time.LocalDate;
import java.time.temporal.ChronoUnit;
import java.util.*;

/**
 * Score Calculator - Handles score calculations, bonuses, and daily rewards
 */
public class ScoreCalculator {
    private static final int STREAK_BONUS_DAY_3 = 50;
    private static final int STREAK_BONUS_DAY_7 = 100;
    private static final int STREAK_BONUS_DAY_30 = 500;
    
    /**
     * Calculate final score with multipliers
     */
    public int calculateFinalScore(int baseScore, int enemiesKilled, int timeSurvived, int combo) {
        int base = baseScore;
        int enemyBonus = enemiesKilled * 10;
        int timeBonus = timeSurvived / 10;
        int comboBonus = combo * 50;
        
        double multiplier = 1.0 + (combo * 0.05);
        
        return (int)((base + enemyBonus + timeBonus + comboBonus) * multiplier);
    }
    
    /**
     * Calculate minigame score
     */
    public MinigameScore calculateMinigameScore(int wavesSurvived, int resourcesRemaining, int timeElapsed) {
        int waveScore = wavesSurvived * 100;
        int resourceBonus = resourcesRemaining * 2;
        int timeBonus = Math.max(0, 300 - timeElapsed) * 5;
        int efficiency = (resourcesRemaining > 300) ? 200 : 0;
        
        int total = waveScore + resourceBonus + timeBonus + efficiency;
        
        return new MinigameScore(total, wavesSurvived, resourcesRemaining);
    }
    
    /**
     * Calculate daily login reward
     */
    public DailyReward calculateDailyReward(int streak) {
        int baseReward = 100;
        int streakBonus = 0;
        
        if (streak >= 30) streakBonus = STREAK_BONUS_DAY_30;
        else if (streak >= 7) streakBonus = STREAK_BONUS_DAY_7;
        else if (streak >= 3) streakBonus = STREAK_BONUS_DAY_3;
        
        int total = baseReward + streakBonus;
        
        return new DailyReward(total, streak, streakBonus > 0);
    }
    
    /**
     * Calculate ranking based on score
     */
    public int calculateRank(int score, List<Integer> allScores) {
        int rank = 1;
        for (int s : allScores) {
            if (s > score) rank++;
        }
        return rank;
    }
    
    /**
     * Calculate combo multiplier
     */
    public double calculateComboMultiplier(int killsInRow) {
        if (killsInRow < 5) return 1.0;
        if (killsInRow < 10) return 1.2;
        if (killsInRow < 20) return 1.5;
        return 2.0;
    }
    
    /**
     * Inner class for minigame scores
     */
    public static class MinigameScore {
        public final int totalScore;
        public final int wavesSurvived;
        public final int resourcesRemaining;
        
        public MinigameScore(int total, int waves, int resources) {
            this.totalScore = total;
            this.wavesSurvived = waves;
            this.resourcesRemaining = resources;
        }
    }
    
    /**
     * Inner class for daily rewards
     */
    public static class DailyReward {
        public final int coins;
        public final int streak;
        public final boolean bonusUnlocked;
        
        public DailyReward(int coins, int streak, boolean bonus) {
            this.coins = coins;
            this.streak = streak;
            this.bonusUnlocked = bonus;
        }
    }
}