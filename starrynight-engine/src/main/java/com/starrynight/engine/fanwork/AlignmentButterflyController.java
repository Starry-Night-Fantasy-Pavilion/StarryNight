package com.starrynight.engine.fanwork;

import lombok.Data;
import java.util.ArrayList;
import java.util.List;

@Data
public class AlignmentButterflyController {

    private final ButterflyEffectTracker butterflyTracker;
    private final PlotAlignmentService alignmentService;

    public AlignmentButterflyController(ButterflyEffectTracker butterflyTracker, PlotAlignmentService alignmentService) {
        this.butterflyTracker = butterflyTracker;
        this.alignmentService = alignmentService;
    }

    public ConstraintSet processConstraints(WritingRequest request) {
        ConstraintSet constraints = new ConstraintSet();
        constraints.setAlignmentConstraints(new ArrayList<>());
        constraints.setButterflyConstraints(new ArrayList<>());
        constraints.setConflictWarnings(new ArrayList<>());

        if (request.isUseAlignment()) {
            AlignmentResult alignment = getAlignmentConstraints(request);
            constraints.setAlignmentConstraints(alignment.getConstraints());
        }

        if (request.isTimeTraveler()) {
            ButterflyResult butterfly = getButterflyConstraints(request);
            constraints.setButterflyConstraints(butterfly.getConstraints());
            constraints.setConflictWarnings(butterfly.getWarnings());
        }

        List<ConstraintConflict> conflicts = detectConstraintConflicts(constraints);

        if (!conflicts.isEmpty()) {
            constraints.setConflicts(conflicts);
            constraints.setRequiresUserDecision(true);
        }

        return constraints;
    }

    private AlignmentResult getAlignmentConstraints(WritingRequest request) {
        AlignmentResult result = new AlignmentResult();
        result.setConstraints(new ArrayList<>());
        result.setWarnings(new ArrayList<>());

        List<CanonPlotAnchor> anchors = alignmentService.getAnchorsForChapter(request.getChapterNo());
        for (CanonPlotAnchor anchor : anchors) {
            AlignmentConstraint constraint = new AlignmentConstraint();
            constraint.setId(anchor.getId());
            constraint.setDescription(anchor.getTitle() + ": " + anchor.getChapterRange());
            constraint.setType(anchor.getAlignmentType().name());
            constraint.setPriority("high");
            result.getConstraints().add(constraint);
        }

        return result;
    }

    private ButterflyResult getButterflyConstraints(WritingRequest request) {
        ButterflyResult result = new ButterflyResult();
        result.setConstraints(new ArrayList<>());
        result.setWarnings(new ArrayList<>());

        ButterflyEffectTracker.TravelerAction action = new ButterflyEffectTracker.TravelerAction();
        action.setId("action_" + System.currentTimeMillis());
        action.setChapterNo(request.getChapterNo());
        action.setDescription(request.getDescription());
        action.setType(ButterflyEffectTracker.TravelerAction.ActionType.TRAVELER_ACTION);

        List<ButterflyEffectTracker.EffectPropagation> effects = butterflyTracker.recordTravelerAction(action);

        for (ButterflyEffectTracker.EffectPropagation effect : effects) {
            ButterflyConstraint constraint = new ButterflyConstraint();
            constraint.setId(effect.getId());
            constraint.setDescription(effect.getAffectedEntity().getDescription());
            constraint.setDeviationLevel(effect.getDeviationLevel());
            result.getConstraints().add(constraint);
        }

        return result;
    }

    private List<ConstraintConflict> detectConstraintConflicts(ConstraintSet constraints) {
        List<ConstraintConflict> conflicts = new ArrayList<>();

        for (AlignmentConstraint alignConstraint : constraints.getAlignmentConstraints()) {
            for (ButterflyConstraint butterflyConstraint : constraints.getButterflyConstraints()) {
                if (isConflicting(alignConstraint, butterflyConstraint)) {
                    ConstraintConflict conflict = new ConstraintConflict();
                    conflict.setConstraintA(alignConstraint.getId());
                    conflict.setConstraintB(butterflyConstraint.getId());
                    conflict.setDescription("剧情对齐约束与蝴蝶效应约束冲突");
                    conflicts.add(conflict);
                }
            }
        }

        return conflicts;
    }

    private boolean isConflicting(AlignmentConstraint a, ButterflyConstraint b) {
        return false;
    }

    public String buildCPrompt(ConstraintSet constraints) {
        StringBuilder prompt = new StringBuilder();

        if (!constraints.getAlignmentConstraints().isEmpty()) {
            prompt.append("\n【剧情对齐约束】\n");
            for (AlignmentConstraint c : constraints.getAlignmentConstraints()) {
                prompt.append("- ").append(c.getDescription()).append("\n");
            }
        }

        if (!constraints.getButterflyConstraints().isEmpty()) {
            prompt.append("\n【蝴蝶效应约束】\n");
            for (ButterflyConstraint c : constraints.getButterflyConstraints()) {
                prompt.append("- ").append(c.getDescription()).append("\n");
            }
        }

        if (!constraints.getConflictWarnings().isEmpty()) {
            prompt.append("\n【⚠️ 冲突警告】\n");
            for (String w : constraints.getConflictWarnings()) {
                prompt.append("- ").append(w).append("\n");
            }
        }

        return prompt.toString();
    }

    @Data
    public static class WritingRequest {
        private int chapterNo;
        private String description;
        private boolean useAlignment;
        private boolean timeTraveler;
        private List<String> characterIds;
        private String location;
    }

    @Data
    public static class ConstraintSet {
        private List<AlignmentConstraint> alignmentConstraints;
        private List<ButterflyConstraint> butterflyConstraints;
        private List<String> conflictWarnings;
        private List<ConstraintConflict> conflicts;
        private boolean requiresUserDecision;
    }

    @Data
    public static class AlignmentConstraint {
        private String id;
        private String description;
        private String type;
        private String priority;
    }

    @Data
    public static class ButterflyConstraint {
        private String id;
        private String description;
        private double deviationLevel;
    }

    @Data
    public static class ConstraintConflict {
        private String constraintA;
        private String constraintB;
        private String description;
    }

    @Data
    public static class AlignmentResult {
        private List<AlignmentConstraint> constraints;
        private List<String> warnings;
    }

    @Data
    public static class ButterflyResult {
        private List<ButterflyConstraint> constraints;
        private List<String> warnings;
    }

    public interface PlotAlignmentService {
        List<CanonPlotAnchor> getAnchorsForChapter(int chapterNo);
    }
}
