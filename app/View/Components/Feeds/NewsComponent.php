<?php

namespace App\View\Components\Feeds;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\News;

class NewsComponent extends Component
{
    public News $news;
    public string $viewsType;

    public function __construct(News $news, string $viewsType = 'RSS')
    {
        $this->news = $news;
        $this->viewsType = strtoupper($viewsType);
    }

    public function render(): View|Closure|string
    {
        $news = $this->news;
        $viewsType = strtoupper($this->viewsType);
        return view('components.feeds.news-component', compact('news','viewsType'));
    }
}
