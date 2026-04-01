import { defineStore } from "pinia";
import api from "../api";

export const useUserStore = defineStore("user", {
  state: () => ({
    users: [],
    roles: [],
    jobRoles: [],
    companies: [],
    departments: [],
    loading: false,
    selectedUser: [],        // store the user being edited
    showEditModal: false,      // edit dialog visibility
    showSuspendDialog: false,
    suspendData: {
        user: {},
        status: 'active',
        until: null,
        reason: null,
    },     // edit dialog visibility
    form: {
        id: null,                // used for edit
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
        role: "",
        role_id: "",
        job_role_id: null,
        company: null,
        company_id: null,
        department_id: null,

    },
    errors: {},
  }),

  actions: {
    validateForm() {
      this.errors = {};

      // Name
      if (!this.form.name) {
        this.errors.name = "Name is required.";
      }

      // Email
      if (!this.form.email) {
        this.errors.email = "Email is required.";
      } else if (!/^\S+@\S+\.\S+$/.test(this.form.email)) {
        this.errors.email = "Invalid email format.";
      }

      // Password
      if (!this.form.password) {
        this.errors.password = "Password is required.";
      } else if (this.form.password.length < 6) {
        this.errors.password = "Password must be at least 6 characters.";
      }

      // Confirm password
      if (this.form.password !== this.form.password_confirmation) {
        this.errors.password_confirmation = "Passwords do not match.";
      }

      // Company
      if (!this.form.company_id) {
        this.errors.company_id = "Company is required.";
      }

      // Department
      if (!this.form.department_id) {
        this.errors.department_id = "Department is required.";
      }

      // Role
      if (!this.form.role) {
        this.errors.role = "Role is required.";
      }

      // Return true only if no errors
      return Object.keys(this.errors).length === 0;
    },
    // Fetch all users
    async fetchUsers() {
      this.loading = true;
      try {
        const data = await api.get(`/get-users`);
        this.users = data.data;
      } catch (err) {
        throw err;
      } finally {
        this.loading = false;
      }
    },

    // Fetch roles by company
    async getRoles() {
      this.loading = true;
      try {
        const { data } = await api.get(`/get-roles-dropdown`);
        this.roles = data;
      } catch (err) {
        console.error("getRoles failed:", err);
      } finally {
        this.loading = false;
      }
    },

    // Fetch companies
    async getCompanies() {
      this.loading = true;
      try {
        const { data } = await api.get("/get-companies-dropdown");
        this.companies = data;
      } catch (err) {
        console.error("getCompanies failed:", err);
      } finally {
        this.loading = false;
      }
    },

    async getDepartments() {
      this.loading = true;
      try {
        const { data } = await api.get("/get-departments");
        this.departments = data?.departments ?? data ?? [];
      } catch (err) {
        console.error("getDepartments failed:", err);
      } finally {
        this.loading = false;
      }
    },

    async getJobRoles() {
      this.loading = true;
      try {
        const { data } = await api.get("/get-job-roles");
        this.jobRoles = data?.jobRoles ?? [];
      } catch (err) {
        console.error("getJobRoles failed:", err);
      } finally {
        this.loading = false;
      }
    },

    async getUser(id) {
      try {
        const { data } = await api.get(`/get-user/${id}`);

        const roleName = data.roles?.[0]?.name ?? data.role ?? null;
        const roleId = data.roles?.[0]?.id ?? data.role_id ?? null;
        const normalizedRoleId = roleId !== null && roleId !== undefined ? Number(roleId) : null;

        this.form = {
          ...this.form,
          id: data.id ?? null,
          name: data.name ?? "",
          email: data.email ?? "",
          company_id: data.company_id ?? data.company?.id ?? null,
          department_id: data.department_id ?? data.department?.id ?? null,
          job_role_id: data.job_role_id ?? data.job_role?.id ?? null,
          role_id: normalizedRoleId ?? "",
          role: roleName ?? "",
          company: data.company?.name ?? null,
        };
      } catch (err) {
        console.error("getUser failed:", err);
      } finally {
        this.loading = false;
      }
    },

    // Create new user
    async createUser() {
      this.errors = {};
      if (!this.form.company_id || !this.form.department_id) return;
      try {
        const data = await api.post("/user", this.form);
        if (data.data?.users) {
          this.users = data.data.users;
        }
        return data;
      } catch (err) {
        if (err.response?.status === 422) {
          const rawErrors = err.response.data.errors || {};
          this.errors = Object.fromEntries(
            Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
          );
        } else {
          console.error("createUser failed:", err);
        }
      }
    },

    // Open edit modal with selected user
    openEditModal(user) {
        this.showEditModal = true;

        this.getUser(user.id);
        this.getRoles();
        this.getCompanies();
        this.getDepartments();
        this.getJobRoles();

        this.errors = {};
    },

    // Update existing user
    async updateUser() {
        if (!this.form.id) return;

        this.errors = {};

        try {
            const response = await api.put(`/user/${this.form.id}`, this.form);

            if (response.data?.users) {
                this.users = response.data.users;
            }
            return response;
        } catch (err) {
            if (err.response?.status === 422) {
            const rawErrors = err.response.data.errors || {};
            this.errors = Object.fromEntries(
                Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
            );
            } else {
            console.error("updateUser failed:", err);
            }
        }
    },

    // Delete user
    async removeUser(id) {
        try {
            const response = await api.delete(`/user/${id}`);
            this.users = this.users.filter((u) => u.id !== id);
            return response;
        } catch (err) {
            console.error("removeUser failed:", err);
        }
    },

    // Reset form
    resetForm() {
      this.form = {
        id: null,
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
        role: "",
        company_id: null,
        department_id: null,
        job_role_id: null,
      };
      this.errors = {};
    },

    openDialog(user) {
        this.suspendData = {
            user,
            status: "suspend",
            until: null,
            reason: "",
        };
        this.showSuspendDialog = true;
    },

    closeDialog() {
        this.showSuspendDialog = false;
        this.suspendData = {
            user: {},
            status: "active",
            until: null,
            reason: null,
        };
        this.fetchUsers();
    },

    async suspendUser() {
        if (!this.suspendData.user) return;

        this.loading = true;
        try {
            const res = await api.post(`/users/suspend`, this.suspendData);
            if (res.data?.users) {
                this.users = res.data.users;
            }
            return res;
        } catch (err) {
            console.error("Suspend failed:", err);
            throw err;
        } finally {
            this.loading = false;
        }
    },

  },
});
