<template>
  <AppLayout>
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-surface-900 dark:text-surface-500 font-semibold text-2xl tracking-tight">
          Department Management
        </h2>
      </div>

      <EditDepartment />

      <DataTable
        :value="departmentStore.departments"
        :loading="departmentStore.loading"
        paginator
        :rows="perPage"
        ref="dt"
        :exportFilename="exportFilename"
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
                placeholder="Keyword Search"
                class="w-72"
              />
            </IconField>

            <div class="flex gap-2">
              <Button label="Export CSV" icon="pi pi-file" @click="exportCSV" />
              <CreateDepartment />
            </div>
          </div>
        </template>

        <Column header="No" field="id" :exportable="true" />
        <Column field="name" header="Name" :exportable="true" />
        <Column field="slug" header="Slug" :exportable="true" />
        <Column field="custom_domain" header="Custom Domain" :exportable="true">
          <template #body="{ data }">
            {{ data.custom_domain || "N/A" }}
          </template>
        </Column>
        <Column field="subscription_type" header="Subscription" :exportable="true" />

        <Column header="Action" :exportable="false">
          <template #body="{ data }">
            <Button
              icon="pi pi-pencil"
              outlined
              rounded
              severity="success"
              class="mr-2"
              :disabled="!can(['update departments'])"
              @click="departmentStore.openEditModal(data)"
              v-tooltip="!can(['update departments']) ? 'You do not have permission' : 'Edit department'"
            />

            <Button
              icon="pi pi-trash"
              outlined
              rounded
              severity="danger"
              :disabled="!can(['delete departments'])"
              @click="confirmDelete(data)"
              v-tooltip="!can(['delete departments']) ? 'You do not have permission' : 'Delete department'"
            />
          </template>
        </Column>
      </DataTable>

      <Dialog
        v-model:visible="deleteDialog"
        :style="{ width: '25rem' }"
        header="Are you sure you want to delete this department?"
        modal
      >
        <div class="flex items-center gap-4">
          <div class="flex flex-col gap-2">
            <span><i class="pi pi-exclamation-triangle text-3xl" /> Delete <b>{{ selectedDepartment?.name }}</b>?</span>
          </div>
        </div>
        <template #footer>
          <Button label="No" text @click="deleteDialog = false" />
          <Button label="Yes" @click="deleteDepartment" />
        </template>
      </Dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import { useDepartmentStore } from "@/Store/department";
import { useToast } from "primevue/usetoast";
import { toastSuccess, toastError } from "@/composables/toastService";
import CreateDepartment from "./Create.vue";
import EditDepartment from "./Edit.vue";

const departmentStore = useDepartmentStore();
const dt = ref(null);
const deleteDialog = ref(false);
const selectedDepartment = ref(null);

const filters = ref({
  global: { value: null, matchMode: "contains" },
});

const today = new Date();
const formattedDate = today.toISOString().split("T")[0];
const exportFilename = `departments-${formattedDate}`;
const perPage = 10;

const toast = useToast();

onMounted(() => {
  departmentStore.fetchDepartments();
});

const exportCSV = () => {
  dt.value?.exportCSV();
};

const confirmDelete = (department) => {
  selectedDepartment.value = department;
  deleteDialog.value = true;
};

const deleteDepartment = async () => {
  if (!selectedDepartment.value) return;

  try {
    const response = await departmentStore.removeDepartment(selectedDepartment.value.id);
    deleteDialog.value = false;
    if (response && (response.status === 200 || response.status === 201)) {
      toastSuccess(toast, "Department Management", response.data.message || "Department deleted successfully");
      await departmentStore.fetchDepartments();
    } else if (response) {
      toastError(toast, "Department Management", response.data.message);
    }
  } catch (err) {
    console.error("Delete failed:", err);
    toastError(toast, "Department Management", "Something went wrong.");
  }
};
</script>
