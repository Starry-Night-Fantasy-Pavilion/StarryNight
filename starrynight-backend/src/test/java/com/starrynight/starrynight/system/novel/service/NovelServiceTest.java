package com.starrynight.starrynight.system.novel.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.system.novel.dto.NovelDTO;
import com.starrynight.starrynight.system.novel.dto.NovelVolumeDTO;
import com.starrynight.starrynight.system.novel.dto.NovelChapterDTO;
import com.starrynight.starrynight.system.novel.entity.Novel;
import com.starrynight.starrynight.system.novel.entity.NovelVolume;
import com.starrynight.starrynight.system.novel.entity.NovelChapter;
import com.starrynight.starrynight.system.novel.repository.NovelRepository;
import com.starrynight.starrynight.system.novel.repository.NovelVolumeRepository;
import com.starrynight.starrynight.system.novel.repository.NovelChapterRepository;
import com.starrynight.starrynight.system.auth.realname.RealnameVerificationService;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.extension.ExtendWith;
import org.mockito.InjectMocks;
import org.mockito.Mock;
import org.mockito.junit.jupiter.MockitoExtension;

import java.util.List;

import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.Mockito.*;

@ExtendWith(MockitoExtension.class)
class NovelServiceTest {

    @Mock
    private NovelRepository novelRepository;

    @Mock
    private NovelVolumeRepository novelVolumeRepository;

    @Mock
    private NovelChapterRepository novelChapterRepository;

    @Mock
    private RealnameVerificationService realnameVerificationService;

    @InjectMocks
    private NovelService novelService;

    private Novel testNovel;
    private NovelVolume testVolume;
    private NovelChapter testChapter;

    @BeforeEach
    void setUp() {
        testNovel = new Novel();
        testNovel.setId(1L);
        testNovel.setTitle("Test Novel");
        testNovel.setUserId(100L);
        testNovel.setWordCount(0);
        testNovel.setChapterCount(0);

        testVolume = new NovelVolume();
        testVolume.setId(1L);
        testVolume.setNovelId(1L);
        testVolume.setTitle("Volume 1");
        testVolume.setVolumeOrder(1);

        testChapter = new NovelChapter();
        testChapter.setId(1L);
        testChapter.setNovelId(1L);
        testChapter.setVolumeId(1L);
        testChapter.setTitle("Chapter 1");
        testChapter.setWordCount(1000);
        testChapter.setChapterOrder(1);
    }

    @Test
    void listNovels_shouldReturnPagedResults() {
        when(ThreadLocalUtil.getUserId()).thenReturn(100L);
        when(novelRepository.selectPage(any(Page.class), any(LambdaQueryWrapper.class)))
                .thenReturn(new Page<Novel>().setRecords(List.of(testNovel)));

        NovelDTO result = novelService.list("1", 10);

        assertNotNull(result);
        assertEquals(1, result.getRecords().size());
        assertEquals("Test Novel", result.getRecords().get(0).getTitle());
    }

    @Test
    void getNovelById_shouldReturnNovel_whenExists() {
        when(novelRepository.selectById(1L)).thenReturn(testNovel);

        NovelDTO result = novelService.getById(1L);

        assertNotNull(result);
        assertEquals("Test Novel", result.getTitle());
    }

    @Test
    void getNovelById_shouldThrowException_whenNotFound() {
        when(novelRepository.selectById(999L)).thenReturn(null);

        assertThrows(ResourceNotFoundException.class, () -> {
            novelService.getById(999L);
        });
    }

    @Test
    void deleteNovel_shouldThrowException_whenNotOwner() {
        when(ThreadLocalUtil.getUserId()).thenReturn(200L);
        when(novelRepository.selectById(1L)).thenReturn(testNovel);

        assertThrows(BusinessException.class, () -> {
            novelService.deleteNovel(1L);
        });
    }

    @Test
    void deleteNovel_shouldSucceed_whenOwner() {
        when(ThreadLocalUtil.getUserId()).thenReturn(100L);
        when(novelRepository.selectById(1L)).thenReturn(testNovel);
        when(novelVolumeRepository.selectList(any(LambdaQueryWrapper.class))).thenReturn(List.of());
        when(novelChapterRepository.selectList(any(LambdaQueryWrapper.class))).thenReturn(List.of());

        assertDoesNotThrow(() -> {
            novelService.deleteNovel(1L);
        });

        verify(novelRepository).deleteById(1L);
    }

    @Test
    void createVolume_shouldSucceed() {
        when(ThreadLocalUtil.getUserId()).thenReturn(100L);
        when(novelRepository.selectById(1L)).thenReturn(testNovel);

        NovelVolumeDTO dto = new NovelVolumeDTO();
        dto.setNovelId(1L);
        dto.setTitle("New Volume");
        dto.setVolumeOrder(2);

        NovelVolumeDTO result = novelService.createVolume(dto);

        assertNotNull(result);
        verify(novelVolumeRepository).insert(any(NovelVolume.class));
    }

    @Test
    void listVolumes_shouldReturnVolumes() {
        when(novelVolumeRepository.selectList(any(LambdaQueryWrapper.class)))
                .thenReturn(List.of(testVolume));

        List<NovelVolumeDTO> result = novelService.listVolumes(1L);

        assertNotNull(result);
        assertEquals(1, result.size());
        assertEquals("Volume 1", result.get(0).getTitle());
    }

    @Test
    void deleteChapter_shouldUpdateNovelWordCount() {
        when(novelChapterRepository.selectById(1L)).thenReturn(testChapter);
        when(novelRepository.selectById(1L)).thenReturn(testNovel);
        when(novelChapterRepository.deleteById(1L)).thenReturn(1);

        novelService.deleteChapter(1L);

        verify(novelChapterRepository).deleteById(1L);
        assertEquals(-1000, testNovel.getWordCount());
        assertEquals(-1, testNovel.getChapterCount());
    }

    @Test
    void exportNovel_shouldReturnHtmlFormat() {
        when(novelRepository.selectById(1L)).thenReturn(testNovel);
        when(ThreadLocalUtil.getUserId()).thenReturn(100L);
        when(novelVolumeRepository.selectList(any(LambdaQueryWrapper.class))).thenReturn(List.of(testVolume));
        when(novelChapterRepository.selectList(any(LambdaQueryWrapper.class))).thenReturn(List.of(testChapter));

        String result = novelService.exportNovel(1L, "html");

        assertNotNull(result);
        assertTrue(result.contains("<html>"));
        assertTrue(result.contains("Test Novel"));
        assertTrue(result.contains("Volume 1"));
        assertTrue(result.contains("Chapter 1"));
    }

    @Test
    void exportNovel_shouldReturnTxtFormat() {
        when(novelRepository.selectById(1L)).thenReturn(testNovel);
        when(ThreadLocalUtil.getUserId()).thenReturn(100L);
        when(novelVolumeRepository.selectList(any(LambdaQueryWrapper.class))).thenReturn(List.of(testVolume));
        when(novelChapterRepository.selectList(any(LambdaQueryWrapper.class))).thenReturn(List.of(testChapter));

        String result = novelService.exportNovel(1L, "txt");

        assertNotNull(result);
        assertTrue(result.contains("Test Novel"));
        assertFalse(result.contains("<html>"));
    }
}
