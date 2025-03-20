<template>
  <q-card class="overall-summary q-mb-lg" flat bordered>
    <q-card-section class="bg-primary text-white">
      <div class="text-h6">Общая сводка</div>
      <div class="text-subtitle2">Статистика по всем тестам</div>
    </q-card-section>
    <q-card-section>
      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-4">
          <div class="stat-box">
            <div class="text-subtitle2 text-grey-7">Всего ошибок</div>
            <div class="text-h4 text-negative">
              {{ totalErrors }}
              <q-icon name="error_outline" class="q-ml-sm" />
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="stat-box">
            <div class="text-subtitle2 text-grey-7">Всего предупреждений</div>
            <div class="text-h4 text-warning">
              {{ totalWarnings }}
              <q-icon name="warning_amber" class="q-ml-sm" />
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="stat-box">
            <div class="text-subtitle2 text-grey-7">Проверено файлов</div>
            <div class="text-h4 text-primary">
              {{ totalFiles }}
              <q-icon name="folder" class="q-ml-sm" />
            </div>
          </div>
        </div>
      </div>

      <div class="row q-mt-md">
        <div class="col-12 col-md-6">
          <div class="text-subtitle2 text-grey-7 q-mb-sm">Топ проблемных файлов</div>
          <div class="top-files">
            <div v-for="file in topErrorFiles" :key="file.path" class="file-stat q-mb-md">
              <div 
                class="row items-center justify-between cursor-pointer"
                @click="selectFile(file.path)"
              >
                <div class="col">
                  <div class="text-subtitle2">{{ file.path.split('\\').pop() }}</div>
                  <div class="text-caption text-grey">{{ file.path }}</div>
                </div>
                <div class="col-auto">
                  <q-chip color="negative" text-color="white" dense>
                    {{ file.errors }} ошибок
                  </q-chip>
                  <q-chip color="warning" text-color="white" dense class="q-ml-sm">
                    {{ file.warnings }} предупреждений
                  </q-chip>
                </div>
              </div>
              <q-linear-progress
                :value="file.errors / maxErrors"
                color="negative"
                class="q-mt-xs"
              />
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="text-subtitle2 text-grey-7 q-mb-sm">Распределение проблем</div>
          <div class="chart-container">
            <canvas ref="chartRef"></canvas>
          </div>
        </div>
      </div>

      <!-- Ошибки выбранного файла -->
      <div v-if="selectedFile" class="q-mt-lg">
        <div class="row items-center justify-between q-mb-md">
          <div class="text-subtitle2 text-grey-7">Ошибки в файле: {{ selectedFile.split('\\').pop() }}</div>
          <q-btn flat round icon="close" @click="selectedFile = null" />
        </div>
        <q-list bordered separator>
          <q-item v-for="(msg, index) in selectedFileMessages" :key="index" class="error-item">
            <q-item-section avatar>
              <q-avatar :color="msg.severity === 'error' ? 'red-2' : 'orange-2'" :text-color="msg.severity === 'error' ? 'red' : 'orange'">
                <q-icon :name="msg.severity === 'error' ? 'error' : 'warning'" />
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ msg.message }}</q-item-label>
              <q-item-label caption>
                <div class="row items-center q-gutter-x-md">
                  <span>
                    <q-icon name="code" size="xs" class="q-mr-xs" />
                    Строка: {{ msg.line }}
                  </span>
                  <span v-if="msg.column">
                    <q-icon name="straighten" size="xs" class="q-mr-xs" />
                    Колонка: {{ msg.column }}
                  </span>
                  <span v-if="msg.source" class="text-grey-7">
                    <q-icon name="label" size="xs" class="q-mr-xs" />
                    {{ msg.source }}
                  </span>
                </div>
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
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
import type { TestResult, Message } from 'src/types/test.type' // eslint-disable-line @typescript-eslint/no-unused-vars

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

const props = defineProps<{
  tests: TestResult[]
}>()

const chartRef = ref<HTMLCanvasElement | null>(null)
let chart: Chart | null = null

const selectedFile = ref<string | null>(null)

const selectedFileMessages = computed(() => {
  if (!selectedFile.value) return []
  
  // Ищем сообщения во всех тестах для выбранного файла
  for (const test of props.tests) {
    if (test.result?.files?.[selectedFile.value]) {
      return test.result.files[selectedFile.value]!.messages || []
    }
  }
  
  return []
})

const selectFile = (filePath: string) => {
  selectedFile.value = filePath
}

// Агрегированная статистика по файлам
const fileStats = computed(() => {
  const stats: Record<string, { errors: number, warnings: number }> = {}
  
  console.log('Tests:', props.tests)
  
  props.tests.forEach(test => {
    console.log('Test result:', test.result)
    if (test.result?.files) {
      Object.entries(test.result.files).forEach(([path, file]) => {
        console.log('File:', path, file)
        // Инициализируем статистику для файла
        const fileStats = { errors: 0, warnings: 0 }
        stats[path] = fileStats
        
        // Подсчитываем ошибки и предупреждения
        if (file.errors) {
          fileStats.errors = file.errors
        }
        if (file.warnings) {
          fileStats.warnings = file.warnings
        }
      })
    }
  })
  
  console.log('Final stats:', stats)
  return stats
})

// Топ файлов с ошибками
const topErrorFiles = computed(() => {
  return Object.entries(fileStats.value)
    .map(([path, stats]) => ({
      path,
      errors: stats.errors,
      warnings: stats.warnings
    }))
    .sort((a, b) => b.errors - a.errors)
    .slice(0, 5)
})

const maxErrors = computed(() => {
  const max = Math.max(...topErrorFiles.value.map(f => f.errors))
  return max || 1
})

// Общая статистика
const totalErrors = computed(() => {
  return Object.values(fileStats.value).reduce((sum, stats) => sum + stats.errors, 0)
})

const totalWarnings = computed(() => {
  return Object.values(fileStats.value).reduce((sum, stats) => sum + stats.warnings, 0)
})

const totalFiles = computed(() => {
  return Object.keys(fileStats.value).length
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
          position: 'bottom'
        }
      }
    }
  })
}

onMounted(createChart)
watch(() => props.tests, createChart, { deep: true })

onUnmounted(() => {
  if (chart) {
    chart.destroy()
    chart = null
  }
})
</script>

<style scoped lang="scss">
.overall-summary {
  border-radius: 12px;
  transition: all 0.3s ease;
  
  &:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  }
}

.stat-box {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  text-align: center;
  transition: all 0.3s ease;
  
  &:hover {
    transform: translateY(-2px);
    background: #f1f3f5;
  }
}

.chart-container {
  height: 300px;
  padding: 16px;
}

.top-files {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
}

.file-stat {
  background: white;
  border-radius: 8px;
  padding: 12px;
  transition: all 0.3s ease;
  
  &:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }

  .cursor-pointer {
    cursor: pointer;
  }
}

.error-item {
  transition: all 0.3s ease;

  &:hover {
    background: rgba(var(--q-primary), 0.03);
  }
}
</style> 