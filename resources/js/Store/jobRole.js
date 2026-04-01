import { defineStore } from "pinia";
import api from "@/api";
import { toastSuccess, toastError, toastInfo } from "@/composables/toastService";

const emptyForm = () => ({
  id: null,
  name: null,
  description: null,
  job_family_id: null,
  category_ids: [],
  course_ids: [],
  mandatory_course_ids: [],
  optional_course_ids: [],
});

export const useJobRoleStore = defineStore("jobRole", {
  state: () => ({
    jobRoles: [],
    form: emptyForm(),
    loading: false,
    createModal: false,
    showEditModal: false,
    selectedJobRole: null,
    errors: [],
  }),

  actions: {
    async fetchJobRoles() {
      this.loading = true;
      try {
        const res = await api.get("/get-job-roles");
        this.jobRoles = res.data.jobRoles;
      } finally {
        this.loading = false;
      }
    },

    async createJobRole(toast) {
      try {
        const res = await api.post("/job-roles", this.form);
        toastSuccess(toast, "Job Role Management", "Job role created");
        toastInfo(toast, "Job Role Management", "Requirements saved. Updating affected users…");
        this.createModal = false;
        this.form = emptyForm();
        this.fetchJobRoles();
        return res;
      } catch (err) {
        if (err.response?.status === 422) {
          const rawErrors = err.response.data.errors || {};
          this.errors = Object.fromEntries(
            Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
          );
        } else {
          toastError(toast, "Job Role Management", "Create failed");
          console.error("createJobRole failed:", err);
        }
      }
    },

    async updateJobRole(toast) {
      try {
        const res = await api.put(`/job-roles/${this.form.id}`, this.form);
        toastSuccess(toast, "Job Role Management", "Job role updated");
        toastInfo(toast, "Job Role Management", "Requirements saved. Updating affected users…");
        this.showEditModal = false;
        this.selectedJobRole = null;
        this.form = emptyForm();
        this.fetchJobRoles();
        return res;
      } catch (err) {
        if (err.response?.status === 422) {
          const rawErrors = err.response.data.errors || {};
          this.errors = Object.fromEntries(
            Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
          );
        } else {
          toastError(toast, "Job Role Management", "Update failed");
          console.error("updateJobRole failed:", err);
        }
      }
    },

    async deleteJobRole(id, toast) {
      try {
        const res = await api.delete(`/job-roles/${id}`);
        toastSuccess(toast, "Job Role Management", "Job role deleted");
        this.fetchJobRoles();
        return res;
      } catch (err) {
        toastError(toast, "Job Role Management", "Delete failed");
        console.error("deleteJobRole failed:", err);
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

    openEditModal(jobRole) {
      const categoryIds = Array.isArray(jobRole.courses)
        ? Array.from(new Set(jobRole.courses.map((course) => course.course_category_id ?? course.category?.id).filter(Boolean)))
        : [];

      this.form = {
        ...emptyForm(),
        ...jobRole,
        category_ids: categoryIds,
        course_ids: Array.isArray(jobRole.courses)
          ? jobRole.courses.map((course) => course.id)
          : [],
        mandatory_course_ids: Array.isArray(jobRole.courses)
          ? jobRole.courses.filter((course) => course.pivot?.mandatory).map((course) => course.id)
          : [],
        optional_course_ids: Array.isArray(jobRole.courses)
          ? jobRole.courses.filter((course) => course.pivot && !course.pivot.mandatory).map((course) => course.id)
          : [],
      };
      this.errors = [];
      this.showEditModal = true;
      this.selectedJobRole = jobRole;
    },

    closeEdit() {
      this.showEditModal = false;
      this.selectedJobRole = null;
      this.errors = [];
      this.form = emptyForm();
    },
  },
});
