<template>
  <q-list bordered separator>
    <q-item-label header class="bg-grey-2">
      <div class="row items-center justify-between">
        <div class="text-subtitle1">
          Ошибки в файле: <span class="text-weight-bold">{{ filePath?.split('\\').pop() }}</span>
        </div>
        <q-btn-group flat>
          <q-btn flat round icon="arrow_back" @click="$emit('back')" />
          <q-btn 
            flat 
            round 
            icon="content_copy" 
            @click="filePath && $emit('copy-path', filePath)"
            :disable="!filePath" 
          />
        </q-btn-group>
      </div>
    </q-item-label>
    <q-item v-for="(msg, index) in errors" :key="index" class="error-item">
      <q-item-section avatar>
        <q-avatar :color="msgTypeColor(msg) + '-2'" :text-color="msgTypeColor(msg)">
          <q-icon :name="msgTypeIcon(msg)" />
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
      <q-item-section side>
        <div class="row items-center q-gutter-x-sm">
          <q-badge 
            v-if="msg.fixable" 
            color="positive"
            class="q-px-sm"
          >
            <q-icon name="build" size="xs" class="q-mr-xs" />
            Исправимо
          </q-badge>
          <q-btn
            v-if="msg.fixable"
            flat
            round
            size="sm"
            icon="auto_fix_high"
            color="positive"
            @click="$emit('fix-error', msg)"
          >
            <q-tooltip>Исправить автоматически</q-tooltip>
          </q-btn>
        </div>
      </q-item-section>
    </q-item>
  </q-list>
</template>

<script setup lang="ts">
import type { Message } from 'src/types/test.type'

defineProps<{
  filePath: string | null
  errors: Message[]
}>()

defineEmits<{
  'back': []
  'copy-path': [path: string]
  'fix-error': [msg: Message]
}>()

const msgTypeIcon = (msg: Message) => {
  if (msg.severity === 'error') return 'error'
  if (msg.severity === 'warning') return 'warning'
  return 'info'
}

const msgTypeColor = (msg: Message) => {
  if (msg.severity === 'error') return 'red'
  if (msg.severity === 'warning') return 'orange'
  return 'grey'
}
</script>

<style scoped lang="scss">
.error-item {
  transition: all 0.3s ease;

  &:hover {
    background: rgba(var(--q-primary), 0.03);
  }
}
</style> 