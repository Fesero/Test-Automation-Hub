<template>
  <div class="row q-col-gutter-md">
    <!-- Time-based trends -->
    <div class="col-12">
      <q-card flat bordered>
        <q-card-section>
          <div class="text-h6">Тренды по времени</div>
          <div class="chart-container">
            <canvas ref="trendsChartRef"></canvas>
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Project statistics -->
    <div class="col-12 col-md-6">
      <q-card flat bordered>
        <q-card-section>
          <div class="text-h6">Статистика по проектам</div>
          <div class="chart-container">
            <canvas ref="projectsChartRef"></canvas>
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Error types distribution -->
    <div class="col-12 col-md-6">
      <q-card flat bordered>
        <q-card-section>
          <div class="text-h6">Распределение типов ошибок</div>
          <div class="chart-container">
            <canvas ref="errorTypesChartRef"></canvas>
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Top issues -->
    <div class="col-12">
      <q-card flat bordered>
        <q-card-section>
          <div class="text-h6">Топ проблемных мест</div>
          <q-table
            :rows="topIssues"
            :columns="issueColumns"
            row-key="id"
            flat
            bordered
            :pagination="{ rowsPerPage: 5 }"
            class="modern-table"
          >
            <template v-slot:body-cell-severity="props">
              <q-td :props="props">
                <q-badge :color="props.value === 'error' ? 'negative' : 'warning'">
                  {{ props.value }}
                </q-badge>
              </q-td>
            </template>
          </q-table>
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, onUnmounted, computed } from 'vue'
import {
  Chart,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  LineController,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  DoughnutController
} from 'chart.js'
import type { Test /*, TestResult, TestResultMessage, TestResultMetrics */ } from 'src/types/test.type'

defineOptions({
  name: 'TestAnalytics'
})

Chart.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  LineController,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  DoughnutController
)

interface Props {
  tests: Test[]
}

const props = defineProps<Props>()

const trendsChartRef = ref<HTMLCanvasElement | null>(null)
const projectsChartRef = ref<HTMLCanvasElement | null>(null)
const errorTypesChartRef = ref<HTMLCanvasElement | null>(null)

let trendsChart: Chart | null = null
let projectsChart: Chart | null = null
let errorTypesChart: Chart | null = null

// Table columns for top issues
const issueColumns = [
  { name: 'type', label: 'Уровень', field: 'type', align: 'left' as const },
  { name: 'message', label: 'Сообщение', field: 'message', align: 'left' as const },
  { name: 'count', label: 'Количество', field: 'count', align: 'right' as const },
  { name: 'files', label: 'Файлы', field: 'files', align: 'left' as const },
  { name: 'source', label: 'Источник', field: 'source', align: 'left' as const },
]

// Process data for charts
const timeData = computed(() => {
  const data = props.tests.reduce((acc, test) => {
    const date = new Date(test.created_at).toLocaleDateString()
    if (!acc[date]) {
      acc[date] = { errors: 0, warnings: 0 }
    }
    test.results?.forEach((result) => {
      acc[date]!.errors += result.parsedMetrics?.errors ?? 0
      acc[date]!.warnings += result.parsedMetrics?.warnings ?? 0
    })
    return acc
  }, {} as Record<string, { errors: number, warnings: number }>)

  return {
    labels: Object.keys(data),
    datasets: [
      {
        label: 'Ошибки',
        data: Object.values(data).map(d => d.errors),
        borderColor: '#D50000',
        tension: 0.4
      },
      {
        label: 'Предупреждения',
        data: Object.values(data).map(d => d.warnings),
        borderColor: '#FFD600',
        tension: 0.4
      }
    ]
  }
})

const projectData = computed(() => {
  const data = props.tests.reduce((acc, test) => {
    const project = test.project?.name || 'Без проекта'
    if (!acc[project]) {
      acc[project] = { errors: 0, warnings: 0 }
    }
    test.results?.forEach((result) => {
      acc[project]!.errors += result.parsedMetrics?.errors ?? 0
      acc[project]!.warnings += result.parsedMetrics?.warnings ?? 0
    })
    return acc
  }, {} as Record<string, { errors: number, warnings: number }>)

  return {
    labels: Object.keys(data),
    datasets: [{
      data: Object.values(data).map(d => d.errors + d.warnings),
      backgroundColor: ['#D50000', '#FFD600', '#00C853', '#2196F3', '#9C27B0']
    }]
  }
})

const errorTypesData = computed(() => {
  const data = props.tests.reduce((acc, test) => {
    test.results?.forEach((result) => {
      result.parsedOutput?.forEach((msg) => {
        const type = msg.source || 'Неизвестный источник'
        acc[type] = (acc[type] || 0) + 1
      })
    })
    return acc
  }, {} as Record<string, number>)

  return {
    labels: Object.keys(data),
    datasets: [{
      data: Object.values(data),
      backgroundColor: ['#D50000', '#FFD600', '#00C853', '#2196F3', '#9C27B0']
    }]
  }
})

const topIssues = computed(() => {
  const issues = props.tests.reduce((acc, test) => {
    test.results?.forEach((result) => {
      result.parsedOutput?.forEach((msg) => {
        const key = `${msg.type}-${msg.message}`
        if (!acc[key]) {
          acc[key] = {
            id: key,
            type: msg.type,
            message: msg.message,
            source: msg.source || 'N/A',
            count: 0,
            files: new Set<string>()
          }
        }
        acc[key].count++
        if (result.parsedMetrics?.file_path) {
          acc[key].files.add(result.parsedMetrics.file_path)
        }
      })
    })
    return acc
  }, {} as Record<string, { id: string, type: string, message: string, source: string, count: number, files: Set<string> }>)

  return Object.values(issues)
    .map(issue => ({
      ...issue,
      files: Array.from(issue.files).slice(0, 3).join(', ') + (issue.files.size > 3 ? '...' : ''),
    }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 10)
})

const createCharts = () => {
  // Trends chart
  if (trendsChartRef.value) {
    trendsChart?.destroy()
    trendsChart = new Chart(trendsChartRef.value, {
      type: 'line',
      data: timeData.value,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top' as const
          }
        }
      }
    })
  }

  // Projects chart
  if (projectsChartRef.value) {
    projectsChart?.destroy()
    projectsChart = new Chart(projectsChartRef.value, {
      type: 'doughnut',
      data: projectData.value,
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

  // Error types chart
  if (errorTypesChartRef.value) {
    errorTypesChart?.destroy()
    errorTypesChart = new Chart(errorTypesChartRef.value, {
      type: 'doughnut',
      data: errorTypesData.value,
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
}

watch(() => props.tests, createCharts, { deep: true })

onMounted(createCharts)
onUnmounted(() => {
  trendsChart?.destroy()
  projectsChart?.destroy()
  errorTypesChart?.destroy()
})
</script>

<style scoped>
.chart-container {
  height: 300px;
  position: relative;
}
</style> 