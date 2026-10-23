<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { FileSpreadsheet, LayoutGrid, Moon, ShieldCheck, Sun, UserCheck, Users } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import { dashboard } from '@/routes';
import { index as patientsIndex } from '@/routes/patients';
import type { NavItem } from '@/types';

const { resolvedAppearance, updateAppearance } = useAppearance();

const toggleTheme = () => {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
};

const page = usePage();
const userRoles = computed<string[]>(() => (page.props.auth as any)?.user?.roles || []);
const userPermissions = computed<string[]>(() => (page.props.auth as any)?.user?.permissions || []);
const isAdmin = computed(() => userRoles.value.includes('admin'));
const canViewAudit = computed(() => isAdmin.value || userPermissions.value.includes('view activity logs'));
const canManageUsers = computed(() => isAdmin.value || userPermissions.value.includes('manage users'));

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Tableau de bord',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Dossiers Patients',
            href: patientsIndex(),
            icon: Users,
        },
        {
            title: 'Recherche & Cohortes',
            href: '/research',
            icon: FileSpreadsheet,
        },
    ];

    if (canViewAudit.value) {
        items.push({
            title: "Journal d'Audit & CNDP",
            href: '/audit-trail',
            icon: ShieldCheck,
        });
    }

    if (canManageUsers.value) {
        items.push({
            title: 'Utilisateurs & Rôles',
            href: '/users',
            icon: UserCheck,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />

            <!-- Theme Toggle (Light / Dark) -->
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="default"
                        :tooltip="resolvedAppearance === 'dark' ? 'Passer au mode clair' : 'Passer au mode sombre'"
                        class="cursor-pointer text-muted-foreground hover:text-foreground transition-colors"
                        @click="toggleTheme"
                    >
                        <Sun v-if="resolvedAppearance === 'dark'" class="size-4 text-amber-400 shrink-0" />
                        <Moon v-else class="size-4 text-slate-700 dark:text-slate-300 shrink-0" />
                        <span class="truncate">{{ resolvedAppearance === 'dark' ? 'Mode Clair' : 'Mode Sombre' }}</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
