const { chromium } = require('./node_modules/playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    let allPassed = true;

    async function parseJSON(response) {
        let text = await response.text();
        if (text.length > 0 && text.charCodeAt(0) === 0xFEFF) text = text.substring(1);
        if (text.startsWith('﻿')) text = text.substring(1);
        return JSON.parse(text);
    }

    const baseURL = 'http://localhost';

    try {
        // Get token first
        const loginCtx = await browser.newContext();
        const loginPage = await loginCtx.newPage();
        const loginRes = await loginPage.request.post(`${baseURL}/api/auth/login`, {
            data: { username: 'admin', password: 'password' }
        });
        const loginBody = await parseJSON(loginRes);
        const token = loginBody.data.token;
        await loginCtx.close();

        // Test 1: Login
        console.log('Test 1: Login returns token');
        if (token) { console.log('  ✓ PASS'); } else { console.log('  ✗ FAIL'); allPassed = false; }

        // Test 2: Songs list with lyrics
        console.log('\nTest 2: Songs list includes lyrics');
        const ctx2 = await browser.newContext();
        const page2 = await ctx2.newPage();
        const songsRes = await page2.request.get(`${baseURL}/api/songs/hot`);
        const songsBody = await parseJSON(songsRes);
        const hasLyrics = songsBody.data?.length > 0 && 'lyrics' in songsBody.data[0];
        console.log('  Songs count:', songsBody.data?.length || 0);
        console.log('  Has lyrics field:', hasLyrics);
        if (hasLyrics) { console.log('  ✓ PASS'); } else { console.log('  ✗ FAIL'); allPassed = false; }
        await ctx2.close();

        // Test 3: Favorite toggle
        console.log('\nTest 3: Favorite toggle');
        const ctx3 = await browser.newContext();
        const page3 = await ctx3.newPage();
        // First toggle (might add or remove)
        const t1 = await parseJSON(await page3.request.post(`${baseURL}/api/favorite/1`, {
            headers: { Authorization: `Bearer ${token}` }
        }));
        // Second toggle (should be opposite)
        const t2 = await parseJSON(await page3.request.post(`${baseURL}/api/favorite/1`, {
            headers: { Authorization: `Bearer ${token}` }
        }));
        console.log('  First toggle:', t1.data?.is_favorite);
        console.log('  Second toggle:', t2.data?.is_favorite);
        if (t1.data?.is_favorite !== t2.data?.is_favorite) {
            console.log('  ✓ PASS - Toggle works');
        } else {
            console.log('  ✗ FAIL');
            allPassed = false;
        }
        await ctx3.close();

        // Test 4: Unauthorized - use fresh context
        console.log('\nTest 4: Favorite without token returns 401');
        const ctx4 = await browser.newContext();
        const page4 = await ctx4.newPage();
        const noAuthRes = await page4.request.post(`${baseURL}/api/favorite/1`);
        console.log('  Status:', noAuthRes.status());
        if (noAuthRes.status() === 401) {
            console.log('  ✓ PASS');
        } else {
            console.log('  ✗ FAIL - got', noAuthRes.status());
            allPassed = false;
        }
        await ctx4.close();

        // Test 5: Auth/me
        console.log('\nTest 5: Auth/me with token');
        const ctx5 = await browser.newContext();
        const page5 = await ctx5.newPage();
        const meRes = await page5.request.get(`${baseURL}/api/auth/me`, {
            headers: { Authorization: `Bearer ${token}` }
        });
        const meBody = await parseJSON(meRes);
        console.log('  Code:', meBody.code, 'Username:', meBody.data?.username);
        if (meBody.code === 0 && meBody.data?.username === 'admin') {
            console.log('  ✓ PASS');
        } else { console.log('  ✗ FAIL'); allPassed = false; }
        await ctx5.close();

        // Test 6: Admin stats
        console.log('\nTest 6: Admin stats');
        const ctx6 = await browser.newContext();
        const page6 = await ctx6.newPage();
        const statsRes = await page6.request.get(`${baseURL}/api/admin/stats`, {
            headers: { Authorization: `Bearer ${token}` }
        });
        const statsBody = await parseJSON(statsRes);
        console.log('  Code:', statsBody.code, 'total_songs:', statsBody.data?.total_songs);
        if (statsBody.code === 0 && statsBody.data?.total_songs > 0) {
            console.log('  ✓ PASS');
        } else { console.log('  ✗ FAIL'); allPassed = false; }
        await ctx6.close();

        console.log('\n=== Result:', allPassed ? 'ALL PASSED ✓' : 'SOME FAILED ✗', '===');

    } catch (e) {
        console.error('Error:', e.message);
    } finally {
        await browser.close();
    }
})();
