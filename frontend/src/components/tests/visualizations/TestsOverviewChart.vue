<template>
  <div class="chart-container">
    <canvas ref="chartRef"></canvas>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import {
  Chart,
  CategoryScale,
  LinearScale,
  BarElement,
  BarController,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
import type { Test } from 'src/types/test.type'

Chart.register(
  CategoryScale,
  LinearScale,
  BarElement,
  BarController,
  Title,
  Tooltip,
  Legend
)

const props = defineProps<{
  tests: Test[]
}>()

const chartRef = ref<HTMLCanvasElement | null>(null)
let chart: Chart | null = null

const chartData = computed(() => {
  const labels = props.tests.map(t => t.name)
  const errorsData: number[] = []
  const warningsData: number[] = []

  props.tests.forEach(test => {
    let errors = 0
    let warnings = 0
    test.results?.forEach(result => {
      errors += result.parsedMetrics?.errors ?? 0
      warnings += result.parsedMetrics?.warnings ?? 0
    })
    errorsData.push(errors)
    warningsData.push(warnings)
  })

  return {
    labels,
    datasets: [
      {
        label: 'Ошибки',
        data: errorsData,
        backgroundColor: '#ff6b6b'
      },
      {
        label: 'Предупреждения',
        data: warningsData,
        backgroundColor: '#ffd93d'
      }
    ]
  }
})

const createChart = () => {
  if (!chartRef.value) return

  chart?.destroy()
  
  chart = new Chart(chartRef.value, {
    type: 'bar',
    data: chartData.value,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom' as const
        }
      },
      scales: {
        y: {
          beginAtZero: true
        },
        x: {
          // Optional: Add category scale if needed explicitly
        }
      }
    }
  })
}

onMounted(createChart)
watch(chartData, createChart, { deep: true })
</script>

<style scoped>
.chart-container {
  height: 400px;
  padding: 16px;
}
</style> 