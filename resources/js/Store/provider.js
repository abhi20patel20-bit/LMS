import { defineStore } from "pinia";
import api from "@/api";
import { toastSuccess, toastError } from "@/composables/toastService";

const emptyForm = () => ({
  id: null,
  name: null,
  description: null,
});

export const useProviderStore = defineStore("provider", {
  state: () => ({
    providers: [],
    form: emptyForm(),
    loading: false,
    createModal: false,
    showEditModal: false,
    selectedProvider: null,
    errors: [],
  }),

  actions: {
    async fetchProviders() {
      this.loading = true;
      try {
        const res = await api.get("/get-providers");
        this.providers = res.data.providers;
      } finally {
        this.loading = false;
      }
    },

    async createProvider(toast) {
      try {
        const res = await api.post("/providers", this.form);
        toastSuccess(toast, "Provider Management", "Provider created");
        this.createModal = false;
        this.form = emptyForm();
        this.fetchProviders();
        return res;
      } catch (err) {
        if (err.response?.status === 422) {
          const rawErrors = err.response.data.errors || {};
          this.errors = Object.fromEntries(
            Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
          );
        } else {
          toastError(toast, "Provider Management", "Create failed");
          console.error("createProvider failed:", err);
        }
      }
    },

    async updateProvider(toast) {
      try {
        const res = await api.put(`/providers/${this.form.id}`, this.form);
        toastSuccess(toast, "Provider Management", "Provider updated");
        this.showEditModal = false;
        this.selectedProvider = null;
        this.form = emptyForm();
        this.fetchProviders();
        return res;
      } catch (err) {
        if (err.response?.status === 422) {
          const rawErrors = err.response.data.errors || {};
          this.errors = Object.fromEntries(
            Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
          );
        } else {
          toastError(toast, "Provider Management", "Update failed");
          console.error("updateProvider failed:", err);
        }
      }
    },

    async deleteProvider(id, toast) {
      try {
        const res = await api.delete(`/providers/${id}`);
        toastSuccess(toast, "Provider Management", "Provider deleted");
        this.fetchProviders();
        return res;
      } catch (err) {
        toastError(toast, "Provider Management", "Delete failed");
        console.error("deleteProvider failed:", err);
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

    openEditModal(provider) {
      this.form = { ...provider };
      this.errors = [];
      this.showEditModal = true;
      this.selectedProvider = provider;
    },

    closeEdit() {
      this.showEditModal = false;
      this.selectedProvider = null;
      this.errors = [];
      this.form = emptyForm();
    },
  },
});
