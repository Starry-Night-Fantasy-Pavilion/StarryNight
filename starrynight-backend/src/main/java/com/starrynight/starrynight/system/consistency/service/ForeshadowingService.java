package com.starrynight.starrynight.system.consistency.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.core.conditions.update.LambdaUpdateWrapper;
import com.starrynight.engine.foreshadowing.Foreshadowing;
import com.starrynight.engine.foreshadowing.ForeshadowingDetector;
import com.starrynight.engine.foreshadowing.ForeshadowingPayoffChecker;
import com.starrynight.engine.foreshadowing.PayoffCheckResult;
import com.starrynight.starrynight.system.consistency.entity.ForeshadowingRecord;
import com.starrynight.starrynight.system.consistency.mapper.ForeshadowingRecordMapper;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.stream.Collectors;

@Service
public class ForeshadowingService {

    @Autowired
    private ForeshadowingRecordMapper foreshadowingRecordMapper;

    @Autowired
    private ForeshadowingDetector foreshadowingDetector;

    @Autowired
    private ForeshadowingPayoffChecker payoffChecker;

    @Transactional
    public List<ForeshadowingRecord> detectAndSave(Long novelId, Integer chapterNo, String content) {
        List<Foreshadowing> detected = foreshadowingDetector.detect(content, novelId, chapterNo);

        List<ForeshadowingRecord> records = new ArrayList<>();
        for (Foreshadowing fs : detected) {
            ForeshadowingRecord record = convertToRecord(fs);
            foreshadowingRecordMapper.insert(record);
            records.add(record);
        }

        return records;
    }

    public List<ForeshadowingRecord> getPendingForeshadowings(Long novelId) {
        return foreshadowingRecordMapper.findPendingByNovelId(novelId);
    }

    public List<ForeshadowingRecord> getPaidOffForeshadowings(Long novelId) {
        return foreshadowingRecordMapper.findPaidOffByNovelId(novelId);
    }

    public List<ForeshadowingRecord> getAllForeshadowings(Long novelId) {
        LambdaQueryWrapper<ForeshadowingRecord> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(ForeshadowingRecord::getNovelId, novelId)
                .orderByAsc(ForeshadowingRecord::getChapterNo);
        return foreshadowingRecordMapper.selectList(wrapper);
    }

    @Transactional
    public ForeshadowingRecord confirmForeshadowing(String id) {
        ForeshadowingRecord record = foreshadowingRecordMapper.selectById(id);
        if (record != null) {
            record.setStatus(Foreshadowing.ForeshadowingStatus.CONFIRMED.getCode());
            record.setConfirmedAt(LocalDateTime.now());
            record.setUserEdited(true);
            foreshadowingRecordMapper.updateById(record);
        }
        return record;
    }

    @Transactional
    public ForeshadowingRecord cancelForeshadowing(String id) {
        ForeshadowingRecord record = foreshadowingRecordMapper.selectById(id);
        if (record != null) {
            record.setStatus(Foreshadowing.ForeshadowingStatus.CANCELLED.getCode());
            record.setUserEdited(true);
            foreshadowingRecordMapper.updateById(record);
        }
        return record;
    }

    @Transactional
    public ForeshadowingRecord setExpectedChapter(String id, Integer expectedChapterNo) {
        ForeshadowingRecord record = foreshadowingRecordMapper.selectById(id);
        if (record != null) {
            record.setExpectedChapterNo(expectedChapterNo);
            foreshadowingRecordMapper.updateById(record);
        }
        return record;
    }

    public PayoffCheckResult checkPayoff(String id, List<String> subsequentContents) {
        ForeshadowingRecord record = foreshadowingRecordMapper.selectById(id);
        if (record == null) {
            return null;
        }

        Foreshadowing foreshadowing = convertToForeshadowing(record);
        return payoffChecker.checkPayoff(foreshadowing, subsequentContents);
    }

    @Transactional
    public ForeshadowingRecord markAsPaidOff(String id, Integer paidOffChapterNo, String payoffMethod, String payoffContent) {
        ForeshadowingRecord record = foreshadowingRecordMapper.selectById(id);
        if (record != null) {
            record.setStatus(Foreshadowing.ForeshadowingStatus.PAID_OFF.getCode());
            record.setPaidOffAt(LocalDateTime.now());
            record.setPaidOffChapterNo(paidOffChapterNo);
            record.setPayoffMethod(payoffMethod);
            record.setPayoffContent(payoffContent);
            foreshadowingRecordMapper.updateById(record);
        }
        return record;
    }

    public List<String> getPayoffSuggestions(String id) {
        ForeshadowingRecord record = foreshadowingRecordMapper.selectById(id);
        if (record == null) {
            return new ArrayList<>();
        }

        Foreshadowing foreshadowing = convertToForeshadowing(record);
        return payoffChecker.generatePayoffSuggestions(foreshadowing);
    }

    public List<ForeshadowingRecord> checkUnpaidForeshadowings(Long novelId, Integer currentChapterNo) {
        return foreshadowingRecordMapper.findUnpaidForeshadowingsBeforeChapter(novelId, currentChapterNo);
    }

    public List<ForeshadowingRecord> getOverdueForeshadowings(Long novelId, Integer thresholdChapter) {
        LambdaQueryWrapper<ForeshadowingRecord> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(ForeshadowingRecord::getNovelId, novelId)
                .eq(ForeshadowingRecord::getStatus, Foreshadowing.ForeshadowingStatus.CONFIRMED.getCode())
                .lt(ForeshadowingRecord::getChapterNo, thresholdChapter - 5)
                .isNull(ForeshadowingRecord::getPaidOffChapterNo);
        return foreshadowingRecordMapper.selectList(wrapper);
    }

    private ForeshadowingRecord convertToRecord(Foreshadowing fs) {
        ForeshadowingRecord record = new ForeshadowingRecord();
        record.setId(fs.getId());
        record.setNovelId(fs.getNovelId());
        record.setChapterNo(fs.getChapterNo());
        record.setSetupContent(fs.getSetupContent());
        record.setSetupLocation(fs.getSetupLocation());
        record.setType(fs.getType() != null ? fs.getType().getCode() : null);
        record.setStatus(fs.getStatus() != null ? fs.getStatus().getCode() : Foreshadowing.ForeshadowingStatus.PENDING.getCode());
        record.setExpectedChapterNo(fs.getExpectedChapterNo());
        record.setAutoDetectedExpected(fs.getAutoDetectedExpected());
        record.setConfidence(fs.getConfidence());
        record.setDetectedAt(fs.getDetectedAt());
        record.setConfirmedAt(fs.getConfirmedAt());
        record.setUserEdited(fs.getUserEdited());
        record.setCreatedAt(LocalDateTime.now());
        record.setUpdatedAt(LocalDateTime.now());
        return record;
    }

    private Foreshadowing convertToForeshadowing(ForeshadowingRecord record) {
        Foreshadowing fs = new Foreshadowing();
        fs.setId(record.getId());
        fs.setNovelId(record.getNovelId());
        fs.setChapterNo(record.getChapterNo());
        fs.setSetupContent(record.getSetupContent());
        fs.setSetupLocation(record.getSetupLocation());
        fs.setType(record.getType() != null ?
                Foreshadowing.ForeshadowingType.valueOf(record.getType().toUpperCase()) : null);
        fs.setStatus(record.getStatus() != null ?
                Foreshadowing.ForeshadowingStatus.valueOf(record.getStatus().toUpperCase()) : null);
        fs.setExpectedChapterNo(record.getExpectedChapterNo());
        fs.setAutoDetectedExpected(record.getAutoDetectedExpected());
        fs.setConfidence(record.getConfidence());
        fs.setDetectedAt(record.getDetectedAt());
        fs.setConfirmedAt(record.getConfirmedAt());
        fs.setUserEdited(record.getUserEdited());

        if (record.getPaidOffAt() != null) {
            Foreshadowing.PayoffInfo payoffInfo = new Foreshadowing.PayoffInfo();
            payoffInfo.setPaidOffAt(record.getPaidOffAt());
            payoffInfo.setPaidOffChapterNo(record.getPaidOffChapterNo());
            payoffInfo.setPayoffMethod(record.getPayoffMethod());
            payoffInfo.setPayoffContent(record.getPayoffContent());
            fs.setPayoffInfo(payoffInfo);
        }

        return fs;
    }
}