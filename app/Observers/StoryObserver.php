<?php

namespace App\Observers;

use App\Models\Story;
use Illuminate\Support\Facades\Storage;

class StoryObserver
{
    public function deleted(Story $story): void
    {
        Storage::delete($story->media_path);
    }
}
