<?php 
require __DIR__ . "/../public/index.php";

Artisan::call('config:cache');
Artisan::call('route:cache');
Artisan::call('view:cache');