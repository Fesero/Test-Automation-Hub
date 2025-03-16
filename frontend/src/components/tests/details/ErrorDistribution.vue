<template>
  <div class="row q-col-gutter-md">
    <div class="col-12 col-md-6">
      <q-card flat bordered>
        <q-card-section>
          <div class="text-h6">Распределение ошибок</div>
          <div class="chart-container">
            <canvas ref="chartRef"></canvas>
          </div>
        </q-card-section>
      </q-card>
    </div>
    <div class="col-12 col-md-6">
      <q-card flat bordered>
        <q-card-section>
          <div class="text-h6">Топ проблемных файлов</div>
          <div class="q-mt-md">
            <div v-for="file in topErrorFiles" :key="file.path" class="q-mb-md">
              <div class="row items-center q-gutter-x-sm">
                <div class="col">
                  <div class="text-subtitle2">{{ file.path.split('\\').pop() }}</div>
                  <div class="text-caption text-grey">{{ file.path }}</div>
                </div>
                <div class="text-subtitle1 text-negative">{{ file.errors }}</div>
              </div>
              <q-linear-progress
                :value="file.errors / maxErrors"
                color="negative"
                class="q-mt-xs"
              />
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import {
  Chart,
  ArcElement,
  DoughnutController,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
import type { FileResult } from 'src/types/test.type'

Chart.register(
  ArcElement,
  DoughnutController,
  Title,
  Tooltip,
  Legend
)

const props = defineProps<{
  files: Record<string, FileResult>
}>()

const chartRef = ref<HTMLCanvasElement | null>(null)
let chart: Chart | null = null

const createChart = () => {
  if (!chartRef.value) return

  chart?.destroy()

  const totalFiles = Object.keys(props.files).length
  const filesWithErrors = Object.values(props.files).filter(f => f.errors > 0).length
  const filesWithWarnings = Object.values(props.files).filter(f => f.warnings > 0 && f.errors === 0).length
  const cleanFiles = totalFiles - filesWithErrors - filesWithWarnings

  chart = new Chart(chartRef.value, {
    type: 'doughnut',
    data: {
      labels: ['С ошибками', 'С предупреждениями', 'Чистые'],
      datasets: [{
        data: [filesWithErrors, filesWithWarnings, cleanFiles],
        backgroundColor: ['#ff6b6b', '#ffd93d', '#6bff6b']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom' as const
        }
      }
    }
  })
}

onMounted(createChart)
watch(() => props.files, createChart, { deep: true })

// Топ файлов с ошибками
const topErrorFiles = computed(() => {
  return Object.entries(props.files)
    .map(([path, data]) => ({
      path,
      errors: data.errors || 0
    }))
    .filter(file => file.errors > 0)
    .sort((a, b) => b.errors - a.errors)
    .slice(0, 5)
})

const maxErrors = computed(() => {
  const max = Math.max(...topErrorFiles.value.map(f => f.errors))
  return max || 1
})
</script>

<style scoped>
.chart-container {
  height: 300px;
  position: relative;
}
</style> 