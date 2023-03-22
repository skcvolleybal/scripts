<?php 
require '../vendor/autoload.php';

$env = json_decode(file_get_contents("../../../env.json"));

\Sentry\init(['dsn' => 'https://45df5d88f1084fcd96c8ae9fa7db50c7@o4504883122143232.ingest.sentry.io/4504883124240384',
'environment' => $env->Environment ]);

?>