package com.starrynight.engine.tokusatsu;

import com.starrynight.engine.tokusatsu.model.TokusatsuCharacter;
import com.starrynight.engine.tokusatsu.model.Device;
import com.starrynight.engine.tokusatsu.model.Form;
import lombok.Data;

import java.util.Map;
import java.util.HashMap;

public class TokusatsuCharacterManager {

    private Map<String, TokusatsuCharacter> characters;

    public TokusatsuCharacterManager() {
        this.characters = new HashMap<>();
    }

    public void registerCharacter(TokusatsuCharacter character) {
        this.characters.put(character.getId(), character);
    }

    public TokusatsuCharacter getCharacter(String characterId) {
        return this.characters.get(characterId);
    }

    public boolean hasCharacter(String characterId) {
        return this.characters.containsKey(characterId);
    }

    public void updateCharacterForm(String characterId, String formId) {
        TokusatsuCharacter character = this.characters.get(characterId);
        if (character != null && character.getFormTree().hasForm(formId)) {
            Form form = character.getFormTree().getForm(formId);
            form.setParentFormId(formId);
        }
    }

    public void addDeviceToCharacter(String characterId, Device device) {
        TokusatsuCharacter character = this.characters.get(characterId);
        if (character != null) {
            character.addDevice(device);
        }
    }

    public void removeDeviceFromCharacter(String characterId, String deviceId) {
        TokusatsuCharacter character = this.characters.get(characterId);
        if (character != null) {
            Device device = character.getDevice(deviceId);
            if (device != null) {
                device.setStatus(Device.DeviceStatus.LOST);
            }
        }
    }

    public static TokusatsuCharacterManager createWithSampleCharacters() {
        TokusatsuCharacterManager manager = new TokusatsuCharacterManager();

        TokusatsuCharacter rider = TokusatsuCharacter.create("rider_01", "假面骑士", "主角骑士");
        manager.registerCharacter(rider);

        return manager;
    }
}
