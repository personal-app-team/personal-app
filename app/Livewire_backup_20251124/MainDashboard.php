<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\WorkRequest;
use App\Models\Category;
use App\Models\Contractor;

class MainDashboard extends Component
{
    public function render()
    {
        $stats = [
            'users_count' => User::count(),
            'work_requests_count' => WorkRequest::count(),
            'categories_count' => Category::count(),
            'contractors_count' => Contractor::count(),
        ];

        return view('livewire.dashboard', compact('stats'))
            ->layout('layouts.app');
    }
}
