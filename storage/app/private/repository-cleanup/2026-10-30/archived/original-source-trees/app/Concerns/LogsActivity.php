<?php

namespace App\Concerns;

use Spatie\Activitylog\Traits\LogsActivity as SpatieLogsActivity;

trait LogsActivity
{
    use SpatieLogsActivity {
        bootLogsActivity as protected bootSpatieLogsActivity;
    }

    protected static function bootLogsActivity(): void
    {
        // The deployment switch skips activity-only listeners, including the
        // old-attribute snapshot. Other model observers and auditing still run.
        if (config('activitylog.enabled', true)) {
            static::bootSpatieLogsActivity();
        }
    }
}
