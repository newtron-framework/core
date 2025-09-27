<?php
declare(strict_types=1);

use Newtron\Core\Routing\Route;

Route::get('/test', function () {
  return 'test_value';
});
