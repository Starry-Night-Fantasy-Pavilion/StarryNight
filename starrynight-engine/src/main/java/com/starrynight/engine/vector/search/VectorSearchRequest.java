package com.starrynight.engine.vector.search;

import com.starrynight.engine.vector.EntryType;
import lombok.Data;

import java.util.HashMap;
import java.util.Map;

@Data
public class VectorSearchRequest {
    private String collection;
    private String queryText;
    private float[] queryVector;
    private int limit = 10;
    private Map<String, String> metadataEquals = new HashMap<>();
    private EntryType type;
}

