<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Activity, ArrowRight, Brain, FileSpreadsheet, Lock, ShieldCheck } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
</script>

<template>
    <Head title="Accueil" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Top Navigation -->
        <header class="border-b border-border/60 bg-background/80 backdrop-blur-md sticky top-0 z-40">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link :href="dashboard()" class="flex items-center gap-2 hover:opacity-90 transition-opacity">
                    <AppLogo />
                </Link>

                <div class="flex items-center gap-3">
                    <Link v-if="$page.props.auth.user" :href="dashboard()">
                        <Button size="sm" class="gap-1.5 font-semibold">
                            <span>Accéder au Registre</span>
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </Link>
                    <Link v-else :href="login()">
                        <Button size="sm" class="gap-1.5 font-semibold">
                            <Lock class="h-3.5 w-3.5" />
                            <span>Espace Praticien</span>
                        </Button>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center py-10 px-4 sm:px-6 lg:px-8">
            <div class="w-full max-w-6xl overflow-hidden rounded-2xl border border-border/70 bg-card shadow-xl lg:grid lg:grid-cols-12">
                <!-- Left Details -->
                <div class="p-8 sm:p-12 lg:col-span-7 flex flex-col justify-between space-y-8">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge variant="outline" class="bg-primary/10 text-primary border-primary/20 text-xs">
                                Plateforme Clinique & Recherche
                            </Badge>
                            <Badge variant="outline" class="bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 text-xs">
                                Loi 09-08 & CNDP
                            </Badge>
                        </div>

                        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-foreground">
                            Registre Médical de la Sclérose en Plaques
                        </h1>

                        <p class="text-sm sm:text-base text-muted-foreground leading-relaxed">
                            Système hospitalier sécurisé pour la collecte standardisée, le suivi clinique longitudinal, la gestion des thérapeutiques et la recherche multicentrique sur la SEP.
                        </p>
                    </div>

                    <!-- Features Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="flex items-start gap-3 rounded-lg border border-border/50 p-3 bg-muted/30">
                            <Brain class="h-5 w-5 text-primary shrink-0 mt-0.5" />
                            <div>
                                <span class="font-semibold block text-foreground">Profil Phénotypique & EDSS</span>
                                <span class="text-muted-foreground">Classification McDonald 2017 et calcul automatique du score EDSS</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 rounded-lg border border-border/50 p-3 bg-muted/30">
                            <Activity class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                            <div>
                                <span class="font-semibold block text-foreground">Traitements & Rechutes</span>
                                <span class="text-muted-foreground">Suivi des poussées, tolérance DMD et escalades thérapeutiques</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 rounded-lg border border-border/50 p-3 bg-muted/30">
                            <FileSpreadsheet class="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                            <div>
                                <span class="font-semibold block text-foreground">Recherche & Export SPSS</span>
                                <span class="text-muted-foreground">Cohortes filtrables et extraction avec dictionnaire de variables</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 rounded-lg border border-border/50 p-3 bg-muted/30">
                            <ShieldCheck class="h-5 w-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
                            <div>
                                <span class="font-semibold block text-foreground">Traçabilité & CNDP</span>
                                <span class="text-muted-foreground">Journal d'audit horodaté et contrôle des accès basé sur les rôles</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="pt-2 flex flex-col sm:flex-row items-center gap-4">
                        <Link v-if="$page.props.auth.user" :href="dashboard()" class="w-full sm:w-auto">
                            <Button size="lg" class="w-full sm:w-auto font-semibold gap-2">
                                <span>Ouvrir le Registre</span>
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>
                        <Link v-else :href="login()" class="w-full sm:w-auto">
                            <Button size="lg" class="w-full sm:w-auto font-semibold gap-2">
                                <span>Connexion Praticien</span>
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>

                        <span class="text-xs text-muted-foreground">
                            Accès restreint au personnel médical autorisé
                        </span>
                    </div>
                </div>

                <!-- Right Brand Graphic -->
                <div class="bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 border-t lg:border-t-0 lg:border-l border-border/60 p-8 sm:p-12 lg:col-span-5 flex flex-col items-center justify-center text-center">
                    <div class="max-w-xs space-y-6 flex flex-col items-center">
                        <div class="h-44 w-44 sm:h-52 sm:w-52 rounded-2xl bg-white p-5 shadow-2xl flex items-center justify-center">
                            <img
                                src="/images/sonarya-logo.svg"
                                alt="Logo Sonarya"
                                class="h-full w-full object-contain"
                            />
                        </div>

                        <div class="space-y-1.5 text-white">
                            <div class="text-xl font-bold tracking-tight">Plateforme Sonarya</div>
                            <div class="text-xs text-slate-400">Service de Neurologie — GST TTA</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-border/60 py-4 text-center text-xs text-muted-foreground">
            <div class="mx-auto max-w-6xl px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span>Sonarya SEP — Plateforme Clinique & Épidémiologique</span>
                <span>Conforme à la Loi 09-08 relative à la protection des données à caractère personnel</span>
            </div>
        </footer>
    </div>
</template>
