package com.starrynight.starrynight.system.bookstore.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreBookPublicDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreChapterReadDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreChapterTocItem;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreHomeDTO;
import com.starrynight.starrynight.system.bookstore.service.BookstoreChapterService;
import com.starrynight.starrynight.system.bookstore.service.BookstoreService;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;

@RestController
@RequestMapping("/api/bookstore")
@RequiredArgsConstructor
public class BookstorePublicController {

    private final BookstoreService bookstoreService;
    private final BookstoreChapterService bookstoreChapterService;

    @GetMapping("/home")
    public ResponseVO<BookstoreHomeDTO> home() {
        return ResponseVO.success(bookstoreService.home());
    }

    @GetMapping("/books/{id}")
    public ResponseVO<BookstoreBookPublicDTO> book(@PathVariable Long id) {
        return ResponseVO.success(bookstoreService.getPublicBook(id));
    }

    @GetMapping("/books/{bookId}/chapters")
    public ResponseVO<List<BookstoreChapterTocItem>> chapters(@PathVariable Long bookId) {
        return ResponseVO.success(bookstoreChapterService.listTocPublic(bookId));
    }

    @GetMapping("/books/{bookId}/read/{chapterNo}")
    public ResponseVO<BookstoreChapterReadDTO> readChapter(
            @PathVariable Long bookId,
            @PathVariable int chapterNo) {
        return ResponseVO.success(bookstoreChapterService.readPublic(bookId, chapterNo));
    }
}
