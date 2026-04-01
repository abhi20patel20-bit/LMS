<template>
  <AppLayout>
    <div class="card space-y-6">
      <div>
        <h2 class="text-surface-900 dark:text-surface-0 font-semibold text-2xl tracking-tight">
          My Learning
        </h2>
        <p class="text-muted-color">View your required and optional courses.</p>
      </div>

      <div class="flex flex-wrap gap-4 items-end">
        <div class="flex flex-col gap-2 min-w-[220px]">
          <label>Search</label>
          <InputText v-model="searchTerm" placeholder="Search courses" />
        </div>
        <div class="flex flex-col gap-2 min-w-[220px]">
          <label>Category</label>
          <Select
            v-model="selectedCategory"
            :options="categoryOptions"
            optionLabel="name"
            optionValue="id"
            placeholder="All categories"
            showClear
          />
        </div>
        <div class="flex flex-col gap-2 min-w-[220px]">
          <label>Provider</label>
          <Select
            v-model="selectedProvider"
            :options="providerOptions"
            optionLabel="name"
            optionValue="id"
            placeholder="All providers"
            showClear
          />
        </div>
        <div class="flex flex-col gap-2 min-w-[220px]">
          <label>Status</label>
          <Select
            v-model="selectedStatus"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All statuses"
            showClear
          />
        </div>
        <Button
          label="Refresh"
          icon="pi pi-refresh"
          severity="secondary"
          class="ml-auto"
          @click="refresh"
          :loading="store.loadingLearning"
        />
      </div>

      <TabView>
        <TabPanel header="Required">
          <DataTable :value="filteredRequired" :loading="store.loadingLearning" :rows="10" paginator>
            <Column field="course.title" header="Course">
              <template #body="{ data }">
                <div class="font-medium text-surface-900 dark:text-surface-0">
                  {{ data.course?.title }}
                </div>
                <div class="text-sm text-muted-color">{{ data.course?.category?.name }}</div>
              </template>
            </Column>
            <Column header="Provider">
              <template #body="{ data }">
                <span class="text-sm text-muted-color">
                  {{ providerLabel(data) }}
                </span>
              </template>
            </Column>
            <Column header="Status">
              <template #body="{ data }">
                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
              </template>
            </Column>
            <Column header="Due">
              <template #body="{ data }">
                <span class="text-sm text-muted-color">{{ formatDate(data.due_at) }}</span>
              </template>
            </Column>
            <Column header="Expiry">
              <template #body="{ data }">
                <span class="text-sm text-muted-color">{{ formatDate(data.expires_at) }}</span>
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
        </TabPanel>

        <TabPanel header="In Progress">
          <DataTable :value="filteredInProgress" :loading="store.loadingLearning" :rows="10" paginator>
            <Column field="course.title" header="Course">
              <template #body="{ data }">
                <div class="font-medium text-surface-900 dark:text-surface-0">
                  {{ data.course?.title }}
                </div>
                <div class="text-sm text-muted-color">{{ data.course?.category?.name }}</div>
              </template>
            </Column>
            <Column header="Provider">
              <template #body="{ data }">
                <span class="text-sm text-muted-color">
                  {{ providerLabel(data) }}
                </span>
              </template>
            </Column>
            <Column header="Status">
              <template #body="{ data }">
                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
              </template>
            </Column>
            <Column header="Due">
              <template #body="{ data }">
                <span class="text-sm text-muted-color">{{ formatDate(data.due_at) }}</span>
              </template>
            </Column>
            <Column header="Expiry">
              <template #body="{ data }">
                <span class="text-sm text-muted-color">{{ formatDate(data.expires_at) }}</span>
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
                    v-else
                    label="Continue"
                    size="small"
                    severity="secondary"
                    @click="startCourse(data)"
                  />
                  <Button
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
        </TabPanel>

        <TabPanel header="Completed">
          <DataTable :value="filteredCompleted" :loading="store.loadingLearning" :rows="10" paginator>
            <Column field="course.title" header="Course">
              <template #body="{ data }">
                <div class="font-medium text-surface-900 dark:text-surface-0">
                  {{ data.course?.title }}
                </div>
                <div class="text-sm text-muted-color">{{ data.course?.category?.name }}</div>
              </template>
            </Column>
            <Column header="Provider">
              <template #body="{ data }">
                <span class="text-sm text-muted-color">
                  {{ providerLabel(data) }}
                </span>
              </template>
            </Column>
            <Column header="Status">
              <template #body="{ data }">
                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
              </template>
            </Column>
            <Column header="Due">
              <template #body="{ data }">
                <span class="text-sm text-muted-color">{{ formatDate(data.due_at) }}</span>
              </template>
            </Column>
            <Column header="Expiry">
              <template #body="{ data }">
                <span class="text-sm text-muted-color">{{ formatDate(data.expires_at) }}</span>
              </template>
            </Column>
            <Column header="Action">
              <template #body="{ data }">
                <Button
                  label="View"
                  size="small"
                  severity="info"
                  outlined
                  @click="openCourse(data)"
                />
              </template>
            </Column>
          </DataTable>
        </TabPanel>

        <TabPanel header="Optional">
          <DataTable :value="filteredOptional" :loading="store.loadingLearning" :rows="10" paginator>
            <Column field="course.title" header="Course">
              <template #body="{ data }">
                <div class="font-medium text-surface-900 dark:text-surface-0">
                  {{ data.course?.title }}
                </div>
                <div class="text-sm text-muted-color">{{ data.course?.category?.name }}</div>
              </template>
            </Column>
            <Column header="Provider">
              <template #body="{ data }">
                <span class="text-sm text-muted-color">
                  {{ providerLabel(data) }}
                </span>
              </template>
            </Column>
            <Column header="Status">
              <template #body="{ data }">
                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
              </template>
            </Column>
            <Column header="Due">
              <template #body="{ data }">
                <span class="text-sm text-muted-color">{{ formatDate(data.due_at) }}</span>
              </template>
            </Column>
            <Column header="Expiry">
              <template #body="{ data }">
                <span class="text-sm text-muted-color">{{ formatDate(data.expires_at) }}</span>
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
                    label="Cancel"
                    size="small"
                    severity="danger"
                    outlined
                    @click="cancelCourse(data)"
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
        </TabPanel>
      </TabView>
    </div>

    <CourseDetailDialog v-model="showCourseDialog" :course="selectedCourse" />
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Tag from "primevue/tag";
import Button from "primevue/button";
import CourseDetailDialog from "@/Components/CourseDetailDialog.vue";
import { useMeLearningStore } from "@/Store/meLearning";
import { useToast } from "primevue/usetoast";
import { toastError } from "@/composables/toastService";

const store = useMeLearningStore();
const toast = useToast();
const searchTerm = ref("");
const selectedCategory = ref(null);
const selectedProvider = ref(null);
const selectedStatus = ref(null);
const selectedCourse = ref(null);
const showCourseDialog = ref(false);

const statusOptions = [
  { label: "Not Started", value: "not_started" },
  { label: "In Progress", value: "in_progress" },
  { label: "Completed", value: "completed" },
  { label: "Expired", value: "expired" },
];

const refresh = async () => {
  await store.fetchLearning();
};

onMounted(() => {
  refresh();
});

const allEnrollments = computed(() => [
  ...(store.learning?.required || []),
  ...(store.learning?.in_progress || []),
  ...(store.learning?.completed || []),
  ...(store.learning?.optional || []),
]);

const categoryOptions = computed(() => {
  const map = new Map();
  allEnrollments.value.forEach((item) => {
    const category = item.course?.category;
    if (category?.id) {
      map.set(category.id, category);
    }
  });
  return Array.from(map.values());
});

const providerOptions = computed(() => {
  const map = new Map();
  allEnrollments.value.forEach((item) => {
    (item.course?.providers || []).forEach((provider) => {
      if (provider?.id) {
        map.set(provider.id, provider);
      }
    });
  });
  return Array.from(map.values());
});

const matchesFilters = (item) => {
  const term = searchTerm.value.trim().toLowerCase();
  const title = item.course?.title?.toLowerCase() ?? "";
  const category = item.course?.category?.name?.toLowerCase() ?? "";
  const providers = (item.course?.providers || [])
    .map((provider) => provider.name?.toLowerCase())
    .join(" ");

  if (term && ![title, category, providers].some((val) => val.includes(term))) {
    return false;
  }

  if (selectedCategory.value && item.course?.category?.id !== selectedCategory.value) {
    return false;
  }

  if (
    selectedProvider.value &&
    !(item.course?.providers || []).some((provider) => provider.id === selectedProvider.value)
  ) {
    return false;
  }

  if (selectedStatus.value && item.status !== selectedStatus.value) {
    return false;
  }

  return true;
};

const filteredRequired = computed(() => (store.learning?.required || []).filter(matchesFilters));
const filteredInProgress = computed(() => (store.learning?.in_progress || []).filter(matchesFilters));
const filteredCompleted = computed(() => (store.learning?.completed || []).filter(matchesFilters));
const filteredOptional = computed(() => (store.learning?.optional || []).filter(matchesFilters));

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

const providerLabel = (data) => {
  const providers = data.course?.providers || [];
  if (!providers.length) {
    return "N/A";
  }
  return providers.map((provider) => provider.name).filter(Boolean).join(", ");
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
    toastError(toast, "My Learning", "Unable to start the course.");
  }
};

const completeCourse = async (enrollment) => {
  if (!enrollment?.course?.id) return;
  try {
    await store.completeCourse(enrollment.course.id);
  } catch (err) {
    console.error("Complete course failed:", err);
    toastError(toast, "My Learning", "Unable to complete the course.");
  }
};

const cancelCourse = async (enrollment) => {
  if (!enrollment?.course?.id) return;
  try {
    await store.cancelCourse(enrollment.course.id);
  } catch (err) {
    console.error("Cancel course failed:", err);
    toastError(toast, "My Learning", "Unable to cancel the course.");
  }
};
</script>
