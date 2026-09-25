# EcoTrail — Backend API

Laravel 13 JSON API for EcoTrail, a hiking-trail guide to Azerbaijan. The web frontend lives in [`ecotrail-frontend`](https://github.com/arto-rash/ecotrail-frontend).

## Endpoints

All under `/api`. Pass `?lang=az|en` (or `Accept-Language`); Azerbaijani is the default.

| Method | Path | Notes |
|---|---|---|
| GET | `/trails` | Filters: `q`, `region`, `district`, `difficulty[]`, `feature[]`, `max_duration`, `max_distance`, `featured`, `sort` |
| GET | `/trails/{slug}` | Route geometry, elevation profile, nearby places, reviews, sources |
| GET | `/trails/{slug}/nearby` | Three closest trails |
| POST | `/trails` | Community submission (saved as pending; district worked out from the coordinates) |
| GET | `/places` | Waterfalls, lakes, peaks, villages… Filters: `q`, `type[]`, `region`, `district`, `bbox`, `featured`, `sort` |
| GET | `/places/{slug}` · `/places/{slug}/nearby` | Place detail and neighbours |
| POST | `/trails/{slug}/reviews` · `/places/{slug}/reviews` | Reviews (honeypot field `website`, rate-limited) |
| GET | `/map` | GeoJSON of all trail routes and places |
| GET | `/regions` · `/districts` | 14 economic regions with counts; 74 districts |
| GET | `/guides` · POST `/guide-applications` | Verified guides; partner applications |
| GET | `/stats` · `/options` · `/health` | |

Data provenance and how to rebuild the catalogue: [`database/data/README.md`](database/data/README.md).

## Admin commands

Run on the server with `sudo docker exec -it ecotrail-api-1 php artisan <command>`.

| Command | What it does |
|---|---|
| `submissions:list [--all]` | Community trail submissions (pending by default) |
| `submissions:approve {slug} [--reject]` | Publish or reject a submission |
| `reviews:delete --latest=20` / `reviews:delete {id}…` | Find and remove spam reviews |
| `guides:create [--list-applications] [--from-application=ID]` | List a verified partner guide, optionally prefilled from an application |
| `catalogue:build` / `catalogue:sync` | Rebuild / load the open-data catalogue |

## Development

```sh
docker compose up -d --build
docker compose exec api php artisan migrate --seed
```

Or without Docker (SQLite): `composer install && php artisan migrate --seed && php artisan serve`.

Run tests with `php artisan test`.

## Production

```sh
cp .env.example .env   # APP_ENV=production, APP_DEBUG=false, APP_URL, APP_KEY, DB_PASSWORD,
                       # CORS_ALLOWED_ORIGINS=https://<your-vercel-domain>
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec api php artisan db:seed --force   # first deploy only
```

Migrations run automatically when the container starts. Put a TLS-terminating reverse proxy in front.
