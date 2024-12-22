<?php

require_once '../vendor/autoload.php';

use Romurs\Server\SudokuHundler;

header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

try {
    // Получение данных с фронта
    $data = json_decode(file_get_contents('php://input'), true);

    // Проверяем, что переданы все необходимые параметры
    if (!isset($data['i'], $data['j'], $data['value'], $data['unsolvedSudoku'])) {
        throw new Exception('Invalid input: i, j, value, and unsolvedSudoku are required.');
    }

    $i = (int)$data['i']; // Индекс строки
    $j = (int)$data['j']; // Индекс столбца
    $value = (int)$data['value']; // Значение, введённое пользователем
    $unsolvedSudoku = $data['unsolvedSudoku']; // Нерешённая судоку (одномерный массив)

    // Проверка индексов
    if ($i < 0 || $i > 8 || $j < 0 || $j > 8) {
        throw new Exception('Invalid indices: i and j must be between 0 and 8.');
    }

    // Инициализация генератора судоку
    $sudokuGenerator = new SudokuHundler();

    // Проверка значения
    $isCorrect = $sudokuGenerator->checkCell($i, $j, $value, $unsolvedSudoku);

    // Возврат результата
    echo json_encode([
        'status' => 'success',
        'isCorrect' => $isCorrect
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}