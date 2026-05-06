package com.starrynight.engine.prompt;

import com.starrynight.engine.vector.VectorEntry;
import lombok.Data;

import java.util.ArrayList;
import java.util.List;

@Data
public class CPromptContext {
    private List<VectorEntry> constraints = new ArrayList<>();
    private String intentSummary;
    private int totalTokens = 0;
    private int maxTokens = 4096;
    private boolean truncated = false;
}

