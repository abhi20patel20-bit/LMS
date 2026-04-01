<template>
    <Dialog
        v-model:visible="courseStore.showEditModal"
        header="Edit Course"
        modal
        :closable="false"
        :style="{ width: '56rem' }"
    >
        <TabView>
            <TabPanel header="Details">
                <div class="flex flex-col gap-4">
                <!-- Title -->
                    <div class="flex flex-col gap-2">
                        <label>Title</label>
                        <InputText v-model="courseStore.form.title" placeholder="Title" />
                        <small v-if="courseStore.errors.title" class="text-red-500">{{ courseStore.errors.title }}</small>
                    </div>

                    <!-- Description -->
                    <div class="flex flex-col gap-2">
                        <label>Description</label>
                        <Textarea
                            id="description"
                            v-model="courseStore.form.description"
                            placeholder="Enter description"
                            rows="3"
                            autoResize
                        />
                        <small v-if="courseStore.errors.description" class="text-red-500">{{ courseStore.errors.description }}</small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label>Status</label>
                        <Select
                            v-model="courseStore.form.status"
                            :options="statusOptions"
                            placeholder="Select Status"
                        />
                        <small v-if="courseStore.errors.status" class="text-red-500">{{ courseStore.errors.status }}</small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label>Category</label>
                        <Select
                            v-model="courseStore.form.course_category_id"
                            :options="filteredCategories"
                            optionValue="id"
                            optionLabel="name"
                            placeholder="Select Category"
                        />
                        <small v-if="courseStore.errors.course_category_id" class="text-red-500">{{ courseStore.errors.course_category_id }}</small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label>Course Type</label>
                        <Select
                            v-model="courseStore.form.course_type"
                            :options="courseTypeOptions"
                            placeholder="Select Type"
                        />
                        <small v-if="courseStore.errors.course_type" class="text-red-500">{{ courseStore.errors.course_type }}</small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label>Delivery Type</label>
                        <Select
                            v-model="courseStore.form.delivery_type"
                            :options="deliveryTypeOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Select Delivery Type"
                        />
                        <small v-if="courseStore.errors.delivery_type" class="text-red-500">{{ courseStore.errors.delivery_type }}</small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label>Booking Required</label>
                        <div class="flex items-center gap-2">
                            <Checkbox
                                v-model="courseStore.form.booking_required"
                                :binary="true"
                                :disabled="courseStore.form.delivery_type !== 'scheduled'"
                            />
                            <span class="text-sm text-muted-color">
                                {{ courseStore.form.delivery_type === 'scheduled' ? 'Required for scheduled sessions.' : 'Not required for self-paced.' }}
                            </span>
                        </div>
                        <small v-if="courseStore.errors.booking_required" class="text-red-500">{{ courseStore.errors.booking_required }}</small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label>Duration (minutes)</label>
                        <InputNumber v-model="courseStore.form.duration" placeholder="Duration" :min="0" />
                        <small v-if="courseStore.errors.duration" class="text-red-500">{{ courseStore.errors.duration }}</small>
                    </div>

                    <div v-if="courseStore.form.delivery_type === 'scheduled'" class="flex flex-col gap-2">
                        <label>Default Capacity</label>
                        <InputNumber v-model="courseStore.form.default_capacity" placeholder="Capacity" :min="1" />
                        <small v-if="courseStore.errors.default_capacity" class="text-red-500">{{ courseStore.errors.default_capacity }}</small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label>Providers</label>
                        <MultiSelect
                            v-model="courseStore.form.provider_ids"
                            :options="filteredProviders"
                            optionValue="id"
                            optionLabel="name"
                            placeholder="Select Providers"
                            display="chip"
                        />
                        <small v-if="courseStore.errors.provider_ids || courseStore.errors['provider_ids.0']" class="text-red-500">
                            {{ courseStore.errors.provider_ids || courseStore.errors['provider_ids.0'] }}
                        </small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label>Price</label>
                        <InputNumber v-model="courseStore.form.price" placeholder="Title" />
                        <small v-if="courseStore.errors.price" class="text-red-500">{{ courseStore.errors.price }}</small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label>Settings</label>
                        <Textarea
                            id="description"
                            v-model="courseStore.form.settings"
                            placeholder="Enter description"
                            rows="3"
                            autoResize
                        />
                        <small v-if="courseStore.errors.settings" class="text-red-500">{{ courseStore.errors.settings }}</small>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-2">
                        <Button label="Cancel" severity="secondary" @click="courseStore.closeEdit" />
                        <Button label="Update" @click="submit" />
                    </div>
                </div>
            </TabPanel>
            <TabPanel header="Sessions">
                <SessionsTab
                    :course-id="courseStore.form.id"
                    :providers="filteredProviders"
                    :delivery-type="courseStore.form.delivery_type"
                    :default-capacity="courseStore.form.default_capacity"
                />
            </TabPanel>
        </TabView>
    </Dialog>
</template>


<script setup>
import { onMounted, computed, watch } from "vue";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import InputNumber from "primevue/inputnumber";
import MultiSelect from "primevue/multiselect";
import Checkbox from "primevue/checkbox";
import Button from "primevue/button";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import { useCourseStore } from "@/Store/course";
import { useCourseCategoryStore } from "@/Store/courseCategory";
import { useProviderStore } from "@/Store/provider";
import { toastError, toastSuccess } from "@/composables/toastService";
import { useToast } from "primevue/usetoast";
import SessionsTab from "./SessionsTab.vue";

const toast = useToast();

const courseStore = useCourseStore();
const courseCategoryStore = useCourseCategoryStore();
const providerStore = useProviderStore();
onMounted(() => {
    courseCategoryStore.fetchCourseCategories()
    providerStore.fetchProviders()
});

const filteredCategories = computed(() => {
    return courseCategoryStore.categories;
});

const filteredProviders = computed(() => {
    return providerStore.providers;
});

watch(
    () => courseStore.form.delivery_type,
    (value) => {
        courseStore.form.booking_required = value === "scheduled";
    }
);

const submit = async () => {
  try {
    const response = await courseStore.updateCourse(); // await the async function
    if (response && response.status === 201) {
        courseStore.closeEdit();
        courseStore.fetchCourses();
        toastSuccess(toast, "Course Management", response.data.message);
    } else if (response) {
        toastError(toast, "Course Management", response.data.message);
    }

  } catch (err) {
    console.error("Submit failed:", err);
    toastError(toast, "Course Management", "Something went wrong.");
  }
};

const statusOptions = ['active', 'inactive', 'admin'];
const courseTypeOptions = ['online', 'in-person'];
const deliveryTypeOptions = [
    { label: 'Self-paced', value: 'self_paced' },
    { label: 'Scheduled', value: 'scheduled' },
];
</script>
