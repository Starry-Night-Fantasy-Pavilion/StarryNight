package com.starrynight.engine.consistency;

import lombok.Data;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

@Data
public class ConsistencyReport {
    private boolean passed;
    private List<ConsistencyIssue> issues = new ArrayList<>();
    private int totalIssues;
    private Map<String, List<ConsistencyIssue>> issuesBySeverity = new HashMap<>();
    private Map<String, List<ConsistencyIssue>> issuesByType = new HashMap<>();
    private List<String> suggestions = new ArrayList<>();
}

