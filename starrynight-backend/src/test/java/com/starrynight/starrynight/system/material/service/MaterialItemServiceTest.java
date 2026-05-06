package com.starrynight.starrynight.system.material.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.core.metadata.IPage;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.system.material.dto.MaterialItemDTO;
import com.starrynight.starrynight.system.material.entity.MaterialItem;
import com.starrynight.starrynight.system.material.repository.MaterialItemRepository;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.extension.ExtendWith;
import org.mockito.InjectMocks;
import org.mockito.Mock;
import org.mockito.Spy;
import org.mockito.junit.jupiter.MockitoExtension;

import java.util.List;
import java.util.Map;

import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.Mockito.*;

@ExtendWith(MockitoExtension.class)
class MaterialItemServiceTest {

    @Mock
    private MaterialItemRepository materialItemRepository;

    @Spy
    private ObjectMapper objectMapper = new ObjectMapper();

    @InjectMocks
    private MaterialItemService materialItemService;

    private MaterialItem testItem;

    @BeforeEach
    void setUp() {
        testItem = new MaterialItem();
        testItem.setId(1L);
        testItem.setTitle("Test Material");
        testItem.setType("golden_finger");
        testItem.setDescription("Test description");
        testItem.setUserId(100L);
        testItem.setUsageCount(0);
    }

    @Test
    void list_shouldReturnPagedResults() {
        when(ThreadLocalUtil.getUserId()).thenReturn(100L);

        IPage<MaterialItem> mockPage = new Page<>();
        mockPage.setRecords(List.of(testItem));
        mockPage.setTotal(1);

        when(materialItemRepository.selectPage(any(Page.class), any(LambdaQueryWrapper.class)))
                .thenReturn(mockPage);

        PageVO<MaterialItemDTO> result = materialItemService.list(null, null, null, 1, 10);

        assertNotNull(result);
        assertEquals(1, result.getRecords().size());
        assertEquals("Test Material", result.getRecords().get(0).getTitle());
    }

    @Test
    void getById_shouldReturnItem_whenExists() {
        when(materialItemRepository.selectById(1L)).thenReturn(testItem);

        MaterialItemDTO result = materialItemService.getById(1L);

        assertNotNull(result);
        assertEquals("Test Material", result.getTitle());
    }

    @Test
    void getById_shouldThrowException_whenNotFound() {
        when(materialItemRepository.selectById(999L)).thenReturn(null);

        assertThrows(ResourceNotFoundException.class, () -> {
            materialItemService.getById(999L);
        });
    }

    @Test
    void create_shouldSucceed() {
        when(ThreadLocalUtil.getUserId()).thenReturn(100L);
        when(materialItemRepository.insert(any(MaterialItem.class))).thenReturn(1);

        MaterialItemDTO dto = new MaterialItemDTO();
        dto.setTitle("New Material");
        dto.setType("worldview");
        dto.setDescription("New description");

        MaterialItemDTO result = materialItemService.create(dto);

        assertNotNull(result);
        verify(materialItemRepository).insert(any(MaterialItem.class));
    }

    @Test
    void update_shouldSucceed() {
        when(materialItemRepository.selectById(1L)).thenReturn(testItem);
        when(materialItemRepository.updateById(any(MaterialItem.class))).thenReturn(true);

        MaterialItemDTO dto = new MaterialItemDTO();
        dto.setTitle("Updated Material");

        MaterialItemDTO result = materialItemService.update(1L, dto);

        assertNotNull(result);
        verify(materialItemRepository).updateById(any(MaterialItem.class));
    }

    @Test
    void delete_shouldSucceed() {
        when(materialItemRepository.selectById(1L)).thenReturn(testItem);
        when(materialItemRepository.deleteById(1L)).thenReturn(1);

        assertDoesNotThrow(() -> {
            materialItemService.delete(1L);
        });

        verify(materialItemRepository).deleteById(1L);
    }

    @Test
    void delete_shouldThrowException_whenNotOwner() {
        when(materialItemRepository.selectById(1L)).thenReturn(testItem);
        when(ThreadLocalUtil.getUserId()).thenReturn(200L);

        assertThrows(BusinessException.class, () -> {
            materialItemService.delete(1L);
        });

        verify(materialItemRepository, never()).deleteById(any());
    }

    @Test
    void recordUsage_shouldIncrementUsageCount() {
        when(materialItemRepository.selectById(1L)).thenReturn(testItem);
        when(materialItemRepository.updateById(any(MaterialItem.class))).thenReturn(true);

        assertDoesNotThrow(() -> {
            materialItemService.recordUsage(1L);
        });

        assertEquals(1, testItem.getUsageCount());
        verify(materialItemRepository).updateById(testItem);
    }
}
