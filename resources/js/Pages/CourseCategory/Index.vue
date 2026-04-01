<template>
  <AppLayout>
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-surface-900 dark:text-surface-500 font-semibold text-2xl tracking-tight">
          Course Category Management
        </h2>
      </div>

      <!-- Modals -->
      <CreateCategory />
      <EditCategory />

      <!-- Datatable -->
      <DataTable
        :value="categoryStore.categories"
        :loading="categoryStore.loading"
        paginator
        :rows="10"
        ref="dt"
        v-model:filters="filters"
        filterDisplay="menu"
      >
        <template #header>
          <div class="flex justify-between items-center w-full">
            <IconField>
              <InputIcon>
                <i class="pi pi-search" />
              </InputIcon>
              <InputText
                v-model="filters['global'].value"
                placeholder="Search category"
                class="w-72"
              />
            </IconField>

            <div class="flex gap-2">
              <Button
                label="Export CSV"
                icon="pi pi-file"
                @click="exportCSV"
              />
              <Button
                label="Create"
                icon="pi pi-plus"
                severity="primary"
                @click="categoryStore.openCreateModal"
                v-if="can(['create course categories'])"
              />
            </div>
          </div>
        </template>

        <Column field="id" header="No" />
        <Column field="name" header="Name" />
        <Column field="description" header="Description" />

        <Column header="Action" :exportable="false">
          <template #body="{ data }">
            <Button
              icon="pi pi-pencil"
              outlined
              rounded
              severity="success"
              class="mr-2"
              @click="editCategory(data)"
              :disabled="!can(['update course categories'])"
            />

            <Button
              icon="pi pi-trash"
              outlined
              rounded
              severity="danger"
              @click="confirmDelete(data.id)"
              lockLabel="Deleting..."
              :rearm-key="categoryStore.categories.length"
              :disabled="!can(['delete course categories'])"
            />
          </template>
        </Column>
      </DataTable>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import { useCourseCategoryStore } from "@/Store/courseCategory";
import CreateCategory from "./Create.vue";
import EditCategory from "./Edit.vue";
import { useToast } from "primevue/usetoast";
import { toastError, toastSuccess } from "@/composables/toastService";

const categoryStore = useCourseCategoryStore();
const dt = ref(null);
const toast = useToast();

const filters = ref({
  global: { value: null, matchMode: "contains" },
});

onMounted(() => {
  categoryStore.fetchCourseCategories();
});

const editCategory = (category) => {
  categoryStore.openEditModal(category);
};

const confirmDelete = async (id) => {
  try {
    const response = await categoryStore.deleteCourseCategory(id, toast);
    if (response && response.status === 200) {
      toastSuccess(toast, "Course Category Management", response.data.message);
    } else if (response) {
      toastError(toast, "Course Category Management", response.data.message);
    }
  } catch (err) {
    console.error("Delete failed:", err);
    toastError(toast, "Course Category Management", "Something went wrong.");
  }
};

const exportCSV = () => {
  dt.value.exportCSV();
};
</script>
