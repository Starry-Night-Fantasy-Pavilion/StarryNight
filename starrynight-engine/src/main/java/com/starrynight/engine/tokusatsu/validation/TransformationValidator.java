package com.starrynight.engine.tokusatsu.validation;

import com.starrynight.engine.tokusatsu.model.Form;
import com.starrynight.engine.tokusatsu.model.TokusatsuCharacter;
import lombok.Data;

@Data
public class TransformationValidator {

    @Data
    public static class ValidationResult {
        private boolean valid;
        private String error;
        private ValidationType type;
        private String suggestion;

        public static ValidationResult success() {
            ValidationResult result = new ValidationResult();
            result.setValid(true);
            return result;
        }

        public static ValidationResult failure(String error, ValidationType type) {
            ValidationResult result = new ValidationResult();
            result.setValid(false);
            result.setError(error);
            result.setType(type);
            return result;
        }

        public static ValidationResult failure(String error, ValidationType type, String suggestion) {
            ValidationResult result = failure(error, type);
            result.setSuggestion(suggestion);
            return result;
        }
    }

    public enum ValidationType {
        MISSING_DEVICE,
        INSUFFICIENT_ENERGY,
        EMOTIONAL_MISMATCH,
        DEVICE_NOT_EVOLVED,
        TRANSFORMATION_TIMEOUT,
        WORLDLINE_CONFLICT
    }

    public ValidationResult validate(
            TokusatsuCharacter character,
            String targetFormId,
            SceneContext context) {

        Form targetForm = character.getFormTree().getForm(targetFormId);
        if (targetForm == null) {
            return ValidationResult.failure(
                    "未知形态: " + targetFormId,
                    ValidationType.MISSING_DEVICE
            );
        }

        if (!validateDeviceRequired(character, targetForm)) {
            return ValidationResult.failure(
                    "缺少必要道具: " + targetForm.getEvolutionConditions().getDeviceRequired(),
                    ValidationType.MISSING_DEVICE,
                    "可尝试获取该道具或使用基础形态"
            );
        }

        if (!validateEnergyLevel(context, targetForm)) {
            return ValidationResult.failure(
                    "能量不足: 需要" + targetForm.getMinEnergyRequired() + "，当前" + context.getCurrentEnergy(),
                    ValidationType.INSUFFICIENT_ENERGY,
                    "建议先进行充能或等待能量恢复"
            );
        }

        if (!validateEmotionalCondition(context, targetForm)) {
            return ValidationResult.failure(
                    "情绪条件未满足: 需要\"" + targetForm.getEvolutionConditions().getEmotionalTrigger() + "\"",
                    ValidationType.EMOTIONAL_MISMATCH,
                    "可标记为\"唯心突破\"事件触发进化"
            );
        }

        return ValidationResult.success();
    }

    private boolean validateDeviceRequired(TokusatsuCharacter character, Form targetForm) {
        if (targetForm.getEvolutionConditions() == null ||
            targetForm.getEvolutionConditions().getDeviceRequired() == null) {
            return true;
        }

        String deviceId = targetForm.getEvolutionConditions().getDeviceRequired();
        return character.hasDevice(deviceId);
    }

    private boolean validateEnergyLevel(SceneContext context, Form targetForm) {
        return context.getCurrentEnergy() >= targetForm.getMinEnergyRequired();
    }

    private boolean validateEmotionalCondition(SceneContext context, Form targetForm) {
        if (targetForm.getEvolutionConditions() == null ||
            targetForm.getEvolutionConditions().getEmotionalTrigger() == null) {
            return true;
        }

        String requiredEmotion = targetForm.getEvolutionConditions().getEmotionalTrigger();
        return checkEmotionalCondition(context.getCurrentEmotion(), requiredEmotion);
    }

    private boolean checkEmotionalCondition(String currentEmotion, String requiredEmotion) {
        if (currentEmotion == null || requiredEmotion == null) {
            return true;
        }

        return currentEmotion.contains(requiredEmotion) ||
               isCompatibleEmotion(currentEmotion, requiredEmotion);
    }

    private boolean isCompatibleEmotion(String current, String required) {
        if ("不再迷茫".equals(required)) {
            return current.contains("坚定") || current.contains("决心") || current.contains("觉醒");
        }
        if ("同伴羁绊".equals(required)) {
            return current.contains("羁绊") || current.contains("友情") || current.contains("信任");
        }
        if ("愤怒突破".equals(required)) {
            return current.contains("愤怒") || current.contains("不甘") || current.contains("斗志");
        }
        if ("牺牲觉悟".equals(required)) {
            return current.contains("觉悟") || current.contains("牺牲") || current.contains("决意");
        }
        return false;
    }

    @Data
    public static class SceneContext {
        private int currentEnergy;
        private String currentEmotion;
        private String currentWorldlineId;
        private int transformationTimeRemaining;
        private boolean isInBattle;

        public static SceneContext create(int energy, String emotion) {
            SceneContext context = new SceneContext();
            context.setCurrentEnergy(energy);
            context.setCurrentEmotion(emotion);
            context.setTransformationTimeRemaining(60);
            context.setInBattle(false);
            return context;
        }
    }
}
