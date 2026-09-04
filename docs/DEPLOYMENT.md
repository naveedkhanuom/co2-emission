# Deployment

> How to get this platform from a clone to serving clients. Written for Ubuntu +
> nginx + MySQL; the parts that are specific to *this* codebase rather than to
> Laravel generally are marked **⚠**, and those are the ones that bite.
>
> This application is **multi-tenant with a database per client**. Clients are
> identified by subdomain (`acme.yourdomain.com`), and each has their own MySQL
> database (`tenant_acme`). Nothing about that is optional — the application
> refuses to serve app routes outside a tenant.

- **Stack:** PHP 8.2+, Laravel 11, MySQL 8, `stancl/tenancy` v3
- **Central domain:** marketing page + `/admin` back-office
- **Tenant subdomains:** the application itself

---

## 1. System packages

```bash
sudo apt update
sudo apt install -y nginx mysql-server \
  php8.2-{fpm,mysql,gd,zip,bcmath,mbstring,intl,curl,xml} \
  tesseract-ocr poppler-utils git unzip
```

`tesseract-ocr` backs utility-bill OCR; `poppler-utils` backs `spatie/pdf-to-image`.
Without them those features fail at runtime, not at boot.

Install Composer and Node 18+ by your usual route.

## 2. DNS — wildcard record

Do this first; nothing below works without it.

```
A     yourdomain.com      →  <server-ip>
A     *.yourdomain.com    →  <server-ip>
```

**⚠** Clients are resolved from the subdomain by `InitializeTenancyBySubdomain`.
Without the wildcard, every client address fails at DNS and no amount of
application configuration helps.

## 3. MySQL

**⚠** `tenant:provision` issues `CREATE DATABASE` **at runtime**, and tenant
connections reuse the central credentials (`template_tenant_connection` is
`null` in `config/tenancy.php`). The application user therefore needs create
rights across the whole `tenant_%` namespace, not just one database:

```sql
CREATE DATABASE ghg_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ghg'@'localhost' IDENTIFIED BY 'strong-password';
GRANT ALL PRIVILEGES ON ghg_central.*  TO 'ghg'@'localhost';
GRANT ALL PRIVILEGES ON `tenant\_%`.*  TO 'ghg'@'localhost';
FLUSH PRIVILEGES;
```

A grant scoped to a single database provisions the first client and then fails.

## 4. Application

```bash
sudo mkdir -p /var/www/ghg && cd /var/www/ghg
git clone <repo> .
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env
php artisan key:generate
```

## 5. `.env`

**⚠ Do not copy a development `.env` to the server.** The one in the repo carries
three Laravel 10 key names that Laravel 11 does not read, and copying it carries
the bug across:

| Wrong (Laravel 10) | Right (Laravel 11) | Effect if wrong |
|---|---|---|
| `BROADCAST_DRIVER` | `BROADCAST_CONNECTION` | broadcaster silently falls back to `null` — every `broadcast()` is a no-op |
| `CACHE_DRIVER` | `CACHE_STORE` | works only by matching the default |
| `FILESYSTEM_DRIVER` | `FILESYSTEM_DISK` | works only by matching the default |

```env
APP_NAME="GHG Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=UTC

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ghg_central
DB_USERNAME=ghg
DB_PASSWORD=strong-password

TENANCY_CENTRAL_DOMAINS=yourdomain.com

SESSION_DRIVER=database
SESSION_DOMAIN=
QUEUE_CONNECTION=database
CACHE_STORE=database
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

TENANT_DEV_ACCOUNT_ENABLED=false

ANTHROPIC_API_KEY=
OCR_SPACE_API_KEY=
TESSERACT_PATH=/usr/bin/tesseract

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
```

Four of these are load-bearing, and the application enforces the first two at
boot (`app/Support/EnvironmentGuard.php`):

- **`APP_DEBUG=false`** — with `APP_ENV=production` and debug on, the app
  **refuses to boot**. Debug mode publishes stack traces, config and database
  credentials to anyone who can trigger an error, and an error on the login
  screen is enough.
- **`SESSION_DOMAIN` empty** — **⚠** setting it to `.yourdomain.com` (the natural
  thing to reach for when "login doesn't work across subdomains") shares one
  session cookie across every client. The session payload holds a numeric user
  id and the `sessions` table is central, so that single change is a
  cross-tenant login bypass. Leaving it unset scopes the cookie to the exact
  host, which is the behaviour you want. Guarded at boot.
- **`TENANCY_CENTRAL_DOMAINS`** — the **first** entry is canonical. `/admin` is
  registered on it only, and sign-in URLs are built from it.
- **`TENANT_DEV_ACCOUNT_ENABLED=false`** — when true, the same email and password
  are written into every client database as an account owner. Handing a client
  their database, the point of this architecture, hands over a hash that opens
  everyone else's. Use back-office impersonation instead.

## 6. Web server

**⚠** The `server_name` must cover both the bare domain and the wildcard.

```nginx
server {
    listen 80;
    server_name yourdomain.com *.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com *.yourdomain.com;
    root /var/www/ghg/public;

    ssl_certificate     /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    index index.php;
    charset utf-8;
    client_max_body_size 25M;          # bill and document uploads

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;      # see "Provisioning is synchronous" below
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

TLS needs a **wildcard certificate**, which requires a DNS-01 challenge —
HTTP-01 cannot issue one:

```bash
sudo certbot certonly --manual --preferred-challenges dns \
     -d yourdomain.com -d '*.yourdomain.com'
```

Use your DNS provider's certbot plugin instead of `--manual` if there is one, so
renewal is not a recurring manual task.

## 7. Migrate and cache

```bash
cd /var/www/ghg
php artisan migrate --force          # 9 central tables only
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

The central database holds only `tenants`, `domains`, `platform_users`,
`sessions`, `cache`, `jobs` and their siblings. **No client data belongs there.**
If you are migrating from a pre-tenancy install, drop the leftover application
tables from central once the data is in tenant databases — the absence of a
table is the cheapest guard against code that accidentally runs outside a tenant.

## 8. Queue worker

`QUEUE_CONNECTION=database`, so a worker must be running or queued jobs never
execute.

```ini
# /etc/systemd/system/ghg-worker.service
[Unit]
Description=GHG queue worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/ghg
ExecStart=/usr/bin/php /var/www/ghg/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now ghg-worker
```

## 9. Scheduler

```cron
# crontab -u www-data -e
* * * * * cd /var/www/ghg && php artisan schedule:run >> /dev/null 2>&1
```

**⚠** The scheduled work runs **inside each tenant** via `tenants:each`
(`routes/console.php`) — scheduled report delivery at 07:00 and anomaly scanning
at 06:00. Scheduling those commands bare would run them against the central
database, where no client would be scanned and a suspended account's reports
would still go out. The commands refuse to run without a tenant, so that mistake
fails loudly rather than silently.

## 10. Back-office account

```bash
php artisan platform:user you@yourdomain.com --name="Your Name"
```

The password is generated and printed **once**. Platform staff authenticate on
their own guard against their own table, so no back-office account can reach
client data except through a client's own workspace.

Sign in at `https://yourdomain.com/admin`.

## 11. Provision a client

From the back-office, or from the CLI:

```bash
php artisan tenant:provision acme \
    --name="Acme Ltd" \
    --company="Acme Ltd" \
    --owner-name="Jane Doe" \
    --owner-email="jane@acme.com" \
    --plan=standard
```

This creates `tenant_acme`, runs the tenant migrations, seeds roles, permissions,
271 emission factors, 190 emission sources and 119 industry templates, and
creates the first company and account owner. The owner password is printed once
if not supplied.

The client signs in at `https://acme.yourdomain.com`.

**Verify the tenant resolves end to end:**

```bash
curl https://acme.yourdomain.com/tenant-health
# {"tenant":"acme","name":"Acme Ltd","status":"active","database":"tenant_acme"}
```

This is the fastest way to tell a routing problem from a connection problem — it
reports which database the connection actually landed on.

---

## Deploying a change

**⚠ `tenants:migrate` is the step that is easy to forget and expensive to
forget.** `Jobs\MigrateDatabase` runs once, at tenant creation. After that, a new
file in `database/migrations/tenant/` reaches **new tenants only** — existing
clients keep the old schema and start throwing on the first query touching a new
column. Put it in the script so it is not a thing anyone has to remember:

```bash
#!/usr/bin/env bash
# deploy.sh
set -euo pipefail
cd /var/www/ghg

php artisan down --render="errors::503"

git pull --ff-only
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force          # central
php artisan tenants:migrate --force  # EVERY tenant — do not skip
php artisan tenants:each "schema:stamp"    # record what each tenant migrated to

# The built-in catalogue is AUTHORED in config/scope1_sources.php and COMPILED
# into each tenant's emission_factors. Editing the config and deploying without
# this leaves every tenant serving yesterday's numbers from the library while
# the Scope 1/2 entry pages serve today's — including the NCV and energy-basis
# EF the MRV workbook is built from. Cheap and idempotent; run it every deploy.
php artisan tenants:each "factors:import builtin"
php artisan tenants:each factors:check-drift   # exits non-zero if any tenant is stale

php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo systemctl restart ghg-worker
sudo systemctl reload php8.2-fpm

php artisan up
```

Restarting the worker matters: `queue:work` holds the old code in memory until it
is restarted.

**If you skip `tenants:migrate` anyway**, the application now notices rather than
500ing: `EnsureTenantSchemaIsCurrent` compares each tenant's recorded
`schema_version` against the newest tenant migration on disk and answers a 503
"Being updated" page instead of serving a workspace whose database is behind the
code. The back-office client list shows a **behind** badge for the same reason,
so drift is visible across the fleet without connecting to each database.

`schema:stamp` refuses to stamp a tenant that still has pending migrations, so a
half-finished `tenants:migrate` fails the deploy rather than recording a tenant
as current when it is not. To check without writing anything:

```bash
php artisan tenants:each "schema:stamp --check"
```

**Quote the inner command when it takes arguments.** `tenants:each` passes the
whole string to Symfony's parser, so `"schema:stamp --check"` works but an
unquoted `schema:stamp --check` would apply `--check` to `tenants:each` itself.

Forgetting `schema:stamp` does not take anything offline. The guard verifies
against the tenant's real migrations table before refusing, so a database that is
actually up to date is re-stamped and served; only a genuinely un-migrated tenant
gets the 503. Running it keeps the back-office accurate and saves that check.

---

## Operational notes

### Provisioning runs on the queue — the worker is not optional

`Platform\TenantController::store()` dispatches `App\Jobs\ProvisionTenantWorkspace`
and returns immediately. The job creates the database, runs the tenant migrations
and seeds the reference data — 10–15s, and the seeding grows with every factor
library imported.

**No worker, no provisioning.** If `ghg-worker` is not running, the back-office
reports success, shows the credentials, and the workspace never appears. The
screen says as much, but check the worker first when a client account does not
show up:

```bash
systemctl status ghg-worker
php artisan queue:failed          # a provision that failed lands here
```

The credentials are generated before dispatch, so they are correct and can be
sent immediately — the workspace just is not ready for a minute. The account
appears in the client list when the job completes.

**Do not "fix" this by queueing the `TenantCreated` pipeline.** It is the obvious
move and it breaks provisioning. `ProvisionTenant` runs `$tenant->run(...)` on the
line after `Tenant::create()`, and that needs the database the pipeline creates —
queue the pipeline and it writes into a database that does not exist yet.
`shouldBeQueued(false)` in `app/Providers/TenancyServiceProvider.php` is correct
and there is a test pinning it.

### Client uploads are private; branding is not

Supporting documents, AI-extraction source files and energy-certificate documents
are written to the **`local`** disk and served only through company-checked
controller actions. Company and app logos stay on the **`public`** disk, because
they have to render on the unauthenticated login screen.

**⚠** The public disk is served by stancl's `/tenancy/assets/{path}` route, whose
only middleware is `InitializeTenancyBySubdomain` — no authentication, no company
check, and no `EnsureTenantIsActive`, so even a suspended client's public-disk
files stay reachable. Treat that disk as world-readable and put nothing on it
that is not meant to be.

If you are upgrading an install that predates this, documents uploaded earlier
are still on the public disk. The download actions fall back to reading them
there so they keep working — which means **the exposure is not closed until they
are moved**. There is a command for it:

```bash
php artisan tenants:each "documents:privatise --pretend"   # list what would move
php artisan tenants:each "documents:privatise"            # apply
```

It copies, verifies, then deletes — never the other way round — and leaves
`company_logos/` and `app/` alone, because those have to stay publicly readable
for the login screen. It is safe to re-run.

Once every tenant is clear, the `public` fallbacks in
`EmissionRecordController::downloadDocument`, `DocumentExtractionController::store`
and `EnergyAttributeCertificateController::destroy` can be removed.

### The EAD MRV workbook template

The regulated MRV layer (`/mrv`) exports a filled **EAD "Deliverable C"**
workbook by loading EAD's own file and writing values into its cells, so the
download keeps EAD's formatting, dropdowns and inter-sheet formulas.

**That file is not shipped with the application, and must not be committed.** It
carries EAD's confidentiality notice — its contents "must not be distributed
without prior consent" — and EAD issues it to each operator directly. Install
the copy your deployment was issued:

```bash
mkdir -p storage/app/templates
cp "20260227 - Deliverable C Template_v8 1.xlsx" storage/app/templates/ead_deliverable_c.xlsx
```

Or point `MRV_EAD_TEMPLATE_PATH` at it anywhere on disk.

Note the path is **not** under `storage/tenant*/`: the workbook is shared
reference data, byte-identical for every client, and `storage_path()` is
suffixed per tenant. Putting it inside a tenant directory is what previously
broke every export.

**The cell map is pinned to v8.1.** `EadWorkbookFiller` writes to fixed rows
(`SRC_FIRST_ROW = 43`, `STREAM_FIRST_ROW = 75`). If EAD issues a version that
moves them, the export will write into the wrong cells of a regulatory
submission — verify a sample export after any template change.

### Emission factor libraries

Published factor sets are imported from the publishers' own files, committed
under `database/factors/sources/` with their SHA-256 hashes and licences recorded
in the MANIFEST there. A factor's provenance therefore resolves to specific bytes
that can still be checked years later, rather than to a URL that may have moved.

```bash
php artisan tenants:each "factors:import defra --pretend"   # parse and report
php artisan tenants:each "factors:import defra"             # apply
```

### Catalogue drift

The built-in catalogue exists twice: authored in `config/scope1_sources.php` and
`config/scope2_sources.php`, and compiled into each tenant's `emission_factors`.
Only the config half is edited by hand, so the compiled half can go stale.

```bash
php artisan tenants:each factors:check-drift
```

Read only, and exits non-zero when a tenant's stored rows disagree with the
deployed catalogue — which is why it is in the deploy script above. It reports
two things: entries the library cannot resolve at all (never compiled), and
entries whose stored value has moved away from the config.

Nothing about stale rows looks wrong from the outside. The Scope 1/2 entry pages
read the config directly and would show the new number; everything reading the
library — the factor list, reports, and the NCV and energy-basis EF that the MRV
workbook's tier calculations are built from — would show the old one.

DEFRA/DESNZ 2026 adds ~2,600 factors and ~1,600 emission sources per tenant, and
takes roughly 12s each. Re-running supersedes rather than duplicating: the
previous edition's rows are retired with a `valid_to` date and stay readable, so
a figure computed against them still resolves to the row that produced it.

**⚠ Memory.** Reading the DESNZ workbook costs about 66 MB through
PhpSpreadsheet. The command raises its own `memory_limit` to 512 MB when it finds
less, so a CLI left at the 128 MB default does not die part-way through with an
error naming a library file.

**⚠ Not part of provisioning.** This is deliberately not in
`TenantDatabaseSeeder`: provisioning already runs inline in the HTTP request, and
adding ~12s to it would push onboarding past the FPM timeout. Run it for new
tenants after `tenant:provision`, or move provisioning to the queue first (see
"Provisioning is synchronous" above).

### Backups

Every client is a separate database, so a backup job that names one database
backs up one client:

```bash
mysql -N -e "SHOW DATABASES LIKE 'tenant\_%'" \
  | xargs -I{} sh -c 'mysqldump --single-transaction {} | gzip > /backups/{}-$(date +%F).sql.gz'
mysqldump --single-transaction ghg_central | gzip > /backups/central-$(date +%F).sql.gz
```

Per-tenant uploads live in `storage/tenant{id}/` and need backing up too.

### Suspending a client

```bash
php artisan tinker --execute='App\Models\Tenant::find("acme")->update(["status" => "suspended"]);'
```

Or use the back-office. `EnsureTenantIsActive` is ordered ahead of `Authenticate`,
so a suspended client sees the unavailable page rather than a login form. Note
the caveat above: public-disk files remain reachable.

---

## Health checks

| Check | Command | Expected |
|---|---|---|
| App boots | `curl https://yourdomain.com/up` | 200 |
| Central routes | `curl https://yourdomain.com/` | marketing page |
| Tenant resolves | `curl https://acme.yourdomain.com/tenant-health` | correct database name |
| Back-office | `curl -I https://yourdomain.com/admin` | 302 to `/admin/login` |
| Worker alive | `systemctl status ghg-worker` | active (running) |
| Schema drift | `php artisan tenants:each "schema:stamp --check"` | every tenant up to date |

---

## Troubleshooting

**"Tenant could not be identified on domain"** — the subdomain has no row in the
central `domains` table, or the request arrived on a host that is not a
subdomain of a `TENANCY_CENTRAL_DOMAINS` entry.

**App routes 404 on the central domain** — correct and deliberate. The
application is only served on tenant subdomains; the central domain has the
marketing page and `/admin` and nothing else.

**A client 500s on a page that works for others** — likely schema drift. Run
`php artisan tenants:migrate --force`.

**Provisioning times out** — see "Provisioning is synchronous". Raise
`fastcgi_read_timeout`, or provision from the CLI.

**Refuses to boot with a message about `APP_DEBUG`** — working as intended. Set
`APP_DEBUG=false` and run `php artisan config:clear`.
