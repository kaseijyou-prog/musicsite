// tests/auth.spec.js
const { test, expect } = require('@playwright/test');

test.describe('用户认证', () => {
    test('登录成功返回 token', async ({ request }) => {
        const res = await request.post('/api/auth/login', {
            data: { username: 'admin', password: 'password' }
        });
        expect(res.ok()).toBeTruthy();
        const body = await res.json();
        expect(body.code).toBe(0);
        expect(body.data.token).toBeTruthy();
        expect(body.data.username).toBe('admin');
    });

    test('登录失败返回错误', async ({ request }) => {
        const res = await request.post('/api/auth/login', {
            data: { username: 'admin', password: 'wrong' }
        });
        expect(res.status()).toBe(400);
    });

    test('未登录访问 /api/auth/me 返回 401', async ({ request }) => {
        const res = await request.get('/api/auth/me');
        expect(res.status()).toBe(401);
    });

    test('使用 token 访问 /api/auth/me 成功', async ({ request }) => {
        const loginRes = await request.post('/api/auth/login', {
            data: { username: 'admin', password: 'password' }
        });
        const { token } = (await loginRes.json()).data;

        const meRes = await request.get('/api/auth/me', {
            headers: { Authorization: `Bearer ${token}` }
        });
        expect(meRes.ok()).toBeTruthy();
        const body = await meRes.json();
        expect(body.code).toBe(0);
        expect(body.data.username).toBe('admin');
    });
});
