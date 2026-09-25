<?php

namespace App\Catalogue;

/** Small geometry helpers. Coordinates are [lng, lat] as in GeoJSON. */
class Geo
{
    private const EARTH_RADIUS_KM = 6371.0088;

    public static function distanceKm(array $a, array $b): float
    {
        [$lng1, $lat1] = $a;
        [$lng2, $lat2] = $b;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1, sqrt($h)));
    }

    public static function lineLengthKm(array $line): float
    {
        $sum = 0.0;
        for ($i = 1, $n = count($line); $i < $n; $i++) {
            $sum += self::distanceKm($line[$i - 1], $line[$i]);
        }

        return $sum;
    }

    /**
     * Chain unordered ways into as few continuous lines as possible, greedily
     * attaching the way whose nearer end is closest to the current line's tail.
     * A new segment starts when the gap exceeds $maxGapKm.
     *
     * @param  list<list<array{0: float, 1: float}>>  $ways
     * @return list<list<array{0: float, 1: float}>>
     */
    public static function stitch(array $ways, float $maxGapKm = 0.05): array
    {
        $ways = array_values(array_filter($ways, fn ($w) => count($w) > 1));
        if (! $ways) {
            return [];
        }

        $segments = [];
        $current = array_shift($ways);

        while ($ways) {
            $head = $current[0];
            $tail = end($current);
            $best = null;
            foreach ($ways as $i => $w) {
                $first = $w[0];
                $last = end($w);
                // [gap, index, attach at head?, reverse the way?]
                foreach ([
                    [self::distanceKm($tail, $first), $i, false, false],
                    [self::distanceKm($tail, $last), $i, false, true],
                    [self::distanceKm($head, $last), $i, true, false],
                    [self::distanceKm($head, $first), $i, true, true],
                ] as $candidate) {
                    if ($best === null || $candidate[0] < $best[0]) {
                        $best = $candidate;
                    }
                }
            }
            [$gap, $i, $atHead, $reverse] = $best;
            $w = $reverse ? array_reverse($ways[$i]) : $ways[$i];
            unset($ways[$i]);

            if ($gap > $maxGapKm) {
                $segments[] = $current;
                $current = $w;
            } elseif ($atHead) {
                $current = array_merge(array_slice($w, 0, -1), $current);
            } else {
                $current = array_merge($current, array_slice($w, 1));
            }
        }
        $segments[] = $current;

        // Longest segment first; it carries the trailhead and elevation profile.
        usort($segments, fn ($a, $b) => self::lineLengthKm($b) <=> self::lineLengthKm($a));

        return $segments;
    }

    /** Points spaced evenly along a line, each as [lng, lat, kmFromStart]. */
    public static function resample(array $line, int $count): array
    {
        $total = self::lineLengthKm($line);
        if ($total <= 0 || $count < 2) {
            return [[...$line[0], 0.0]];
        }
        $step = $total / ($count - 1);
        $out = [[$line[0][0], $line[0][1], 0.0]];
        $walked = 0.0;
        $target = $step;
        for ($i = 1, $n = count($line); $i < $n && count($out) < $count - 1; $i++) {
            $seg = self::distanceKm($line[$i - 1], $line[$i]);
            while ($seg > 0 && $walked + $seg >= $target && count($out) < $count - 1) {
                $t = ($target - $walked) / $seg;
                $out[] = [
                    $line[$i - 1][0] + ($line[$i][0] - $line[$i - 1][0]) * $t,
                    $line[$i - 1][1] + ($line[$i][1] - $line[$i - 1][1]) * $t,
                    $target,
                ];
                $target += $step;
            }
            $walked += $seg;
        }
        $last = end($line);
        $out[] = [$last[0], $last[1], $total];

        return $out;
    }

    /** Douglas–Peucker simplification with a tolerance in degrees. */
    public static function simplify(array $line, float $tolerance): array
    {
        if (count($line) < 3) {
            return $line;
        }
        $maxD = 0.0;
        $index = 0;
        $last = count($line) - 1;
        for ($i = 1; $i < $last; $i++) {
            $d = self::perpendicular($line[$i], $line[0], $line[$last]);
            if ($d > $maxD) {
                $maxD = $d;
                $index = $i;
            }
        }
        if ($maxD <= $tolerance) {
            return [$line[0], $line[$last]];
        }
        $left = self::simplify(array_slice($line, 0, $index + 1), $tolerance);
        $right = self::simplify(array_slice($line, $index), $tolerance);

        return array_merge(array_slice($left, 0, -1), $right);
    }

    private static function perpendicular(array $p, array $a, array $b): float
    {
        [$x, $y] = $p;
        [$x1, $y1] = $a;
        [$x2, $y2] = $b;
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        if ($dx == 0 && $dy == 0) {
            return hypot($x - $x1, $y - $y1);
        }

        return abs($dy * $x - $dx * $y + $x2 * $y1 - $y2 * $x1) / hypot($dx, $dy);
    }

    public static function pointInPolygon(array $point, array $polygon): bool
    {
        // $polygon: list of rings; first is outer, the rest are holes.
        if (! self::pointInRing($point, $polygon[0])) {
            return false;
        }
        foreach (array_slice($polygon, 1) as $hole) {
            if (self::pointInRing($point, $hole)) {
                return false;
            }
        }

        return true;
    }

    private static function pointInRing(array $point, array $ring): bool
    {
        [$x, $y] = $point;
        $inside = false;
        for ($i = 0, $j = count($ring) - 1, $n = count($ring); $i < $n; $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];
            if ((($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Walking time using Tobler's hiking function, v = 6·e^(−3.5·|slope + 0.05|) km/h,
     * over consecutive profile points [km, metres].
     */
    public static function toblerMinutes(array $profile): int
    {
        $hours = 0.0;
        for ($i = 1, $n = count($profile); $i < $n; $i++) {
            $dx = $profile[$i][0] - $profile[$i - 1][0];
            if ($dx <= 0) {
                continue;
            }
            $slope = (($profile[$i][1] - $profile[$i - 1][1]) / 1000) / $dx;
            $speed = 6 * exp(-3.5 * abs($slope + 0.05));
            $hours += $dx / $speed;
        }

        return (int) (round($hours * 60 / 5) * 5);
    }
}
