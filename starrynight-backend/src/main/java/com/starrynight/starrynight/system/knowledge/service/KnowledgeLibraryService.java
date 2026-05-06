package com.starrynight.starrynight.system.knowledge.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.core.metadata.IPage;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.services.document.DocumentParserService;
import com.starrynight.starrynight.services.document.DocumentParserService.ParseResult;
import com.starrynight.starrynight.services.document.DocumentParserService.Chunk;
import com.starrynight.starrynight.system.knowledge.dto.KnowledgeCapacityDTO;
import com.starrynight.starrynight.system.knowledge.dto.KnowledgeChunkDTO;
import com.starrynight.starrynight.system.knowledge.dto.KnowledgeLibraryDTO;
import com.starrynight.starrynight.system.knowledge.entity.KnowledgeChunk;
import com.starrynight.starrynight.system.knowledge.entity.KnowledgeLibrary;
import com.starrynight.starrynight.system.knowledge.repository.KnowledgeChunkRepository;
import com.starrynight.starrynight.system.knowledge.repository.KnowledgeLibraryRepository;
import org.springframework.beans.BeanUtils;
import com.starrynight.starrynight.system.storage.StorageService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.multipart.MultipartFile;

import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.HexFormat;
import java.util.List;
import java.util.stream.Collectors;

@Service
public class KnowledgeLibraryService {

    @Autowired
    private KnowledgeLibraryRepository knowledgeLibraryRepository;

    @Autowired
    private KnowledgeChunkRepository knowledgeChunkRepository;

    @Autowired
    private StorageService storageService;

    @Autowired
    private DocumentParserService documentParserService;

    // ==================== 知识库 CRUD ====================

    public PageVO<KnowledgeLibraryDTO> list(String keyword, String type, String status, int page, int size) {
        Long userId = ThreadLocalUtil.getUserId();
        LambdaQueryWrapper<KnowledgeLibrary> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(KnowledgeLibrary::getUserId, userId);
        if (keyword != null && !keyword.isBlank()) {
            wrapper.like(KnowledgeLibrary::getName, keyword);
        }
        if (type != null && !type.isBlank()) {
            wrapper.eq(KnowledgeLibrary::getType, type);
        }
        if (status != null && !status.isBlank()) {
            wrapper.eq(KnowledgeLibrary::getStatus, status);
        }
        wrapper.orderByDesc(KnowledgeLibrary::getUpdateTime);

        IPage<KnowledgeLibrary> pageResult = knowledgeLibraryRepository.selectPage(new Page<>(page, size), wrapper);
        List<KnowledgeLibraryDTO> records = pageResult.getRecords().stream().map(this::toDTO).collect(Collectors.toList());
        return PageVO.of(pageResult.getTotal(), records, (long) page, (long) size);
    }

    public KnowledgeLibraryDTO getById(Long id) {
        KnowledgeLibrary entity = knowledgeLibraryRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("知识库不存在");
        }
        return toDTO(entity);
    }

    @Transactional
    public KnowledgeLibraryDTO create(KnowledgeLibraryDTO dto) {
        Long userId = ThreadLocalUtil.getUserId();
        KnowledgeLibrary entity = new KnowledgeLibrary();
        BeanUtils.copyProperties(dto, entity);
        entity.setUserId(userId);
        entity.setDocumentCount(0);
        entity.setChunkCount(0);
        entity.setStatus("READY");
        knowledgeLibraryRepository.insert(entity);
        return toDTO(entity);
    }

    @Transactional
    public KnowledgeLibraryDTO update(Long id, KnowledgeLibraryDTO dto) {
        KnowledgeLibrary entity = knowledgeLibraryRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("知识库不存在");
        }
        if (dto.getName() != null) entity.setName(dto.getName());
        if (dto.getDescription() != null) entity.setDescription(dto.getDescription());
        if (dto.getTags() != null) entity.setTags(dto.getTags());
        knowledgeLibraryRepository.updateById(entity);
        return toDTO(entity);
    }

    @Transactional
    public void delete(Long id) {
        KnowledgeLibrary entity = knowledgeLibraryRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("知识库不存在");
        }
        // 同时删除关联的切片
        knowledgeChunkRepository.delete(new LambdaQueryWrapper<KnowledgeChunk>().eq(KnowledgeChunk::getLibraryId, id));
        if (knowledgeLibraryRepository.deleteById(id) <= 0) {
            throw new BusinessException("删除失败");
        }
    }

    public KnowledgeCapacityDTO getCapacity() {
        Long userId = ThreadLocalUtil.getUserId();
        LambdaQueryWrapper<KnowledgeLibrary> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(KnowledgeLibrary::getUserId, userId);
        List<KnowledgeLibrary> list = knowledgeLibraryRepository.selectList(wrapper);
        long usedBytes = list.stream().mapToLong(k -> k.getFileSize() != null ? k.getFileSize() : 0).sum();
        long totalBytes = 1024L * 1024 * 1024; // 默认 1GB
        return KnowledgeCapacityDTO.of(usedBytes, totalBytes);
    }

    // ==================== 文档上传与解析 ====================

    /**
     * 上传文档文件到知识库
     */
    @Transactional
    public KnowledgeLibraryDTO uploadDocument(Long libraryId, MultipartFile file) {
        KnowledgeLibrary entity = knowledgeLibraryRepository.selectById(libraryId);
        if (entity == null) {
            throw new ResourceNotFoundException("知识库不存在");
        }

        String fileName = file.getOriginalFilename();
        String contentType = file.getContentType();
        byte[] fileBytes;
        try {
            fileBytes = file.getBytes();
        } catch (IOException e) {
            throw new BusinessException("Failed to read file: " + e.getMessage());
        }

        String objectPath = "knowledge/" + libraryId + "/";
        String fileUrl = storageService.uploadFile(file, objectPath);

        entity.setFileUrl(fileUrl);
        entity.setFileType(contentType);
        entity.setFileSize((long) fileBytes.length);
        entity.setStatus("PROCESSING");
        knowledgeLibraryRepository.updateById(entity);

        try {
            ParseResult parseResult;
            try (InputStream inputStream = new ByteArrayInputStream(fileBytes)) {
                parseResult = documentParserService.parseDocument(inputStream, fileName, contentType);
            }

            for (Chunk chunk : parseResult.chunks()) {
                KnowledgeChunk knowledgeChunk = new KnowledgeChunk();
                knowledgeChunk.setLibraryId(libraryId);
                knowledgeChunk.setContent(chunk.content());
                knowledgeChunk.setChunkOrder(chunk.index());
                knowledgeChunk.setContentHash(hashContent(chunk.content()));
                knowledgeChunk.setTokenCount(estimateTokens(chunk.content()));
                knowledgeChunk.setMetadata(chunk.metadata());
                knowledgeChunkRepository.insert(knowledgeChunk);
            }

            entity.setDocumentCount(entity.getDocumentCount() + 1);
            entity.setChunkCount(entity.getChunkCount() + parseResult.chunks().size());
            entity.setStatus("READY");
            knowledgeLibraryRepository.updateById(entity);
        } catch (Exception e) {
            entity.setStatus("ERROR");
            entity.setErrorMessage(e.getMessage());
            knowledgeLibraryRepository.updateById(entity);
            throw new BusinessException("文档处理失败: " + e.getMessage());
        }

        return toDTO(entity);
    }

    private KnowledgeChunk createChunk(Long libraryId, String content, int order) {
        KnowledgeChunk chunk = new KnowledgeChunk();
        chunk.setLibraryId(libraryId);
        chunk.setContent(content);
        chunk.setChunkOrder(order);
        chunk.setContentHash(hashContent(content));
        chunk.setTokenCount(estimateTokens(content));
        return chunk;
    }

    private String hashContent(String content) {
        try {
            MessageDigest md = MessageDigest.getInstance("MD5");
            byte[] digest = md.digest(content.getBytes(StandardCharsets.UTF_8));
            return HexFormat.of().formatHex(digest);
        } catch (NoSuchAlgorithmException e) {
            return String.valueOf(content.hashCode());
        }
    }

    private int estimateTokens(String text) {
        // 粗略估算：中文约 1.5 字/token，英文约 4 字符/token
        int chineseChars = 0;
        int asciiChars = 0;
        for (char c : text.toCharArray()) {
            if (c > 0x4E00 && c < 0x9FFF) {
                chineseChars++;
            } else {
                asciiChars++;
            }
        }
        return (int) (chineseChars / 1.5 + asciiChars / 4.0);
    }

    // ==================== 切片查询 ====================

    /**
     * 获取知识库的切片列表
     */
    public PageVO<KnowledgeChunkDTO> listChunks(Long libraryId, int page, int size) {
        LambdaQueryWrapper<KnowledgeChunk> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(KnowledgeChunk::getLibraryId, libraryId);
        wrapper.orderByAsc(KnowledgeChunk::getChunkOrder);

        IPage<KnowledgeChunk> pageResult = knowledgeChunkRepository.selectPage(new Page<>(page, size), wrapper);
        List<KnowledgeChunkDTO> records = pageResult.getRecords().stream().map(this::toChunkDTO).collect(Collectors.toList());
        return PageVO.of(pageResult.getTotal(), records, (long) page, (long) size);
    }

    /**
     * 知识库全文检索（基于 LIKE）
     */
    public PageVO<KnowledgeChunkDTO> searchChunks(Long libraryId, String keyword, int page, int size) {
        LambdaQueryWrapper<KnowledgeChunk> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(KnowledgeChunk::getLibraryId, libraryId);
        if (keyword != null && !keyword.isBlank()) {
            wrapper.like(KnowledgeChunk::getContent, keyword);
        }
        wrapper.orderByAsc(KnowledgeChunk::getChunkOrder);

        IPage<KnowledgeChunk> pageResult = knowledgeChunkRepository.selectPage(new Page<>(page, size), wrapper);
        List<KnowledgeChunkDTO> records = pageResult.getRecords().stream().map(this::toChunkDTO).collect(Collectors.toList());
        return PageVO.of(pageResult.getTotal(), records, (long) page, (long) size);
    }

    /**
     * 跨知识库全局检索
     */
    public PageVO<KnowledgeChunkDTO> searchAllChunks(String keyword, int page, int size) {
        Long userId = ThreadLocalUtil.getUserId();
        List<KnowledgeLibrary> libs = knowledgeLibraryRepository.selectList(
                new LambdaQueryWrapper<KnowledgeLibrary>().eq(KnowledgeLibrary::getUserId, userId));
        List<Long> libraryIds = libs.stream().map(KnowledgeLibrary::getId).collect(Collectors.toList());
        if (libraryIds.isEmpty()) {
            return PageVO.of(0L, List.of(), (long) page, (long) size);
        }
        LambdaQueryWrapper<KnowledgeChunk> wrapper = new LambdaQueryWrapper<>();
        wrapper.in(KnowledgeChunk::getLibraryId, libraryIds);
        if (keyword != null && !keyword.isBlank()) {
            wrapper.like(KnowledgeChunk::getContent, keyword);
        }
        wrapper.orderByAsc(KnowledgeChunk::getLibraryId).orderByAsc(KnowledgeChunk::getChunkOrder);

        IPage<KnowledgeChunk> pageResult = knowledgeChunkRepository.selectPage(new Page<>(page, size), wrapper);
        List<KnowledgeChunkDTO> records = pageResult.getRecords().stream().map(this::toChunkDTO).collect(Collectors.toList());
        return PageVO.of(pageResult.getTotal(), records, (long) page, (long) size);
    }

    // ==================== DTO 转换 ====================

    private KnowledgeLibraryDTO toDTO(KnowledgeLibrary entity) {
        KnowledgeLibraryDTO dto = new KnowledgeLibraryDTO();
        BeanUtils.copyProperties(entity, dto);
        return dto;
    }

    private KnowledgeChunkDTO toChunkDTO(KnowledgeChunk entity) {
        KnowledgeChunkDTO dto = new KnowledgeChunkDTO();
        BeanUtils.copyProperties(entity, dto);
        return dto;
    }
}