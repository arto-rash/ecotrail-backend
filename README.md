# EcoTrail — Backend API

Laravel 13 JSON API for EcoTrail, a hiking-trail guide to Azerbaijan. The web frontend lives in [`ecotrail-frontend`](https://github.com/arto-rash/ecotrail-frontend).

## Endpoints

All under `/api`:

| Method | Path | Notes |
|---|---|---|
| GET | `/trails` | Published trails. Filters: `q`, `region`, `difficulty[]`, `feature[]`, `max_duration`, `max_distance`, `featured`, `sort` (`popular`, `rating`, `distance`, `duration`, `name`), `per_page` |
| GET | `/trails/{slug}` | Trail detail with reviews |
| GET | `/trails/{slug}/nearby` | Three closest published trails |
| POST | `/trails` | Community submission (multipart, photos optional). Saved as `pending`; rate-limited to 10/hour |
| GET | `/regions` | Regions with published trail counts |
| GET | `/guides` | Partner guides and agencies |
| GET | `/stats` | Totals for the home page |
| GET | `/options` | Difficulty, route-type and feature enums |
| GET | `/health` | Health check |

## Development

```sh
docker compose up -d --build
docker compose exec api php artisan migrate --seed
```

Or without Docker (SQLite): `composer install && php artisan migrate --seed && php artisan serve`.

Run tests with `php artisan test`.

## Seed data

`database/data/trails.php` holds 13 real Azerbaijani trails. Coordinates, distances and times are **approximate** and should be checked before being treated as authoritative. Reviews and guides in `CatalogueSeeder` are sample data for development.

## Production

```sh
cp .env.example .env   # APP_ENV=production, APP_DEBUG=false, APP_URL, APP_KEY, DB_PASSWORD,
                       # CORS_ALLOWED_ORIGINS=https://<your-vercel-domain>
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec api php artisan db:seed --force   # first deploy only
```

Migrations run automatically when the container starts. Put a TLS-terminating reverse proxy in front.
