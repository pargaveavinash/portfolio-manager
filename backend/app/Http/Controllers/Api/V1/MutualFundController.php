<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\MutualFund;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MutualFundController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json(['data' => []]);
        }

        $funds = MutualFund::where('scheme_name', 'like', '%' . $query . '%')
            ->orWhere('amfi_code', $query)
            ->limit(20)
            ->get();

        return response()->json(['data' => $funds]);
    }
}
