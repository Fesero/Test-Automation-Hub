<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TestController extends Controller
{
    /**
     * Получение всех тестов
     */
    public function index()
    {
        return Test::all();
    }

    /**
     * Создать новый тест
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,running,completed,failed',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }

        $test = Test::create($request->all());

        return response($test, 201);
    }

    /**
     * Получить конкретный тест
     */
    public function show(Test $test)
    {
        return $test->load('results');
    }

    /**
     * Обновить тест
     */
    public function update(Request $request, Test $test)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,running,completed,failed',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }

        $test->update($request->all());

        return response($test);
    }

    /**
     * Удалить тест
     */
    public function destroy(Test $test)
    {
        $test->delete();

        return response(['message' => 'Тест удален'], 200);
    }
}
