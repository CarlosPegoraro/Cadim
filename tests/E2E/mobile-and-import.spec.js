import { expect, test } from '@playwright/test';

test('mobile authentication screens remain usable', async ({ page }) => {
    await page.goto('/login');
    await expect(page.getByRole('button', { name: 'Entrar na minha conta' })).toBeVisible();
    await page.goto('/register');
    await expect(page.getByRole('button', { name: 'Criar minha conta' })).toBeVisible();
});
