<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    LayoutDashboard,
    Monitor,
    MonitorPlay,
    ShieldCheck,
    Users,
} from 'lucide-vue-next';

defineProps<{
    canRegister?: boolean;
}>();
</script>

<template>
    <Head title="Bienvenido - Sistema de Colas" />

    <div
        class="min-h-screen bg-background text-foreground selection:bg-primary selection:text-primary-foreground"
    >
        <!-- Background Gradients -->
        <div class="fixed inset-0 -z-10 overflow-hidden">
            <div
                class="absolute -top-[30%] -left-[10%] h-[70%] w-[70%] rounded-full bg-primary/20 blur-[120px] filter"
            ></div>
            <div
                class="absolute top-[40%] -right-[10%] h-[60%] w-[60%] rounded-full bg-blue-500/10 blur-[120px] filter"
            ></div>
        </div>

        <!-- Header / Nav -->
        <header
            class="container mx-auto flex items-center justify-between px-6 py-6"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-lg"
                >
                    <Activity class="h-6 w-6" />
                </div>
                <span class="text-xl font-bold tracking-tight"
                    >SakNoel Colas</span
                >
            </div>

            <nav>
                <Link v-if="$page.props.auth.user" :href="dashboard()">
                    <Button>Ir al Panel</Button>
                </Link>
                <div v-else class="flex items-center gap-4">
                    <Link :href="login()">
                        <Button variant="ghost" class="font-medium">
                            Iniciar Sesión
                        </Button>
                    </Link>
                    <!-- Register button hidden as mostly internal app -->
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <main class="container mx-auto px-6 py-12 lg:py-24">
            <div class="flex flex-col items-center text-center">
                <div
                    class="mb-6 inline-flex items-center rounded-full border bg-background/50 px-3 py-1 text-sm font-medium backdrop-blur-sm"
                >
                    <span
                        class="mr-2 flex h-2 w-2 rounded-full bg-green-500"
                    ></span>
                    Sistema v2.0 Disponible
                </div>

                <h1
                    class="max-w-4xl text-5xl font-extrabold tracking-tight sm:text-6xl md:text-7xl"
                >
                    Gestión Inteligente de
                    <span
                        class="bg-gradient-to-r from-primary to-blue-600 bg-clip-text text-transparent"
                    >
                        Filas y Turnos
                    </span>
                </h1>

                <p
                    class="mt-6 max-w-2xl text-lg text-muted-foreground md:text-xl"
                >
                    Optimiza el flujo de clientes, reduce tiempos de espera y
                    mejora la experiencia de servicio con nuestra solución
                    integral de administración de colas.
                </p>

                <div class="mt-10 flex flex-col gap-4 sm:flex-row">
                    <Link :href="login()">
                        <Button size="lg" class="h-12 px-8 text-lg shadow-xl">
                            Comenzar Ahora
                        </Button>
                    </Link>
                    <!--Link href="#features">
                        <Button size="lg" variant="outline" class="h-12 px-8 text-lg">
                            Ver Características
                        </Button>
                    </Link-->
                </div>

                <!-- Hero Image / Preview Placeholder -->
                <div class="relative mt-16 w-full max-w-5xl">
                    <div
                        class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-primary to-blue-600 opacity-30 blur-lg transition duration-1000 group-hover:opacity-100 group-hover:duration-200"
                    ></div>
                    <div
                        class="relative overflow-hidden rounded-2xl border bg-card shadow-2xl"
                    >
                        <div
                            class="flex items-center gap-2 border-b bg-muted/50 px-4 py-2"
                        >
                            <div class="h-3 w-3 rounded-full bg-red-400"></div>
                            <div
                                class="h-3 w-3 rounded-full bg-yellow-400"
                            ></div>
                            <div
                                class="h-3 w-3 rounded-full bg-green-400"
                            ></div>
                        </div>
                        <div
                            class="flex aspect-video items-center justify-center bg-muted/20"
                        >
                            <!-- Placeholder for actual screenshot -->
                            <LayoutDashboard
                                class="h-24 w-24 text-muted-foreground/20"
                            />
                            <span
                                class="ml-4 font-medium text-muted-foreground/40"
                                >Panel de Control & Monitor TV</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Grid -->
            <div id="features" class="mt-32">
                <h2 class="text-center text-3xl font-bold tracking-tight">
                    Todo lo que necesitas para tu sucursal
                </h2>
                <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Feature 1 -->
                    <div
                        class="group relative rounded-2xl border bg-card p-8 transition-all hover:shadow-lg"
                    >
                        <div
                            class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400"
                        >
                            <MonitorPlay class="h-6 w-6" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold">
                            Pantalla TV (Turnos)
                        </h3>
                        <p class="text-muted-foreground">
                            Visualización clara de turnos llamados, videos
                            promocionales de YouTube y anuncios de voz (TTS).
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div
                        class="group relative rounded-2xl border bg-card p-8 transition-all hover:shadow-lg"
                    >
                        <div
                            class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400"
                        >
                            <LayoutDashboard class="h-6 w-6" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold">
                            Panel de Operador
                        </h3>
                        <p class="text-muted-foreground">
                            Interfaz intuitiva para cajeros y asesores. Llamar,
                            finalizar o derivar turnos con un solo clic.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div
                        class="group relative rounded-2xl border bg-card p-8 transition-all hover:shadow-lg"
                    >
                        <div
                            class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400"
                        >
                            <Monitor class="h-6 w-6" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold">
                            Kiosco de Autoservicio
                        </h3>
                        <p class="text-muted-foreground">
                            Generación de tickets táctil para clientes.
                            Selección de servicios prioritarios y normales.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div
                        class="group relative rounded-2xl border bg-card p-8 transition-all hover:shadow-lg"
                    >
                        <div
                            class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400"
                        >
                            <Users class="h-6 w-6" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold">
                            Gestión de Usuarios
                        </h3>
                        <p class="text-muted-foreground">
                            Administración completa de roles y permisos.
                            Controla quién accede a qué módulo del sistema.
                        </p>
                    </div>

                    <!-- Feature 5 -->
                    <div
                        class="group relative rounded-2xl border bg-card p-8 transition-all hover:shadow-lg"
                    >
                        <div
                            class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400"
                        >
                            <Activity class="h-6 w-6" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold">
                            Estadísticas en Vivo
                        </h3>
                        <p class="text-muted-foreground">
                            Gráficos de demanda, tiempos de espera promedio y
                            rendimiento de operadores en tiempo real.
                        </p>
                    </div>

                    <!-- Feature 6 -->
                    <div
                        class="group relative rounded-2xl border bg-card p-8 transition-all hover:shadow-lg"
                    >
                        <div
                            class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/20 dark:text-teal-400"
                        >
                            <ShieldCheck class="h-6 w-6" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold">Seguro y Robusto</h3>
                        <p class="text-muted-foreground">
                            Basado en Laravel y Vue.js. Sistema moderno,
                            escalable y con seguridad de grado empresarial.
                        </p>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div
                class="mt-32 rounded-3xl bg-primary px-6 py-16 text-center text-primary-foreground md:px-12"
            >
                <h2
                    class="mx-auto max-w-2xl text-3xl font-bold tracking-tight sm:text-4xl"
                >
                    ¿Listo para mejorar la atención?
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-lg opacity-90">
                    Únete a la nueva era de gestión de turnos. Configuración
                    rápida y uso sencillo.
                </p>
                <div class="mt-10 flex justify-center gap-4">
                    <Link :href="login()">
                        <Button
                            size="lg"
                            variant="secondary"
                            class="h-12 px-8 text-lg font-bold text-primary"
                        >
                            Acceder al Sistema
                        </Button>
                    </Link>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="mt-20 border-t bg-muted/20 py-12">
            <div
                class="container mx-auto px-6 text-center text-muted-foreground"
            >
                <div class="mb-4 flex justify-center gap-2">
                    <Activity class="h-6 w-6 text-primary" />
                    <span class="font-bold text-foreground">SakNoel Colas</span>
                </div>
                <p>
                    &copy; 2024 Sistema de Colas. Todos los derechos reservados.
                </p>
                <!--p class="mt-2 text-sm">
                    Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
                </p-->
            </div>
        </footer>
    </div>
</template>
