<?php

use App\Http\Controllers\PromptController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('prompts.index');
});

Route::resource('prompts', PromptController::class);
