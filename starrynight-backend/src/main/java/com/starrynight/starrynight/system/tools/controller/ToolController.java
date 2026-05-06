package com.starrynight.starrynight.system.tools.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import org.springframework.web.bind.annotation.*;

import java.util.*;

@RestController
@RequestMapping("/api/tools")
public class ToolController {

    // ==================== 金手指生成器 ====================

    @PostMapping("/golden-finger")
    public ResponseVO<Map<String, Object>> generateGoldenFinger(@RequestBody Map<String, String> request) {
        String genre = request.getOrDefault("genre", "玄幻");
        List<Map<String, Object>> results = generateGoldenFingerResults(genre);
        return ResponseVO.success(Map.of("results", results));
    }

    // ==================== 书名生成器 ====================

    @PostMapping("/book-title")
    public ResponseVO<Map<String, Object>> generateBookTitle(@RequestBody Map<String, String> request) {
        String genre = request.getOrDefault("genre", "玄幻");
        String style = request.getOrDefault("style", "爆款");
        List<Map<String, Object>> results = generateTitleResults(genre, style);
        return ResponseVO.success(Map.of("results", results));
    }

    // ==================== 简介生成器 ====================

    @PostMapping("/synopsis")
    public ResponseVO<Map<String, Object>> generateSynopsis(@RequestBody Map<String, String> request) {
        String title = request.getOrDefault("title", "");
        String genre = request.getOrDefault("genre", "玄幻");
        String coreConflict = request.getOrDefault("coreConflict", "");
        String protagonist = request.getOrDefault("protagonist", "主角");
        String worldBackground = request.getOrDefault("worldBackground", "");

        StringBuilder sb = new StringBuilder();
        sb.append("【").append(genre).append("】");
        if (!title.isEmpty()) {
            sb.append("《").append(title).append("》");
        }
        sb.append("\n\n");
        sb.append(protagonist).append("原本是").append(worldBackground.isEmpty() ? "一个平凡少年" : worldBackground).append("，");
        sb.append("却因一次意外卷入了").append(coreConflict.isEmpty() ? "一场关乎命运的纷争" : coreConflict).append("。\n\n");
        sb.append("当命运的齿轮开始转动，没有人能够置身事外……\n");
        sb.append("在这个强者为尊的世界里，唯有不断变强，才能守护自己想要守护的一切。\n\n");
        sb.append("（基于「").append(genre).append("」风格生成，可进一步编辑完善）");

        return ResponseVO.success(Map.of("result", sb.toString()));
    }

    // ==================== 世界观生成器 ====================

    @PostMapping("/worldview")
    public ResponseVO<Map<String, Object>> generateWorldview(@RequestBody Map<String, String> request) {
        String genre = request.getOrDefault("genre", "玄幻");
        String coreElement = request.getOrDefault("coreElement", "修炼");
        String worldName = request.getOrDefault("worldName", genre + "大陆");

        Map<String, Object> worldview = new LinkedHashMap<>();
        worldview.put("worldName", worldName);
        worldview.put("geography", generateGeography(genre));
        worldview.put("powerSystem", generatePowerSystem(genre, coreElement));
        worldview.put("factions", generateFactions(genre));
        worldview.put("rules", generateWorldRules(genre));
        worldview.put("history", generateHistory(genre, worldName));

        return ResponseVO.success(Map.of("worldview", worldview));
    }

    // ==================== 冲突桥段生成器 ====================

    @PostMapping("/conflict")
    public ResponseVO<Map<String, Object>> generateConflict(@RequestBody Map<String, String> request) {
        String type = request.getOrDefault("type", "战斗");
        String characterA = request.getOrDefault("characterA", "主角");
        String characterB = request.getOrDefault("characterB", "反派");
        List<Map<String, Object>> conflicts = generateConflictResults(type, characterA, characterB);
        return ResponseVO.success(Map.of("results", conflicts));
    }

    // ==================== 人物速成生成器 ====================

    @PostMapping("/quick-character")
    public ResponseVO<Map<String, Object>> quickCharacter(@RequestBody Map<String, String> request) {
        String keyword = request.getOrDefault("keyword", "剑客");
        String genre = request.getOrDefault("genre", "玄幻");
        Map<String, Object> character = generateQuickCharacter(keyword, genre);
        return ResponseVO.success(Map.of("character", character));
    }

    // ==================== 桥段拼接器 ====================

    @PostMapping("/plot-weaver")
    public ResponseVO<Map<String, Object>> weavePlot(@RequestBody Map<String, String> request) {
        String genre = request.getOrDefault("genre", "玄幻");
        String stage = request.getOrDefault("stage", "开局");
        String protagonist = request.getOrDefault("protagonist", "主角");

        List<Map<String, Object>> plotPoints = generatePlotPoints(genre, stage, protagonist);
        return ResponseVO.success(Map.of("plotPoints", plotPoints));
    }

    // ==================== 私有辅助方法 ====================

    private List<Map<String, Object>> generateGoldenFingerResults(String genre) {
        List<Map<String, Object>> list = new ArrayList<>();
        list.add(Map.of(
                "name", "抽奖系统",
                "type", "系统流",
                "description", "每日签到抽奖，概率获得功法、丹药、神器",
                "features", List.of("每日抽奖", "保底机制", "隐藏奖励")
        ));
        list.add(Map.of(
                "name", "老爷爷戒指",
                "type", "随身流",
                "description", "戒指中封印着上古大能的残魂",
                "features", List.of("随身导师", "知识传承", "关键时刻出手")
        ));
        list.add(Map.of(
                "name", "重生记忆",
                "type", "重生流",
                "description", "带着前世记忆重生到关键节点",
                "features", List.of("先知先觉", "抢占先机", "改变命运")
        ));
        list.add(Map.of(
                "name", "万能系统",
                "type", "系统流",
                "description", "拥有商城、任务、抽奖等多功能系统",
                "features", List.of("任务系统", "积分商城", "技能兑换")
        ));
        list.add(Map.of(
                "name", "神级天赋",
                "type", "天赋流",
                "description", "觉醒万中无一的绝世天赋",
                "features", List.of("修炼加速", "越级挑战", "领悟神技")
        ));
        return list;
    }

    private List<Map<String, Object>> generateTitleResults(String genre, String style) {
        List<Map<String, Object>> list = new ArrayList<>();
        String[][] titles = {
                {"万古第一神", "95"},
                {"开局签到荒古圣体", "92"},
                {"重生之绝世武神", "90"},
                {"我有一座随身仙府", "88"},
                {"九转混沌诀", "87"},
                {"从凡人开始无敌", "85"},
                {"剑道独尊", "84"},
                {"万界主宰", "82"},
                {"我在幕后掌控一切", "80"},
                {"诸天从签到开始", "78"}
        };
        for (String[] t : titles) {
            list.add(Map.of("title", t[0], "score", t[1], "genre", genre, "style", style));
        }
        return list;
    }

    private List<String> generateGeography(String genre) {
        return switch (genre) {
            case "玄幻" -> List.of("东域（人族领地）", "西域（妖族领地）", "南海（神秘海域）", "北境（冰封之地）", "中州（核心区域）");
            case "仙侠" -> List.of("凡人界", "修真界", "仙界", "神界", "混沌虚空");
            case "科幻" -> List.of("地球联邦", "火星殖民地", "星际要塞", "虫族星域", "未知星域");
            default -> List.of("中央大陆", "迷雾森林", "无尽海域", "荒古沙漠", "极北冰原");
        };
    }

    private List<String> generatePowerSystem(String genre, String coreElement) {
        return switch (genre) {
            case "玄幻" -> List.of(
                    coreElement + "体系",
                    "境界划分：炼体→开脉→凝气→筑基→金丹→元婴→化神→合体→大乘→渡劫",
                    "特殊体质：荒古圣体、混沌道体、先天神体"
            );
            case "仙侠" -> List.of(
                    "修真体系",
                    "境界划分：练气→筑基→金丹→元婴→化神→合体→大乘→渡劫→飞升",
                    "修炼资源：灵石、丹药、法宝、功法"
            );
            case "科幻" -> List.of(
                    "科技强化体系",
                    "等级划分：D级→C级→B级→A级→S级→SS级",
                    "强化方式：基因改造、机械义体、精神异能"
            );
            default -> List.of(
                    coreElement + "体系",
                    "境界划分：入门→小成→大成→圆满→巅峰",
                    "修炼途径：天赋、努力、机缘"
            );
        };
    }

    private List<String> generateFactions(String genre) {
        return switch (genre) {
            case "玄幻" -> List.of("正道联盟", "魔道宗门", "中立势力", "隐世家族", "散修联盟");
            case "仙侠" -> List.of("天庭", "魔界", "佛门", "妖域", "散修");
            case "科幻" -> List.of("联邦政府", "星际海盗", "商业联盟", "科研机构", "反抗军");
            default -> List.of("光明阵营", "黑暗势力", "中立组织", "古老家族");
        };
    }

    private List<String> generateWorldRules(String genre) {
        return switch (genre) {
            case "玄幻" -> List.of("弱肉强食，强者为尊", "因果循环，天道轮回", "机缘与危险并存");
            case "仙侠" -> List.of("天道无情，大道至简", "因果报应，天劫考验", "机缘天定，事在人为");
            case "科幻" -> List.of("适者生存，进化永恒", "科技至上，实力为尊", "星际法则，弱肉强食");
            default -> List.of("弱肉强食", "强者为尊", "因果循环");
        };
    }

    private List<String> generateHistory(String genre, String worldName) {
        return List.of(
                "远古时代：天地初开，万物混沌",
                "上古时代：诸神争霸，百族林立",
                "中古时代：人族崛起，建立秩序",
                "近古时代：强者辈出，群雄逐鹿",
                "当今时代：" + worldName + "进入新的纪元"
        );
    }

    private List<Map<String, Object>> generateConflictResults(String type, String charA, String charB) {
        List<Map<String, Object>> list = new ArrayList<>();
        list.add(Map.of(
                "title", "宿命对决",
                "description", charA + "与" + charB + "在秘境中狭路相逢，新仇旧恨一并清算",
                "tension", "高",
                "suggestion", "利用环境元素制造变数，避免平铺直叙"
        ));
        list.add(Map.of(
                "title", "身份揭露",
                "description", charB + "的真实身份被当众揭穿，" + charA + "陷入信任危机",
                "tension", "中",
                "suggestion", "加入第三方势力介入，增加局势复杂性"
        ));
        list.add(Map.of(
                "title", "利益冲突",
                "description", "争夺同一件宝物/机缘，" + charA + "与" + charB + "正面交锋",
                "tension", "中高",
                "suggestion", "设置时间限制或规则约束，增加紧迫感"
        ));
        list.add(Map.of(
                "title", "情感纠葛",
                "description", charB + "与" + charA + "之间存在复杂的情感纠葛，爱恨交织",
                "tension", "中",
                "suggestion", "通过回忆闪回丰富人物关系背景"
        ));
        list.add(Map.of(
                "title", "理念之争",
                "description", charA + "与" + charB + "因理念不同而产生激烈冲突",
                "tension", "中高",
                "suggestion", "双方各有道理，让读者也难以抉择"
        ));
        return list;
    }

    private Map<String, Object> generateQuickCharacter(String keyword, String genre) {
        Map<String, Object> character = new LinkedHashMap<>();
        String name = randomName();
        character.put("name", name);
        character.put("title", keyword + "中的佼佼者");
        character.put("personality", List.of("坚毅", "果断", "重情重义"));
        character.put("background", "出身平凡，凭借不懈努力在" + keyword + "领域闯出一片天地");
        character.put("goal", "成为" + keyword + "领域的巅峰存在");
        character.put("conflict", "面临来自同行的挑战和内心的挣扎");
        character.put("specialAbility", keyword + "天赋异禀，拥有独特的战斗风格");
        character.put("appearance", "身形挺拔，目光如炬，周身散发着" + keyword + "特有的气质");
        return character;
    }

    private List<Map<String, Object>> generatePlotPoints(String genre, String stage, String protagonist) {
        List<Map<String, Object>> list = new ArrayList<>();

        switch (stage) {
            case "开局":
                list.add(Map.of(
                        "title", "平凡开局",
                        "description", protagonist + "原本过着平凡的生活，却因一次意外获得机缘",
                        "type", "起"
                ));
                list.add(Map.of(
                        "title", "危机降临",
                        "description", "突如其来的危机打破了平静，" + protagonist + "被迫踏上征程",
                        "type", "承"
                ));
                list.add(Map.of(
                        "title", "金手指觉醒",
                        "description", protagonist + "在危急时刻觉醒了自己的金手指",
                        "type", "转"
                ));
                break;
            case "发展":
                list.add(Map.of(
                        "title", "初露锋芒",
                        "description", protagonist + "在历练中不断成长，开始崭露头角",
                        "type", "起"
                ));
                list.add(Map.of(
                        "title", "遭遇强敌",
                        "description", "遇到实力远超自己的对手，" + protagonist + "陷入苦战",
                        "type", "承"
                ));
                list.add(Map.of(
                        "title", "突破自我",
                        "description", "在绝境中突破极限，实力大幅提升",
                        "type", "转"
                ));
                break;
            case "高潮":
                list.add(Map.of(
                        "title", "最终对决",
                        "description", protagonist + "与宿敌展开最终决战",
                        "type", "起"
                ));
                list.add(Map.of(
                        "title", "真相大白",
                        "description", "隐藏在幕后的真相终于浮出水面",
                        "type", "承"
                ));
                list.add(Map.of(
                        "title", "命运抉择",
                        "description", protagonist + "面临关乎命运的终极抉择",
                        "type", "转"
                ));
                break;
            default:
                list.add(Map.of(
                        "title", "新的开始",
                        "description", protagonist + "的故事翻开了新的一页",
                        "type", "起"
                ));
                break;
        }

        return list;
    }

    private String randomName() {
        String[] surnames = {"云", "萧", "林", "叶", "楚", "苏", "秦", "白", "陆", "沈", "顾", "江", "谢", "韩", "唐"};
        String[] givenNames = {"尘", "凡", "逸", "轩", "辰", "昊", "羽", "枫", "炎", "寒", "夜", "雪", "风", "雷", "云"};
        Random rand = new Random();
        return surnames[rand.nextInt(surnames.length)] + givenNames[rand.nextInt(givenNames.length)];
    }
}