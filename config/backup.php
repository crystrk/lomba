<?php

return [

    'disk' => env('BACKUP_DISK', 's3'),

    'prefix' => env('BACKUP_PREFIX', 'backups-arena/'.env('APP_ENV', 'production')),

    'retention' => (int) env('BACKUP_RETENTION', 112),

];
