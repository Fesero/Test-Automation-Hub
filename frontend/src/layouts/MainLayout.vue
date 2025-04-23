<template>
  <q-layout view="lHh Lpr fFf">
    <q-header class="bg-dark text-white" height-hint="64">
      <q-toolbar class="header-toolbar">
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="toggleLeftDrawer"
          class="q-mr-sm"
        />

        <q-toolbar-title class="toolbar-title">
          <q-avatar class="q-mr-sm">
            <img src="icons/favicon-128x128.png">
          </q-avatar>
          Test Automation Hub
        </q-toolbar-title>

        <q-space />

        <q-btn-dropdown flat icon="person" dropdown-icon="expand_more" class="profile-dropdown">
          <q-list class="bg-dark-alt text-white">
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
      :width="260"
      :breakpoint="700"
      class="sidebar-drawer bg-dark-alt"
    >
      <q-scroll-area class="fit">
        <q-list padding class="nav-list">
          <q-item-label header class="nav-header">Navigation</q-item-label>

          <q-item
            v-for="link in links"
            :key="link.title"
            :to="link.link"
            clickable
            v-ripple
            :active="$route.path.startsWith(link.link)" 
            active-class="nav-item--active"
            class="nav-item"
          >
            <q-item-section avatar class="nav-item-icon">
              <q-icon :name="link.icon" />
            </q-item-section>

            <q-item-section class="nav-item-label">
              {{ link.title }}
            </q-item-section>
          </q-item>
        </q-list>
      </q-scroll-area>
    </q-drawer>

    <q-page-container class="page-container">
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

<style lang="scss" scoped>
.q-header {
  border-bottom: 1px solid $separator-color;
}

.header-toolbar {
  padding: 0 24px;
  min-height: 64px;
}

.toolbar-title {
  font-weight: 600;
  display: flex;
  align-items: center;
  .q-avatar {
    width: 32px;
    height: 32px;
  }
}

.profile-dropdown {
  .q-icon {
    font-size: 24px;
  }
}

.sidebar-drawer {
  background-color: $dark;
  color: $text-primary;
  border-right: 1px solid $separator-color;
}

.nav-list {
  padding-top: 16px;
}

.nav-header {
  color: $text-secondary;
  font-size: 0.75rem;
  text-transform: uppercase;
  font-weight: 600;
  margin-left: 16px;
  margin-bottom: 8px;
}

.nav-item {
  color: $text-secondary;
  margin: 4px 16px;
  padding: 10px 16px;
  border-radius: 6px;
  transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;

  .nav-item-icon {
    min-width: 36px;
    font-size: 20px;
  }

  &:hover {
    background-color: rgba($primary, 0.1);
    color: $text-primary;
  }

  &.nav-item--active {
    background-color: $primary;
    color: white;
    font-weight: 500;

    .nav-item-icon {
      color: white;
    }
  }
}

.page-container {
  background-color: $body-background;
}

.q-menu {
  .q-list {
    background-color: $surface-background !important;
    color: $text-primary !important;
    border: 1px solid $separator-dark-color;
  }
  .q-item:hover {
      background-color: $list-item-hover-background !important;
  }
}
</style>
