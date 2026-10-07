<?php

use App\Livewire\Items\Show as ItemShow;
use App\Livewire\Projects\Board;
use App\Livewire\Projects\Inbox;
use App\Livewire\Projects\Index as ProjectIndex;
use App\Livewire\Projects\ItemList;
use App\Livewire\Projects\Settings as ProjectSettings;
use App\Livewire\Settings\Tokens;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', fn () => auth()->check() ? redirect()->route('projects.index') : view('welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', 'projects')->name('dashboard');

    Route::get('projects', ProjectIndex::class)->name('projects.index');
    Route::get('projects/{project}', Board::class)->name('projects.board');
    Route::get('projects/{project}/list', ItemList::class)->name('projects.list');
    Route::get('projects/{project}/inbox', Inbox::class)->name('projects.inbox');
    Route::get('projects/{project}/settings', ProjectSettings::class)->name('projects.settings');

    Route::get('items/{item}', ItemShow::class)->name('items.show');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    Route::get('settings/tokens', Tokens::class)->name('settings.tokens');
});

require __DIR__.'/auth.php';
