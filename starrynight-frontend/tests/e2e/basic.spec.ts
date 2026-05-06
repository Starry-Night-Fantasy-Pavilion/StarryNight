import { test, expect } from '@playwright/test';

test.describe('用户认证流程', () => {
  test('用户注册和登录', async ({ page }) => {
    await page.goto('/');

    await page.click('text=登录');
    await page.click('text=注册账号');

    const timestamp = Date.now();
    await page.fill('input[placeholder="用户名"]', `testuser_${timestamp}`);
    await page.fill('input[placeholder="邮箱"]', `test_${timestamp}@example.com`);
    await page.fill('input[placeholder="密码"]', 'Test123456');
    await page.fill('input[placeholder="确认密码"]', 'Test123456');

    await page.click('button:has-text("注册")');

    await expect(page.locator('text=注册成功')).toBeVisible({ timeout: 10000 });
  });
});

test.describe('作品管理', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
    await page.click('text=登录');
    await page.fill('input[placeholder="用户名"]', 'testuser');
    await page.fill('input[placeholder="密码"]', 'Test123456');
    await page.click('button:has-text("登录")');
    await page.waitForURL('**/');
  });

  test('创建新作品', async ({ page }) => {
    await page.click('text=作者中心');
    await page.click('text=创建新作品');

    await page.fill('input[placeholder="作品标题"]', '测试小说_' + Date.now());
    await page.fill('textarea[placeholder="简介"]', '这是一本测试小说');
    await page.selectOption('select', { label: '都市' });

    await page.click('button:has-text("创建")');
    await expect(page.locator('text=创建成功')).toBeVisible({ timeout: 10000 });
  });
});