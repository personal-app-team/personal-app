<?php

namespace App\Livewire\WorkRequests;

use App\Models\WorkRequest;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $workRequests = WorkRequest::with(['initiator', 'brigadier', 'workType'])
            ->where('initiator_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.work-requests.index', compact('workRequests'));
    }
}
