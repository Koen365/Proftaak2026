package com.towerdefense.game;

import java.util.*;

/**
 * Unlockables Manager - Handles unlockable items, progression, and rewards
 */
public class UnlockablesManager {
    private static final Map<String, Integer> UNLOCK_THRESHOLDS = new HashMap<>();
    
    static {
        UNLOCK_THRESHOLDS.put("sniper_tower", 1000);
        UNLOCK_THRESHOLDS.put("machinegun_tower", 2500);
        UNLOCK_THRESHOLDS.put("desert_map", 500);
        UNLOCK_THRESHOLDS.put("fast_enemy", 1500);
        UNLOCK_THRESHOLDS.put("tank_enemy", 3000);
        UNLOCK_THRESHOLDS.put("survival_trial", 2000);
    }
    
    private Map<String, Boolean> unlockedItems;
    private Map<String, String> itemRarity;
    
    public UnlockablesManager() {
        this.unlockedItems = new HashMap<>();
        this.itemRarity = new HashMap<>();
        initializeItems();
    }
    
    private void initializeItems() {
        itemRarity.put("basic_tower", "common");
        itemRarity.put("sniper_tower", "rare");
        itemRarity.put("machinegun_tower", "epic");
        itemRarity.put("basic_enemy", "common");
        itemRarity.put("fast_enemy", "uncommon");
        itemRarity.put("tank_enemy", "rare");
        itemRarity.put("grassland_map", "common");
        itemRarity.put("desert_map", "uncommon");
        itemRarity.put("tower_builder", "common");
        itemRarity.put("survival_trial", "rare");
    }
    
    /**
     * Check if an item should be unlocked based on score
     */
    public List<String> checkUnlocks(int score, Set<String> currentUnlocks) {
        List<String> newUnlocks = new ArrayList<>();
        
        for (Map.Entry<String, Integer> entry : UNLOCK_THRESHOLDS.entrySet()) {
            if (score >= entry.getValue() && !currentUnlocks.contains(entry.getKey())) {
                newUnlocks.add(entry.getKey());
                unlockedItems.put(entry.getKey(), true);
            }
        }
        
        return newUnlocks;
    }
    
    /**
     * Check unlock based on specific criteria (waves survived, minigame score, etc.)
     */
    public List<String> checkCriteriaUnlocks(Criteria criteria) {
        List<String> unlocks = new ArrayList<>();
        
        if (criteria.wavesSurvived >= 10 && !unlocks.contains("sniper_tower")) {
            unlocks.add("sniper_tower");
        }
        if (criteria.wavesSurvived >= 20 && !unlocks.contains("machinegun_tower")) {
            unlocks.add("machinegun_tower");
        }
        if (criteria.minigameScore >= 5000 && !unlocks.contains("survival_trial")) {
            unlocks.add("survival_trial");
        }
        if (criteria.totalScore >= 10000 && !unlocks.contains("desert_map")) {
            unlocks.add("desert_map");
        }
        
        return unlocks;
    }
    
    /**
     * Get completion percentage for collection
     */
    public double getCompletionPercentage(Set<String> unlocked) {
        long total = itemRarity.size();
        long unlockedCount = unlocked.size();
        return (double) unlockedCount / total * 100;
    }
    
    /**
     * Get rarity for an item
     */
    public String getItemRarity(String itemId) {
        return itemRarity.getOrDefault(itemId, "common");
    }
    
    /**
     * Get all locked items for a user
     */
    public List<String> getLockedItems(Set<String> unlocked) {
        List<String> locked = new ArrayList<>();
        for (String item : itemRarity.keySet()) {
            if (!unlocked.contains(item)) {
                locked.add(item);
            }
        }
        return locked;
    }
    
    /**
     * Criteria class for unlock checks
     */
    public static class Criteria {
        public int wavesSurvived;
        public int minigameScore;
        public int totalScore;
        public int gamesPlayed;
        
        public Criteria(int waves, int minigameScore, int totalScore, int games) {
            this.wavesSurvived = waves;
            this.minigameScore = minigameScore;
            this.totalScore = totalScore;
            this.gamesPlayed = games;
        }
    }
}