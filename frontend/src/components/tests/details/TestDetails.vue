<template>
  <q-card v-if="test" class="details-card q-mt-lg" flat bordered>
    <q-card-section class="bg-primary text-white details-header">
      <div class="row items-center justify-between q-mb-md">
        <div class="col-grow">
          <div class="text-h5 q-mb-sm">{{ test.name }}</div>
          <div class="row items-center q-gutter-x-md">
            <q-chip dense outline color="white" text-color="white" icon="folder_shared">
              {{ test.project?.name || 'Неизвестный проект' }}
              <q-tooltip>Проект: {{ test.project?.name || 'N/A' }}</q-tooltip>
            </q-chip>
            <q-badge
              :color="typeColor(test.type)"
              class="text-bold q-px-sm cursor-default"
              style="font-size: 0.9rem"
            >
              {{ test.type }}
              <q-tooltip>Тип анализатора</q-tooltip>
            </q-badge>
            <q-badge
              :color="statusColor(test.status)"
              class="text-bold q-px-sm cursor-default"
            >
              {{ test.status }}
              <q-tooltip>Статус выполнения теста</q-tooltip>
            </q-badge>
            <q-chip
              dense
              :color="totalErrors > 0 ? 'negative' : 'positive'"
              text-color="white"
              icon="error_outline"
            >
              {{ totalErrors }} ошибок
              <q-tooltip>Общее количество ошибок</q-tooltip>
            </q-chip>
            <q-chip
              dense
              :color="totalWarnings > 0 ? 'warning' : 'positive'"
              text-color="white"
              icon="warning_amber"
            >
              {{ totalWarnings }} предупреждений
              <q-tooltip>Общее количество предупреждений</q-tooltip>
            </q-chip>
          </div>
        </div>
        <div class="col-auto">
          <q-btn flat round icon="close" color="white" @click="$emit('close')" />
        </div>
      </div>

      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-6">
          <!-- This section was using test.result which no longer exists -->
          <!-- 
          <div class="progress-stats">
            <div class="text-subtitle2 q-mb-sm">Прогресс проверки</div>
            <q-linear-progress
              :value="((test.result?.totals?.checked_files ?? 0) / (test.result?.totals?.total_files ?? 1))"
              color="positive"
              class="q-mb-sm"
              style="height: 10px"
              rounded
            />
            <div class="row justify-between text-caption">
              <span>Проверено файлов: {{ test.result?.totals?.checked_files ?? 0 }}</span>
              <span>Всего файлов: {{ test.result?.totals?.total_files ?? 0 }}</span>
            </div>
          </div> 
          -->
        </div>
        <div class="col-12 col-md-6">
          <div class="execution-stats">
            <div class="text-subtitle2 q-mb-sm">Статистика выполнения</div>
            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-card flat class="stat-mini-card bg-blue-1">
                  <q-card-section class="text-center">
                    <div class="text-h6 text-blue">{{ test.execution_time ?? 'N/A' }}s</div>
                    <div class="text-caption">Время выполнения</div>
                  </q-card-section>
                </q-card>
              </div>
              <div class="col-6">
                <q-card flat class="stat-mini-card bg-purple-1">
                  <q-card-section class="text-center">
                    <div class="text-h6 text-purple">{{ test.results?.length ?? 0 }}</div>
                    <div class="text-caption">Проверено файлов</div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </div>
        </div>
      </div>
    </q-card-section>

    <q-tabs
      v-model="activeTab"
      dense
      class="bg-grey-2 text-primary"
      active-color="primary"
      indicator-color="primary"
      align="justify"
      narrow-indicator
    >
      <q-tab name="files" icon="folder" label="Файлы" />
      <q-tab name="errors" icon="error_outline" label="Ошибки" />
      <q-tab v-if="test.type === 'sniffer'" name="fixable" icon="build" label="Исправимые" />
      <q-tab v-if="test.type === 'static_analysis'" name="complexity" icon="analytics" label="Сложность" />
      <q-tab name="summary" icon="summarize" label="Сводка" />
    </q-tabs>

    <q-separator />

    <q-tab-panels v-model="activeTab" animated>
      <q-tab-panel name="files" class="q-pa-none">
        <FileList
          :results="test.results ?? []"
          :current-file-path="selectedResult?.parsedMetrics?.file_path"
          @select-file="selectFile"
          @copy-path="copyPath"
        />
      </q-tab-panel>

      <q-tab-panel name="errors" class="q-pa-none">
        <FileErrors
          v-if="selectedResult"
          :file-path="selectedResult.parsedMetrics?.file_path || ''"
          :messages="selectedResult.parsedOutput ?? []"
          @back="selectedResult = null"
          @copy-path="copyPath"
          @fix-error="fixError"
        />
        <div v-else class="text-center q-pa-lg">
          <q-icon name="find_in_page" size="48px" color="grey-4" />
          <div class="text-h6 text-grey-6 q-mt-sm">Выберите файл для просмотра ошибок</div>
        </div>
      </q-tab-panel>

      <q-tab-panel name="summary">
        <ErrorDistribution :results="test.results ?? []" />
      </q-tab-panel>
    </q-tab-panels>
  </q-card>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import type { Test, TestResult /*, TestResultMessage */ } from 'src/types/test.type'
import FileList from './FileList.vue'
import FileErrors from './FileErrors.vue'
import ErrorDistribution from './ErrorDistribution.vue'

const props = defineProps<{
  test: Test | null
}>()

defineEmits<{
  'close': []
}>()

const $q = useQuasar()
const activeTab = ref('files')
const selectedResult = ref<TestResult | null>(null)

const totalErrors = computed(() => {
  return (
    props.test?.results?.reduce(
      (sum, result) => sum + (result.parsedMetrics?.errors ?? 0),
      0
    ) ?? 0
  )
})

const totalWarnings = computed(() => {
  return (
    props.test?.results?.reduce(
      (sum, result) => sum + (result.parsedMetrics?.warnings ?? 0),
      0
    ) ?? 0
  )
})

watch(() => props.test, () => {
  selectedResult.value = null
  activeTab.value = 'files'
})

const statusColor = (status: string | undefined) => {
  switch (status) {
    case 'completed': return 'green'
    case 'failed': return 'red'
    case 'error': return 'red'
    default: return 'orange'
  }
}

const typeColor = (type: string | undefined) => {
  switch (type) {
    case 'sniffer': return 'blue'
    case 'phpstan': return 'purple'
    default: return 'grey'
  }
}

const selectFile = (filePath: string) => {
  const result = props.test?.results?.find(
    (r) => r.parsedMetrics?.file_path === filePath
  )
  if (result) {
    selectedResult.value = result
    activeTab.value = 'errors'
  } else {
    selectedResult.value = null
  }
}

const copyPath = async (path: string) => {
  if (!path) return
  try {
    await navigator.clipboard.writeText(path)
    $q.notify({
      message: 'Путь скопирован в буфер обмена',
      color: 'positive',
      icon: 'content_copy',
      position: 'top',
      timeout: 2000
    })
  } catch (error) {
    $q.notify({
      message: `Не удалось скопировать путь: ${String(error)}`,
      color: 'negative',
      icon: 'error',
      position: 'top',
      timeout: 2000
    })
  }
}

const fixError = () => {
  $q.notify({
    message: 'Функция автоматического исправления будет доступна в следующей версии',
    color: 'info',
    icon: 'build',
    position: 'top',
    timeout: 3000
  })
}
</script>

<style scoped lang="scss">
.details-header {
  background: linear-gradient(135deg, var(--q-primary) 0%, darken($primary, 15%) 100%);
}

.stat-mini-card {
  border-radius: 8px;
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;

  &:hover {
    transform: translateY(-2px);
  }
}

.progress-stats {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 12px;
}
</style> 