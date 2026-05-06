package com.starrynight.engine.vector;

import lombok.Data;

import java.util.HashMap;
import java.util.Map;

@Data
public class VectorEntry {
    private String id;
    private String chunk;
    private float[] denseVector;
    private Map<String, Float> sparseVector;
    private VectorMetadata metadata;

    public Map<String, Float> getSparseVectorOrEmpty() {
        return sparseVector == null ? new HashMap<>() : sparseVector;
    }
}

