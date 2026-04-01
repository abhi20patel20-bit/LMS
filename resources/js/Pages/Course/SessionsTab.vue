<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h4 class="font-semibold text-surface-900 dark:text-surface-0">Sessions</h4>
                <p class="text-sm text-muted-color">Create and manage scheduled sessions.</p>
            </div>
            <Button
                label="Add Session"
                icon="pi pi-plus"
                @click="openCreate"
                :disabled="!isScheduled"
            />
        </div>

        <div v-if="!isScheduled" class="text-sm text-muted-color">
            Switch the course to scheduled delivery to manage sessions.
        </div>

        <DataTable
            v-else
            :value="sessions"
            :loading="loading"
            paginator
            :rows="6"
        >
            <Column field="starts_at" header="Starts">
                <template #body="{ data }">
                    {{ formatDateTime(data.starts_at) }}
                </template>
            </Column>
            <Column field="ends_at" header="Ends">
                <template #body="{ data }">
                    {{ formatDateTime(data.ends_at) }}
                </template>
            </Column>
            <Column header="Provider">
                <template #body="{ data }">
                    {{ data.provider?.name || "N/A" }}
                </template>
            </Column>
            <Column field="capacity" header="Capacity" />
            <Column field="booked_count" header="Booked" />
            <Column field="waitlist_count" header="Waitlist" />
            <Column field="status" header="Status" />
            <Column header="Action">
                <template #body="{ data }">
                    <div class="flex gap-2">
                        <Button icon="pi pi-pencil" outlined size="small" @click="openEdit(data)" />
                        <Button
                            icon="pi pi-trash"
                            outlined
                            size="small"
                            severity="danger"
                            @click="deleteSession(data)"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>

        <Dialog
            v-model:visible="showDialog"
            :header="editing ? 'Edit Session' : 'Create Session'"
            modal
            :style="{ width: '28rem' }"
        >
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label>Provider</label>
                    <Select
                        v-model="form.provider_id"
                        :options="providers"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select Provider"
                    />
                    <small v-if="errors.provider_id" class="text-red-500">{{ errors.provider_id }}</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label>Starts At</label>
                    <Calendar
                        v-model="form.starts_at"
                        showTime
                        hourFormat="24"
                        :showIcon="true"
                        placeholder="Select start date/time"
                    />
                    <small v-if="errors.starts_at" class="text-red-500">{{ errors.starts_at }}</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label>Ends At</label>
                    <Calendar
                        v-model="form.ends_at"
                        showTime
                        hourFormat="24"
                        :showIcon="true"
                        placeholder="Select end date/time"
                    />
                    <small v-if="errors.ends_at" class="text-red-500">{{ errors.ends_at }}</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label>Capacity</label>
                    <InputNumber v-model="form.capacity" :min="1" placeholder="Capacity" />
                    <small v-if="errors.capacity" class="text-red-500">{{ errors.capacity }}</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label>Status</label>
                    <Select v-model="form.status" :options="statusOptions" placeholder="Select status" />
                    <small v-if="errors.status" class="text-red-500">{{ errors.status }}</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label>Location</label>
                    <InputText v-model="form.location" placeholder="Location" />
                    <small v-if="errors.location" class="text-red-500">{{ errors.location }}</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label>Notes</label>
                    <Textarea v-model="form.notes" rows="3" autoResize placeholder="Notes" />
                    <small v-if="errors.notes" class="text-red-500">{{ errors.notes }}</small>
                </div>

                <div class="flex justify-end gap-2">
                    <Button label="Cancel" severity="secondary" @click="closeDialog" />
                    <Button label="Save" @click="saveSession" :loading="saving" />
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import Select from "primevue/select";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Textarea from "primevue/textarea";
import Calendar from "primevue/calendar";
import { useToast } from "primevue/usetoast";
import { toastError, toastSuccess } from "@/composables/toastService";
import api from "@/api";

const props = defineProps({
    courseId: {
        type: Number,
        default: null,
    },
    providers: {
        type: Array,
        default: () => [],
    },
    deliveryType: {
        type: String,
        default: "self_paced",
    },
    defaultCapacity: {
        type: Number,
        default: null,
    },
});

const toast = useToast();
const sessions = ref([]);
const loading = ref(false);
const saving = ref(false);
const showDialog = ref(false);
const editing = ref(false);
const errors = ref({});

const emptyForm = () => ({
    id: null,
    provider_id: null,
    starts_at: null,
    ends_at: null,
    capacity: props.defaultCapacity ?? null,
    status: "open",
    location: null,
    notes: null,
});

const form = ref(emptyForm());

const statusOptions = ["open", "closed", "cancelled"];

const isScheduled = computed(() => props.deliveryType === "scheduled");

const formatDateTime = (value) => {
    if (!value) return "N/A";
    return new Date(value).toLocaleString();
};

const fetchSessions = async () => {
    if (!props.courseId || !isScheduled.value) {
        sessions.value = [];
        return;
    }

    loading.value = true;
    try {
        const res = await api.get(`/courses/${props.courseId}/sessions`);
        sessions.value = res.data?.sessions ?? [];
    } catch (err) {
        toastError(toast, "Course Sessions", "Unable to load sessions.");
    } finally {
        loading.value = false;
    }
};

const openCreate = () => {
    editing.value = false;
    errors.value = {};
    form.value = emptyForm();
    showDialog.value = true;
};

const openEdit = (session) => {
    editing.value = true;
    errors.value = {};
    form.value = {
        id: session.id,
        provider_id: session.provider?.id ?? null,
        starts_at: session.starts_at ? new Date(session.starts_at) : null,
        ends_at: session.ends_at ? new Date(session.ends_at) : null,
        capacity: session.capacity ?? null,
        status: session.status ?? "open",
        location: session.location ?? null,
        notes: session.notes ?? null,
    };
    showDialog.value = true;
};

const closeDialog = () => {
    showDialog.value = false;
};

const deleteSession = async (session) => {
    if (!props.courseId || !session?.id) return;
    if (!confirm("Delete this session?")) return;

    try {
        await api.delete(`/courses/${props.courseId}/sessions/${session.id}`);
        toastSuccess(toast, "Course Sessions", "Session deleted.");
        fetchSessions();
    } catch (err) {
        toastError(toast, "Course Sessions", "Unable to delete session.");
    }
};

const saveSession = async () => {
    if (!props.courseId) return;
    saving.value = true;
    errors.value = {};

    const payload = {
        provider_id: form.value.provider_id,
        starts_at: form.value.starts_at ? new Date(form.value.starts_at).toISOString() : null,
        ends_at: form.value.ends_at ? new Date(form.value.ends_at).toISOString() : null,
        capacity: form.value.capacity,
        status: form.value.status,
        location: form.value.location,
        notes: form.value.notes,
    };

    try {
        if (editing.value && form.value.id) {
            await api.put(`/courses/${props.courseId}/sessions/${form.value.id}`, payload);
            toastSuccess(toast, "Course Sessions", "Session updated.");
        } else {
            await api.post(`/courses/${props.courseId}/sessions`, payload);
            toastSuccess(toast, "Course Sessions", "Session created.");
        }
        showDialog.value = false;
        fetchSessions();
    } catch (err) {
        if (err.response?.status === 422) {
            const rawErrors = err.response.data.errors || {};
            errors.value = Object.fromEntries(
                Object.entries(rawErrors).map(([key, val]) => [key, val[0]])
            );
        } else {
            toastError(toast, "Course Sessions", "Unable to save session.");
        }
    } finally {
        saving.value = false;
    }
};

watch(
    () => [props.courseId, props.deliveryType],
    () => {
        fetchSessions();
    }
);

onMounted(() => {
    fetchSessions();
});
</script>
