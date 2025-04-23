<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TestController extends Controller
{
    /**
     * Получение списка тестов.
     * Позволяет фильтровать по project_id.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'nullable|integer|exists:projects,id'
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }

        $query = Test::query()->with('project', 'results');

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        return $query->get();
    }

    /**
     * Создать новый тест.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:crud,sniffer,phpstan,load',
            'project_id' => 'required|integer|exists:projects,id'
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }

        $test = Test::create($validator->validated());

        return response($test->load('project'), 201);
    }

    /**
     * Получить конкретный тест.
     */
    public function show(Test $test)
    {
        return $test->load('project');
    }

    /**
     * Обновить тест.
     * Обновление project_id не разрешено.
     */
    public function update(Request $request, Test $test)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,running,completed,failed',
            'type' => 'required|in:crud,sniffer,phpstan,load',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }

        $test->update($validator->validated());

        return response($test->load('project'));
    }

    /**
     * Удалить тест.
     */
    public function destroy(Test $test)
    {
        $test->delete();

        return response(['message' => 'Тест удален'], 200);
    }
}
