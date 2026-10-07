<?php

namespace App\Mcp\Resources;

use App\Mcp\Markdown;
use App\Models\Item;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('item')]
#[Description('One item by key, including comments and activity.')]
#[MimeType('text/markdown')]
class ItemResource extends Resource implements HasUriTemplate
{
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('item://{key}');
    }

    public function handle(Request $request): Response
    {
        try {
            $item = Item::findByKeyOrFail((string) $request->get('key'));
        } catch (ModelNotFoundException) {
            return Response::error('Item not found.');
        }

        Gate::forUser($request->user())->authorize('view', $item);

        return Response::text(Markdown::item($item));
    }
}
