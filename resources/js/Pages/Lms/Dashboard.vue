<template>
  <AppLayout>
    <div class="card space-y-6">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="text-surface-900 dark:text-surface-0 font-semibold text-2xl tracking-tight">
            My Dashboard
          </h2>
          <p class="text-muted-color">Track required courses and upcoming deadlines.</p>
        </div>
        <Button
          label="Refresh"
          icon="pi pi-refresh"
          severity="secondary"
          @click="refresh"
          :loading="store.loadingDashboard"
        />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-2">Overdue</span>
              <div class="text-surface-900 dark:text-surface-0 font-semibold text-2xl">
                {{ counts.overdue }}
              </div>
            </div>
            <div class="flex items-center justify-center bg-red-100 dark:bg-red-400/10 rounded-border h-10 w-10">
              <i class="pi pi-exclamation-triangle text-red-500 !text-xl"></i>
            </div>
          </div>
        </div>

        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-2">Due Soon</span>
              <div class="text-surface-900 dark:text-surface-0 font-semibold text-2xl">
                {{ counts.dueSoon }}
              </div>
            </div>
            <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-border h-10 w-10">
              <i class="pi pi-calendar text-orange-500 !text-xl"></i>
            </div>
          </div>
        </div>

        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-2">In Progress</span>
              <div class="text-surface-900 dark:text-surface-0 font-semibold text-2xl">
                {{ counts.inProgress }}
              </div>
            </div>
            <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-border h-10 w-10">
              <i class="pi pi-spinner text-blue-500 !text-xl"></i>
            </div>
          </div>
        </div>

        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-2">Completed</span>
              <div class="text-surface-900 dark:text-surface-0 font-semibold text-2xl">
                {{ counts.completed }}
              </div>
            </div>
            <div class="flex items-center justify-center bg-emerald-100 dark:bg-emerald-400/10 rounded-border h-10 w-10">
              <i class="pi pi-check text-emerald-500 !text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <div>
        <h3 class="text-surface-900 dark:text-surface-0 font-semibold text-xl mb-3">Next Actions</h3>
        <DataTable
          :value="lists.nextUp"
          :loading="store.loadingDashboard"
          :rows="5"
          responsiveLayout="scroll"
        >
          <Column field="course.title" header="Course">
            <template #body="{ data }">
              <div class="font-medium text-surface-900 dark:text-surface-0">
                {{ data.course?.title }}
              </div>
              <div class="text-sm text-muted-color">{{ data.course?.category?.name }}</div>
            </template>
          </Column>
          <Column header="Due">
            <template #body="{ data }">
              <span class="text-sm text-muted-color">{{ formatDate(data.due_at) }}</span>
            </template>
          </Column>
          <Column header="Status">
            <template #body="{ data }">
              <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Action">
            <template #body="{ data }">
              <div class="flex flex-wrap gap-2">
                <Button
                  v-if="isScheduledCourse(data) && data.status !== 'completed'"
                  :label="bookingActionLabel(data)"
                  size="small"
                  @click="startCourse(data)"
                />
                <Button
                  v-else-if="data.status === 'not_started'"
                  label="Start"
                  size="small"
                  @click="startCourse(data)"
                />
                <Button
                  v-else-if="data.status === 'in_progress'"
                  label="Continue"
                  size="small"
                  severity="secondary"
                  @click="startCourse(data)"
                />
                <Button
                  v-if="data.status !== 'completed'"
                  label="Mark Complete"
                  size="small"
                  severity="success"
                  @click="completeCourse(data)"
                />
                <Button
                  label="View"
                  size="small"
                  severity="info"
                  outlined
                  @click="openCourse(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="p-4 border border-surface-200 dark:border-surface-800 rounded space-y-2">
          <h4 class="font-semibold text-surface-900 dark:text-surface-0">Overdue</h4>
          <div v-if="lists.overdue.length" class="space-y-2">
            <div v-for="item in lists.overdue" :key="item.id" class="flex items-center justify-between">
              <span class="text-sm text-surface-900 dark:text-surface-0">
                {{ item.course?.title }}
              </span>
              <span class="text-xs text-muted-color">{{ formatDate(item.due_at) }}</span>
            </div>
          </div>
          <p v-else class="text-sm text-muted-color">No overdue courses.</p>
        </div>

        <div class="p-4 border border-surface-200 dark:border-surface-800 rounded space-y-2">
          <h4 class="font-semibold text-surface-900 dark:text-surface-0">Due Soon</h4>
          <div v-if="lists.dueSoon.length" class="space-y-2">
            <div v-for="item in lists.dueSoon" :key="item.id" class="flex items-center justify-between">
              <span class="text-sm text-surface-900 dark:text-surface-0">
                {{ item.course?.title }}
              </span>
              <span class="text-xs text-muted-color">{{ formatDate(item.due_at) }}</span>
            </div>
          </div>
          <p v-else class="text-sm text-muted-color">Nothing due soon.</p>
        </div>

        <div class="p-4 border border-surface-200 dark:border-surface-800 rounded space-y-2">
          <h4 class="font-semibold text-surface-900 dark:text-surface-0">In Progress</h4>
          <div v-if="lists.inProgress.length" class="space-y-2">
            <div v-for="item in lists.inProgress" :key="item.id" class="flex items-center justify-between">
              <span class="text-sm text-surface-900 dark:text-surface-0">
                {{ item.course?.title }}
              </span>
              <span class="text-xs text-muted-color">{{ formatDate(item.due_at) }}</span>
            </div>
          </div>
          <p v-else class="text-sm text-muted-color">No courses in progress.</p>
        </div>
      </div>
    </div>

    <CourseDetailDialog v-model="showCourseDialog" :course="selectedCourse" />
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Button from "primevue/button";
import CourseDetailDialog from "@/Components/CourseDetailDialog.vue";
import { useMeLearningStore } from "@/Store/meLearning";
import { useToast } from "primevue/usetoast";
import { toastError } from "@/composables/toastService";

const store = useMeLearningStore();
const toast = useToast();
const selectedCourse = ref(null);
const showCourseDialog = ref(false);

const counts = computed(() => store.dashboard?.counts ?? {
  overdue: 0,
  dueSoon: 0,
  inProgress: 0,
  completed: 0,
});
const lists = computed(() => store.dashboard?.lists ?? {
  overdue: [],
  dueSoon: [],
  inProgress: [],
  nextUp: [],
});

const refresh = async () => {
  await store.fetchDashboard();
};

onMounted(() => {
  refresh();
});

const formatDate = (value) => {
  if (!value) return "";
  return new Date(value).toLocaleDateString();
};

const statusLabel = (status) => {
  switch (status) {
    case "in_progress":
      return "In Progress";
    case "completed":
      return "Completed";
    case "expired":
      return "Expired";
    default:
      return "Not Started";
  }
};

const statusSeverity = (status) => {
  switch (status) {
    case "completed":
      return "success";
    case "in_progress":
      return "warning";
    case "expired":
      return "danger";
    default:
      return "info";
  }
};

const bookingActionLabel = (enrollment) => {
  const status = enrollment?.booking_status ?? "none";
  switch (status) {
    case "booked":
      return "Booked Session";
    case "waitlisted":
      return "Waitlisted";
    default:
      return "Book Session";
  }
};

const isScheduledCourse = (enrollment) => {
  return enrollment?.course?.delivery_type === "scheduled";
};

const openCourse = (enrollment) => {
  selectedCourse.value = enrollment?.course ?? null;
  showCourseDialog.value = true;
};

const startCourse = async (enrollment) => {
  if (!enrollment?.course?.id) return;
  if (isScheduledCourse(enrollment)) {
    openCourse(enrollment);
    return;
  }
  try {
    await store.startCourse(enrollment.course.id);
  } catch (err) {
    console.error("Start course failed:", err);
    toastError(toast, "My Dashboard", "Unable to start the course.");
  }
};

const completeCourse = async (enrollment) => {
  if (!enrollment?.course?.id) return;
  try {
    await store.completeCourse(enrollment.course.id);
  } catch (err) {
    console.error("Complete course failed:", err);
    toastError(toast, "My Dashboard", "Unable to complete the course.");
  }
};
</script>
