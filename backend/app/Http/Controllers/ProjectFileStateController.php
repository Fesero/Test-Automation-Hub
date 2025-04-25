<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProjectFileState;
use Illuminate\Http\JsonResponse;

class ProjectFileStateController extends Controller
{
    /**
     * Get file states for a specific project
     */
    public function index(int $projectId): JsonResponse
    {
        $fileStates = ProjectFileState::where('project_id', $projectId)
            ->with(['lastTest'])
            ->orderBy('file_path')
            ->get();

        return response()->json($fileStates);
    }

    /**
     * Get file state by ID
     */
    public function show(int $id): JsonResponse
    {
        $fileState = ProjectFileState::with(['lastTest'])->findOrFail($id);
        return response()->json($fileState);
    }
} 