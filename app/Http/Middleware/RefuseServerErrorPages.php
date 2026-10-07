<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apache serves its error pages through the front controller (ErrorDocument). When the web
 * server refuses a request itself, such as a PATCH or DELETE blocked by the host's
 * ModSecurity rules, that error page reaches Laravel as a GET to the original URL. Running
 * the route then answers a refused PATCH with the item it was meant to change, under the
 * server's 403, and nothing is saved. Answer with the server's refusal instead, and say how
 * to get the request through.
 */
class RefuseServerErrorPages
{
    private const OVERRIDABLE = ['PATCH', 'PUT', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $status = $request->server->getInt('REDIRECT_STATUS', 200);

        if ($status < 400) {
            return $next($request);
        }

        $method = strtoupper($request->server->getString('REDIRECT_REQUEST_METHOD'));

        abort($status, in_array($method, self::OVERRIDABLE, true)
            ? "The web server refused this {$method} request. Send it as POST with the header X-HTTP-Method-Override: {$method}."
            : 'The web server refused this request.');
    }
}
