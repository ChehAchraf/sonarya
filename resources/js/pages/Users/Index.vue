<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    KeyRound,
    Lock,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    Shield,
    ShieldAlert,
    ShieldCheck,
    Trash2,
    UserCheck,
    UserPlus,
    Users,
} from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface UserItem {
    id: number;
    name: string;
    email: string;
    roles: string[];
    primary_role: string;
    two_factor_enabled: boolean;
    created_at: string;
}

const props = defineProps<{
    users: {
        data: UserItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    metrics: {
        total_users: number;
        doctors_count: number;
        admins_count: number;
        operators_count: number;
    };
    availableRoles: string[];
    filters: {
        search: string;
        role: string;
    };
}>();

const search = ref(props.filters.search);
const roleFilter = ref(props.filters.role);

// Modals state
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const userToEdit = ref<UserItem | null>(null);
const userToDelete = ref<UserItem | null>(null);

// Forms
const createForm = useForm({
    name: '',
    email: '',
    role: 'medecin',
    password: '',
});

const editForm = useForm({
    name: '',
    email: '',
    role: 'medecin',
    password: '',
});

const deleteForm = useForm({});

const applyFilters = () => {
    router.get(
        '/users',
        {
            search: search.value || undefined,
            role: roleFilter.value !== 'all' ? roleFilter.value : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    roleFilter.value = 'all';
    applyFilters();
};

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.role = 'medecin';
    isCreateOpen.value = true;
};

const submitCreate = () => {
    createForm.post('/users', {
        preserveScroll: true,
        onSuccess: () => {
            isCreateOpen.value = false;
            createForm.reset();
        },
    });
};

const openEditModal = (user: UserItem) => {
    userToEdit.value = user;
    editForm.reset();
    editForm.clearErrors();
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.role = user.primary_role;
    editForm.password = '';
    isEditOpen.value = true;
};

const submitEdit = () => {
    if (!userToEdit.value) return;

    editForm.put(`/users/${userToEdit.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditOpen.value = false;
            userToEdit.value = null;
        },
    });
};

const openDeleteModal = (user: UserItem) => {
    userToDelete.value = user;
    isDeleteOpen.value = true;
};

const confirmDelete = () => {
    if (!userToDelete.value) return;

    deleteForm.delete(`/users/${userToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteOpen.value = false;
            userToDelete.value = null;
        },
    });
};

const getRoleBadgeClass = (role: string) => {
    switch (role) {
        case 'admin':
            return 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200';
        case 'medecin':
            return 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200';
        case 'operateur_saisie':
            return 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const formatRoleLabel = (role: string) => {
    switch (role) {
        case 'admin':
            return 'Administrateur';
        case 'medecin':
            return 'Médecin Neurologue';
        case 'operateur_saisie':
            return 'Opérateur de Saisie';
        default:
            return role;
    }
};
</script>

<template>
    <Head title="Gestion des Utilisateurs & Rôles" />

    <div class="space-y-6 p-3 sm:p-6 lg:p-8 max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between border-b pb-5">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                            <Users class="h-6 w-6" />
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-foreground flex items-center gap-2 flex-wrap">
                                Utilisateurs & Rôles Médicaux
                            </h1>
                            <p class="text-xs sm:text-sm text-muted-foreground mt-0.5">
                                Gestion des comptes praticiens, neurologues, opérateurs et attribution des privilèges de sécurité
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <Button @click="openCreateModal" size="sm" class="gap-1.5 text-xs w-full sm:w-auto justify-center">
                        <UserPlus class="h-3.5 w-3.5" />
                        Nouvel Utilisateur
                    </Button>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="rounded-xl border bg-card p-4 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-muted-foreground">Total Comptes</span>
                        <div class="rounded-lg bg-primary/10 p-2 text-primary">
                            <Users class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight">{{ metrics.total_users }}</span>
                        <span class="text-xs text-muted-foreground">utilisateurs</span>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-muted-foreground">Médecins Neurologues</span>
                        <div class="rounded-lg bg-blue-500/10 p-2 text-blue-600 dark:text-blue-400">
                            <UserCheck class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight text-blue-600 dark:text-blue-400">
                            {{ metrics.doctors_count }}
                        </span>
                        <span class="text-xs text-muted-foreground">praticiens</span>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-muted-foreground">Administrateurs</span>
                        <div class="rounded-lg bg-rose-500/10 p-2 text-rose-600 dark:text-rose-400">
                            <ShieldAlert class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400">
                            {{ metrics.admins_count }}
                        </span>
                        <span class="text-xs text-muted-foreground">gestionnaires</span>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-muted-foreground">Opérateurs de Saisie</span>
                        <div class="rounded-lg bg-amber-500/10 p-2 text-amber-600 dark:text-amber-400">
                            <Shield class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400">
                            {{ metrics.operators_count }}
                        </span>
                        <span class="text-xs text-muted-foreground">collaborateurs</span>
                    </div>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="rounded-xl border bg-card p-4 shadow-xs">
                <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <div class="flex flex-1 flex-col sm:flex-row gap-3 w-full">
                        <div class="relative flex-1">
                            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Rechercher par nom ou email..."
                                class="pl-8 text-xs h-9"
                            />
                        </div>

                        <div class="w-full sm:w-56">
                            <select
                                v-model="roleFilter"
                                @change="applyFilters"
                                class="w-full h-9 rounded-md border border-input bg-background px-2.5 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="all">Tous les rôles</option>
                                <option value="admin">Administrateur</option>
                                <option value="medecin">Médecin Neurologue</option>
                                <option value="operateur_saisie">Opérateur de Saisie</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <Button @click="applyFilters" size="sm" class="h-9 text-xs gap-1.5 flex-1 sm:flex-initial">
                            <Search class="h-3.5 w-3.5" />
                            Filtrer
                        </Button>
                        <Button variant="outline" size="sm" @click="resetFilters" class="h-9 text-xs">
                            <RotateCcw class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="rounded-xl border bg-card shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b bg-muted/50 font-semibold text-muted-foreground">
                            <tr>
                                <th class="p-3 w-64">Praticien / Utilisateur</th>
                                <th class="p-3 w-52">Rôle & Privilèges</th>
                                <th class="p-3 w-40">Authentification 2FA</th>
                                <th class="p-3 w-40">Date d'Inscription</th>
                                <th class="p-3 text-right w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="hover:bg-muted/40 transition-colors"
                            >
                                <!-- Name & Email -->
                                <td class="p-3">
                                    <div class="font-semibold text-foreground text-sm">{{ user.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ user.email }}</div>
                                </td>

                                <!-- Role -->
                                <td class="p-3">
                                    <Badge
                                        variant="outline"
                                        :class="['text-xs font-semibold px-2.5 py-0.5 border', getRoleBadgeClass(user.primary_role)]"
                                    >
                                        {{ formatRoleLabel(user.primary_role) }}
                                    </Badge>
                                </td>

                                <!-- 2FA Status -->
                                <td class="p-3">
                                    <span
                                        v-if="user.two_factor_enabled"
                                        class="inline-flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 font-medium"
                                    >
                                        <ShieldCheck class="h-4 w-4" />
                                        2FA Activée
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 text-xs text-muted-foreground"
                                    >
                                        <Lock class="h-3.5 w-3.5" />
                                        Standard (Mot de passe)
                                    </span>
                                </td>

                                <!-- Registered date -->
                                <td class="p-3 text-muted-foreground">
                                    {{ user.created_at }}
                                </td>

                                <!-- Actions -->
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 px-2 text-xs gap-1"
                                            @click="openEditModal(user)"
                                        >
                                            <Pencil class="h-3.5 w-3.5 text-muted-foreground" />
                                            Modifier
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 px-2 text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                            @click="openDeleteModal(user)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-muted-foreground">
                                    <Users class="h-8 w-8 mx-auto mb-2 opacity-40" />
                                    Aucun utilisateur trouvé.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="users.last_page > 1"
                    class="flex items-center justify-between border-t px-4 py-3 bg-card"
                >
                    <div class="text-xs text-muted-foreground">
                        Affichage de
                        <span class="font-medium">{{ (users.current_page - 1) * users.per_page + 1 }}</span>
                        à
                        <span class="font-medium">{{ Math.min(users.current_page * users.per_page, users.total) }}</span>
                        sur
                        <span class="font-medium">{{ users.total }}</span> utilisateurs
                    </div>
                    <div class="flex items-center gap-1">
                        <Button
                            v-for="(link, i) in users.links"
                            :key="i"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            class="h-8 min-w-[32px] px-2 text-xs"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create User Dialog -->
        <Dialog v-model:open="isCreateOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-lg">
                        <UserPlus class="h-5 w-5 text-primary" />
                        Créer un Nouvel Utilisateur Médical
                    </DialogTitle>
                    <DialogDescription>
                        Renseignez les coordonnées professionnelles et le rôle du nouveau praticien
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitCreate" class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label for="create-name">Nom et Prénom</Label>
                        <Input
                            id="create-name"
                            v-model="createForm.name"
                            placeholder="Dr. Nom Prénom"
                            required
                        />
                        <InputError :message="createForm.errors.name" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="create-email">Adresse Email Professionnelle</Label>
                        <Input
                            id="create-email"
                            type="email"
                            v-model="createForm.email"
                            placeholder="medecin@sonarya.med"
                            required
                        />
                        <InputError :message="createForm.errors.email" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="create-role">Rôle & Privilèges d'Accès</Label>
                        <select
                            id="create-role"
                            v-model="createForm.role"
                            class="w-full h-9 rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            required
                        >
                            <option value="medecin">Médecin (Neurologue / Consultant)</option>
                            <option value="operateur_saisie">Opérateur de Saisie / Résident</option>
                            <option value="admin">Administrateur Système</option>
                        </select>
                        <InputError :message="createForm.errors.role" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="create-password">Mot de Passe Initial (min 8 car.)</Label>
                        <Input
                            id="create-password"
                            type="password"
                            v-model="createForm.password"
                            placeholder="••••••••"
                            required
                        />
                        <InputError :message="createForm.errors.password" />
                    </div>

                    <DialogFooter class="pt-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline" size="sm">Annuler</Button>
                        </DialogClose>
                        <Button type="submit" size="sm" :disabled="createForm.processing">
                            Créer le Compte
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit User Dialog -->
        <Dialog v-model:open="isEditOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-lg">
                        <Pencil class="h-5 w-5 text-primary" />
                        Modifier le Profil Utilisateur
                    </DialogTitle>
                    <DialogDescription>
                        Mise à jour des coordonnées et des permissions d'accès
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEdit" class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label for="edit-name">Nom et Prénom</Label>
                        <Input
                            id="edit-name"
                            v-model="editForm.name"
                            required
                        />
                        <InputError :message="editForm.errors.name" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="edit-email">Adresse Email</Label>
                        <Input
                            id="edit-email"
                            type="email"
                            v-model="editForm.email"
                            required
                        />
                        <InputError :message="editForm.errors.email" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="edit-role">Rôle & Privilèges</Label>
                        <select
                            id="edit-role"
                            v-model="editForm.role"
                            class="w-full h-9 rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            required
                        >
                            <option value="medecin">Médecin (Neurologue / Consultant)</option>
                            <option value="operateur_saisie">Opérateur de Saisie / Résident</option>
                            <option value="admin">Administrateur Système</option>
                        </select>
                        <InputError :message="editForm.errors.role" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="edit-password">
                            Nouveau mot de passe <span class="text-xs text-muted-foreground font-normal">(laisser vide pour conserver)</span>
                        </Label>
                        <Input
                            id="edit-password"
                            type="password"
                            v-model="editForm.password"
                            placeholder="••••••••"
                        />
                        <InputError :message="editForm.errors.password" />
                    </div>

                    <DialogFooter class="pt-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline" size="sm">Annuler</Button>
                        </DialogClose>
                        <Button type="submit" size="sm" :disabled="editForm.processing">
                            Enregistrer les Modifications
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="isDeleteOpen">
            <DialogContent class="max-w-sm">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-rose-600">
                        <Trash2 class="h-5 w-5" />
                        Supprimer le Compte ?
                    </DialogTitle>
                    <DialogDescription>
                        Êtes-vous sûr de vouloir supprimer le compte de
                        <span class="font-semibold text-foreground">{{ userToDelete?.name }}</span>
                        ({{ userToDelete?.email }}) ? Cette action est irréversible.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="pt-2">
                    <DialogClose as-child>
                        <Button type="button" variant="outline" size="sm">Annuler</Button>
                    </DialogClose>
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        :disabled="deleteForm.processing"
                        @click="confirmDelete"
                    >
                        Confirmer la Suppression
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
</template>
