<template>
  <Button v-show="can(['create departments'])" label="Add Department" @click="openDialog" icon="pi pi-plus" />

  <Dialog
    v-model:visible="showDialog"
    position="top"
    modal
    header="Add Department"
    :style="{ width: '30rem' }"
    :closable="false"
  >
    <div class="flex flex-col gap-4">
      <div class="flex flex-col gap-2">
        <label>Name</label>
        <InputText v-model="departmentStore.form.name" placeholder="Name" />
        <small v-if="departmentStore.errors.name" class="text-red-500">{{ departmentStore.errors.name }}</small>
      </div>

      <div class="flex flex-col gap-2">
        <label>Slug</label>
        <InputText v-model="departmentStore.form.slug" placeholder="Slug" />
        <small v-if="departmentStore.errors.slug" class="text-red-500">{{ departmentStore.errors.slug }}</small>
      </div>

      <div class="flex flex-col gap-2">
        <label>Custom Domain</label>
        <InputText v-model="departmentStore.form.custom_domain" placeholder="domain.example.com" />
        <small v-if="departmentStore.errors.custom_domain" class="text-red-500">{{ departmentStore.errors.custom_domain }}</small>
      </div>

      <div class="flex flex-col gap-2">
        <label>Subscription Type</label>
        <InputText v-model="departmentStore.form.subscription_type" placeholder="free / paid" />
        <small v-if="departmentStore.errors.subscription_type" class="text-red-500">
          {{ departmentStore.errors.subscription_type }}
        </small>
      </div>

      <div class="flex justify-end gap-2">
        <Button label="Cancel" severity="secondary" @click="closeDialog" />
        <Button label="Save" @click="submit" />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { ref } from "vue";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import { useDepartmentStore } from "@/Store/department";
import { toastSuccess, toastError } from "@/composables/toastService";
import { useToast } from "primevue/usetoast";

const departmentStore = useDepartmentStore();
const showDialog = ref(false);
const toast = useToast();

const openDialog = () => {
  departmentStore.resetForm();
  showDialog.value = true;
};

const closeDialog = () => {
  showDialog.value = false;
  departmentStore.resetForm();
};

const submit = async () => {
  try {
    const response = await departmentStore.createDepartment();
    if (response && response.status === 201) {
      toastSuccess(toast, "Department Management", response.data.message || "Department created successfully");
      closeDialog();
    } else if (response) {
      toastError(toast, "Department Management", response.data.message);
    }
  } catch (err) {
    if (err.response?.status !== 422) {
      console.error("Submit failed:", err);
      toastError(toast, "Department Management", "Something went wrong.");
    }
  }
};
</script>
