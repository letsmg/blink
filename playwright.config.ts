import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e', // Onde ficarão os seus testes ponta a ponta
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',
  use: {
    baseURL: 'http://localhost:8000', // URL padrão do seu servidor Laravel local
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
  // Opcional: Se quiser que o Playwright suba o servidor PHP automaticamente antes dos testes
  // webServer: {
  //   command: 'php artisan serve',
  //   url: 'http://localhost:8000',
  //   reuseExistingServer: !process.env.CI,
  // },
});