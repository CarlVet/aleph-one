<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Records a tamper-evident audit trail (who changed what, when) for the model.
 *
 * Only changed attributes are logged. Models holding encrypted or otherwise
 * sensitive columns must list them in $activityLogExcept so their values never
 * leak into the activity_log table in plaintext.
 */
trait TracksChanges
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logUnguarded()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logExcept($this->activityLogExcept ?? []);
    }
}
