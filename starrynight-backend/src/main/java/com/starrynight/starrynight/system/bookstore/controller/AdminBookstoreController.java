package com.starrynight.starrynight.system.bookstore.controller;

import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreBookDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreConfigDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreChapterAdminRowDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreChapterMutateDTO;
import com.starrynight.starrynight.system.bookstore.entity.BookstoreChapter;
import com.starrynight.starrynight.system.bookstore.service.BookstoreChapterService;
import com.starrynight.starrynight.system.bookstore.service.BookstoreService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/admin/bookstore")
@PreAuthorize("hasRole('ADMIN')")
@RequiredArgsConstructor
public class AdminBookstoreController {

    private final BookstoreService bookstoreService;
    private final BookstoreChapterService bookstoreChapterService;

    @GetMapping("/config")
    public ResponseVO<BookstoreConfigDTO> getConfig() {
        return ResponseVO.success(bookstoreService.getConfigForAdmin());
    }

    @PutMapping("/config")
    public ResponseVO<Void> saveConfig(@RequestBody BookstoreConfigDTO dto) {
        bookstoreService.saveConfig(dto);
        return ResponseVO.success();
    }

    @GetMapping("/books/list")
    public ResponseVO<PageVO<BookstoreBookDTO>> listBooks(
            @RequestParam(required = false) String keyword,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "10") int size) {
        return ResponseVO.success(bookstoreService.pageBooks(keyword, page, size));
    }

    @GetMapping("/books/{id}")
    public ResponseVO<BookstoreBookDTO> getBook(@PathVariable Long id) {
        return ResponseVO.success(bookstoreService.getBookAdmin(id));
    }

    @PostMapping("/books")
    public ResponseVO<BookstoreBookDTO> createBook(@Valid @RequestBody BookstoreBookDTO dto) {
        return ResponseVO.success(bookstoreService.createBook(dto));
    }

    @PutMapping("/books/{id}")
    public ResponseVO<BookstoreBookDTO> updateBook(@PathVariable Long id, @Valid @RequestBody BookstoreBookDTO dto) {
        return ResponseVO.success(bookstoreService.updateBook(id, dto));
    }

    @DeleteMapping("/books/{id}")
    public ResponseVO<Void> deleteBook(@PathVariable Long id) {
        bookstoreService.deleteBook(id);
        return ResponseVO.success();
    }

    @GetMapping("/books/{bookId}/chapters")
    public ResponseVO<List<BookstoreChapterAdminRowDTO>> listChapters(@PathVariable Long bookId) {
        return ResponseVO.success(bookstoreChapterService.listAdminRows(bookId));
    }

    @GetMapping("/books/{bookId}/chapters/{chapterId}")
    public ResponseVO<BookstoreChapter> getChapter(
            @PathVariable Long bookId,
            @PathVariable Long chapterId) {
        return ResponseVO.success(bookstoreChapterService.getAdminDetail(bookId, chapterId));
    }

    @PostMapping("/books/{bookId}/chapters")
    public ResponseVO<BookstoreChapterAdminRowDTO> createChapter(
            @PathVariable Long bookId,
            @Valid @RequestBody BookstoreChapterMutateDTO dto) {
        return ResponseVO.success(bookstoreChapterService.create(bookId, dto));
    }

    @PutMapping("/books/{bookId}/chapters/{chapterId}")
    public ResponseVO<Void> updateChapter(
            @PathVariable Long bookId,
            @PathVariable Long chapterId,
            @Valid @RequestBody BookstoreChapterMutateDTO dto) {
        bookstoreChapterService.update(bookId, chapterId, dto);
        return ResponseVO.success();
    }

    @DeleteMapping("/books/{bookId}/chapters/{chapterId}")
    public ResponseVO<Void> deleteChapter(
            @PathVariable Long bookId,
            @PathVariable Long chapterId) {
        bookstoreChapterService.delete(bookId, chapterId);
        return ResponseVO.success();
    }
}
