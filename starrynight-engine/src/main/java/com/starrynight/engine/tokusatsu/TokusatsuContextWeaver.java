package com.starrynight.engine.tokusatsu;

import com.starrynight.engine.prompt.CPromptContext;
import com.starrynight.engine.retrieval.HybridRetriever;
import com.starrynight.engine.token.ContextTruncator;
import com.starrynight.engine.vector.VectorEntry;
import com.starrynight.engine.vector.search.VectorSearchRequest;
import com.starrynight.engine.tokusatsu.model.TokusatsuCharacter;
import com.starrynight.engine.tokusatsu.validation.TransformationValidator;
import com.starrynight.engine.workflow.ContextWeaver;
import com.starrynight.engine.workflow.WritingIntent;
import lombok.Data;

import java.util.ArrayList;
import java.util.List;

public class TokusatsuContextWeaver extends ContextWeaver {

    private final TokusatsuCharacterManager characterManager;
    private final TransformationValidator validator;

    public TokusatsuContextWeaver(HybridRetriever hybridRetriever,
                                   ContextTruncator truncator,
                                   TokusatsuCharacterManager characterManager,
                                   TransformationValidator validator) {
        super(hybridRetriever, truncator);
        this.characterManager = characterManager;
        this.validator = validator;
    }

    @Override
    public CPromptContext weave(WritingIntent intent) {
        CPromptContext baseContext = super.weave(intent);

        if (isTransformationAction(intent)) {
            TransformationContext transformationContext = buildTransformationContext(intent);
            baseContext.getConstraints().addAll(0, transformationContext.getConstraints());
        }

        if (isBattleAction(intent)) {
            BattleContext battleContext = buildBattleContext(intent);
            baseContext.getConstraints().addAll(battleContext.getConstraints());
        }

        return baseContext;
    }

    public boolean isTransformationAction(WritingIntent intent) {
        if (intent == null || intent.getCoreEvent() == null) {
            return false;
        }
        String event = intent.getCoreEvent().toLowerCase();
        return event.contains("变身") ||
               event.contains("形态") ||
               event.contains("进化") ||
               event.contains("henshin") ||
               event.contains("transform");
    }

    public boolean isBattleAction(WritingIntent intent) {
        if (intent == null || intent.getCoreEvent() == null) {
            return false;
        }
        String event = intent.getCoreEvent().toLowerCase();
        return event.contains("战斗") ||
               event.contains("攻击") ||
               event.contains("必杀") ||
               event.contains("battle") ||
               event.contains("fight");
    }

    private TransformationContext buildTransformationContext(WritingIntent intent) {
        TransformationContext ctx = new TransformationContext();

        if (intent.getPresentCharacters() != null && !intent.getPresentCharacters().isEmpty()) {
            String characterId = intent.getPresentCharacters().get(0).getCharacterId();
            TokusatsuCharacter character = characterManager.getCharacter(characterId);

            if (character != null) {
                ctx.getConstraints().addAll(buildCharacterContext(character));
                ctx.setCharacter(character);
            }
        }

        return ctx;
    }

    private BattleContext buildBattleContext(WritingIntent intent) {
        BattleContext ctx = new BattleContext();

        ctx.getConstraints().addAll(buildEnemyContext(intent));
        ctx.getConstraints().addAll(buildBattleRulesContext(intent));

        return ctx;
    }

    private List<VectorEntry> buildCharacterContext(TokusatsuCharacter character) {
        List<VectorEntry> constraints = new ArrayList<>();

        constraints.add(createConstraint("character_baseline",
                character.getName() + "的基础信息：" + character.getIdentity()));

        for (String deviceId : character.getOwnedDevices().keySet()) {
            constraints.add(createConstraint("owned_device",
                    "持有道具：" + deviceId));
        }

        if (character.getFormTree() != null && character.getFormTree().getRootForm() != null) {
            constraints.add(createConstraint("current_form",
                    "当前形态：" + character.getFormTree().getRootForm().getName()));
        }

        return constraints;
    }

    private List<VectorEntry> buildEnemyContext(WritingIntent intent) {
        List<VectorEntry> constraints = new ArrayList<>();

        constraints.add(createConstraint("battle_start",
                "战斗场景触发，需要调用战斗规则"));

        return constraints;
    }

    private List<VectorEntry> buildBattleRulesContext(WritingIntent intent) {
        List<VectorEntry> constraints = new ArrayList<>();

        constraints.add(createConstraint("battle_rules",
                "战斗规则：遵守变身时间限制，注意能量消耗"));

        return constraints;
    }

    private VectorEntry createConstraint(String type, String content) {
        VectorEntry entry = new VectorEntry();
        entry.setChunk(content);
        return entry;
    }

    @Data
    public static class TransformationContext {
        private List<VectorEntry> constraints = new ArrayList<>();
        private TokusatsuCharacter character;
        private String targetFormId;
        private TransformationValidator.ValidationResult validationResult;
    }

    @Data
    public static class BattleContext {
        private List<VectorEntry> constraints = new ArrayList<>();
        private String enemyName;
        private int enemyPower;
    }
}
