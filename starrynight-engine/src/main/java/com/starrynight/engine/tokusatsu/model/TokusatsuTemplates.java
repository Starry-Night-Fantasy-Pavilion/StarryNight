package com.starrynight.engine.tokusatsu.model;

import lombok.Data;
import java.util.List;
import java.util.Map;
import java.util.HashMap;

@Data
public class TokusatsuTemplates {

    private Map<String, EnergySource> energySources;
    private Map<String, TransformationLimit> transformationLimits;
    private Map<String, BattleMode> battleModes;
    private Map<String, MentalBurstTrigger> mentalBurstTriggers;

    @Data
    public static class EnergySource {
        private String id;
        private String name;
        private String color;
        private String description;

        public static EnergySource create(String id, String name, String color) {
            EnergySource source = new EnergySource();
            source.setId(id);
            source.setName(name);
            source.setColor(color);
            return source;
        }
    }

    @Data
    public static class TransformationLimit {
        private String type;
        private String name;
        private String unit;
        private List<String> sideEffects;

        public static TransformationLimit timeLimit(int seconds) {
            TransformationLimit limit = new TransformationLimit();
            limit.setType("time_limit");
            limit.setName("时间限制");
            limit.setUnit(seconds + "秒");
            limit.setSideEffects(List.of("能量耗尽", "形态退化"));
            return limit;
        }

        public static TransformationLimit useCount(int count) {
            TransformationLimit limit = new TransformationLimit();
            limit.setType("use_count");
            limit.setName("使用次数");
            limit.setUnit(count + "次");
            limit.setSideEffects(List.of("道具损坏", "变身失败"));
            return limit;
        }
    }

    @Data
    public static class BattleMode {
        private String id;
        private String name;
        private String description;
        private List<String> applicableScenes;

        public static BattleMode giant() {
            BattleMode mode = new BattleMode();
            mode.setId("giant");
            mode.setName("巨大化战斗");
            mode.setDescription("与等身或大型怪人进行战斗");
            mode.setApplicableScenes(List.of("城市中心", "工业区", "山区"));
            return mode;
        }

        public static BattleMode humanScale() {
            BattleMode mode = new BattleMode();
            mode.setId("human_scale");
            mode.setName("等身战");
            mode.setDescription("在人类尺度下进行战斗");
            mode.setApplicableScenes(List.of("街道", "建筑物内", "地下"));
            return mode;
        }

        public static BattleMode both() {
            BattleMode mode = new BattleMode();
            mode.setId("both");
            mode.setName("双重战斗规则");
            mode.setDescription("同时包含巨大化和等身战");
            mode.setApplicableScenes(List.of("城市", "任何场景"));
            return mode;
        }
    }

    @Data
    public static class MentalBurstTrigger {
        private String condition;
        private BurstIntensity intensity;

        public enum BurstIntensity {
            LOW, MEDIUM, HIGH, EXTREME
        }

        public static MentalBurstTrigger create(String condition, BurstIntensity intensity) {
            MentalBurstTrigger trigger = new MentalBurstTrigger();
            trigger.setCondition(condition);
            trigger.setIntensity(intensity);
            return trigger;
        }
    }

    public static TokusatsuTemplates createDefault() {
        TokusatsuTemplates templates = new TokusatsuTemplates();

        templates.setEnergySources(new HashMap<>());
        templates.getEnergySources().put("photon_blood", EnergySource.create("photon_blood", "光子血液", "#FFD700"));
        templates.getEnergySources().put("magic_energy", EnergySource.create("magic_energy", "魔力", "#9B59B6"));
        templates.getEnergySources().put("rider_power", EnergySource.create("rider_power", "骑士槽", "#E74C3C"));
        templates.getEnergySources().put("light_force", EnergySource.create("light_force", "光之力", "#3498DB"));

        templates.setTransformationLimits(new HashMap<>());
        templates.getTransformationLimits().put("time_limit_60", TransformationLimit.timeLimit(60));
        templates.getTransformationLimits().put("time_limit_120", TransformationLimit.timeLimit(120));
        templates.getTransformationLimits().put("use_count_3", TransformationLimit.useCount(3));
        templates.getTransformationLimits().put("use_count_5", TransformationLimit.useCount(5));

        templates.setBattleModes(new HashMap<>());
        templates.getBattleModes().put("giant", BattleMode.giant());
        templates.getBattleModes().put("human_scale", BattleMode.humanScale());
        templates.getBattleModes().put("both", BattleMode.both());

        templates.setMentalBurstTriggers(new HashMap<>());
        templates.getMentalBurstTriggers().put("resolve", MentalBurstTrigger.create("不再迷茫", MentalBurstTrigger.BurstIntensity.HIGH));
        templates.getMentalBurstTriggers().put("bond", MentalBurstTrigger.create("同伴羁绊", MentalBurstTrigger.BurstIntensity.MEDIUM));
        templates.getMentalBurstTriggers().put("anger", MentalBurstTrigger.create("愤怒突破", MentalBurstTrigger.BurstIntensity.HIGH));
        templates.getMentalBurstTriggers().put("sacrifice", MentalBurstTrigger.create("牺牲觉悟", MentalBurstTrigger.BurstIntensity.EXTREME));

        return templates;
    }

    public EnergySource getEnergySource(String id) {
        return energySources.get(id);
    }

    public TransformationLimit getTransformationLimit(String id) {
        return transformationLimits.get(id);
    }

    public BattleMode getBattleMode(String id) {
        return battleModes.get(id);
    }
}
