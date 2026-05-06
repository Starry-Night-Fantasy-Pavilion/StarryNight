package com.starrynight.engine.token;

/**
 * 简单Tokenizer实现：
 * - 中文：每个汉字计为1个Token
 * - 英文单词：按空格分隔，每个单词计为1个Token
 * - 标点符号：单独计算
 *
 * 简化实现适用于演示和轻量级场景
 */
public class SimpleTokenizer implements Tokenizer {

    private static final int CHINESE_CHAR_WEIGHT = 1;
    private static final int ENGLISH_WORD_WEIGHT = 1;
    private static final int PUNCTUATION_WEIGHT = 1;

    @Override
    public int countTokens(String text) {
        if (text == null || text.isBlank()) {
            return 0;
        }

        int count = 0;
        int length = text.length();

        for (int i = 0; i < length; i++) {
            char c = text.charAt(i);

            if (isChinese(c)) {
                count += CHINESE_CHAR_WEIGHT;
            } else if (isEnglishLetter(c)) {
                int wordEnd = i;
                while (wordEnd < length && isEnglishLetter(text.charAt(wordEnd))) {
                    wordEnd++;
                }
                count += ENGLISH_WORD_WEIGHT;
                i = wordEnd - 1;
            } else if (isPunctuation(c)) {
                count += PUNCTUATION_WEIGHT;
            }
        }

        return count;
    }

    @Override
    public boolean exceedsLimit(String text, int maxTokens) {
        return countTokens(text) > maxTokens;
    }

    @Override
    public String truncate(String text, int maxTokens) {
        if (text == null || text.isBlank()) {
            return text;
        }

        int currentTokens = 0;
        StringBuilder result = new StringBuilder();
        int length = text.length();

        for (int i = 0; i < length; i++) {
            char c = text.charAt(i);
            int tokenCost;

            if (isChinese(c)) {
                tokenCost = CHINESE_CHAR_WEIGHT;
            } else if (isEnglishLetter(c)) {
                int wordEnd = i;
                while (wordEnd < length && isEnglishLetter(text.charAt(wordEnd))) {
                    wordEnd++;
                }
                tokenCost = ENGLISH_WORD_WEIGHT;
                String word = text.substring(i, wordEnd);
                result.append(word);
                i = wordEnd - 1;
                if (currentTokens + tokenCost <= maxTokens) {
                    currentTokens += tokenCost;
                } else {
                    return result.toString();
                }
                continue;
            } else if (isPunctuation(c)) {
                tokenCost = PUNCTUATION_WEIGHT;
            } else {
                tokenCost = 1;
            }

            if (currentTokens + tokenCost > maxTokens) {
                break;
            }

            result.append(c);
            currentTokens += tokenCost;
        }

        if (currentTokens < countTokens(text) && !result.toString().endsWith("...")) {
            result.append("...");
        }

        return result.toString();
    }

    private boolean isChinese(char c) {
        return Character.UnicodeBlock.of(c) == Character.UnicodeBlock.CJK_UNIFIED_IDEOGRAPHS
                || Character.UnicodeBlock.of(c) == Character.UnicodeBlock.CJK_COMPATIBILITY_IDEOGRAPHS
                || Character.UnicodeBlock.of(c) == Character.UnicodeBlock.CJK_UNIFIED_IDEOGRAPHS_EXTENSION_A
                || Character.UnicodeBlock.of(c) == Character.UnicodeBlock.CJK_UNIFIED_IDEOGRAPHS_EXTENSION_B;
    }

    private boolean isEnglishLetter(char c) {
        return (c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z');
    }

    private boolean isPunctuation(char c) {
        return !isChinese(c) && !isEnglishLetter(c)
                && !Character.isDigit(c)
                && !Character.isWhitespace(c);
    }
}
