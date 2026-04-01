<template>
    <Button v-show="can(['create roles'])" label="Add Role" @click="openDialog" icon="pi pi-plus" />

    <Dialog
        v-model:visible="showDialog"
        position="top"
        modal
        header="Add Role"
        :style="{ width: '40rem' }"
        :closable="false"
    >
        <div class="flex flex-col gap-4">

            <!-- Role Name -->
            <div class="flex flex-col gap-2">
                <label>Role Name</label>
                <InputText v-model="form.name" placeholder="Role Name" />
                <small v-if="roleStore.errors.name" class="text-red-500">{{ roleStore.errors.name }}</small>
            </div>

            <!-- Permissions -->
            <div class="flex flex-col gap-2">
                <label>Permissions</label>

                <!-- Select All -->
                <div class="flex items-center gap-2 mb-2">
                    <Checkbox v-model="multipleSelect" @change="toggleSelectAll" :binary="true" inputId="check_all" />
                    <label for="check_all">Check All</label>
                </div>

                <!-- Permission checkboxes in multiple columns -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                <div v-for="perm in roleStore.permissions" :key="perm.id" class="flex items-center gap-2">
                    <Checkbox
                    v-model="form.permissions"
                    :value="perm.name"
                    :inputId="'perm_' + perm.id"
                    @change="toggleSingle"
                    />
                    <label :for="'perm_' + perm.id">{{ perm.name }}</label>
                </div>
                </div>

                <small v-if="roleStore.errors.permissions" class="text-red-500">{{ roleStore.errors.permissions }}</small>
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
    import { ref, reactive, onMounted } from "vue";
    import Button from "primevue/button";
    import Dialog from "primevue/dialog";
    import InputText from "primevue/inputtext";
    import Checkbox from "primevue/checkbox";
    import Select from "primevue/select";
    import { useRoleStore } from "@/Store/role";
    import { useToast } from "primevue/usetoast";
    import { toastSuccess, toastError } from "@/composables/toastService";
    import { useRouter } from "vue-router";

    const router = useRouter();
    const roleStore = useRoleStore();
    const toast = useToast();

    const showDialog = ref(false);
    const multipleSelect = ref(false);

    const form = reactive({
    name: "",
    permissions: [],
    });

    // Open / close dialog
    const openDialog = () => {
    resetForm();
    showDialog.value = true;
    };
    const closeDialog = () => {
    showDialog.value = false;
    resetForm();
    };
    const resetForm = () => {
    form.name = "";
    form.permissions = [];
    multipleSelect.value = false;
    roleStore.resetErrors();
    };

    // Select All / Deselect All
    const toggleSelectAll = () => {
    form.permissions = multipleSelect.value
        ? roleStore.permissions.map((p) => p.name)
        : [];
    };
    const toggleSingle = () => {
    multipleSelect.value = form.permissions.length === roleStore.permissions.length;
    };

    // Submit form with validation
    const submit = async () => {
        roleStore.resetErrors();

        if (!form.name.trim()) {
            roleStore.errors.name = "Role name is required";
            return;
        }
        if (!form.permissions.length) {
            roleStore.errors.permissions = "Select at least one permission";
            return;
        }

        try {
            const response = await roleStore.createRole(form); // await the async function
            if (response && response.status >= 200 && response.status < 300) {
                toastSuccess(toast, "Role Management", response.data?.message || "Role created");
                await roleStore.fetchRoles(); // refresh table
                closeDialog();
                router.push({name: 'roles'});
            } else if (response?.status === 422) {
                const message = Object.values(roleStore.errors || {})[0] || "Validation failed.";
                toastError(toast, "Role Management", message);
            } else if (response) {
                toastError(toast, "Role Management", response.data?.message || "Role create failed.");
            }
        } catch (err) {
            console.error("Submit failed:", err);
            toastError(toast, "Role Management", "Something went wrong.");
        }
    };

</script>
