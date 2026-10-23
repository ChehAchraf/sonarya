<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    ArrowLeft,
    Baby,
    Check,
    Cigarette,
    FileText,
    HeartPulse,
    Loader2,
    MapPin,
    Phone,
    Ruler,
    Scale,
    Shield,
    ShieldCheck,
    User,
    UserPlus,
    Wine,
} from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes';
import {
    create as patientsCreate,
    index as patientsIndex,
    store as patientsStore,
} from '@/routes/patients';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tableau de bord',
                href: dashboard(),
            },
            {
                title: 'Dossiers Patients',
                href: patientsIndex(),
            },
            {
                title: 'Nouveau Patient',
                href: patientsCreate(),
            },
        ],
    },
});

type SelectOption = {
    value: string;
    label: string;
};

type Props = {
    suggested_dossier_number: string;
    genders: SelectOption[];
    statuses: SelectOption[];
    marital_statuses: SelectOption[];
};

const props = defineProps<Props>();

const form = useForm({
    dossier_number: props.suggested_dossier_number,
    status: 'active',
    last_name: '',
    first_name: '',
    cin: '',
    gender: '',
    birth_date: '',
    marital_status: '',
    children_count: null as number | null,
    phone: '',
    city: '',
    origin: '',
    address: '',
    profession: '',
    height: null as number | null,
    weight: null as number | null,
    education_level: '',
    health_coverage: [] as string[],
    tobacco_consumption: '',
    alcohol_consumption: '',
    pregnancies_count: null as number | null,
    deliveries_count: null as number | null,
    postpartum_info: '',
    family_history_sep: false,
    family_history_neurological: false,
    family_history_autoimmune: false,
    family_history_details: '',
    medical_history_notes: '',
    surgical_history_notes: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    referring_doctor: '',
    notes: '',
});

// Calcul en direct de l'IMC et interprétation clinique
const calculatedImc = computed<number | null>(() => {
    if (!form.height || !form.weight || Number(form.height) <= 0 || Number(form.weight) <= 0) {
        return null;
    }
    const heightInMeters = Number(form.height) / 100;
    const val = Number(form.weight) / (heightInMeters * heightInMeters);
    return Math.round(val * 10) / 10;
});

const imcClassification = computed(() => {
    const imc = calculatedImc.value;
    if (!imc) return null;
    if (imc < 18.5) {
        return { label: 'Insuffisance pondérale (< 18.5)', class: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30' };
    }
    if (imc < 25.0) {
        return { label: 'Corpulence normale (18.5 - 24.9)', class: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30' };
    }
    if (imc < 30.0) {
        return { label: 'Surpoids (25.0 - 29.9)', class: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30' };
    }
    if (imc < 35.0) {
        return { label: 'Obésité modérée Classe I (30.0 - 34.9)', class: 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/30' };
    }
    if (imc < 40.0) {
        return { label: 'Obésité sévère Classe II (35.0 - 39.9)', class: 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/30' };
    }
    return { label: 'Obésité morbide Classe III (≥ 40.0)', class: 'bg-rose-600/15 text-rose-800 dark:text-rose-300 border-rose-600/40 font-bold' };
});

const healthCoverageOptions = [
    { value: 'CNSS', label: 'CNSS (Caisse Nationale de Sécurité Sociale)' },
    { value: 'AMO', label: 'AMO (Assurance Maladie Obligatoire)' },
    { value: 'Sans couverture', label: 'Sans couverture (Paiement direct / RAMEDiste)' },
    { value: 'Autre', label: 'Autre (Assurance privée / Mutuelle)' },
];

const toggleHealthCoverage = (val: string) => {
    const idx = form.health_coverage.indexOf(val);
    if (idx === -1) {
        form.health_coverage.push(val);
    } else {
        form.health_coverage.splice(idx, 1);
    }
};

// Dynamic age calculation based on birth_date
const calculatedAge = computed<number | null>(() => {
    if (!form.birth_date) {
        return null;
    }
    const birth = new Date(form.birth_date);
    if (isNaN(birth.getTime())) {
        return null;
    }
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age >= 0 ? age : null;
});

const submit = () => {
    form.post(patientsStore.url(), {
        preserveScroll: true,
    });
};

const educationLevels = [
    'Analphabète',
    'Primaire',
    'Collégial',
    'Secondaire (Lycée)',
    'Supérieur / Universitaire',
    'Formation Professionnelle',
];

const commonCities = [
    'Tanger',
    'Tétouan',
    'Larache',
    'Al Hoceïma',
    'Chefchaouen',
    'Asilah',
    'Ouezzane',
    'Ksar El Kébir',
    'Rabat',
    'Casablanca',
    'Fès',
    'Autre ville',
];
</script>

<template>
    <Head title="Nouveau Dossier Patient" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 max-w-5xl mx-auto w-full">
        <!-- Top Navigation & Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <Button
                        as-child
                        variant="ghost"
                        size="icon"
                        class="size-8 text-muted-foreground hover:text-foreground shrink-0"
                    >
                        <Link :href="patientsIndex.url()" title="Retour aux dossiers">
                            <ArrowLeft class="size-4" />
                        </Link>
                    </Button>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">
                        Nouveau Dossier Patient
                    </h1>
                </div>
                <p class="text-sm text-muted-foreground mt-1 ml-10">
                    Registre SEP — Service de Neurologie (GST TTA). Tous les champs obligatoires sont annotés d'un astérisque.
                </p>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <Button
                    as-child
                    variant="outline"
                    size="sm"
                >
                    <Link :href="patientsIndex.url()">
                        Annuler
                    </Link>
                </Button>
                <Button
                    type="button"
                    size="sm"
                    class="gap-1.5"
                    :disabled="form.processing"
                    @click="submit"
                >
                    <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                    <UserPlus v-else class="size-4" />
                    <span>Créer le Dossier</span>
                </Button>
            </div>
        </div>

        <!-- Global Error Alert -->
        <div
            v-if="form.hasErrors"
            class="flex items-start gap-3 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive"
        >
            <AlertCircle class="size-5 shrink-0 mt-0.5" />
            <div>
                <p class="font-semibold">Veuillez corriger les erreurs ci-dessous avant d'enregistrer :</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5 text-xs">
                    <li v-for="(error, key) in form.errors" :key="key">
                        {{ error }}
                    </li>
                </ul>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Section 1: Identification Clinique & État Civil -->
            <Card>
                <CardHeader class="pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <User class="size-4" />
                            </div>
                            <div>
                                <CardTitle class="text-base font-semibold">
                                    Identification & État Civil
                                </CardTitle>
                                <CardDescription class="text-xs">
                                    Identifiants hospitaliers, état civil et données démographiques
                                </CardDescription>
                            </div>
                        </div>
                        <Badge variant="outline" class="font-mono text-xs">
                            Module 1
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-5">
                    <!-- Row 1: Dossier Number & Status -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label for="dossier_number" class="text-sm font-medium">
                                    N° Dossier / IPP <span class="text-destructive">*</span>
                                </Label>
                                <span class="text-[11px] text-muted-foreground font-mono">Format SEP-YYYY-XXXX / IPP</span>
                            </div>
                            <Input
                                id="dossier_number"
                                v-model="form.dossier_number"
                                placeholder="SEP-2026-0001"
                                class="font-mono uppercase"
                                required
                            />
                            <InputError :message="form.errors.dossier_number" />
                            <p class="text-[11px] text-muted-foreground">
                                Identifiant unique ou numéro IPP physique du patient au CHU.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="status" class="text-sm font-medium">
                                Statut du Patient <span class="text-destructive">*</span>
                            </Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="status" class="w-full">
                                    <SelectValue placeholder="Sélectionner le statut" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="item in statuses"
                                        :key="item.value"
                                        :value="item.value"
                                    >
                                        {{ item.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.status" />
                        </div>
                    </div>

                    <Separator />

                    <!-- Row 2: Nom, Prénom, Sexe -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="last_name" class="text-sm font-medium">
                                Nom de Famille <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="last_name"
                                v-model="form.last_name"
                                placeholder="ex: BENNANI"
                                class="uppercase"
                                required
                            />
                            <InputError :message="form.errors.last_name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="first_name" class="text-sm font-medium">
                                Prénom <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="first_name"
                                v-model="form.first_name"
                                placeholder="ex: Fatima"
                                required
                            />
                            <InputError :message="form.errors.first_name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="gender" class="text-sm font-medium">
                                Sexe <span class="text-destructive">*</span>
                            </Label>
                            <Select v-model="form.gender">
                                <SelectTrigger id="gender" class="w-full">
                                    <SelectValue placeholder="Choisir le sexe" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="item in genders"
                                        :key="item.value"
                                        :value="item.value"
                                    >
                                        {{ item.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.gender" />
                        </div>
                    </div>

                    <!-- Row 3: Date de naissance, Âge calculé, CIN, Situation familiale & Enfants -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label for="birth_date" class="text-sm font-medium">
                                    Date de Naissance <span class="text-destructive">*</span>
                                </Label>
                                <span
                                    v-if="calculatedAge !== null"
                                    class="text-xs font-semibold text-primary"
                                >
                                    {{ calculatedAge }} ans
                                </span>
                            </div>
                            <Input
                                id="birth_date"
                                v-model="form.birth_date"
                                type="date"
                                required
                            />
                            <InputError :message="form.errors.birth_date" />
                        </div>

                        <div class="space-y-2">
                            <Label for="cin" class="text-sm font-medium">
                                CIN (Carte d'Identité)
                            </Label>
                            <Input
                                id="cin"
                                v-model="form.cin"
                                placeholder="ex: KB123456"
                                class="uppercase font-mono"
                            />
                            <InputError :message="form.errors.cin" />
                        </div>

                        <div class="space-y-2">
                            <Label for="marital_status" class="text-sm font-medium">
                                Situation Familiale
                            </Label>
                            <Select v-model="form.marital_status">
                                <SelectTrigger id="marital_status" class="w-full">
                                    <SelectValue placeholder="Sélectionner..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="item in marital_statuses"
                                        :key="item.value"
                                        :value="item.value"
                                    >
                                        {{ item.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.marital_status" />
                        </div>

                        <div class="space-y-2">
                            <Label for="children_count" class="text-sm font-medium">
                                Nombre d'enfants
                            </Label>
                            <Input
                                id="children_count"
                                type="number"
                                min="0"
                                max="20"
                                :model-value="form.children_count ?? undefined"
                                @update:model-value="(val: string | number) => (form.children_count = val === '' ? null : Number(val))"
                                placeholder="0"
                            />
                            <InputError :message="form.errors.children_count" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Section 2: Coordonnées & Données Socio-Professionnelles -->
            <Card>
                <CardHeader class="pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <MapPin class="size-4" />
                            </div>
                            <div>
                                <CardTitle class="text-base font-semibold">
                                    Coordonnées & Socio-Professionnel
                                </CardTitle>
                                <CardDescription class="text-xs">
                                    Contacts, localisation et milieu socio-économique
                                </CardDescription>
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="phone" class="text-sm font-medium">
                                Téléphone de contact
                            </Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                placeholder="06 12 34 56 78"
                                class="font-mono"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="space-y-2">
                            <Label for="city" class="text-sm font-medium">
                                Ville de Résidence
                            </Label>
                            <Input
                                id="city"
                                v-model="form.city"
                                list="cities-list"
                                placeholder="ex: Tanger"
                            />
                            <datalist id="cities-list">
                                <option v-for="city in commonCities" :key="city" :value="city" />
                            </datalist>
                            <InputError :message="form.errors.city" />
                        </div>

                        <div class="space-y-2">
                            <Label for="origin" class="text-sm font-medium">
                                Origine Géographique
                            </Label>
                            <Input
                                id="origin"
                                v-model="form.origin"
                                placeholder="ex: Rif, Tanger-Tétouan, Gharb..."
                            />
                            <InputError :message="form.errors.origin" />
                            <p class="text-[11px] text-muted-foreground">
                                Région ou province d'origine familiale.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="profession" class="text-sm font-medium">
                                Profession
                            </Label>
                            <Input
                                id="profession"
                                v-model="form.profession"
                                placeholder="ex: Enseignant, Sans emploi..."
                            />
                            <InputError :message="form.errors.profession" />
                        </div>

                        <div class="space-y-2">
                            <Label for="education_level" class="text-sm font-medium">
                                Niveau d'Instruction
                            </Label>
                            <Select v-model="form.education_level">
                                <SelectTrigger id="education_level" class="w-full">
                                    <SelectValue placeholder="Sélectionner..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="level in educationLevels"
                                        :key="level"
                                        :value="level"
                                    >
                                        {{ level }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.education_level" />
                        </div>

                        <div class="space-y-2">
                            <Label for="address" class="text-sm font-medium">
                                Adresse Complète du Domicile
                            </Label>
                            <Input
                                id="address"
                                v-model="form.address"
                                placeholder="Quartier, rue, N° immeuble/maison..."
                            />
                            <InputError :message="form.errors.address" />
                        </div>
                    </div>

                    <Separator />

                    <!-- Paramètres Anthropométriques (Taille, Poids & Calcul IMC) -->
                    <div class="space-y-3 p-4 rounded-xl bg-muted/20 border border-border/80">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2">
                                <Scale class="size-4 text-primary" />
                                <span class="text-xs font-semibold text-foreground uppercase tracking-wider">
                                    Paramètres Anthropométriques & Calcul Automatique de l'IMC
                                </span>
                            </div>
                            <Badge v-if="calculatedImc" :class="['text-xs font-semibold px-2.5 py-0.5 border', imcClassification?.class]">
                                IMC : {{ calculatedImc }} kg/m² • {{ imcClassification?.label }}
                            </Badge>
                            <span v-else class="text-[11px] text-muted-foreground italic">
                                Saisir taille et poids pour calculer l'IMC
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
                            <!-- Taille -->
                            <div class="space-y-1.5">
                                <Label for="height" class="text-xs font-medium flex items-center gap-1.5">
                                    <Ruler class="size-3.5 text-muted-foreground" />
                                    <span>Taille (cm)</span>
                                </Label>
                                <Input
                                    id="height"
                                    type="number"
                                    step="0.5"
                                    min="40"
                                    max="250"
                                    :model-value="form.height ?? undefined"
                                    @update:model-value="(val: string | number) => (form.height = val === '' ? null : Number(val))"
                                    placeholder="ex: 172"
                                    class="h-9 text-xs"
                                />
                                <InputError :message="form.errors.height" />
                            </div>

                            <!-- Poids -->
                            <div class="space-y-1.5">
                                <Label for="weight" class="text-xs font-medium flex items-center gap-1.5">
                                    <Scale class="size-3.5 text-muted-foreground" />
                                    <span>Poids (kg)</span>
                                </Label>
                                <Input
                                    id="weight"
                                    type="number"
                                    step="0.5"
                                    min="20"
                                    max="300"
                                    :model-value="form.weight ?? undefined"
                                    @update:model-value="(val: string | number) => (form.weight = val === '' ? null : Number(val))"
                                    placeholder="ex: 68.5"
                                    class="h-9 text-xs"
                                />
                                <InputError :message="form.errors.weight" />
                            </div>

                            <!-- IMC Affiché -->
                            <div class="space-y-1.5">
                                <Label class="text-xs font-medium flex items-center gap-1.5 text-muted-foreground">
                                    <Activity class="size-3.5" />
                                    <span>Indice de Masse Corporelle (IMC)</span>
                                </Label>
                                <div class="h-9 px-3 rounded-md bg-background border border-input flex items-center justify-between text-xs font-mono font-semibold">
                                    <span v-if="calculatedImc" class="text-foreground">{{ calculatedImc }} <span class="text-[10px] font-normal text-muted-foreground">kg/m²</span></span>
                                    <span v-else class="text-muted-foreground font-normal text-[11px]">—</span>
                                    <span v-if="calculatedImc" class="text-[10px] font-sans font-medium text-muted-foreground">Calculé</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <Separator />

                    <!-- Couverture Sociale (Checkboxes) -->
                    <div class="space-y-2.5">
                        <Label class="text-sm font-medium block">
                            Couverture Médicale / Assurance Sociale
                        </Label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div
                                v-for="cov in healthCoverageOptions"
                                :key="cov.value"
                                class="flex items-center gap-2.5 p-2.5 rounded-lg border border-border/70 hover:bg-muted/40 transition-colors cursor-pointer"
                                @click="toggleHealthCoverage(cov.value)"
                            >
                                <Checkbox
                                    :id="`cov-${cov.value}`"
                                    :checked="form.health_coverage.includes(cov.value)"
                                    @update:checked="() => toggleHealthCoverage(cov.value)"
                                />
                                <Label :for="`cov-${cov.value}`" class="text-xs font-medium cursor-pointer flex-1">
                                    {{ cov.label }}
                                </Label>
                            </div>
                        </div>
                        <InputError :message="form.errors.health_coverage" />
                    </div>
                </CardContent>
            </Card>

            <!-- Section 3: Antécédents (Module 2) -->
            <Card>
                <CardHeader class="pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                <ShieldCheck class="size-4" />
                            </div>
                            <div>
                                <CardTitle class="text-base font-semibold">
                                    Module 2 : Antécédents Personnels & Familiaux
                                </CardTitle>
                                <CardDescription class="text-xs">
                                    Toxiques, gynéco-obstétricaux, familiaux (SEP) et médico-chirurgicaux
                                </CardDescription>
                            </div>
                        </div>
                        <Badge variant="outline" class="text-[11px]">
                            Module 2
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- 1. Antécédents Toxiques -->
                    <div class="space-y-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground block border-b border-border/60 pb-1">
                            1. Antécédents Toxiques
                        </span>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <div class="flex items-center gap-1.5">
                                    <Cigarette class="size-3.5 text-muted-foreground" />
                                    <Label for="tobacco_consumption" class="text-sm font-medium">
                                        Tabagisme
                                    </Label>
                                </div>
                                <Input
                                    id="tobacco_consumption"
                                    v-model="form.tobacco_consumption"
                                    placeholder="ex: Non-fumeur, Fumeur 10 PA, Sevré en 2021..."
                                />
                                <InputError :message="form.errors.tobacco_consumption" />
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center gap-1.5">
                                    <Wine class="size-3.5 text-muted-foreground" />
                                    <Label for="alcohol_consumption" class="text-sm font-medium">
                                        Consommation d'Alcool
                                    </Label>
                                </div>
                                <Input
                                    id="alcohol_consumption"
                                    v-model="form.alcohol_consumption"
                                    placeholder="ex: Non consommateur, Occasionnel..."
                                />
                                <InputError :message="form.errors.alcohol_consumption" />
                            </div>
                        </div>
                    </div>

                    <!-- 2. Antécédents Gynéco-Obstétricaux -->
                    <div class="space-y-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground block border-b border-border/60 pb-1">
                            2. Antécédents Gynéco-Obstétricaux
                        </span>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <div class="flex items-center gap-1.5">
                                    <Baby class="size-3.5 text-muted-foreground" />
                                    <Label for="pregnancies_count" class="text-sm font-medium">
                                        Nombre de Grossesses (Gravidité)
                                    </Label>
                                </div>
                                <Input
                                    id="pregnancies_count"
                                    type="number"
                                    min="0"
                                    max="20"
                                    :model-value="form.pregnancies_count ?? undefined"
                                    @update:model-value="(val: string | number) => (form.pregnancies_count = val === '' ? null : Number(val))"
                                    placeholder="0"
                                />
                                <InputError :message="form.errors.pregnancies_count" />
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center gap-1.5">
                                    <Baby class="size-3.5 text-muted-foreground" />
                                    <Label for="deliveries_count" class="text-sm font-medium">
                                        Nombre d'Accouchements (Parité)
                                    </Label>
                                </div>
                                <Input
                                    id="deliveries_count"
                                    type="number"
                                    min="0"
                                    max="20"
                                    :model-value="form.deliveries_count ?? undefined"
                                    @update:model-value="(val: string | number) => (form.deliveries_count = val === '' ? null : Number(val))"
                                    placeholder="0"
                                />
                                <InputError :message="form.errors.deliveries_count" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="postpartum_info" class="text-xs font-medium">
                                Informations relatives à la Grossesse & Post-Partum
                            </Label>
                            <Input
                                id="postpartum_info"
                                v-model="form.postpartum_info"
                                placeholder="ex: Poussée survenue 2 mois après le dernier accouchement, allaitement exclusif..."
                            />
                            <InputError :message="form.errors.postpartum_info" />
                        </div>
                    </div>

                    <!-- 3. Antécédents Familiaux (SEP, Neurologiques, Auto-immuns) -->
                    <div class="space-y-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground block border-b border-border/60 pb-1">
                            3. Antécédents Familiaux
                        </span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div
                                class="flex items-center gap-2.5 p-3 rounded-lg border border-border/70 hover:bg-muted/40 transition-colors cursor-pointer"
                                @click="form.family_history_sep = !form.family_history_sep"
                            >
                                <Checkbox
                                    id="family_history_sep"
                                    :checked="form.family_history_sep"
                                    @update:checked="(val: boolean) => (form.family_history_sep = val)"
                                />
                                <Label for="family_history_sep" class="text-xs font-semibold cursor-pointer text-foreground">
                                    Antécédent familial de SEP
                                </Label>
                            </div>

                            <div
                                class="flex items-center gap-2.5 p-3 rounded-lg border border-border/70 hover:bg-muted/40 transition-colors cursor-pointer"
                                @click="form.family_history_neurological = !form.family_history_neurological"
                            >
                                <Checkbox
                                    id="family_history_neurological"
                                    :checked="form.family_history_neurological"
                                    @update:checked="(val: boolean) => (form.family_history_neurological = val)"
                                />
                                <Label for="family_history_neurological" class="text-xs font-medium cursor-pointer">
                                    Autre maladie neurologique
                                </Label>
                            </div>

                            <div
                                class="flex items-center gap-2.5 p-3 rounded-lg border border-border/70 hover:bg-muted/40 transition-colors cursor-pointer"
                                @click="form.family_history_autoimmune = !form.family_history_autoimmune"
                            >
                                <Checkbox
                                    id="family_history_autoimmune"
                                    :checked="form.family_history_autoimmune"
                                    @update:checked="(val: boolean) => (form.family_history_autoimmune = val)"
                                />
                                <Label for="family_history_autoimmune" class="text-xs font-medium cursor-pointer">
                                    Maladie auto-immune (Lupus, Gougerot...)
                                </Label>
                            </div>
                        </div>

                        <div class="space-y-1.5 pt-1">
                            <Label for="family_history_details" class="text-xs font-medium">
                                Précisions sur les Antécédents Familiaux & Lien de Parenté
                            </Label>
                            <Input
                                id="family_history_details"
                                v-model="form.family_history_details"
                                placeholder="ex: Sœur atteinte de SEP RR depuis 2018, mère avec thyroïdite de Hashimoto..."
                            />
                            <InputError :message="form.errors.family_history_details" />
                        </div>
                    </div>

                    <!-- 4. Antécédents Médicaux & Chirurgicaux -->
                    <div class="space-y-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground block border-b border-border/60 pb-1">
                            4. Antécédents Médicaux & Chirurgicaux Personnels
                        </span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label for="medical_history_notes" class="text-xs font-medium">
                                    Antécédents Médicaux
                                </Label>
                                <Textarea
                                    id="medical_history_notes"
                                    v-model="form.medical_history_notes"
                                    rows="2"
                                    placeholder="HTA, diabète, dysthyroïdie, asthme, épilepsie... (ou Néant)"
                                />
                                <InputError :message="form.errors.medical_history_notes" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="surgical_history_notes" class="text-xs font-medium">
                                    Antécédents Chirurgicaux
                                </Label>
                                <Textarea
                                    id="surgical_history_notes"
                                    v-model="form.surgical_history_notes"
                                    rows="2"
                                    placeholder="Interventions chirurgicales antérieures et dates... (ou Néant)"
                                />
                                <InputError :message="form.errors.surgical_history_notes" />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Section 4: Médecin Référent, Urgence & Observations -->
            <Card>
                <CardHeader class="pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <HeartPulse class="size-4" />
                            </div>
                            <div>
                                <CardTitle class="text-base font-semibold">
                                    Référencement, Contact d'Urgence & Notes
                                </CardTitle>
                                <CardDescription class="text-xs">
                                    Réseau de soins, contact de confiance et remarques cliniques initiales
                                </CardDescription>
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="referring_doctor" class="text-sm font-medium">
                                Médecin Traitant / Référent
                            </Label>
                            <Input
                                id="referring_doctor"
                                v-model="form.referring_doctor"
                                placeholder="ex: Dr. Alami"
                            />
                            <InputError :message="form.errors.referring_doctor" />
                        </div>

                        <div class="space-y-2">
                            <Label for="emergency_contact_name" class="text-sm font-medium">
                                Contact d'Urgence (Nom)
                            </Label>
                            <Input
                                id="emergency_contact_name"
                                v-model="form.emergency_contact_name"
                                placeholder="Nom du conjoint, parent..."
                            />
                            <InputError :message="form.errors.emergency_contact_name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="emergency_contact_phone" class="text-sm font-medium">
                                Contact d'Urgence (Téléphone)
                            </Label>
                            <Input
                                id="emergency_contact_phone"
                                v-model="form.emergency_contact_phone"
                                type="tel"
                                placeholder="06 00 00 00 00"
                                class="font-mono"
                            />
                            <InputError :message="form.errors.emergency_contact_phone" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes" class="text-sm font-medium">
                            Remarques & Notes Cliniques Initiales
                        </Label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Observations particulières, motif d'orientation, antécédents notables..."
                            class="min-h-24"
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </CardContent>
            </Card>

            <!-- Bottom Action Bar -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <Button
                    as-child
                    variant="outline"
                    type="button"
                >
                    <Link :href="patientsIndex.url()">
                        Annuler
                    </Link>
                </Button>
                <Button
                    type="submit"
                    size="default"
                    class="gap-2 min-w-44"
                    :disabled="form.processing"
                >
                    <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                    <UserPlus v-else class="size-4" />
                    <span>Créer le Dossier Patient</span>
                </Button>
            </div>
        </form>
    </div>
</template>
