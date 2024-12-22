<?php

require_once '../vendor/autoload.php';

use Romurs\Server\SudokuHundler;

header('Access-Control-Allow-Origin: http://localhost:3000');
header('Content-Type: application/json');

try {
  $sudokuGenerator = new SudokuHundler();

  $sudokuGenerator->generateSolvedSudoku();

  $unsolvedSudoku = $sudokuGenerator->getUnsolvedSudoku();

  echo json_encode([
      'status' => 'success',
      'puzzle' => $unsolvedSudoku
  ]);
} catch (Exception $e) {
  echo json_encode([
      'status' => 'error',
      'message' => $e->getMessage()
  ]);
}
