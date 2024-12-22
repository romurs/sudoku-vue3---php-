<?php


namespace Romurs\Server;

use Exception;

class SudokuHundler
{
    private const FILE_PATH = 'sudoku.json';

    // Генерация решённой судоку
    public function generateSolvedSudoku(): void
    {
        $solvedSudoku = $this->generateSudoku(); // Метод для генерации решённой судоку

        // Преобразование двумерного массива в одномерный
        $flatSudoku = $this->flattenSudoku($solvedSudoku);

        // Очистка файла и запись массива в JSON
        file_put_contents(self::FILE_PATH, json_encode($flatSudoku));
    }

    // Получение нерешённой судоку
    public function getUnsolvedSudoku(): array
    {
        $flatSudoku = json_decode(file_get_contents(self::FILE_PATH), true);

        if (!$flatSudoku) {
            throw new Exception("Solved Sudoku not found in " . self::FILE_PATH);
        }

        // Преобразование одномерного массива обратно в двумерный
        $solvedSudoku = $this->unflattenSudoku($flatSudoku);

        // Удаление клеток для создания нерешённой судоку
        $unsolvedSudoku = $this->removeCells($solvedSudoku);

        // Преобразование обратно в одномерный массив перед возвратом
        return $this->flattenSudoku($unsolvedSudoku);
    }

    // Проверка значения пользователя
    public function checkCell(int $i, int $j, int $value, array $unsolvedSudoku): bool
    {
        $flatSolvedSudoku = json_decode(file_get_contents(self::FILE_PATH), true);

        if (!$flatSolvedSudoku) {
            throw new Exception("Solved Sudoku not found in " . self::FILE_PATH);
        }

        // Преобразование в двумерный массив для проверки
        $solvedSudoku = $this->unflattenSudoku($flatSolvedSudoku);

        return $solvedSudoku[$i][$j] === $value;
    }

    // Метод для генерации полного судоку (решённого)
    private function generateSudoku(): array
    {
        $sudoku = array_fill(0, 9, array_fill(0, 9, 0));

        // Логика генерации решённой судоку (можно использовать алгоритм Backtracking)
        $this->fillSudoku($sudoku);

        return $sudoku;
    }

    // Метод удаления случайных клеток для создания нерешённой судоку
    private function removeCells(array $sudoku): array
    {
        $cellsToRemove = rand(40, 50); // Убираем 40-50 клеток

        while ($cellsToRemove > 0) {
            $i = rand(0, 8);
            $j = rand(0, 8);

            if ($sudoku[$i][$j] !== 0) {
                $sudoku[$i][$j] = 0;
                $cellsToRemove--;
            }
        }

        return $sudoku;
    }

    // Заполнение судоку (рекурсивный метод Backtracking)
    private function fillSudoku(array &$sudoku): bool
    {
        for ($i = 0; $i < 9; $i++) {
            for ($j = 0; $j < 9; $j++) {
                if ($sudoku[$i][$j] === 0) {
                    $numbers = range(1, 9);
                    shuffle($numbers);

                    foreach ($numbers as $number) {
                        if ($this->isValid($sudoku, $i, $j, $number)) {
                            $sudoku[$i][$j] = $number;

                            if ($this->fillSudoku($sudoku)) {
                                return true;
                            }

                            $sudoku[$i][$j] = 0;
                        }
                    }

                    return false;
                }
            }
        }

        return true;
    }

    // Проверка, можно ли вставить число в клетку
    private function isValid(array $sudoku, int $row, int $col, int $num): bool
    {
        for ($x = 0; $x < 9; $x++) {
            if ($sudoku[$row][$x] === $num || $sudoku[$x][$col] === $num) {
                return false;
            }
        }

        $startRow = floor($row / 3) * 3;
        $startCol = floor($col / 3) * 3;

        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                if ($sudoku[$startRow + $i][$startCol + $j] === $num) {
                    return false;
                }
            }
        }

        return true;
    }

    // Преобразование двумерного массива в одномерный
    private function flattenSudoku(array $sudoku): array
    {
        $flatSudoku = [];
        foreach ($sudoku as $row) {
            $flatSudoku = array_merge($flatSudoku, $row);
        }
        return $flatSudoku;
    }

    // Преобразование одномерного массива обратно в двумерный
    private function unflattenSudoku(array $flatSudoku): array
    {
        $sudoku = [];
        for ($i = 0; $i < 9; $i++) {
            $sudoku[] = array_slice($flatSudoku, $i * 9, 9);
        }
        return $sudoku;
    }
}