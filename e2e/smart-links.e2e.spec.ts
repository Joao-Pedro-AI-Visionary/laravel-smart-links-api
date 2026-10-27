import { test, expect } from '@playwright/test'
import { execSync } from 'node:child_process'

function querySqlite(sql: string): string {
  return execSync(`sqlite3 database/database.sqlite "${sql}"`, {
    encoding: 'utf-8'
  }).trim()
}

test.describe('Project 2: SmartLinks API Full CRUD, Constraints, SQLite & CDP E2E', () => {
  test('validates Create, Read, Update, Delete, SSRF/Unique/URL Constraints, Queue Click Telemetry, and SQLite FK Cascade', async ({
    page
  }) => {
    // Clean table before E2E run
    querySqlite('DELETE FROM link_clicks; DELETE FROM short_links;')

    const consoleErrors: string[] = []
    page.on('console', (msg) => {
      if (msg.type() === 'error') consoleErrors.push(msg.text())
    })

    await page.goto('/')
    await expect(page.locator('h1')).toContainText('Smart URL Shortener')

    // Active CDP Session
    const cdp = await page.context().newCDPSession(page)
    await cdp.send('Performance.enable')
    await cdp.send('Network.enable')

    // 1. CONSTRAINT: Invalid URL rejected with 422
    await page.getByTestId('input-title').fill('Link Inválido')
    await page.getByTestId('input-target-url').fill('not-a-valid-url')
    await page.getByTestId('submit-link-btn').click()
    await expect(page.getByTestId('api-error-alert')).toContainText('Constraint 422')

    // 2. CONSTRAINT: SSRF private IP in webhook_url rejected with 422
    await page.getByTestId('input-title').fill('Tentativa SSRF')
    await page.getByTestId('input-target-url').fill('https://github.com/Joao-Pedro-AI-Visionary')
    await page.getByTestId('input-webhook-url').fill('http://169.254.169.254/latest/meta-data')
    await page.getByTestId('submit-link-btn').click()
    await expect(page.getByTestId('api-error-alert')).toContainText('SSRF')

    // 3. CREATE: Valid short link
    await page.getByTestId('input-title').fill('Portfólio GitHub Pleno')
    await page.getByTestId('input-slug').fill('github-joao')
    await page.getByTestId('input-target-url').fill('https://github.com/Joao-Pedro-AI-Visionary')
    await page.getByTestId('input-utm-source').fill('linkedin')
    await page.getByTestId('input-utm-medium').fill('recruiter')
    await page.getByTestId('input-webhook-url').fill('')
    await page.getByTestId('submit-link-btn').click()

    await expect(page.getByTestId('api-success-alert')).toContainText('Link criado com sucesso!')
    await expect(page.getByTestId('link-item-github-joao')).toBeVisible()

    // 4. CONSTRAINT: Duplicate slug rejected with 422
    await page.getByTestId('input-title').fill('Outro Link Mesmo Slug')
    await page.getByTestId('input-slug').fill('github-joao')
    await page.getByTestId('input-target-url').fill('https://example.com')
    await page.getByTestId('submit-link-btn').click()
    await expect(page.getByTestId('api-error-alert')).toContainText('Constraint 422')

    // 5. CLICK TELEMETRY & STATS READ
    await page.getByTestId('simulate-click-github-joao').click()
    await expect(page.getByTestId('link-clicks-github-joao')).toContainText('1 clique(s)')
    await expect(page.getByTestId('stats-panel')).toContainText('Cliques Totais: 1')
    await expect(page.getByTestId('stats-panel')).toContainText('Visitantes Únicos: 1')

    // 6. UPDATE (PUT /api/v1/links/github-joao)
    await page.getByTestId('edit-link-github-joao').click()
    await page.getByTestId('input-title').fill('Portfólio GitHub Pleno (Atualizado via PUT)')
    await page.getByTestId('submit-link-btn').click()
    await expect(page.getByTestId('link-title-github-joao')).toHaveText(
      'Portfólio GitHub Pleno (Atualizado via PUT)'
    )

    // 7. SQLITE DATABASE VERIFICATION (SELECT + PRAGMA CONSTRAINTS)
    const dbRow = querySqlite(
      "SELECT slug, title, clicks_count FROM short_links WHERE slug = 'github-joao';"
    )
    expect(dbRow).toBe('github-joao|Portfólio GitHub Pleno (Atualizado via PUT)|1')

    const clickHashLen = querySqlite(
      'SELECT length(visitor_hash), device_type FROM link_clicks LIMIT 1;'
    )
    expect(clickHashLen).toBe('64|desktop')

    const fkCheck = querySqlite("PRAGMA foreign_key_list('link_clicks');")
    expect(fkCheck).toContain('short_links')
    expect(fkCheck).toContain('CASCADE')

    // 8. SCREENSHOTS (Desktop 1440px & Mobile 390px)
    await page.setViewportSize({ width: 1440, height: 900 })
    await page.screenshot({
      path: 'test-results/p2-smart-links-desktop.png',
      fullPage: true
    })

    await page.setViewportSize({ width: 390, height: 844 })
    await page.screenshot({
      path: 'test-results/p2-smart-links-mobile.png',
      fullPage: true
    })

    // 9. DELETE & CASCADE VERIFICATION
    await page.getByTestId('delete-link-github-joao').click()
    await expect(page.getByTestId('link-item-github-joao')).toHaveCount(0)

    const remainingLinks = querySqlite("SELECT COUNT(*) FROM short_links WHERE slug = 'github-joao';")
    const remainingClicks = querySqlite('SELECT COUNT(*) FROM link_clicks;')
    expect(remainingLinks).toBe('0')
    expect(remainingClicks).toBe('0')

    const unexpectedErrors = consoleErrors.filter(
      (err) => !err.includes('status of 422 (Unprocessable Content)')
    )
    expect(unexpectedErrors).toEqual([])
    expect(consoleErrors).toHaveLength(3)
  })
})
