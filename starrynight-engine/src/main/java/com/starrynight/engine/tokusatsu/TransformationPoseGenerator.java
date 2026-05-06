package com.starrynight.engine.tokusatsu;

import lombok.Data;
import java.util.List;
import java.util.ArrayList;
import java.util.Map;
import java.util.HashMap;

public class TransformationPoseGenerator {

    @Data
    public static class TransformationPose {
        private String poseName;
        private String description;
        private String keyAction;
        private String effect;
        private int dynamicLevel;
    }

    @Data
    public static class PoseSequence {
        private List<TransformationPose> poses;
        private int totalDuration;
        private String style;
    }

    private final Map<String, List<TransformationPose>> poseLibrary;

    public TransformationPoseGenerator() {
        this.poseLibrary = initializePoseLibrary();
    }

    private Map<String, List<TransformationPose>> initializePoseLibrary() {
        Map<String, List<TransformationPose>> library = new HashMap<>();

        library.put("classic", List.of(
            createPose("高举过头", "单臂伸展指向天空", "举起变身器", "金色光芒", 10),
            createPose("腰部旋转", "腰部扭转90度", "旋转变身器", "能量光环", 8),
            createPose("踢腿定型", "侧踢腿定住", "踢出骑士踢", "气流环绕", 9),
            createPose("落地pose", "双脚站稳双臂张开", "最终落地", "光芒四射", 10)
        ));

        library.put("dramatic", List.of(
            createPose("仰望天空", "抬头凝视上方", "接收光芒", "天降光柱", 9),
            createPose("闭目沉思", "双眼紧闭集中精神", "内心觉醒", "气场爆发", 8),
            createPose("双手合十", "双掌合拢于胸前", "能量聚集", "闪电纹路", 10),
            createPose("冲击展开", "双手猛然展开", "力量释放", "冲击波扩散", 10)
        ));

        library.put("speed", List.of(
            createPose("快速抽出", "快速拔出腰带", "瞬间抽出", "残影效果", 9),
            createPose("旋转加速", "身体高速旋转", "加速变身", "风卷残云", 8),
            createPose("冲刺pose", "向前冲刺滑步", "滑行制动", "地面摩擦", 7),
            createPose("急停定型", "突然停止", "急停pose", "尘土飞扬", 8)
        ));

        return library;
    }

    private TransformationPose createPose(String name, String desc, String action, String effect, int level) {
        TransformationPose pose = new TransformationPose();
        pose.setPoseName(name);
        pose.setDescription(desc);
        pose.setKeyAction(action);
        pose.setEffect(effect);
        pose.setDynamicLevel(level);
        return pose;
    }

    public PoseSequence generatePoseSequence(String style, int intensity) {
        PoseSequence sequence = new PoseSequence();
        List<TransformationPose> poses = new ArrayList<>();

        List<TransformationPose> basePoses = poseLibrary.getOrDefault(style, poseLibrary.get("classic"));

        for (TransformationPose pose : basePoses) {
            TransformationPose adjusted = new TransformationPose();
            adjusted.setPoseName(pose.getPoseName());
            adjusted.setDescription(pose.getDescription());
            adjusted.setKeyAction(pose.getKeyAction());
            adjusted.setEffect(pose.getEffect());
            adjusted.setDynamicLevel(Math.min(10, pose.getDynamicLevel() * intensity / 10));
            poses.add(adjusted);
        }

        sequence.setPoses(poses);
        sequence.setTotalDuration(poses.stream().mapToInt(TransformationPose::getDynamicLevel).sum());
        sequence.setStyle(style);

        return sequence;
    }

    public String generatePoseDescription(TransformationPose pose) {
        return String.format("%s：%s。关键动作：%s。视觉效果：%s。动感等级：%d/10",
                pose.getPoseName(),
                pose.getDescription(),
                pose.getKeyAction(),
                pose.getEffect(),
                pose.getDynamicLevel());
    }

    public String generateTransformationNarrative(String riderName, String formName, PoseSequence sequence) {
        StringBuilder sb = new StringBuilder();
        sb.append(riderName).append("深吸一口气，将").append(formName).append("腰带置于腰间。\n\n");

        for (int i = 0; i < sequence.getPoses().size(); i++) {
            TransformationPose pose = sequence.getPoses().get(i);
            sb.append("【").append(pose.getPoseName()).append("】\n");
            sb.append(pose.getDescription()).append("。\n");
            sb.append("\"").append(pose.getKeyAction()).append("！\"\n");
            sb.append("——").append(pose.getEffect()).append("\n\n");
        }

        sb.append("光芒消散，").append(riderName).append("以假面骑士").append(formName).append("的姿态矗立于战场之上！");
        return sb.toString();
    }

    public List<String> getAvailableStyles() {
        return new ArrayList<>(poseLibrary.keySet());
    }
}
