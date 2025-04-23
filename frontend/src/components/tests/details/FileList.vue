<template>
  <q-list bordered separator>
    <q-item-label header class="bg-grey-2">
      <div class="row items-center justify-between">
        <div>Список проверенных файлов ({{ filteredResults.length }})</div>
        <q-input 
          v-model="search" 
          dense 
          outlined 
          placeholder="Поиск файлов"
          class="col-4"
        >
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
      </div>
    </q-item-label>
    <q-item
      v-for="result in filteredResults"
      :key="result.id"
      clickable
      v-ripple
      :active="currentFilePath === result.parsedMetrics?.file_path"
      @click="$emit('select-file', result.parsedMetrics?.file_path || '')"
    >
      <q-item-section avatar>
        <q-avatar :color="(result.parsedMetrics?.errors ?? 0) > 0 ? 'red-2' : 'green-2'" text-color="white">
          <q-icon 
            name="code" 
            :color="(result.parsedMetrics?.errors ?? 0) > 0 ? 'red' : 'green'" 
          />
        </q-avatar>
      </q-item-section>
      <q-item-section>
        <q-item-label>{{ result.parsedMetrics?.file_path?.split(/[\\/]/).pop() }}</q-item-label>
        <q-tooltip v-if="result.parsedMetrics?.file_path">{{ result.parsedMetrics.file_path }}</q-tooltip>
        <q-item-label caption>
          <div class="row items-center q-gutter-x-sm">
            <q-badge :color="(result.parsedMetrics?.errors ?? 0) > 0 ? 'negative' : 'positive'" outline>
              {{ result.parsedMetrics?.errors ?? 0 }} ошибок
            </q-badge>
            <q-badge :color="(result.parsedMetrics?.warnings ?? 0) > 0 ? 'warning' : 'positive'" outline>
              {{ result.parsedMetrics?.warnings ?? 0 }} предупреждений
            </q-badge>
            <span class="text-grey-7">{{ result.parsedMetrics?.file_path }}</span>
          </div>
        </q-item-label>
      </q-item-section>
      <q-item-section side>
        <q-btn-group flat>
          <q-btn flat round icon="visibility" @click.stop="$emit('select-file', result.parsedMetrics?.file_path || '')" />
          <q-btn flat round icon="content_copy" @click.stop="$emit('copy-path', result.parsedMetrics?.file_path || '')" />
        </q-btn-group>
      </q-item-section>
    </q-item>
    <q-item v-if="filteredResults.length === 0">
      <q-item-section class="text-center text-grey">
        Файлы не найдены или не содержат ошибок/предупреждений.
      </q-item-section>
    </q-item>
  </q-list>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { TestResult } from 'src/types/test.type'

const search = ref('')

const props = defineProps<{
  results: TestResult[]
  currentFilePath: string | null | undefined
}>()

defineEmits<{
  'select-file': [path: string]
  'copy-path': [path: string]
}>()

const filteredResults = computed(() => {
  if (!search.value) return props.results
  
  const needle = search.value.toLowerCase()
  return props.results.filter((result) =>
    result.parsedMetrics?.file_path?.toLowerCase().includes(needle)
  )
})
</script>

<style scoped lang="scss">
.q-item {
  transition: background-color 0.3s ease;

  &:hover {
    background-color: rgba(var(--q-primary), 0.03);
  }

  &--active {
    background-color: rgba(var(--q-primary), 0.05);
  }
}
</style> 