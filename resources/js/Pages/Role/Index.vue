<template>
  <AppLayout>
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-surface-900 dark:text-surface-500 font-semibold text-2xl tracking-tight">
          Role Management
        </h2>
      </div>

      <!-- Header: Search + Create Role -->
      <div class="flex justify-between items-center mb-3">
        <IconField>
          <InputIcon>
            <i class="pi pi-search" />
          </InputIcon>
          <InputText
            v-model="filters['global'].value"
            placeholder="Search roles..."
            class="w-72"
          />
        </IconField>

        <CreateRole />
      </div>

      <!-- Roles Table -->
      <DataTable
        :value="roleStore.roles"
        :filters="filters"
        filterDisplay="menu"
        dataKey="id"
        tableStyle="min-width: 40rem"
      >
        <Column field="id" header="ID" sortable />
        <Column field="name" header="Role Name" sortable />

        <Column header="Actions" style="width: 12rem">
          <template #body="slotProps">
            <Button
              icon="pi pi-pencil"
              outlined
              rounded
              class="p-button-sm mr-2"
              @click="openEditDialog(slotProps.data)"
              :disabled="!can(['update roles'])"
              tooltip="Edit Role"
            />

            <Button
              icon="pi pi-trash"
              severity="danger"
              class="p-button-sm"
              rounded
              outlined
              @click="confirmDelete(slotProps.data.id)"
              :disabled="!can(['delete roles'])"
              tooltip="Delete Role"
            />
          </template>
        </Column>
      </DataTable>

      <!-- Confirm Delete Dialog -->
      <Dialog
        v-model:visible="showDeleteDialog"
        header="Confirm Delete"
        modal
        :closable="false"
        :style="{ width: '25rem' }"
      >
        <p>Are you sure you want to delete this role?</p>

        <div class="flex justify-end gap-2 mt-4">
          <Button label="Cancel" severity="secondary" @click="showDeleteDialog = false" />
          <Button label="Delete" severity="danger" @click="deleteRole" />
        </div>
      </Dialog>

      <EditRole />
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";

import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import Button from "primevue/button";

import { useRoleStore } from "@/Store/role";
import { useToast } from "primevue/usetoast";
import { toastSuccess, toastError } from "@/composables/toastService";

import CreateRole from "./Create.vue";
import EditRole from "./Edit.vue";

const roleStore = useRoleStore();
const toast = useToast();

const filters = reactive({
  global: { value: null, matchMode: "contains" },
});

// Delete dialog
const showDeleteDialog = ref(false);
const deletingRoleId = ref(null);

onMounted(async () => {
  await roleStore.fetchRoles();
  await roleStore.fetchAllPermissions();

});

// Open Edit Modal
const openEditDialog = (role) => {
  roleStore.editForm = {
    id: role.id,
    name: role.name,
    permissions: role.permissions.map((p) => p.name),
  };
  roleStore.showEditModal = true;
};

// Delete Confirmation
const confirmDelete = (roleId) => {
  deletingRoleId.value = roleId;
  showDeleteDialog.value = true;
};

// Delete Role
const deleteRole = async () => {
  try {
    await roleStore.deleteRole(deletingRoleId.value);
    toastSuccess(toast, "Role Management", "Role deleted successfully");
  } catch (err) {
    toastError(toast, "Role Management", "Failed to delete role");
  } finally {
    showDeleteDialog.value = false;
    deletingRoleId.value = null;
  }
};
</script>
