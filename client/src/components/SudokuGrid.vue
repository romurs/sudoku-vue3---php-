<script setup lang="ts">
import { defineComponent, PropType } from "vue";
import CellState from "../models/CellState";

defineProps({
  grid: Object as PropType<CellState[][]>,
  pencilGrid: Object as PropType<(boolean[][][])>,
  sudokuLevel: {
    type: Number,
    default: 3
  }
})
</script>

<template>
  <div class="game-board mt-6" :class="{ 'game-board-4': (sudokuLevel === 4) }">
    <template v-for="(_, block_i) in sudokuLevel">
      <template v-for="(_, block_j) in sudokuLevel">
        <!-- Each block is a 3x3 -->
        <div class="grid-square" :class="{ 'grid-square-4': (sudokuLevel === 4) }">
          <template v-for="(_, i) in sudokuLevel">
            <template v-for="(_, j) in sudokuLevel" :key="(i + '' + j)">
              <slot
                :i="block_i * sudokuLevel + i"
                :j="block_j * sudokuLevel + j"
                :cell="grid[block_i * sudokuLevel + i][block_j * sudokuLevel + j]"
                :pencil="pencilGrid[block_i * sudokuLevel + i][block_j * sudokuLevel + j]"
              ></slot>
            </template>
          </template>
        </div>
      </template>
    </template>
  </div>
</template>

<style lang="postcss" >
.my-grid {
  margin: auto;
  display: grid;
  /* align-content: center;
  justify-items: center; */
  @apply grid-rows-3;
  @apply grid-cols-3;
}

.game-board {
  @apply my-grid;
  @apply border-2 border-slate-500;

  height: calc(100vw + 6px);
  width: 100vw;
  max-width: 462px;
  max-height: 462px;
}

.game-board-4 {
  @apply grid-rows-4;
  @apply grid-cols-4;
  height: calc(50px * 16);
  width: calc(50px * 16);
  /* // 462px; */
}

.grid-square {
  @apply my-grid;
  /* row-gap: 1px;
  column-gap: 1px; */
  @apply border border-slate-500;
}

.grid-square-4 {
  @apply grid-rows-4;
  @apply grid-cols-4;
}
</style>