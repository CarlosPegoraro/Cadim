import { expect, test } from '@playwright/test';

async function register(page, suffix = Date.now()) {
    const email = `e2e-${suffix}@example.com`;

    await page.goto('/register');
    await page.getByLabel('Nome completo').fill('Usuário de Cobertura');
    await page.getByLabel('E-mail').fill(email);
    await page.getByLabel('Senha', { exact: true }).fill('password');
    await page.getByLabel('Confirmar senha').fill('password');
    await page.getByRole('checkbox').check();
    await page.getByRole('button', { name: 'Criar minha conta' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
}

test('a visitor is redirected to login from protected areas', async ({ page }) => {
    await page.goto('/transactions');

    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByRole('button', { name: 'Entrar na minha conta' })).toBeVisible();
});

test('a user can create, confirm, filter and log out a transaction', async ({ page }) => {
    await register(page, `${Date.now()}-workflow`);
    await page.getByRole('link', { name: 'Transações' }).click();
    await expect(page).toHaveURL(/\/transactions(?:\?.*)?$/);

    await page.getByRole('button', { name: '+ Nova transação' }).click();
    await expect(page.getByRole('heading', { name: 'Nova transação' })).toBeVisible();
    await page.getByLabel('Tipo').selectOption('expense');
    await page.getByLabel('Valor').fill('125.50');
    await page.getByLabel('Descrição').fill('Mercado E2E');
    await page.getByLabel('Data da compra/competência').fill('2026-08-10');
    await page.getByLabel('Categoria').selectOption({ label: 'Alimentação' });
    await page.getByRole('button', { name: 'Salvar lançamento' }).click();

    const row = page.locator('tr').filter({ hasText: 'Mercado E2E' });
    await expect(row).toContainText('Pendente');
    await expect(row).toContainText('R$ 125,50');
    await row.getByRole('button', { name: 'Confirmar transação' }).click();
    await expect(row).toContainText('Confirmado');

    await page.getByPlaceholder('Buscar descrição ou loja').fill('Mercado E2E');
    await expect(page.locator('tbody tr')).toHaveCount(1);
    await expect(page.locator('tbody')).toContainText('Mercado E2E');

    await page.locator('summary[aria-label="Abrir menu do perfil"]').click();
    await page.getByRole('button', { name: /Sair/ }).click();
    await expect(page).toHaveURL(/\/login$/);
});

test('registration validates terms and password confirmation', async ({ page }) => {
    await page.goto('/register');
    await page.getByLabel('Nome completo').fill('Cadastro inválido');
    await page.getByLabel('E-mail').fill(`invalid-${Date.now()}@example.com`);
    await page.getByLabel('Senha', { exact: true }).fill('password');
    await page.getByLabel('Confirmar senha').fill('different');
    await page.getByRole('button', { name: 'Criar minha conta' }).click();

    await expect(page).toHaveURL(/\/register$/);
    await expect(page.locator('.form-error')).toContainText('termos');
});
