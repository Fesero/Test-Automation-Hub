<template>
  <q-layout view="hHh lpR fFf">
    <q-header elevated class="bg-gradient-primary text-white">
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="toggleLeftDrawer"
        />

        <q-toolbar-title class="text-h6">
          Test Automation Hub
        </q-toolbar-title>

        <q-btn-dropdown flat icon="person">
          <q-list>
            <q-item clickable v-close-popup @click="handleProfile">
              <q-item-section>
                <q-item-label>Профиль</q-item-label>
                <q-item-label caption>Настройки аккаунта</q-item-label>
              </q-item-section>
            </q-item>

            <q-item clickable v-close-popup @click="handleLogout">
              <q-item-section>
                <q-item-label>Выйти</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      bordered
      class="bg-white"
      :width="250"
    >
      <q-list padding>
        <q-item-label header class="text-weight-bold text-primary">Навигация</q-item-label>

        <q-item
          v-for="link in links"
          :key="link.title"
          :to="link.link"
          clickable
          v-ripple
          :active="$route.path === link.link"
          class="nav-item"
        >
          <q-item-section avatar>
            <q-icon :name="link.icon" :color="$route.path === link.link ? 'white' : 'primary'" />
          </q-item-section>

          <q-item-section>
            {{ link.title }}
          </q-item-section>
        </q-item>
      </q-list>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

defineOptions({
  name: 'MainLayout'
})

const router = useRouter()
const leftDrawerOpen = ref(false)

interface NavLink {
  title: string
  icon: string
  link: string
}

const links: NavLink[] = [
  {
    title: 'Тесты',
    icon: 'bug_report',
    link: '/tests'
  },
  {
    title: 'Проекты',
    icon: 'folder',
    link: '/projects'
  },
  {
    title: 'Аналитика',
    icon: 'analytics',
    link: '/analytics'
  },
  {
    title: 'Аудит',
    icon: 'history',
    link: '/audit'
  },
  {
    title: 'Типы тестов',
    icon: 'science',
    link: '/test-types'
  },
  {
    title: 'Настройки',
    icon: 'settings',
    link: '/settings'
  }
]

const toggleLeftDrawer = () => {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

const handleProfile = async () => {
  await router.push('/settings')
}

const handleLogout = async () => {
  // TODO: Implement logout logic
  await router.push('/login')
}
</script>

<style lang="scss">
.q-drawer {
  .nav-item {
    border-radius: 0 24px 24px 0;
    margin-right: 12px;
    margin-bottom: 4px;
    transition: all 0.3s ease;

    &:hover {
      background: rgba(var(--q-primary), 0.05);
    }

    &.q-router-link-active {
      background: var(--q-primary);
      color: white;
      box-shadow: $neon-shadow;
    }
  }
}

.q-header {
  background: $gradient-primary;
  box-shadow: $shadow-lg;
}

.q-toolbar {
  min-height: 64px;
}

.q-toolbar-title {
  font-weight: 600;
  letter-spacing: -0.5px;
}

.q-btn-dropdown {
  .q-icon {
    font-size: 24px;
  }
}
</style>

    font-size: 24px;
