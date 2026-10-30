<?php

namespace App\Models;

/**
 * The 16 official barangays of Marikina City, grouped by district.
 *
 * Values are stored in their canonical short form. The labels keep the
 * common aliases visible in dropdowns without polluting reports or the
 * database with duplicate spellings.
 */
class Barangay
{
    public const DISTRICTS = [
        'District 1' => [
            'Barangka' => 'Barangka',
            'Calumpang' => 'Calumpang',
            'Industrial Valley Complex' => 'Industrial Valley Complex (IVC)',
            'Jesus de la Peña' => 'Jesus de la Peña',
            'Malanday' => 'Malanday',
            'San Roque' => 'San Roque',
            'Santa Elena' => 'Santa Elena',
            'Santo Niño' => 'Santo Niño',
            'Tañong' => 'Tañong',
        ],
        'District 2' => [
            'Concepcion Uno' => 'Concepcion Uno (Concepcion I)',
            'Concepcion Dos' => 'Concepcion Dos (Concepcion II)',
            'Fortune' => 'Fortune',
            'Marikina Heights' => 'Marikina Heights',
            'Nangka' => 'Nangka',
            'Parang' => 'Parang',
            'Tumana' => 'Tumana',
        ],
    ];

    public static function names(): array
    {
        return array_merge(...array_values(array_map('array_keys', self::DISTRICTS)));
    }

    public static function districts(): array
    {
        return self::DISTRICTS;
    }

    public static function isValid(?string $barangay): bool
    {
        return $barangay !== null && in_array($barangay, self::names(), true);
    }

    public static function label(?string $barangay): ?string
    {
        foreach (self::DISTRICTS as $barangays) {
            if ($barangay !== null && isset($barangays[$barangay])) {
                return $barangays[$barangay];
            }
        }

        return $barangay;
    }
}
