<template>
  <AppLayout>
    <Toast />
    <div class="card space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-surface-900 dark:text-surface-500 font-semibold text-2xl tracking-tight">
          Matrices
        </h2>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="flex flex-col gap-2">
          <label>Job Family</label>
          <Select
            v-model="selectedJobFamilyId"
            :options="jobFamilies"
            optionLabel="name"
            optionValue="id"
            placeholder="Select Job Family"
            :loading="loadingFilters"
            class="w-full"
          />
        </div>

        <div class="flex flex-col gap-2">
          <label>Course Category</label>
          <Select
            v-model="selectedCategoryId"
            :options="categories"
            optionLabel="name"
            optionValue="id"
            placeholder="All Categories"
            :loading="loadingCategories"
            class="w-full"
            :disabled="!selectedJobFamilyId"
          />
        </div>

        <div class="flex flex-col gap-2">
          <label>Job Role</label>
          <Select
            v-model="selectedJobRoleId"
            :options="jobRoles"
            optionLabel="name"
            optionValue="id"
            placeholder="Select Job Role"
            :loading="loadingJobRoles"
            class="w-full"
            :disabled="!selectedJobFamilyId"
          />
        </div>
      </div>

      <div class="text-muted-color" v-if="!selectedJobFamilyId">
        Select a job family to load categories and roles.
      </div>
      <div class="text-muted-color" v-else-if="selectedJobFamilyId && !jobRoles.length && !loadingJobRoles">
        No job roles are linked to this selection.
      </div>

      <TabView>
        <TabPanel header="Mandatory">
          <DataTable
            :value="mandatoryCourses"
            :loading="loadingCourses"
            dataKey="id"
            selectionMode="single"
            v-model:selection="selectedCourse"
            @rowSelect="openCourse"
          >
            <Column field="title" header="Course" />
            <Column header="Category">
              <template #body="{ data }">
                {{ data.category_name || "Uncategorized" }}
              </template>
            </Column>
            <Column header="Provider">
              <template #body="{ data }">
                {{ data.provider_name || "N/A" }}
              </template>
            </Column>
            <Column field="type" header="Type" />
            <Column header="Enrollment Status">
              <template #body="{ data }">
                <Tag :value="statusLabel(data.enrollment_status)" :severity="statusSeverity(data.enrollment_status)" />
              </template>
            </Column>
          </DataTable>
        </TabPanel>

        <TabPanel header="Optional">
          <DataTable
            :value="optionalCourses"
            :loading="loadingCourses"
            dataKey="id"
            selectionMode="single"
            v-model:selection="selectedCourse"
            @rowSelect="openCourse"
          >
            <Column field="title" header="Course" />
            <Column header="Category">
              <template #body="{ data }">
                {{ data.category_name || "Uncategorized" }}
              </template>
            </Column>
            <Column header="Provider">
              <template #body="{ data }">
                {{ data.provider_name || "N/A" }}
              </template>
            </Column>
            <Column field="type" header="Type" />
            <Column header="Enrollment Status">
              <template #body="{ data }">
                <Tag :value="statusLabel(data.enrollment_status)" :severity="statusSeverity(data.enrollment_status)" />
              </template>
            </Column>
          </DataTable>
        </TabPanel>
      </TabView>
    </div>

    <Dialog
      v-model:visible="showCourseDialog"
      header="Course Details"
      modal
      :closable="true"
      :dismissableMask="true"
      :style="{ width: '36rem' }"
    >
      <div class="space-y-4">
        <div>
          <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-0">
            {{ courseDetail?.title || "Course" }}
          </h3>
          <p class="text-sm text-muted-color">
            {{ courseDetail?.category?.name || "Uncategorized" }}
          </p>
        </div>

        <p class="text-sm text-muted-color">
          {{ courseDetail?.description || "No description available." }}
        </p>

        <div class="grid grid-cols-2 gap-3 text-sm">
          <div>
            <span class="text-muted-color">Type</span>
            <div class="font-medium text-surface-900 dark:text-surface-0">
              {{ selectedCourse?.type || "Optional" }}
            </div>
          </div>
          <div>
            <span class="text-muted-color">Duration</span>
            <div class="font-medium text-surface-900 dark:text-surface-0">
              {{ durationLabel }}
            </div>
          </div>
          <div>
            <span class="text-muted-color">Updated</span>
            <div class="font-medium text-surface-900 dark:text-surface-0">
              {{ updatedLabel }}
            </div>
          </div>
          <div>
            <span class="text-muted-color">Enrollment</span>
            <div class="font-medium text-surface-900 dark:text-surface-0">
              {{ statusLabel(courseDetail?.enrollment_status) }}
            </div>
          </div>
        </div>

        <div>
          <span class="text-muted-color text-sm">Providers</span>
          <div class="flex flex-wrap gap-2 mt-2">
            <span
              v-for="provider in providerNames"
              :key="provider"
              class="px-2 py-1 text-xs rounded-full bg-surface-100 dark:bg-surface-800 text-muted-color"
            >
              {{ provider }}
            </span>
            <span v-if="!providerNames.length" class="text-sm text-muted-color">None listed</span>
          </div>
        </div>

        <div v-if="isScheduled" class="border-t border-surface-200 dark:border-surface-700 pt-4 space-y-4">
          <div v-if="bookingLoading" class="text-sm text-muted-color">
            Loading booking options...
          </div>
          <div v-else>
            <div v-if="currentBooking" class="space-y-2">
              <div class="text-sm text-muted-color">Current booking</div>
              <div class="font-medium text-surface-900 dark:text-surface-0">
                {{ formatDateTime(currentBooking?.session?.starts_at) }}
              </div>
              <div class="text-sm text-muted-color">
                {{ currentBooking?.session?.provider?.name || "Provider TBD" }}
                <span v-if="currentBooking?.session?.location"> - {{ currentBooking.session.location }}</span>
              </div>
              <div class="flex flex-wrap gap-2">
                <Button
                  label="Cancel Booking"
                  severity="danger"
                  outlined
                  @click="cancelBooking"
                  :loading="bookingActionLoading"
                />
                <Button
                  v-if="hasAlternativeSessions"
                  label="Update Booking"
                  outlined
                  @click="startUpdateBooking"
                  :loading="checkingAlternatives"
                />
              </div>
            </div>

            <div v-if="!currentBooking || isUpdatingBooking">
              <Steps :model="bookingSteps" :activeIndex="bookingStepIndex" readOnly />

              <div v-if="currentBookingStep === 'provider'" class="mt-4 space-y-2">
                <label>Choose Provider</label>
                <Select
                  v-model="selectedProviderId"
                  :options="bookingProviders"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Select provider"
                />
              </div>

              <div v-else-if="currentBookingStep === 'date'" class="mt-4 space-y-2">
                <label>Choose Date</label>
                <Calendar v-model="selectedDate" :showIcon="true" placeholder="Select date" />
                <div class="text-xs text-muted-color">
                  <span v-if="bookingDatesLoading">Loading dates...</span>
                  <span v-else-if="bookingDates.length">
                    Available: {{
                      bookingDates
                        .filter((entry) => entry.has_availability)
                        .map((entry) => entry.date)
                        .join(", ")
                    }}
                  </span>
                  <span v-else>No available dates yet.</span>
                </div>
              </div>

              <div v-else-if="currentBookingStep === 'session'" class="mt-4 space-y-2">
                <label>Choose Time Slot</label>
                <div v-if="bookingSessionsLoading" class="text-sm text-muted-color">
                  Loading sessions...
                </div>
                <div v-else-if="!sessionOptions.length" class="text-sm text-muted-color">
                  No sessions for the selected date.
                </div>
                <Listbox
                  v-else
                  v-model="selectedSession"
                  :options="sessionOptions"
                  optionLabel="label"
                  class="w-full"
                >
                  <template #option="{ option }">
                    <div class="flex items-center justify-between">
                      <div class="flex flex-col">
                        <span class="font-medium text-surface-900 dark:text-surface-0">
                          {{ option.label }}
                        </span>
                        <span class="text-xs text-muted-color">
                          {{ option.location || "Location TBD" }}
                        </span>
                      </div>
                      <span class="text-xs text-muted-color">
                        {{ option.seats_available }} seats
                      </span>
                    </div>
                  </template>
                </Listbox>
              </div>

              <div v-else class="mt-4 space-y-3">
                <div class="text-sm text-muted-color">Confirm booking details</div>
                <div class="font-medium text-surface-900 dark:text-surface-0">
                  {{ selectedSession?.label || "Select a session" }}
                </div>
                <div class="text-sm text-muted-color">
                  {{ selectedSession?.location || "Location TBD" }}
                </div>
                <div
                  v-if="bookingFull || selectedSession?.seats_available <= 0"
                  class="text-sm text-red-500"
                >
                  Session full. Join the waitlist to save your spot.
                </div>
                <div class="flex gap-2">
                  <Button
                    v-if="selectedSession?.seats_available > 0 && selectedSession?.status === 'open'"
                    :label="isUpdatingBooking ? 'Update Booking' : 'Confirm Booking'"
                    @click="confirmBooking"
                    :loading="bookingActionLoading"
                    :disabled="
                      isUpdatingBooking && selectedSession?.id === currentBookingSessionId
                    "
                  />
                  <Button
                    v-else
                    label="Join Waitlist"
                    severity="secondary"
                    outlined
                    @click="joinWaitlist"
                    :loading="bookingActionLoading"
                  />
                </div>
              </div>

              <div class="flex items-center justify-between mt-4">
                <Button
                  v-if="isUpdatingBooking"
                  label="Cancel Update"
                  severity="secondary"
                  outlined
                  @click="stopUpdateBooking"
                />
                <div class="flex gap-2 ml-auto">
                  <Button
                    label="Back"
                    severity="secondary"
                    outlined
                    @click="prevBookingStep"
                    :disabled="bookingStepIndex === 0"
                  />
                  <Button
                    v-if="currentBookingStep !== 'confirm'"
                    label="Next"
                    @click="nextBookingStep"
                    :disabled="!canAdvanceStep"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <div class="flex items-center justify-between w-full">
          <span class="text-sm text-muted-color" v-if="isEnrolled">
            Already enrolled
          </span>
          <div class="flex gap-2">
            <Button label="Close" severity="secondary" @click="showCourseDialog = false" />
            <Button
              v-if="!isScheduled"
              label="Enroll"
              :disabled="isEnrolled || enrolling"
              :loading="enrolling"
              @click="enroll"
            />
          </div>
        </div>
      </template>
    </Dialog>
  </AppLayout>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Select from "primevue/select";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import Steps from "primevue/steps";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import Tag from "primevue/tag";
import Toast from "primevue/toast";
import Calendar from "primevue/calendar";
import Listbox from "primevue/listbox";
import { useToast } from "primevue/usetoast";
import { toastSuccess, toastError } from "@/composables/toastService";
import api from "@/api";

const toast = useToast();
const jobFamilies = ref([]);
const categories = ref([]);
const jobRoles = ref([]);
const mandatoryCourses = ref([]);
const optionalCourses = ref([]);
const selectedJobFamilyId = ref(null);
const selectedCategoryId = ref(null);
const selectedJobRoleId = ref(null);
const selectedCourse = ref(null);
const courseDetail = ref(null);
const showCourseDialog = ref(false);
const bookingMeta = ref(null);
const bookingLoading = ref(false);
const bookingDates = ref([]);
const bookingSessions = ref([]);
const bookingDatesLoading = ref(false);
const bookingSessionsLoading = ref(false);
const bookingActionLoading = ref(false);
const bookingStepIndex = ref(0);
const selectedProviderId = ref(null);
const selectedDate = ref(null);
const selectedSession = ref(null);
const bookingFull = ref(false);
const isUpdatingBooking = ref(false);
const hasAlternativeSessions = ref(false);
const checkingAlternatives = ref(false);
const holdBookingSelection = ref(false);
const loadingFilters = ref(false);
const loadingCategories = ref(false);
const loadingJobRoles = ref(false);
const loadingCourses = ref(false);
const loadingCourseDetail = ref(false);
const enrolling = ref(false);
const defaultJobFamilyId = ref(null);
const defaultJobRoleId = ref(null);
const applyDefaultRole = ref(true);

const providerNames = computed(() =>
  Array.isArray(courseDetail.value?.providers)
    ? courseDetail.value.providers.map((provider) => provider.name).filter(Boolean)
    : []
);

const isScheduled = computed(() => courseDetail.value?.delivery_type === "scheduled");
const bookingProviders = computed(() => bookingMeta.value?.providers || []);
const needsProvider = computed(() => bookingProviders.value.length > 1);
const currentBooking = computed(() => bookingMeta.value?.current_booking || null);
const currentBookingSessionId = computed(() => currentBooking.value?.session?.id ?? null);
const bookingSteps = computed(() => {
  const steps = [];
  if (needsProvider.value) {
    steps.push({ label: "Provider", key: "provider" });
  }
  steps.push({ label: "Date", key: "date" });
  steps.push({ label: "Session", key: "session" });
  steps.push({ label: "Confirm", key: "confirm" });
  return steps;
});
const currentBookingStep = computed(
  () => bookingSteps.value[bookingStepIndex.value]?.key || "date"
);
const sessionOptions = computed(() =>
  (bookingSessions.value || []).map((session) => ({
    ...session,
    label: `${formatTimeRange(session.starts_at, session.ends_at)}${session.provider?.name ? ` - ${session.provider.name}` : ""}`,
  }))
);

const durationLabel = computed(() => {
  const minutes = courseDetail.value?.duration;
  return minutes == null ? "N/A" : `${minutes} min`;
});

const updatedLabel = computed(() => courseDetail.value?.updated_at || "N/A");

const isEnrolled = computed(() => {
  if (isScheduled.value) {
    return false;
  }
  return !!(courseDetail.value?.enrollment_status || selectedCourse.value?.enrollment_status);
});

const statusLabel = (status) => {
  if (!status) return "Not enrolled";
  return status.replace("_", " ");
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

const canAdvanceStep = computed(() => {
  switch (currentBookingStep.value) {
    case "provider":
      return !!selectedProviderId.value;
    case "date":
      return !!selectedDate.value;
    case "session":
      if (!selectedSession.value) {
        return false;
      }
      if (isUpdatingBooking.value && selectedSession.value.id === currentBookingSessionId.value) {
        return false;
      }
      return true;
    default:
      return false;
  }
});

const formatDateParam = (value) => {
  if (!value) return null;
  const date = new Date(value);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
};

const formatTimeRange = (start, end) => {
  if (!start || !end) return "TBD";
  const startDate = new Date(start);
  const endDate = new Date(end);
  const options = { hour: "2-digit", minute: "2-digit" };
  return `${startDate.toLocaleTimeString([], options)} - ${endDate.toLocaleTimeString([], options)}`;
};

const formatDateTime = (value) => {
  if (!value) return "N/A";
  return new Date(value).toLocaleString();
};

const resetBookingFlow = () => {
  bookingDates.value = [];
  bookingSessions.value = [];
  selectedProviderId.value = null;
  selectedDate.value = null;
  selectedSession.value = null;
  bookingStepIndex.value = 0;
  bookingFull.value = false;
  isUpdatingBooking.value = false;
  hasAlternativeSessions.value = false;
};

const evaluateAlternativeSessions = async (courseId) => {
  hasAlternativeSessions.value = false;
  if (!courseId || !currentBookingSessionId.value) {
    return;
  }

  checkingAlternatives.value = true;
  try {
    const datesRes = await api.get(`/lms/me/courses/${courseId}/booking/dates`);
    const dates = datesRes.data?.dates ?? [];

    for (const entry of dates) {
      const res = await api.get(`/lms/me/courses/${courseId}/booking/sessions`, {
        params: { date: entry.date },
      });
      const sessions = res.data?.sessions ?? [];
      const hasAlternative = sessions.some(
        (session) =>
          session.id !== currentBookingSessionId.value &&
          session.status === "open" &&
          session.seats_available > 0
      );
      if (hasAlternative) {
        hasAlternativeSessions.value = true;
        break;
      }
    }
  } catch (err) {
    hasAlternativeSessions.value = false;
  } finally {
    checkingAlternatives.value = false;
  }
};

const fetchBookingMetadata = async (courseId) => {
  bookingLoading.value = true;
  try {
    const res = await api.get(`/lms/me/courses/${courseId}/booking/metadata`);
    bookingMeta.value = res.data ?? null;
    bookingDates.value = res.data?.next_available_dates ?? [];
    if (currentBooking.value) {
      await evaluateAlternativeSessions(courseId);
    } else {
      hasAlternativeSessions.value = false;
      isUpdatingBooking.value = false;
    }
    if (isScheduled.value) {
      await fetchBookingDates();
    }
  } catch (err) {
    toastError(toast, "Matrices", "Unable to load booking metadata.");
  } finally {
    bookingLoading.value = false;
  }
};

const fetchBookingDates = async () => {
  if (!courseDetail.value?.id) return;
  bookingDatesLoading.value = true;
  try {
    const res = await api.get(`/lms/me/courses/${courseDetail.value.id}/booking/dates`, {
      params: {
        provider_id: selectedProviderId.value || undefined,
      },
    });
    bookingDates.value = res.data?.dates ?? [];
  } catch (err) {
    toastError(toast, "Matrices", "Unable to load available dates.");
  } finally {
    bookingDatesLoading.value = false;
  }
};

const fetchBookingSessions = async () => {
  if (!courseDetail.value?.id) return;
  const dateParam = formatDateParam(selectedDate.value);
  if (!dateParam) return;
  bookingSessionsLoading.value = true;
  try {
    const res = await api.get(`/lms/me/courses/${courseDetail.value.id}/booking/sessions`, {
      params: {
        date: dateParam,
        provider_id: selectedProviderId.value || undefined,
      },
    });
    bookingSessions.value = res.data?.sessions ?? [];
  } catch (err) {
    toastError(toast, "Matrices", "Unable to load sessions.");
  } finally {
    bookingSessionsLoading.value = false;
  }
};

const nextBookingStep = () => {
  if (!canAdvanceStep.value) return;
  if (bookingStepIndex.value < bookingSteps.value.length - 1) {
    bookingStepIndex.value += 1;
  }
};

const prevBookingStep = () => {
  if (bookingStepIndex.value > 0) {
    bookingStepIndex.value -= 1;
  }
};

const startUpdateBooking = async () => {
  if (!currentBooking.value?.session?.id) {
    return;
  }

  isUpdatingBooking.value = true;
  bookingStepIndex.value = 0;
  bookingFull.value = false;
  holdBookingSelection.value = true;
  selectedProviderId.value = currentBooking.value?.session?.provider?.id ?? null;
  selectedDate.value = currentBooking.value?.session?.starts_at
    ? new Date(currentBooking.value.session.starts_at)
    : null;
  selectedSession.value = null;
  await nextTick();
  holdBookingSelection.value = false;
};

const stopUpdateBooking = () => {
  isUpdatingBooking.value = false;
  bookingStepIndex.value = 0;
  bookingFull.value = false;
  selectedProviderId.value = null;
  selectedDate.value = null;
  selectedSession.value = null;
};

const updateBooking = async () => {
  const courseId = courseDetail.value?.id;
  const sessionId = selectedSession.value?.id;
  if (!courseId || !sessionId) return;
  bookingActionLoading.value = true;
  bookingFull.value = false;
  try {
    await api.post(`/lms/me/courses/${courseId}/booking/update`, {
      course_session_id: sessionId,
    });
    toastSuccess(toast, "Matrices", "Booking updated.");
    await fetchBookingMetadata(courseId);
    await fetchCourses(selectedJobRoleId.value, selectedCategoryId.value);
    isUpdatingBooking.value = false;
  } catch (err) {
    if (err.response?.status === 409) {
      bookingFull.value = true;
    } else {
      toastError(toast, "Matrices", "Unable to update the booking.");
    }
  } finally {
    bookingActionLoading.value = false;
  }
};

const confirmBooking = async () => {
  if (isUpdatingBooking.value && currentBooking.value) {
    await updateBooking();
    return;
  }
  const courseId = courseDetail.value?.id;
  const sessionId = selectedSession.value?.id;
  if (!courseId || !sessionId) return;
  bookingActionLoading.value = true;
  bookingFull.value = false;
  try {
    await api.post(`/lms/me/courses/${courseId}/booking`, {
      course_session_id: sessionId,
      enrollment_type_context: selectedCourse.value?.type,
    });
    toastSuccess(toast, "Matrices", "Booking confirmed.");
    await fetchBookingMetadata(courseId);
    await fetchCourses(selectedJobRoleId.value, selectedCategoryId.value);
  } catch (err) {
    if (err.response?.status === 409) {
      bookingFull.value = true;
    } else {
      toastError(toast, "Matrices", "Unable to book the session.");
    }
  } finally {
    bookingActionLoading.value = false;
  }
};

const joinWaitlist = async () => {
  const courseId = courseDetail.value?.id;
  const sessionId = selectedSession.value?.id;
  if (!courseId || !sessionId) return;
  bookingActionLoading.value = true;
  try {
    await api.post(`/lms/me/courses/${courseId}/waitlist`, {
      course_session_id: sessionId,
    });
    toastSuccess(toast, "Matrices", "Added to waitlist.");
  } catch (err) {
    toastError(toast, "Matrices", "Unable to join the waitlist.");
  } finally {
    bookingActionLoading.value = false;
  }
};

const cancelBooking = async () => {
  const bookingId = currentBooking.value?.id;
  if (!bookingId) return;
  bookingActionLoading.value = true;
  try {
    await api.post(`/lms/me/bookings/${bookingId}/cancel`);
    toastSuccess(toast, "Matrices", "Booking canceled.");
    resetBookingFlow();
    await fetchBookingMetadata(courseDetail.value?.id);
    await fetchCourses(selectedJobRoleId.value, selectedCategoryId.value);
  } catch (err) {
    toastError(toast, "Matrices", "Unable to cancel the booking.");
  } finally {
    bookingActionLoading.value = false;
  }
};

const fetchFilters = async () => {
  loadingFilters.value = true;
  try {
    const res = await api.get("/lms/me/matrices/filters");
    jobFamilies.value = res.data?.job_families ?? [];
    defaultJobFamilyId.value = res.data?.default_job_family_id ?? null;
    defaultJobRoleId.value = res.data?.default_job_role_id ?? null;
    console.debug("[Matrices] filters loaded", {
      jobFamilies: jobFamilies.value,
      defaultJobFamilyId: defaultJobFamilyId.value,
      defaultJobRoleId: defaultJobRoleId.value,
    });
  } finally {
    loadingFilters.value = false;
  }
};

const fetchCategories = async (jobFamilyId) => {
  loadingCategories.value = true;
  console.debug("[Matrices] fetch categories", {
    url: "/lms/me/matrices/categories",
    params: { job_family_id: jobFamilyId },
  });
  try {
    const res = await api.get("/lms/me/matrices/categories", {
      params: { job_family_id: jobFamilyId },
    });
    categories.value = res.data?.categories ?? [];
    console.debug("[Matrices] categories loaded", {
      jobFamilyId,
      categories: categories.value,
    });
  } finally {
    loadingCategories.value = false;
  }
};

const fetchJobRoles = async (jobFamilyId) => {
  loadingJobRoles.value = true;
  console.debug("[Matrices] fetch job roles", {
    url: "/lms/me/matrices/job-roles",
    params: { job_family_id: jobFamilyId },
  });
  try {
    const res = await api.get("/lms/me/matrices/job-roles", {
      params: { job_family_id: jobFamilyId },
    });
    jobRoles.value = res.data?.job_roles ?? [];
    console.debug("[Matrices] job roles loaded", {
      jobFamilyId,
      jobRoles: jobRoles.value,
    });
  } finally {
    loadingJobRoles.value = false;
  }
};

const fetchCourses = async (jobRoleId, categoryId) => {
  if (!jobRoleId) return;
  loadingCourses.value = true;
  try {
    const params = { job_role_id: jobRoleId };
    const normalizedCategoryId =
      categoryId === null || categoryId === "all" || categoryId === "null" || categoryId === ""
        ? null
        : categoryId;

    if (normalizedCategoryId !== null) {
      params.category_id = normalizedCategoryId;
    }

    console.debug("[Matrices] fetch courses", {
      url: "/lms/me/matrices/courses",
      params,
    });

    const res = await api.get("/lms/me/matrices/courses", { params });
    mandatoryCourses.value = res.data?.mandatory ?? [];
    optionalCourses.value = res.data?.optional ?? [];
  } finally {
    loadingCourses.value = false;
  }
};

const fetchCourseDetail = async (courseId) => {
  loadingCourseDetail.value = true;
  try {
    const res = await api.get(`/lms/me/courses/${courseId}`);
    courseDetail.value = res.data?.course ?? null;
  } finally {
    loadingCourseDetail.value = false;
  }
};

watch(bookingProviders, (providers) => {
  if (providers.length === 1) {
    selectedProviderId.value = providers[0].id;
  }
  if (!providers.find((provider) => provider.id === selectedProviderId.value)) {
    selectedProviderId.value = providers.length === 1 ? providers[0].id : null;
  }
});

watch(selectedProviderId, async () => {
  if (!holdBookingSelection.value) {
    selectedDate.value = null;
  }
  selectedSession.value = null;
  bookingSessions.value = [];
  bookingFull.value = false;
  if (!isScheduled.value) return;
  await fetchBookingDates();
});

watch(selectedDate, async () => {
  selectedSession.value = null;
  bookingSessions.value = [];
  bookingFull.value = false;
  if (!isScheduled.value) return;
  await fetchBookingSessions();
});

watch(selectedSession, () => {
  bookingFull.value = false;
});

watch(bookingSessions, (sessions) => {
  if (!isUpdatingBooking.value || selectedSession.value || !currentBookingSessionId.value) {
    return;
  }
  const match = (sessions || []).find((session) => session.id === currentBookingSessionId.value);
  if (match) {
    selectedSession.value = match;
  }
});

watch(bookingSteps, () => {
  if (bookingStepIndex.value >= bookingSteps.value.length) {
    bookingStepIndex.value = Math.max(bookingSteps.value.length - 1, 0);
  }
});

watch(selectedJobFamilyId, (value) => {
  console.debug("[Matrices] selectedJobFamilyId", value);
});

watch(selectedCategoryId, (value) => {
  console.debug("[Matrices] selectedCategoryId", value);
});

watch(selectedJobRoleId, (value) => {
  console.debug("[Matrices] selectedJobRoleId", value);
});

const openCourse = async (event) => {
  const course = event?.data;
  if (!course?.id) return;
  selectedCourse.value = course;
  try {
    resetBookingFlow();
    bookingMeta.value = null;
    await fetchCourseDetail(course.id);
    if (courseDetail.value?.delivery_type === "scheduled") {
      await fetchBookingMetadata(course.id);
    }
    showCourseDialog.value = true;
  } catch (err) {
    toastError(toast, "Matrices", "Unable to load course details.");
  }
};

const enroll = async () => {
  const courseId = courseDetail.value?.id;
  if (!courseId || isEnrolled.value || isScheduled.value) return;
  enrolling.value = true;
  try {
    await api.post(`/lms/me/courses/${courseId}/enroll`, {
      enrollment_type: selectedCourse.value?.type,
    });
    toastSuccess(toast, "Matrices", "Enrollment updated.");
    showCourseDialog.value = false;
    await fetchCourses(selectedJobRoleId.value, selectedCategoryId.value);
  } catch (err) {
    toastError(toast, "Matrices", "Unable to enroll in the course.");
  } finally {
    enrolling.value = false;
  }
};

onMounted(async () => {
  try {
    await fetchFilters();
    if (defaultJobFamilyId.value) {
      selectedJobFamilyId.value = defaultJobFamilyId.value;
    }
  } catch (err) {
    toastError(toast, "Matrices", "Unable to load filters.");
  }
});

watch(selectedJobFamilyId, async (jobFamilyId) => {
  selectedCategoryId.value = null;
  selectedJobRoleId.value = null;
  mandatoryCourses.value = [];
  optionalCourses.value = [];
  jobRoles.value = [];
  categories.value = [];

  if (!jobFamilyId) {
    return;
  }

  try {
    await fetchCategories(jobFamilyId);
    await fetchJobRoles(jobFamilyId);

    if (!selectedCategoryId.value && categories.value.some((category) => category.id === "all")) {
      selectedCategoryId.value = "all";
    }

    if (applyDefaultRole.value && defaultJobRoleId.value) {
      const defaultRole = jobRoles.value.find(
        (role) => role.id === defaultJobRoleId.value
      );
      if (defaultRole) {
        selectedJobRoleId.value = defaultRole.id;
      }
    }
  } catch (err) {
    toastError(toast, "Matrices", "Unable to load job roles or categories.");
  } finally {
    applyDefaultRole.value = false;
  }
});

watch(
  () => [selectedJobRoleId.value, selectedCategoryId.value],
  async ([jobRoleId, categoryId]) => {
    if (!jobRoleId) {
      mandatoryCourses.value = [];
      optionalCourses.value = [];
      return;
    }
    try {
      await fetchCourses(jobRoleId, categoryId);
    } catch (err) {
      toastError(toast, "Matrices", "Unable to load courses.");
    }
  }
);
</script>
