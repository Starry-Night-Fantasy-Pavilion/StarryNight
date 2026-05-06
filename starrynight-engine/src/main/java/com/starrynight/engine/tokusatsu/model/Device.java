package com.starrynight.engine.tokusatsu.model;

import lombok.Data;

@Data
public class Device {
    private String id;
    private String name;
    private DeviceType type;
    private DeviceStatus status;
    private String evolvedInto;
    private String evolutionCondition;
    private String description;

    public enum DeviceType {
        BELT, BUCKLE, EYECON, BOTTLE, CORE_IDOL, DRIVER, CARD, RING
    }

    public enum DeviceStatus {
        OWNED, DESTROYED, EVOLVED, LOST
    }

    public static Device create(String id, String name, DeviceType type) {
        Device device = new Device();
        device.setId(id);
        device.setName(name);
        device.setType(type);
        device.setStatus(DeviceStatus.OWNED);
        return device;
    }

    public boolean canEvolve() {
        return this.evolvedInto != null && this.status == DeviceStatus.OWNED;
    }

    public boolean isAvailable() {
        return this.status == DeviceStatus.OWNED;
    }
}
