<script setup lang="ts">
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
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { index as CallIndex } from '@/routes/calls';
import { index as callStatusesIndex } from '@/routes/callStatuses';
import { index as counterAssigmentsIndex } from '@/routes/counterAssignments';
import { index as countersIndex } from '@/routes/counters';
import { index as permissionsIndex } from '@/routes/permissions';
import { index as reportsIndex } from '@/routes/reports';
import { index as rolesIndex } from '@/routes/roles';
import { index as servicesIndex } from '@/routes/services';
import { edit as SistemaEdit } from '@/routes/settings';
import { index as tvIndex } from '@/routes/tv';
import { index as usersIndex } from '@/routes/users';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    FileText,
    Flag,
    Layers,
    LayoutGrid,
    Lock,
    Monitor,
    MonitorCog,
    Phone,
    Shield,
    Store,
    Tag,
    Users,
    Wrench,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const { can } = usePermissions();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Panel',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (can('assignments.ver')) {
        items.push({
            title: 'Asignación',
            href: counterAssigmentsIndex.url(),
            icon: Flag,
        });
    }

    if (can('calls.ver')) {
        items.push({
            title: 'Atención',
            href: CallIndex.url(),
            icon: Phone,
        });
    }

    if (can('counters.ver')) {
        items.push({
            title: 'Ventanillas',
            href: countersIndex.url(),
            icon: Monitor,
        });
    }

    if (can('call_statuses.ver')) {
        items.push({
            title: 'Estados de Atención',
            href: callStatusesIndex.url(),
            icon: Tag,
        });
    }

    if (can('services.ver')) {
        items.push({
            title: 'Servicios',
            href: servicesIndex.url(),
            icon: Layers,
        });
    }

    if (can('reportes.index')) {
        items.push({
            title: 'Reportes',
            href: reportsIndex.url(),
            icon: FileText,
        });
    }

    if (can('users.ver')) {
        items.push({
            title: 'Usuarios',
            href: usersIndex.url(),
            icon: Users,
        });
    }

    if (can('roles.ver')) {
        items.push({
            title: 'Roles',
            href: rolesIndex.url(),
            icon: Shield,
        });
    }

    if (can('permisos.ver')) {
        items.push({
            title: 'Permisos',
            href: permissionsIndex.url(),
            icon: Lock,
        });
    }

    if (can('settings.ver')) {
        items.push({
            title: 'Sistema',
            href: SistemaEdit.url(),
            icon: Wrench,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Monitor',
        href: tvIndex.url(),
        icon: MonitorCog,
    },
    {
        title: 'Kiosco',
        href: page.props.kiosk_url as string,
        icon: Store,
    },
];
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
