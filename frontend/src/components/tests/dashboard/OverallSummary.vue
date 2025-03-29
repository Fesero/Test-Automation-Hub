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
import type { TestResult } from 'src/types/test.type'

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
  tests: TestResult[]
}

const props = defineProps<Props>()

const chartRef = ref<HTMLCanvasElement | null>(null)
let chart: Chart | null = null

const selectedFile = ref<string | null>(null)

// Message columns for the table
const messageColumns = [
  { name: 'severity', label: 'Уровень', field: 'severity', align: 'left' as const },
  { name: 'location', label: 'Расположение', field: 'line', align: 'left' as const },
  { name: 'message', label: 'Сообщение', field: 'message', align: 'left' as const }
]

const selectedFileMessages = computed(() => {
  if (!selectedFile.value) return []
  
  // Find messages in all tests for the selected file
  for (const test of props.tests) {
    if (test.result?.files?.[selectedFile.value]) {
      const messages = test.result.files[selectedFile.value]!.messages || []
      return messages.map((msg, index) => ({...msg, index}))
    }
  }
  
  return []
})

const selectedFileStats = computed(() => {
  if (!selectedFile.value) return null
  return fileStats.value[selectedFile.value] || null
})

const selectFile = (filePath: string) => {
  selectedFile.value = filePath
}

// Aggregated statistics for files
const fileStats = computed(() => {
  const stats: Record<string, { errors: number, warnings: number }> = {}
  
  props.tests.forEach(test => {
    if (test.result?.files) {
      Object.entries(test.result.files).forEach(([path, file]) => {
        // Initialize file stats
        if (!stats[path]) {
          stats[path] = { errors: 0, warnings: 0 }
        }
        
        // Count errors and warnings
        if (file.errors) {
          stats[path].errors += file.errors
        }
        if (file.warnings) {
          stats[path].warnings += file.warnings
        }
      })
    }
  })
  
  return stats
})

// Top files with errors
const topErrorFiles = computed(() => {
  return Object.entries(fileStats.value)
    .map(([path, stats]) => ({
      path,
      errors: stats.errors,
      warnings: stats.warnings
    }))
    .sort((a, b) => (b.errors + b.warnings) - (a.errors + a.warnings))
    .slice(0, 5)
})

// Calculate health percentage for a file
const calculateHealthPercent = (file: {errors: number, warnings: number}) => {
  const totalIssues = file.errors * 2 + file.warnings
  const maxPossibleIssues = 20 // Arbitrary max value for percentage calculation
  const healthPercent = Math.max(0, 100 - (totalIssues / maxPossibleIssues) * 100)
  return Math.round(healthPercent)
}

// Calculate health color based on percentage
const calculateHealthColor = (file: {errors: number, warnings: number}) => {
  const percent = calculateHealthPercent(file)
  if (percent >= 70) return 'positive'
  if (percent >= 40) return 'warning'
  return 'negative'
}

// Overall statistics
const totalErrors = computed(() => {
  return Object.values(fileStats.value).reduce((sum, stats) => sum + stats.errors, 0)
})

const totalWarnings = computed(() => {
  return Object.values(fileStats.value).reduce((sum, stats) => sum + stats.warnings, 0)
})

const totalFiles = computed(() => {
  return Object.keys(fileStats.value).length
})

// Some previous metrics for comparison (sample data)
const previousTotalErrors = computed(() => Math.round(totalErrors.value * 0.8))
const previousTotalWarnings = computed(() => Math.round(totalWarnings.value * 1.2))

// Clean files percentage
const cleanFilesPercent = computed(() => {
  if (totalFiles.value === 0) return 0
  const filesWithIssues = Object.values(fileStats.value).filter(f => f.errors > 0 || f.warnings > 0).length
  const cleanFiles = totalFiles.value - filesWithIssues
  return Math.round((cleanFiles / totalFiles.value) * 100)
})

const createChart = () => {
  if (!chartRef.value) return

  chart?.destroy()
  
  const totalFiles = Object.keys(fileStats.value).length
  const filesWithErrors = Object.values(fileStats.value).filter(f => f.errors > 0).length
  const filesWithWarnings = Object.values(fileStats.value).filter(f => f.warnings > 0 && f.errors === 0).length
  const cleanFiles = totalFiles - filesWithErrors - filesWithWarnings

  chart = new Chart(chartRef.value, {
    type: 'doughnut',
    data: {
      labels: ['Файлы с ошибками', 'Файлы с предупреждениями', 'Чистые файлы'],
      datasets: [{
        data: [filesWithErrors, filesWithWarnings, cleanFiles],
        backgroundColor: ['#D50000', '#FFD600', '#00C853'],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 20,
            usePointStyle: true,
            pointStyle: 'circle'
          }
        },
        tooltip: {
          callbacks: {
            label: (tooltipItem: TooltipItem<'doughnut'>) => {
              const label = tooltipItem.label || '';
              const rawValue = tooltipItem.raw as number;
              const percentage = totalFiles ? Math.round((rawValue / totalFiles) * 100) : 0;
              return `${label}: ${rawValue} (${percentage}%)`;
            }
          }
        }
      }
    }
  })
}

// Update chart when data changes
watch(() => props.tests, () => {
  createChart()
}, { deep: true })

onMounted(() => {
  createChart()
})

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
</style> 