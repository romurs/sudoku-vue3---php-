<?php

require_once '../vendor/autoload.php';

use Romurs\Server\SudokuHundler;

header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['i'], $data['j'], $data['value'], $data['unsolvedSudoku'])) {
        throw new Exception('Invalid input: i, j, value, and unsolvedSudoku are required.');
    }

    $i = (int)$data['i']; 
    $j = (int)$data['j']; 
    $value = (int)$data['value'];
    $unsolvedSudoku = $data['unsolvedSudoku'];

    if ($i < 0 || $i > 8 || $j < 0 || $j > 8) {
        throw new Exception('Invalid indices: i and j must be between 0 and 8.');
    }

    $sudokuGenerator = new SudokuHundler();

    $isCorrect = $sudokuGenerator->checkCell($i, $j, $value, $unsolvedSudoku);

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