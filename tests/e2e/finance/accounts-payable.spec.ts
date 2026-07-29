import { test, expect } from '@playwright/test';

/**
 * Testes E2E para o módulo financeiro - Contas a Pagar.
 * Valida o fluxo completo de gestão financeira via SPA.
 */

test.describe('Módulo Financeiro - Contas a Pagar (Staff)', () => {

  test.beforeEach(async ({ page }) => {
    // Login como Admin antes de cada teste
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@blink.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/staff/**', { timeout: 5000 });
  });

  test('acessa tela de contas a pagar', async ({ page }) => {
    await page.goto('/staff/accounts-payable');
    
    // Verifica elementos da página
    await expect(page.locator('text=Contas a Pagar')).toBeVisible({ timeout: 3000 });
    await expect(page.locator('text=Nova Conta')).toBeVisible({ timeout: 3000 });
  });

  test('cria nova conta a pagar', async ({ page }) => {
    await page.goto('/staff/accounts-payable');
    
    // Clica no botão de nova conta
    await page.click('text=Nova Conta');
    
    // Preenche formulário
    await page.fill('input[name="description"]', 'Aluguel da Clínica - Teste E2E');
    await page.fill('input[name="amount"]', '5000.00');
    await page.fill('input[name="due_date"]', '2026-12-31');
    await page.selectOption('select[name="category"]', 'aluguel');
    
    // Submete
    await page.click('button[type="submit"]');
    
    // Verifica mensagem de sucesso
    await expect(page.locator('text=sucesso')).toBeVisible({ timeout: 3000 });
  });

  test('marca conta como paga', async ({ page }) => {
    // Primeiro cria uma conta via API direta para agilizar
    await page.evaluate(async () => {
      const token = document.cookie.split('; ').find(r => r.startsWith('auth_token='))?.split('=')[1];
      await fetch('/api/staff/accounts-payable', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({
          description: 'Conta para teste de pagamento',
          amount: 350.00,
          due_date: '2026-12-15',
          category: 'material',
        }),
      });
    });

    await page.goto('/staff/accounts-payable');
    await page.waitForTimeout(1000);
    
    // Clica no botão de pagar da primeira conta
    await page.click('button:has-text("Pagar")');
    
    // Preenche dados do pagamento
    await page.click('text=Confirmar');
    
    // Verifica que a conta foi marcada como paga
    await expect(page.locator('text=Pago')).toBeVisible({ timeout: 3000 });
  });

  test('dashboard financeiro mostra totais', async ({ page }) => {
    // Cria algumas contas via API para ter dados
    await page.evaluate(async () => {
      const token = document.cookie.split('; ').find(r => r.startsWith('auth_token='))?.split('=')[1];
      const headers = {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
      };
      
      await fetch('/api/staff/accounts-payable', {
        method: 'POST',
        headers,
        body: JSON.stringify({ description: 'Pendente', amount: 500, due_date: '2026-12-31' }),
      });
      await fetch('/api/staff/accounts-payable', {
        method: 'POST',
        headers,
        body: JSON.stringify({ description: 'Vencida', amount: 300, due_date: '2020-01-01', status: 'overdue' }),
      });
    });

    await page.goto('/staff/accounts-payable');
    await page.waitForTimeout(1000);
    
    // Verifica cards de totais
    const totalsSection = page.locator('text=Total Pendente, text=Total Vencido');
    await expect(totalsSection).toBeVisible({ timeout: 3000 });
  });
});