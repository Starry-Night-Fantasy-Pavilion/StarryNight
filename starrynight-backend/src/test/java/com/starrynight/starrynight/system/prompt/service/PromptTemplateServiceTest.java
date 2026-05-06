package com.starrynight.starrynight.system.prompt.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.core.metadata.IPage;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.system.prompt.dto.PromptTemplateDTO;
import com.starrynight.starrynight.system.prompt.entity.PromptTemplate;
import com.starrynight.starrynight.system.prompt.repository.PromptTemplateRepository;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.extension.ExtendWith;
import org.mockito.InjectMocks;
import org.mockito.Mock;
import org.mockito.Spy;
import org.mockito.junit.jupiter.MockitoExtension;

import java.util.HashMap;
import java.util.List;
import java.util.Map;

import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.Mockito.*;

@ExtendWith(MockitoExtension.class)
class PromptTemplateServiceTest {

    @Mock
    private PromptTemplateRepository promptTemplateRepository;

    @Spy
    private ObjectMapper objectMapper = new ObjectMapper();

    @InjectMocks
    private PromptTemplateService promptTemplateService;

    private PromptTemplate testTemplate;

    @BeforeEach
    void setUp() {
        testTemplate = new PromptTemplate();
        testTemplate.setId(1L);
        testTemplate.setName("Test Prompt");
        testTemplate.setCategory("剧情");
        testTemplate.setPromptTemplate("请根据以下大纲写一章：{{outline}}");
        testTemplate.setUserId(100L);
        testTemplate.setIsBuiltin(0);
        testTemplate.setUsageCount(0);
    }

    @Test
    void list_shouldReturnPagedResults() {
        when(ThreadLocalUtil.getUserId()).thenReturn(100L);

        IPage<PromptTemplate> mockPage = new Page<>();
        mockPage.setRecords(List.of(testTemplate));
        mockPage.setTotal(1);

        when(promptTemplateRepository.selectPage(any(Page.class), any(LambdaQueryWrapper.class)))
                .thenReturn(mockPage);

        PageVO<PromptTemplateDTO> result = promptTemplateService.list(null, null, 1, 10);

        assertNotNull(result);
        assertEquals(1, result.getRecords().size());
        assertEquals("Test Prompt", result.getRecords().get(0).getName());
    }

    @Test
    void getById_shouldReturnTemplate_whenExists() {
        when(promptTemplateRepository.selectById(1L)).thenReturn(testTemplate);

        PromptTemplateDTO result = promptTemplateService.getById(1L);

        assertNotNull(result);
        assertEquals("Test Prompt", result.getName());
    }

    @Test
    void getById_shouldThrowException_whenNotFound() {
        when(promptTemplateRepository.selectById(999L)).thenReturn(null);

        assertThrows(ResourceNotFoundException.class, () -> {
            promptTemplateService.getById(999L);
        });
    }

    @Test
    void create_shouldSucceed() {
        when(ThreadLocalUtil.getUserId()).thenReturn(100L);
        when(promptTemplateRepository.insert(any(PromptTemplate.class))).thenReturn(1);

        PromptTemplateDTO dto = new PromptTemplateDTO();
        dto.setName("New Prompt");
        dto.setCategory("角色");
        dto.setPromptTemplate("创建角色：{{name}}");

        PromptTemplateDTO result = promptTemplateService.create(dto);

        assertNotNull(result);
        verify(promptTemplateRepository).insert(any(PromptTemplate.class));
    }

    @Test
    void update_shouldSucceed_whenNotBuiltin() {
        when(promptTemplateRepository.selectById(1L)).thenReturn(testTemplate);
        when(promptTemplateRepository.updateById(any(PromptTemplate.class))).thenReturn(true);

        PromptTemplateDTO dto = new PromptTemplateDTO();
        dto.setName("Updated Prompt");

        PromptTemplateDTO result = promptTemplateService.update(1L, dto);

        assertNotNull(result);
        verify(promptTemplateRepository).updateById(any(PromptTemplate.class));
    }

    @Test
    void update_shouldThrowException_whenBuiltin() {
        testTemplate.setIsBuiltin(1);
        when(promptTemplateRepository.selectById(1L)).thenReturn(testTemplate);

        PromptTemplateDTO dto = new PromptTemplateDTO();
        dto.setName("Updated Prompt");

        assertThrows(BusinessException.class, () -> {
            promptTemplateService.update(1L, dto);
        });
    }

    @Test
    void delete_shouldSucceed_whenNotBuiltin() {
        when(promptTemplateRepository.selectById(1L)).thenReturn(testTemplate);
        when(promptTemplateRepository.deleteById(1L)).thenReturn(1);

        assertDoesNotThrow(() -> {
            promptTemplateService.delete(1L);
        });

        verify(promptTemplateRepository).deleteById(1L);
    }

    @Test
    void delete_shouldThrowException_whenBuiltin() {
        testTemplate.setIsBuiltin(1);
        when(promptTemplateRepository.selectById(1L)).thenReturn(testTemplate);

        assertThrows(BusinessException.class, () -> {
            promptTemplateService.delete(1L);
        });

        verify(promptTemplateRepository, never()).deleteById(any());
    }

    @Test
    void applyPrompt_shouldReplaceVariables() {
        when(promptTemplateRepository.selectById(1L)).thenReturn(testTemplate);
        when(promptTemplateRepository.updateById(any(PromptTemplate.class))).thenReturn(true);

        Map<String, String> variables = new HashMap<>();
        variables.put("outline", "这是一个测试大纲");

        String result = promptTemplateService.applyPrompt(1L, variables);

        assertNotNull(result);
        assertEquals("请根据以下大纲写一章：这是一个测试大纲", result);
    }

    @Test
    void applyPrompt_shouldIncrementUsageCount() {
        when(promptTemplateRepository.selectById(1L)).thenReturn(testTemplate);
        when(promptTemplateRepository.updateById(any(PromptTemplate.class))).thenReturn(true);

        Map<String, String> variables = new HashMap<>();
        variables.put("outline", "测试大纲");

        promptTemplateService.applyPrompt(1L, variables);

        assertEquals(1, testTemplate.getUsageCount());
        verify(promptTemplateRepository).updateById(testTemplate);
    }

    @Test
    void listCategories_shouldReturnCategories() {
        PromptTemplate template1 = new PromptTemplate();
        template1.setCategory("剧情");

        PromptTemplate template2 = new PromptTemplate();
        template2.setCategory("角色");

        when(promptTemplateRepository.selectList(any(LambdaQueryWrapper.class)))
                .thenReturn(List.of(template1, template2));

        List<String> result = promptTemplateService.listCategories();

        assertNotNull(result);
        assertEquals(2, result.size());
        assertTrue(result.contains("剧情"));
        assertTrue(result.contains("角色"));
    }
}
