package com.starrynight.starrynight.api;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.system.auth.dto.LoginRequest;
import com.starrynight.starrynight.system.auth.dto.RegisterRequest;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.autoconfigure.web.servlet.AutoConfigureMockMvc;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.http.MediaType;
import org.springframework.test.context.ActiveProfiles;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MvcResult;

import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.*;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
public class AuthApiIntegrationTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private ObjectMapper objectMapper;

    private String authToken;

    @BeforeEach
    public void setup() throws Exception {
        String uniqueUsername = "testuser_" + System.currentTimeMillis();
        RegisterRequest registerRequest = new RegisterRequest();
        registerRequest.setUsername(uniqueUsername);
        registerRequest.setPassword("Test123456");
        registerRequest.setEmail(uniqueUsername + "@test.com");

        mockMvc.perform(post("/api/auth/register")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content(objectMapper.writeValueAsString(registerRequest)))
                .andExpect(status().isOk());

        LoginRequest loginRequest = new LoginRequest();
        loginRequest.setUsername(uniqueUsername);
        loginRequest.setPassword("Test123456");

        MvcResult loginResult = mockMvc.perform(post("/api/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content(objectMapper.writeValueAsString(loginRequest)))
                .andExpect(status().isOk())
                .andReturn();

        String response = loginResult.getResponse().getContentAsString();
        authToken = objectMapper.readTree(response).get("data").get("token").asText();
    }

    @Test
    public void testHealthEndpoint() throws Exception {
        mockMvc.perform(get("/api/auth/health"))
                .andExpect(status().isOk());
    }

    @Test
    public void testNovelCrudOperations() throws Exception {
        String novelJson = """
            {
                "title": "测试小说",
                "description": "这是一本测试小说",
                "genre": "fantasy",
                "wordCountGoal": 500000
            }
            """;

        MvcResult createResult = mockMvc.perform(post("/api/novels")
                        .header("Authorization", "Bearer " + authToken)
                        .contentType(MediaType.APPLICATION_JSON)
                        .content(novelJson))
                .andExpect(status().isOk())
                .andReturn();

        Long novelId = objectMapper.readTree(createResult.getResponse().getContentAsString())
                .get("data").get("id").asLong();

        mockMvc.perform(get("/api/novels/" + novelId)
                        .header("Authorization", "Bearer " + authToken))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.data.title").value("测试小说"));

        String updateJson = """
            {
                "title": "更新后的小说标题",
                "description": "更新后的描述"
            }
            """;

        mockMvc.perform(put("/api/novels/" + novelId)
                        .header("Authorization", "Bearer " + authToken)
                        .contentType(MediaType.APPLICATION_JSON)
                        .content(updateJson))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.data.title").value("更新后的小说标题"));
    }

    @Test
    public void testXssProtection() throws Exception {
        String maliciousJson = """
            {
                "title": "<script>alert('xss')</script>测试",
                "description": "javascript:alert('xss')"
            }
            """;

        MvcResult result = mockMvc.perform(post("/api/novels")
                        .header("Authorization", "Bearer " + authToken)
                        .contentType(MediaType.APPLICATION_JSON)
                        .content(maliciousJson))
                .andExpect(status().isOk());

        String response = result.getResponse().getContentAsString();
        assert !response.contains("<script>");
        assert !response.contains("javascript:");
    }

    @Test
    public void testRateLimiting() throws Exception {
        for (int i = 0; i < 65; i++) {
            mockMvc.perform(get("/api/novels/list")
                            .header("Authorization", "Bearer " + authToken));
        }

        mockMvc.perform(get("/api/novels/list")
                        .header("Authorization", "Bearer " + authToken))
                .andExpect(status().isOk());
    }
}