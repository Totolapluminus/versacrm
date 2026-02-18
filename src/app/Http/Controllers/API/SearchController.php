<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{
    public function search(Request $request){
        $res = Http::withHeaders([
            'X-API-Key' => config('myapp.melsu_api_key'),
        ])->get('https://dekanat.melsu.ru/api/v1/external/users', request()->query());

        return response()->json($res->json('users'));
    }
}
