package com.starrynight.starrynight.services.document;

import org.apache.pdfbox.Loader;
import org.apache.pdfbox.pdmodel.PDDocument;
import org.apache.pdfbox.text.PDFTextStripper;
import org.apache.commons.text.StringEscapeUtils;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Service;

import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.List;
import java.util.regex.Pattern;
import java.util.zip.ZipEntry;
import java.util.zip.ZipInputStream;

@Service
public class DocumentParserService {

    private static final Logger log = LoggerFactory.getLogger(DocumentParserService.class);

    private static final int DEFAULT_CHUNK_SIZE = 500;
    private static final int DEFAULT_CHUNK_OVERLAP = 50;

    public record ParseResult(
            String fullText,
            List<Chunk> chunks,
            String fileName,
            String fileType,
            int totalPages
    ) {}

    public record Chunk(
            int index,
            String content,
            int startPos,
            int endPos,
            String metadata
    ) {}

    public ParseResult parseDocument(InputStream inputStream, String fileName, String fileType) throws IOException {
        return parseDocument(inputStream, fileName, fileType, DEFAULT_CHUNK_SIZE, DEFAULT_CHUNK_OVERLAP);
    }

    public ParseResult parseDocument(InputStream inputStream, String fileName, String fileType,
                                    int chunkSize, int chunkOverlap) throws IOException {
        String content;
        int totalPages = 0;

        if (isPdfFile(fileType, fileName)) {
            content = parsePdf(inputStream);
            totalPages = getPdfPageCount(new ByteArrayInputStream(inputStream.readAllBytes()));
            inputStream.close();
        } else if (isTxtFile(fileType, fileName)) {
            content = parseTxt(inputStream);
        } else if (isEpubFile(fileType, fileName)) {
            content = parseEpub(inputStream);
        } else {
            throw new IllegalArgumentException(
                "Unsupported file type: " + fileType + ". Supported types: PDF, TXT, EPUB"
            );
        }

        content = cleanText(content);

        List<Chunk> chunks = chunkText(content, chunkSize, chunkOverlap);

        return new ParseResult(content, chunks, fileName, fileType, totalPages);
    }

    private String parsePdf(InputStream inputStream) throws IOException {
        byte[] pdfBytes = inputStream.readAllBytes();
        try (PDDocument document = Loader.loadPDF(pdfBytes)) {
            PDFTextStripper stripper = new PDFTextStripper();
            stripper.setSortByPosition(true);
            return stripper.getText(document);
        }
    }

    private int getPdfPageCount(InputStream inputStream) throws IOException {
        byte[] pdfBytes = inputStream.readAllBytes();
        try (PDDocument document = Loader.loadPDF(pdfBytes)) {
            return document.getNumberOfPages();
        }
    }

    private String parseTxt(InputStream inputStream) throws IOException {
        return new String(inputStream.readAllBytes(), StandardCharsets.UTF_8);
    }

    private String parseEpub(InputStream inputStream) throws IOException {
        StringBuilder content = new StringBuilder();
        byte[] bytes = inputStream.readAllBytes();

        try (ZipInputStream zis = new ZipInputStream(new ByteArrayInputStream(bytes))) {
            ZipEntry entry;
            while ((entry = zis.getNextEntry()) != null) {
                if (!entry.isDirectory() && isHtmlOrXmlFile(entry.getName())) {
                    String html = new String(zis.readAllBytes(), StandardCharsets.UTF_8);
                    content.append(extractTextFromHtml(html)).append("\n\n");
                }
                zis.closeEntry();
            }
        }

        return content.toString();
    }

    private String extractTextFromHtml(String html) {
        html = html.replaceAll("(?is)<script[^>]*>.*?</script>", "");
        html = html.replaceAll("(?is)<style[^>]*>.*?</style>", "");
        html = html.replaceAll("(?is)<[^>]+>", " ");
        html = html.replaceAll("&nbsp;", " ");
        html = html.replaceAll("&lt;", "<");
        html = html.replaceAll("&gt;", ">");
        html = html.replaceAll("&amp;", "&");
        html = html.replaceAll("&quot;", "\"");
        html = html.replaceAll("&#\\d+;", "");
        html = html.replaceAll("\\s+", " ");
        return html.trim();
    }

    private boolean isHtmlOrXmlFile(String fileName) {
        String lower = fileName.toLowerCase();
        return lower.endsWith(".html") || lower.endsWith(".xhtml") ||
               lower.endsWith(".htm") || lower.endsWith(".xml");
    }

    private String cleanText(String text) {
        if (text == null) return "";

        text = text.replaceAll("\\r\\n", "\n")
                   .replaceAll("\\r", "\n");

        text = text.replaceAll("[ \\t]+", " ");

        text = text.replaceAll("(?m)^[ \\t]+", "");

        text = text.replaceAll("(?m)\\n{3,}", "\n\n");

        text = StringEscapeUtils.escapeHtml4(text);

        return text.trim();
    }

    private List<Chunk> chunkText(String text, int chunkSize, int chunkOverlap) {
        List<Chunk> chunks = new ArrayList<>();

        if (text == null || text.isEmpty()) {
            return chunks;
        }

        String[] paragraphs = text.split("\n\n");

        StringBuilder currentChunk = new StringBuilder();
        int currentSize = 0;
        int chunkIndex = 0;
        int startPos = 0;

        for (String paragraph : paragraphs) {
            paragraph = paragraph.trim();
            if (paragraph.isEmpty()) continue;

            int paragraphSize = paragraph.length();

            if (currentSize + paragraphSize + 2 > chunkSize && currentSize > 0) {
                chunks.add(new Chunk(
                        chunkIndex,
                        currentChunk.toString().trim(),
                        startPos,
                        startPos + currentChunk.length(),
                        ""
                ));

                chunkIndex++;

                String overlapText = currentChunk.toString();
                int overlapStart = Math.max(0, overlapText.length() - chunkOverlap);
                currentChunk = new StringBuilder(overlapText.substring(overlapStart));
                startPos = startPos + overlapText.length() - chunkOverlap;
                currentSize = currentChunk.length();
            }

            if (currentSize > 0) {
                currentChunk.append("\n\n");
                currentSize += 2;
            }

            currentChunk.append(paragraph);
            currentSize += paragraphSize;
        }

        if (currentChunk.length() > 0) {
            chunks.add(new Chunk(
                    chunkIndex,
                    currentChunk.toString().trim(),
                    startPos,
                    startPos + currentChunk.length(),
                    ""
            ));
        }

        return chunks;
    }

    public String extractTextByPage(InputStream inputStream, int startPage, int endPage) throws IOException {
        if (!isPdfFile("", "")) {
            throw new IllegalArgumentException("Page extraction is only supported for PDF files");
        }

        try (PDDocument document = Loader.loadPDF(inputStream.readAllBytes())) {
            PDFTextStripper stripper = new PDFTextStripper();
            stripper.setStartPage(startPage);
            stripper.setEndPage(endPage);
            return stripper.getText(document);
        }
    }

    public String searchInDocument(String text, String keyword) {
        if (text == null || keyword == null) {
            return null;
        }

        Pattern pattern = Pattern.compile(Pattern.quote(keyword), Pattern.CASE_INSENSITIVE);
        java.util.regex.Matcher matcher = pattern.matcher(text);

        StringBuilder context = new StringBuilder();
        int matches = 0;
        int contextWindow = 100;

        while (matcher.find() && matches < 10) {
            int start = Math.max(0, matcher.start() - contextWindow);
            int end = Math.min(text.length(), matcher.end() + contextWindow);

            String snippet = text.substring(start, end);
            snippet = (start > 0 ? "..." : "") + snippet + (end < text.length() ? "..." : "");

            context.append("Match ").append(++matches).append(":\n").append(snippet).append("\n\n");
        }

        return context.length() > 0 ? context.toString() : null;
    }

    private boolean isPdfFile(String fileType, String fileName) {
        if (fileType != null && fileType.contains("pdf")) {
            return true;
        }
        if (fileName != null && fileName.toLowerCase().endsWith(".pdf")) {
            return true;
        }
        return false;
    }

    private boolean isTxtFile(String fileType, String fileName) {
        if (fileType != null && (fileType.contains("text") || fileType.contains("plain"))) {
            return true;
        }
        if (fileName != null) {
            String lower = fileName.toLowerCase();
            return lower.endsWith(".txt") || lower.endsWith(".md") ||
                   lower.endsWith(".text") || lower.endsWith(".log");
        }
        return false;
    }

    private boolean isEpubFile(String fileType, String fileName) {
        if (fileType != null && fileType.contains("epub")) {
            return true;
        }
        if (fileName != null && fileName.toLowerCase().endsWith(".epub")) {
            return true;
        }
        return false;
    }
}
