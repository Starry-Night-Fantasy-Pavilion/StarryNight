package com.starrynight.engine.tokusatsu.model;

import lombok.Data;
import java.util.List;
import java.util.Map;
import java.util.HashMap;

@Data
public class VillainTemplate {
    private String id;
    private String name;
    private VillainCategory category;
    private Organization organization;
    private Abilities abilities;
    private StatusHistory[] statusHistory;
    private Map<String, RivalryRelation> rivalries;

    public enum VillainCategory {
        MONSTER, RIDER, KAIJIN, ULTRAMAN, BOSS
    }

    @Data
    public static class Organization {
        private String id;
        private String name;
    }

    @Data
    public static class Abilities {
        private int combatPower;
        private String[] specialAttacks;
        private String[] weaknesses;
        private String[] bodyParts;
    }

    @Data
    public static class StatusHistory {
        private VillainStatus status;
        private Integer deathChapter;
        private String revivalCondition;
    }

    public enum VillainStatus {
        ALIVE, DEAD, REVIVED, CLONED
    }

    @Data
    public static class RivalryRelation {
        private RivalryType type;
        private String specificForm;
    }

    public enum RivalryType {
        WEAK_TO, STRONG_TO, EQUAL
    }

    public static VillainTemplate createMonster(String id, String name, int power) {
        VillainTemplate villain = new VillainTemplate();
        villain.setId(id);
        villain.setName(name);
        villain.setCategory(VillainCategory.MONSTER);
        villain.setRivalries(new HashMap<>());

        Abilities abilities = new Abilities();
        abilities.setCombatPower(power);
        abilities.setSpecialAttacks(new String[]{"普通攻击"});
        abilities.setWeaknesses(new String[]{"火焰"});
        abilities.setBodyParts(new String[]{"核心"});
        villain.setAbilities(abilities);

        return villain;
    }

    public VillainTemplate setOrganization(String orgId, String orgName) {
        Organization org = new Organization();
        org.setId(orgId);
        org.setName(orgName);
        this.setOrganization(org);
        return this;
    }

    public VillainTemplate addRivalry(String riderId, RivalryType type, String specificForm) {
        if (this.rivalries == null) {
            this.rivalries = new HashMap<>();
        }
        RivalryRelation relation = new RivalryRelation();
        relation.setType(type);
        relation.setSpecificForm(specificForm);
        this.rivalries.put(riderId, relation);
        return this;
    }

    public VillainTemplate markDeath(int chapterNo) {
        StatusHistory history = new StatusHistory();
        history.setStatus(VillainStatus.DEAD);
        history.setDeathChapter(chapterNo);
        this.setStatusHistory(new StatusHistory[]{history});
        return this;
    }

    public VillainTemplate markRevival(String condition) {
        StatusHistory history = new StatusHistory();
        history.setStatus(VillainStatus.REVIVED);
        history.setRevivalCondition(condition);
        this.setStatusHistory(new StatusHistory[]{history});
        return this;
    }

    public String getWeaknessInfo() {
        if (abilities == null || abilities.getWeaknesses() == null) {
            return "无已知弱点";
        }
        return String.join("、", abilities.getWeaknesses());
    }

    public boolean isAlive() {
        if (statusHistory == null || statusHistory.length == 0) {
            return true;
        }
        StatusHistory latest = statusHistory[statusHistory.length - 1];
        return latest.getStatus() != VillainStatus.DEAD;
    }
}
