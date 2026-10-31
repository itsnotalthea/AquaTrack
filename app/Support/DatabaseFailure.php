<?php

namespace App\Support;

use Illuminate\Database\LostConnectionException;
use PDOException;
use Throwable;

class DatabaseFailure
{
    public static function isUnavailable(Throwable $exception): bool
    {
        if ($exception instanceof LostConnectionException) {
            return true;
        }

        if (! $exception instanceof PDOException) {
            return false;
        }

        $errorInfo = $exception->errorInfo ?? $exception->getPrevious()?->errorInfo;
        $driverCode = (int) ($errorInfo[1] ?? 0);
        $sqlState = (string) ($errorInfo[0] ?? $exception->getCode());

        return str_starts_with($sqlState, '08')
            || in_array($driverCode, [1045, 1049, 2002, 2003, 2005, 2006, 2013, 2055], true);
    }

    public static function message(): string
    {
        return 'AquaTrack cannot connect to its database. Check that MySQL is running, the DB settings are correct, and the database is available, then try again.';
    }
}
