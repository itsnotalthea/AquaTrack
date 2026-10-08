<?php

namespace App\Models;

// Marikina City barangay list
// for the address dropdowns and the barangay report filter. Not backed by a table.
class Barangay
{
    public const LIST = [
        'Bagong Nayon',
        'Balante',
        'Barangka Drive',
        'Barangka Ibaba',
        'Barangka Ilaya',
        'Barangka Ito',
        'Buayang Bato',
        'Buliruhan',
        'Calvary',
        'Camarin',
        'Canlubang',
        'Capitol Commons',
        'Caridad',
        'Concepcion',
        'Dela Paz',
        'Don Manuel',
        'Doña Josefa',
        'Fabian',
        'Gatabuk',
        'Greenhills',
        'Guerrero',
        'Happy Homes',
        'Hillcrest',
        'Industrial',
        'Kabayanan',
        'Kalumpang',
        'Kaunlaran',
        'Kristong Hari',
        'Lambingan',
        'Lawa',
        'Libingan',
        'Malanday',
        'Marikina Heights',
        'Montecillo',
        'Morning Breeze',
        'Mukat',
        'Parang',
        'Pasig',
        'Payapa',
        'Pinambaran',
        'Poblacion',
        'San Isidro',
        'San Jose',
        'San Luis',
        'San Roque',
        'Santa Cruz',
        'Santa Maria',
        'Santo Niño',
        'Tanza',
        'UP Village',
    ];

    public static function names(): array
    {
        return self::LIST;
    }

    public static function isValid(?string $barangay): bool
    {
        return $barangay !== null && in_array($barangay, self::LIST, true);
    }
}
