package com.starrynight.engine.tokusatsu;

import lombok.Data;
import java.util.List;
import java.util.ArrayList;
import java.util.Map;
import java.util.HashMap;

public class HenshinEffectComposer {

    @Data
    public static class EffectLayer {
        private String layerName;
        private String effectType;
        private String visualDescription;
        private String soundCue;
        private int intensity;
    }

    @Data
    public static class HenshinEffect {
        private String effectName;
        private List<EffectLayer> layers;
        private int totalDuration;
        private String overallAtmosphere;
    }

    private final Map<String, List<EffectLayer>> effectTemplates;

    public HenshinEffectComposer() {
        this.effectTemplates = initializeEffectTemplates();
    }

    private Map<String, List<EffectLayer>> initializeEffectTemplates() {
        Map<String, List<EffectLayer>> templates = new HashMap<>();

        templates.put("classic_flame", List.of(
            createLayer("能量聚集", "light_ball", "红色光球在腰间聚集", "\"能量聚集中\"", 7),
            createLayer("能量爆发", "explosion", "光球猛然爆发", "\"HenShin！\"", 9),
            createLayer("光环扩散", "ring_expand", "火焰光环向四周扩散", "\"燃烧吧！\"", 8),
            createLayer("最终定型", "armor_assemble", "装甲一片片覆盖全身", "\"终结！\"", 10)
        ));

        templates.put("electric_spark", List.of(
            createLayer("电流刺激", "lightning", "电弧在身体周围跳跃", "\"电流激活\"", 6),
            createLayer("电场构建", "field_formation", "电场包裹全身", "\"电场构建中\"", 7),
            createLayer("能量注入", "energy_inject", "闪电状能量注入腰带", "\"能量注入！\"", 8),
            createLayer("电流装甲", "armor_conduction", "电流形成装甲轮廓", "\"放电！\"", 9)
        ));

        templates.put("light_beam", List.of(
            createLayer("光之召唤", "summon_light", "天空降下光柱", "\"响应召唤\"", 8),
            createLayer("光之洗礼", "light_baptism", "光芒笼罩全身", "\"接受洗礼\"", 8),
            createLayer("光之融合", "light_fusion", "身体与光融合", "\"融合完成\"", 9),
            createLayer("光之化身", "light_incarnate", "光芒散去露出真身", "\"光之骑士！\"", 10)
        ));

        templates.put("speed_line", List.of(
            createLayer("加速准备", "speed_charge", "周围开始出现残影", "\"加速准备\"", 5),
            createLayer("速度突破", "speed_up", "残影越来越多", "\"速度突破！\"", 7),
            createLayer("极速形态", "high_speed", "形成速度线风暴", "\"极速形态！\"", 9),
            createLayer("形态完成", "speed_finalize", "速度线汇聚成型", "\"完成！\"", 10)
        ));

        return templates;
    }

    private EffectLayer createLayer(String name, String type, String visual, String sound, int intensity) {
        EffectLayer layer = new EffectLayer();
        layer.setLayerName(name);
        layer.setEffectType(type);
        layer.setVisualDescription(visual);
        layer.setSoundCue(sound);
        layer.setIntensity(intensity);
        return layer;
    }

    public HenshinEffect composeEffect(String effectStyle, int intensityLevel) {
        HenshinEffect effect = new HenshinEffect();
        List<EffectLayer> composedLayers = new ArrayList<>();

        List<EffectLayer> baseLayers = effectTemplates.getOrDefault(effectStyle, effectTemplates.get("classic_flame"));

        for (EffectLayer baseLayer : baseLayers) {
            EffectLayer adjusted = new EffectLayer();
            adjusted.setLayerName(baseLayer.getLayerName());
            adjusted.setEffectType(baseLayer.getEffectType());
            adjusted.setVisualDescription(baseLayer.getVisualDescription());
            adjusted.setSoundCue(baseLayer.getSoundCue());
            adjusted.setIntensity(Math.min(10, baseLayer.getIntensity() * intensityLevel / 10));
            composedLayers.add(adjusted);
        }

        effect.setLayers(composedLayers);
        effect.setEffectName(determineEffectName(effectStyle));
        effect.setTotalDuration(composedLayers.stream().mapToInt(EffectLayer::getIntensity).sum());
        effect.setOverallAtmosphere(determineAtmosphere(effectStyle));

        return effect;
    }

    private String determineEffectName(String effectStyle) {
        return switch (effectStyle) {
            case "classic_flame" -> "烈焰变身";
            case "electric_spark" -> "闪电变身";
            case "light_beam" -> "光之变身";
            case "speed_line" -> "极速变身";
            default -> "标准变身";
        };
    }

    private String determineAtmosphere(String effectStyle) {
        return switch (effectStyle) {
            case "classic_flame" -> "热烈、燃烧、热血";
            case "electric_spark" -> "震撼、电流、能量";
            case "light_beam" -> "神圣、光辉、庄严";
            case "speed_line" -> "极速、动感、飘逸";
            default -> "标准、平衡";
        };
    }

    public String generateEffectNarrative(String riderName, HenshinEffect effect) {
        StringBuilder sb = new StringBuilder();
        sb.append("【").append(effect.getEffectName()).append("】\n");
        sb.append("风格：").append(effect.getOverallAtmosphere()).append("\n");
        sb.append("总时长：").append(effect.getTotalDuration()).append("秒\n\n");

        for (EffectLayer layer : effect.getLayers()) {
            sb.append("━━━━━━━━━━━\n");
            sb.append("【").append(layer.getLayerName()).append("】\n");
            sb.append("视觉：").append(layer.getVisualDescription()).append("\n");
            sb.append("音效：").append(layer.getSoundCue()).append("\n");
            sb.append("强度：").append("★".repeat(layer.getIntensity())).append("☆".repeat(10 - layer.getIntensity())).append("\n\n");
        }

        sb.append("━━━━━━━━━━━\n");
        sb.append("\n").append(riderName).append("完成了变身！");
        sb.append("假面骑士的力量，现在觉醒！");

        return sb.toString();
    }

    public String generateEffectPrompt(String riderName, String effectStyle) {
        StringBuilder sb = new StringBuilder();
        sb.append("请描写").append(riderName).append("的变身特效场景：\n\n");
        sb.append("特效风格：").append(determineEffectName(effectStyle)).append("\n");
        sb.append("整体氛围：").append(determineAtmosphere(effectStyle)).append("\n\n");
        sb.append("要求：\n");
        sb.append("- 描写光影效果的变化过程\n");
        sb.append("- 加入音效描写增强画面感\n");
        sb.append("- 体现变身的仪式感和力量感\n");
        sb.append("- 文字要有节奏感和冲击力\n");
        sb.append("- 控制在300-400字\n");
        return sb.toString();
    }

    public List<String> getAvailableEffectStyles() {
        return new ArrayList<>(effectTemplates.keySet());
    }
}
