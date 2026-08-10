<?php

namespace App\Helpers;

class ColorHelper
{
    public const DEFAULT_PRIMARY = '#C59B27';
    public const DEFAULT_SIDEBAR = '#1E1E1E';

    /**
     * Tentukan warna teks kontras (terang/gelap) untuk latar belakang hex.
     *
     * Menggunakan formula luminance ITU-R BT.601 (YIQ).
     * - Background terang  -> #1E1E1E (gelap)
     * - Background gelap   -> #FFFFFF (terang)
     */
    public static function getContrastTextColor(?string $hexColor): string
    {
        [$r, $g, $b] = self::parseHex($hexColor);

        if ($r === null) {
            return self::DEFAULT_SIDEBAR;
        }

        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $yiq >= 150 ? self::DEFAULT_SIDEBAR : '#FFFFFF';
    }

    /**
     * Parsing hex menjadi [r, g, b] int (0-255).
     * Mendukung format 3 atau 6 digit. Mengembalikan [null, null, null] bila tidak valid.
     *
     * @return array{0:int|null,1:int|null,2:int|null}
     */
    public static function parseHex(?string $hexColor): array
    {
        if (! is_string($hexColor)) {
            return [null, null, null];
        }

        $hex = ltrim(trim($hexColor), '#');

        if (preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return [
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2)),
            ];
        }

        if (preg_match('/^[0-9a-fA-F]{3}$/', $hex)) {
            return [
                hexdec(str_repeat($hex[0], 2)),
                hexdec(str_repeat($hex[1], 2)),
                hexdec(str_repeat($hex[2], 2)),
            ];
        }

        return [null, null, null];
    }

    /**
     * Normalisasi warna hex agar aman untuk dipakai di CSS.
     * Kembalikan fallback bila input tidak valid.
     */
    public static function normalizeHex(?string $hexColor, string $fallback = self::DEFAULT_PRIMARY): string
    {
        [$r, $g, $b] = self::parseHex($hexColor);

        if ($r === null) {
            return $fallback;
        }

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }
}
