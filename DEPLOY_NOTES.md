# Deploy-Notizen WaWi

Dieses Dokument hält fest, welche Änderungen (insbesondere an der Datenbank) zu welchem Deploy gehören.

## Format pro Eintrag

- **Datum:** YYYY-MM-DD
- **Branch/Tag:** z.B. `main` oder `feature/...`
- **Feature/Beschreibung:** Kurzbeschreibung
- **Neue Migrationen:**
  - `YYYY_MM_DD_HHMMSS_migration_name`
- **Betroffene Tabellen:**
  - `table_name` (Spalten: `column1`, `column2`)
- **Lokale Schritte:**
  - `php artisan migrate`
  - Manuelle Tests (stichpunktartig)
- **Server/CapRover Schritte:**
  - Deploy von Branch/Tag
  - `php artisan migrate --force` im App-Container

---

## 2026-01-17 – Finanzen-Modul (Ausgaben & Kategorien)

- **Branch/Tag:** `main`
- **Feature/Beschreibung:** Neues Finanzen-Modul mit Ausgaben (gastos/consumos) und Kostenkategorien.
- **Neue Migrationen:**
  - `2026_01_17_150000_create_expense_categories_table`
  - `2026_01_17_150100_create_expenses_table`
- **Betroffene Tabellen:**
  - `expense_categories` (z.B. `id`, `name`, `is_active`, Timestamps)
  - `expenses` (z.B. `id`, `date`, `amount`, `type`, `expense_category_id`, `payment_method`, `note`, Timestamps)
- **Lokale Schritte:**
  - `php artisan migrate`
  - Manuell: Kategorien anlegen/bearbeiten/löschen, Ausgaben erfassen, Filtern testen.
- **Server/CapRover Schritte:**
  - Deploy aktueller `main`-Stand
  - Im App-Container:
    - `php artisan migrate --force`
  - Manuell: Zugriff auf `/finances` und `/finances/categories` prüfen, Menüpunkte sichtbar nur für Admin und bei aktiviertem `finances_enabled`.

## 2026-06-23 – Landing Page, Dashboard, TPV-Tour, SENIAT Libro Electrónico

- **Branch/Tag:** `master`
- **Feature/Beschreibung:** Öffentliche Landing Page, Dashboard mit KPIs/Chart, POS-Tour, Supplier-Form-Tour, SENIAT Libro Electrónico (XML-Export, Steuerbericht), Kunden-RIF-Auswahl im POS.
- **Neue Migrationen:**
  - `2026_06_22_153700_add_location_fields_to_products_table`
  - `2026_06_22_153800_add_description_zh_to_products_table`
  - `2026_06_22_154000_create_cash_shifts_table`
  - `2026_06_22_154100_create_delivery_infos_table`
  - `2026_06_22_154200_create_orders_table`
  - `2026_06_22_154300_create_order_items_table`
  - `2026_06_22_154400_create_payments_table`
  - `2026_06_22_154500_create_credit_notes_table`
  - `2026_06_22_154600_create_credit_note_items_table`
  - `2026_06_22_154700_create_debit_notes_table`
  - `2026_06_22_154800_create_debit_note_items_table`
  - `2026_06_22_154900_add_delivery_infos_order_fk`
  - `2026_06_22_155000_add_warehouse_to_stock_tables`
  - `2026_06_22_155100_add_location_to_stock_tables`
  - `2026_06_22_155200_add_detail_fields_to_locations`
  - `2026_06_23_000001_add_seniat_fields_to_fiscal_ledgers`
  - `2026_06_23_000002_add_seniat_fields_to_orders`
- **Betroffene Tabellen:**
  - `orders`, `order_items`, `payments`, `cash_shifts`, `delivery_infos`, `credit_notes`, `credit_note_items`, `debit_notes`, `debit_note_items`
  - `fiscal_ledgers` (neue SENIAT-Spalten)
  - `products` (Lager-/Standort-Felder)
  - `stock_items`, `stock_movements` (warehouse/location)
  - `locations` (detail-Felder)
- **Lokale Schritte:**
  - `php artisan migrate`
  - `php artisan view:clear`
  - Manuell: Landing Page, Dashboard, POS, Libro Electrónico, Steuerbericht testen
- **Server/CapRover Schritte:**
  - Deploy aktueller `master`-Stand
  - Im App-Container:
    - `php artisan migrate --force`
    - `php artisan view:clear`
  - App-Config (falls noch nicht geschehen):
    - Port: `80`
    - HTTP-Settings: Enable HTTPS, Force HTTPS empfohlen
    - Env-Variablen: APP_KEY, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL, APP_ENV=production, APP_DEBUG=false, etc. (siehe `.env.example`)
  - Manuell: Landing Page, Dashboard, POS, `/fiscal-ledger`, `/fiscal-ledger/tax-report` prüfen
