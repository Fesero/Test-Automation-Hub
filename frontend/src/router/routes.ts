import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', redirect: '/tests' },
      {
        path: 'tests',
        name: 'tests',
        component: () => import('pages/TestsPage.vue')
      },
      {
        path: 'analytics',
        name: 'analytics',
        component: () => import('pages/AnalyticsPage.vue')
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('pages/SettingsPage.vue')
      },
      {
        path: 'projects',
        name: 'projects',
        component: () => import('pages/ProjectsPage.vue')
      },
      {
        path: 'audit',
        name: 'audit',
        component: () => import('pages/AuditPage.vue')
      },
      {
        path: 'test-types',
        name: 'test-types',
        component: () => import('pages/TestTypesPage.vue')
      }
    ]
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue'),
  },
];

export default routes;
