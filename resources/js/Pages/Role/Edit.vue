<template>
  <Dialog
    v-model:visible="roleStore.showEditModal"
    position="top"
    modal
    header="Edit Role"
    :style="{ width: '40rem' }"
    :closable="false"
  >
    <div class="flex flex-col gap-4">
      <!-- Role Name -->
      <div class="flex flex-col gap-2">
        <label>Role Name</label>
        <InputText v-model="roleStore.editForm.name" placeholder="Role Name" />
        <small v-if="roleStore.errors.name" class="text-red-500">{{ roleStore.errors.name }}</small>
      </div>

      <!-- Permissions -->
      <div class="flex flex-col gap-2">
        <label>Permissions</label>

        <!-- Select All -->
        <div class="flex items-center gap-2 mb-2">
          <Checkbox v-model="multipleSelect" @change="toggleSelectAll" :binary="true" inputId="check_all_edit" />
          <label for="check_all_edit">Check All</label>
        </div>

        <!-- Permission checkboxes -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
          <div v-for="perm in roleStore.permissions" :key="perm.id" class="flex items-center gap-2">
            <Checkbox
              v-model="roleStore.editForm.permissions"
              :value="perm.name"
              :inputId="'perm_edit_' + perm.id"
              @change="toggleSingle"
            />
            <label :for="'perm_edit_' + perm.id">{{ perm.name }}</label>
          </div>
        </div>

        <small v-if="roleStore.errors.permissions" class="text-red-500">{{ roleStore.errors.permissions }}</small>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-2">
        <Button label="Cancel" severity="secondary" @click="closeDialog" />
        <Button label="Update" @click="submit" />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Checkbox from "primevue/checkbox";
import Select from "primevue/select";
import { useRoleStore } from "@/Store/role";
import { useToast } from "primevue/usetoast";
import { toastSuccess, toastError } from "@/composables/toastService";

const roleStore = useRoleStore();
const toast = useToast();
const multipleSelect = ref(false);

// Watch for opening the edit modal to prefill Select All
watch(
  () => roleStore.showEditModal,
  (val) => {
    if (val) {
      multipleSelect.value =
        roleStore.editForm.permissions.length === roleStore.permissions.length;
    }
  }
);

const closeDialog = () => {
  roleStore.showEditModal = false;
  roleStore.resetEditForm();
};

const toggleSelectAll = () => {
  roleStore.editForm.permissions = multipleSelect.value
    ? roleStore.permissions.map((p) => p.name)
    : [];
};

const toggleSingle = () => {
  multipleSelect.value =
    roleStore.editForm.permissions.length === roleStore.permissions.length;
};

const submit = async () => {
  roleStore.resetErrors();

  if (!roleStore.editForm.name.trim()) {
    roleStore.errors.name = "Role name is required";
    return;
  }
  if (!roleStore.editForm.permissions.length) {
    roleStore.errors.permissions = "Select at least one permission";
    return;
  }

  try {
    const response = await roleStore.updateRole();
    if (response && response.status >= 200 && response.status < 300) {
      toastSuccess(toast, "Role Management", response.data.message || "Role updated");
      await roleStore.fetchRoles();
      closeDialog();
      return;
    }

    if (response?.status === 422) {
      const message = Object.values(roleStore.errors || {})[0] || "Validation failed.";
      toastError(toast, "Role Management", message);
      return;
    }

    if (response) {
      toastError(toast, "Role Management", response.data?.message || "Role update failed.");
    }
  } catch (err) {
    console.error("Update failed:", err);
    toastError(toast, "Role Management", "Something went wrong.");
  }
};
</script>
