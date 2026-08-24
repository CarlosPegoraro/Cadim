import { expect, test } from '@playwright/test';

test('a visitor can register and access the dashboard', async ({ page }) => {
    const email = `e2e-${Date.now()}@example.com`;

    await page.goto('/register');
    await page.getByLabel('Nome').fill('Usuário E2E');
    await page.getByLabel('E-mail').fill(email);
    await page.getByLabel('Senha', { exact: true }).fill('password');
    await page.getByLabel('Confirmar senha').fill('password');
    await page.getByRole('checkbox').check();
    await page.getByRole('button', { name: 'Criar minha conta' }).click();

    await expect(page).toHaveURL(/\/dashboard$/);
    await expect(page.getByText('Cadim')).toBeVisible();

    await page.getByRole('button', { name: 'Ativar modo escuro' }).click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
    await page.getByRole('link', { name: 'Transações' }).click();
    await expect(page).toHaveURL(/\/transactions(?:\?.*)?$/);
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
});
