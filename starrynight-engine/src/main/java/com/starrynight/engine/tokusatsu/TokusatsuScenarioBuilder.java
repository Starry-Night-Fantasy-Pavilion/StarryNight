package com.starrynight.engine.tokusatsu;

import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.HashMap;

public class TokusatsuScenarioBuilder {

    private final TokusatsuScenarioContext context;
    private final Map<String, String> narrativeTemplates;

    public TokusatsuScenarioBuilder(TokusatsuScenarioContext context) {
        this.context = context;
        this.narrativeTemplates = initializeTemplates();
    }

    private Map<String, String> initializeTemplates() {
        Map<String, String> templates = new HashMap<>();
        templates.put("transformation_sequence",
            "【变身场景】{rider_name}举起驱动器，喊出\"HenShin！\"。光芒包裹全身，{rider_name}变身为假面骑士{form_name}。");

        templates.put("team_assembly",
            "【队伍集结】{base_name}警报响起，{team_name}全员集合。{rider_list}分别启动各自的变身腰带，迎接新一轮战斗。");

        templates.put("monster_introduction",
            "【怪物登场】突然，地面剧烈震动。{monster_name}从裂缝中现身，散发着{element}属性的能量波动。");

        templates.put("finisher_sequence",
            "【必杀技】{rider_name}使出终极奥义——{finisher_name}！骑士踢划破空气，直击{monster_name}的要害！");

        templates.put("victory_formation",
            "【胜利阵型】假面骑士们摆出经典的骑士踢姿势，齐声高喊\"Kamen Rider\"！{monster_name}在光芒中化为尘埃。");

        templates.put("post_battle_reflection",
            "【战后】战斗结束，{rider_name}解除变身，恢复常人姿态。{ally_name}递上毛巾：\"辛苦了，下次还要一起守护这座城市。\"");

        templates.put("cliffhanger_episode",
            "【悬念】画面定格在{antagonist_name}的冷笑中。\"游戏才刚刚开始...\"他的声音回荡在废墟之上。待续。");

        return templates;
    }

    public String buildTransformationSequence(String riderName, String formName) {
        return narrativeTemplates.get("transformation_sequence")
                .replace("{rider_name}", riderName)
                .replace("{form_name}", formName);
    }

    public String buildTeamAssembly(String teamName, List<String> riderNames) {
        StringBuilder sb = new StringBuilder();
        sb.append(narrativeTemplates.get("team_assembly")
                .replace("{base_name}", context.getHeadquartersLocation())
                .replace("{team_name}", teamName));

        String riderList = String.join("、", riderNames);
        return sb.toString().replace("{rider_list}", riderList);
    }

    public String buildMonsterIntroduction(String monsterName, String element) {
        return narrativeTemplates.get("monster_introduction")
                .replace("{monster_name}", monsterName)
                .replace("{element}", element);
    }

    public String buildFinisherSequence(String riderName, String finisherName, String monsterName) {
        return narrativeTemplates.get("finisher_sequence")
                .replace("{rider_name}", riderName)
                .replace("{finisher_name}", finisherName)
                .replace("{monster_name}", monsterName);
    }

    public String buildVictoryFormation(List<String> riderNames, String monsterName) {
        String result = narrativeTemplates.get("victory_formation");
        String riderList = String.join("、", riderNames);
        return result.replace("{rider_list}", riderList)
                    .replace("{monster_name}", monsterName);
    }

    public String buildPostBattleReflection(String riderName, String allyName) {
        return narrativeTemplates.get("post_battle_reflection")
                .replace("{rider_name}", riderName)
                .replace("{ally_name}", allyName);
    }

    public String buildCliffhangerEpisode(String antagonistName) {
        return narrativeTemplates.get("cliffhanger_episode")
                .replace("{antagonist_name}", antagonistName);
    }

    public List<String> generateEpisodeOutline(int episodeNumber, String episodeTheme) {
        List<String> outline = new ArrayList<>();
        outline.add(String.format("【第%d集】%s", episodeNumber, episodeTheme));
        outline.add("1. 开场：队伍在基地进行日常训练");
        outline.add("2. 事件：警报响起，发现怪物踪迹");
        outline.add("3. 集结：队员变身，赶往现场");
        outline.add("4. 战斗：与怪物展开激战");
        outline.add("5. 危机：敌方干部现身，形势危急");
        outline.add("6. 转机：主角爆发新形态");
        outline.add("7. 必杀：使用终极奥义击败敌人");
        outline.add("8. 结尾：战后日常 + 悬念伏笔");
        return outline;
    }

    public String buildTransformationPrompt(String riderName, String riderIdentity, String transformationItem) {
        StringBuilder sb = new StringBuilder();
        sb.append("请描写").append(riderName).append("的变身场景：\n\n");
        sb.append("角色背景：").append(riderIdentity).append("\n");
        sb.append("变身道具：").append(transformationItem).append("\n");
        sb.append("变身风格：").append(context.getTransformationStyle()).append("\n\n");
        sb.append("要求：\n");
        sb.append("- 描写变身动作的层次感和仪式感\n");
        sb.append("- 体现假面骑士的热血精神\n");
        sb.append("- 穿插内心独白，展现角色的决心\n");
        sb.append("- 变身时间控制在200-300字\n");
        return sb.toString();
    }

    public String buildTeamBattlePrompt(List<String> riderNames, String enemyName, String battleLocation) {
        StringBuilder sb = new StringBuilder();
        sb.append("请描写团队作战场景：\n\n");
        sb.append("参战骑士：").append(String.join("、", riderNames)).append("\n");
        sb.append("敌方：").append(enemyName).append("\n");
        sb.append("战斗地点：").append(battleLocation).append("\n\n");
        sb.append("要求：\n");
        sb.append("- 体现每位骑士的独特战斗风格\n");
        sb.append("- 展现团队配合作战的默契\n");
        sb.append("- 战斗节奏要有起伏\n");
        sb.append("- 包含1-2次危机时刻\n");
        sb.append("- 最终必杀技要热血沸腾\n");
        return sb.toString();
    }
}
