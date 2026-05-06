package com.starrynight.starrynight.system.announcement.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.system.announcement.dto.AnnouncementDTO;
import com.starrynight.starrynight.system.announcement.entity.Announcement;
import com.starrynight.starrynight.system.announcement.repository.AnnouncementRepository;
import org.springframework.beans.BeanUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;
import java.util.stream.Collectors;

@Service
public class AnnouncementService {

    @Autowired
    private AnnouncementRepository announcementRepository;

    public List<AnnouncementDTO> list(Integer status) {
        LambdaQueryWrapper<Announcement> wrapper = new LambdaQueryWrapper<>();
        if (status != null) {
            wrapper.eq(Announcement::getStatus, status);
        }
        wrapper.orderByDesc(Announcement::getPublishTime).orderByDesc(Announcement::getCreateTime);
        return announcementRepository.selectList(wrapper).stream().map(this::toDTO).collect(Collectors.toList());
    }

    public AnnouncementDTO getById(Long id) {
        Announcement entity = announcementRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("Announcement not found");
        }
        return toDTO(entity);
    }

    @Transactional
    public AnnouncementDTO create(AnnouncementDTO dto) {
        Announcement entity = new Announcement();
        BeanUtils.copyProperties(dto, entity);
        announcementRepository.insert(entity);
        return toDTO(entity);
    }

    @Transactional
    public AnnouncementDTO update(Long id, AnnouncementDTO dto) {
        Announcement entity = announcementRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("Announcement not found");
        }
        entity.setTitle(dto.getTitle());
        entity.setContent(dto.getContent());
        entity.setStatus(dto.getStatus());
        if (dto.getPublishTime() != null) {
            entity.setPublishTime(dto.getPublishTime());
        }
        announcementRepository.updateById(entity);
        return toDTO(entity);
    }

    @Transactional
    public void delete(Long id) {
        Announcement entity = announcementRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("Announcement not found");
        }
        if (announcementRepository.deleteById(id) <= 0) {
            throw new BusinessException("Delete announcement failed");
        }
    }

    private AnnouncementDTO toDTO(Announcement entity) {
        AnnouncementDTO dto = new AnnouncementDTO();
        BeanUtils.copyProperties(entity, dto);
        return dto;
    }
}
