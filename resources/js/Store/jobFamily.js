import { defineStore } from "pinia";
import api from "@/api";
import { toastSuccess, toastError, toastInfo } from "@/composables/toastService";

const emptyForm = () => ({
  id: null,
  name: null,
  description: null,
  company_id: null,
  course_ids: [],
  mandatory_course_ids: [],
  optional_course_ids: [],
});

export const useJobFamilyStore = defineStore("jobFamily", {
  state: () => ({
    jobFamilies: [],
    form: emptyForm(),
    loading: false,
    createModal: false,
    showEditModal: false,
    selectedJobFamily: null,
    errors: [],
  }),

  actions: {
    async fetchJobFamilies() {
      this.loading = true;
      try {
        const res = await api.get("/get-job-families");
        this.jobFamilies = res.data.jobFamilies;
      } finally {
        this.loading = false;
      }
    },

    async createJobFamily(toast) {
      try {
        const res = await api.post("/job-families", this.form);
        toastSuccess(toast, "Job Family Management", "Job family created");
        toastInfo(toast, "Job Family Management", "Requirements saved. Updating affected users…");
        this.createModal = false;
        this.form = emptyForm();
        this.fetchJobFamilies();
        return res;
      } catch (err) {
        if (err.response?.status === 422) {
          const rawErrors = err.response.data.errors || {};
          this.errors = Object.fromEntries(
            Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
          );
        } else {
          toastError(toast, "Job Family Management", "Create failed");
          console.error("createJobFamily failed:", err);
        }
      }
    },

    async updateJobFamily(toast) {
      try {
        const res = await api.put(`/job-families/${this.form.id}`, this.form);
        toastSuccess(toast, "Job Family Management", "Job family updated");
        toastInfo(toast, "Job Family Management", "Requirements saved. Updating affected users…");
        this.showEditModal = false;
        this.selectedJobFamily = null;
        this.form = emptyForm();
        this.fetchJobFamilies();
        return res;
      } catch (err) {
        if (err.response?.status === 422) {
          const rawErrors = err.response.data.errors || {};
          this.errors = Object.fromEntries(
            Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
          );
        } else {
          toastError(toast, "Job Family Management", "Update failed");
          console.error("updateJobFamily failed:", err);
        }
      }
    },

    async deleteJobFamily(id, toast) {
      try {
        const res = await api.delete(`/job-families/${id}`);
        toastSuccess(toast, "Job Family Management", "Job family deleted");
        this.fetchJobFamilies();
        return res;
      } catch (err) {
        toastError(toast, "Job Family Management", "Delete failed");
        console.error("deleteJobFamily failed:", err);
      }
    },

    openCreateModal() {
      this.createModal = true;
      this.errors = [];
      this.form = emptyForm();
    },

    closeCreate() {
      this.createModal = false;
      this.errors = [];
      this.form = emptyForm();
    },

    openEditModal(jobFamily) {
      const mandatoryIds = Array.isArray(jobFamily.courses)
        ? jobFamily.courses
            .filter((course) => course.pivot?.mandatory ?? true)
            .map((course) => course.id)
        : [];
      const optionalIds = Array.isArray(jobFamily.courses)
        ? jobFamily.courses
            .filter((course) => course.pivot && course.pivot.mandatory === false)
            .map((course) => course.id)
        : [];

      this.form = {
        ...emptyForm(),
        ...jobFamily,
        course_ids: Array.isArray(jobFamily.courses)
          ? jobFamily.courses.map((course) => course.id)
          : [],
        mandatory_course_ids: mandatoryIds,
        optional_course_ids: optionalIds,
      };
      this.errors = [];
      this.showEditModal = true;
      this.selectedJobFamily = jobFamily;
    },

    closeEdit() {
      this.showEditModal = false;
      this.selectedJobFamily = null;
      this.errors = [];
      this.form = emptyForm();
    },
  },
});
