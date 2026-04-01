<template>
  <Dialog
    v-model:visible="companyStore.showEditModal"
    header="Update User"
    modal
    :closable="false"
    :style="{ width: '30rem' }"
  >
    <div class="flex flex-col gap-4">

                <!-- Company Name -->
                <div class="flex flex-col gap-2">
                    <label for="companyName">Company Name</label>
                    <InputText
                        id="companyName"
                        v-model="companyStore.name"
                        placeholder="Enter company name"
                        type="text"
                    />
                    <small v-if="companyStore.errors.name" class="text-red-500">
                        {{ companyStore.errors.name }}
                    </small>
                </div>

                <!-- Company Email -->
                <div class="flex flex-col gap-2">
                    <label for="companyEmail">Email</label>
                    <InputText
                        id="companyEmail"
                        v-model="companyStore.email"
                        placeholder="Enter company email"
                        type="email"
                    />
                    <small v-if="companyStore.errors.email" class="text-red-500">
                        {{ companyStore.errors.email }}
                    </small>
                </div>

                <!-- Phone Number -->
                <div class="flex flex-col gap-2">
                    <label for="companyPhone">Phone Number</label>
                    <InputText
                        id="companyPhone"
                        v-model="companyStore.phone"
                        placeholder="Enter phone number"
                        type="tel"
                    />
                    <small v-if="companyStore.errors.phone" class="text-red-500">
                        {{ companyStore.errors.phone }}
                    </small>
                </div>

                <!-- Type -->
                <div class="flex flex-col gap-2">
                    <label>type</label>
                    <Select
                        v-model="companyStore.type"
                        :options="compayTypeList"
                        placeholder="Select Type"
                    />
                    <small v-if="companyStore.errors.type" class="text-red-500">{{ companyStore.errors.type }}</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="companyAddress">Settings</label>
                    <Textarea
                        id="companyAddress"
                        v-model="companyStore.settings"
                        placeholder="Enter settings"
                        rows="3"
                        autoResize
                    />
                    <small v-if="companyStore.errors.settings" class="text-red-500">
                        {{ companyStore.errors.settings }}
                    </small>
                </div>

                <!-- Address -->
                <div class="flex flex-col gap-2">
                    <label for="companyAddress">Address</label>
                    <Textarea
                        id="companyAddress"
                        v-model="companyStore.address"
                        placeholder="Enter company address"
                        rows="3"
                        autoResize
                    />
                    <small v-if="companyStore.errors.address" class="text-red-500">
                        {{ companyStore.errors.address }}
                    </small>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-2 mt-4">
                    <Button label="Back" severity="secondary" @click="closeDialog" />
                    <Button label="Update" @click="submit" />
                </div>
            </div>
  </Dialog>
</template>

<script setup>
import { ref } from "vue";
import { toastSuccess, toastError } from "@/composables/toastService";
import { useToast } from "primevue/usetoast";
import api from "../../api";
import { useCompanyStore } from "@/Store/company";

const companyStore = useCompanyStore();
const toast = useToast();

// Close dialog
const closeDialog = () => {
  companyStore.showEditModal = false;
  companyStore.resetForm();
};

const submit = async () => {
  try {
    const response = await userStore.updateUser(); // await the async function
    companyStore.resetForm();
    if (response && response.status === 201) {
        companyStore.companies = response.data.users.original;
        toastSuccess(toast, "User Management", response.data.message);
    } else if (response) {
        toastError(toast, "User Management", response.data.message);
    }

    companyStore.showEditModal = false;

  } catch (err) {
    console.error("Submit failed:", err);
    toastError(toast, "User Management", "Something went wrong.");
  }
};


const compayTypeList = [
    'Free',
    'Paid',
    'Enterprise',
]
</script>
