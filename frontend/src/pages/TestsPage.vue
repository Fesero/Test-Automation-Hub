<template>
  <q-page padding class="modern-page">
    <div v-if="loading" class="full-width full-height flex flex-center">
      <q-spinner-dots color="primary" size="40px" />
    </div>
    <template v-else>
      <OverallSummary :tests="tests" />
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
        class="view-tabs q-mb-lg modern-tabs"
        active-color="primary"
        indicator-color="primary"
        align="left"
        narrow-indicator
        dense
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
        <q-tab name="analytics">
          <q-item-section avatar>
            <q-icon name="analytics" />
          </q-item-section>
          <q-item-section>
            Аналитика
            <q-badge color="purple" floating>{{ tests.length }}</q-badge>
          </q-item-section>
        </q-tab>
      </q-tabs>

      <div class="content-container">
        <component
          :is="currentView"
          :tests="formattedTests"
          :aggregated-files="aggregatedFiles"
          @select-file="onSelectFile"
          @row-click="onRowClick"
        />
        
        <TestDetails
          v-if="selectedTest"
          :test="selectedTest"
          @close="selectedTest = null"
          class="q-mt-lg"
        />
      </div>
    </template>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useTestStore } from 'stores/testStore'
import type { TestResult } from 'src/types/test.type'

defineOptions({
  name: 'TestsPage'
})

// Импортируем компоненты
import StatisticsCards from 'components/tests/dashboard/StatisticsCards.vue'
import QuickActions from 'components/tests/dashboard/QuickActions.vue'
import TestsTable from 'components/tests/visualizations/TestsTable.vue'
import HeatMap from 'components/tests/visualizations/HeatMap.vue'
import BubbleChart from 'components/tests/visualizations/BubbleChart.vue'
import OverallSummary from 'components/tests/dashboard/OverallSummary.vue'
import TestAnalytics from 'components/tests/analytics/Analytics.vue'
import TestDetails from 'components/tests/details/TestDetails.vue'

const testStore = useTestStore()
const { tests, loading } = storeToRefs(testStore)
const selectedTest = ref<TestResult | null>(null)
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
  
  tests.value.forEach((test: TestResult) => {
    if (test.result?.files) {
      Object.entries(test.result.files).forEach(([path, data]) => {
        if (!files[path]) {
          files[path] = { errors: 0, warnings: 0 };
        }
        if (data && typeof data === 'object' && 'errors' in data && 'warnings' in data) {
          files[path].errors += data.errors || 0;
          files[path].warnings += data.warnings || 0;
        }
      });
    }
  });
  
  return files;
})

// Фильтрация тестов
const filteredTests = computed(() => {
  if (filterType.value === 'Все') return tests.value
  return tests.value.filter((t: TestResult) => t.type === filterType.value)
})

const formattedTests = computed(() => 
  filteredTests.value.map((test: TestResult) => ({
    ...test,
    errors: test.result?.totals?.errors || 0,
    warnings: test.result?.totals?.warnings || 0
  }))
)

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

const onRowClick = (evt: Event, row: TestResult) => {
  selectedTest.value = row
}

const onSelectFile = (filePath: string) => {
  const test = tests.value.find((t: TestResult) => 
    t.result?.files && t.result.files[filePath]
  )
  if (test) {
    selectedTest.value = test
  }
}

const currentView = computed(() => {
  switch (viewMode.value) {
    case 'table':
      return TestsTable
    case 'heatmap':
      return HeatMap
    case 'bubble':
      return BubbleChart
    case 'analytics':
      return TestAnalytics
    default:
      return TestsTable
  }
})
</script>

<style scoped lang="scss">
.modern-page {
  background: $bg-gradient-light;
  min-height: 100vh;
}

.content-container {
  background: $background-white;
  border-radius: $border-radius;
  box-shadow: $card-shadow;
  padding: 24px;
  min-height: 400px;
  position: relative;
  z-index: 1;
}

.view-tabs {
  background: $glass-background;
  backdrop-filter: blur(10px);
  border-radius: $border-radius;
  padding: 4px;
  box-shadow: $glass-shadow;
  border: 1px solid $glass-border;

  .q-tab {
    border-radius: 8px;
    transition: all 0.3s ease;

    &--active {
      background: $gradient-primary;
      color: white;
      box-shadow: $neon-shadow;
    }

    .q-icon {
      font-size: 20px;
    }
  }
}

.q-badge {
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 12px;
  box-shadow: $shadow-sm;
}
</style>