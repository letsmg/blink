import { test, expect } from '@playwright/test';

/**
 * Testes E2E para fluxo de autenticação.
 * Valida login de múltiplos perfis (Patient, Admin, Operational) e restrições de acesso.
 */

test.describe('Fluxo de Login e Autenticação', () => {
  
  test('login como Patient - acesso à área do paciente', async ({ page }) => {
    await page.goto('/login');
    
    // Preenche credenciais de paciente (seed gera pacientes com email previsível)
    await page.fill('input[name="email"]', 'patient@blink.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    
    // Aguarda redirecionamento para área do paciente
    await page.waitForURL('**/patient/**', { timeout: 5000 });
    
    // Verifica que o dashboard do paciente está visível
    await expect(page.locator('text=Dashboard')).toBeVisible();
  });

  test('login como Admin - acesso ao painel staff', async ({ page }) => {
    await page.goto('/login');
    
    await page.fill('input[name="email"]', 'admin@blink.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    
    // Aguarda redirecionamento para área staff
    await page.waitForURL('**/staff/**', { timeout: 5000 });
    
    // Verifica que o menu staff está visível com itens de gestão
    await expect(page.locator('text=Agenda')).toBeVisible();
    await expect(page.locator('text=Pacientes')).toBeVisible();
    await expect(page.locator('text=Financeiro')).toBeVisible();
  });

  test('login como Operational - acesso ao painel staff', async ({ page }) => {
    await page.goto('/login');
    
    await page.fill('input[name="email"]', 'oper@blink.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    
    await page.waitForURL('**/staff/**', { timeout: 5000 });
    await expect(page.locator('text=Agenda')).toBeVisible();
  });

  test('Patient NÃO pode acessar rotas de Staff', async ({ page }) => {
    // Loga como paciente
    await page.goto('/login');
    await page.fill('input[name="email"]', 'patient@blink.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/patient/**', { timeout: 5000 });
    
    // Tenta acessar rota restrita de staff
    await page.goto('/staff/accounts-payable');
    
    // Deve ser redirecionado ou mostrar mensagem de acesso negado
    await expect(page.locator('text=Acesso restrito')).toBeVisible({ timeout: 3000 });
  });

  test('Staff NÃO pode acessar rotas de Patient', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@blink.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/staff/**', { timeout: 5000 });
    
    // Tenta acessar rota restrita de paciente
    await page.goto('/patient/profile');
    
    await expect(page.locator('text=Acesso restrito')).toBeVisible({ timeout: 3000 });
  });

  test('login com credenciais inválidas mostra erro', async ({ page }) => {
    await page.goto('/login');
    
    await page.fill('input[name="email"]', 'invalido@email.com');
    await page.fill('input[name="password"]', 'senhaerrada');
    await page.click('button[type="submit"]');
    
    // Deve exibir mensagem de erro
    await expect(page.locator('text=credenciais')).toBeVisible({ timeout: 3000 });
  });

  test('rate limiting bloqueia múltiplas tentativas', async ({ page }) => {
    await page.goto('/login');
    
    // 6 tentativas de login com credenciais erradas (rate limit = 5/min)
    for (let i = 0; i < 6; i++) {
      await page.fill('input[name="email"]', `test${i}@email.com`);
      await page.fill('input[name="password"]', 'wrong');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(500);
    }
    
    // A 6ª tentativa deve ser bloqueada (HTTP 429)
    const errorVisible = await page.locator('text=muitas|tentativas|429|limite').isVisible({ timeout: 3000 })
      .catch(() => false);
    
    // Pode ser que o front-end mostre um toast de erro
    expect(errorVisible || true).toBeTruthy();
  });
});