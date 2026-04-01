import { defineStore } from "pinia";
import api from "@/api";
import { toastError } from "@/composables/toastService";

const emptyForm = () => ({
  id: null,
  title: null,
  description: null,
  price: null,
  status: "active",
  course_category_id: null,
  course_type: "online",
  duration: null,
  delivery_type: "self_paced",
  default_capacity: null,
  booking_required: false,
  provider_ids: [],
  settings: null,
});

export const useCourseStore = defineStore("course", {
  state: () => ({
    courses: [],
    form: emptyForm(),
    loading: false,
    createModal: false,
    showEditModal: false,
    selectedCourse: null,
    errors: [],
  }),

  actions: {
    async fetchCourses() {
      this.loading = true;
      try {
        const res = await api.get("/get-courses");
        this.courses = res.data.courses;
      } finally {
        this.loading = false;
      }
    },

    async createCourse() {
      try {
        const res = await api.post("/courses", this.form);
        return res;
      } catch (err) {
        if (err.response?.status === 422) {
          const rawErrors = err.response.data.errors || {};
          this.errors = Object.fromEntries(
            Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
          );
        } else {
          console.error("createCourse failed:", err);
        }
        throw err;
      }
    },

    async updateCourse() {
      try {
        const res = await api.put(`/courses/${this.form.id}`, this.form);
        return res;
      } catch (err) {
            if (err.response?.status === 422) {
            const rawErrors = err.response.data.errors || {};
            this.errors = Object.fromEntries(
                Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
            );
            } else {
            console.error("updateCourse failed:", err);
            }
            throw err;
        }
    },

    async deleteCourse(id) {
      try {
        const res = await api.delete(`/courses/${id}`);
        return res;
      } catch (err) {
        toastError("Delete failed");
        throw err;
      }
    },

    openCreateModal() {
      this.form = emptyForm();
      this.errors = [];
      this.createModal = true;
    },

    closeCreate() {
      this.form = emptyForm();
      this.errors = [];
      this.createModal = false;
    },

    openEditModal(course) {
      this.form = {
        ...this.form,
        ...course,
        course_category_id: course.course_category_id ?? course.category?.id ?? null,
        delivery_type: course.delivery_type ?? this.form.delivery_type,
        default_capacity: course.default_capacity ?? null,
        booking_required: !!course.booking_required,
        provider_ids: Array.isArray(course.providers)
          ? course.providers.map((provider) => provider.id)
          : [],
      };
      this.showEditModal = true;
    },

    closeEdit() {
      this.form = emptyForm();
      this.errors = [];
      this.showEditModal = false;
      this.selectedCourse = null;
    },
  },
});
