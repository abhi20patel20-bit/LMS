<template>
    <Dialog
        v-model:visible="jobFamilyStore.createModal"
        header="Create Job Family"
        modal
        :closable="false"
        :style="{ width: '90rem', height: '90rem' }"
    >
        <div class="flex flex-col gap-4">
            <!-- Name -->
            <div class="flex flex-col gap-2">
                <label>Name</label>
                <InputText v-model="jobFamilyStore.form.name" placeholder="Name" />
                <small v-if="jobFamilyStore.errors.name" class="text-red-500">{{ jobFamilyStore.errors.name }}</small>
            </div>

            <!-- Description -->
            <div class="flex flex-col gap-2">
                <label>Description</label>
                <Textarea
                    id="description"
                    v-model="jobFamilyStore.form.description"
                    placeholder="Enter description"
                    rows="3"
                    autoResize
                />
                <small v-if="jobFamilyStore.errors.description" class="text-red-500">{{ jobFamilyStore.errors.description }}</small>
            </div>

            <div class="flex items-center justify-between">
                <label>Courses</label>
                <Button label="Add Course" icon="pi pi-plus" @click="openCourseDialog" />
            </div>
            <DataTable :value="assignedCourses" dataKey="id" class="p-datatable-sm">
                <Column field="title" header="Course" />
                <Column header="Assignment">
                    <template #body="{ data }">
                        <Select
                            :modelValue="data.assignment"
                            :options="assignmentOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Select type"
                            @update:modelValue="(value) => updateAssignment(data.id, value)"
                        />
                    </template>
                </Column>
                <Column header="Action">
                    <template #body="{ data }">
                        <Button
                            icon="pi pi-trash"
                            severity="danger"
                            outlined
                            @click="removeAssignment(data.id)"
                        />
                    </template>
                </Column>
            </DataTable>
            <small v-if="assignmentError" class="text-red-500">
                {{ assignmentError }}
            </small>

            <!-- Actions -->
            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" @click="jobFamilyStore.closeCreate" />
                <Button label="Create" lockLabel="Creating..." @click="submit" />
            </div>
        </div>

        <Dialog
            v-model:visible="showCourseDialog"
            header="Add Courses"
            modal
            :style="{ width: '32rem' }"
        >
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label>Courses</label>
                    <MultiSelect
                        v-model="selectedCourseIds"
                        :options="availableCourses"
                        optionValue="id"
                        optionLabel="title"
                        placeholder="Select courses"
                        display="chip"
                        filter
                        filterPlaceholder="Search courses"
                    />
                </div>
                <div class="flex flex-col gap-2">
                    <label>Assignment</label>
                    <Select
                        v-model="selectedAssignmentType"
                        :options="assignmentOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select type"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <Button label="Cancel" severity="secondary" @click="closeCourseDialog" />
                    <Button label="Add" @click="addCourses" />
                </div>
            </div>
        </Dialog>
    </Dialog>
</template>

<script setup>
import { onMounted, computed, ref } from "vue";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import MultiSelect from "primevue/multiselect";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Select from "primevue/select";
import Button from "primevue/button";
import { useJobFamilyStore } from "@/Store/jobFamily";
import { useCourseStore } from "@/Store/course";
import { useToast } from "primevue/usetoast";
import { toastError } from "@/composables/toastService";

const toast = useToast();
const jobFamilyStore = useJobFamilyStore();
const courseStore = useCourseStore();

onMounted(() => {
    courseStore.fetchCourses();
});

const filteredCourses = computed(() => {
    return courseStore.courses;
});

const assignmentOptions = [
    { label: "Required", value: "mandatory" },
    { label: "Optional", value: "optional" },
];

const showCourseDialog = ref(false);
const selectedCourseIds = ref([]);
const selectedAssignmentType = ref("mandatory");

const courseMap = computed(() => new Map(filteredCourses.value.map((course) => [course.id, course])));
const assignmentError = computed(() =>
    jobFamilyStore.errors.course_ids ||
    jobFamilyStore.errors["course_ids.0"] ||
    jobFamilyStore.errors.mandatory_course_ids ||
    jobFamilyStore.errors["mandatory_course_ids.0"] ||
    jobFamilyStore.errors.optional_course_ids ||
    jobFamilyStore.errors["optional_course_ids.0"] ||
    ""
);

const assignedCourses = computed(() => {
    const mandatoryIds = new Set(jobFamilyStore.form.mandatory_course_ids || []);
    const optionalIds = new Set(jobFamilyStore.form.optional_course_ids || []);
    const ids = Array.from(new Set([...mandatoryIds, ...optionalIds]));

    return ids.map((id) => {
        const course = courseMap.value.get(id);
        return {
            id,
            title: course?.title || `Course ${id}`,
            assignment: mandatoryIds.has(id) ? "mandatory" : "optional",
        };
    });
});

const availableCourses = computed(() => {
    const assignedIds = new Set(assignedCourses.value.map((course) => course.id));
    return filteredCourses.value.filter((course) => !assignedIds.has(course.id));
});

const syncCourseIds = () => {
    const mandatoryIds = jobFamilyStore.form.mandatory_course_ids || [];
    const optionalIds = jobFamilyStore.form.optional_course_ids || [];
    jobFamilyStore.form.course_ids = Array.from(new Set([...mandatoryIds, ...optionalIds]));
};

const updateAssignment = (courseId, value) => {
    const mandatoryIds = new Set(jobFamilyStore.form.mandatory_course_ids || []);
    const optionalIds = new Set(jobFamilyStore.form.optional_course_ids || []);

    if (value === "mandatory") {
        mandatoryIds.add(courseId);
        optionalIds.delete(courseId);
    } else {
        optionalIds.add(courseId);
        mandatoryIds.delete(courseId);
    }

    jobFamilyStore.form.mandatory_course_ids = Array.from(mandatoryIds);
    jobFamilyStore.form.optional_course_ids = Array.from(optionalIds);
    syncCourseIds();
};

const removeAssignment = (courseId) => {
    jobFamilyStore.form.mandatory_course_ids = (jobFamilyStore.form.mandatory_course_ids || []).filter(
        (id) => id !== courseId
    );
    jobFamilyStore.form.optional_course_ids = (jobFamilyStore.form.optional_course_ids || []).filter(
        (id) => id !== courseId
    );
    syncCourseIds();
};

const openCourseDialog = () => {
    selectedCourseIds.value = [];
    selectedAssignmentType.value = "mandatory";
    showCourseDialog.value = true;
};

const closeCourseDialog = () => {
    showCourseDialog.value = false;
};

const addCourses = () => {
    const target = selectedAssignmentType.value === "mandatory"
        ? "mandatory_course_ids"
        : "optional_course_ids";
    const other = target === "mandatory_course_ids" ? "optional_course_ids" : "mandatory_course_ids";

    const targetIds = new Set(jobFamilyStore.form[target] || []);
    const otherIds = new Set(jobFamilyStore.form[other] || []);

    selectedCourseIds.value.forEach((id) => {
        targetIds.add(id);
        otherIds.delete(id);
    });

    jobFamilyStore.form[target] = Array.from(targetIds);
    jobFamilyStore.form[other] = Array.from(otherIds);
    syncCourseIds();
    closeCourseDialog();
};

const submit = async () => {
  try {
    await jobFamilyStore.createJobFamily(toast);
  } catch (err) {
    console.error("Submit failed:", err);
    toastError(toast, "Job Family Management", "Something went wrong.");
  }
};
</script>
