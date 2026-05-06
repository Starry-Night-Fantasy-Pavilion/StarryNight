package com.starrynight.engine.tokusatsu;

import lombok.Data;
import java.util.List;
import java.util.ArrayList;
import java.util.Map;
import java.util.HashMap;

public class TeamTacticsEngine {

    @Data
    public static class TeamMember {
        private String name;
        private String riderForm;
        private String combatRole;
        private int combatPower;
        private List<String> specialSkills;
    }

    @Data
    public static class TacticsPlan {
        private String tacticName;
        private String description;
        private List<String> executionSteps;
        private Map<String, String> memberAssignments;
        private String finishingMove;
        private int estimatedPower;
    }

    @Data
    public static class CombatScenario {
        private String enemyName;
        private String enemyType;
        private int enemyPower;
        private String battlefield;
        private String weatherCondition;
    }

    private final Map<String, List<String>> tacticsLibrary;

    public TeamTacticsEngine() {
        this.tacticsLibrary = initializeTacticsLibrary();
    }

    private Map<String, List<String>> initializeTacticsLibrary() {
        Map<String, List<String>> library = new HashMap<>();

        library.put("surround", List.of(
            "全员分散包围敌人",
            "建立包围圈，从四面牵制",
            "远程骑士进行火力压制",
            "近战骑士等待时机突袭",
            "同步发起进攻"
        ));

        library.put("pincer", List.of(
            "分成两队从左右两侧突击",
            "利用地形掩护接近",
            "两侧同时发动攻击",
            "造成混乱后中央突破"
        ));

        library.put("rush", List.of(
            "主力骑士担任先锋",
            "全队直线冲锋",
            "集中火力打开缺口",
            "一击必杀"
        ));

        library.put("defense", List.of(
            "防御型骑士顶在最前",
            "建立防线抵挡攻击",
            "辅助骑士寻找破绽",
            "反击时机成熟后反击"
        ));

        library.put("combined_finisher", List.of(
            "全员聚集能量",
            "能量汇聚于主力骑士",
            "联合必杀技准备",
            "全员释放终极攻击"
        ));

        return library;
    }

    public TacticsPlan generateTactics(List<TeamMember> team, CombatScenario scenario) {
        TacticsPlan plan = new TacticsPlan();
        plan.setTacticName(determineTacticName(scenario));
        plan.setDescription(generateTacticDescription(team, scenario));
        plan.setExecutionSteps(selectTacticSteps(scenario));
        plan.setMemberAssignments(assignMembers(team, scenario));
        plan.setFinishingMove(generateFinishingMove(team, scenario));
        plan.setEstimatedPower(calculateTotalPower(team) + 5000);

        return plan;
    }

    private String determineTacticName(CombatScenario scenario) {
        if (scenario.getEnemyPower() > 10000) {
            return "联合必杀战术";
        } else if (scenario.getBattlefield().contains("狭窄")) {
            return "钳形包围战术";
        } else if (scenario.getWeatherCondition().contains("雨天")) {
            return "环境利用战术";
        } else {
            return "标准突击战术";
        }
    }

    private String generateTacticDescription(List<TeamMember> team, CombatScenario scenario) {
        return String.format("%d名假面骑士对抗%s的%d场团队作战。",
                team.size(), scenario.getEnemyName(), scenario.getEnemyPower());
    }

    private List<String> selectTacticSteps(CombatScenario scenario) {
        if (scenario.getEnemyPower() > 10000) {
            return tacticsLibrary.get("combined_finisher");
        } else if (scenario.getEnemyType().contains("集团")) {
            return tacticsLibrary.get("surround");
        } else {
            return tacticsLibrary.get("rush");
        }
    }

    private Map<String, String> assignMembers(List<TeamMember> team, CombatScenario scenario) {
        Map<String, String> assignments = new HashMap<>();

        for (TeamMember member : team) {
            switch (member.getCombatRole()) {
                case "前锋":
                    assignments.put(member.getName(), "担任突击先锋，正面牵制敌人");
                    break;
                case "支援":
                    assignments.put(member.getName(), "提供火力支援，寻找敌人破绽");
                    break;
                case "防御":
                    assignments.put(member.getName(), "守护队友，抵挡敌人攻击");
                    break;
                case "终结":
                    assignments.put(member.getName(), "把握时机，给予敌人致命一击");
                    break;
                default:
                    assignments.put(member.getName(), "协同作战，配合团队行动");
            }
        }

        return assignments;
    }

    private String generateFinishingMove(List<TeamMember> team, CombatScenario scenario) {
        StringBuilder sb = new StringBuilder();
        sb.append("【终极一击】\n");
        sb.append("队长一声令下：\"全员准备——\"\n\n");
        sb.append("所有骑士的能量汇聚成一道璀璨的光柱。\n\n");
        sb.append("\"假面骑士——")
           .append(team.stream().map(TeamMember::getRiderForm).findFirst().orElse("骑士"))
           .append("！\"\n\n");
        sb.append("众人的必杀技合为一体，直击")
           .append(scenario.getEnemyName())
           .append("的要害！\n\n");
        sb.append("轰——！\n");
        sb.append("敌人应声倒下，化为虚无。");
        return sb.toString();
    }

    private int calculateTotalPower(List<TeamMember> team) {
        return team.stream().mapToInt(TeamMember::getCombatPower).sum();
    }

    public String generateCombatNarrative(TacticsPlan plan, CombatScenario scenario) {
        StringBuilder sb = new StringBuilder();
        sb.append("【").append(plan.getTacticName()).append("】\n\n");
        sb.append(plan.getDescription()).append("\n\n");

        sb.append("【作战部署】\n");
        plan.getMemberAssignments().forEach((name, role) -> {
            sb.append("- ").append(name).append("：").append(role).append("\n");
        });
        sb.append("\n");

        sb.append("【执行步骤】\n");
        for (int i = 0; i < plan.getExecutionSteps().size(); i++) {
            sb.append(i + 1).append(". ").append(plan.getExecutionSteps().get(i)).append("\n");
        }
        sb.append("\n");

        sb.append("【战场环境】\n");
        sb.append("地点：").append(scenario.getBattlefield()).append("\n");
        sb.append("天气：").append(scenario.getWeatherCondition()).append("\n\n");

        sb.append(plan.getFinishingMove());

        return sb.toString();
    }

    public List<String> getAvailableTactics() {
        return new ArrayList<>(tacticsLibrary.keySet());
    }
}
