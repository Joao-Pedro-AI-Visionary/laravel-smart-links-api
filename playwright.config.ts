import { defineConfig } from '@playwright/test'

export default defineConfig({
  testDir: './e2e',
  timeout: 30000,
  fullyParallel: false,
  workers: 1,
  use: {
    baseURL: 'http://127.0.0.1:8001',
    channel: 'chrome',
    headless: false,
    viewport: { width: 1440, height: 900 },
    launchOptions: {
      slowMo: 150
    }
  },
  webServer: {
    command: 'QUEUE_CONNECTION=sync /opt/homebrew/bin/php artisan serve --host=127.0.0.1 --port=8001',
    url: 'http://127.0.0.1:8001',
    reuseExistingServer: false,
    timeout: 15000
  }
})
