<?php

namespace App\Catalogue;

/** Finds the district (and economic region) containing a point, from az-districts.geojson. */
class DistrictLocator
{
    /** @var list<array{props: array, polygons: list<array>, bbox: array}> */
    private array $districts = [];

    public function __construct(?string $path = null)
    {
        $data = json_decode(file_get_contents($path ?? database_path('data/az-districts.geojson')), true);
        foreach ($data['features'] as $f) {
            $geom = $f['geometry'];
            $polygons = $geom['type'] === 'Polygon' ? [$geom['coordinates']] : $geom['coordinates'];
            $xs = $ys = [];
            foreach ($polygons as $poly) {
                foreach ($poly[0] as [$x, $y]) {
                    $xs[] = $x;
                    $ys[] = $y;
                }
            }
            $this->districts[] = [
                'props' => $f['properties'],
                'polygons' => $polygons,
                'bbox' => [min($xs), min($ys), max($xs), max($ys)],
            ];
        }
    }

    /** @return array{pcode: string, slug: string, name_az: string, name_en: string, region: string}|null */
    public function locate(float $lng, float $lat): ?array
    {
        foreach ($this->districts as $d) {
            [$minX, $minY, $maxX, $maxY] = $d['bbox'];
            if ($lng < $minX || $lng > $maxX || $lat < $minY || $lat > $maxY) {
                continue;
            }
            foreach ($d['polygons'] as $poly) {
                if (Geo::pointInPolygon([$lng, $lat], $poly)) {
                    return $d['props'];
                }
            }
        }

        return null;
    }

    public function all(): array
    {
        return array_column($this->districts, 'props');
    }
}
