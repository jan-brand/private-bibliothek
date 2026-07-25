<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Throwable;

final class SystemStatus extends Component
{
    public int $refreshCount = 0;

    public function refreshStatus(): void
    {
        $this->refreshCount++;
    }

    public function render(): View
    {
        $databaseAvailable = true;

        try {
            DB::select('select 1');
        } catch (Throwable) {
            $databaseAvailable = false;
        }

        return view('livewire.system-status', [
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => Application::VERSION,
            'databaseConnection' => (string) config('database.default'),
            'databaseAvailable' => $databaseAvailable,
            'checkedAt' => now()->format('d.m.Y H:i:s'),
        ]);
    }
}
