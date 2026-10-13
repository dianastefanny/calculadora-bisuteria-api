<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Bitácora de actividad del usuario (creación, edición y eliminación de
 * registros), alimentada automáticamente por el trait LogsHistory.
 */
class HistoryController extends Controller
{
    /**
     * Lista paginada (20 por página), de lo más reciente a lo más antiguo.
     * Admite filtrar por tipo de registro con ?subject_type=Material, etc.
     */
    public function index(Request $request)
    {
        $query = $request->user()->histories()->latest('created_at');

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->string('subject_type'));
        }

        return response()->json($query->paginate(20));
    }
}
