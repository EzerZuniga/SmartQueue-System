import { AppPageProps } from '@/shared/types';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const hasPermission = (permission: string): boolean => {
        const { auth } = usePage<AppPageProps>().props;
        const permissions = auth.user?.permissions || [];
        return permissions.includes(permission);
    };

    const hasRole = (role: string): boolean => {
        const { auth } = usePage<AppPageProps>().props;
        const roles = auth.user?.roles || [];
        return roles.includes(role);
    };

    return { can: hasPermission, hasRole };
}
