<template>
    <Dialog
        v-model:visible="providerStore.showEditModal"
        header="Edit Provider"
        modal
        :closable="false"
        :style="{ width: '30rem' }"
    >
        <div class="flex flex-col gap-4">
            <!-- Name -->
            <div class="flex flex-col gap-2">
                <label>Name</label>
                <InputText v-model="providerStore.form.name" placeholder="Name" />
                <small v-if="providerStore.errors.name" class="text-red-500">{{ providerStore.errors.name }}</small>
            </div>

            <!-- Description -->
            <div class="flex flex-col gap-2">
                <label>Description</label>
                <Textarea
                    id="description"
                    v-model="providerStore.form.description"
                    placeholder="Enter description"
                    rows="3"
                    autoResize
                />
                <small v-if="providerStore.errors.description" class="text-red-500">{{ providerStore.errors.description }}</small>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" @click="providerStore.closeEdit" />
                <Button label="Update" lockLabel="Updating..." @click="submit" />
            </div>
        </div>
    </Dialog>
</template>

<script setup>
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Button from "primevue/button";
import { useProviderStore } from "@/Store/provider";
import { useToast } from "primevue/usetoast";
import { toastError } from "@/composables/toastService";

const toast = useToast();
const providerStore = useProviderStore();

const submit = async () => {
  try {
    await providerStore.updateProvider(toast);
  } catch (err) {
    console.error("Submit failed:", err);
    toastError(toast, "Provider Management", "Something went wrong.");
  }
};
</script>
