<?php

require_once 'bootstrap/app.php';

echo 'FINCRA_BUSINESS_ID from env: '.env('FINCRA_BUSINESS_ID').PHP_EOL;
echo 'Fincra business_id from config: '.config('services.fincra.business_id').PHP_EOL;
echo 'FINCRA_BASE_URL from env: '.env('FINCRA_BASE_URL').PHP_EOL;
echo 'Fincra base_url from config: '.config('services.fincra.base_url').PHP_EOL;
echo 'FINCRA_API_KEY set: '.(env('FINCRA_API_KEY') ? 'YES' : 'NO').PHP_EOL;
