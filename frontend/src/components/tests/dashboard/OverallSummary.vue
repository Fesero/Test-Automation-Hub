<template>
  <q-card class="modern-card q-mb-lg">
    <div class="row no-wrap items-center q-px-lg q-pt-lg">
      <div>
        <div class="text-h6 q-mb-xs">Обзор производительности</div>
        <div class="text-grey-8">Анализ результатов тестирования и метрики</div>
      </div>
      <q-space />
      <q-btn-dropdown flat color="primary" label="Последние 30 дней">
        <q-list>
          <q-item clickable v-close-popup>
            <q-item-section>Последние 7 дней</q-item-section>
          </q-item>
          <q-item clickable v-close-popup>
            <q-item-section>Последние 30 дней</q-item-section>
          </q-item>
          <q-item clickable v-close-popup>
            <q-item-section>Последние 90 дней</q-item-section>
          </q-item>
        </q-list>
      </q-btn-dropdown>
    </div>
    
    <q-card-section>
      <div class="row q-col-gutter-md">
        <!-- Stats overview -->
        <div class="col-12 col-md-4">
          <q-card flat class="modern-card bg-blue-1 full-height">
            <q-card-section>
              <div class="text-subtitle1 text-primary q-mb-sm">Всего проблем</div>
              
              <div class="text-h3 text-weight-bold q-mb-md">
                {{ totalErrors + totalWarnings }}
              </div>
              
              <div class="row q-col-gutter-lg">
                <div class="col-6">
                  <div class="text-caption text-grey-8">ОШИБКИ</div>
                  <div class="row items-center">
                    <div class="text-h6 text-negative">{{ totalErrors }}</div>
                    <q-icon 
                      :name="totalErrors > previousTotalErrors ? 'arrow_upward' : 'arrow_downward'" 
                      :color="totalErrors > previousTotalErrors ? 'negative' : 'positive'" 
                      size="18px"
                      class="q-ml-sm"
                    />
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-8">ПРЕДУПРЕЖДЕНИЯ</div>
                  <div class="row items-center">
                    <div class="text-h6 text-warning">{{ totalWarnings }}</div>
                    <q-icon 
                      :name="totalWarnings > previousTotalWarnings ? 'arrow_upward' : 'arrow_downward'" 
                      :color="totalWarnings > previousTotalWarnings ? 'negative' : 'positive'" 
                      size="18px"
                      class="q-ml-sm"
                    />
                  </div>
                </div>
              </div>
              
              <q-separator class="q-my-md" />
              
              <div class="text-subtitle2 q-mb-sm">Проанализировано файлов</div>
              <div class="row items-center">
                <div class="text-h5 text-secondary">{{ totalFiles }}</div>
                <div class="text-caption text-grey q-ml-md">{{ cleanFilesPercent }}% без проблем</div>
              </div>
            </q-card-section>
          </q-card>
        </div>
        
        <!-- Distribution pie chart -->
        <div class="col-12 col-md-4">
          <q-card flat class="modern-card full-height">
            <q-card-section>
              <div class="text-subtitle2 text-grey-8 q-mb-md">Распределение проблем</div>
              <div class="chart-container">
                <canvas ref="chartRef"></canvas>
              </div>
            </q-card-section>
          </q-card>
        </div>
        
        <!-- Top error files -->
        <div class="col-12 col-md-4">
          <q-card flat class="modern-card full-height">
            <q-card-section>
              <div class="text-subtitle2 text-grey-8 q-mb-md">Файлы с наибольшим количеством проблем</div>
              <div class="top-files">
                <q-list padding class="rounded-borders">
                  <q-item v-for="file in topErrorFiles" :key="file.path" clickable @click="selectFile(file.path)" class="q-mb-sm rounded-borders file-item" :class="selectedFile === file.path ? 'selected-file' : ''">
                    <q-item-section>
                      <q-item-label lines="1" class="ellipsis">
                        {{ file.path.split('\\').pop() }}
                      </q-item-label>
                      <q-item-label caption class="text-grey-8 ellipsis">{{ file.path }}</q-item-label>
                      
                      <div class="row q-mt-xs q-gutter-x-sm">
                        <q-badge outline color="negative">
                          {{ file.errors }} ошибок
                        </q-badge>
                        <q-badge outline color="warning">
                          {{ file.warnings }} предупреждений
                        </q-badge>
                      </div>
                    </q-item-section>
                    <q-item-section side>
                      <q-circular-progress
                        show-value
                        font-size="10px"
                        :value="calculateHealthPercent(file)"
                        size="40px"
                        :color="calculateHealthColor(file)"
                        track-color="grey-3"
                      >
                        {{ calculateHealthPercent(file) }}%
                      </q-circular-progress>
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Selected file details -->
      <div v-if="selectedFile" class="q-mt-lg">
        <q-card class="modern-card">
          <q-card-section class="row items-center no-wrap q-pb-none">
            <div>
              <div class="text-subtitle1 text-weight-medium">{{ selectedFile.split('\\').pop() }}</div>
              <div class="text-caption text-grey-8">{{ selectedFile }}</div>
            </div>
            <q-space />
            <q-btn flat round icon="close" @click="selectedFile = null" />
          </q-card-section>
          
          <q-separator class="q-my-md" />
          
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-4">
                <div class="issue-stats">
                  <div class="row q-col-gutter-md">
                    <div class="col-6">
                      <q-card flat class="bg-red-1 text-center q-pa-md rounded-borders">
                        <div class="text-subtitle1 text-grey-8">Ошибки</div>
                        <div class="text-h4 text-negative">{{ selectedFileStats?.errors || 0 }}</div>
                      </q-card>
                    </div>
                    <div class="col-6">
                      <q-card flat class="bg-orange-1 text-center q-pa-md rounded-borders">
                        <div class="text-subtitle1 text-grey-8">Предупреждения</div>
                        <div class="text-h4 text-warning">{{ selectedFileStats?.warnings || 0 }}</div>
                      </q-card>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="col-12 col-md-8">
                <q-table
                  :rows="selectedFileMessages"
                  :columns="messageColumns"
                  row-key="index"
                  flat
                  bordered
                  wrap-cells
                  hide-pagination
                  :pagination="{ rowsPerPage: 0 }"
                  class="modern-table"
                >
                  <template v-slot:body-cell-severity="props">
                    <q-td :props="props">
                      <q-badge :color="props.value === 'error' ? 'negative' : 'warning'">
                        {{ props.value }}
                      </q-badge>
                    </q-td>
                  </template>
                  
                  <template v-slot:body-cell-location="props">
                    <q-td :props="props">
                      <div class="row items-center">
                        <q-icon name="code" size="xs" class="q-mr-xs" />
                        Line: {{ props.row.line }}
                        <span v-if="props.row.column" class="q-ml-sm">
                          <q-icon name="straighten" size="xs" class="q-mr-xs" />
                          Column: {{ props.row.column }}
                        </span>
                      </div>
                    </q-td>
                  </template>
                </q-table>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import type { TooltipItem } from 'chart.js'
import {
  Chart,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
  DoughnutController,
  ArcElement
} from 'chart.js'
import type { Test, TestResultMessage } from 'src/types/test.type'

Chart.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
  DoughnutController,
  ArcElement
)

interface Props {
  tests: Test[]
}

const props = defineProps<Props>()

const chartRef = ref<HTMLCanvasElement | null>(null)
let chart: Chart | null = null

const selectedFile = ref<string | null>(null)

// Message columns for the table
const messageColumns = [
  { name: 'type', label: 'Уровень', field: 'type', align: 'left' as const },
  { name: 'location', label: 'Расположение', field: 'line', align: 'left' as const },
  { name: 'message', label: 'Сообщение', field: 'message', align: 'left' as const },
  { name: 'source', label: 'Источник', field: 'source', align: 'left' as const },
]

// Recalculate stats based on Test[] and results
const aggregatedStats = computed(() => {
  let totalErrors = 0
  let totalWarnings = 0
  let totalFiles = 0
  const fileStats: Record<string, { errors: number; warnings: number }> = {}

  props.tests.forEach((test) => {
    test.results?.forEach((result) => {
      totalFiles++
      const errors = result.parsedMetrics?.errors ?? 0
      const warnings = result.parsedMetrics?.warnings ?? 0
      totalErrors += errors
      totalWarnings += warnings

      const filePath = result.parsedMetrics?.file_path
      if (filePath) {
        if (!fileStats[filePath]) {
          fileStats[filePath] = { errors: 0, warnings: 0 }
        }
        fileStats[filePath].errors += errors
        fileStats[filePath].warnings += warnings
      }
    })
  })

  const filesWithErrors = Object.values(fileStats).filter(f => f.errors > 0).length
  const filesWithWarnings = Object.values(fileStats).filter(f => f.warnings > 0 && f.errors === 0).length
  const cleanFiles = totalFiles - filesWithErrors - filesWithWarnings
  const cleanFilesPercent = totalFiles === 0 ? 100 : Math.round((cleanFiles / totalFiles) * 100)

  return {
    totalErrors,
    totalWarnings,
    totalFiles,
    fileStats,
    filesWithErrors,
    filesWithWarnings,
    cleanFiles,
    cleanFilesPercent
  }
})

// Use aggregated stats in computed properties
const totalErrors = computed(() => aggregatedStats.value.totalErrors)
const totalWarnings = computed(() => aggregatedStats.value.totalWarnings)
const totalFiles = computed(() => aggregatedStats.value.totalFiles)
const cleanFilesPercent = computed(() => aggregatedStats.value.cleanFilesPercent)

// Simulate previous stats for trend icons (replace with actual logic if needed)
const previousTotalErrors = ref(0)
const previousTotalWarnings = ref(0)

// Top error files based on aggregated stats
const topErrorFiles = computed(() => {
  return Object.entries(aggregatedStats.value.fileStats)
    .map(([path, data]) => ({ path, ...data }))
    .filter(file => file.errors > 0 || file.warnings > 0)
    .sort((a, b) => b.errors - a.errors || b.warnings - a.warnings)
    .slice(0, 5)
})

// Chart data based on aggregated stats
const chartData = computed(() => ({
  labels: ['С ошибками', 'С предупреждениями', 'Чистые'],
  datasets: [
    {
      data: [
        aggregatedStats.value.filesWithErrors,
        aggregatedStats.value.filesWithWarnings,
        aggregatedStats.value.cleanFiles
      ],
      backgroundColor: ['#ff6b6b', '#ffd93d', '#6bff6b'],
    },
  ],
}))

// Logic for selected file details
const selectedFileStats = computed(() => {
  if (!selectedFile.value) return null
  return aggregatedStats.value.fileStats[selectedFile.value] || { errors: 0, warnings: 0 }
})

// Find messages across all tests for the selected file
const selectedFileMessages = computed(() => {
  if (!selectedFile.value) return []
  const messages: (TestResultMessage & { testId: number; index: number })[] = []
  let messageIndex = 0
  props.tests.forEach(test => {
    test.results?.forEach(result => {
      if (result.parsedMetrics?.file_path === selectedFile.value) {
        result.parsedOutput?.forEach(msg => {
          messages.push({ ...msg, testId: test.id, index: messageIndex++ })
        })
      }
    })
  })
  return messages
})

const selectFile = (filePath: string | null) => {
  selectedFile.value = filePath
}

// Chart creation logic
const createChart = () => {
  if (!chartRef.value) return
  chart?.destroy()
  chart = new Chart(chartRef.value, {
    type: 'doughnut',
    data: chartData.value,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom' as const,
        },
        tooltip: {
          callbacks: {
            label: function (context: TooltipItem<'doughnut'>) {
              let label = context.label || ''
              if (label) {
                label += ': '
              }
              if (context.parsed !== null) {
                const dataset = context.chart.data.datasets?.[0]
                if (dataset?.data) {
                  const total = dataset.data.reduce((a, b) => (a as number) + (b as number), 0) as number
                  const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0
                  label += `${context.parsed} (${percentage}%)`
                }
              }
              return label
            },
          },
        },
      },
    },
  })
}

// Health calculation helpers
const calculateHealthPercent = (file: { errors: number; warnings: number }) => {
  const totalIssues = file.errors + file.warnings
  const score = Math.max(0, 100 - totalIssues * 5)
  return score
}

const calculateHealthColor = (file: { errors: number; warnings: number }) => {
  if (file.errors > 0) return 'negative'
  if (file.warnings > 0) return 'warning'
  return 'positive'
}

onMounted(createChart)
watch(chartData, createChart, { deep: true })

onUnmounted(() => {
  chart?.destroy()
})
</script>

<style scoped lang="scss">
.chart-container {
  position: relative;
  height: 260px;
  max-width: 100%;
}

.file-item {
  transition: all 0.2s ease;
  border: 1px solid transparent;
  
  &:hover {
    background-color: rgba(0, 0, 0, 0.02);
  }
  
  &.selected-file {
    background-color: rgba(var(--q-primary), 0.08);
    border-color: rgba(var(--q-primary), 0.2);
  }
}

.ellipsis {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.modern-card {
  background-color: #fff;
  border-radius: $border-radius;
  box-shadow: $shadow-sm;
}

.top-files {
  max-height: 300px;
  overflow-y: auto;
}
</style> 