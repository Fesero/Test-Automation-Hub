<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    /**
     * Получение списка проектов.
     */
    public function index()
    {
        return Project::all();
    }

    /**
     * Создание нового проекта.
     */
    public function store(Request $request)
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
    public function show(Project $project)
    {
        return $project;
    }

    /**
     * Обновление проекта.
     */
    public function update(Request $request, Project $project)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:projects,name,' . $project->id,
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
    public function destroy(Project $project)
    {
        $project->delete();

        return response(['message' => 'Проект удален'], 200);
    }

    /**
     * Генерация нового API токена для проекта.
     */
    public function regenerateToken(Project $project)
    {
        $project->update(['api_token' => Str::random(60)]);
        return response(['api_token' => $project->api_token]);
    }
}
