<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->histories()->latest('created_at');

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->string('subject_type'));
        }

        return response()->json($query->paginate(20));
    }
}
