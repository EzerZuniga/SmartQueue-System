import { InertiaLinkProps } from '@inertiajs/vue3';
import { type Updater } from '@tanstack/vue-table';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { type Ref } from 'vue';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function valueUpdater<T extends Updater<any>>(
    updaterOrValue: T,
    ref: Ref,
) {
    ref.value =
        typeof updaterOrValue === 'function'
            ? updaterOrValue(ref.value)
            : updaterOrValue;
}

export function urlIsActive(
    urlToCheck: NonNullable<InertiaLinkProps['href']>,
    currentUrl: string,
) {
    const href = toUrl(urlToCheck);

    // 1. Limpiamos los parámetros query (ej: ?page=2) para la comparación
    const cleanHref = href.split('?')[0];
    const cleanCurrent = currentUrl.split('?')[0];

    // 2. Si son idénticos, es activo (Dashboard usualmente cae aquí)
    if (cleanHref === cleanCurrent) {
        return true;
    }

    // 3. Si la URL actual empieza con el href + "/", es una sub-ruta (Create, Edit)
    // Usamos el "/" extra para evitar que "/users" active "/users-log" por error.
    if (cleanCurrent.startsWith(`${cleanHref}/`)) {
        return true;
    }

    return false;
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}
