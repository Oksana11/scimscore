<?php

namespace App\Http\Controllers;

use App\Services\Institutions\InstitutionResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionSearchController extends Controller
{
    public function __invoke(Request $request, InstitutionResolver $institutions): JsonResponse
    {
        $q = (string) $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']])['q'];

        return response()->json($institutions->suggestions($q));
    }
}
