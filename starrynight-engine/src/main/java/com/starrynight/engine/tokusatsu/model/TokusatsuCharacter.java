package com.starrynight.engine.tokusatsu.model;

import lombok.Data;
import java.util.List;
import java.util.Map;
import java.util.HashMap;
import java.util.ArrayList;

@Data
public class TokusatsuCharacter {
    private String id;
    private String name;
    private String identity;
    private FormTree formTree;
    private Map<String, Device> ownedDevices;
    private LanguageFingerprint languageFingerprint;
    private String currentWorldlineId;

    @Data
    public static class LanguageFingerprint {
        private List<String> transformationAnnounce;
        private List<String> catchphrases;
        private List<String> finisherAnnounce;

        public static LanguageFingerprint createDefault() {
            LanguageFingerprint fp = new LanguageFingerprint();
            fp.setTransformationAnnounce(new ArrayList<>());
            fp.setCatchphrases(new ArrayList<>());
            fp.setFinisherAnnounce(new ArrayList<>());
            return fp;
        }
    }

    public static TokusatsuCharacter create(String id, String name, String identity) {
        TokusatsuCharacter character = new TokusatsuCharacter();
        character.setId(id);
        character.setName(name);
        character.setIdentity(identity);
        character.setFormTree(new FormTree());
        character.setOwnedDevices(new HashMap<>());
        character.setLanguageFingerprint(LanguageFingerprint.createDefault());
        return character;
    }

    public void addDevice(Device device) {
        this.ownedDevices.put(device.getId(), device);
    }

    public Device getDevice(String deviceId) {
        return this.ownedDevices.get(deviceId);
    }

    public boolean hasDevice(String deviceId) {
        return this.ownedDevices.containsKey(deviceId) &&
               this.ownedDevices.get(deviceId).isAvailable();
    }

    public Form getCurrentForm() {
        return this.formTree.getRootForm();
    }

    public List<Device> getAvailableDevices() {
        return this.ownedDevices.values().stream()
                .filter(Device::isAvailable)
                .toList();
    }
}
