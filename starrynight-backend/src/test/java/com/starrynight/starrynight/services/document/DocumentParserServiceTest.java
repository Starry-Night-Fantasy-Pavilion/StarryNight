package com.starrynight.starrynight.services.document;

import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;

import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.nio.charset.StandardCharsets;

import static org.junit.jupiter.api.Assertions.*;

class DocumentParserServiceTest {

    private DocumentParserService documentParserService;

    @BeforeEach
    void setUp() {
        documentParserService = new DocumentParserService();
    }

    @Test
    void parseTxtDocument_shouldExtractText() throws IOException {
        String content = "这是第一段内容。\n\n这是第二段内容。\n\n这是第三段内容。";
        InputStream inputStream = new ByteArrayInputStream(content.getBytes(StandardCharsets.UTF_8));

        DocumentParserService.ParseResult result = documentParserService.parseDocument(
                inputStream, "test.txt", "text/plain");

        assertNotNull(result);
        assertEquals("test.txt", result.fileName());
        assertTrue(result.fullText().contains("第一段"));
        assertTrue(result.fullText().contains("第二段"));
        assertTrue(result.fullText().contains("第三段"));
        assertFalse(result.chunks().isEmpty());
    }

    @Test
    void parseTxtDocument_shouldCleanText() throws IOException {
        String content = "这是内容   带有多余空格\r\n\r\n\r\n换行过多";
        InputStream inputStream = new ByteArrayInputStream(content.getBytes(StandardCharsets.UTF_8));

        DocumentParserService.ParseResult result = documentParserService.parseDocument(
                inputStream, "test.txt", "text/plain");

        assertNotNull(result);
        assertFalse(result.fullText().contains("   "));
    }

    @Test
    void parseDocument_shouldChunkText() throws IOException {
        StringBuilder sb = new StringBuilder();
        for (int i = 0; i < 10; i++) {
            sb.append("这是第").append(i).append("段内容，包含一些文字。\n\n");
        }
        String content = sb.toString();
        InputStream inputStream = new ByteArrayInputStream(content.getBytes(StandardCharsets.UTF_8));

        DocumentParserService.ParseResult result = documentParserService.parseDocument(
                inputStream, "test.txt", "text/plain", 100, 20);

        assertNotNull(result);
        assertFalse(result.chunks().isEmpty());
        assertTrue(result.chunks().size() > 1);
    }

    @Test
    void parseDocument_shouldHandleEmptyContent() throws IOException {
        InputStream inputStream = new ByteArrayInputStream("".getBytes(StandardCharsets.UTF_8));

        DocumentParserService.ParseResult result = documentParserService.parseDocument(
                inputStream, "empty.txt", "text/plain");

        assertNotNull(result);
        assertTrue(result.fullText().isEmpty());
        assertTrue(result.chunks().isEmpty());
    }

    @Test
    void parseDocument_shouldThrowExceptionForUnsupportedFileType() {
        InputStream inputStream = new ByteArrayInputStream("test".getBytes(StandardCharsets.UTF_8));

        assertThrows(IllegalArgumentException.class, () -> {
            documentParserService.parseDocument(inputStream, "test.xyz", "application/xyz", 500, 50);
        });
    }

    @Test
    void searchInDocument_shouldFindMatches() throws IOException {
        String content = "这是一段关于武侠的内容。\n\n另一段关于武侠江湖的内容。\n\n还有一段关于修仙的内容。";
        InputStream inputStream = new ByteArrayInputStream(content.getBytes(StandardCharsets.UTF_8));

        DocumentParserService.ParseResult result = documentParserService.parseDocument(
                inputStream, "test.txt", "text/plain");

        String searchResult = documentParserService.searchInDocument(result.fullText(), "武侠");

        assertNotNull(searchResult);
        assertTrue(searchResult.contains("武侠"));
        assertTrue(searchResult.contains("Match"));
    }

    @Test
    void searchInDocument_shouldReturnNullForNoMatch() throws IOException {
        String content = "这是正常的内容。";
        InputStream inputStream = new ByteArrayInputStream(content.getBytes(StandardCharsets.UTF_8));

        DocumentParserService.ParseResult result = documentParserService.parseDocument(
                inputStream, "test.txt", "text/plain");

        String searchResult = documentParserService.searchInDocument(result.fullText(), "不存在的关键词");

        assertNull(searchResult);
    }

    @Test
    void chunkText_shouldPreserveChunkIndices() throws IOException {
        String content = "第一段内容。\n\n第二段内容。\n\n第三段内容。";
        InputStream inputStream = new ByteArrayInputStream(content.getBytes(StandardCharsets.UTF_8));

        DocumentParserService.ParseResult result = documentParserService.parseDocument(
                inputStream, "test.txt", "text/plain", 100, 20);

        for (DocumentParserService.Chunk chunk : result.chunks()) {
            assertTrue(chunk.index() >= 0);
            assertNotNull(chunk.content());
            assertTrue(chunk.startPos() >= 0);
            assertTrue(chunk.endPos() >= chunk.startPos());
        }
    }

    @Test
    void parseDocument_shouldHandleChineseCharacters() throws IOException {
        String content = "这是一个中文测试文档。包含中文标点符号，。、！";
        InputStream inputStream = new ByteArrayInputStream(content.getBytes(StandardCharsets.UTF_8));

        DocumentParserService.ParseResult result = documentParserService.parseDocument(
                inputStream, "chinese.txt", "text/plain");

        assertNotNull(result);
        assertTrue(result.fullText().contains("中文"));
        assertTrue(result.fullText().contains("标点符号"));
    }

    @Test
    void parseDocument_shouldHandlePdfFileType() throws IOException {
        InputStream inputStream = new ByteArrayInputStream("test".getBytes(StandardCharsets.UTF_8));

        assertThrows(Exception.class, () -> {
            documentParserService.parseDocument(inputStream, "test.pdf", "application/pdf", 500, 50);
        });
    }
}
