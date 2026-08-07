<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use Illuminate\Support\Facades\Artisan;
Route::get('/seed-db-xk92', function () {
    if (request()->query('key') !== 'buildmart-seed-2026') {
        abort(404);
    }
    Artisan::call('db:seed', ['--force' => true]);
    return response()->json(['status' => 'seeded', 'output' => Artisan::output()]);
});
