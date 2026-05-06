package com.starrynight.engine.tokusatsu;

import com.starrynight.engine.tokusatsu.model.Device;
import com.starrynight.engine.tokusatsu.model.Form;
import com.starrynight.engine.tokusatsu.model.TokusatsuCharacter;
import lombok.Data;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.HashMap;

public class HeritageTracker {

    private final TokusatsuCharacterManager characterManager;
    private final Map<String, HeritageState> heritageStates;

    public HeritageTracker(TokusatsuCharacterManager characterManager) {
        this.characterManager = characterManager;
        this.heritageStates = new HashMap<>();
    }

    public HeritageUpdate updateHeritage(ChapterUpdate chapter) {
        HeritageUpdate update = new HeritageUpdate();

        for (ItemEvent event : chapter.getItemEvents()) {
            HeritageUpdate.DeviceChange change = new HeritageUpdate.DeviceChange();
            change.setDeviceId(event.getDeviceId());
            change.setChapterNo(chapter.getChapterNo());

            if (event.getType() == ItemEventType.OBTAINED) {
                change.setChange("obtained");
                update.getDeviceChanges().add(change);
                applyDeviceObtained(chapter.getCharacterId(), event.getDeviceId());
            } else if (event.getType() == ItemEventType.DESTROYED) {
                change.setChange("destroyed");
                change.setMajorEvent(true);
                update.getDeviceChanges().add(change);
                applyDeviceDestroyed(chapter.getCharacterId(), event.getDeviceId());
            }
        }

        for (FormUnlock form : chapter.getNewForms()) {
            HeritageUpdate.FormUnlock unlock = new HeritageUpdate.FormUnlock();
            unlock.setCharacterId(form.getCharacterId());
            unlock.setFormId(form.getFormId());
            unlock.setUnlockCondition(form.getCondition());
            unlock.setChapterNo(chapter.getChapterNo());
            update.getFormUnlocks().add(unlock);
            applyFormUnlocked(form.getCharacterId(), form.getFormId());
        }

        for (VillainStatusUpdate status : chapter.getVillainStatusChanges()) {
            update.getVillainStatusChanges().add(status);
            if (status.getNewStatus() == VillainStatus.DEAD) {
                applyOrganizationStrengthUpdate(status);
            }
        }

        return update;
    }

    private void applyDeviceObtained(String characterId, String deviceId) {
        TokusatsuCharacter character = characterManager.getCharacter(characterId);
        if (character != null) {
            Device device = new Device();
            device.setId(deviceId);
            device.setStatus(Device.DeviceStatus.OWNED);
            character.addDevice(device);
        }
    }

    private void applyDeviceDestroyed(String characterId, String deviceId) {
        TokusatsuCharacter character = characterManager.getCharacter(characterId);
        if (character != null) {
            Device device = character.getDevice(deviceId);
            if (device != null) {
                device.setStatus(Device.DeviceStatus.DESTROYED);
            }
        }
    }

    private void applyFormUnlocked(String characterId, String formId) {
        TokusatsuCharacter character = characterManager.getCharacter(characterId);
        if (character != null && character.getFormTree().hasForm(formId)) {
            Form form = character.getFormTree().getForm(formId);
            List<String> childIds = form.getChildFormIds();
            if (childIds == null) {
                childIds = new ArrayList<>();
                form.setChildFormIds(childIds);
            }
        }
    }

    private void applyOrganizationStrengthUpdate(VillainStatusUpdate status) {
    }

    public HeritageState getHeritageState(String storyId) {
        return heritageStates.get(storyId);
    }

    public void initializeHeritageState(String storyId, String mainCharacterId) {
        HeritageState state = new HeritageState();
        state.setStoryId(storyId);
        state.setMainCharacterId(mainCharacterId);
        heritageStates.put(storyId, state);
    }

    @Data
    public static class HeritageUpdate {
        private List<DeviceChange> deviceChanges = new ArrayList<>();
        private List<FormUnlock> formUnlocks = new ArrayList<>();
        private List<VillainStatusUpdate> villainStatusChanges = new ArrayList<>();

        @Data
        public static class DeviceChange {
            private String deviceId;
            private String change;
            private int chapterNo;
            private boolean isMajorEvent;
        }

        @Data
        public static class FormUnlock {
            private String characterId;
            private String formId;
            private String unlockCondition;
            private int chapterNo;
        }
    }

    @Data
    public static class ChapterUpdate {
        private int chapterNo;
        private String characterId;
        private List<ItemEvent> itemEvents = new ArrayList<>();
        private List<FormUnlock> newForms = new ArrayList<>();
        private List<VillainStatusUpdate> villainStatusChanges = new ArrayList<>();
    }

    @Data
    public static class ItemEvent {
        private String deviceId;
        private ItemEventType type;
    }

    public enum ItemEventType {
        OBTAINED, DESTROYED, LOST, EVOLVED
    }

    @Data
    public static class FormUnlock {
        private String characterId;
        private String formId;
        private String condition;
    }

    @Data
    public static class VillainStatusUpdate {
        private String villainId;
        private String villainName;
        private VillainStatus newStatus;
    }

    public enum VillainStatus {
        ALIVE, DEAD, REVIVED, CLONED
    }

    @Data
    public static class HeritageState {
        private String storyId;
        private String mainCharacterId;
        private List<String> unlockedForms = new ArrayList<>();
        private Map<String, Device.DeviceStatus> deviceStates = new HashMap<>();
        private int totalMainPlotProgress;
    }
}
