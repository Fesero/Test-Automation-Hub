<template>
  <div class="chart-container">
    <canvas ref="chartRef"></canvas>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
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
import type { TestResult } from 'src/types/test.type'

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
  tests: TestResult[]
}>()

const chartRef = ref<HTMLCanvasElement | null>(null)
let chart: Chart | null = null

const createChart = () => {
  if (!chartRef.value) return

  chart?.destroy()
  
  chart = new Chart(chartRef.value, {
    type: 'bar',
    data: {
      labels: props.tests.map(t => t.name),
      datasets: [
        {
          label: 'Ошибки',
          data: props.tests.map(t => t.result?.totals?.errors || 0),
          backgroundColor: '#ff6b6b'
        },
        {
          label: 'Предупреждения',
          data: props.tests.map(t => t.result?.totals?.warnings || 0),
          backgroundColor: '#4ecdc4'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  })
}

onMounted(createChart)
watch(() => props.tests, createChart, { deep: true })
</script>

<style scoped>
.chart-container {
  height: 400px;
  padding: 16px;
}
</style> 