<template>
  <q-page padding class="modern-page">
    <div class="row q-col-gutter-md">
      <!-- General Settings -->
      <div class="col-12 col-md-6">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Общие настройки</div>
            <q-form @submit="onSubmit" class="q-gutter-md">
              <q-input
                v-model="settings.language"
                label="Язык интерфейса"
                :options="['Русский', 'English']"
                outlined
                dense
                class="modern-input"
              />

              <q-input
                v-model="settings.theme"
                label="Тема"
                :options="['Светлая', 'Темная']"
                outlined
                dense
                class="modern-input"
              />

              <q-input
                v-model="settings.timezone"
                label="Часовой пояс"
                outlined
                dense
                class="modern-input"
              />

              <q-toggle
                v-model="settings.autoRefresh"
                label="Автоматическое обновление"
                color="primary"
                class="modern-toggle"
              />

              <q-input
                v-model="settings.refreshInterval"
                label="Интервал обновления (секунды)"
                type="number"
                outlined
                dense
                :disable="!settings.autoRefresh"
                class="modern-input"
              />
            </q-form>
          </q-card-section>
        </q-card>
      </div>

      <!-- Notification Settings -->
      <div class="col-12 col-md-6">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Настройки уведомлений</div>
            <q-form @submit="onSubmit" class="q-gutter-md">
              <q-toggle
                v-model="settings.notifications.email"
                label="Email уведомления"
                color="primary"
                class="modern-toggle"
              />

              <q-input
                v-model="settings.notifications.emailAddress"
                label="Email адрес"
                type="email"
                outlined
                dense
                :disable="!settings.notifications.email"
                class="modern-input"
              />

              <q-toggle
                v-model="settings.notifications.desktop"
                label="Десктопные уведомления"
                color="primary"
                class="modern-toggle"
              />

              <q-toggle
                v-model="settings.notifications.sound"
                label="Звуковые уведомления"
                color="primary"
                :disable="!settings.notifications.desktop"
                class="modern-toggle"
              />
            </q-form>
          </q-card-section>
        </q-card>
      </div>

      <!-- Integration Settings -->
      <div class="col-12 col-md-6">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Настройки интеграций</div>
            <q-form @submit="onSubmit" class="q-gutter-md">
              <q-input
                v-model="settings.integrations.jiraUrl"
                label="JIRA URL"
                outlined
                dense
                class="modern-input"
              />

              <q-input
                v-model="settings.integrations.jiraToken"
                label="JIRA API токен"
                type="password"
                outlined
                dense
                class="modern-input"
              />

              <q-input
                v-model="settings.integrations.githubToken"
                label="GitHub токен"
                type="password"
                outlined
                dense
                class="modern-input"
              />

              <q-toggle
                v-model="settings.integrations.autoSync"
                label="Автоматическая синхронизация"
                color="primary"
                class="modern-toggle"
              />
            </q-form>
          </q-card-section>
        </q-card>
      </div>

      <!-- Analysis Settings -->
      <div class="col-12 col-md-6">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Настройки анализа</div>
            <q-form @submit="onSubmit" class="q-gutter-md">
              <q-input
                v-model="settings.analysis.maxFileSize"
                label="Максимальный размер файла (MB)"
                type="number"
                outlined
                dense
                class="modern-input"
              />

              <q-input
                v-model="settings.analysis.excludedPaths"
                label="Исключенные пути"
                outlined
                dense
                hint="Разделяйте пути запятыми"
                class="modern-input"
              />

              <q-toggle
                v-model="settings.analysis.autoFix"
                label="Автоматическое исправление"
                color="primary"
                class="modern-toggle"
              />

              <q-toggle
                v-model="settings.analysis.generateReport"
                label="Генерация отчета"
                color="primary"
                class="modern-toggle"
              />
            </q-form>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Save Button -->
    <div class="row justify-end q-mt-md">
      <q-btn
        color="primary"
        label="Сохранить"
        @click="onSubmit"
        class="premium-btn"
      />
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useQuasar } from 'quasar'

defineOptions({
  name: 'SettingsPage'
})

const $q = useQuasar()

const settings = ref({
  language: 'Русский',
  theme: 'Светлая',
  timezone: 'UTC+3',
  autoRefresh: true,
  refreshInterval: 30,
  notifications: {
    email: false,
    emailAddress: '',
    desktop: true,
    sound: true
  },
  integrations: {
    jiraUrl: '',
    jiraToken: '',
    githubToken: '',
    autoSync: false
  },
  analysis: {
    maxFileSize: 10,
    excludedPaths: '',
    autoFix: false,
    generateReport: true
  }
})

const onSubmit = () => {
  // TODO: Implement settings save logic
  $q.notify({
    color: 'positive',
    message: 'Настройки сохранены'
  })
}
</script>

<style scoped lang="scss">
.modern-page {
  background: $bg-gradient-light;
  min-height: 100vh;
}

.modern-card {
  border-radius: $border-radius;
  box-shadow: $card-shadow;
  transition: $transition-default;
  overflow: hidden;
  background-color: $background-white;
  border: 1px solid $border-color;
  backdrop-filter: blur(10px);
  
  &:hover {
    box-shadow: $shadow-lg;
    transform: translateY(-2px);
  }
}

.modern-input {
  .q-field {
    &__control {
      border-radius: 8px;
      background: $background-white;
      border: 1px solid $border-color;
      transition: $transition-default;

      &:hover {
        border-color: $primary;
      }

      &--focused {
        border-color: $primary;
        box-shadow: $neon-shadow;
      }
    }

    &__label {
      color: $text-secondary;
      font-weight: 500;
    }
  }
}

.modern-toggle {
  .q-toggle {
    &__track {
      background: $border-color;
    }

    &__thumb {
      background: $background-white;
      box-shadow: $shadow-sm;
    }

    &--checked {
      .q-toggle__track {
        background: $primary;
      }

      .q-toggle__thumb {
        background: $primary;
      }
    }
  }
}

.modern-checkbox {
  .q-checkbox {
    &__track {
      background: $border-color;
    }

    &__thumb {
      background: $background-white;
      box-shadow: $shadow-sm;
    }

    &--checked {
      .q-checkbox__track {
        background: $primary;
      }

      .q-checkbox__thumb {
        background: $primary;
      }
    }
  }
}

.notification-options {
  border-left: 2px solid $border-color;
  padding-left: 16px;
  margin-top: 8px;
}

.premium-btn {
  background: $gradient-primary;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 12px 24px;
  font-weight: 600;
  transition: all 0.3s ease;
  
  &:hover {
    transform: translateY(-1px);
    box-shadow: $neon-shadow;
  }
  
  &:active {
    transform: translateY(0);
  }
}
</style> 