<template>
  <AppLayout>
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-surface-900 dark:text-surface-500 font-semibold text-2xl tracking-tight">
          Job Role Management
        </h2>
      </div>

      <!-- Modals -->
      <CreateJobRole />
      <EditJobRole />

      <!-- Datatable -->
      <DataTable
        :value="jobRoleStore.jobRoles"
        :loading="jobRoleStore.loading"
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
                placeholder="Search job role"
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
                @click="jobRoleStore.openCreateModal"
                v-if="can(['create job roles'])"
              />
            </div>
          </div>
        </template>

        <Column field="id" header="No" />
        <Column field="name" header="Name" />
        <Column field="description" header="Description" />
        <Column field="job_family.name" header="Job Family">
          <template #body="{ data }">
            {{ data.job_family?.name || "N/A" }}
          </template>
        </Column>

        <Column header="Action" :exportable="false">
          <template #body="{ data }">
            <Button
              icon="pi pi-pencil"
              outlined
              rounded
              severity="success"
              class="mr-2"
              @click="editJobRole(data)"
              :disabled="!can(['update job roles'])"
            />

            <Button
              icon="pi pi-trash"
              outlined
              rounded
              severity="danger"
              @click="confirmDelete(data.id)"
              lockLabel="Deleting..."
              :rearm-key="jobRoleStore.jobRoles.length"
              :disabled="!can(['delete job roles'])"
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
import { useJobRoleStore } from "@/Store/jobRole";
import CreateJobRole from "./Create.vue";
import EditJobRole from "./Edit.vue";
import { useToast } from "primevue/usetoast";
import { toastError, toastSuccess } from "@/composables/toastService";

const jobRoleStore = useJobRoleStore();
const dt = ref(null);
const toast = useToast();

const filters = ref({
  global: { value: null, matchMode: "contains" },
});

onMounted(() => {
  jobRoleStore.fetchJobRoles();
});

const editJobRole = (jobRole) => {
  jobRoleStore.openEditModal(jobRole);
};

const confirmDelete = async (id) => {
  try {
    const response = await jobRoleStore.deleteJobRole(id, toast);
    if (response && response.status === 200) {
      toastSuccess(toast, "Job Role Management", response.data.message);
    } else if (response) {
      toastError(toast, "Job Role Management", response.data.message);
    }
  } catch (err) {
    console.error("Delete failed:", err);
    toastError(toast, "Job Role Management", "Something went wrong.");
  }
};

const exportCSV = () => {
  dt.value.exportCSV();
};
</script>
