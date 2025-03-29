<template>
  <q-list bordered separator>
    <q-item-label header class="bg-grey-2">
      <div class="row items-center justify-between">
        <div>Список проверенных файлов</div>
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
      v-for="(fileData, filePath) in filteredFiles"
      :key="filePath"
      clickable
      v-ripple
      :active="currentFile === String(filePath)"
      @click="$emit('select-file', String(filePath))"
    >
      <q-item-section avatar>
        <q-avatar :color="(fileData.errors ?? 0) > 0 ? 'red-2' : 'green-2'" text-color="white">
          <q-icon 
            name="code" 
            :color="(fileData.errors ?? 0) > 0 ? 'red' : 'green'" 
          />
        </q-avatar>
      </q-item-section>
      <q-item-section>
        <q-item-label>{{ String(filePath).split('\\').pop() }}</q-item-label>
        <q-item-label caption>
          <div class="row items-center q-gutter-x-sm">
            <q-badge :color="(fileData.errors ?? 0) > 0 ? 'negative' : 'positive'" outline>
              {{ fileData.errors ?? 0 }} ошибок
            </q-badge>
            <q-badge :color="(fileData.warnings ?? 0) > 0 ? 'warning' : 'positive'" outline>
              {{ fileData.warnings ?? 0 }} предупреждений
            </q-badge>
            <span class="text-grey-7">{{ filePath }}</span>
          </div>
        </q-item-label>
      </q-item-section>
      <q-item-section side>
        <q-btn-group flat>
          <q-btn flat round icon="visibility" @click.stop="$emit('select-file', String(filePath))" />
          <q-btn flat round icon="content_copy" @click.stop="$emit('copy-path', String(filePath))" />
        </q-btn-group>
      </q-item-section>
    </q-item>
  </q-list>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { FileResult } from 'src/types/test.type'

const search = ref('')

const props = defineProps<{
  files: Record<string, FileResult>
  currentFile: string | null
}>()

defineEmits<{
  'select-file': [path: string]
  'copy-path': [path: string]
}>()

const filteredFiles = computed(() => {
  if (!search.value) return props.files
  
  return Object.entries(props.files).reduce((acc, [path, data]) => {
    if (path.toLowerCase().includes(search.value.toLowerCase())) {
      acc[path] = data
    }
    return acc
  }, {} as Record<string, FileResult>)
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