<template>
  <q-page padding class="page-padding">
    <div class="row items-center justify-between q-mb-lg">
      <div class="text-h4 page-title">Проекты</div>
      <q-btn
        color="primary"
        icon="add"
        label="Новый проект"
        unelevated 
        @click="openNewProjectDialog"
      />
    </div>

    <!-- Loading Indicator -->
    <div v-if="loadingProjects && projects.length === 0" class="flex flex-center q-my-xl">
      <q-spinner-dots color="primary" size="40px" />
    </div>

    <!-- Error Message -->
     <div v-else-if="error" class="text-center text-negative q-my-lg">
      <q-icon name="error_outline" size="md" class="q-mr-sm" />
      {{ error }}
    </div>

    <!-- Project Cards Grid -->
    <div v-else-if="projects.length > 0" class="row q-col-gutter-lg">
      <div v-for="project in projects" :key="project.id" class="col-12 col-sm-6 col-md-4 col-lg-3">
        <q-card class="project-card cursor-pointer" flat bordered @click="goToProjectTests(project.id)">
          <q-card-section class="project-card__header">
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="text-h6 ellipsis project-card__title">{{ project.name }}</div>
                 <div v-if="project.url" class="text-caption text-secondary ellipsis q-mt-xs">
                     <q-icon name="link" size="xs" class="q-mr-xs"/>
                     <a :href="project.url" target="_blank" @click.stop class="text-inherit">{{ project.url }}</a>
                 </div>
              </div>
              <div class="col-auto">
                 <q-btn flat round icon="more_vert" text-color="secondary">
                   <q-menu anchor="bottom right" self="top right" class="dialog-menu">
                     <q-list dense style="min-width: 150px">
                       <q-item clickable v-close-popup @click.stop="editProject(project)">
                         <q-item-section avatar><q-icon name="edit" size="xs"/></q-item-section>
                         <q-item-section>Редактировать</q-item-section>
                       </q-item>
                       <q-item clickable v-close-popup @click.stop="confirmDeleteProject(project)">
                         <q-item-section avatar><q-icon name="delete" size="xs"/></q-item-section>
                         <q-item-section class="text-negative">Удалить</q-item-section>
                       </q-item>
                     </q-list>
                   </q-menu>
                 </q-btn>
               </div>
             </div>
          </q-card-section>

          <q-separator />

          <q-card-section class="project-card__stats">
             <div class="row items-center q-gutter-sm">
                <q-icon name="assignment" color="secondary"/>
                 <span class="text-caption text-secondary">Тестов: {{ project.tests_count ?? 0 }}</span>
                 <span class="q-ml-auto">
                     <q-badge 
                        v-if="project.last_test_status" 
                        :class="`status-badge status-badge--${project.last_test_status.toLowerCase()}`"
                     >
                         {{ project.last_test_status }}
                     </q-badge>
                     <q-badge v-else class="status-badge status-badge--default">
                         Нет данных
                     </q-badge>
                 </span>
             </div>
          </q-card-section>

           <q-card-actions align="right">
                <q-btn flat color="primary" label="К тестам" @click.stop="goToProjectTests(project.id)" />
            </q-card-actions>
        </q-card>
      </div>
    </div>

     <!-- No Projects Message -->
     <div v-else class="text-center text-grey q-my-xl">
        <q-icon name="inbox" size="lg" />
        <div class="text-h6 q-mt-md">Проектов пока нет</div>
        <q-btn flat color="primary" label="Создать первый проект" @click="openNewProjectDialog" class="q-mt-sm" />
     </div>

    <!-- New/Edit Project Dialog -->
    <q-dialog v-model="showProjectDialog" persistent>
      <q-card class="dialog-card" style="min-width: 400px">
        <q-card-section class="dialog-header">
          <div class="text-h6">{{ isEditMode ? 'Редактировать проект' : 'Новый проект' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-form @submit="onSubmitProject" class="q-gutter-md">
            <q-input
              v-model="projectForm.name"
              label="Название проекта *"
              :rules="[val => !!val || 'Название обязательно']"
              outlined dense autofocus dark
            />
            <q-input
              v-model="projectForm.url"
              label="URL проекта (необязательно)"
              outlined dense type="url" dark
            />
             <!-- Add more fields if needed -->
          </q-form>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Отмена" color="primary" v-close-popup />
          <q-btn flat :label="isEditMode ? 'Сохранить' : 'Создать'" color="primary" @click="onSubmitProject" :loading="submittingProject" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Delete Confirmation Dialog -->
    <q-dialog v-model="showDeleteConfirmDialog" persistent>
        <q-card class="dialog-card">
            <q-card-section class="row items-center dialog-warning-header">
                 <q-icon name="warning" color="negative" size="md" class="q-mr-md"/>
                 <span class="text-body1">Вы уверены, что хотите удалить проект "{{ projectToDelete?.name }}"?</span>
            </q-card-section>
             <q-card-actions align="right">
                 <q-btn flat label="Отмена" color="primary" v-close-popup />
                 <q-btn flat label="Удалить" color="negative" @click="deleteProjectConfirmed" :loading="deletingProject" />
             </q-card-actions>
        </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { storeToRefs } from 'pinia';
import { useTestStore } from 'stores/testStore';
import type { Project } from 'src/types/test.type';

defineOptions({
  name: 'ProjectsPage',
});

const router = useRouter();
const $q = useQuasar();
const testStore = useTestStore();

const { projects, loading: loadingProjects, error } = storeToRefs(testStore);

const showProjectDialog = ref(false);
const showDeleteConfirmDialog = ref(false);
const isEditMode = ref(false);
const submittingProject = ref(false);
const deletingProject = ref(false);
const projectForm = ref<Partial<Project>>({ name: '', url: '' });
const projectToDelete = ref<Project | null>(null);

onMounted(() => {
  void testStore.fetchProjects();
});

const openNewProjectDialog = () => {
    isEditMode.value = false;
    projectForm.value = { name: '', url: '' };
    showProjectDialog.value = true;
};

const editProject = (project: Project) => {
    isEditMode.value = true;
    projectForm.value = { ...project }; // Copy project data to form
    showProjectDialog.value = true;
};

const confirmDeleteProject = (project: Project) => {
    projectToDelete.value = project;
    showDeleteConfirmDialog.value = true;
};

const onSubmitProject = async () => {
  submittingProject.value = true;
  try {
    if (isEditMode.value && projectForm.value.id) {
      // Call update action (needs implementation in store)
      // await testStore.updateProject(projectForm.value);
      $q.notify({ type: 'positive', message: 'Проект обновлен (заглушка)' }); // Placeholder
    } else {
      // Call create action (needs implementation in store)
      // await testStore.createProject({ name: projectForm.value.name || '', url: projectForm.value.url || null });
      $q.notify({ type: 'positive', message: 'Проект создан (заглушка)' }); // Placeholder
       await testStore.fetchProjects(); // Refresh list after creation
    }
    showProjectDialog.value = false;
  } catch (err) {
    console.error('Error submitting project:', err);
    $q.notify({ type: 'negative', message: `Ошибка: ${err instanceof Error ? err.message : 'Unknown error'}` });
  } finally {
    submittingProject.value = false;
  }
};

const deleteProjectConfirmed = async () => {
    if (!projectToDelete.value) return;
    deletingProject.value = true;
    try {
        // Call delete action (needs implementation in store)
        // await testStore.deleteProject(projectToDelete.value.id);
        $q.notify({ type: 'info', message: `Проект "${projectToDelete.value.name}" удален (заглушка)` }); // Placeholder
        await testStore.fetchProjects(); // Refresh the list
        showDeleteConfirmDialog.value = false;
        projectToDelete.value = null;
    } catch (err) {
        console.error('Error deleting project:', err);
        $q.notify({ type: 'negative', message: `Ошибка удаления: ${err instanceof Error ? err.message : 'Unknown error'}` });
    } finally {
        deletingProject.value = false;
    }
};

const goToProjectTests = (projectId: number) => {
  testStore.selectProject(projectId);
  void router.push('/tests');
};

</script>

<style scoped lang="scss">
.page-padding {
  padding: 24px;
}

.page-title {
  color: $text-primary; 
  font-weight: 600;
}

.project-card {
  background-color: $surface-background; 
  border: 1px solid $separator-color;
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  height: 100%; // Ensure cards have equal height if needed in a row
  display: flex;
  flex-direction: column;

  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
    border-color: $separator-dark-color;
  }

  &__header {
    padding-bottom: 12px;
  }

  &__title {
    color: $text-primary;
    font-weight: 500;
  }

  &__stats {
    padding-top: 12px;
    margin-top: auto; // Push stats and actions to the bottom
  }
}

// Dialog styling
.dialog-card {
  background-color: $surface-background; 
  color: $text-primary;
  border: 1px solid $separator-dark-color;
  box-shadow: $card-box-shadow;
}

.dialog-header {
  display: flex;
  align-items: center;
  background-color: lighten($surface-background, 5%);
  border-bottom: 1px solid $separator-color;
  padding: 12px 16px;
}

.dialog-warning-header {
   background-color: rgba($negative, 0.1);
   color: $negative;
   border-bottom: 1px solid $negative;
}

.dialog-menu {
  .q-list {
    background-color: lighten($surface-background, 5%) !important;
    border: 1px solid $separator-dark-color;
  }
}

// Adjust input field styles for dark theme within dialogs
.q-dialog {
  .q-field--outlined .q-field__control {
    background-color: darken($surface-background, 5%);
  }
}

.text-inherit {
  color: inherit; 
  text-decoration: none;
  &:hover {
    text-decoration: underline;
  }
}

</style>