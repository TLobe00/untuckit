<?php

namespace App\Http\Controllers\webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp;
use Log;
use App\shopifyapi;

class Orders extends Controller {
    public function listen(Request $request) {
        $data = collect($request->json()->all());

        $shopifyapisave = new shopifyapi;
        $shopifyapisave->savetext = $data;
        $shopifyapisave->save();

        return response("OK", 200);
    }
}
