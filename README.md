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
