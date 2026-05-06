package com.starrynight.engine.fanwork;

import lombok.Data;
import java.util.ArrayList;
import java.util.List;

@Data
public class FanFictionAnalyzer {

    public FanFictionAnalysis analyze(FanFictionDocument document) {
        FanFictionAnalysis analysis = new FanFictionAnalysis();

        StructuredContent structured = parseStructure(document);
        List<KeyEvent> keyEvents = extractKeyEvents(structured);
        List<CharacterArc> characterArcs = extractCharacterArcs(structured);
        RhythmAnalysis rhythm = analyzeRhythm(structured);
        List<SafeZone> safeZones = identifySafeZones(keyEvents, characterArcs, rhythm);

        analysis.setSafeZones(safeZones);
        analysis.setCharacterArcs(characterArcs);
        analysis.setRecommendedPatterns(extractPatterns(keyEvents, rhythm));

        return analysis;
    }

    private StructuredContent parseStructure(FanFictionDocument document) {
        StructuredContent structured = new StructuredContent();
        structured.setChapters(document.getChapters());
        structured.setCharacters(document.getCharacters());
        structured.setEvents(new ArrayList<>());
        return structured;
    }

    private List<KeyEvent> extractKeyEvents(StructuredContent structured) {
        List<KeyEvent> events = new ArrayList<>();
        for (Chapter chapter : structured.getChapters()) {
            for (String event : chapter.getEvents()) {
                if (isKeyEvent(event)) {
                    KeyEvent keyEvent = new KeyEvent();
                    keyEvent.setChapterNo(chapter.getChapterNo());
                    keyEvent.setDescription(event);
                    keyEvent.setType(EventType.CRITICAL);
                    events.add(keyEvent);
                }
            }
        }
        return events;
    }

    private boolean isKeyEvent(String event) {
        return event.contains("关键") ||
               event.contains("转折") ||
               event.contains("高潮") ||
               event.contains("决战");
    }

    private List<CharacterArc> extractCharacterArcs(StructuredContent structured) {
        List<CharacterArc> arcs = new ArrayList<>();
        for (Character character : structured.getCharacters()) {
            CharacterArc arc = new CharacterArc();
            arc.setCharacterId(character.getId());
            arc.setArcPoints(new ArrayList<>());
            arcs.add(arc);
        }
        return arcs;
    }

    private RhythmAnalysis analyzeRhythm(StructuredContent structured) {
        RhythmAnalysis rhythm = new RhythmAnalysis();
        rhythm.setChapterRhythms(new ArrayList<>());
        return rhythm;
    }

    private List<SafeZone> identifySafeZones(List<KeyEvent> keyEvents, List<CharacterArc> characterArcs, RhythmAnalysis rhythm) {
        List<SafeZone> zones = new ArrayList<>();

        SafeZone zone1 = new SafeZone();
        zone1.setStartPosition(1);
        zone1.setEndPosition(5);
        zone1.setSafeLevel(SafeZone.SafeLevel.HIGH);
        zone1.setRecommendedActivities(List.of("角色介绍", "世界观展示", "日常互动"));
        zone1.setPotentialRisks(List.of());
        zones.add(zone1);

        return zones;
    }

    private List<String> extractPatterns(List<KeyEvent> keyEvents, RhythmAnalysis rhythm) {
        return new ArrayList<>();
    }

    @Data
    public static class FanFictionDocument {
        private String id;
        private String title;
        private String originalWork;
        private String fandom;
        private List<Chapter> chapters;
        private List<Character> characters;
    }

    @Data
    public static class Chapter {
        private int chapterNo;
        private String title;
        private List<String> events;
    }

    @Data
    public static class Character {
        private String id;
        private String name;
    }

    @Data
    public static class StructuredContent {
        private List<Chapter> chapters;
        private List<Character> characters;
        private List<Object> events;
    }

    @Data
    public static class KeyEvent {
        private int chapterNo;
        private String description;
        private EventType type;
    }

    public enum EventType {
        CRITICAL, NORMAL, BACKGROUND
    }

    @Data
    public static class CharacterArc {
        private String characterId;
        private List<ArcPoint> arcPoints;
    }

    @Data
    public static class ArcPoint {
        private int chapterNo;
        private String status;
        private String emotion;
    }

    @Data
    public static class RhythmAnalysis {
        private List<ChapterRhythm> chapterRhythms;
    }

    @Data
    public static class ChapterRhythm {
        private int chapterNo;
        private int intensity;
    }

    @Data
    public static class SafeZone {
        private int startPosition;
        private int endPosition;
        private SafeLevel safeLevel;
        private List<String> recommendedActivities;
        private List<String> potentialRisks;

        public enum SafeLevel {
            HIGH, MEDIUM, LOW
        }
    }

    @Data
    public static class FanFictionAnalysis {
        private List<SafeZone> safeZones;
        private List<CharacterArc> characterArcs;
        private List<String> recommendedPatterns;
    }
}
