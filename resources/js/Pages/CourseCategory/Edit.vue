<template>
    <Dialog
        v-model:visible="categoryStore.showEditModal"
        header="Edit Course Category"
        modal
        :closable="false"
        :style="{ width: '30rem' }"
    >
        <div class="flex flex-col gap-4">
            <!-- Name -->
            <div class="flex flex-col gap-2">
                <label>Name</label>
                <InputText v-model="categoryStore.form.name" placeholder="Name" />
                <small v-if="categoryStore.errors.name" class="text-red-500">{{ categoryStore.errors.name }}</small>
            </div>

            <!-- Description -->
            <div class="flex flex-col gap-2">
                <label>Description</label>
                <Textarea
                    id="description"
                    v-model="categoryStore.form.description"
                    placeholder="Enter description"
                    rows="3"
                    autoResize
                />
                <small v-if="categoryStore.errors.description" class="text-red-500">{{ categoryStore.errors.description }}</small>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" @click="categoryStore.closeEdit" />
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
import { useCourseCategoryStore } from "@/Store/courseCategory";
import { useToast } from "primevue/usetoast";
import { toastError } from "@/composables/toastService";

const toast = useToast();
const categoryStore = useCourseCategoryStore();

const submit = async () => {
  try {
    await categoryStore.updateCourseCategory(toast);
  } catch (err) {
    console.error("Submit failed:", err);
    toastError(toast, "Course Category Management", "Something went wrong.");
  }
};
</script>
