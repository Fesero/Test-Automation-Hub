<template>
  <q-page padding class="modern-page">
    <!-- Project Selection Dropdown -->
    <div class="row items-center justify-between q-mb-lg">
        <div class="text-h4 page-title">Запуски тестов</div> 
        <q-select
          v-model="selectedProjectId"
          :options="projectOptions"
          label="Выберите проект"
          emit-value
          map-options
          outlined
          dense
          class="project-selector bg-surface"
          style="min-width: 300px; max-width: 400px;" 
          @update:model-value="onProjectSelect"
          :loading="loadingProjects"
          options-dense
          use-input
          hide-selected
          fill-input
          input-debounce="0"
          @filter="filterProjects"
          dark
          popup-content-class="bg-surface-alt"
        >
          <template v-slot:no-option>
            <q-item>
              <q-item-section class="text-grey">
                Проекты не найдены
              </q-item-section>
            </q-item>
          </template>
        </q-select>
    </div>

    <div v-if="loading && !selectedProjectId" class="full-width full-height flex flex-center">
      <q-spinner-dots color="primary" size="40px" />
    </div>
    <div v-else-if="!selectedProjectId && !loadingProjects" class="text-center text-secondary q-mt-lg">
      Пожалуйста, выберите проект для отображения тестов.
    </div>
    <template v-else>
      <OverallSummary :tests="tests" :key="selectedProjectId || 'no-project'" />
      <StatisticsCards :tests="tests" :key="selectedProjectId || 'no-project'" />
      <QuickActions
        v-model:filter-type="filterType"
        v-model:date-range="dateRange"
        @new-test="onNewTest"
        @export="onExport"
        @refresh="onRefresh"
        :disabled="!selectedProjectId"
      />

      <q-tabs 
        v-model="viewMode" 
        class="view-tabs q-mb-lg modern-tabs"
        active-color="primary"
        indicator-color="primary"
        align="left"
        narrow-indicator
        dense
        :key="selectedProjectId || 'no-project'"
      >
        <q-tab name="table">
          <q-item-section avatar>
            <q-icon name="table_chart" />
          </q-item-section>
          <q-item-section>
            Таблица
            <q-badge color="grey-7" floating>{{ formattedTests.length }}</q-badge>
          </q-item-section>
        </q-tab>
        <q-tab name="heatmap">
          <q-item-section avatar>
            <q-icon name="grid_view" />
          </q-item-section>
          <q-item-section>
            Тепловая карта
            <q-badge color="grey-7" floating>{{ Object.keys(aggregatedFiles).length }}</q-badge>
          </q-item-section>
        </q-tab>
        <q-tab name="bubble">
          <q-item-section avatar>
            <q-icon name="bubble_chart" />
          </q-item-section>
          <q-item-section>
            Карта проблем
            <q-badge color="grey-7" floating>{{ Object.keys(aggregatedFiles).length }}</q-badge>
          </q-item-section>
        </q-tab>
        <q-tab name="analytics">
          <q-item-section avatar>
            <q-icon name="analytics" />
          </q-item-section>
          <q-item-section>
            Аналитика
            <q-badge color="grey-7" floating>{{ tests.length }}</q-badge>
          </q-item-section>
        </q-tab>
      </q-tabs>

      <div class="content-container">
        <div v-if="loading" class="full-width full-height flex flex-center">
          <q-spinner-dots color="primary" size="40px" />
        </div>
        <component
          v-else
          :is="currentView"
          :tests="formattedTests"
          :aggregated-files="aggregatedFiles"
          @select-file="onSelectFile"
          @row-click="onRowClick"
          :key="selectedProjectId || 'no-project'"
        />
        
        <TestDetails
          v-if="selectedTest"
          :test="selectedTest"
          @close="selectedTest = null"
          class="q-mt-lg"
          :key="selectedTest?.id"
        />
      </div>
    </template>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useTestStore } from 'stores/testStore'
import type { Test, TestResult, Project } from 'src/types/test.type'

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
const { tests, loading, projects, selectedProjectId, loadingProjects } = storeToRefs(testStore)
const selectedTest = ref<Test | null>(null)
const filterType = ref('Все')
const dateRange = ref<{ from: string; to: string } | null>(null)
const viewMode = ref('table')
const filteredProjectOptions = ref<Project[]>([])

// Загрузка данных при монтировании компонента
onMounted(async () => {
  console.log('Component mounted, fetching projects...')
  await testStore.fetchProjects()
})

// Добавляем вычисляемое свойство для агрегированных файлов
const aggregatedFiles = computed(() => {
  const files: Record<string, { errors: number, warnings: number, tests: number[] }> = {}
  
  tests.value.forEach((test: Test) => {
    test.results.forEach((result: TestResult) => {
      const metrics = result.parsedMetrics
      if (metrics?.file_path) {
        const path = metrics.file_path
        if (!files[path]) {
          files[path] = { errors: 0, warnings: 0, tests: [] }
        }
        files[path].errors += metrics.errors || 0
        files[path].warnings += metrics.warnings || 0
        if (!files[path].tests.includes(test.id)) {
          files[path].tests.push(test.id)
        }
      }
    })
  })
  
  return files
})

// Filter projects for QSelect
const filterProjects = (val: string, update: (callbackFn: () => void) => void) => {
  if (val === '') {
    update(() => {
      filteredProjectOptions.value = projects.value;
    });
    return;
  }

  update(() => {
    const needle = val.toLowerCase();
    filteredProjectOptions.value = projects.value.filter(
      (p) => p.name.toLowerCase().indexOf(needle) > -1
    );
  });
};

// Watch for projects load to initialize filter options
watch(projects, (newProjects) => {
    if (newProjects) {
        filteredProjectOptions.value = newProjects;
    }
});

// Computed property for QSelect options (using filtered state)
const projectOptions = computed(() => {
  return filteredProjectOptions.value.map((p) => ({
    label: p.name,
    value: p.id,
  }));
});

// Select project handler
const onProjectSelect = (projectId: number | null) => {
  testStore.selectProject(projectId);
  selectedTest.value = null // Clear selected test when project changes
}

// Combined Filtering Logic
const filteredAndFormattedTests = computed(() => {
  let items = tests.value;

  // Filter by Type
  if (filterType.value !== 'Все') {
    items = items.filter((t: Test) => t.type === filterType.value);
  }

  // Filter by Date Range
  if (dateRange.value?.from && dateRange.value?.to) {
    const fromDate = new Date(dateRange.value.from);
    const toDate = new Date(dateRange.value.to);
    // Set hours to ensure full day coverage
    fromDate.setHours(0, 0, 0, 0);
    toDate.setHours(23, 59, 59, 999);

    items = items.filter((t: Test) => {
      const testDate = new Date(t.created_at);
      return testDate >= fromDate && testDate <= toDate;
    });
  }

  // Format the filtered items
  return items.map((test: Test) => {
    let totalErrors = 0;
    let totalWarnings = 0;
    test.results.forEach((result) => {
      totalErrors += result.parsedMetrics?.errors ?? 0;
      totalWarnings += result.parsedMetrics?.warnings ?? 0;
    });
    return {
      ...test,
      errors: totalErrors,
      warnings: totalWarnings,
      projectName: test.project?.name,
    };
  });
});

// Use the combined computed property for the view
const formattedTests = filteredAndFormattedTests;

// Action handlers
const onNewTest = () => {
  console.log('New test requested')
}

const onExport = () => {
  console.log('Export requested')
}

const onRefresh = async () => {
  if (selectedProjectId.value) {
    await testStore.fetchTests(selectedProjectId.value)
  }
}

// Correct the type definition for the row parameter
type FormattedTest = typeof formattedTests.value[number]; // Get type of array element

const onRowClick = (evt: Event, row: FormattedTest) => {
  const originalTest = tests.value.find(t => t.id === row.id);
  selectedTest.value = originalTest || null;
};

const onSelectFile = (filePath: string) => {
  const test = tests.value.find((t: Test) =>
    t.results.some((r) => r.parsedMetrics?.file_path === filePath)
  )
  selectedTest.value = test || null
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

.project-selector {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(5px);
  border-radius: $border-radius;
  box-shadow: $shadow-sm;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

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
   :deep(.q-field__label) {
        color: $text-secondary !important;
    }
  :deep(.q-field__control) {
      background-color: $surface-background !important;
      &:before, &:after {
        border-color: $separator-color !important;
      }
  }
}

.content-container {
    margin-top: 20px;
}

.view-tabs {
    // Inherits modern-tabs styles from app.scss
    // Add specific overrides if needed
}
</style>