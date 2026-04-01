<script setup>
import { computed } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Divider from "primevue/divider";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import Tag from "primevue/tag";

const props = defineProps({
  profile: {
    type: Object,
    default: () => ({}),
  },
  mandatoryCourses: {
    type: Array,
    default: () => [],
  },
  nonMandatoryCourses: {
    type: Array,
    default: () => [],
  },
  teamMembers: {
    type: Array,
    default: () => [],
  },
});

const profileName = computed(() => props.profile?.name ?? "");
const profileEmail = computed(() => props.profile?.email ?? "");
const profileSummary = computed(() => ({
  role: props.profile?.jobRole ?? props.profile?.role ?? "",
  department: props.profile?.department ?? "",
  company: props.profile?.company ?? "",
  status: props.profile?.status ?? "",
  joinedAt: props.profile?.joinedAt ?? "",
  emailVerifiedAt: props.profile?.emailVerifiedAt ?? "",
  location: props.profile?.location ?? "",
  phone: props.profile?.phone ?? "",
}));

const mandatoryCourses = computed(() => props.mandatoryCourses ?? []);
const nonMandatoryCourses = computed(() => props.nonMandatoryCourses ?? []);
const teamMembers = computed(() => props.teamMembers ?? []);

const hasJobRole = computed(() => Boolean(props.profile?.jobRoleId));
const totalCourses = computed(() => {
  if (!hasJobRole.value) {
    return "";
  }

  return mandatoryCourses.value.length + nonMandatoryCourses.value.length;
});
const mandatoryCount = computed(() => (hasJobRole.value ? mandatoryCourses.value.length : ""));
const nonMandatoryCount = computed(() => (hasJobRole.value ? nonMandatoryCourses.value.length : ""));

const formatDuration = (minutes) => {
  if (minutes == null) {
    return "";
  }

  return `${minutes} min`;
};

const enrollmentLabel = (status) => {
  switch (status) {
    case "completed":
      return "Completed";
    case "in_progress":
      return "In Progress";
    case "expired":
      return "Expired";
    case "not_started":
      return "Not Started";
    default:
      return "Not set";
  }
};

const enrollmentSeverity = (status) => {
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

const bookingLabel = (status) => {
  switch (status) {
    case "booked":
      return "Booked";
    case "waitlisted":
      return "Waitlisted";
    case "none":
      return "Not booked";
    default:
      return "Unknown";
  }
};

const bookingSeverity = (status) => {
  switch (status) {
    case "booked":
      return "success";
    case "waitlisted":
      return "warning";
    default:
      return "info";
  }
};

const formatBookingSummary = (summary) => {
  if (!summary) {
    return "";
  }

  const startsAt = summary.starts_at ? new Date(summary.starts_at).toLocaleString() : "";
  const endsAt = summary.ends_at ? new Date(summary.ends_at).toLocaleString() : "";
  const provider = summary.provider_name ? ` - ${summary.provider_name}` : "";
  const location = summary.location ? ` - ${summary.location}` : "";

  if (!startsAt && !endsAt && !provider && !location) {
    return "";
  }

  return [startsAt, endsAt].filter(Boolean).join(" to ") + provider + location;
};

const initials = computed(() => {
  const parts = profileName.value.trim().split(" ").filter(Boolean);
  if (!parts.length) return "";
  return parts
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join("");
});
</script>

<template>
  <app-layout>
    <div class="card space-y-6">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-4">
          <div
            class="h-16 w-16 rounded-full bg-surface-200 text-surface-900 dark:bg-surface-700 dark:text-surface-0 flex items-center justify-center text-lg font-semibold"
          >
            {{ initials }}
          </div>
          <div>
            <h2 class="text-2xl font-semibold text-surface-900 dark:text-surface-0">{{ profileName }}</h2>
            <p class="text-muted-color">{{ profileEmail }}</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
          <span class="px-3 py-1 rounded-full bg-surface-100 dark:bg-surface-800 text-muted-color">
            Department: {{ profileSummary.department }}
          </span>
          <span class="px-3 py-1 rounded-full bg-surface-100 dark:bg-surface-800 text-muted-color">
            Role: {{ profileSummary.role }}
          </span>
        </div>
      </div>

      <Divider />

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="space-y-3">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-muted-color">About</h3>
          <div class="space-y-2 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-muted-color">Role</span>
              <span class="font-medium text-surface-900 dark:text-surface-0">{{ profileSummary.role }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-muted-color">Department</span>
              <span class="font-medium text-surface-900 dark:text-surface-0">{{ profileSummary.department }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-muted-color">Company</span>
              <span class="font-medium text-surface-900 dark:text-surface-0">{{ profileSummary.company }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-muted-color">Status</span>
              <span class="font-medium text-surface-900 dark:text-surface-0">{{ profileSummary.status }}</span>
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-muted-color">Academics</h3>
          <div class="space-y-2 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-muted-color">Active Courses</span>
              <span class="font-medium text-surface-900 dark:text-surface-0">
                {{ totalCourses }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-muted-color">Mandatory</span>
              <span class="font-medium text-surface-900 dark:text-surface-0">{{ mandatoryCount }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-muted-color">Elective</span>
              <span class="font-medium text-surface-900 dark:text-surface-0">{{ nonMandatoryCount }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-muted-color">Joined</span>
              <span class="font-medium text-surface-900 dark:text-surface-0">{{ profileSummary.joinedAt }}</span>
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-muted-color">Contact</h3>
          <div class="space-y-2 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-muted-color">Email</span>
              <span class="font-medium text-surface-900 dark:text-surface-0">{{ profileEmail }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-muted-color">Email Verified</span>
              <span class="font-medium text-surface-900 dark:text-surface-0">{{ profileSummary.emailVerifiedAt }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-muted-color">Phone</span>
              <span class="font-medium text-surface-900 dark:text-surface-0">{{ profileSummary.phone }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-muted-color">Location</span>
              <span class="font-medium text-surface-900 dark:text-surface-0">{{ profileSummary.location }}</span>
            </div>
          </div>
        </div>
      </div>

      <Divider />

      <TabView>
        <TabPanel header="Matrices">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="course in mandatoryCourses"
              :key="course.id"
              class="p-4 border border-surface-200 dark:border-surface-800 rounded space-y-2"
            >
              <div class="flex items-start justify-between">
                <div>
                  <p class="text-base font-semibold text-surface-900 dark:text-surface-0">{{ course.title }}</p>
                </div>
                <span class="text-xs uppercase tracking-wide px-2 py-1 rounded bg-orange-50 text-orange-600">
                  Mandatory
                </span>
              </div>
              <p class="text-sm text-muted-color">Type: {{ course.courseType }}</p>
              <p class="text-sm text-muted-color">Duration: {{ formatDuration(course.duration) }}</p>
              <div class="flex flex-wrap gap-2">
                <Tag
                  :value="enrollmentLabel(course.enrollment_status)"
                  :severity="enrollmentSeverity(course.enrollment_status)"
                />
                <Tag
                  v-if="course.delivery_type === 'scheduled'"
                  :value="bookingLabel(course.booking_status)"
                  :severity="bookingSeverity(course.booking_status)"
                />
              </div>
              <p
                v-if="
                  course.delivery_type === 'scheduled' &&
                  course.booking_status === 'booked' &&
                  course.booking_summary
                "
                class="text-xs text-muted-color"
              >
                {{ formatBookingSummary(course.booking_summary) }}
              </p>
            </div>
          </div>
        </TabPanel>
        <TabPanel header="Non-matrices">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="course in nonMandatoryCourses"
              :key="course.id"
              class="p-4 border border-surface-200 dark:border-surface-800 rounded space-y-2"
            >
              <div class="flex items-start justify-between">
                <div>
                  <p class="text-base font-semibold text-surface-900 dark:text-surface-0">{{ course.title }}</p>
                </div>
                <span class="text-xs uppercase tracking-wide px-2 py-1 rounded bg-emerald-50 text-emerald-700">
                  Optional
                </span>
              </div>
              <p class="text-sm text-muted-color">Type: {{ course.courseType }}</p>
              <p class="text-sm text-muted-color">Duration: {{ formatDuration(course.duration) }}</p>
              <div class="flex flex-wrap gap-2">
                <Tag
                  :value="enrollmentLabel(course.enrollment_status)"
                  :severity="enrollmentSeverity(course.enrollment_status)"
                />
                <Tag
                  v-if="course.delivery_type === 'scheduled'"
                  :value="bookingLabel(course.booking_status)"
                  :severity="bookingSeverity(course.booking_status)"
                />
              </div>
              <p
                v-if="
                  course.delivery_type === 'scheduled' &&
                  course.booking_status === 'booked' &&
                  course.booking_summary
                "
                class="text-xs text-muted-color"
              >
                {{ formatBookingSummary(course.booking_summary) }}
              </p>
            </div>
          </div>
        </TabPanel>
        <TabPanel header="Team">
          <div class="space-y-3">
            <div
              v-for="member in teamMembers"
              :key="member.id"
              class="flex items-center justify-between border border-surface-200 dark:border-surface-800 rounded px-4 py-3"
            >
              <div>
                <p class="text-base font-semibold text-surface-900 dark:text-surface-0">{{ member.name }}</p>
                <p class="text-sm text-muted-color">{{ member.jobRole }}</p>
              </div>
              <span class="text-xs uppercase tracking-wide text-muted-color">
                {{ profileSummary.department }}
              </span>
            </div>
          </div>
        </TabPanel>
      </TabView>
    </div>
  </app-layout>
</template>
