<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Trophy,
    LogIn,
    LayoutDashboard,
    Menu,
    X,
    Home,
    Sparkles,
    Sun,
    Moon,
    ChevronRight,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useAppearance } from '@/composables/useAppearance';
import { login, dashboard, home } from '@/routes';

defineProps<{
    competitionName?: string;
    competitionSlug?: string;
}>();

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const mobileMenuOpen = ref(false);
const { appearance, updateAppearance } = useAppearance();

function toggleTheme() {
    if (appearance.value === 'dark') {
        updateAppearance('light');
    } else {
        updateAppearance('dark');
    }
}
</script>

<template>
    <header
        class="sticky top-0 z-50 w-full border-b border-border/60 bg-background/80 backdrop-blur-md transition-all"
    >
        <div
            class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
        >
            <!-- Brand Logo & Title -->
            <div class="flex items-center gap-3">
                <Link
                    :href="home()"
                    class="group flex items-center gap-2.5 rounded-lg px-1.5 py-1 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                >
                    <div
                        class="relative flex size-9 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-500/10 text-amber-500 shadow-sm transition-all group-hover:scale-105 group-hover:bg-amber-500/20"
                    >
                        <AppLogoIcon class="size-5 text-amber-500" />
                        <span class="absolute -top-0.5 -right-0.5 flex size-2">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"
                            ></span>
                            <span
                                class="relative inline-flex size-2 rounded-full bg-amber-500"
                            ></span>
                        </span>
                    </div>
                    <div class="flex flex-col">
                        <span
                            class="flex items-center gap-1 text-base font-bold tracking-tight text-foreground"
                        >
                            Arena Lomba
                            <Sparkles
                                class="hidden size-3 text-amber-500 sm:inline"
                            />
                        </span>
                        <span
                            class="-mt-1 hidden text-[10px] font-medium tracking-wider text-muted-foreground uppercase sm:block"
                            >Sistem Manajemen & Live Match</span
                        >
                    </div>
                </Link>

                <!-- Breadcrumb when inside competition page -->
                <div
                    v-if="competitionName"
                    class="ml-1 hidden items-center gap-2 border-l border-border pl-3 text-sm text-muted-foreground md:flex"
                >
                    <ChevronRight
                        class="size-4 shrink-0 text-muted-foreground/60"
                    />
                    <span
                        class="max-w-[200px] truncate font-medium text-foreground lg:max-w-[300px]"
                        >{{ competitionName }}</span
                    >
                </div>
            </div>

            <!-- Desktop Navigation Links -->
            <nav class="hidden items-center gap-6 md:flex">
                <Link
                    :href="home()"
                    class="flex items-center gap-1.5 py-1 text-sm font-medium transition-colors hover:text-primary"
                    :class="
                        page.url === '/'
                            ? 'font-semibold text-primary'
                            : 'text-muted-foreground'
                    "
                >
                    <Home class="size-4" />
                    <span>Beranda</span>
                </Link>

                <a
                    href="/#lomba-aktif"
                    class="flex items-center gap-1.5 py-1 text-sm font-medium text-muted-foreground transition-colors hover:text-primary"
                >
                    <Trophy class="size-4" />
                    <span>Lomba Aktif</span>
                </a>
            </nav>

            <!-- Right Actions Area (Desktop) -->
            <div class="hidden items-center gap-3 md:flex">
                <!-- Theme Toggle Button -->
                <button
                    type="button"
                    @click="toggleTheme"
                    class="inline-flex size-9 items-center justify-center rounded-lg border border-input bg-background transition-colors hover:bg-accent hover:text-accent-foreground focus:ring-2 focus:ring-primary focus:outline-none"
                    :title="
                        appearance === 'dark'
                            ? 'Beralih ke mode terang'
                            : 'Beralih ke mode gelap'
                    "
                >
                    <Sun class="hidden size-4 text-amber-500 dark:block" />
                    <Moon class="size-4 text-slate-700 dark:hidden" />
                    <span class="sr-only">Toggle Theme</span>
                </button>

                <!-- Auth Status Button -->
                <Link
                    v-if="authUser"
                    :href="dashboard()"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow transition-all hover:bg-primary/90 hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <LayoutDashboard class="size-4" />
                    <span>Dashboard Admin</span>
                </Link>
                <Link
                    v-else
                    :href="login()"
                    class="inline-flex items-center gap-2 rounded-lg border border-input bg-background px-4 py-2 text-sm font-semibold shadow-xs transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <LogIn class="size-4 text-primary" />
                    <span>Login Staf</span>
                </Link>
            </div>

            <!-- Mobile Menu Toggle Button -->
            <div class="flex items-center gap-2 md:hidden">
                <button
                    type="button"
                    @click="toggleTheme"
                    class="inline-flex size-9 items-center justify-center rounded-lg border border-input bg-background transition-colors hover:bg-accent hover:text-accent-foreground"
                    :title="
                        appearance === 'dark'
                            ? 'Beralih ke mode terang'
                            : 'Beralih ke mode gelap'
                    "
                >
                    <Sun class="hidden size-4 text-amber-500 dark:block" />
                    <Moon class="size-4 text-slate-700 dark:hidden" />
                </button>

                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="inline-flex size-9 items-center justify-center rounded-lg border border-input bg-background text-foreground transition-colors hover:bg-accent"
                    aria-label="Toggle navigation menu"
                >
                    <Menu v-if="!mobileMenuOpen" class="size-5" />
                    <X v-else class="size-5" />
                </button>
            </div>
        </div>

        <!-- Mobile Drawer / Menu Dropdown -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="mobileMenuOpen"
                class="space-y-3 border-b border-border/60 bg-background/95 px-4 py-4 shadow-lg backdrop-blur-xl md:hidden"
            >
                <div
                    v-if="competitionName"
                    class="flex items-center gap-2 rounded-lg bg-accent/50 p-2.5 text-xs text-muted-foreground"
                >
                    <Trophy class="size-4 shrink-0 text-amber-500" />
                    <span class="truncate font-medium text-foreground">{{
                        competitionName
                    }}</span>
                </div>

                <div class="space-y-1">
                    <Link
                        :href="home()"
                        @click="mobileMenuOpen = false"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors hover:bg-accent"
                        :class="
                            page.url === '/'
                                ? 'bg-primary/10 font-semibold text-primary'
                                : 'text-foreground'
                        "
                    >
                        <Home class="size-4 text-primary" />
                        <span>Beranda</span>
                    </Link>

                    <a
                        href="/#lomba-aktif"
                        @click="mobileMenuOpen = false"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-accent"
                    >
                        <Trophy class="size-4 text-amber-500" />
                        <span>Daftar Lomba</span>
                    </a>
                </div>

                <div class="border-t border-border pt-2">
                    <Link
                        v-if="authUser"
                        :href="dashboard()"
                        @click="mobileMenuOpen = false"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow"
                    >
                        <LayoutDashboard class="size-4" />
                        <span>Dashboard Admin</span>
                    </Link>
                    <Link
                        v-else
                        :href="login()"
                        @click="mobileMenuOpen = false"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-input bg-background px-4 py-2.5 text-sm font-semibold shadow-xs hover:bg-accent"
                    >
                        <LogIn class="size-4 text-primary" />
                        <span>Login Staf</span>
                    </Link>
                </div>
            </div>
        </transition>
    </header>
</template>
