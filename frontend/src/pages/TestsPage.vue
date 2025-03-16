<template>
  <q-page padding class="modern-page">
    <div v-if="loading" class="full-width full-height flex flex-center">
      <q-spinner-dots color="primary" size="40px" />
    </div>
    <template v-else>
      <StatisticsCards :tests="tests" />
      <QuickActions
        v-model:filter-type="filterType"
        v-model:date-range="dateRange"
        @new-test="onNewTest"
        @export="onExport"
        @refresh="onRefresh"
      />

      <q-tabs 
        v-model="viewMode" 
        class="view-tabs q-mb-lg"
        active-color="primary"
        indicator-color="primary"
        align="left"
        narrow-indicator
      >
        <q-tab name="table">
          <q-item-section avatar>
            <q-icon name="table_chart" />
          </q-item-section>
          <q-item-section>
            Таблица
            <q-badge color="primary" floating>{{ formattedTests.length }}</q-badge>
          </q-item-section>
        </q-tab>
        <q-tab name="heatmap">
          <q-item-section avatar>
            <q-icon name="grid_view" />
          </q-item-section>
          <q-item-section>
            Тепловая карта
            <q-badge color="deep-orange" floating>{{ Object.keys(aggregatedFiles).length }}</q-badge>
          </q-item-section>
        </q-tab>
        <q-tab name="bubble">
          <q-item-section avatar>
            <q-icon name="bubble_chart" />
          </q-item-section>
          <q-item-section>
            Карта проблем
            <q-badge color="accent" floating>{{ Object.keys(aggregatedFiles).length }}</q-badge>
          </q-item-section>
        </q-tab>
      </q-tabs>

      <HeatMap 
        v-if="viewMode === 'heatmap'" 
        :files="aggregatedFiles"
        @select-file="showFileFromMap"
      />

      <TestsTable 
        v-if="viewMode === 'table'"
        :tests="formattedTests"
        :loading="loading"
        v-model:selected="selectedTest"
        @row-click="showDetails"
      />

      <BubbleChart
        v-if="viewMode === 'bubble'"
        :files="aggregatedFiles"
        @select-file="showFileFromMap"
      />

      <TestDetails
        v-if="currentTest"
        :test="currentTest"
        @close="selectedTest = []"
      />
    </template>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useTestStore } from 'stores/testStore'
import type { TestResult } from 'src/types/test.type'

// Импортируем компоненты
import StatisticsCards from 'components/tests/dashboard/StatisticsCards.vue'
import QuickActions from 'components/tests/dashboard/QuickActions.vue'
import TestsTable from 'components/tests/visualizations/TestsTable.vue'
import HeatMap from 'components/tests/visualizations/HeatMap.vue'
import BubbleChart from 'components/tests/visualizations/BubbleChart.vue'
import TestDetails from 'components/tests/details/TestDetails.vue'

const testStore = useTestStore()
const { tests, loading } = storeToRefs(testStore)
const selectedTest = ref<TestResult[]>([])
const filterType = ref('Все')
const dateRange = ref('')
const viewMode = ref('table')

// Загрузка данных при монтировании компонента
onMounted(async () => {
  console.log('Component mounted, fetching tests...')
  try {
    await testStore.fetchTests()
  } catch (error) {
    console.error('Error in onMounted:', error)
  }
})

// Добавляем вычисляемое свойство для агрегированных файлов
const aggregatedFiles = computed(() => {
  const files: Record<string, { errors: number, warnings: number }> = {};
  
  tests.value.forEach(test => {
    if (test.result?.files) {
      Object.entries(test.result.files).forEach(([path, data]) => {
        if (!files[path]) {
          files[path] = { errors: 0, warnings: 0 };
        }
        files[path].errors += data.errors || 0;
        files[path].warnings += data.warnings || 0;
      });
    }
  });
  
  return files;
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
    errors: test.result?.totals?.errors || 0,
    warnings: test.result?.totals?.warnings || 0
  }))
)

const currentTest = computed(() => selectedTest.value[0] || null)

// Показ деталей теста
const showDetails = (evt: Event, row: TestResult) => {
  selectedTest.value = [row]
}

// Показ файла из карты
const showFileFromMap = (filePath: string) => {
  const test = tests.value.find(t => 
    t.result?.files && t.result.files[filePath]
  )
  if (test) {
    selectedTest.value = [test]
  }
}

// Action handlers
const onNewTest = () => {
  console.log('New test requested')
}

const onExport = () => {
  console.log('Export requested')
}

const onRefresh = async () => {
  await testStore.fetchTests()
}
</script>

<style scoped lang="scss">
.modern-page {
  background: #f8f9fa;
  min-height: 100vh;
  padding: 1.5rem;
}

.view-tabs {
  background: white;
  border-radius: 12px;
  padding: 4px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);

  .q-tab {
    border-radius: 8px;
    padding: 8px 16px;
    min-height: 48px;
    
    &--active {
      background: rgba(var(--q-primary), 0.1);
    }

    .q-badge {
      top: 4px;
      right: 4px;
    }
  }
}
</style>