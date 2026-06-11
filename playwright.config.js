// playwright.config.js
const { defineConfig } = require('./tests/node_modules/@playwright/test');

module.exports = defineConfig({
    testDir: './tests',
    timeout: 30000,
    use: {
        baseURL: 'http://localhost',
        headless: true,
        screenshot: 'only-on-failure',
        video: 'off',
    },
    webServer: {
        command: 'D:\\phpstudy_pro\\Extensions\\php\\php7.4.3nts\\php-win.exe -S localhost:80 -t D:\\phpstudy_pro\\WWW\\musicsite\\public',
        port: 80,
        reuseExistingServer: true,
    },
});
