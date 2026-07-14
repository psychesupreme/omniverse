# OmniRoute v2: Multi-Tenant Field Force Automation SaaS

OmniRoute is a multi-tenant Field Force Automation and tracking system built with a **Laravel 11 API Backend** and a **Flutter Mobile Client**. It combines offline-first database synchronization (Last-Write-Wins) with real-time GPS coordinates broadcasting on a Dispatcher mapping portal.

---

## 🛠️ Technology Stack

### Backend & Dispatcher Portal
* **Framework:** Laravel 11.x & PHP 8.3
* **Frontend:** Vue.js 3 (Inertia.js) & TailwindCSS
* **Database:** PostgreSQL + PostGIS (Spatial Database Extension)
* **Multi-Tenancy:** `stancl/tenancy` (Schema Isolation driver)
* **Real-time WebSockets:** Laravel Reverb (Port `8085` local configuration)
* **Mapping Framework:** Leaflet.js

### Mobile Tracking Client
* **Framework:** Flutter SDK (`>=3.4.3 <4.0.0`)
* **Local Database:** Isar (Reactive bridge-free NoSQL)
* **Secure Storage:** `flutter_secure_storage` (Android Keystore / iOS Keychain encryption)
* **Geolocation Engine:** `geolocator`
* **Background Isolation:** `flutter_background_service` (Foreground notifications isolate)

---

## 🚀 Setup & Installation Guide

### Prerequisites
* **Docker & Docker Compose** (for PostgreSQL/PostGIS database)
* **PHP 8.3 & Composer**
* **Node.js & NPM**
* **Flutter SDK 3.22+**

---

### Backend (Server) Setup

1. **Clone & Install Dependencies:**
   ```bash
   composer install
   npm install
   ```

2. **Configure Environment Variables:**
   * Copy the example file: `cp .env.example .env`
   * Configure your local credentials. Ensure `REVERB_PORT=8085` and `DB_HOST=127.0.0.1` are mapped properly.

3. **Start Infrastructure Services:**
   * Start your Docker PostgreSQL/PostGIS services:
     ```bash
     docker compose up -d
     ```

4. **Run Migrations & Seeders:**
   * Run central migrations:
     ```bash
     php artisan migrate
     ```
   * Pre-seed tenants, users, domains (`acme.lvh.me` and `acme.localhost`), and database tokens:
     ```bash
     php C:\Users\S3dwn\.gemini\antigravity\brain\9148f6a3-664f-48ff-9225-711bf67aba02\scratch\create_tenant.php
     ```

5. **Compile Frontend & Start Servers:**
   * Compile assets:
     ```bash
     npm run build
     ```
   * Launch Web server:
     ```bash
     php artisan serve --host=0.0.0.0 --port=8888
     ```
   * Launch WebSocket server (foreground debug mode):
     ```bash
     php artisan reverb:start --port=8085 --debug
     ```

---

### Mobile Client Setup

1. **Navigate to Mobile folder and Fetch Packages:**
   ```bash
   cd mobile
   flutter pub get
   ```

2. **Generate Database Adapters (Isar):**
   ```bash
   dart run build_runner build --delete-conflicting-outputs
   ```

3. **Run Application:**
   * Ensure your physical test device/emulator is on the same local subnet as the server host.
   * Run debug build:
     ```bash
     $env:PUB_CACHE="D:\Dev\.pub-cache"
     D:\Environment\flutter\bin\flutter.bat run
     ```

---

## 📈 Current Project Milestones

- [x] **Multi-Tenant Schema Isolation**: PostgreSQL Schema separation with dynamic PostGIS geography types resolution.
- [x] **Background GPS Tracking Isolate**: Safe foreground notification isolate logging coordinates every 60s with mock-GPS countermeasures.
- [x] **Offline Synchronization Queue**: local Isar DB caching, Last-Write-Wins (LWW) resolution, and batch-chunking requests (slices of 50).
- [x] **Sanctum Authentication Flow**: Mobile credentials login mapping to dynamic tenant domains, saving keys securely in native Keystore/Keychain.
- [x] **Dispatcher Live Mapping**: Dynamic Leaflet map markers updating in real-time on Vue frontend via Echo/Reverb channels.
- [x] **Resilient Error Tolerance**: Try-catch wraps around event dispatches ensuring sync endpoints return `200 OK` even if Reverb goes offline.

---

## 🗺️ Enterprise SaaS Product Roadmap

> **Overall Platform Completion: ~25%** — Core tracking engine is fully operational. Business administration, HR, and commerce layers are pending.
>
> For full technical specifications and data flow diagrams for each module, see [`docs/architecture.md`](docs/architecture.md).

### Module Progress Tracker

| # | Module | Status | Description |
|---|--------|--------|-------------|
| 1 | Core Tracking & Sync Engine | ✅ Done | Offline-first GPS logging, LWW sync, batch chunking |
| 2 | Multi-Tenant Infrastructure | ✅ Done | Schema isolation, PostGIS search path, domain routing |
| 3 | Real-Time WebSocket Mapping | ✅ Done | Reverb broadcasting, Leaflet live markers, Echo channels |
| 4 | Secure Mobile Auth & Session | ✅ Done | Sanctum tokens, flutter_secure_storage, dynamic routing |
| 5 | **Central SaaS Super-Admin** | 🔲 Pending | Tenant provisioning, subscription billing, feature toggling |
| 6 | **Sales Operations** | 🔲 Pending | Product catalogue, order management, inventory, proof-of-delivery |
| 7 | **HR & Attendance** | 🔲 Pending | Timesheets, geofence clock-in/out, leave management |
| 8 | **Payroll Engine** | 🔲 Pending | Wage calculation, commissions, tax deductions, payslips |
| 9 | **Expense & Asset Management** | 🔲 Pending | Receipt capture, finance approval, equipment tracking |
| 10 | **CRM & Task Dispatching** | 🔲 Pending | Full CRM contacts, visit planning, job assignment, SLA timers |
| 11 | **Geofence & Route Optimization** | 🔲 Pending | Polygon drawing, PgRouting, route deviation alerts |
| 12 | **Analytics & Reporting** | 🔲 Pending | Worker performance, sales dashboards, compliance, PDF/CSV exports |

### Module Descriptions

#### 5. Central SaaS Super-Admin Layer
The platform owner's control plane operating on the central database (outside tenant context):
- **Tenant Provisioning:** Automated schema creation, subdomain registration, and admin credential bootstrapping on company signup.
- **Subscription & Billing:** SaaS tier enforcement (Basic/Pro/Enterprise), usage metering, payment gateway integration (Stripe, M-Pesa).
- **Global Health Dashboard:** Monitor tenant activity, sync queue health, database sizes, and server load.
- **Feature Toggling:** Enable/disable modules per tenant based on subscription tier via middleware-level feature flags.

#### 6. Sales Operations & Order Management
- **Mobile:** Offline order capture forms, barcode/QR scanning, product catalogue browsing, proof-of-delivery photo attachments.
- **Web:** Product management, order lifecycle tracking (Created → Confirmed → Dispatched → Delivered), inventory alerts.
- **Sync:** Bidirectional LWW replication for orders and product definitions.

#### 7. HR & Attendance Management
- **Timesheets:** Convert raw Start/End Shift GPS logs into formal, auditable timesheet records with manager approval workflows.
- **Geofence Attendance:** Auto clock-in/out when workers enter/exit designated warehouse/office geofence polygons.
- **Leave Management:** Mobile leave requests, manager approval, balance tracking, team availability calendars.

#### 8. Payroll Processing Engine
- **Wages:** Link approved timesheets to hourly/daily/monthly wage calculations across multiple pay schedules.
- **Commissions:** Rules tied to sales (% of order value, tiered targets).
- **Deductions:** Tax templates (PAYE, NHIF, NSSF for Kenya; extensible), statutory and voluntary deductions.
- **Payslips:** Digital PDF generation with itemized breakdowns, mobile push notifications, historical archive.

#### 9. Expense & Asset Management
- **Expenses:** Offline receipt photo capture on mobile → finance approval workflow on web → payroll reimbursement integration.
- **Assets:** Track company equipment (vehicles, tablets, samples) through full lifecycle (Issued → In Use → Returned → Decommissioned).

#### 10. CRM & Task Dispatching
- **CRM:** Expand Outlets into full customer profiles with contact persons, interaction history, scheduled visit planning, and segmentation.
- **Task Dispatching:** Dispatcher assigns jobs/deliveries to field workers; workers accept, navigate, complete, and attach evidence. Priority queuing with SLA timers. Route optimization via PostGIS/PgRouting.

#### 11. Geofence Controls & Advanced Routing
- Interactive polygon drawing on the web Leaflet map.
- Named zones with configurable alert rules (entry, exit, dwell time).
- PgRouting integration for shortest-path and traveling salesman problems.
- Real-time route deviation alerts.

#### 12. Analytics & Performance Reporting
- Worker performance metrics (distance, visits, time-on-site).
- Sales dashboards (revenue by worker, outlet, product; target vs. actual).
- Compliance reports (shift adherence, GPS gaps, geofence violations).
- Export to PDF, CSV/Excel, and scheduled email digests.
