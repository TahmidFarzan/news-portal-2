<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeedResponse
{
    public function handle(Request $request, Closure $next, string $type = 'rss'): Response
    {
        $type = strtolower($type);

        if (! in_array($type, ['rss', 'atom'], true)) {
            $type = 'rss';
        }

        $request->attributes->set('viewsType', strtoupper($type));

        $response = $next($request);

        $contentTypes = [
            'rss'  => 'application/rss+xml; charset=UTF-8',
            'atom' => 'application/atom+xml; charset=UTF-8',
        ];

        $response->headers->set('Content-Type', $contentTypes[$type]);

        return $response;
    }
}
