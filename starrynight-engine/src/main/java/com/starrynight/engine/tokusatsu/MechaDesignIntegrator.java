package com.starrynight.engine.tokusatsu;

import lombok.Data;
import java.util.List;
import java.util.ArrayList;
import java.util.Map;
import java.util.HashMap;

public class MechaDesignIntegrator {

    @Data
    public static class MechaDesign {
        private String name;
        private String type;
        private String colorScheme;
        private String primaryWeapon;
        private String specialAbility;
        private int powerLevel;
        private List<String> designFeatures;
    }

    @Data
    public static class IntegratedDesign {
        private MechaDesign mecha;
        private String riderCompatibility;
        private String combatMode;
        private String fusionSequence;
        private List<String> upgradePaths;
    }

    private final Map<String, MechaDesign> mechaDatabase;

    public MechaDesignIntegrator() {
        this.mechaDatabase = initializeMechaDatabase();
    }

    private Map<String, MechaDesign> initializeMechaDatabase() {
        Map<String, MechaDesign> database = new HashMap<>();

        MechaDesign riderMecha = new MechaDesign();
        riderMecha.setName("骑士机械");
        riderMecha.setType("humanoid");
        riderMecha.setColorScheme("红/银");
        riderMecha.setPrimaryWeapon("骑士剑");
        riderMecha.setSpecialAbility("陆地突击");
        riderMecha.setPowerLevel(8000);
        riderMecha.setDesignFeatures(List.of("流线型装甲", "能量推进器", "骑士之眼传感器"));
        database.put("rider_vehicle", riderMecha);

        MechaDesign combatDrill = new MechaDesign();
        combatDrill.setName("战斗钻机");
        combatDrill.setType("drill");
        combatDrill.setColorScheme("蓝/金");
        combatDrill.setPrimaryWeapon("超级钻头");
        combatDrill.setSpecialAbility("地下穿透");
        combatDrill.setPowerLevel(7500);
        combatDrill.setDesignFeatures(List.of("旋转钻头", "装甲外壳", "地下导航系统"));
        database.put("combat_drill", combatDrill);

        return database;
    }

    public IntegratedDesign integrateDesign(String riderName, String mechaType, String combatScenario) {
        IntegratedDesign result = new IntegratedDesign();

        MechaDesign baseMecha = mechaDatabase.get(mechaType);
        if (baseMecha == null) {
            baseMecha = mechaDatabase.get("rider_vehicle");
        }

        result.setMecha(baseMecha);
        result.setRiderCompatibility(generateRiderCompatibility(riderName, baseMecha));
        result.setCombatMode(determineCombatMode(combatScenario));
        result.setFusionSequence(generateFusionSequence(riderName, baseMecha));
        result.setUpgradePaths(generateUpgradePaths(baseMecha));

        return result;
    }

    private String generateRiderCompatibility(String riderName, MechaDesign mecha) {
        return String.format("%s与%s的适配度达到97%%。骑手能够完美操控机械的各项功能，战斗时人机合一，发挥出120%%的战斗潜力。",
                riderName, mecha.getName());
    }

    private String determineCombatMode(String scenario) {
        if (scenario.contains("空")) {
            return "空中压制模式";
        } else if (scenario.contains("地")) {
            return "陆地突击模式";
        } else if (scenario.contains("海")) {
            return "水中潜航模式";
        } else {
            return "综合战斗模式";
        }
    }

    private String generateFusionSequence(String riderName, MechaDesign mecha) {
        StringBuilder sb = new StringBuilder();
        sb.append("【融合启动】\n");
        sb.append(riderName).append("呼叫支援：\"").append(mecha.getName()).append("，前来汇合！\"\n\n");
        sb.append("【机械变形】\n");
        sb.append(mecha.getName()).append("启动变形程序，部件展开重组。\n\n");
        sb.append("【人机对接】\n");
        sb.append(riderName).append("举起骑士腰带，发出对接指令。\n\n");
        sb.append("【融合完成】\n");
        sb.append("光芒闪烁，").append(mecha.getName()).append("与").append(riderName).append("完成融合！");
        return sb.toString();
    }

    private List<String> generateUpgradePaths(MechaDesign mecha) {
        List<String> upgrades = new ArrayList<>();
        upgrades.add("超进化形态 - 解锁终极形态");
        upgrades.add("武器强化模块 - 攻击力+50%");
        upgrades.add("防御增幅系统 - 伤害减免+30%");
        upgrades.add("速度增强组件 - 移动速度+40%");
        return upgrades;
    }

    public String generateMechaDescription(MechaDesign mecha) {
        StringBuilder sb = new StringBuilder();
        sb.append("【").append(mecha.getName()).append("】\n");
        sb.append("类型：").append(mecha.getType()).append("\n");
        sb.append("配色：").append(mecha.getColorScheme()).append("\n");
        sb.append("主武器：").append(mecha.getPrimaryWeapon()).append("\n");
        sb.append("特殊能力：").append(mecha.getSpecialAbility()).append("\n");
        sb.append("战斗力：").append(mecha.getPowerLevel()).append("\n");
        sb.append("设计特点：").append(String.join("、", mecha.getDesignFeatures()));
        return sb.toString();
    }
}
