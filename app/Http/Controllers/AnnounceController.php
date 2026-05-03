<?php

namespace App\Http\Controllers;

use App\Services\AnnounceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class AnnounceController extends Controller
{
    public function __construct(private readonly AnnounceService $service) {}

    public function handle(Request $request): BaseResponse
    {
        // xbtt backend redirect must be a true HTTP 302, not 200 with a header
        if ($url = $this->service->xbttRedirectUrl($request)) {
            return response('', 302, ['Location' => $url]);
        }

        $body = $this->service->handle($request);

        return response($body, 200, [
            'Content-Type'   => 'text/plain',
            'Pragma'         => 'no-cache',
            'Cache-Control'  => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
