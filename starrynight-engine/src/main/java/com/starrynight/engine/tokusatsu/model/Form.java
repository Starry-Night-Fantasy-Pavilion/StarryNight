package com.starrynight.engine.tokusatsu.model;

import lombok.Data;
import java.util.List;
import java.util.Map;
import java.util.HashMap;

@Data
public class Form {
    private String id;
    private String name;
    private String parentFormId;
    private List<String> childFormIds;
    private EvolutionConditions evolutionConditions;
    private DegenerationConditions degenerationConditions;
    private AbilityVector abilityVector;
    private Map<String, Double> enemyWeaknesses;
    private int minEnergyRequired;

    @Data
    public static class EvolutionConditions {
        private String emotionalTrigger;
        private String deviceRequired;
        private Boolean externalCharge;
        private String battleCondition;
    }

    @Data
    public static class DegenerationConditions {
        private boolean energyDepletion;
        private boolean transformationTimeout;
        private boolean forcedByEnemy;
    }

    @Data
    public static class AbilityVector {
        private int power;
        private int speed;
        private int defense;
        private int special;
        private List<String> specialAbilities;
        private List<String> weaknesses;
    }

    public static Form createBaseForm(String id, String name) {
        Form form = new Form();
        form.setId(id);
        form.setName(name);
        form.setChildFormIds(new java.util.ArrayList<>());
        form.setEnemyWeaknesses(new HashMap<>());
        form.setMinEnergyRequired(0);

        AbilityVector abilities = new AbilityVector();
        abilities.setPower(50);
        abilities.setSpeed(50);
        abilities.setDefense(50);
        abilities.setSpecial(50);
        abilities.setSpecialAbilities(new java.util.ArrayList<>());
        abilities.setWeaknesses(new java.util.ArrayList<>());
        form.setAbilityVector(abilities);

        return form;
    }

    public Form addEvolution(String targetFormId, String emotionalTrigger, String deviceRequired) {
        if (this.childFormIds == null) {
            this.childFormIds = new java.util.ArrayList<>();
        }
        this.childFormIds.add(targetFormId);

        if (this.evolutionConditions == null) {
            this.evolutionConditions = new EvolutionConditions();
        }
        this.evolutionConditions.setEmotionalTrigger(emotionalTrigger);
        this.evolutionConditions.setDeviceRequired(deviceRequired);

        return this;
    }

    public void addEnemyWeakness(String enemyId, double weaknessFactor) {
        if (this.enemyWeaknesses == null) {
            this.enemyWeaknesses = new HashMap<>();
        }
        this.enemyWeaknesses.put(enemyId, weaknessFactor);
    }
}
