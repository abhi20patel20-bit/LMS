import { defineStore } from "pinia";
import api from "@/api";
import { toastSuccess, toastError } from "@/composables/toastService";

const emptyForm = () => ({
  id: null,
  name: null,
  description: null,
});

export const useCourseCategoryStore = defineStore("courseCategory", {
  state: () => ({
    categories: [],
    form: emptyForm(),
    loading: false,
    createModal: false,
    showEditModal: false,
    selectedCategory: null,
    errors: [],
  }),

  actions: {
    async fetchCourseCategories() {
      this.loading = true;
      try {
        const res = await api.get("/get-course-categories");
        this.categories = res.data.categories;
      } finally {
        this.loading = false;
      }
    },

    async createCourseCategory(toast) {
      try {
        const res = await api.post("/course-categories", this.form);
        toastSuccess(toast, "Course Category Management", "Category created");
        this.createModal = false;
        this.form = emptyForm();
        this.fetchCourseCategories();
        return res;
      } catch (err) {
        if (err.response?.status === 422) {
          const rawErrors = err.response.data.errors || {};
          this.errors = Object.fromEntries(
            Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
          );
        } else {
          toastError(toast, "Course Category Management", "Create failed");
          console.error("createCourseCategory failed:", err);
        }
      }
    },

    async updateCourseCategory(toast) {
      try {
        const res = await api.put(`/course-categories/${this.form.id}`, this.form);
        toastSuccess(toast, "Course Category Management", "Category updated");
        this.showEditModal = false;
        this.selectedCategory = null;
        this.form = emptyForm();
        this.fetchCourseCategories();
        return res;
      } catch (err) {
        if (err.response?.status === 422) {
          const rawErrors = err.response.data.errors || {};
          this.errors = Object.fromEntries(
            Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
          );
        } else {
          toastError(toast, "Course Category Management", "Update failed");
          console.error("updateCourseCategory failed:", err);
        }
      }
    },

    async deleteCourseCategory(id, toast) {
      try {
        const res = await api.delete(`/course-categories/${id}`);
        toastSuccess(toast, "Course Category Management", "Category deleted");
        this.fetchCourseCategories();
        return res;
      } catch (err) {
        toastError(toast, "Course Category Management", "Delete failed");
        console.error("deleteCourseCategory failed:", err);
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

    openEditModal(category) {
      this.form = { ...category };
      this.errors = [];
      this.showEditModal = true;
      this.selectedCategory = category;
    },

    closeEdit() {
      this.showEditModal = false;
      this.selectedCategory = null;
      this.errors = [];
      this.form = emptyForm();
    },
  },
});
