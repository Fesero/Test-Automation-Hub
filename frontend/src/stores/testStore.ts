import { defineStore, acceptHMRUpdate } from 'pinia';
import { apiClient } from 'src/boot/axios';
import type {
  Project,
  Test,
  TestResultMessage,
  TestResultMetrics,
} from 'src/types/test.type';
import { useQuasar } from 'quasar';

function safeJsonParse<T>(jsonString: string | null | undefined): T | null {
  if (!jsonString) {
    return null;
  }
  try {
    return JSON.parse(jsonString) as T;
  } catch (e) {
    console.error('Failed to parse JSON:', e, jsonString);
    return null;
  }
}

export const useTestStore = defineStore('test', {
  state: () => ({
    projects: [] as Project[],
    selectedProjectId: null as number | null,
    tests: [] as Test[],
    loading: false,
    loadingProjects: false,
    error: null as string | null,
  }),
  actions: {
    async fetchProjects() {
      const $q = useQuasar();
      this.loadingProjects = true;
      this.error = null;
      try {
        console.log('Fetching projects...');
        const res = await apiClient.get<Project[]>('/projects');
        console.log('Projects Response:', res.data);
        this.projects = res.data;

      } catch (error) {
        console.error('Error fetching projects:', error);
        this.error = error instanceof Error ? error.message : 'Unknown error';
        $q.notify({
          message: `Ошибка загрузки проектов: ${this.error}`,
          color: 'negative',
          icon: 'error',
        });
      } finally {
        this.loadingProjects = false;
      }
    },

    async fetchTests(projectId: number | null) {
      if (!projectId) {
        this.tests = [];
        return;
      }

      const $q = useQuasar();
      this.loading = true;
      this.error = null;
      this.tests = [];

      try {
        console.log(`Fetching tests for project ${projectId}...`);
        const res = await apiClient.get<Test[]>('/tests', {
          params: { project_id: projectId },
        });
        console.log('Tests Response:', res.data);

        const processedTests = res.data.map((test) => {
          test.results = test.results.map((result) => {
            result.parsedOutput = safeJsonParse<TestResultMessage[]>(
              result.output
            );
            result.parsedMetrics = safeJsonParse<TestResultMetrics>(
              result.metrics
            );
            return result;
          });
          return test;
        });

        this.tests = processedTests;

        if (this.tests.length === 0) {
          console.log('No tests found for this project');
        }
      } catch (error) {
        console.error(`Error fetching tests for project ${projectId}:`, error);
        this.error = error instanceof Error ? error.message : 'Unknown error';
        $q.notify({
          message: `Ошибка загрузки тестов: ${this.error}`,
          color: 'negative',
          icon: 'error',
        });
      } finally {
        this.loading = false;
      }
    },

    selectProject(projectId: number | null) {
      console.log('Selecting project:', projectId);
      this.selectedProjectId = projectId;
      void this.fetchTests(projectId);
    },
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useTestStore, import.meta.hot))
}
