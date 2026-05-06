package com.starrynight.starrynight.config;

import com.starrynight.engine.fanwork.AlignmentButterflyController;
import com.starrynight.engine.fanwork.ButterflyEffectTracker;
import com.starrynight.engine.fanwork.CanonPlotAnchor;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;

import java.util.Collections;

/**
 * 剧情对齐与蝴蝶效应控制器：提供可注入 Bean，便于后续接入原作锚点数据。
 */
@Configuration
public class FanworkAlignmentConfig {

    @Bean
    public ButterflyEffectTracker butterflyEffectTracker() {
        return new ButterflyEffectTracker();
    }

    @Bean
    public AlignmentButterflyController.PlotAlignmentService plotAlignmentService() {
        return chapterNo -> Collections.<CanonPlotAnchor>emptyList();
    }

    @Bean
    public AlignmentButterflyController alignmentButterflyController(
            ButterflyEffectTracker butterflyEffectTracker,
            AlignmentButterflyController.PlotAlignmentService plotAlignmentService) {
        return new AlignmentButterflyController(butterflyEffectTracker, plotAlignmentService);
    }
}
