// tests/favorite.spec.js
const { test, expect } = require('@playwright/test');

test.describe('收藏功能', () => {
    let token;

    test.beforeAll(async ({ request }) => {
        const res = await request.post('/api/auth/login', {
            data: { username: 'admin', password: 'password' }
        });
        const body = await res.json();
        token = body.data.token;
    });

    test('收藏歌曲成功', async ({ request }) => {
        const res = await request.post('/api/favorite/1', {
            headers: { Authorization: `Bearer ${token}` }
        });
        expect(res.ok()).toBeTruthy();
        const body = await res.json();
        expect(body.code).toBe(0);
        expect(body.data.is_favorite).toBe(true);
    });

    test('取消收藏成功', async ({ request }) => {
        const res = await request.post('/api/favorite/1', {
            headers: { Authorization: `Bearer ${token}` }
        });
        expect(res.ok()).toBeTruthy();
        const body = await res.json();
        expect(body.code).toBe(0);
        expect(body.data.is_favorite).toBe(false);
    });

    test('未登录收藏返回 401', async ({ request }) => {
        const res = await request.post('/api/favorite/1');
        expect(res.status()).toBe(401);
    });
});
