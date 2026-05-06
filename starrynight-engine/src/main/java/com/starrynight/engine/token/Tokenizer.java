package com.starrynight.engine.token;

/**
 * Tokenizer接口：用于计算文本的Token数量
 */
public interface Tokenizer {

    /**
     * 计算文本的Token数量
     * @param text 输入文本
     * @return Token数量
     */
    int countTokens(String text);

    /**
     * 检查文本是否超过指定Token限制
     * @param text 输入文本
     * @param maxTokens 最大Token数
     * @return true表示超过限制
     */
    boolean exceedsLimit(String text, int maxTokens);

    /**
     * 截断文本到指定Token数量
     * @param text 输入文本
     * @param maxTokens 最大Token数
     * @return 截断后的文本
     */
    String truncate(String text, int maxTokens);
}
