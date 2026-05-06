package com.starrynight.starrynight.system.bookstore.support;

/** 目录页解析得到的章节链接（相对或绝对 href + 锚文本） */
public record BookstoreTocLink(String href, String text) {}
