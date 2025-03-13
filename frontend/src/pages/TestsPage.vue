<template>
  <q-page padding>
    <!-- Общий график -->
    <q-card class="q-mb-lg rounded-borders">
      <q-card-section>
        <div class="text-h6">Обзор тестов</div>
      </q-card-section>
      <q-card-section>
        <BarChart v-if="overviewChart" :chart-data="overviewChart" :chart-options="chartOptions" class="q-mx-auto" style="max-height: 300px"/>
      </q-card-section>
    </q-card>

    <!-- Основная таблица -->
    <q-table
      title="PHP Sniffer Результаты"
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
      <!-- Кастомные ячейки -->
      <template v-slot:body-cell-status="props">
        <q-td :props="props">
          <q-badge :color="statusColor(props.value)" class="q-py-xs">
            {{ props.value }}
          </q-badge>
        </q-td>
      </template>

      <template v-slot:body-cell-errors="props">
        <q-td :props="props">
          <q-chip color="red" text-color="white" dense>
            {{ props.value }}
          </q-chip>
        </q-td>
      </template>

      <template v-slot:body-cell-warnings="props">
        <q-td :props="props">
          <q-chip color="orange" text-color="white" dense>
            {{ props.value }}
          </q-chip>
        </q-td>
      </template>

      <template v-slot:body-cell-execution_time="props">
        <q-td :props="props">
          {{ props.value ? parseFloat(props.value).toFixed(2) + 's' : '-' }}
        </q-td>
      </template>
    </q-table>

    <!-- Детальная панель -->
    <q-card v-if="currentTest" class="q-mt-lg rounded-borders">
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">{{ currentTest.name }}</div>
        <div class="row q-col-gutter-x-md">
          <div class="col">
            Статус: <q-badge :color="statusColor(currentTest.status)">{{ currentTest.status }}</q-badge>
          </div>
          <div class="col">
            Ошибок: <q-chip color="red" text-color="white" dense>{{ currentTest.result.totals.errors }}</q-chip>
          </div>
          <div class="col">
            Предупреждений: <q-chip color="orange" text-color="white" dense>{{ currentTest.result.totals.warnings }}</q-chip>
          </div>
          <div class="col">
            Исправимо: <q-badge color="green">{{ currentTest.result.totals.fixable }}</q-badge>
          </div>
        </div>
      </q-card-section>

      <q-tabs v-model="activeTab" dense class="bg-grey-2 rounded-borders">
        <q-tab name="files" label="Файлы" />
        <q-tab name="errors" label="Ошибки" />
        <q-tab name="chart" label="График" />
      </q-tabs>

      <q-tab-panels v-model="activeTab" animated class="rounded-borders">
        <!-- Вкладка с файлами -->
        <q-tab-panel name="files">
          <q-list bordered separator>
            <q-item v-for="(fileData, filePath) in currentTest.result.files" :key="filePath">
              <q-item-section avatar>
                <q-icon name="description" color="primary" />
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

        <!-- Вкладка с ошибками -->
        <q-tab-panel name="errors">
          <q-list bordered separator v-if="currentFileErrors">
            <q-item v-for="(msg, index) in currentFileErrors" :key="index">
              <q-item-section avatar>
                <q-icon :name="msg.type === 'ERROR' ? 'error' : 'warning'" 
                       :color="msg.type === 'ERROR' ? 'red' : 'orange'" />
              </q-item-section>

              <q-item-section>
                <q-item-label>{{ msg.message }}</q-item-label>
                <q-item-label caption lines="2">
                  Строка: {{ msg.line }}, Колонка: {{ msg.column }}<br>
                  Источник: {{ msg.source }}
                </q-item-label>
              </q-item-section>

              <q-item-section side v-if="msg.fixable">
                <q-badge color="green" label="Исправимо" />
              </q-item-section>
            </q-item>
          </q-list>
        </q-tab-panel>

        <!-- Вкладка с графиком -->
        <q-tab-panel name="chart">
          <BarChart v-if="fileDistributionChart" :chart-data="fileDistributionChart" :chart-options="chartOptions" />
        </q-tab-panel>
      </q-tab-panels>
    </q-card>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { BarChart } from 'vue-chart-3'
import { Chart, registerables } from 'chart.js'
import { useTestStore } from 'stores/testStore'
import type { QTableColumn } from 'quasar'
import type { TestResult, Message } from 'src/types/test.type'

Chart.register(...registerables)

const testStore = useTestStore()
const { tests, loading } = storeToRefs(testStore)
const selectedTest = ref<TestResult[]>([])
const activeTab = ref('files')
const currentFileErrors = ref<Message[] | null>(null)

onMounted(async () => {
  await testStore.fetchTests()
})

const currentTest = computed(() => selectedTest.value[0] || null)

const formattedTests = computed(() => 
  tests.value.map(test => ({
    ...test,
    errors: test.result?.totals?.errors || 0,
    warnings: test.result?.totals?.warnings || 0
  }))
)

const columns: QTableColumn[] = [
  { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true },
  { name: 'name', label: 'Название', field: 'name', align: 'left', sortable: true },
  { name: 'status', label: 'Статус', field: 'status', align: 'left', sortable: true },
  { name: 'errors', label: 'Ошибки', field: 'errors', align: 'center', sortable: true },
  { name: 'warnings', label: 'Предупреждения', field: 'warnings', align: 'center', sortable: true },
  { name: 'execution_time', label: 'Время выполнения', field: 'execution_time', align: 'right', sortable: true }
]

const statusColor = (status: string) => {
  switch(status) {
    case 'completed': return 'green'
    case 'failed': return 'red'
    default: return 'orange'
  }
}

const showDetails = (evt: Event, row: TestResult) => {
  selectedTest.value = [row]
  activeTab.value = 'files'
  currentFileErrors.value = null
}

const showFileErrors = (filePath: string) => {
  if (currentTest.value?.result?.files?.[filePath]) {
    currentFileErrors.value = currentTest.value.result.files[filePath].messages
    activeTab.value = 'errors'
  }
}

// Графики
const overviewChart = computed(() => ({
  labels: formattedTests.value.map(t => t.name),
  datasets: [
    {
      label: 'Ошибки',
      data: formattedTests.value.map(t => t.errors),
      backgroundColor: '#ff6b6b',
      borderColor: '#c62828',
      borderWidth: 1
    },
    {
      label: 'Предупреждения',
      data: formattedTests.value.map(t => t.warnings),
      backgroundColor: '#4ecdc4',
      borderColor: '#00695c',
      borderWidth: 1
    }
  ]
}))

const fileDistributionChart = computed(() => {
  if (!currentTest.value?.result?.files) return null

  const files = Object.entries(currentTest.value.result.files)
  return {
    labels: files.map(([path]) => path.split('\\').pop()),
    datasets: [
      {
        label: 'Ошибки',
        data: files.map(([, data]) => data.errors),
        backgroundColor: '#ff6b6b'
      }
    ]
  }
})

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

<style scoped>
.rounded-borders {
  border-radius: 12px;
}

.q-table {
  background: transparent;
}

.q-card {
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
</style>