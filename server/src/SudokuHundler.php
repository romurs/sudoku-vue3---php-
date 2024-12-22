<?php


namespace Romurs\Server;

use Exception;

class SudokuHundler
{
    private const FILE_PATH = 'sudoku.json';

    public function generateSolvedSudoku(): void
    {
        $solvedSudoku = $this->generateSudoku(); 

        $flatSudoku = $this->flattenSudoku($solvedSudoku);

        file_put_contents(self::FILE_PATH, json_encode($flatSudoku));
    }
    
    public function getUnsolvedSudoku(): array
    {
        $flatSudoku = json_decode(file_get_contents(self::FILE_PATH), true);

        if (!$flatSudoku) {
            throw new Exception("Solved Sudoku not found in " . self::FILE_PATH);
        }
        $solvedSudoku = $this->unflattenSudoku($flatSudoku);
        $unsolvedSudoku = $this->removeCells($solvedSudoku);
        return $this->flattenSudoku($unsolvedSudoku);
    }
    public function checkCell(int $i, int $j, int $value, array $unsolvedSudoku): bool
    {
        $flatSolvedSudoku = json_decode(file_get_contents(self::FILE_PATH), true);

        if (!$flatSolvedSudoku) {
            throw new Exception("Solved Sudoku not found in " . self::FILE_PATH);
        }
        $solvedSudoku = $this->unflattenSudoku($flatSolvedSudoku);

        return $solvedSudoku[$i][$j] === $value;
    }

    private function generateSudoku(): array
    {
        $sudoku = array_fill(0, 9, array_fill(0, 9, 0));
        $this->fillSudoku($sudoku);

        return $sudoku;
    }

    private function removeCells(array $sudoku): array
    {
        $cellsToRemove = rand(40, 50);

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

    private function flattenSudoku(array $sudoku): array
    {
        $flatSudoku = [];
        foreach ($sudoku as $row) {
            $flatSudoku = array_merge($flatSudoku, $row);
        }
        return $flatSudoku;
    }

    private function unflattenSudoku(array $flatSudoku): array
    {
        $sudoku = [];
        for ($i = 0; $i < 9; $i++) {
            $sudoku[] = array_slice($flatSudoku, $i * 9, 9);
        }
        return $sudoku;
    }
}