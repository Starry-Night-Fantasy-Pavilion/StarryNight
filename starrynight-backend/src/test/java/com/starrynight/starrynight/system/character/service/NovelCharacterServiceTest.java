package com.starrynight.starrynight.system.character.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.system.character.dto.NovelCharacterDTO;
import com.starrynight.starrynight.system.character.entity.NovelCharacter;
import com.starrynight.starrynight.system.character.repository.NovelCharacterRepository;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.extension.ExtendWith;
import org.mockito.InjectMocks;
import org.mockito.Mock;
import org.mockito.Spy;
import org.mockito.junit.jupiter.MockitoExtension;

import java.util.List;

import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.Mockito.*;

@ExtendWith(MockitoExtension.class)
class NovelCharacterServiceTest {

    @Mock
    private NovelCharacterRepository characterRepository;

    @Spy
    private ObjectMapper objectMapper = new ObjectMapper();

    @InjectMocks
    private NovelCharacterService characterService;

    private NovelCharacter testCharacter;

    @BeforeEach
    void setUp() {
        testCharacter = new NovelCharacter();
        testCharacter.setId(1L);
        testCharacter.setName("Test Character");
        testCharacter.setIdentity("主角");
        testCharacter.setGender("男");
        testCharacter.setAge("25");
        testCharacter.setUserId(100L);
        testCharacter.setNovelId(1L);
    }

    @Test
    void getById_shouldReturnCharacter_whenExists() {
        when(characterRepository.selectById(1L)).thenReturn(testCharacter);

        NovelCharacterDTO result = characterService.getById(1L);

        assertNotNull(result);
        assertEquals("Test Character", result.getName());
        assertEquals("主角", result.getIdentity());
    }

    @Test
    void getById_shouldThrowException_whenNotFound() {
        when(characterRepository.selectById(999L)).thenReturn(null);

        assertThrows(ResourceNotFoundException.class, () -> {
            characterService.getById(999L);
        });
    }

    @Test
    void update_shouldSucceed() {
        when(characterRepository.selectById(1L)).thenReturn(testCharacter);
        when(characterRepository.updateById(any(NovelCharacter.class))).thenReturn(true);

        NovelCharacterDTO dto = new NovelCharacterDTO();
        dto.setName("Updated Name");

        NovelCharacterDTO result = characterService.update(1L, dto);

        assertNotNull(result);
        verify(characterRepository).updateById(any(NovelCharacter.class));
    }

    @Test
    void delete_shouldThrowException_whenNotOwner() {
        when(characterRepository.selectById(1L)).thenReturn(testCharacter);
        when(ThreadLocalUtil.getUserId()).thenReturn(200L);

        assertThrows(BusinessException.class, () -> {
            characterService.delete(1L);
        });
    }

    @Test
    void delete_shouldSucceed_whenOwner() {
        when(characterRepository.selectById(1L)).thenReturn(testCharacter);
        when(ThreadLocalUtil.getUserId()).thenReturn(100L);
        when(characterRepository.deleteById(1L)).thenReturn(1);

        assertDoesNotThrow(() -> {
            characterService.delete(1L);
        });

        verify(characterRepository).deleteById(1L);
    }

    @Test
    void getRelationshipGraph_shouldReturnGraphData() {
        when(ThreadLocalUtil.getUserId()).thenReturn(100L);

        NovelCharacter char1 = new NovelCharacter();
        char1.setId(1L);
        char1.setName("Character 1");
        char1.setIdentity("主角");
        char1.setGender("男");
        char1.setUserId(100L);
        char1.setNovelId(1L);

        NovelCharacter char2 = new NovelCharacter();
        char2.setId(2L);
        char2.setName("Character 2");
        char2.setIdentity("配角");
        char2.setGender("女");
        char2.setUserId(100L);
        char2.setNovelId(1L);

        when(characterRepository.selectList(any(LambdaQueryWrapper.class)))
                .thenReturn(List.of(char1, char2));

        var result = characterService.getRelationshipGraph(1L);

        assertNotNull(result);
        assertTrue(result.containsKey("nodes"));
        assertTrue(result.containsKey("edges"));
    }
}
