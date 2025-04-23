<template>
  <q-page padding class="modern-page">
    <div class="row q-col-gutter-md">
      <!-- Test Data Generator -->
      <div class="col-12 col-md-6">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6 q-mb-md">Генератор тестовых данных</div>
            <q-form @submit="generateTestData" class="q-gutter-md">
              <q-select
                v-model="testData.type"
                :options="testDataTypes"
                label="Тип данных"
                outlined
                dense
              />

              <q-input
                v-model="testData.count"
                label="Количество"
                type="number"
                outlined
                dense
                :rules="[val => val > 0 || 'Должно быть больше 0']"
              />

              <q-btn
                color="primary"
                label="Сгенерировать"
                type="submit"
              />
            </q-form>

            <div v-if="generatedData.length" class="q-mt-md">
              <div class="text-subtitle2">Результат:</div>
              <pre class="bg-grey-2 q-pa-sm">{{ JSON.stringify(generatedData, null, 2) }}</pre>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- JSON Formatter -->
      <div class="col-12 col-md-6">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6 q-mb-md">JSON форматтер</div>
            <q-form @submit="formatJSON" class="q-gutter-md">
              <q-input
                v-model="jsonInput"
                label="JSON"
                type="textarea"
                outlined
                dense
                :rules="[val => isValidJSON(val) || 'Некорректный JSON']"
              />

              <div class="row q-gutter-sm">
                <q-btn
                  color="primary"
                  label="Форматировать"
                  @click="formatJSON"
                />
                <q-btn
                  color="secondary"
                  label="Минифицировать"
                  @click="minifyJSON"
                />
              </div>
            </q-form>

            <div v-if="formattedJSON" class="q-mt-md">
              <div class="text-subtitle2">Результат:</div>
              <pre class="bg-grey-2 q-pa-sm">{{ formattedJSON }}</pre>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Base64 Converter -->
      <div class="col-12 col-md-6">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6 q-mb-md">Base64 конвертер</div>
            <q-form class="q-gutter-md">
              <q-input
                v-model="base64Input"
                label="Текст"
                type="textarea"
                outlined
                dense
              />

              <div class="row q-gutter-sm">
                <q-btn
                  color="primary"
                  label="Кодировать"
                  @click="encodeBase64"
                />
                <q-btn
                  color="secondary"
                  label="Декодировать"
                  @click="decodeBase64"
                />
              </div>
            </q-form>

            <div v-if="base64Result" class="q-mt-md">
              <div class="text-subtitle2">Результат:</div>
              <pre class="bg-grey-2 q-pa-sm">{{ base64Result }}</pre>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Hash Generator -->
      <div class="col-12 col-md-6">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6 q-mb-md">Генератор хешей</div>
            <q-form @submit="generateHash" class="q-gutter-md">
              <q-input
                v-model="hashInput"
                label="Текст"
                type="textarea"
                outlined
                dense
              />

              <q-select
                v-model="hashAlgorithm"
                :options="hashAlgorithms"
                label="Алгоритм"
                outlined
                dense
              />

              <q-btn
                color="primary"
                label="Сгенерировать"
                @click="generateHash"
              />
            </q-form>

            <div v-if="hashResult" class="q-mt-md">
              <div class="text-subtitle2">Результат:</div>
              <pre class="bg-grey-2 q-pa-sm">{{ hashResult }}</pre>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Regular Expression Tester -->
      <div class="col-12">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6 q-mb-md">Тестер регулярных выражений</div>
            <q-form @submit="testRegex" class="q-gutter-md">
              <q-input
                v-model="regexPattern"
                label="Регулярное выражение"
                outlined
                dense
                :rules="[val => isValidRegex(val) || 'Некорректное регулярное выражение']"
              />

              <q-input
                v-model="regexTest"
                label="Текст для проверки"
                type="textarea"
                outlined
                dense
              />

              <q-btn
                color="primary"
                label="Проверить"
                @click="testRegex"
              />
            </q-form>

            <div v-if="regexResult" class="q-mt-md">
              <div class="text-subtitle2">Результат:</div>
              <div class="row q-col-gutter-md">
                <div class="col-12 col-md-6">
                  <div class="text-subtitle2">Совпадения:</div>
                  <q-list bordered separator>
                    <q-item v-for="(match, index) in regexResult.matches" :key="index">
                      <q-item-section>{{ match }}</q-item-section>
                    </q-item>
                  </q-list>
                </div>
                <div class="col-12 col-md-6">
                  <div class="text-subtitle2">Группы:</div>
                  <pre class="bg-grey-2 q-pa-sm">{{ JSON.stringify(regexResult.groups, null, 2) }}</pre>
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useQuasar } from 'quasar'

defineOptions({
  name: 'ToolsPage'
})

const $q = useQuasar()

interface TestData {
  id: number
  name: string
  email: string
}

interface RegexResult {
  matches: string[]
  groups: string[]
}

const testDataTypes = ['Пользователи', 'Тесты', 'Ошибки', 'Проекты']
const testData = ref({
  type: 'Пользователи',
  count: 5
})
const generatedData = ref<TestData[]>([])

const jsonInput = ref('')
const formattedJSON = ref('')

const base64Input = ref('')
const base64Result = ref('')

const hashAlgorithms = ['MD5', 'SHA-1', 'SHA-256', 'SHA-512']
const hashAlgorithm = ref('MD5')
const hashInput = ref('')
const hashResult = ref('')

const regexPattern = ref('')
const regexTest = ref('')
const regexResult = ref<RegexResult | null>(null)

const generateTestData = () => {
  // TODO: Implement test data generation
  generatedData.value = [
    { id: 1, name: 'User 1', email: 'user1@example.com' },
    { id: 2, name: 'User 2', email: 'user2@example.com' }
  ]
}

const isValidJSON = (str: string): boolean => {
  try {
    JSON.parse(str)
    return true
  } catch {
    return false
  }
}

const formatJSON = () => {
  try {
    const parsed = JSON.parse(jsonInput.value)
    formattedJSON.value = JSON.stringify(parsed, null, 2)
  } catch {
    $q.notify({
      color: 'negative',
      message: 'Некорректный JSON'
    })
  }
}

const minifyJSON = () => {
  try {
    const parsed = JSON.parse(jsonInput.value)
    formattedJSON.value = JSON.stringify(parsed)
  } catch {
    $q.notify({
      color: 'negative',
      message: 'Некорректный JSON'
    })
  }
}

const encodeBase64 = () => {
  try {
    base64Result.value = btoa(base64Input.value)
  } catch {
    $q.notify({
      color: 'negative',
      message: 'Ошибка кодирования'
    })
  }
}

const decodeBase64 = () => {
  try {
    base64Result.value = atob(base64Input.value)
  } catch {
    $q.notify({
      color: 'negative',
      message: 'Ошибка декодирования'
    })
  }
}

const generateHash = () => {
  // TODO: Implement hash generation
  hashResult.value = 'd41d8cd98f00b204e9800998ecf8427e' // Sample MD5 hash
}

const isValidRegex = (pattern: string): boolean => {
  try {
    new RegExp(pattern)
    return true
  } catch {
    return false
  }
}

const testRegex = () => {
  try {
    const regex = new RegExp(regexPattern.value, 'g')
    const matches = regexTest.value.match(regex) || []
    const groups = regex.exec(regexTest.value) || []
    
    regexResult.value = {
      matches,
      groups: groups.slice(1)
    }
  } catch {
    $q.notify({
      color: 'negative',
      message: 'Некорректное регулярное выражение'
    })
  }
}
</script>

<style scoped lang="scss">
.modern-page {
  background: #f8f9fa;
  min-height: 100vh;
  padding: 1.5rem;
}
</style> 