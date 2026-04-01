<template>
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-surface-900 dark:text-surface-500 font-semibold text-2xl tracking-tight">
                Company Management
            </h2>
        </div>

        <div class="flex flex-col gap-4">

            <!-- Company Name -->
            <div class="flex flex-col gap-2">
                <label for="companyName">Company Name</label>
                <InputText
                    id="companyName"
                    v-model="company.name"
                    placeholder="Enter company name"
                    type="text"
                    :disabled="!can(['update companies'])"
                />
                <small v-if="company.errors.name" class="text-red-500">
                    {{ company.errors.name }}
                </small>
            </div>

            <!-- Company Email -->
            <div class="flex flex-col gap-2">
                <label for="companyEmail">Email</label>
                <InputText
                    id="companyEmail"
                    v-model="company.email"
                    placeholder="Enter company email"
                    type="email"
                    :disabled="!can(['update companies'])"
                />
                <small v-if="company.errors.email" class="text-red-500">
                    {{ company.errors.email }}
                </small>
            </div>

            <!-- Phone Number -->
            <div class="flex flex-col gap-2">
                <label for="companyPhone">Phone Number</label>
                <InputText
                    id="companyPhone"
                    v-model="company.phone"
                    placeholder="Enter phone number"
                    type="tel"
                    :disabled="!can(['update companies'])"
                />
                <small v-if="company.errors.phone" class="text-red-500">
                    {{ company.errors.phone }}
                </small>
            </div>

            <!-- Type -->
            <div class="flex flex-col gap-2">
                <label>type</label>
                <Select
                    v-model="company.type"
                    :options="compayTypeList"
                    placeholder="Select Type"
                />
                <small v-if="company.errors.type" class="text-red-500">{{ company.errors.type }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <label for="companyAddress">Settings</label>
                <Textarea
                    id="companyAddress"
                    v-model="company.settings"
                    placeholder="Enter settings"
                    rows="3"
                    autoResize
                />
                <small v-if="company.errors.settings" class="text-red-500">
                    {{ company.errors.settings }}
                </small>
            </div>

            <!-- Address -->
            <div class="flex flex-col gap-2">
                <label for="companyAddress">Address</label>
                <Textarea
                    id="companyAddress"
                    v-model="company.address"
                    placeholder="Enter company address"
                    rows="3"
                    autoResize
                    :disabled="!can(['update companies'])"
                />
                <small v-if="company.errors.address" class="text-red-500">
                    {{ company.errors.address }}
                </small>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2 mt-4">
                <Link as="button" class="p-button p-component" href="/dashboard">Back</Link>
                <!-- <Button label="Back" severity="secondary" @click="closeDialog" /> -->
                <Button label="Update" @click="submit" :disabled="!can(['update companies'])" />
            </div>
        </div>
    </div>
</template>


<script setup>
import { ref, onMounted } from "vue";
import { useCompanyStore } from "@/Store/company";
import { toastSuccess, toastError } from "../../composables/toastService"; // adjust if needed
import { useToast } from "primevue/usetoast";
import { Link } from '@inertiajs/vue3';

const company = useCompanyStore();
const toast = useToast();
// Load company info on mount
onMounted(async () => {
    await company.fetchCompany();
});

// Validation before sending to backend
const validateCompany = () => {
  company.errors = {};

  if (!company.name?.trim()) {
    company.errors.name = "Company name is required";
  }
  if (!company.email?.trim()) {
    company.errors.email = "Email is required";
  }
  if (!company.phone?.trim()) {
    company.errors.phone = "Phone number is required";
  }
  if (!company.address?.trim()) {
    company.errors.address = "Address is required";
  }

  return Object.keys(company.errors).length === 0;
};

// Submit update
const submit = async () => {
    if (!validateCompany()) return;

    try {
        const response = await company.updateCompany();

        if (response?.status === 201) {
            toastSuccess(toast, "Company Updated", response.data.message);

        } else if (response) {
            toastError(toast, "Company Update Failed", response.data.message);
        }
    } catch (err) {
        console.error("Update failed:", err);

        // Show backend validation errors
        if (err.response?.data?.errors) {
            company.errors = err.response.data.errors;
        }

        toastError("Error", "Something went wrong.");
    }
};

const compayTypeList = [
    'Free',
    'Paid',
    'Enterprise',
]
</script>
