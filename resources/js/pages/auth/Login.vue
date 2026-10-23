<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Moon, ShieldCheck, Sun } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useAppearance } from '@/composables/useAppearance';
import { home } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const { resolvedAppearance, updateAppearance } = useAppearance();

const toggleTheme = () => {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
};
</script>

<template>
    <Head title="Connexion" />

    <div class="min-h-screen w-full lg:grid lg:grid-cols-2 bg-background text-foreground">
        <!-- LEFT COLUMN: Authentication Form -->
        <div class="flex min-h-screen flex-col justify-between p-6 sm:p-10 lg:p-14 xl:p-16">
            <!-- Top Bar: Logo & Theme Toggle -->
            <div class="flex items-center justify-between">
                <Link :href="home()" class="flex items-center gap-2 hover:opacity-90 transition-opacity">
                    <AppLogo />
                </Link>

                <!-- Theme Switcher Button -->
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-9 w-9 p-0 rounded-full text-muted-foreground hover:text-foreground"
                    :title="resolvedAppearance === 'dark' ? 'Passer au mode clair' : 'Passer au mode sombre'"
                    @click="toggleTheme"
                >
                    <Sun v-if="resolvedAppearance === 'dark'" class="h-4 w-4 text-amber-400" />
                    <Moon v-else class="h-4 w-4 text-slate-700" />
                    <span class="sr-only">Changer le thème</span>
                </Button>
            </div>

            <!-- Center: Login Form -->
            <div class="mx-auto w-full max-w-sm sm:max-w-md my-auto py-8 space-y-6">
                <!-- Titles & Badges -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <Badge variant="outline" class="text-[11px] bg-primary/10 text-primary border-primary/20">
                            Registre Médical SEP
                        </Badge>
                        <Badge variant="outline" class="text-[11px] bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300">
                            Service de Neurologie
                        </Badge>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-foreground">
                        Espace Praticien
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Veuillez renseigner vos identifiants sécurisés pour accéder aux dossiers cliniques
                    </p>
                </div>

                <!-- Status Flash Message -->
                <div
                    v-if="status"
                    class="rounded-lg border border-emerald-300 bg-emerald-50/80 p-3 text-center text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300"
                >
                    {{ status }}
                </div>

                <!-- Login Form -->
                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="space-y-5"
                >
                    <!-- Email Field -->
                    <div class="space-y-1.5">
                        <Label for="email" class="text-xs font-semibold">Adresse e-mail professionnelle</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            v-focus
                            :tabindex="1"
                            autocomplete="email"
                            placeholder="docteur@sonarya.med"
                            class="h-10 text-sm"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="text-xs font-semibold">Mot de passe</Label>
                            <TextLink
                                v-if="canResetPassword"
                                :href="request()"
                                class="text-xs text-primary hover:underline"
                                :tabindex="5"
                            >
                                Mot de passe oublié ?
                            </TextLink>
                        </div>
                        <PasswordInput
                            id="password"
                            name="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="h-10 text-sm"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center justify-between pt-1">
                        <Label for="remember" class="flex items-center gap-2 text-xs font-normal text-muted-foreground cursor-pointer">
                            <Checkbox id="remember" name="remember" :tabindex="3" />
                            <span>Mémoriser ma session sur ce poste</span>
                        </Label>
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        class="w-full h-10 text-sm font-semibold shadow-xs"
                        :tabindex="4"
                        :disabled="processing"
                        data-test="login-button"
                    >
                        <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                        <span>Se connecter au registre</span>
                    </Button>
                </Form>
            </div>

            <!-- Bottom: CNDP Legal Notice & Security Badge -->
            <div class="pt-6 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-muted-foreground">
                <div class="flex items-center gap-1.5">
                    <ShieldCheck class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <span>Conforme à la Loi 09-08 & CNDP</span>
                </div>
                <span>Service de Neurologie — GST TTA</span>
            </div>
        </div>

        <!-- RIGHT COLUMN: SONARYA-LOGIN-PAGE.svg Image -->
        <div class="relative hidden lg:flex h-full w-full items-center justify-center bg-slate-950 overflow-hidden border-l border-border/40 select-none">
            <img
                src="/images/SONARYA-LOGIN-PAGE.svg"
                alt="Illustration Sonarya Plateforme SEP"
                class="h-full w-full object-cover object-center"
            />
        </div>
    </div>
</template>
