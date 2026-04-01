<template>
    <Dialog
        v-model:visible="jobRoleStore.createModal"
        header="Create Job Role"
        modal
        :closable="false"
        :style="{ width: '30rem' }"
    >
        <div class="flex flex-col gap-4">
            <!-- Name -->
            <div class="flex flex-col gap-2">
                <label>Name</label>
                <InputText v-model="jobRoleStore.form.name" placeholder="Name" />
                <small v-if="jobRoleStore.errors.name" class="text-red-500">{{ jobRoleStore.errors.name }}</small>
            </div>

            <!-- Description -->
            <div class="flex flex-col gap-2">
                <label>Description</label>
                <Textarea
                    id="description"
                    v-model="jobRoleStore.form.description"
                    placeholder="Enter description"
                    rows="3"
                    autoResize
                />
                <small v-if="jobRoleStore.errors.description" class="text-red-500">{{ jobRoleStore.errors.description }}</small>
            </div>

            <!-- Job Family -->
            <div class="flex flex-col gap-2">
                <label>Job Family</label>
                <Select
                    v-model="jobRoleStore.form.job_family_id"
                    :options="filteredJobFamilies"
                    optionValue="id"
                    optionLabel="name"
                    placeholder="Select Job Family"
                />
                <small v-if="jobRoleStore.errors.job_family_id" class="text-red-500">{{ jobRoleStore.errors.job_family_id }}</small>
            </div>

            <!-- Course Categories -->
            <div class="flex flex-col gap-2">
                <label>Course Categories</label>
                <MultiSelect
                    v-model="jobRoleStore.form.category_ids"
                    :options="filteredCategories"
                    optionValue="id"
                    optionLabel="name"
                    placeholder="Select Categories"
                    display="chip"
                />
                <small v-if="jobRoleStore.errors.category_ids || jobRoleStore.errors['category_ids.0']" class="text-red-500">
                    {{ jobRoleStore.errors.category_ids || jobRoleStore.errors['category_ids.0'] }}
                </small>
            </div>

            <div class="flex items-center justify-between">
                <label>Courses</label>
                <Button
                    label="Add Course"
                    icon="pi pi-plus"
                    @click="openCourseDialog"
                    :disabled="!jobRoleStore.form.category_ids.length"
                />
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
                <Button label="Cancel" severity="secondary" @click="jobRoleStore.closeCreate" />
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
import { onMounted, computed, ref, watch } from "vue";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import MultiSelect from "primevue/multiselect";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import { useJobRoleStore } from "@/Store/jobRole";
import { useJobFamilyStore } from "@/Store/jobFamily";
import { useCourseStore } from "@/Store/course";
import { useCourseCategoryStore } from "@/Store/courseCategory";
import { useToast } from "primevue/usetoast";
import { toastError } from "@/composables/toastService";

const toast = useToast();
const jobRoleStore = useJobRoleStore();
const jobFamilyStore = useJobFamilyStore();
const courseStore = useCourseStore();
const courseCategoryStore = useCourseCategoryStore();

onMounted(() => {
    courseStore.fetchCourses();
    courseCategoryStore.fetchCourseCategories();
    jobFamilyStore.fetchJobFamilies();
});

const filteredJobFamilies = computed(() => {
    return jobFamilyStore.jobFamilies;
});

const filteredCategories = computed(() => {
    return courseCategoryStore.categories;
});

const filteredCourses = computed(() => {
    const categoryIds = jobRoleStore.form.category_ids || [];
    if (!categoryIds.length) return [];
    return courseStore.courses.filter((course) => {
        return categoryIds.includes(course.course_category_id);
    });
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
    jobRoleStore.errors.course_ids ||
    jobRoleStore.errors["course_ids.0"] ||
    jobRoleStore.errors.mandatory_course_ids ||
    jobRoleStore.errors["mandatory_course_ids.0"] ||
    jobRoleStore.errors.optional_course_ids ||
    jobRoleStore.errors["optional_course_ids.0"] ||
    ""
);

const assignedCourses = computed(() => {
    const mandatoryIds = new Set(jobRoleStore.form.mandatory_course_ids || []);
    const optionalIds = new Set(jobRoleStore.form.optional_course_ids || []);
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
    const mandatoryIds = jobRoleStore.form.mandatory_course_ids || [];
    const optionalIds = jobRoleStore.form.optional_course_ids || [];
    jobRoleStore.form.course_ids = Array.from(new Set([...mandatoryIds, ...optionalIds]));
};

const reconcileAssignments = () => {
    const allowedIds = new Set(filteredCourses.value.map((course) => course.id));
    const mandatoryIds = (jobRoleStore.form.mandatory_course_ids || []).filter((id) => allowedIds.has(id));
    const optionalIds = (jobRoleStore.form.optional_course_ids || []).filter((id) => allowedIds.has(id));
    const mandatorySet = new Set(mandatoryIds);

    jobRoleStore.form.mandatory_course_ids = mandatoryIds;
    jobRoleStore.form.optional_course_ids = optionalIds.filter((id) => !mandatorySet.has(id));
    syncCourseIds();
};

const updateAssignment = (courseId, value) => {
    const mandatoryIds = new Set(jobRoleStore.form.mandatory_course_ids || []);
    const optionalIds = new Set(jobRoleStore.form.optional_course_ids || []);

    if (value === "mandatory") {
        mandatoryIds.add(courseId);
        optionalIds.delete(courseId);
    } else {
        optionalIds.add(courseId);
        mandatoryIds.delete(courseId);
    }

    jobRoleStore.form.mandatory_course_ids = Array.from(mandatoryIds);
    jobRoleStore.form.optional_course_ids = Array.from(optionalIds);
    syncCourseIds();
};

const removeAssignment = (courseId) => {
    jobRoleStore.form.mandatory_course_ids = (jobRoleStore.form.mandatory_course_ids || []).filter(
        (id) => id !== courseId
    );
    jobRoleStore.form.optional_course_ids = (jobRoleStore.form.optional_course_ids || []).filter(
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

    const targetIds = new Set(jobRoleStore.form[target] || []);
    const otherIds = new Set(jobRoleStore.form[other] || []);

    selectedCourseIds.value.forEach((id) => {
        targetIds.add(id);
        otherIds.delete(id);
    });

    jobRoleStore.form[target] = Array.from(targetIds);
    jobRoleStore.form[other] = Array.from(otherIds);
    syncCourseIds();
    closeCourseDialog();
};

watch(
    () => jobRoleStore.form.job_family_id,
    () => {
        const selectedCategories = jobRoleStore.form.category_ids || [];
        if (!selectedCategories.length) {
            reconcileAssignments();
            return;
        }
        const validCategoryIds = new Set(filteredCategories.value.map((category) => category.id));
        const nextCategories = selectedCategories.filter((id) => validCategoryIds.has(id));
        if (nextCategories.length !== selectedCategories.length) {
            jobRoleStore.form.category_ids = nextCategories;
        }

        reconcileAssignments();
    }
);

watch(() => jobRoleStore.form.category_ids, reconcileAssignments, { immediate: true });
watch(() => courseStore.courses, reconcileAssignments);

const submit = async () => {
  try {
    await jobRoleStore.createJobRole(toast);
  } catch (err) {
    console.error("Submit failed:", err);
    toastError(toast, "Job Role Management", "Something went wrong.");
  }
};
</script>
