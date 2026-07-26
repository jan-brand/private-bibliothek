<?php

return [
    'dnb_sru_url' => env(
        'DNB_SRU_URL',
        'https://services.dnb.de/sru/dnb',
    ),

    'zdb_sru_url' => env(
        'ZDB_SRU_URL',
        'https://services.dnb.de/sru/zdb',
    ),

    'timeout_seconds' => (int) env('METADATA_TIMEOUT_SECONDS', 12),
];
