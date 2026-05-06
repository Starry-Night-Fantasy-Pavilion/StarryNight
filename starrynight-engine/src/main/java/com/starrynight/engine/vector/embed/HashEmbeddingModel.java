package com.starrynight.engine.vector.embed;

import java.nio.charset.StandardCharsets;
import java.util.zip.CRC32;

public class HashEmbeddingModel implements EmbeddingModel {

    private final int dims;

    public HashEmbeddingModel(int dims) {
        this.dims = Math.max(16, dims);
    }

    @Override
    public float[] embed(String text) {
        float[] v = new float[dims];
        if (text == null || text.isBlank()) {
            return v;
        }
        String[] tokens = text.toLowerCase().split("\\s+");
        for (String t : tokens) {
            if (t.isBlank()) continue;
            int idx = (int) (crc32(t) % dims);
            v[idx] += 1.0f;
        }
        normalize(v);
        return v;
    }

    private long crc32(String s) {
        CRC32 crc = new CRC32();
        crc.update(s.getBytes(StandardCharsets.UTF_8));
        return crc.getValue();
    }

    private void normalize(float[] v) {
        double sum = 0;
        for (float x : v) sum += x * x;
        double norm = Math.sqrt(sum);
        if (norm < 1e-9) return;
        for (int i = 0; i < v.length; i++) {
            v[i] = (float) (v[i] / norm);
        }
    }
}

