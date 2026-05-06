package com.starrynight.engine.tokusatsu.model;

import lombok.Data;

@Data
public class EpisodeCard {
    private int episodeNo;
    private MonsterEvent monsterEvent;
    private VictimEvent victimEvent;
    private Gains gains;
    private MainPlotConnection mainPlotConnection;
    private String battleLocation;

    @Data
    public static class MonsterEvent {
        private String mainMonster;
        private String[] minions;
        private ThreatLevel episodeThreat;
    }

    public enum ThreatLevel {
        LOW, MEDIUM, HIGH
    }

    @Data
    public static class VictimEvent {
        private VictimType type;
        private String description;
    }

    public enum VictimType {
        CIVILIAN, ALLY, SELF
    }

    @Data
    public static class Gains {
        private String newForm;
        private String newDevice;
        private String plotAdvance;
    }

    @Data
    public static class MainPlotConnection {
        private String foreshadowingId;
        private double advanceAmount;
    }

    public static EpisodeCard create(int episodeNo, String monster, ThreatLevel threat) {
        EpisodeCard card = new EpisodeCard();
        card.setEpisodeNo(episodeNo);

        MonsterEvent monsterEvent = new MonsterEvent();
        monsterEvent.setMainMonster(monster);
        monsterEvent.setEpisodeThreat(threat);
        card.setMonsterEvent(monsterEvent);

        return card;
    }

    public EpisodeCard setVictim(VictimType type, String description) {
        VictimEvent victim = new VictimEvent();
        victim.setType(type);
        victim.setDescription(description);
        this.setVictimEvent(victim);
        return this;
    }

    public EpisodeCard setGains(String newForm, String newDevice, String plotAdvance) {
        Gains gains = new Gains();
        gains.setNewForm(newForm);
        gains.setNewDevice(newDevice);
        gains.setPlotAdvance(plotAdvance);
        this.setGains(gains);
        return this;
    }

    public EpisodeCard setMainPlot(String foreshadowingId, double advanceAmount) {
        MainPlotConnection connection = new MainPlotConnection();
        connection.setForeshadowingId(foreshadowingId);
        connection.setAdvanceAmount(advanceAmount);
        this.setMainPlotConnection(connection);
        return this;
    }

    public String generateEpisodeSummary() {
        StringBuilder sb = new StringBuilder();
        sb.append("【第").append(episodeNo).append("话】\n");
        sb.append("怪人：").append(monsterEvent.getMainMonster());
        if (monsterEvent.getMinions() != null && monsterEvent.getMinions().length > 0) {
            sb.append("（伴随").append(String.join("、", monsterEvent.getMinions())).append("）");
        }
        sb.append("\n");
        sb.append("威胁等级：").append(monsterEvent.getEpisodeThreat()).append("\n");

        if (victimEvent != null) {
            sb.append("受害者：").append(victimEvent.getDescription()).append("\n");
        }

        if (gains != null) {
            if (gains.getNewForm() != null) {
                sb.append("获得新形态：").append(gains.getNewForm()).append("\n");
            }
            if (gains.getNewDevice() != null) {
                sb.append("获得新道具：").append(gains.getNewDevice()).append("\n");
            }
        }

        if (mainPlotConnection != null) {
            sb.append("主线推进：").append((int)(mainPlotConnection.getAdvanceAmount() * 100)).append("%\n");
        }

        sb.append("战斗地点：").append(battleLocation).append("\n");

        return sb.toString();
    }
}
