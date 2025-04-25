<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePluginResultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For now, allow any request. Add authorization logic if needed.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'projectName' => 'required|string|max:255',
            'totals' => 'required|array', // Validate totals structure
            'totals.errors' => 'required|integer|min:0',
            'totals.warnings' => 'required|integer|min:0',
            'totals.fixable' => 'required|integer|min:0',
            'files' => 'required|array', // Files is an associative array (object)
            // Validate the structure within each file object using wildcard '*'
            'files.*' => 'required|array', // Each file entry must be an array/object
            'files.*.errors' => 'required|integer|min:0',
            'files.*.warnings' => 'required|integer|min:0',
            'files.*.messages' => 'required|array', // Messages array for each file
            'files.*.messages.*.message' => 'required|string',
            'files.*.messages.*.source' => 'required|string',
            'files.*.messages.*.severity' => 'required|integer',
            'files.*.messages.*.type' => ['required', 'string', Rule::in(['ERROR', 'WARNING'])], // Use Rule::in for specific values
            'files.*.messages.*.line' => 'required|integer|min:1',
            'files.*.messages.*.column' => 'required|integer|min:1',
            'files.*.messages.*.fixable' => 'required|boolean',
            // Add other fields sent by the plugin if any
            'analyzer_type' => 'sometimes|string|in:sniffer,phpstan', // Add if type comes in payload
            'commit_hash' => 'sometimes|string|max:40', // Example: if commit info is sent
        ];
    }

    /**
     * Получить пользовательские сообщения для ошибок валидатора.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'projectName.required' => 'Необходимо указать имя проекта.',
            'totals.required' => 'Отсутствует информация об итогах (totals).',
            'totals.*.required' => 'Поле :attribute в totals обязательно.',
            'totals.*.integer' => 'Поле :attribute в totals должно быть целым числом.',
            'files.required' => 'Отсутствуют результаты анализа файлов (files).',
            'files.array' => 'Результаты анализа файлов (files) должны быть массивом/объектом.',
            'files.*.required' => 'Запись для файла :attribute обязательна.',
            'files.*.array' => 'Запись для файла :attribute должна быть массивом/объектом.',
            'files.*.errors.required' => 'Количество ошибок (errors) обязательно для файла :attribute.',
            'files.*.warnings.required' => 'Количество предупреждений (warnings) обязательно для файла :attribute.',
            'files.*.messages.required' => 'Массив сообщений (messages) обязателен для файла :attribute.',
            'files.*.messages.array' => 'Сообщения (messages) для файла :attribute должны быть массивом.',
            'files.*.messages.*.message.required' => 'Текст сообщения обязателен.',
            'files.*.messages.*.source.required' => 'Источник сообщения (source) обязателен.',
            'files.*.messages.*.severity.required' => 'Уровень серьезности (severity) обязателен.',
            'files.*.messages.*.type.required' => 'Тип сообщения (type) обязателен.',
            'files.*.messages.*.type.in' => 'Недопустимый тип сообщения (type). Допустимые значения: ERROR, WARNING.',
            'files.*.messages.*.line.required' => 'Номер строки (line) обязателен.',
            'files.*.messages.*.line.integer' => 'Номер строки (line) должен быть целым числом.',
            'files.*.messages.*.column.required' => 'Номер столбца (column) обязателен.',
            'files.*.messages.*.column.integer' => 'Номер столбца (column) должен быть целым числом.',
            'files.*.messages.*.fixable.required' => 'Признак исправимости (fixable) обязателен.',
            'files.*.messages.*.fixable.boolean' => 'Признак исправимости (fixable) должен быть булевым значением.',
        ];
    }
} 