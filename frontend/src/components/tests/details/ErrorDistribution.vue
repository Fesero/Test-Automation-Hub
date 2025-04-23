<template>
  <div class="row q-col-gutter-md">
    <div class="col-12 col-md-6">
      <q-card flat bordered>
        <q-card-section>
          <div class="text-h6">Распределение статуса файлов</div>
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
          <div class="q-mt-md" v-if="topErrorFiles.length > 0">
            <div v-for="(file, index) in topErrorFiles" :key="file.path ?? index" class="q-mb-md">
              <div class="row items-center q-gutter-x-sm">
                <div class="col">
                  <div class="text-subtitle2">{{ file.path?.split('\\').pop() }}</div>
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
          <div v-else class="text-center text-grey q-pa-md">
              Нет файлов с ошибками.
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
import type { TestResult } from 'src/types/test.type'

Chart.register(
  ArcElement,
  DoughnutController,
  Title,
  Tooltip,
  Legend
)

const props = defineProps<{
  results: TestResult[]
}>()

const chartRef = ref<HTMLCanvasElement | null>(null)
let chart: Chart | null = null

const createChart = () => {
  if (!chartRef.value) return

  chart?.destroy()
  const totalFiles = props.results.length
  const filesWithErrors = props.results.filter((r): r is TestResult => true).filter(r => (r.parsedMetrics?.errors ?? 0) > 0).length
  const filesWithWarnings = props.results.filter((r): r is TestResult => true).filter(r => ((r.parsedMetrics?.warnings ?? 0) > 0) && ((r.parsedMetrics?.errors ?? 0) === 0)).length
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
watch(() => props.results, createChart, { deep: true })

// Топ файлов с ошибками
const topErrorFiles = computed(() => {
  return props.results
    .map((result) => ({
      path: result.parsedMetrics?.file_path,
      errors: result.parsedMetrics?.errors ?? 0
    }))
    .filter((file) => file.path && file.errors > 0)
    .sort((a, b) => b.errors - a.errors)
    .slice(0, 5)
})
const maxErrors = computed(() => {
  if (topErrorFiles.value.length === 0) return 1
  const max = Math.max(...topErrorFiles.value.map((f: { errors: number }) => f.errors))
  return max || 1
})
</script>

<style scoped>
.chart-container {
  height: 300px;
  position: relative;
}
</style> 