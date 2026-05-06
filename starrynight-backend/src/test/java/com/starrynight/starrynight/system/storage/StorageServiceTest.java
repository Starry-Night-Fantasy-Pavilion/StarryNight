package com.starrynight.starrynight.system.storage;

import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import io.minio.GetPresignedObjectUrlArgs;
import io.minio.MinioClient;
import io.minio.PutObjectArgs;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.extension.ExtendWith;
import org.mockito.ArgumentCaptor;
import org.mockito.Mock;
import org.mockito.junit.jupiter.MockitoExtension;
import org.springframework.mock.web.MockMultipartFile;
import org.springframework.test.util.ReflectionTestUtils;

import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.ArgumentMatchers.anyString;
import static org.mockito.Mockito.*;

@ExtendWith(MockitoExtension.class)
class StorageServiceTest {

    @Mock
    private RuntimeConfigService runtimeConfigService;

    @Mock
    private MinioClient minioClient;

    private StorageService storageService;

    @BeforeEach
    void setUp() {
        lenient().when(runtimeConfigService.getProperty(anyString())).thenAnswer(invocation -> {
            String key = invocation.getArgument(0);
            return switch (key) {
                case "storage.minio.endpoint" -> "http://localhost:9000";
                case "storage.minio.access-key" -> "access";
                case "storage.minio.secret-key" -> "secret";
                case "storage.minio.bucket" -> "test-bucket";
                default -> null;
            };
        });
        lenient().when(runtimeConfigService.getString("storage.minio.bucket", "starrynight")).thenReturn("test-bucket");

        storageService = new StorageService(runtimeConfigService);
        String fp = String.join("\u0001", "http://localhost:9000", "access", "secret", "test-bucket");
        ReflectionTestUtils.setField(storageService, "minioClient", minioClient);
        ReflectionTestUtils.setField(storageService, "minioFingerprint", fp);
    }

    @Test
    void uploadFile_shouldSucceed_whenFileIsValid() throws Exception {
        MockMultipartFile file = new MockMultipartFile(
                "file",
                "hello.txt",
                "text/plain",
                "Hello, World!".getBytes()
        );
        String path = "test-path/";
        String expectedObjectName = "test-path/[a-f0-9-]{36}\\.txt";
        String mockUrl = "http://mock-url/test-bucket/test-path/some-uuid.txt";

        lenient().when(minioClient.putObject(any(PutObjectArgs.class))).thenReturn(null);
        when(minioClient.getPresignedObjectUrl(any(GetPresignedObjectUrlArgs.class))).thenReturn(mockUrl);

        String resultUrl = storageService.uploadFile(file, path);

        assertNotNull(resultUrl);
        assertEquals(mockUrl, resultUrl);

        ArgumentCaptor<PutObjectArgs> putObjectArgsCaptor = ArgumentCaptor.forClass(PutObjectArgs.class);
        verify(minioClient).putObject(putObjectArgsCaptor.capture());

        PutObjectArgs capturedArgs = putObjectArgsCaptor.getValue();
        assertEquals("test-bucket", capturedArgs.bucket());
        assertTrue(capturedArgs.object().matches(expectedObjectName));
        assertEquals(file.getSize(), capturedArgs.stream().available());
        assertEquals(file.getContentType(), capturedArgs.contentType());

        verify(minioClient).getPresignedObjectUrl(any(GetPresignedObjectUrlArgs.class));
    }

    @Test
    void uploadFile_shouldThrowException_whenFileIsEmpty() {
        MockMultipartFile file = new MockMultipartFile(
                "file",
                "empty.txt",
                "text/plain",
                new byte[0]
        );

        Exception exception = assertThrows(com.starrynight.starrynight.framework.common.exception.BusinessException.class, () -> {
            storageService.uploadFile(file, "test-path/");
        });

        assertEquals("File is empty", exception.getMessage());
        verify(minioClient, never()).putObject(any());
    }
}
