<?php

namespace App\View\Components\Sitemaps;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\Story;

class StoryComponent extends Component
{
    public Story $story;

    public function __construct(Story $story)
    {
        $this->story = $story;
    }

    public function render(): View|Closure|string
    {
        $story = $this->story;
        return view('components.sitemaps.story-component',compact('story'));
    }
}
