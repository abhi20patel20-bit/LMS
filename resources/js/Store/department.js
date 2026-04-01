import { defineStore } from "pinia";
import api from "@/api";

export const useDepartmentStore = defineStore("department", {
  state: () => ({
    departments: [],
    loading: false,
    showEditModal: false,
    errors: {},
    form: {
      id: null,
      name: "",
      slug: "",
      custom_domain: "",
      subscription_type: "free",
      settings: null,
    },
  }),

  actions: {
    resetForm() {
      this.form = {
        id: null,
        name: "",
        slug: "",
        custom_domain: "",
        subscription_type: "free",
        settings: null,
      };
      this.errors = {};
    },

    async fetchDepartments() {
      this.loading = true;
      try {
        const res = await api.get("/get-departments");
        this.departments = res.data.departments;
      } finally {
        this.loading = false;
      }
    },

    async createDepartment() {
      this.errors = {};
      try {
        const res = await api.post("/department", this.form);
        await this.fetchDepartments();
        return res;
      } catch (err) {
        if (err.response?.status === 422) {
          const rawErrors = err.response.data.errors || {};
          this.errors = Object.fromEntries(
            Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
          );
        } else {
          console.error("createDepartment failed:", err);
        }
        throw err;
      }
    },

    openEditModal(department) {
      this.showEditModal = true;
      this.form = {
        id: department.id ?? null,
        name: department.name ?? "",
        slug: department.slug ?? "",
        custom_domain: department.custom_domain ?? "",
        subscription_type: department.subscription_type ?? "free",
        settings: department.settings ?? null,
      };
      this.errors = {};
    },

    async updateDepartment() {
      if (!this.form.id) return;

      this.errors = {};

      try {
        const res = await api.put(`/department/${this.form.id}`, this.form);
        await this.fetchDepartments();
        return res;
      } catch (err) {
        if (err.response?.status === 422) {
          const rawErrors = err.response.data.errors || {};
          this.errors = Object.fromEntries(
            Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
          );
        } else {
          console.error("updateDepartment failed:", err);
        }
        throw err;
      }
    },

    async removeDepartment(id) {
      try {
        const res = await api.delete(`/department/${id}`);
        this.departments = this.departments.filter((department) => department.id !== id);
        return res;
      } catch (err) {
        console.error("removeDepartment failed:", err);
        throw err;
      }
    },
  },
});
