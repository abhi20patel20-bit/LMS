<template>
  <AppLayout>
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-surface-900 dark:text-surface-500 font-semibold text-2xl tracking-tight">
          Course Management
        </h2>
      </div>

      <!-- Modals -->
      <CreateCourse />
      <EditCourse />

      <!-- Datatable -->
      <DataTable
        :value="courseStore.courses"
        :loading="courseStore.loading"
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
                placeholder="Search course"
                class="w-72"
              />
            </IconField>

            <div class="flex gap-2">
              <SingleClickButton
                label="Export CSV"
                icon="pi pi-file"
                @click="exportCSV"
              />
              <SingleClickButton
                label="Create"
                icon="pi pi-plus"
                severity="primary"
                @click="courseStore.openCreateModal"
                v-if="can(['create courses'])"
              />
            </div>
          </div>
        </template>

        <Column field="id" header="No" />
        <Column field="title" header="Title" />
        <Column field="description" header="Description" />
        <Column field="price" header="Price" />
        <Column field="status" header="Status" />
        <Column header="Category">
          <template #body="{ data }">
            {{ data.category?.name || "N/A" }}
          </template>
        </Column>
        <Column field="course_type" header="Type" />
        <Column field="duration" header="Duration" />
        <Column header="Providers">
          <template #body="{ data }">
            {{ data.providers?.map((provider) => provider.name).join(", ") || "N/A" }}
          </template>
        </Column>

        <Column header="Action" :exportable="false">
          <template #body="{ data }">
            <SingleClickButton
              icon="pi pi-pencil"
              outlined
              rounded
              severity="success"
              class="mr-2"
              @click="editCourse(data)"
              :disabled="!can(['update courses'])"
            />

            <SingleClickButton
              icon="pi pi-trash"
              outlined
              rounded
              severity="danger"
              @click="confirmDelete(data.id)"
              lockLabel="Deleting..."
              :rearm-key="courseStore.courses.length"
              :disabled="!can(['delete courses'])"
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
import { useCourseStore } from "@/Store/course";
import CreateCourse from "./Create.vue";
import EditCourse from "./Edit.vue";
import { toastError, toastSuccess } from "@/composables/toastService";
import SingleClickButton from "@/Components/SingleClickButton.vue";

const courseStore = useCourseStore();
const dt = ref(null);

const filters = ref({
  global: { value: null, matchMode: "contains" },
});

onMounted(() => {
  courseStore.fetchCourses();
});

const editCourse = (course) => {
  courseStore.openEditModal(course);
};

const confirmDelete = async (id) => {

  try {
    const response = await courseStore.deleteCourse(id); // await the async function
    if (response && response.status === 201) {
        courseStore.closeCreate();
        courseStore.fetchCourses();
        toastSuccess(toast, "Course Management", response.data.message);
    } else if (response) {
        toastError(toast, "Course Management", response.data.message);
    }

    userStore.showEditModal = false;

  } catch (err) {
    console.error("Submit failed:", err);
    toastError(toast, "User Management", "Something went wrong.");
  }
};


const exportCSV = () => {
  dt.value.exportCSV();
};
</script>
