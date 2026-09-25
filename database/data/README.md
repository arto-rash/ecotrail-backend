# Catalogue data

Everything in this folder is either an official list or generated from open data.

| File | What | Source & licence |
|---|---|---|
| `catalogue.json` | Trails and places shown on the site | Built by `php artisan catalogue:build` from OpenStreetMap (ODbL), Wikidata (CC0), Wikipedia (CC BY-SA), Wikimedia Commons (per-image licences) and SRTM elevation via OpenTopoData (public domain). Every record carries its own `sources`. |
| `economic-regions.php` | The 14 economic regions and their member districts | Presidential Decree of 7 July 2021 — https://president.az/az/articles/view/52389 |
| `az-districts.geojson` | 74 district/city boundaries, simplified, tagged with region | OCHA COD-AB Azerbaijan admin-1 (CC BY-IGO), https://data.humdata.org/dataset/cod-ab-aze |
| `local-photos.json` | Credits for the photos bundled with the frontend, keyed by OSM/Wikidata id | Wikimedia Commons (licences listed per photo) |

## Rebuilding

```sh
php artisan catalogue:build   # ~4 minutes; queries public APIs politely
git diff database/data/catalogue.json   # review before committing
php artisan catalogue:sync    # load into the database (deploy runs this automatically)
```

Which OSM routes and Wikidata landmarks are included is configured at the top of
`app/Catalogue/CatalogueBuilder.php`. Trail stats (walking time, ascent) are computed
from the route geometry and flagged as estimates. Routes and places in Karabakh and
East Zangezur are excluded because of landmine contamination.
