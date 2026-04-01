<template>
    <Dialog
        v-model:visible="userStore.showSuspendDialog" :header='"Suspend User: " + userStore.suspendData.user.name' modal
        :closable="false"
        :style="{ width: '50rem' }"
    >
        <div class="flex flex-col gap-4">
            <!-- Name -->
            <div class="flex flex-col gap-2">
                    <label class="font-medium">Reason</label>
                    <InputTextarea v-model="userStore.suspendData.reason" rows="3" autoResize />
            </div>

            <!-- Email -->
            <div class="flex flex-col gap-2">
                    <label>Suspend Until (optional)</label>
                    <DatePicker v-model="userStore.suspendData.until" showIcon dateFormat="yy-mm-dd" />
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2">
                <Button label="Cancel" text @click="userStore.closeDialog" />
                <Button
                    label="Suspend"
                    icon="pi pi-ban"
                    severity="warning"
                    :loading="userStore.loading"
                    @click="suspendUser"
                />
            </div>
        </div>
    </Dialog>
</template>

<script setup>
import { useUserStore } from "@/Store/user";
import { useToast } from "primevue/usetoast";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import InputTextarea from "primevue/textarea"
import DatePicker from "primevue/datepicker";
import { toastSuccess, toastError } from "@/composables/toastService";

const userStore = useUserStore();
const toast = useToast();

// Suspend action
const suspendUser = async () => {
  try {
    const response = await userStore.suspendUser();
    if (response && response.status === 201) {
        userStore.users = response.data.users;
        toastSuccess(toast, "User Management", response.data.message);
        userStore.closeDialog();
    } else if (response) {
      toastError(toast, "User Management", response.data.message);
    }
  } catch (err) {
    console.error("Submit failed:", err);
    toastError(toast, "User Management", "Something went wrong.");
  }
};

</script>
