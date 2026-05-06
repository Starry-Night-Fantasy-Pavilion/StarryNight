package com.starrynight.engine.vector.search;

import com.starrynight.engine.vector.VectorEntry;
import lombok.Data;

@Data
public class VectorSearchResult {
    private VectorEntry entry;
    private double score;
    private double denseScore;
    private double sparseScore;
}

