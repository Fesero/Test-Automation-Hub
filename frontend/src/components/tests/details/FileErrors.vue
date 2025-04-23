<template>
  <q-list bordered separator>
    <q-item-label header class="bg-grey-2">
      <div class="row items-center justify-between">
        <div class="text-subtitle1">
          Ошибки в файле: 
          <span class="text-weight-bold cursor-pointer">
            {{ filePathShort }}
            <q-tooltip v-if="filePath">{{ filePath }}</q-tooltip>
          </span>
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
    <q-item v-for="(msg, index) in messages" :key="index" class="error-item">
      <q-item-section avatar>
        <q-avatar :color="msgTypeColor(msg) + '-2'" :text-color="msgTypeColor(msg)">
          <q-icon :name="msgTypeIcon(msg)" />
          <q-tooltip>{{ msg.type }}</q-tooltip>
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
            <q-tooltip>Эту ошибку можно исправить автоматически</q-tooltip>
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
          <q-btn
            flat
            round
            size="sm"
            icon="content_copy"
            color="grey-7"
            @click="copyErrorDetails(msg)"
          >
            <q-tooltip>Копировать детали ошибки</q-tooltip>
          </q-btn>
        </div>
      </q-item-section>
    </q-item>
    <q-item v-if="!messages || messages.length === 0">
      <q-item-section class="text-center text-grey">
        Нет ошибок/предупреждений для этого файла.
      </q-item-section>
    </q-item>
  </q-list>
</template>

<script setup lang="ts">
import type { TestResultMessage } from 'src/types/test.type'
import { copyToClipboard, useQuasar } from 'quasar'
import { computed } from 'vue'

const props = defineProps<{
  filePath: string | null
  messages: TestResultMessage[]
}>()

const $q = useQuasar()

const filePathShort = computed(() => {
  return props.filePath?.split(/[\\/]/).pop() || 'Неизвестный файл';
});

const copyErrorDetails = (msg: TestResultMessage) => {
  const details = `Тип: ${msg.type}\nСтрока: ${msg.line}${msg.column ? ", Колонка: " + msg.column : ""}\nИсточник: ${msg.source || 'N/A'}\nСообщение: ${msg.message}`;
  copyToClipboard(details)
    .then(() => {
      $q.notify({
        color: 'positive',
        textColor: 'white',
        icon: 'check_circle',
        message: 'Детали ошибки скопированы',
        position: 'top-right',
        timeout: 1500
      });
    })
    .catch(() => {
      $q.notify({
        color: 'negative',
        textColor: 'white',
        icon: 'error',
        message: 'Не удалось скопировать детали',
        position: 'top-right',
        timeout: 1500
      });
    });
};

const msgTypeIcon = (msg: TestResultMessage) => {
  if (msg.type === 'ERROR') return 'error'
  if (msg.type === 'WARNING') return 'warning'
  return 'info'
}

const msgTypeColor = (msg: TestResultMessage) => {
  if (msg.type === 'ERROR') return 'red'
  if (msg.type === 'WARNING') return 'orange'
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