package com.starrynight.engine.memcore;

import com.starrynight.engine.vector.EntryStatus;
import com.starrynight.engine.vector.VectorEntry;
import com.starrynight.engine.vector.VectorMetadata;
import com.starrynight.engine.vector.store.VectorStore;

import java.time.Instant;
import java.util.Objects;
import java.util.UUID;

/**
 * MemCore：向量条目版本控制/快照管理/过期标记（核心骨架实现）。
 */
public class MemCoreManager {

    private final VectorStore vectorStore;

    public MemCoreManager(VectorStore vectorStore) {
        this.vectorStore = vectorStore;
    }

    public VectorEntry upsert(String collection, VectorEntry entry) {
        if (entry.getId() == null || entry.getId().isBlank()) {
            entry.setId(UUID.randomUUID().toString());
        }
        if (entry.getMetadata() == null) {
            entry.setMetadata(new VectorMetadata());
        }
        VectorMetadata md = entry.getMetadata();
        if (md.getCreatedAt() == null) {
            md.setCreatedAt(Instant.now());
        }
        md.setUpdatedAt(Instant.now());
        if (md.getStatus() == null) {
            md.setStatus(EntryStatus.ACTIVE);
        }
        vectorStore.upsert(collection, entry);
        return entry;
    }

    public void supersede(String collection, String id) {
        vectorStore.get(collection, id).ifPresent(e -> {
            if (e.getMetadata() == null) {
                e.setMetadata(new VectorMetadata());
            }
            e.getMetadata().setStatus(EntryStatus.SUPERSEDED);
            e.getMetadata().setUpdatedAt(Instant.now());
            vectorStore.upsert(collection, e);
        });
    }

    public VectorEntry snapshot(String collection, VectorEntry current) {
        Objects.requireNonNull(current, "current entry required");
        VectorEntry snap = new VectorEntry();
        snap.setId(UUID.randomUUID().toString());
        snap.setChunk(current.getChunk());
        snap.setDenseVector(current.getDenseVector());
        snap.setSparseVector(current.getSparseVector());

        VectorMetadata md = current.getMetadata() == null ? new VectorMetadata() : current.getMetadata();
        VectorMetadata smd = new VectorMetadata();
        smd.setType(md.getType());
        smd.setSubType(md.getSubType());
        smd.setEntityIds(md.getEntityIds());
        smd.setNarrativeTimestamp(md.getNarrativeTimestamp());
        smd.setImportanceWeight(md.getImportanceWeight());
        smd.setTags(md.getTags());
        smd.setForeshadowingId(md.getForeshadowingId());
        smd.setStatus(EntryStatus.HISTORICAL_SNAPSHOT);
        smd.setCreatedAt(Instant.now());
        smd.setUpdatedAt(Instant.now());
        snap.setMetadata(smd);

        vectorStore.upsert(collection, snap);
        return snap;
    }
}

