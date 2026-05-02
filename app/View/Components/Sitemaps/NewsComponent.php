<?php

namespace App\View\Components\Sitemaps;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\News;

class NewsComponent extends Component
{
    public News $news;

    public function __construct(News $news)
    {
        $this->news = $news;
    }

    public function render(): View|Closure|string
    {
        $news = $this->news;
        return view('components.sitemaps.news-component',compact('news'));
    }
}
