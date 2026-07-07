# OmniRoute: System Architecture & Requirements

## 1. Core Identity
OmniRoute is a Multi-Tenant Field Force Automation SaaS built on Laravel 11. It combines offline-first mobile sales operations with real-time IoT GPS tracking.

## 2. Infrastructure Rules (STRICT COMPLIANCE)
- **Multi-Tenancy:** We use `stancl/tenancy` with the PostgreSQL Schema Isolation driver. 
- **Database Engine:** PostgreSQL exclusively. You MUST use the `postgis/postgis:15-3.3` image in docker-compose.
- **Migration Separation:** System migrations (tenants, domains, users) stay in `database/migrations/`. Tenant migrations (outlets, tracking_logs) MUST be placed in `database/migrations/tenant/`. 
- **Tenancy Config Fix:** In `config/tenancy.php`, the `migration_parameters` array MUST explicitly set `'--path' => [database_path('migrations/tenant')]` and `'--realpath' => true`.
- **PostGIS Search Path Fix:** When switching to a tenant schema, PostGIS types (like `geography`) disappear. You MUST add an event listener to `TenancyInitialized` (likely in TenancyServiceProvider) that executes: `\Illuminate\Support\Facades\DB::statement('SET search_path TO ' . $tenant->getTenantKey() . ', public');`

## 3. Database Schema
**System Schema (public):**
- `tenants`: id (string/uuid), data (jsonb)
- `domains`: id, tenant_id, domain

**Tenant Schemas (e.g., tenant_foo):**
All CRM entities here MUST include `version` (integer) and `last_updated_at` (timestamp) for LWW offline sync logic. SoftDeletes must be used.
- `outlets`: id, name, location (PostGIS Point), version, last_updated_at, deleted_at
- `tracking_logs`: id, user_id, location (PostGIS Point), speed, recorded_at_mobile, synced_at

## 4. API Sync Contracts (LWW Logic)
- **Pull Sync (Server to Mobile):** `POST /api/v1/sync/pull`. Payload: `{ "last_sync_timestamp": "Y-m-d H:i:s", "collections": ["outlets"] }`.
- **Push Sync (Mobile to Server):** `POST /api/v1/sync/push`. Payload: `{ "client_timestamp": "...", "data": { "outlets": [...], "tracking_logs": [...] } }`. Server updates if mobile `last_updated_at` is newer than server, otherwise rejects.