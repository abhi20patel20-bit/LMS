import { defineStore } from "pinia";
import api from "../api";

export const useRoleStore = defineStore("role", {
  state: () => ({
    /* ------------------ Role-related state ------------------ */
    roles: [],       // Companies with roles & permissions
    permissions: [],     // All permissions
    loading: false,
    showEditModal: false,
    editForm: {
        id: null,
        name: null,
        permissions: [],
    },        // For editing role
    errors: {},

    /* ------------------ Permission-related state ------------------ */
    showCreatePermissionModal: false,
    editPermissionForm: {
      id: null,
      name: "",
      guard_name: "web",
    },
    createPermissionForm: {
      name: "",
      guard_name: "web",
    },
  }),

  actions: {
    /* ------------------ Role Functions ------------------ */
    async fetchRoles() {
      this.loading = true;
      try {
        const data = await api.get("/get-roles");
        this.roles = data.data;
      } catch (err) {
        console.error("fetchCompaniesWithRoles failed:", err);
      } finally {
        this.loading = false;
      }
    },

    async fetchAllPermissions() {
      this.loading = true;
      try {
        const { data } = await api.get("/get-permissions");
        this.permissions = data.permissions;
      } catch (err) {
        console.error("fetchAllPermissions failed:", err);
      } finally {
        this.loading = false;
      }
    },

    async createRole(formData) {
      this.errors = {};
      try {
        const data = await api.post(`/role`, formData);
        return data;
      } catch (err) {
        if (err.response?.status === 422) {
          this.errors = Object.fromEntries(
            Object.entries(err.response.data.errors || {}).map(([k, v]) => [k, v[0]])
          );
          return err.response;
        } else {
          console.error("createRole failed:", err);
          throw err;
        }
      }
    },

    async updateRole() {
      this.errors = {};
      try {
        const data = await api.put(`/role/${this.editForm.id}`, this.editForm);
        return data;
      } catch (err) {
        if (err.response?.status === 422) {
          this.errors = Object.fromEntries(
            Object.entries(err.response.data.errors || {}).map(([k, v]) => [k, v[0]])
          );
          return err.response;
        } else {
          console.error("updateRole failed:", err);
          throw err;
        }
      }
    },

    async deleteRole(roleId) {
      try {
        await api.delete(`/role/${roleId}`);
        this.roles = this.roles.filter(r => r.id !== roleId);
      } catch (err) {
        console.error("deleteRole failed:", err);
      }
    },

    resetErrors() {
      this.errors = {};
    },

    resetEditForm() {
        this.editForm = {
            id: null,
            name: null,
            company_id: null,
            permissions: [],
        }
    },

    /* ------------------ Permission Functions ------------------ */
    resetCreatePermissionForm() {
      this.createPermissionForm = { name: "", guard_name: "web" };
      this.errors = {};
    },

    resetEditPermissionForm() {
      this.editPermissionForm = { id: null, name: "", guard_name: "web" };
      this.errors = {};
    },

    async createPermission() {
      this.errors = {};
      try {
        const data = await api.post("/permission", this.createPermissionForm);
        return data;
      } catch (err) {
        if (err.response?.status === 422) {
          this.errors = Object.fromEntries(
            Object.entries(err.response.data.errors || {}).map(([k, v]) => [k, v[0]])
          );
        } else console.error("createPermission failed:", err);
      }
    },

    async updatePermission() {
      if (!this.editPermissionForm.id) return;
      this.errors = {};
      try {
        const data = await api.put(
          `/permission/${this.editPermissionForm.id}`,
          this.editPermissionForm
        );
        return data;
      } catch (err) {
        if (err.response?.status === 422) {
          this.errors = Object.fromEntries(
            Object.entries(err.response.data.errors || {}).map(([k, v]) => [k, v[0]])
          );
        } else console.error("updatePermission failed:", err);
      }
    },

    async deletePermission(permissionId) {
      try {
        await api.delete(`/permissions/${permissionId}`);
        this.permissions = this.permissions.filter((p) => p.id !== permissionId);
      } catch (err) {
        console.error("deletePermission failed:", err);
      }
    },
  },
});
