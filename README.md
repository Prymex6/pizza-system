# RestaurantSaaS

Multi-tenant SaaS platform dla restauracji — system zamówień online z panelem managera, kuchni, kierowcy i klienta.

## Stack technologiczny

- **Backend:** Laravel 12, PHP 8.2
- **Frontend:** Vue 3 + Inertia.js + Tailwind CSS 4
- **Multi-tenancy:** stancl/tenancy (subdomenowa izolacja baz danych)
- **WebSockets:** Laravel Reverb + laravel-echo
- **Baza danych:** MySQL (landlord) + osobna baza per tenant
- **Kolejki:** Laravel Queues (database driver)
- **Build:** Vite 6

## Funkcjonalności

### Panel klienta
- Zamówienia online (dostawa / odbiór / na miejscu)
- Koszyk, checkout, płatności (gotówka, karta, Przelewy24)
- Śledzenie zamówienia z GPS kierowcy (OpenStreetMap)
- Program lojalnościowy (punkty, poziomy, nagrody)
- Konto klienta, historia zamówień, faktury PDF
- Rezerwacje stolików
- PWA (instalacja na telefonie, powiadomienia push)

### Panel managera
- Zarządzanie menu (kategorie, produkty, warianty, dodatki, alergeny)
- Zamówienia w czasie rzeczywistym (WebSocket)
- Pracownicy i role (manager, kucharz, kelner, kierowca, kasjer)
- Uprawnienia per rola
- Strefy dostawy (mapa wielokątów)
- Stoliki i kody QR
- Raporty sprzedaży + eksport CSV
- Marketing e-mailowy (kampanie, opt-out, HMAC unsubscribe)
- Program lojalnościowy v2 (konfiguracja poziomów, nagród, kampanii)
- Ustawienia restauracji (wygląd, godziny, płatności, SMS, SMTP per tenant)
- System wsparcia (zgłoszenia do landlorda)
- Powiadomienia push dla pracowników

### Panel kuchni / kelnerów / kierowcy
- KDS (Kitchen Display System) dla kucharzy
- POS dla kelnerów i kasjerów
- Panel kierowcy z aktualizacją GPS

### Panel landlorda (superadmin)
- Zarządzanie tenantami i planami
- Impersonacja tenantów
- Dashboard z przychodami
- System licencji

## Wymagania

- PHP 8.2+
- MySQL 8.0+
- Node.js 20+
- Composer 2

## Instalacja

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# Landlord migrations
php artisan migrate --path=database/migrations/landlord --force

# Uruchom dev server
php artisan serve
npm run dev
```

Tenant migracje uruchamiane są automatycznie przy tworzeniu tenanta lub ręcznie:

```bash
php artisan tenants:migrate --force
```

## Struktura projektu

```
app/Http/Controllers/
├── Landlord/          # Kontrolery superadmina
└── Tenant/
    ├── Manager/       # Panel managera restauracji
    ├── Staff/         # Panel pracowników (kucharz, kelner, kierowca)
    └── Client/        # Panel klienta

resources/js/
├── Layouts/           # ManagerLayout, StaffLayout, ClientLayout
├── Pages/
│   ├── Landlord/
│   └── Tenant/
│       ├── Manager/
│       ├── Staff/
│       └── Client/
└── Components/

database/migrations/
├── landlord/          # Migracje centralnej bazy
└── tenant/            # Migracje baz tenantów

routes/
├── web.php            # Trasy landlorda
├── landlord.php
└── tenant.php         # Trasy tenantów
```

## Licencja

Projekt prywatny — wszelkie prawa zastrzeżone.
