package com.starrynight.engine.tokusatsu;

import lombok.Data;

@Data
public class TokusatsuScenarioContext {
    private Long novelId;
    private String seriesTitle;
    private String seriesType;
    private String teamComposition;
    private String transformationStyle;
    private String primaryAntagonist;
    private String[] allyKamenRiders;
    private String[] monsterTypes;
    private String baseOperationName;
    private String headquartersLocation;
    private String signatureFinishers;
    private String episodeFormat;
}
