import axios from "axios";

// Base axios instance
const api = axios.create({
    baseURL: "http://127.0.0.1:8000",
    withCredentials: true,
});

api.defaults.withCredentials = true;
api.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
api.defaults.xsrfCookieName = "XSRF-TOKEN";
api.defaults.xsrfHeaderName = "X-XSRF-TOKEN";

// Automatically fetch CSRF cookie before any POST/PUT/PATCH/DELETE request
api.interceptors.request.use(async (config) => {
    const requiresCsrf = ["post", "put", "patch", "delete"].includes(
        config.method
    );

    if (requiresCsrf) {
        // Only fetch the cookie if not already fetched
        if (!document.cookie.includes("XSRF-TOKEN")) {
            await api.get("/sanctum/csrf-cookie");
        }
    }

    return config;
});

export default api;
