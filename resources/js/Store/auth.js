import { defineStore } from "pinia";
import { router } from "@inertiajs/vue3";
import axiosClient from "@/api";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
    errors: {},
    processing: false,
  }),

  actions: {
    async login(email, password, remember = false) {
        this.processing = true;
        this.errors = {};

        try {

            const response = await axiosClient.post("/login", { email, password, remember });
            this.user = response.data.user;
            localStorage.setItem('user', JSON.stringify(this.user));
            return response;
        } catch (error) {
            // Handle validation or general errors
            this.errors =
            error.response?.data?.errors || { general: error.response?.data?.message || error.message };
            throw error;
        } finally {
            this.processing = false;
        }
    },

    async logout() {
        this.processing = true;
        return new Promise((resolve, reject) => {
            router.post(route("logout"), {}, {
                preserveScroll: true,
                onFinish: () => {
                    this.processing = false;
                    this.user = null;
                    localStorage.removeItem("user");
                    resolve();
                },
                onError: (errors) => {
                    this.processing = false;
                    reject(errors);
                },
            });
        });
    },

    async fetchUser() {
      try {
        const response = await axiosClient.get("/user");
        this.user = response.data;
      } catch (error) {
        this.user = null;
      }
    },
  },
});
