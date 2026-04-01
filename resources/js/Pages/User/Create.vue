<template>
  <Button v-show="can(['create users'])" label="Add User" @click="openDialog" icon="pi pi-plus" class="" />

  <Dialog
    v-model:visible="showDialog"
    position="top"
    modal
    header="Add User"
    :style="{ width: '30rem' }"
    :closable="false"
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

      <!-- Password -->
      <div class="flex flex-col gap-2">
        <label>Password</label>
        <InputText type="password" v-model="userStore.form.password" placeholder="Password" />
        <small v-if="userStore.errors.password" class="text-red-500">{{ userStore.errors.password }}</small>
      </div>

      <!-- Confirm Password -->
      <div class="flex flex-col gap-2">
        <label>Confirm Password</label>
        <InputText type="password" v-model="userStore.form.password_confirmation" placeholder="Confirm Password" />
        <small v-if="userStore.errors.password_confirmation" class="text-red-500">
          {{ userStore.errors.password_confirmation }}
        </small>
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
          placeholder="Select"
        />
        <small v-if="userStore.errors.role" class="text-red-500">{{ userStore.errors.role }}</small>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-2">
        <Button label="Cancel" severity="secondary" @click="closeDialog" />
        <Button label="Save" @click="submit" />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, computed } from "vue";
import { useUserStore } from "@/Store/user";
import { toastSuccess, toastError } from "@/composables/toastService";
import { useToast } from "primevue/usetoast";

const toast = useToast();
// Store
const userStore = useUserStore();

// Local state
const showDialog = ref(false);

const session = JSON.parse(localStorage.getItem("user") || "{}");
const defaultCompanyId = session.company?.id ?? null;
const defaultDepartmentId = session.department?.id ?? null;

const filteredDepartments = computed(() => {
  return userStore.departments;
});

const filteredJobRoles = computed(() => {
  return userStore.jobRoles;
});

// Methods
const openDialog = () => {
  userStore.resetForm();
  userStore.getCompanies();
  userStore.getDepartments();
  userStore.getJobRoles();
  userStore.getRoles();
  userStore.form.company_id = defaultCompanyId;
  userStore.form.department_id = defaultDepartmentId;
  showDialog.value = true;
};

const closeDialog = () => {
  showDialog.value = false;
  userStore.resetForm();
};

const submit = async () => {

    if (!userStore.validateForm()) {
        return; // Stop submit if errors exist
    }

    try {
        const response = await userStore.createUser(); // await the async function
        if (response && response.status === 201) {
            userStore.users = response.data.users;
            toastSuccess(toast, "User Management", response.data.message);
            userStore.resetForm();
            closeDialog();

        } else if (response) {
            toastError(toast, "User Management", response.data.message);
        }
    } catch (err) {
        console.error("Submit failed:", err);
        toastError(toast, "User Management", "Something went wrong.");
    }
};

</script>
