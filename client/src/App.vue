<script lang="ts">
import { ref, computed, defineComponent } from "vue";
import type { Ref } from "vue";
import CellState from "./models/CellState";
import SudokuCell from "./components/SudokuCell.vue";
import SudokuGrid from "./components/SudokuGrid.vue";


export default defineComponent({
  components: {
    SudokuCell,
    SudokuGrid,
  },

  setup() {
    const puzzle = ref(null)
    const sudokuLevel = ref(3); // n
    const gridSize = computed(() => sudokuLevel.value ** 2); // n*n

    const grid: Ref<CellState[][]> = ref(
      Array(gridSize.value)
        .fill(null)
        .map((_, i) =>
          Array(gridSize.value)
            .fill(null)
            .map((_, j) => new CellState())
        )
    );

    const pencilGrid: Ref<boolean[][][]> = ref(
      Array(gridSize.value).fill(
        Array(gridSize.value).fill(Array(9).fill(false))
      )
    );

    const selectedIndexes = ref([-1, -1]);

    const pencilMode = ref(false);

    const isSelecting = computed(
      () =>
        selectedIndexes.value[0] >= 0 &&
        selectedIndexes.value[0] < gridSize.value &&
        selectedIndexes.value[1] >= 0 &&
        selectedIndexes.value[1] < gridSize.value
    );

    const selectedValue = computed(() =>
      isSelecting.value
        ? grid.value[selectedIndexes.value[0]][selectedIndexes.value[1]]
            .cellValue
        : null
    );

    return {
      sudokuLevel,
      pencilGrid,
      grid,
      gridSize,
      selected: selectedIndexes,
      isSelecting,
      pencilMode,
      selectedValue,
      puzzle,
    };
  },
  mounted() {
    this.registerKeyboardEvents();
    this.newGame();
  },
  methods: {
    registerKeyboardEvents() {
      const ALLOWED_KEYS: { [key: string]: number | null } = {
        Delete: null,
        Digit1: 1,
        Digit2: 2,
        Digit3: 3,
        Digit4: 4,
        Digit5: 5,
        Digit6: 6,
        Digit7: 7,
        Digit8: 8,
        Digit9: 9,
        KeyQ: 10,
        KeyB: 11,
        KeyC: 12,
        KeyD: 13,
        KeyE: 14,
        KeyF: 15,
      };

      window.addEventListener("keydown", (ev) => {
        if (ev.code in ALLOWED_KEYS) {
          this.assignCell(ALLOWED_KEYS[ev.code]);
          return;
        }

        if (!this.isSelecting) return;

        if (ev.code == "Space") {
          this.togglePencilMode();
          return;
        }

        if (ev.code == "AltLeft") {
          this.quickPencilCell();
          return;
        }

        const i = this.selected[0];
        const j = this.selected[1];
        if (ev.code == "ArrowRight") {
          this.selected = [i, j >= this.gridSize - 1 ? j : j + 1];
          return;
        }

        if (ev.code == "ArrowLeft") {
          this.selected = [i, j === 0 ? j : j - 1];
          return;
        }

        if (ev.code == "ArrowUp") {
          this.selected = [i === 0 ? i : i - 1, j];
          return;
        }

        if (ev.code == "ArrowDown") {
          this.selected = [i >= this.gridSize - 1 ? i : i + 1, j];
          return;
        }
      });
    },

    async generateSudoku(): Promise<number[]> {
      const response = await fetch('http://localhost:8000/src/getPuzzle.php');
      
      if (!response.ok) {
        throw new Error(`Failed to fetch puzzle: ${response.statusText}`);
      }

      const data = await response.json();

      if (
        !data ||
        !Array.isArray(data.puzzle) ||
        !data.puzzle.every((item: any) => typeof item === 'number')
      ) {
        throw new Error('Invalid data format: Expected an object with a "puzzle" array of numbers.');
      }

      this.puzzle = data.puzzle
      return data.puzzle;
    },
    async newGame(): Promise<void> {
      this.clearAll();
      const puzzle = await this.generateSudoku();

      this.grid = Array(this.gridSize)
        .fill(null)
        .map((_, i) =>
          Array(this.gridSize)
            .fill(null)
            .map((_, j) => new CellState(puzzle[i * this.gridSize + j] || null))
        );
    },
    togglePencilMode(): void {
      this.pencilMode = !this.pencilMode;
    },

    selectCell(i: number, j: number): void {
      this.selected = [i, j];
    },

    clearSelection(): void {
      this.selected = [-1, -1];
    },

    async judgeCell(i: number, j: number): Promise<boolean> {

      const value = this.grid[i][j].cellValue;
      let unSolve = this.puzzle

      if (value == null) {
        this.setGridWrongValue(i, j, false);
        return true;
      }

      try {
        const response = await fetch('http://localhost:8000/src/validateSudokuCell.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            i: i,
            j: j,
            value: value,
            unsolvedSudoku: unSolve,
          }),
        });

        if (!response.ok) {
          throw new Error('Failed to validate cell');
        }

        const data = await response.json();

        if (data.isCorrect === undefined) {
          throw new Error('Invalid response from server');1
        }

        this.setGridWrongValue(i, j, !data.isCorrect);
        

        return data.isCorrect;
      } catch (error) {
        console.error('Error validating cell:', error);
        return false;
      }
    },

    async judgeBoard(): Promise<void> {
      let hasWon = true;
      for (let i = 0; i < this.gridSize; i++) {
        for (let j = 0; j < this.gridSize; j++) {
          if (this.grid[i][j].cellValue === null) {
            console.log("cell ", i, j, " is empty");
            hasWon = false;
          }

          const isCellCorrect = await this.judgeCell(i, j);

          hasWon = hasWon && isCellCorrect;
        }
      }

      if (hasWon) alert("Yay you won !\n... Yes that's all I can say now.");
    },

    setGridValue(i: number, j: number, cellState: CellState): void {
      let modifiedRow = this.grid[i].slice(0);

      modifiedRow[j] = cellState;

      this.grid[i] = modifiedRow;
    },

    togglePencilGridValue(i: number, j: number, n: number): void {
      const row = this.pencilGrid[i].slice(0);

      const arr = row[j].slice(0);

      arr[n - 1] = !arr[n - 1];

      row[j] = arr;
      this.pencilGrid[i] = row;
    },

    clearPencilGridCell(i: number, j: number): void {
      const row = this.pencilGrid[i].slice(0);

      row[j] = new Array(this.gridSize).fill(false);
      this.pencilGrid[i] = row;
    },

    setGridWrongValue(i: number, j: number, wrong = true) {
      this.setGridValue(i, j, {
        ...this.grid[i][j],
        isWrong: wrong,
      });
    },

    quickPencilCell() {
      this.pencilMode = true;
      for (let i = 0; i < this.gridSize; i++) {
        this.assignCell(i + 1);
      }
    },

    updatePencilGridValues(i: number, j: number, n: number): void {
      for (let k = 0; k < this.gridSize; k++) {
        if (this.pencilGrid[i][k][n - 1]) {
          this.togglePencilGridValue(i, k, n);
        }

        if (this.pencilGrid[k][j][n - 1]) {
          this.togglePencilGridValue(k, j, n);
        }
      }
      const startI = i - (i % this.sudokuLevel);
      const startJ = j - (j % this.sudokuLevel);

      const endI = startI + this.sudokuLevel;
      const endJ = startJ + this.sudokuLevel;

      for (let k = startI; k < endI; k++) {
        for (let m = startJ; m < endJ; m++) {
          if (this.pencilGrid[k][m][n - 1]) {
            this.togglePencilGridValue(k, m, n);
          }
        }
      }
    },
    async assignCell(n: number | null): Promise<void> {
      const i = this.selected[0];
      const j = this.selected[1];

      if (i < 0 || i >= this.gridSize || j < 0 || j >= this.gridSize) return;

      const cell = this.grid[i][j];

      if (cell.isPrefilled) return;
      if (cell.cellValue === n) return;
      if (n && n > this.gridSize) return;

      if (n && this.pencilMode) {
        this.togglePencilGridValue(i, j, n);
      } else {
        this.setGridValue(i, j, {
          ...cell,
          cellValue: n,
        });

        this.clearPencilGridCell(i, j);

        if (n != null) {
          this.updatePencilGridValues(i, j, n);
        }

        const isValid = await this.judgeCell(i, j);
      }
    },

    clearAll(): void {
      let board = this.grid;
      let pencilGrid = this.pencilGrid;

      for (let i = 0; i < this.gridSize; i++) {
        for (let j = 0; j < this.gridSize; j++) {
          if (!board[i][j].isPrefilled) {
            board[i][j] = new CellState();
            pencilGrid[i][j] = new Array(this.gridSize).fill(false);
          }
        }
      }

      this.grid = board;
      this.pencilGrid = pencilGrid;
    },
  },
});
</script>

<template>
  <div class="container m-auto">
    <h1 class="text-2xl font-bold underline mb-3">
      Sudoku {{ gridSize }} x {{ gridSize }}
    </h1>
    <div class="flex flex-col-reverse md:flex-row h-full">
      <!-- Sidebar -->
      <div class="md:basis-1/4 md:border-r">
        <div class="flex flex-col px-10">
          <button
            class="mt-5"
            :class="{ 'button-dark': pencilMode, button: !pencilMode }"
            @click="togglePencilMode"
          >
            Pencil mode {{ pencilMode ? "On" : "Off" }}
          </button>
          <button class="button-dark mt-5" @click="newGame">New Game</button>

          <button class="button-dark mt-5" @click="clearAll">Clear all</button>
          <div class="w-full text-left mt-2 hidden md:block">
            <kbd>Space</kbd> Toggle pencil mode On/Off
            <br />
            <kbd>Del</kbd> &nbsp;&nbsp; Delete Cell value
            <br />
            <kbd>Alt</kbd> &nbsp;&nbsp; Quick fill cell with pencil
            <br />
          </div>
        </div>
      </div>
      <!-- Sudoku Grid -->
      <div class="md:basis-3/4 h-full">
        <div class="container mx-auto mt-10 h-full">
          <div class="flex flex-wrap flex-row h-full">
            <div class="md:basis-1/6"></div>
            <div class="md:basis-3/6">
              <sudoku-grid
                :grid="grid"
                :pencil-grid="pencilGrid"
                :sudoku-level="sudokuLevel"
              >
                <template v-slot.default="{ i, j, cell, pencil }">
                  <sudoku-cell
                    :cell="grid[i][j]"
                    :pencil="pencil"
                    :highlighted-value="selectedValue"
                    :is-selected="i == selected[0] && j == selected[1]"
                    @onCellSelect="selectCell(i, j)"
                  />
                </template>
              </sudoku-grid>
            </div>
            <div class="md:basis-1/12"></div>
            <div
              class="basis-full md:basis-1/12 flex flex-row md:flex-col my-5 md:my-0"
            >
              <!-- Row -->
              <button
                @click="assignCell(i)"
                v-for="i in 9"
                class="number-cell flex-grow"
              >
                {{ i }}
              </button>
              <button @click="assignCell(null)" class="number-cell flex-grow">
                X
              </button>
              <!-- <div class="md:invisible w-full grid grid-rows-1 grid-cols-10">
              </div>-->
              <!-- Col -->
              <!-- <div class="invisible md:visible grid grid-flow-row grid-cols-1 bg-red-300 h-full">
              </div>-->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="postcss">
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  height: 100vh;
}

.number-cell {
  cursor: pointer;
  @apply text-3xl;
  @apply border border-slate-600;
}

.button {
  @apply h-10 px-6 font-semibold rounded-md bg-white text-black;
  @apply border border-slate-700;
}

.button-dark {
  @apply button;
  @apply bg-slate-700 text-white;
}

kbd {
  background-color: #eee;
  border-radius: 3px;
  border: 1px solid #b4b4b4;
  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.2),
    0 2px 0 0 rgba(255, 255, 255, 0.7) inset;
  color: #333;
  display: inline-block;
  font-size: 0.85em;
  font-weight: 700;
  line-height: 1;
  padding: 2px 4px;
  white-space: nowrap;
}
</style>
