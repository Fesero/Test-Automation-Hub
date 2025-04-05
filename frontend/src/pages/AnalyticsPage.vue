<template>
  <q-page padding class="page-padding">
    <!-- Project Selector -->
    <div class="row items-center justify-between q-mb-lg">
        <div class="text-h4 page-title">Аналитика</div>
        <q-select
            v-model="selectedProjectId"
            :options="projectOptions"
            label="Выберите проект для детальной аналитики"
            emit-value
            map-options
            outlined
            dense
            clearable
            options-dense
            class="project-selector bg-surface"
            style="min-width: 300px; max-width: 400px;"
            dark
            popup-content-class="bg-surface-alt"
        />
    </div>

    <div class="row q-col-gutter-lg">
      <!-- Performance Metrics -->
      <div class="col-12 col-md-6">
        <q-card flat bordered class="chart-card">
          <q-card-section>
            <div class="text-h6 card-title">Метрики производительности</div>
            <div v-if="loading" class="flex flex-center q-my-md"><q-spinner-dots color="primary" size="30px" /></div>
            <div v-else-if="!selectedProjectId" class="text-grey text-center q-my-md">Выберите проект для отображения метрик.</div>
            <div v-else-if="!tests.length" class="text-grey text-center q-my-md">Нет данных для выбранного проекта.</div>
            <div v-else class="chart-container">
              <canvas ref="performanceChartRef"></canvas>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Test Coverage -->
      <div class="col-12 col-md-6">
        <q-card flat bordered class="chart-card">
          <q-card-section>
            <div class="text-h6 card-title">Распределение статусов тестов</div>
            <div v-if="loading" class="flex flex-center q-my-md"><q-spinner-dots color="primary" size="30px" /></div>
            <div v-else-if="!selectedProjectId" class="text-grey text-center q-my-md">Выберите проект для отображения статусов.</div>
            <div v-else-if="!tests.length" class="text-grey text-center q-my-md">Нет данных для выбранного проекта.</div>
            <div v-else class="chart-container">
              <canvas ref="coverageChartRef"></canvas>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Error Trends -->
      <div class="col-12">
        <q-card flat bordered class="chart-card">
          <q-card-section>
            <div class="text-h6 card-title">Тренды ошибок</div>
            <div v-if="loading" class="flex flex-center q-my-md"><q-spinner-dots color="primary" size="30px" /></div>
            <div v-else-if="!selectedProjectId" class="text-grey text-center q-my-md">Выберите проект для отображения трендов.</div>
            <div v-else-if="!tests.length" class="text-grey text-center q-my-md">Нет данных для выбранного проекта.</div>
            <div v-else class="chart-container">
              <canvas ref="errorTrendsChartRef"></canvas>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Project Health -->
      <div class="col-12 col-md-8">
        <q-card flat bordered class="table-card">
          <q-card-section>
            <div class="text-h6 card-title">Здоровье проектов</div>
            <div v-if="loadingProjects" class="flex flex-center q-my-md"><q-spinner-dots color="primary" size="30px" /></div>
            <div v-else-if="errorLoadingProjects" class="text-negative text-center q-my-md">{{ errorLoadingProjects }}</div>
            <q-table
              :rows="projectHealthData"
              :columns="healthColumns"
              row-key="name"
              flat
              bordered
              :pagination="{ rowsPerPage: 5 }"
              class="modern-table"
              dark
            >
              <template v-slot:body-cell-health="props">
                <q-td :props="props">
                  <q-linear-progress
                    :value="props.value / 100"
                    :color="getHealthColor(props.value)"
                    class="q-mt-sm"
                    rounded
                    size="8px"
                  />
                  <div class="text-caption text-grey text-center q-mt-xs">{{ props.row.last_test_status || 'Нет данных' }}</div>
                </q-td>
              </template>
            </q-table>
          </q-card-section>
        </q-card>
      </div>

      <!-- Overall Stats -->
      <div class="col-12 col-md-4">
        <q-card flat bordered class="stats-card-container">
          <q-card-section>
            <div class="text-h6 card-title">Общая статистика</div>
            <div v-if="loadingProjects" class="flex flex-center q-my-md"><q-spinner-dots color="primary" size="30px" /></div>
            <div v-else-if="errorLoadingProjects" class="text-negative text-center q-my-md">{{ errorLoadingProjects }}</div>
            <div v-else class="row q-col-gutter-md">
              <div class="col-12 col-sm-4" v-for="metric in overallMetrics" :key="metric.label">
                <div class="metric-card text-center">
                  <div class="metric-value text-h5">{{ metric.value }}</div>
                  <div class="metric-label text-caption text-secondary">{{ metric.label }}</div>
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import Chart from 'chart.js/auto'
import { storeToRefs } from 'pinia'
import { useTestStore } from 'stores/testStore'
import { statusColor as getStatusColor } from 'src/utils/statusColor'
import { colors } from 'quasar' // Import colors for chart options

defineOptions({ name: 'AnalyticsPage' })

// Store setup
const testStore = useTestStore()
const {
  projects,
  tests,
  selectedProjectId: storeSelectedProjectId,
  loading,
  loadingProjects,
} = storeToRefs(testStore)
const errorLoadingProjects = ref<string | null>(null)

const selectedProjectId = ref<number | null>(storeSelectedProjectId.value)

// Chart instances
let performanceChartInstance: Chart | null = null
let coverageChartInstance: Chart | null = null
let errorTrendsChartInstance: Chart | null = null

// Chart Refs
const performanceChartRef = ref<HTMLCanvasElement | null>(null)
const coverageChartRef = ref<HTMLCanvasElement | null>(null)
const errorTrendsChartRef = ref<HTMLCanvasElement | null>(null)

// --- Computed properties ---

// Options for project selector
const projectOptions = computed(() => {
  return projects.value.map(p => ({ label: p.name, value: p.id }))
})

// Data for Project Health Table
const projectHealthData = computed(() => {
  return projects.value.map(project => ({
    ...project,
    health: calculateProjectHealth(project.last_test_status),
  }))
})

// Data for Overall Stats Cards
const overallMetrics = computed(() => {
  const totalProjects = projects.value.length
  const totalTests = projects.value.reduce((sum, p) => sum + (p.tests_count ?? 0), 0)
  const successfulProjects = projects.value.filter(p =>
    ['passed', 'completed'].includes(p.last_test_status?.toLowerCase() ?? '')
  ).length
  const successRate = totalProjects > 0 ? Math.round((successfulProjects / totalProjects) * 100) : 0

  return [
    { label: 'Всего проектов', value: totalProjects },
    { label: 'Всего тестов (запуски)', value: totalTests },
    { label: 'Успешных проектов', value: `${successRate}%` }
  ]
})

// Health Calculation Logic (Example)
const calculateProjectHealth = (lastStatus: string | undefined | null): number => {
  if (!lastStatus) return 50 // Default if no data
  switch (lastStatus.toLowerCase()) {
    case 'passed':
    case 'completed': return 100
    case 'running':
    case 'pending': return 75
    case 'failed':
    case 'error': return 25
    default: return 50
  }
}

const healthColumns = [
  {
    name: 'name',
    required: true,
    label: 'Проект',
    align: 'left' as const,
    field: 'name',
    sortable: true
  },
  {
    name: 'health',
    required: true,
    label: 'Здоровье',
    align: 'center' as const,
    field: 'health',
    sortable: true,
    format: (val: number) => `${val}%`
  }
]

const getHealthColor = (value: number) => {
  if (value >= 90) return 'positive'
  if (value >= 70) return 'primary'
  if (value >= 40) return 'warning'
  return 'negative'
}

// --- Watchers ---

// Watch for changes in the local selectedProjectId and update the store
watch(selectedProjectId, (newId) => {
  console.log('Analytics Page: Project selected:', newId)
  testStore.selectProject(newId)
})

// Watch for changes in the store's selectedProjectId (e.g., if changed elsewhere)
watch(storeSelectedProjectId, (newId) => {
  if (selectedProjectId.value !== newId) {
    selectedProjectId.value = newId
  }
})

// Watch for test data changes to update charts
watch(tests, (newTestData) => {
  if (selectedProjectId.value && newTestData && newTestData.length > 0) {
    console.log("Updating charts with new test data for project:", selectedProjectId.value)
    updatePerformanceChart(newTestData)
    updateCoverageChart(newTestData)
    updateErrorTrendsChart(newTestData)
  } else {
    // Clear charts if no project selected or no data
    clearCharts()
  }
}, { deep: true })

// --- Chart Update Functions ---

const updatePerformanceChart = (data: typeof tests.value) => {
  if (!performanceChartRef.value) return
  // Example: Show number of tests run over time
  const testCounts = data.reduce((acc, t) => {
    const date = new Date(t.created_at).toLocaleDateString()
    acc[date] = (acc[date] || 0) + 1
    return acc
  }, {} as Record<string, number>)

  const chartData = {
    labels: Object.keys(testCounts),
    datasets: [{
      label: 'Кол-во тестов',
      data: Object.values(testCounts),
      borderColor: '#1e3a8a',
      backgroundColor: 'rgba(30, 58, 138, 0.1)',
      tension: 0.1,
      fill: true
    }]
  }

  if (performanceChartInstance) {
    performanceChartInstance.data = chartData
    performanceChartInstance.update()
  } else {
    performanceChartInstance = new Chart(performanceChartRef.value, { type: 'line', data: chartData, options: commonChartOptions('top') })
  }
}

const updateCoverageChart = (data: typeof tests.value) => {
  if (!coverageChartRef.value) return
  // Example: Show test status distribution
  const statusCounts = data.reduce((acc, t) => {
    const status = t.status ?? 'unknown'
    acc[status] = (acc[status] || 0) + 1
    return acc
  }, {} as Record<string, number>)

  const chartData = {
    labels: Object.keys(statusCounts),
    datasets: [{
      data: Object.values(statusCounts),
      backgroundColor: Object.keys(statusCounts).map(status => getStatusColor(status))
    }]
  }

  if (coverageChartInstance) {
    coverageChartInstance.data = chartData
    coverageChartInstance.update()
  } else {
    coverageChartInstance = new Chart(coverageChartRef.value, { type: 'doughnut', data: chartData, options: commonChartOptions('top') })
  }
}

const updateErrorTrendsChart = (data: typeof tests.value) => {
  if (!errorTrendsChartRef.value) return
  // Example: Show number of failed/error tests over time
  const errorCounts = data.reduce((acc, t) => {
    if (['failed', 'error'].includes(t.status?.toLowerCase() ?? '')) {
      const date = new Date(t.created_at).toLocaleDateString()
      acc[date] = (acc[date] || 0) + 1
    }
    return acc
  }, {} as Record<string, number>)

  const chartData = {
    labels: Object.keys(errorCounts),
    datasets: [{
      label: 'Кол-во ошибок/сбоев',
      data: Object.values(errorCounts),
      backgroundColor: getStatusColor('failed')
    }]
  }

  if (errorTrendsChartInstance) {
    errorTrendsChartInstance.data = chartData
    errorTrendsChartInstance.update()
  } else {
    errorTrendsChartInstance = new Chart(errorTrendsChartRef.value, { type: 'bar', data: chartData, options: commonChartOptions('top') })
  }
}

const clearCharts = () => {
  const emptyData = { labels: [], datasets: [] }
  if (performanceChartInstance) {
    performanceChartInstance.data = emptyData
    performanceChartInstance.update()
  }
  if (coverageChartInstance) {
    coverageChartInstance.data = emptyData
    coverageChartInstance.update()
  }
  if (errorTrendsChartInstance) {
    errorTrendsChartInstance.data = emptyData
    errorTrendsChartInstance.update()
  }
}

// Common Chart Options
const commonChartOptions = (legendPosition: 'top' | 'bottom' | 'left' | 'right' = 'top') => {
  // Get colors from Quasar variables
  const textColor = colors.getPaletteColor('secondary'); // Use secondary for text (Platinum)
  const gridColor = 'rgba(224, 224, 224, 0.1)'; // Lighter grid lines on dark background
  const tooltipBackground = colors.getPaletteColor('dark'); // Jet Black for tooltips

  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { 
            position: legendPosition, 
            labels: {
                color: textColor, // Use text color for legend
                boxWidth: 12,
                padding: 15
            }
        },
        tooltip: {
            mode: 'index' as const,
            intersect: false,
            backgroundColor: tooltipBackground, // Dark tooltip background
            titleColor: textColor,
            bodyColor: textColor,
            padding: 10,
            cornerRadius: 4,
            boxPadding: 4
        },
    },
    scales: {
        x: {
            display: true,
            title: { display: false, text: 'Дата' },
            ticks: {
                color: textColor, // Use text color for X axis ticks
                 maxRotation: 0, // Prevent label rotation
                 autoSkip: true, // Improve label skipping
                 maxTicksLimit: 10 // Limit number of ticks
            },
            grid: {
                color: gridColor, // Use grid color for X axis grid lines
                drawOnChartArea: false // Only draw border if needed
            }
        },
        y: {
            display: true,
            beginAtZero: true,
            title: { display: false, text: 'Значение' },
             ticks: {
                 color: textColor, // Use text color for Y axis ticks
                 padding: 10
            },
             grid: {
                 color: gridColor // Use grid color for Y axis grid lines
            }
        }
    }
  };
};

// --- Lifecycle Hooks ---

onMounted(async () => {
  // Fetch projects when component mounts
  try {
    await testStore.fetchProjects()
    // If a project was already selected in the store, trigger chart updates
    if (selectedProjectId.value && tests.value.length > 0) {
      updatePerformanceChart(tests.value)
      updateCoverageChart(tests.value)
      updateErrorTrendsChart(tests.value)
    } else {
      // Initialize charts as empty if no project selected or no data
      clearCharts()
    }
  } catch (error) {
    console.error("Error fetching projects on mount:", error)
    errorLoadingProjects.value = error instanceof Error ? error.message : 'Не удалось загрузить проекты'
  }
})

onUnmounted(() => {
  // Cleanup charts
  performanceChartInstance?.destroy()
  coverageChartInstance?.destroy()
  errorTrendsChartInstance?.destroy()
  performanceChartInstance = null
  coverageChartInstance = null
  errorTrendsChartInstance = null
})
</script>

<style scoped lang="scss">
.page-padding {
  padding: 24px;
}

.page-title {
  color: $text-primary; 
  font-weight: 600;
}

.project-selector {
  // Uses bg-surface class for background
  :deep(.q-field__native), 
  :deep(.q-field__control), 
  :deep(.q-field__marginal) {
    color: $text-primary;
  }
  :deep(.q-field__control) {
      background-color: $surface-background !important;
      &:before, &:after {
        border-color: $separator-color !important;
      }
  }
}

.card-title {
    color: $text-primary;
    font-weight: 500;
    margin-bottom: 16px; 
}

.chart-card,
.table-card,
.stats-card-container {
    background-color: $surface-background;
    border: 1px solid $separator-color;
    border-radius: $card-border-radius;
    box-shadow: $card-box-shadow;
    height: 100%; // Ensure cards in a row have same height
}

.chart-container {
  position: relative;
  height: 300px;
  width: 100%;
}

.metric-card {
  background-color: lighten($surface-background, 5%); 
  padding: 16px;
  border-radius: $border-radius;
  border: 1px solid $separator-dark-color;
  text-align: center; 
  height: 100%;

  .metric-value {
    font-size: 1.75rem; 
    font-weight: 600;
    color: $primary; 
  }

  .metric-label {
    margin-top: 4px;
    color: $text-secondary;
  }
}

.modern-table {
    border: none; // Remove double border as card has one
    box-shadow: none; // Remove double shadow
    background: transparent; // Inherit background
}

// Responsive adjustments if needed
@media (max-width: $breakpoint-sm-max) {
  .chart-container {
    height: 250px; // Smaller charts on smaller screens
  }
  .metric-card .metric-value {
      font-size: 1.5rem; // Slightly smaller value on mobile
  }
}
</style> 