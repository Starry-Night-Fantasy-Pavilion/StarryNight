package com.starrynight.engine.rhythm;

import lombok.Data;
import java.util.List;

@Data
public class EmotionCurve {
    private Integer chapterNo;
    private Float anticipation;
    private Float tension;
    private Float warmth;
    private Float sadness;
    private Float[] curve;
    private String overallTrend;

    public EmotionCurve() {
        this.anticipation = 0f;
        this.tension = 0f;
        this.warmth = 0f;
        this.sadness = 0f;
    }

    public Float getOverallIntensity() {
        return (anticipation + tension + warmth + sadness) / 4f;
    }
}