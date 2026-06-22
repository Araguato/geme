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
