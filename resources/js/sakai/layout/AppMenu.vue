<script setup>
import { ref } from 'vue';

import AppMenuItem from './AppMenuItem.vue';

const model = ref([
    {
        items: [
            { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/lms/dashboard', can: 'readUserDashboard' },
            { label: 'Profile', icon: 'pi pi-fw pi-user', to: '/profile' },
            { label: 'My Learning', icon: 'pi pi-fw pi-book', to: '/lms/learning', can: 'readMyLearning' },
        ]
    },
    {
        items: [{ label: 'Admin Dashboard', icon: 'pi pi-fw pi-home', to: '/dashboard', can: 'admin dashboard' }]
    },
    {
        // label: 'Master',
        items: [{ label: 'User Management', icon: 'pi pi-fw pi-user', to: '/user', can:'read users' }]
    },
    {
        // label: 'Master',
        items: [{ label: 'Department Management', icon: 'pi pi-sitemap', to: '/department', can:'read departments' }]
    },
    {
        // label: 'Master',
        items: [{ label: 'Company Management', icon: 'pi pi-building', to: '/company', can:'read companies' }]
    },
    {
        // label: 'Master',
        items: [
            { label: 'Job Role Management', icon: 'pi pi-briefcase', to: '/job-roles', can:'read job roles' },
            { label: 'Job Family Management', icon: 'pi pi-sitemap', to: '/job-families', can:'read job families' }
        ]
    },
    {
        // label: 'Master',
        items: [
            {
                label: 'Course Management',
                icon: 'pi pi-book',
                can:'read courses',
                items: [
                    { label: 'Courses', icon: 'pi pi-book', to: '/courses', can:'read courses' },
                    { label: 'Course Categories', icon: 'pi pi-tags', to: '/course-categories', can:'read course categories' },
                    { label: 'Provider Management', icon: 'pi pi-briefcase', to: '/providers', can:'read providers' }
                ]
            }
        ]
    },
    {
        // label: 'Master',
        items: [{ label: 'Matrices', icon: 'pi pi-table', to: '/matrices', can:'read metrics' }]
    },
    {
        // label: 'Setting',
        items: [
            { label: 'Role Management', icon: 'pi pi-fw pi-id-card', to: '/role', can:'read roles' },
            { label: 'Permission Management', icon: 'pi pi-fw pi-mobile', to: '/permission', class: 'rotated-icon', can:'read permissions' },
        ]
    },
]);
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
