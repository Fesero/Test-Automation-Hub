<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    /**
     * Получение списка проектов.
     */
    public function index(): mixed
    {
        // Загружаем проекты с количеством тестов и последним тестом
        $projects = Project::query()
            ->withCount('tests')
            ->with('latestTest')
            ->get();

        $projects = $projects->map(fn(Project $project) => $project->last_test_status = $project->latestTest?->status);

        return response()->json($projects);
    }

    /**
     * Создание нового проекта.
     */
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:projects,name',
            'url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        // Генерируем уникальный токен для нового проекта
        $validatedData['api_token'] = Str::random(60);

        $project = Project::create($validatedData);

        // Возвращаем проект с токеном
        return response($project, 201);
    }

    /**
     * Получение информации о конкретном проекте.
     */
    public function show(Project $project): Project
    {
        return $project;
    }

    /**
     * Обновление проекта.
     */
    public function update(Request $request, Project $project): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => "required|string|max:255|unique:projects,name,{$project->id}",
            'url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }

        $project->update($validator->validated());

        return response($project);
    }

    /**
     * Удаление проекта.
     */
    public function destroy(Project $project): Response
    {
        $project->delete();

        return response(['message' => 'Проект удален'], 200);
    }

    /**
     * Генерация нового API токена для проекта.
     */
    public function regenerateToken(Project $project): Response
    {
        $project->update(['api_token' => Str::random(60)]);
        return response(['api_token' => $project->api_token]);
    }
}
