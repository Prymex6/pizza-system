import { defineConfig, devices } from '@playwright/test';

/**
 * Pizza SaaS – Playwright E2E Configuration
 *
 * Landlord panel: http://localhost:8000      (super-admin)
 * Tenant panel:   http://pizza.localhost:8000     (manager / staff / client)
 *
 * Prerequisites:
 *   1. Add to Windows hosts:  127.0.0.1  pizza.localhost
 *   2. Run:  php artisan e2e:setup
 *   3. Run:  php artisan serve  (or have XAMPP running on port 8000)
 */

export const LANDLORD_URL = process.env.LANDLORD_URL ?? 'http://localhost:8000';
export const TENANT_URL   = process.env.TENANT_URL   ?? 'http://pizza.localhost:8000';

export default defineConfig({
    testDir: './tests/e2e',
    outputDir: './tests/e2e/.output',
    fullyParallel: false,        // keep sequential – single dev server
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: [
        ['list'],
        ['html', { outputFolder: 'tests/e2e/.report', open: 'never' }],
        ['./tests/e2e/reporters/plan-reporter.ts'],
    ],

    use: {
        /* Global defaults */
        baseURL: TENANT_URL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        actionTimeout: 15_000,
        navigationTimeout: 30_000,
        /* Inertia.js SPA – wait for network idle after each navigation */
        waitForLoadState: 'networkidle',
    },

    projects: [
        /* ── Landlord panel (super-admin) ──────────────────────────── */
        {
            name: 'landlord',
            testMatch: '**/landlord/**/*.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: LANDLORD_URL,
                storageState: 'tests/e2e/.auth/super-admin.json',
            },
            dependencies: ['landlord-setup'],
        },

        /* ── Landlord auth setup (must run first, no storageState) ── */
        {
            name: 'landlord-setup',
            testMatch: '**/setup/landlord-auth.setup.ts',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: LANDLORD_URL,
            },
        },

        /* ── Tenant manager ─────────────────────────────────────────── */
        {
            name: 'manager',
            testMatch: '**/manager/**/*.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: TENANT_URL,
                storageState: 'tests/e2e/.auth/manager.json',
            },
            dependencies: ['tenant-setup'],
        },

        /* ── Tenant staff roles ─────────────────────────────────────── */
        {
            name: 'staff',
            testMatch: '**/staff/**/*.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: TENANT_URL,
                storageState: 'tests/e2e/.auth/chef.json',
            },
            dependencies: ['tenant-setup'],
        },

        /* ── Client / storefront ────────────────────────────────────── */
        {
            name: 'client',
            testMatch: '**/client/**/*.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: TENANT_URL,
                storageState: 'tests/e2e/.auth/customer.json',
            },
            dependencies: ['tenant-setup'],
        },

        /* ── Client guest (no auth) ─────────────────────────────────── */
        {
            name: 'client-guest',
            testMatch: '**/client/guest/**/*.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: TENANT_URL,
            },
        },

        /* ── Security / access-control tests ───────────────────────── */
        {
            name: 'security',
            testMatch: '**/security/**/*.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: TENANT_URL,
            },
            dependencies: ['tenant-setup'],
        },

        /* ── Auth setup helpers (no storageState themselves) ────────── */
        {
            name: 'tenant-setup',
            testMatch: '**/setup/tenant-auth.setup.ts',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: TENANT_URL,
            },
        },
    ],

    globalSetup: './tests/e2e/global-setup.ts',
});
