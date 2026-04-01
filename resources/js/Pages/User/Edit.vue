<template>
  <Dialog
    v-model:visible="userStore.showEditModal"
    header="Update User"
    modal
    :closable="false"
    :style="{ width: '30rem' }"
  >
    <div class="flex flex-col gap-4">
      <!-- Name -->
      <div class="flex flex-col gap-2">
        <label>Name</label>
        <InputText v-model="userStore.form.name" placeholder="Name" />
        <small v-if="userStore.errors.name" class="text-red-500">{{ userStore.errors.name }}</small>
      </div>

      <!-- Email -->
      <div class="flex flex-col gap-2">
        <label>Email</label>
        <InputText v-model="userStore.form.email" placeholder="Email" />
        <small v-if="userStore.errors.email" class="text-red-500">{{ userStore.errors.email }}</small>
      </div>

      <!-- Company -->
      <div class="flex flex-col gap-2">
        <label>Company</label>
        <Select
          v-model="userStore.form.company_id"
          :options="userStore.companies"
          optionValue="id"
          optionLabel="name"
          placeholder="Select Company"
          @change="userStore.getRoles(userStore.form.company_id)"
        />
        <small v-if="userStore.errors.company_id" class="text-red-500">{{ userStore.errors.company_id }}</small>
      </div>

      <!-- Department -->
      <div class="flex flex-col gap-2">
        <label>Department</label>
        <Select
          v-model="userStore.form.department_id"
          :options="filteredDepartments"
          optionValue="id"
          optionLabel="name"
          placeholder="Select Department"
        />
        <small v-if="userStore.errors.department_id" class="text-red-500">{{ userStore.errors.department_id }}</small>
      </div>

      <!-- Job Role -->
      <div class="flex flex-col gap-2">
        <label>Job Role</label>
        <Select
          v-model="userStore.form.job_role_id"
          :options="filteredJobRoles"
          optionValue="id"
          optionLabel="name"
          placeholder="Select Job Role"
        />
        <small v-if="userStore.errors.job_role_id" class="text-red-500">{{ userStore.errors.job_role_id }}</small>
      </div>

      <!-- Role -->
      <div class="flex flex-col gap-2">
        <label>Role</label>
        <Select
          v-model="userStore.form.role"
          :options="userStore.roles"
          optionValue="name"
          optionLabel="name"
          placeholder="Select Role"
        />
        <small v-if="userStore.errors.role" class="text-red-500">{{ userStore.errors.role }}</small>
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
import { computed } from "vue";
import { useUserStore } from "@/Store/user";
import { toastSuccess, toastError } from "@/composables/toastService";
import { useToast } from "primevue/usetoast";
import api from "../../api";

const userStore = useUserStore();
const toast = useToast();

const filteredDepartments = computed(() => {
  return userStore.departments;
});

const filteredJobRoles = computed(() => {
  return userStore.jobRoles;
});

// Close dialog
const closeDialog = () => {
  userStore.showEditModal = false;
  userStore.resetForm();
};

const submit = async () => {
  try {
    const response = await userStore.updateUser(); // await the async function
    userStore.resetForm();
    if (response && response.status === 201) {
        userStore.users = response.data.users;
        toastSuccess(toast, "User Management", response.data.message);
    } else if (response) {
        toastError(toast, "User Management", response.data.message);
    }

    userStore.showEditModal = false;

  } catch (err) {
    console.error("Submit failed:", err);
    toastError(toast, "User Management", "Something went wrong.");
  }
};
</script>
