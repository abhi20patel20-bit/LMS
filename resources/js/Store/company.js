import { defineStore } from "pinia";
import axiosClient from "../api";

export const useCompanyStore = defineStore("company", {
    state: () => ({
        companies: [],
        id: null,
        name: null,
        email: null,
        phone: null,
        type: null,
        settings: null,
        address: null,
        errors: {},
        processing: false,
        showEditModal: false,
    }),

    actions: {

        async fetchCompany() {
        try {
            const user = JSON.parse(localStorage.getItem('user'));
            this.id = user.company.id;
            this.name = user.company.name;
            this.type = user.company.type;
            this.settings = user.company.settings;
            this.email = user.company.email;
            this.phone = user.company.phone;
            this.address = user.company.address;

        } catch (error) {
            this.user = null;
        }
        },

        async updateCompany() {

            try {
                const data = await axiosClient.post(`/company-update/${this.id}`, {
                    id: this.id,
                    name: this.name,
                    email: this.email,
                    phone: this.phone,
                    address: this.address,
                    type: this.type,
                    settings: this.settings,
                });

                const storedUser = JSON.parse(localStorage.getItem("user"));

                if (storedUser) {
                    storedUser.company = data.data.company;   // <--- update company details
                    localStorage.setItem("user", JSON.stringify(storedUser));
                }


                return data;
            } catch (err) {
                if (err.response?.status === 422) {
                this.errors = Object.fromEntries(
                    Object.entries(err.response.data.errors || {}).map(([k, v]) => [k, v[0]])
                );
                } else console.error("Update company failed:", err);
            }
        },

        async fetchCompanies() {
            try {
                const data = await axiosClient.get(`/get-companies`);
                this.companies = data.data;
            } catch (err) {
                console.error("get companies failed:", err);
            }
        },

        // Reset form
        resetForm() {
            this.id = null;
            this.name = null;
            this.email = null;
            this.phone = null;
            this.address = null;
            this.type = null;
            this.settings = null;
            this.errors = {};
        },

        openEditModal (company) {
            this.showEditModal = true;
            this.id = company.id;
            this.name = company.name;
            this.email = company.email;
            this.phone = company.phone;
            this.address = company.address;
            this.type = company.type;
            this.settings = company.settings;
        },
    },
});
