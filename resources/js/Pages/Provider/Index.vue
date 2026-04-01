<template>
  <AppLayout>
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-surface-900 dark:text-surface-500 font-semibold text-2xl tracking-tight">
          Provider Management
        </h2>
      </div>

      <!-- Modals -->
      <CreateProvider />
      <EditProvider />

      <!-- Datatable -->
      <DataTable
        :value="providerStore.providers"
        :loading="providerStore.loading"
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
                placeholder="Search provider"
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
                @click="providerStore.openCreateModal"
                v-if="can(['create providers'])"
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
              @click="editProvider(data)"
              :disabled="!can(['update providers'])"
            />

            <Button
              icon="pi pi-trash"
              outlined
              rounded
              severity="danger"
              @click="confirmDelete(data.id)"
              lockLabel="Deleting..."
              :rearm-key="providerStore.providers.length"
              :disabled="!can(['delete providers'])"
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
import { useProviderStore } from "@/Store/provider";
import CreateProvider from "./Create.vue";
import EditProvider from "./Edit.vue";
import { useToast } from "primevue/usetoast";
import { toastError, toastSuccess } from "@/composables/toastService";

const providerStore = useProviderStore();
const dt = ref(null);
const toast = useToast();

const filters = ref({
  global: { value: null, matchMode: "contains" },
});

onMounted(() => {
  providerStore.fetchProviders();
});

const editProvider = (provider) => {
  providerStore.openEditModal(provider);
};

const confirmDelete = async (id) => {
  try {
    const response = await providerStore.deleteProvider(id, toast);
    if (response && response.status === 200) {
      toastSuccess(toast, "Provider Management", response.data.message);
    } else if (response) {
      toastError(toast, "Provider Management", response.data.message);
    }
  } catch (err) {
    console.error("Delete failed:", err);
    toastError(toast, "Provider Management", "Something went wrong.");
  }
};

const exportCSV = () => {
  dt.value.exportCSV();
};
</script>
