package com.starrynight.starrynight.system.bookstore.controller;

import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreBookDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreConfigDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLegadoImportResultDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLegadoImportUrlDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLegadoSourceAdminDTO;
import com.starrynight.starrynight.system.bookstore.service.BookstoreLegadoSourceService;
import com.starrynight.starrynight.system.bookstore.service.BookstoreService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.http.MediaType;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/admin/bookstore")
@PreAuthorize("hasRole('ADMIN')")
@RequiredArgsConstructor
public class AdminBookstoreController {

    private final BookstoreService bookstoreService;
    private final BookstoreLegadoSourceService bookstoreLegadoSourceService;

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

    /** Legado 书源集合：从 URL 拉取 JSON 数组并入库 */
    @PostMapping("/legado-sources/import-url")
    public ResponseVO<BookstoreLegadoImportResultDTO> importLegadoFromUrl(@RequestBody BookstoreLegadoImportUrlDTO dto) {
        return ResponseVO.success(bookstoreLegadoSourceService.importFromUrl(dto.getUrl()));
    }

    /** 直接提交书源数组 JSON（与阅读 3.0 导出格式一致） */
    @PostMapping(value = "/legado-sources/import-json", consumes = MediaType.APPLICATION_JSON_VALUE)
    public ResponseVO<BookstoreLegadoImportResultDTO> importLegadoJson(@RequestBody String rawJson) {
        return ResponseVO.success(bookstoreLegadoSourceService.importJsonArray(rawJson));
    }

    @GetMapping("/legado-sources/list")
    public ResponseVO<PageVO<BookstoreLegadoSourceAdminDTO>> listLegadoSources(
            @RequestParam(required = false) String keyword,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "10") int size) {
        return ResponseVO.success(bookstoreLegadoSourceService.pageAdmin(keyword, page, size));
    }

    @DeleteMapping("/legado-sources/{id}")
    public ResponseVO<Void> deleteLegadoSource(@PathVariable Long id) {
        bookstoreLegadoSourceService.delete(id);
        return ResponseVO.success();
    }

    @PutMapping("/legado-sources/{id}/enabled")
    public ResponseVO<Void> setLegadoSourceEnabled(@PathVariable Long id, @RequestParam boolean enabled) {
        bookstoreLegadoSourceService.setEnabled(id, enabled);
        return ResponseVO.success();
    }
}
