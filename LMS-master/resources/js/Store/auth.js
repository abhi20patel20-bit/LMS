import { defineStore } from "pinia";
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
            // Initialize CSRF cookie first
            // await axiosClient.get("/sanctum/csrf-cookie");

            const response = await axiosClient.post("/login", { email, password, remember });
            this.user = response.data.user;
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
      try {
        await axiosClient.post("/logout");
        this.user = null;
      } catch (error) {
        console.error(error);
      } finally {
        this.processing = false;
      }
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
