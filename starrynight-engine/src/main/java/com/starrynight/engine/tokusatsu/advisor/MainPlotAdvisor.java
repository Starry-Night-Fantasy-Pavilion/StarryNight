package com.starrynight.engine.tokusatsu.advisor;

import com.starrynight.engine.tokusatsu.model.EpisodeCard;
import lombok.Data;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;
import java.util.stream.Collectors;

public class MainPlotAdvisor {

    private List<PendingForeshadowing> pendingForeshadowings;

    public MainPlotAdvisor() {
        this.pendingForeshadowings = new ArrayList<>();
    }

    public List<Advisory> analyze(List<EpisodeCard> episodeCards) {
        List<Advisory> advisories = new ArrayList<>();

        advisories.addAll(checkContinuousUnitEpisodes(episodeCards));
        advisories.addAll(checkForeshadowingExpiry(episodeCards));
        advisories.addAll(checkThreatLevelBalance(episodeCards));
        advisories.addAll(checkPlotAdvancePacing(episodeCards));

        return advisories.stream()
                .sorted(Comparator.comparing(a -> -a.getPriority()))
                .collect(Collectors.toList());
    }

    private List<Advisory> checkContinuousUnitEpisodes(List<EpisodeCard> episodeCards) {
        List<Advisory> advisories = new ArrayList<>();

        if (episodeCards.size() < 5) {
            return advisories;
        }

        List<EpisodeCard> recentEpisodes = episodeCards.subList(
                Math.max(0, episodeCards.size() - 5), episodeCards.size()
        );

        long mainPlotAdvances = recentEpisodes.stream()
                .filter(e -> e.getMainPlotConnection() != null &&
                            e.getMainPlotConnection().getAdvanceAmount() > 0)
                .count();

        if (mainPlotAdvances < 2) {
            Advisory advisory = new Advisory();
            advisory.setType(AdvisoryType.WARNING);
            advisory.setPriority(5);
            advisory.setMessage("已连续" + (5 - mainPlotAdvances) + "话为纯单元回，建议本话结尾引入主线设定");
            advisory.setSuggestion("可在本话结尾添加预示性对话或神秘事件");
            advisories.add(advisory);
        }

        return advisories;
    }

    private List<Advisory> checkForeshadowingExpiry(List<EpisodeCard> episodeCards) {
        List<Advisory> advisories = new ArrayList<>();

        for (PendingForeshadowing fs : pendingForeshadowings) {
            int chaptersSinceSetup = episodeCards.size() - fs.getChapterNo();

            if (chaptersSinceSetup > 10 && !fs.isRecovered()) {
                Advisory advisory = new Advisory();
                advisory.setType(AdvisoryType.WARNING);
                advisory.setPriority(3);
                advisory.setMessage("伏笔\"" + fs.getDescription() + "\"已埋设" + chaptersSinceSetup + "话，尚未回收");
                advisory.setSuggestion("建议尽快安排回收或确认是否废弃");
                advisories.add(advisory);
            }
        }

        return advisories;
    }

    private List<Advisory> checkThreatLevelBalance(List<EpisodeCard> episodeCards) {
        List<Advisory> advisories = new ArrayList<>();

        if (episodeCards.size() < 3) {
            return advisories;
        }

        List<EpisodeCard> recentEpisodes = episodeCards.subList(episodeCards.size() - 3, episodeCards.size());

        long highThreatCount = recentEpisodes.stream()
                .filter(e -> e.getMonsterEvent() != null &&
                            e.getMonsterEvent().getEpisodeThreat() == EpisodeCard.ThreatLevel.HIGH)
                .count();

        if (highThreatCount >= 3) {
            Advisory advisory = new Advisory();
            advisory.setType(AdvisoryType.INFO);
            advisory.setPriority(2);
            advisory.setMessage("连续3话为高威胁等级战斗，建议穿插中低威胁单元回调节节奏");
            advisory.setSuggestion("可安排日常回或角色互动回");
            advisories.add(advisory);
        }

        return advisories;
    }

    private List<Advisory> checkPlotAdvancePacing(List<EpisodeCard> episodeCards) {
        List<Advisory> advisories = new ArrayList<>();

        List<EpisodeCard> episodesWithPlot = episodeCards.stream()
                .filter(e -> e.getMainPlotConnection() != null)
                .toList();

        if (episodesWithPlot.isEmpty()) {
            return advisories;
        }

        double totalAdvance = episodesWithPlot.stream()
                .mapToDouble(e -> e.getMainPlotConnection().getAdvanceAmount())
                .sum();

        if (totalAdvance >= 1.0) {
            Advisory advisory = new Advisory();
            advisory.setType(AdvisoryType.MILESTONE);
            advisory.setPriority(10);
            advisory.setMessage("主线剧情已达到" + (int)(totalAdvance * 100) + "%，可考虑安排阶段性高潮或转折");
            advisory.setSuggestion("可设计BOSS战、角色成长或重大剧情揭露");
            advisories.add(advisory);
        }

        return advisories;
    }

    public void addForeshadowing(int chapterNo, String description) {
        PendingForeshadowing fs = new PendingForeshadowing();
        fs.setChapterNo(chapterNo);
        fs.setDescription(description);
        fs.setRecovered(false);
        this.pendingForeshadowings.add(fs);
    }

    public void markForeshadowingRecovered(String description) {
        for (PendingForeshadowing fs : pendingForeshadowings) {
            if (fs.getDescription().equals(description)) {
                fs.setRecovered(true);
                break;
            }
        }
    }

    @Data
    public static class Advisory {
        private AdvisoryType type;
        private int priority;
        private String message;
        private String suggestion;
    }

    public enum AdvisoryType {
        WARNING, INFO, MILESTONE
    }

    @Data
    public static class PendingForeshadowing {
        private int chapterNo;
        private String description;
        private boolean recovered;
    }
}
