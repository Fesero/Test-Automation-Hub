<template>
  <q-page padding>
    <!-- Фильтры -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-sm-4">
        <q-select
          v-model="filterType"
          :options="['Все', 'sniffer', 'static_analysis']"
          label="Тип теста"
          dense
          outlined
          emit-value
        />
      </div>
      <div class="col-12 col-sm-4">
        <q-input
          v-model="dateRange"
          label="Период"
          dense
          outlined
          type="date"
        />
      </div>
    </div>

    <!-- Вкладки визуализации -->
    <q-tabs v-model="viewMode" dense class="bg-grey-2 rounded-borders q-mb-md">
      <q-tab name="table" label="Таблица" />
      <q-tab name="bubble" label="Карта проблем" />
    </q-tabs>

    <!-- Общий график -->
    <q-card class="q-mb-lg rounded-borders" v-if="viewMode === 'table'">
      <q-card-section>
        <div class="text-h6">Обзор тестов</div>
      </q-card-section>
      <q-card-section>
        <BarChart v-if="overviewChart" :chart-data="overviewChart" :chart-options="chartOptions" />
      </q-card-section>
    </q-card>

    <!-- Основная таблица -->
    <q-table
      v-if="viewMode === 'table'"
      :rows="formattedTests"
      :columns="columns"
      row-key="id"
      :loading="loading"
      @row-click="showDetails"
      selection="single"
      v-model:selected="selectedTest"
      :pagination="{ rowsPerPage: 10 }"
      class="rounded-borders"
    >
      <!-- Тип теста -->
      <template v-slot:body-cell-type="props">
        <q-td :props="props">
          <q-badge :color="typeColor(props.value)">
            {{ props.value }}
          </q-badge>
        </q-td>
      </template>

      <!-- Статус -->
      <template v-slot:body-cell-status="props">
        <q-td :props="props">
          <q-badge :color="statusColor(props.value)">
            {{ props.value }}
          </q-badge>
        </q-td>
      </template>

      <!-- Ошибки -->
      <template v-slot:body-cell-errors="props">
        <q-td :props="props">
          <q-chip 
            :color="props.row.type === 'static_analysis' ? 'deep-purple' : 'red'" 
            text-color="white" 
            dense
          >
            {{ props.value }}
          </q-chip>
        </q-td>
      </template>

      <!-- Время выполнения -->
      <template v-slot:body-cell-execution_time="props">
        <q-td :props="props">
          {{ props.value ? parseFloat(props.value).toFixed(2) + 's' : '-' }}
        </q-td>
      </template>
    </q-table>

    <!-- Пузырьковая карта -->
    <div v-else class="bubble-map-container">
      <svg ref="bubbleMap" width="100%" height="600"></svg>
    </div>

    <!-- Детальная панель -->
    <q-card v-if="currentTest" class="q-mt-lg rounded-borders">
      <q-card-section class="bg-primary text-white">
        <div class="row items-center">
          <div class="text-h6">{{ currentTest.name }}</div>
          <q-badge 
            :color="typeColor(currentTest.type)" 
            class="q-ml-md"
            style="font-size: 0.8rem"
          >
            {{ currentTest.type }}
          </q-badge>
        </div>
        
        <div class="row q-col-gutter-x-md q-mt-sm">
          <div class="col">
            Статус: <q-badge :color="statusColor(currentTest.status)">{{ currentTest.status }}</q-badge>
          </div>
          <div class="col">
            Ошибок: <q-chip color="red" text-color="white" dense>{{ currentTest.result.totals.errors }}</q-chip>
          </div>
          <div class="col">
            Предупреждений: <q-chip color="orange" text-color="white" dense>{{ currentTest.result.totals.warnings }}</q-chip>
          </div>
        </div>
      </q-card-section>

      <q-tabs v-model="activeTab" dense class="bg-grey-2 rounded-borders">
        <q-tab name="files" label="Файлы" />
        <q-tab name="errors" label="Ошибки" />
        <q-tab v-if="currentTest.type === 'sniffer'" name="fixable" label="Исправимые" />
        <q-tab v-if="currentTest.type === 'static_analysis'" name="complexity" label="Сложность" />
      </q-tabs>

      <q-tab-panels v-model="activeTab" animated>
        <!-- Файлы -->
        <q-tab-panel name="files">
          <q-list bordered separator>
            <q-item v-for="(fileData, filePath) in currentTest.result.files" :key="filePath">
              <q-item-section avatar>
                <q-icon 
                  :name="currentTest.type === 'sniffer' ? 'code' : 'analytics'" 
                  :color="currentTest.type === 'sniffer' ? 'blue' : 'purple'" 
                />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ filePath.split('\\').pop() }}</q-item-label>
                <q-item-label caption>
                  Ошибок: {{ fileData.errors }} | Предупреждений: {{ fileData.warnings }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-btn flat round icon="visibility" @click="showFileErrors(filePath)" />
              </q-item-section>
            </q-item>
          </q-list>
        </q-tab-panel>

        <!-- Ошибки -->
        <q-tab-panel name="errors">
          <q-list bordered separator v-if="currentFileErrors">
            <q-item v-for="(msg, index) in currentFileErrors" :key="index">
              <q-item-section avatar>
                <q-icon 
                  :name="msgTypeIcon(msg)" 
                  :color="msgTypeColor(msg)"
                />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ msg.message }}</q-item-label>
                <q-item-label caption>
                  Строка: {{ msg.line }}{{ msg.column ? `, Колонка: ${msg.column}` : '' }}
                </q-item-label>
              </q-item-section>
              <q-item-section side v-if="msg.fixable">
                <q-badge color="green" label="Исправимо" />
              </q-item-section>
            </q-item>
          </q-list>
        </q-tab-panel>

        <!-- Сложность (PHPStan) -->
        <q-tab-panel name="complexity">
          <q-banner class="bg-grey-3">
            Визуализация сложности будет добавлена в будущих версиях
          </q-banner>
        </q-tab-panel>
      </q-tab-panels>
    </q-card>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { storeToRefs } from 'pinia'
import { BarChart } from 'vue-chart-3'
import { Chart, registerables } from 'chart.js'
import * as d3 from 'd3'
import { useTestStore } from 'stores/testStore'
import type { TestResult, Message } from 'src/types/test.type'
import type { QTableColumn } from 'quasar'

interface AggregatedFile {
  errors: number
  warnings: number
  testTypes: Set<string>
  fileName: string
}

// Типы для иерархии D3
interface HierarchyData {
  name?: string
  value?: number
  type: string
  details?: string
  children?: HierarchyData[]
}

interface BubbleNode extends HierarchyData {
  name: string
  value: number
  type: string
  details: string
}

Chart.register(...registerables)

const testStore = useTestStore()
const { tests, loading } = storeToRefs(testStore)
const selectedTest = ref<TestResult[]>([])
const activeTab = ref('files')
const filterType = ref('Все')
const dateRange = ref('')
const viewMode = ref('table')
const currentFileErrors = ref<Message[] | null>(null)
const bubbleMap = ref<SVGSVGElement | null>(null)
const tooltip = ref<{x: number, y: number, text: string} | null>(null)

onMounted(async () => {
  await testStore.fetchTests()
  if (viewMode.value === 'bubble') drawBubbleMap()
})

const currentTest = computed(() => selectedTest.value[0] || null)

// Агрегация данных для карты
const aggregatedFiles = computed(() => {
  const allFiles: Record<string, AggregatedFile> = {}

  tests.value.forEach(test => {
    if (test.result?.files) {
      Object.entries(test.result.files).forEach(([filePath, data]) => {
        if (!allFiles[filePath]) {
          allFiles[filePath] = {
            errors: 0,
            warnings: 0,
            testTypes: new Set(),
            fileName: filePath.split(/[\\/]/).pop() || 'unknown'
          }
        }
        
        allFiles[filePath].errors += data.errors || 0
        allFiles[filePath].warnings += data.warnings || 0
        allFiles[filePath].testTypes.add(test.type)
      })
    }
  })

  return Object.entries(allFiles)
  .filter(([, data]) => data.errors > 0 || data.warnings > 0) // Фильтруем нулевые файлы
  .map(([path, data]) => ({
    path,
    fileName: data.fileName,
    errorCount: data.errors,
    warningCount: data.warnings,
    testTypes: Array.from(data.testTypes)
  }))
})

// Фильтрация тестов
const filteredTests = computed(() => {
  if (filterType.value === 'Все') return tests.value
  return tests.value.filter(t => t.type === filterType.value)
})

// Форматирование тестов для таблицы
const formattedTests = computed(() => 
  filteredTests.value.map(test => ({
    ...test,
    errors: test.result?.totals?.errors || 0, // Гарантируем числа
    warnings: test.result?.totals?.warnings || 0
  }))
)

// Фильтрация файлов для карты
const filteredFiles = computed(() => {
  return aggregatedFiles.value.filter(file => {
    if (filterType.value === 'Все') return true
    return file.testTypes.includes(filterType.value)
  })
})

// Визуализация пузырьковой карты
// Визуализация пузырьковой карты
const drawBubbleMap = () => {
  if (!bubbleMap.value) return

  // Создаем корректную иерархическую структуру
  const rootData: BubbleNode = {
    name: 'Проект',
    value: 0,
    type: '',
    details: 'Корневой элемент',
    children: filteredFiles.value
    .filter(file => file.errorCount + file.warningCount > 0) // Дополнительная фильтрация
    .map(file => ({
      name: file.fileName,
      value: file.errorCount + file.warningCount,
      type: file.testTypes.join(', '),
      details: `Ошибок: ${file.errorCount}\nПредупреждений: ${file.warningCount}\nТипы тестов: ${file.testTypes.join(', ')}`
    }))
  }

  d3.select(bubbleMap.value).selectAll("*").remove()

  const width = 1200
  const height = 600

  const svg = d3.select(bubbleMap.value)
    .attr("viewBox", `0 0 ${width} ${height}`)
    .style("max-width", "1200px")
    .style("margin", "0 auto")

  const bubble = d3.pack<BubbleNode>() // Используем BubbleNode
    .size([width, height])
    .padding(3)

  const root = d3.hierarchy<BubbleNode>(rootData) // Явное указание типа
    .sum(d => d.value)
    .sort((a, b) => {
      const aValue = a.value || 0
      const bValue = b.value || 0
      return bValue - aValue
    })

  const nodes = bubble(root).descendants()

  const colorScale = d3.scaleOrdinal<string>()
    .domain(['sniffer', 'static_analysis'])
    .range(['#2196f3', '#9c27b0'])

  // Отрисовка
  const nodeGroups = svg.selectAll<SVGGElement, d3.HierarchyCircularNode<BubbleNode>>(".node")
    .data(nodes.filter(d => !d.children))
    .enter()
    .append("g")
    .attr("class", "node")
    .attr("transform", d => `translate(${d.x},${d.y})`)

  // Круги
  nodeGroups.append("circle")
    .attr("r", d => d.r)
    .attr("fill", d => {
      const primaryType = d.data.type.split(', ')[0]
      if (!primaryType) return "#f0f0f0"
      return colorScale(primaryType)
    })
    .attr("stroke", "#fff")
    .attr("stroke-width", 2)
    .on("mouseover", (event: MouseEvent, d: d3.HierarchyCircularNode<BubbleNode>) => { // Исправлен тип
      const data = d.data
      tooltip.value = {
        x: event.pageX,
        y: event.pageY,
        text: `${data.name}\n${data.details}`
      }
    })
    .on("mouseout", () => tooltip.value = null)

  // Текстовые метки (имена файлов)
  nodeGroups.append("text")
    .attr("dy", ".3em")
    .style("text-anchor", "middle")
    .style("fill", "white")
    .style("font-size", "14px")
    .text(d => d.data.name.split('.')[0] || '') // Гарантируем строку

  // Текстовые метки (количество проблем)
  nodeGroups.append("text")
    .attr("dy", "1.3em")
    .style("text-anchor", "middle")
    .style("fill", "white")
    .style("font-size", "12px")
    .text(d => d.data.value > 0 ? `${d.data.value} ошибок` : '') // Гарантируем строку

    svg.selectAll(".node")
      .data(nodes.filter(d => !d.children))
      .enter()
      .append("g")
      .attr("class", "node")
      .attr("transform", d => `translate(${d.x},${d.y})`)
      .style("opacity", 0)
      .transition()
      .duration(500)
      .style("opacity", 1)
}

// Обновление при изменении фильтров
watch([filterType, dateRange, viewMode], () => {
  if (viewMode.value === 'bubble') {
    void nextTick(drawBubbleMap) // Добавляем void
  }
})

// График обзора
const overviewChart = computed(() => ({
  labels: filteredTests.value.map(t => t.name),
  datasets: [
    {
      label: 'Ошибки',
      data: filteredTests.value.map(t => t.result?.totals?.errors || 0),
      backgroundColor: '#ff6b6b'
    },
    {
      label: 'Предупреждения',
      data: filteredTests.value.map(t => t.result?.totals?.warnings || 0),
      backgroundColor: '#4ecdc4'
    }
  ]
}))

// Столбцы таблицы
const columns: QTableColumn[] = [
  { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true },
  { name: 'name', label: 'Название', field: 'name', align: 'left', sortable: true },
  { 
    name: 'type', 
    label: 'Тип', 
    field: 'type', 
    align: 'left', 
    sortable: true 
  },
  { name: 'status', label: 'Статус', field: 'status', align: 'left', sortable: true },
  { name: 'errors', label: 'Ошибки', field: 'errors', align: 'center', sortable: true },
  { name: 'warnings', label: 'Предупреждения', field: 'warnings', align: 'center', sortable: true },
  { name: 'execution_time', label: 'Время', field: 'execution_time', align: 'right', sortable: true }
]

// Цвета статусов
const statusColor = (status: string) => {
  switch(status) {
    case 'completed': return 'green'
    case 'failed': return 'red'
    default: return 'orange'
  }
}

// Цвета типов тестов
const typeColor = (type: string) => {
  switch(type) {
    case 'sniffer': return 'blue'
    case 'static_analysis': return 'purple'
    default: return 'grey'
  }
}

// Иконки сообщений
const msgTypeIcon = (msg: Message) => {
  if (msg.type === 'ERROR') return 'error'
  if (msg.type === 'WARNING') return 'warning'
  return 'info'
}

// Цвета сообщений
const msgTypeColor = (msg: Message) => {
  if (msg.type === 'ERROR') return 'red'
  if (msg.type === 'WARNING') return 'orange'
  return 'grey'
}

// Показ деталей теста
const showDetails = (evt: Event, row: TestResult) => {
  selectedTest.value = [row]
  activeTab.value = 'files'
  currentFileErrors.value = null
}

// Показ ошибок файла
const showFileErrors = (filePath: string) => {
  if (currentTest.value?.result?.files?.[filePath]) {
    currentFileErrors.value = currentTest.value.result.files[filePath].messages
    activeTab.value = 'errors'
  }
}

// Опции графика
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom' }
  },
  scales: {
    y: { beginAtZero: true }
  }
}
</script>

<style scoped lang="scss">
.rounded-borders {
  border-radius: 12px;
}

.bubble-map-container {
  max-width: 1200px;
  margin: 0 auto;
  border: 1px solid #eee;
  padding: 16px;
  background: white;
}

.tooltip {
  position: absolute;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 8px 12px;
  border-radius: 4px;
  pointer-events: none;
  font-size: 14px;
  transform: translate(-50%, 20px);
}

circle {
  transition: transform 0.2s;
  
  &:hover {
    transform: scale(1.1);
    cursor: pointer;
  }
}
</style>