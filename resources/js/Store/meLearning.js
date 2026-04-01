import { defineStore } from "pinia";
import api from "@/api";

const emptyDashboard = () => ({
  counts: {
    overdue: 0,
    dueSoon: 0,
    inProgress: 0,
    completed: 0,
  },
  lists: {
    overdue: [],
    dueSoon: [],
    inProgress: [],
    nextUp: [],
  },
});

const emptyLearning = () => ({
  required: [],
  in_progress: [],
  completed: [],
  optional: [],
});

export const useMeLearningStore = defineStore("meLearning", {
  state: () => ({
    dashboard: emptyDashboard(),
    learning: emptyLearning(),
    loadingDashboard: false,
    loadingLearning: false,
  }),

  actions: {
    async fetchDashboard() {
      this.loadingDashboard = true;
      try {
        const res = await api.get("/lms/me/dashboard");
        this.dashboard = res.data || emptyDashboard();
      } finally {
        this.loadingDashboard = false;
      }
    },

    async fetchLearning() {
      this.loadingLearning = true;
      try {
        const res = await api.get("/lms/me/learning");
        this.learning = res.data || emptyLearning();
      } finally {
        this.loadingLearning = false;
      }
    },

    async enroll(courseId) {
      const res = await api.post(`/lms/me/courses/${courseId}/enroll`);
      if (res?.data?.enrollment) {
        this.applyEnrollmentUpdate(res.data.enrollment);
        this.fetchDashboard();
      }
      return res;
    },

    async startCourse(courseId) {
      const res = await api.post(`/lms/me/courses/${courseId}/start`);
      if (res?.data?.enrollment) {
        this.applyEnrollmentUpdate(res.data.enrollment);
        this.fetchDashboard();
      }
      return res;
    },

    async completeCourse(courseId) {
      const res = await api.post(`/lms/me/courses/${courseId}/complete`);
      if (res?.data?.enrollment) {
        this.applyEnrollmentUpdate(res.data.enrollment);
        this.fetchDashboard();
      }
      return res;
    },

    async cancelCourse(courseId) {
      const res = await api.post(`/lms/me/courses/${courseId}/cancel`);
      if (res?.data?.enrollment) {
        this.applyEnrollmentUpdate(res.data.enrollment);
        this.fetchDashboard();
      }
      return res;
    },

    applyEnrollmentUpdate(enrollment) {
      const lists = this.learning;
      if (!lists) {
        return;
      }

      const courseId = enrollment?.course?.id ?? enrollment?.course_id;
      const enrollmentId = enrollment?.id;
      const matches = (item) =>
        item?.id === enrollmentId || item?.course?.id === courseId;

      lists.required = (lists.required || []).filter((item) => !matches(item));
      lists.in_progress = (lists.in_progress || []).filter((item) => !matches(item));
      lists.completed = (lists.completed || []).filter((item) => !matches(item));
      lists.optional = (lists.optional || []).filter((item) => !matches(item));

      if (
        enrollment?.enrollment_type === "mandatory" &&
        enrollment?.status !== "completed"
      ) {
        lists.required.push(enrollment);
      }

      if (enrollment?.status === "in_progress") {
        lists.in_progress.push(enrollment);
      }

      if (enrollment?.status === "completed") {
        lists.completed.push(enrollment);
      }

      if (enrollment?.enrollment_type === "optional") {
        lists.optional.push(enrollment);
      }
    },
  },
});
