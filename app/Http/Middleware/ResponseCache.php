<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseCache
{
    public function handle(
        Request $request,
        Closure $next,
        ?string $seconds = null,
        string $visibility = 'public'
    ): Response {
        $response = $next($request);

        $seconds = $this->seconds($seconds);

        $visibility = in_array($visibility, ['public', 'private'], true)
            ? $visibility
            : 'public';

        $response->headers->set(
            'Cache-Control',
            "{$visibility}, max-age={$seconds}"
        );

        return $response;
    }

    private function seconds(?string $seconds): int
    {
        if ($seconds === null || $seconds === '') {
            return 120;
        }

        $seconds = (int) $seconds;

        return $seconds > 0 ? $seconds : 120;
    }
}
