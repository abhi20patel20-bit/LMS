<template>
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-surface-900 dark:text-surface-500 font-semibold text-2xl tracking-tight">
                Company Management
            </h2>

        </div>

        <!-- Edit User Dialog -->
        <Edit />

        <!-- Users DataTable -->
        <DataTable
            :value="companyStore.companies"
            :loading="companyStore.processing"
            paginator
            :rows="perPage"
            ref="dt"
            :exportFilename="exportFilename"
            v-model:filters="filters"
            filterDisplay="menu"
        >
            <template #header>
                <div class="flex justify-between items-center w-full">
                    <!-- Search field on left -->
                    <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText
                        v-model="filters['global'].value"
                        placeholder="Keyword Search"
                        class="w-72"
                    />
                    </IconField>

                    <!-- Buttons on right -->
                    <div class="flex gap-2">
                    <Button
                        label="Export CSV"
                        icon="pi pi-file"
                        @click="exportCSV"
                    />
                    <Create/>
                    </div>
                </div>
            </template>
            <!-- No -->
            <Column header="No" field="id" :exportable="true" />

            <!-- Name -->
            <Column field="name" header="Name" :exportable="true" />

            <!-- Email -->
            <Column field="email" header="Email" :exportable="true" />

            <Column field="type" header="Type" :exportable="true" />

            <Column field="settings" header="Settings" :exportable="false" />

            <!-- Actions -->
            <Column header="Action" :exportable="false">
                <template #body="{ data }">
                    <!-- Edit -->
                    <Button
                    icon="pi pi-pencil"
                    outlined
                    rounded
                    severity="success"
                    class="mr-2"
                    :disabled="!can(['update companies'])"
                    @click="editCompany(data)"
                    v-tooltip="!can(['update companies']) ? 'You do not have permission' : 'Edit company'"
                    />

                    <!-- Delete -->
                    <Button
                    icon="pi pi-trash"
                    outlined
                    rounded
                    severity="danger"
                    class="mr-2"
                    :disabled="!can(['delete companies'])"
                    @click="confirmDelete(data)"
                    v-tooltip="!can(['delete companies']) ? 'You do not have permission' : 'Delete company'"
                    />
                </template>
            </Column>
        </DataTable>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="deleteDialog" :style="{ width: '25rem' }" header="Are you sure you want to delete this user ?" modal>
            <div class="flex items-center gap-4">
                <div class="flex flex-col gap-2">
                    <span><i class="pi pi-exclamation-triangle text-3xl" /> Delete <b>{{ selectedUser?.name }}</b>?</span>
                </div>
            </div>
            <template #footer>
                <Button label="No" text @click="deleteDialog = false" />
                <Button label="Yes" @click="deleteUser" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import Edit from "@/Pages/Company/Edit.vue";

// PrimeVue components
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import api from "../../api";
import { useToast } from "primevue/usetoast";
import { toastSuccess, toastError } from "@/composables/toastService";
import { useAuthStore } from "@/Store/auth";
import { useCompanyStore } from "@/Store/company";

const companyStore = useCompanyStore();
const dt = ref(null);
const toast = useToast();
const deleteDialog = ref(false);
const selectedUser = ref(null);
const authStore = useAuthStore();

const restoreDialog = ref(false);

const isSuperAdmin = authStore.user?.role === 'superadmin';

// Search filter
const filters = ref({
  global: { value: null, matchMode: "contains" },
});

const today = new Date();
const formattedDate = today.toISOString().split("T")[0];
const exportFilename = `users-${formattedDate}`;
const perPage = 10;

// Fetch users and companies on mount
onMounted(() => {
    companyStore.fetchCompanies();
});

// Open Edit User dialog
const editCompany = (company) => {
    companyStore.openEditModal(company);
};

// Open edit modal with selected user



// Confirm delete dialog
const confirmDelete = (user) => {
    selectedUser.value = user;
    deleteDialog.value = true;
};

const confirmRestore = (user) => {
  selectedUser.value = user;
  restoreDialog.value = true;
};

// Delete user
const deleteUser = async () => {
  if (!selectedUser.value) return;

  try {
    const response = await userStore.removeUser(selectedUser.value.id);
    deleteDialog.value = false;
    if (response && response.status === 201) {
      toastSuccess(toast, "User Management", response.data.message);
    } else if (response) {
      toastError(toast, "User Management", response.data.message);
    }
  } catch (err) {
    console.error("Delete failed:", err);
    toastError(toast, "User Management", "Something went wrong.");
  }
};

const restoreUser = async () => {
    if (!selectedUser.value) return;

    try {
        const response = await api.get(`/users/restore/${selectedUser.value.id}`);
        if (response && response.status === 201) {
            toastSuccess(toast, "User Management", response.data.message);
            restoreDialog.value = false;
            userStore.fetchUsers();
        } else if (response) {
            toastError(toast, "User Management", response.data.message);
        }
    } catch (err) {
        console.error("Unsuspend failed:", err);
        throw err;
    }
};

// Export CSV
const exportCSV = () => {
  dt.value.exportCSV();
};

const openSuspend = (data) => {
  userStore.openDialog(data);
};
</script>
