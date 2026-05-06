package com.starrynight.engine.tokusatsu.model;

import lombok.Data;
import java.util.Map;
import java.util.HashMap;
import java.util.List;
import java.util.ArrayList;

@Data
public class FormTree {
    private String rootFormId;
    private Map<String, Form> forms;

    public FormTree() {
        this.forms = new HashMap<>();
    }

    public FormTree(String rootFormId) {
        this.rootFormId = rootFormId;
        this.forms = new HashMap<>();
    }

    public void addForm(Form form) {
        this.forms.put(form.getId(), form);
    }

    public Form getForm(String formId) {
        return this.forms.get(formId);
    }

    public Form getRootForm() {
        return this.forms.get(this.rootFormId);
    }

    public List<Form> getChildForms(String formId) {
        Form parent = this.forms.get(formId);
        if (parent == null || parent.getChildFormIds() == null) {
            return new ArrayList<>();
        }

        List<Form> children = new ArrayList<>();
        for (String childId : parent.getChildFormIds()) {
            Form child = this.forms.get(childId);
            if (child != null) {
                children.add(child);
            }
        }
        return children;
    }

    public List<Form> getAllForms() {
        return new ArrayList<>(this.forms.values());
    }

    public boolean hasForm(String formId) {
        return this.forms.containsKey(formId);
    }

    public List<String> getEvolutionPath(String fromFormId, String toFormId) {
        List<String> path = new ArrayList<>();
        path.add(fromFormId);

        if (fromFormId.equals(toFormId)) {
            return path;
        }

        Form current = this.forms.get(fromFormId);
        while (current != null && current.getChildFormIds() != null) {
            for (String childId : current.getChildFormIds()) {
                path.add(childId);
                if (childId.equals(toFormId)) {
                    return path;
                }
                Form child = this.forms.get(childId);
                if (child != null && this.findPathRecursive(child, toFormId, path)) {
                    return path;
                }
                path.remove(path.size() - 1);
            }
            break;
        }

        return path.isEmpty() ? null : path;
    }

    private boolean findPathRecursive(Form current, String targetId, List<String> path) {
        if (current.getChildFormIds() == null) {
            return false;
        }

        for (String childId : current.getChildFormIds()) {
            path.add(childId);
            if (childId.equals(targetId)) {
                return true;
            }
            Form child = this.forms.get(childId);
            if (child != null && findPathRecursive(child, targetId, path)) {
                return true;
            }
            path.remove(path.size() - 1);
        }
        return false;
    }

    public static FormTree createKamenRiderTemplate() {
        FormTree tree = new FormTree("base");

        Form baseForm = Form.createBaseForm("base", "基础形态");
        baseForm.setMinEnergyRequired(0);
        tree.addForm(baseForm);

        Form speedForm = Form.createBaseForm("speed", "速度形态");
        speedForm.getAbilityVector().setSpeed(80);
        speedForm.getAbilityVector().setPower(40);
        speedForm.addEvolution("final", "速度的极限突破", "speed_belt");
        tree.addForm(speedForm);

        Form powerForm = Form.createBaseForm("power", "力量形态");
        powerForm.getAbilityVector().setPower(80);
        powerForm.getAbilityVector().setSpeed(40);
        powerForm.addEvolution("final", "力量的极限突破", "power_belt");
        tree.addForm(powerForm);

        Form finalForm = Form.createBaseForm("final", "终极形态");
        finalForm.getAbilityVector().setPower(90);
        finalForm.getAbilityVector().setSpeed(90);
        finalForm.getAbilityVector().setDefense(90);
        finalForm.getAbilityVector().setSpecial(90);
        finalForm.setMinEnergyRequired(100);
        tree.addForm(finalForm);

        baseForm.getChildFormIds().add("speed");
        baseForm.getChildFormIds().add("power");

        return tree;
    }
}
