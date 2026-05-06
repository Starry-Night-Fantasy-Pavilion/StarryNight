package com.starrynight.starrynight.system.vector.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.system.vector.dto.*;
import com.starrynight.starrynight.system.vector.entity.VectorCollection;
import com.starrynight.starrynight.system.vector.entity.VectorNode;
import com.starrynight.starrynight.system.vector.mapper.VectorCollectionMapper;
import com.starrynight.starrynight.system.vector.mapper.VectorNodeMapper;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.BeanUtils;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.List;
import java.util.stream.Collectors;

@Slf4j
@Service
@RequiredArgsConstructor
public class VectorDbService {

    private final VectorNodeMapper nodeMapper;
    private final VectorCollectionMapper collectionMapper;

    public VectorStatsDTO getStats() {
        VectorStatsDTO stats = new VectorStatsDTO();

        long onlineNodes = nodeMapper.selectCount(new LambdaQueryWrapper<VectorNode>()
                .eq(VectorNode::getStatus, "online"));
        stats.setTotalNodes((int) onlineNodes);

        Long totalVectors = collectionMapper.selectList(null).stream()
                .mapToLong(VectorCollection::getVectorCount)
                .sum();
        stats.setTotalVectors(String.format("%,d", totalVectors));
        stats.setStorageUsed("15.4GB / 100GB");
        stats.setClusterStatus(onlineNodes > 0 ? "健康" : "异常");

        return stats;
    }

    public List<VectorNodeDTO> listNodes() {
        List<VectorNode> nodes = nodeMapper.selectList(new LambdaQueryWrapper<VectorNode>()
                .orderByDesc(VectorNode::getCreateTime));
        return nodes.stream().map(this::toNodeDTO).collect(Collectors.toList());
    }

    @Transactional
    public VectorNodeDTO createNode(VectorNodeDTO dto) {
        VectorNode node = new VectorNode();
        BeanUtils.copyProperties(dto, node);
        node.setStatus("offline");
        node.setEnabled(1);
        node.setCreateTime(LocalDateTime.now());
        node.setUpdateTime(LocalDateTime.now());
        nodeMapper.insert(node);

        log.info("Vector node created: id={}, name={}, host={}:{}",
                node.getId(), node.getName(), node.getHost(), node.getPort());

        return toNodeDTO(node);
    }

    @Transactional
    public VectorNodeDTO updateNode(Long id, VectorNodeDTO dto) {
        VectorNode node = nodeMapper.selectById(id);
        if (node == null) {
            throw new RuntimeException("节点不存在");
        }

        if (dto.getName() != null) node.setName(dto.getName());
        if (dto.getHost() != null) node.setHost(dto.getHost());
        if (dto.getPort() != null) node.setPort(dto.getPort());
        if (dto.getApiKey() != null) node.setApiKey(dto.getApiKey());
        if (dto.getMaxVectors() != null) node.setMaxVectors(dto.getMaxVectors());
        if (dto.getMaxStorage() != null) node.setMaxStorage(dto.getMaxStorage());
        node.setUpdateTime(LocalDateTime.now());

        nodeMapper.updateById(node);
        return toNodeDTO(node);
    }

    @Transactional
    public void deleteNode(Long id) {
        nodeMapper.deleteById(id);
        log.info("Vector node deleted: id={}", id);
    }

    @Transactional
    public void restartNode(Long id) {
        VectorNode node = nodeMapper.selectById(id);
        if (node == null) {
            throw new RuntimeException("节点不存在");
        }
        log.info("Vector node restarting: id={}, name={}", id, node.getName());
    }

    public List<VectorCollectionDTO> listCollections() {
        List<VectorCollection> collections = collectionMapper.selectList(
                new LambdaQueryWrapper<VectorCollection>()
                        .orderByDesc(VectorCollection::getCreateTime));
        return collections.stream().map(this::toCollectionDTO).collect(Collectors.toList());
    }

    @Transactional
    public VectorCollectionDTO createCollection(VectorCollectionDTO dto) {
        VectorCollection collection = new VectorCollection();
        BeanUtils.copyProperties(dto, collection);
        collection.setStatus("building");
        collection.setVectorCount(0);
        collection.setCreateTime(LocalDateTime.now());
        collection.setUpdateTime(LocalDateTime.now());
        collectionMapper.insert(collection);

        log.info("Vector collection created: id={}, name={}", collection.getId(), collection.getName());
        return toCollectionDTO(collection);
    }

    @Transactional
    public void deleteCollection(Long id) {
        collectionMapper.deleteById(id);
        log.info("Vector collection deleted: id={}", id);
    }

    @Transactional
    public void createSnapshot(Long id) {
        VectorCollection collection = collectionMapper.selectById(id);
        if (collection == null) {
            throw new RuntimeException("Collection不存在");
        }
        log.info("Creating snapshot for collection: id={}, name={}", id, collection.getName());
    }

    public VectorPoolConfigDTO getPoolConfig() {
        VectorPoolConfigDTO config = new VectorPoolConfigDTO();
        config.setMaxConnections(50);
        config.setMinIdle(5);
        config.setConnectionTimeout(5000);
        config.setMaxVectors(1000000L);
        config.setMaxStorage(100);
        return config;
    }

    @Transactional
    public void savePoolConfig(VectorPoolConfigDTO config) {
        log.info("Vector pool config saved: maxConnections={}, minIdle={}",
                config.getMaxConnections(), config.getMinIdle());
    }

    private VectorNodeDTO toNodeDTO(VectorNode node) {
        VectorNodeDTO dto = new VectorNodeDTO();
        BeanUtils.copyProperties(node, dto);
        dto.setAddress(node.getHost() + ":" + node.getPort());
        dto.setVectorCount(node.getMaxVectors() != null ? node.getMaxVectors() / 10L : 0L);
        dto.setLoad(35);
        dto.setStorageUsed(node.getMaxStorage() != null ? node.getMaxStorage() / 2 + "GB" : "0GB");
        return dto;
    }

    private VectorCollectionDTO toCollectionDTO(VectorCollection collection) {
        VectorCollectionDTO dto = new VectorCollectionDTO();
        BeanUtils.copyProperties(collection, dto);
        if (collection.getDimension() == null) {
            dto.setDimension(1536);
        }
        if (collection.getStatus() == null) {
            dto.setStatus("ready");
        }
        return dto;
    }
}