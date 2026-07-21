<script setup lang="ts">
import { ref, computed } from 'vue';
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
    ChevronRight
} from '@lucide/vue';
import { login, dashboard, home } from '@/routes';
import { useAppearance } from '@/composables/useAppearance';

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
    <header class="sticky top-0 z-50 w-full border-b border-border/60 bg-background/80 backdrop-blur-md transition-all">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8 h-16">
            <!-- Brand Logo & Title -->
            <div class="flex items-center gap-3">
                <Link :href="home()" class="flex items-center gap-2.5 group focus:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded-lg py-1 px-1.5 transition-all">
                    <div class="relative flex items-center justify-center size-9 rounded-xl bg-amber-500/10 text-amber-500 border border-amber-500/20 group-hover:scale-105 group-hover:bg-amber-500/20 transition-all shadow-sm">
                        <Trophy class="size-5 text-amber-500" />
                        <span class="absolute -top-0.5 -right-0.5 flex size-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full size-2 bg-amber-500"></span>
                        </span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-base tracking-tight text-foreground flex items-center gap-1">
                            Arena Lomba
                            <Sparkles class="size-3 text-amber-500 hidden sm:inline" />
                        </span>
                        <span class="text-[10px] text-muted-foreground font-medium -mt-1 tracking-wider uppercase hidden sm:block">Sistem Manajemen & Live Match</span>
                    </div>
                </Link>

                <!-- Breadcrumb when inside competition page -->
                <div v-if="competitionName" class="hidden md:flex items-center gap-2 text-sm text-muted-foreground border-l border-border pl-3 ml-1">
                    <ChevronRight class="size-4 shrink-0 text-muted-foreground/60" />
                    <span class="font-medium text-foreground truncate max-w-[200px] lg:max-w-[300px]">{{ competitionName }}</span>
                </div>
            </div>

            <!-- Desktop Navigation Links -->
            <nav class="hidden md:flex items-center gap-6">
                <Link 
                    :href="home()" 
                    class="flex items-center gap-1.5 text-sm font-medium transition-colors hover:text-primary py-1"
                    :class="page.url === '/' ? 'text-primary font-semibold' : 'text-muted-foreground'"
                >
                    <Home class="size-4" />
                    <span>Beranda</span>
                </Link>
                
                <a 
                    href="/#lomba-aktif" 
                    class="flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-primary py-1"
                >
                    <Trophy class="size-4" />
                    <span>Lomba Aktif</span>
                </a>
            </nav>

            <!-- Right Actions Area (Desktop) -->
            <div class="hidden md:flex items-center gap-3">
                <!-- Theme Toggle Button -->
                <button
                    type="button"
                    @click="toggleTheme"
                    class="inline-flex items-center justify-center size-9 rounded-lg border border-input bg-background hover:bg-accent hover:text-accent-foreground transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                    :title="appearance === 'dark' ? 'Beralih ke mode terang' : 'Beralih ke mode gelap'"
                >
                    <Sun v-if="appearance === 'dark'" class="size-4 text-amber-400" />
                    <Moon v-else class="size-4 text-slate-700" />
                    <span class="sr-only">Toggle Theme</span>
                </button>

                <!-- Auth Status Button -->
                <Link
                    v-if="authUser"
                    :href="dashboard()"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary text-primary-foreground px-4 py-2 text-sm font-semibold shadow transition-all hover:bg-primary/90 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <LayoutDashboard class="size-4" />
                    <span>Dashboard Admin</span>
                </Link>
                <Link
                    v-else
                    :href="login()"
                    class="inline-flex items-center gap-2 rounded-lg border border-input bg-background px-4 py-2 text-sm font-semibold shadow-xs transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
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
                    class="inline-flex items-center justify-center size-9 rounded-lg border border-input bg-background hover:bg-accent hover:text-accent-foreground transition-colors"
                >
                    <Sun v-if="appearance === 'dark'" class="size-4 text-amber-400" />
                    <Moon v-else class="size-4 text-slate-700" />
                </button>

                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="inline-flex items-center justify-center size-9 rounded-lg border border-input bg-background text-foreground hover:bg-accent transition-colors"
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
                class="md:hidden border-b border-border/60 bg-background/95 backdrop-blur-xl px-4 py-4 space-y-3 shadow-lg"
            >
                <div v-if="competitionName" class="p-2.5 rounded-lg bg-accent/50 text-xs text-muted-foreground flex items-center gap-2">
                    <Trophy class="size-4 text-amber-500 shrink-0" />
                    <span class="font-medium text-foreground truncate">{{ competitionName }}</span>
                </div>

                <div class="space-y-1">
                    <Link
                        :href="home()"
                        @click="mobileMenuOpen = false"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors hover:bg-accent"
                        :class="page.url === '/' ? 'bg-primary/10 text-primary font-semibold' : 'text-foreground'"
                    >
                        <Home class="size-4 text-primary" />
                        <span>Beranda</span>
                    </Link>

                    <a
                        href="/#lomba-aktif"
                        @click="mobileMenuOpen = false"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-foreground transition-colors hover:bg-accent"
                    >
                        <Trophy class="size-4 text-amber-500" />
                        <span>Daftar Lomba</span>
                    </a>
                </div>

                <div class="pt-2 border-t border-border">
                    <Link
                        v-if="authUser"
                        :href="dashboard()"
                        @click="mobileMenuOpen = false"
                        class="flex items-center justify-center gap-2 w-full rounded-lg bg-primary text-primary-foreground px-4 py-2.5 text-sm font-semibold shadow"
                    >
                        <LayoutDashboard class="size-4" />
                        <span>Dashboard Admin</span>
                    </Link>
                    <Link
                        v-else
                        :href="login()"
                        @click="mobileMenuOpen = false"
                        class="flex items-center justify-center gap-2 w-full rounded-lg border border-input bg-background px-4 py-2.5 text-sm font-semibold shadow-xs hover:bg-accent"
                    >
                        <LogIn class="size-4 text-primary" />
                        <span>Login Staf</span>
                    </Link>
                </div>
            </div>
        </transition>
    </header>
</template>
