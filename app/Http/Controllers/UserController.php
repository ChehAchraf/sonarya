<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of medical users and their roles.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $roleFilter = $request->string('role')->trim()->value();

        $query = User::query()
            ->with('roles')
            ->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter !== '' && $roleFilter !== 'all') {
            $query->whereHas('roles', function ($rq) use ($roleFilter): void {
                $rq->where('name', $roleFilter);
            });
        }

        $metrics = [
            'total_users' => User::count(),
            'doctors_count' => User::role('medecin')->count(),
            'admins_count' => User::role('admin')->count(),
            'operators_count' => User::role('operateur_saisie')->count(),
        ];

        $users = $query
            ->paginate(15)
            ->withQueryString()
            ->through(function (User $u): array {
                $roleNames = $u->getRoleNames()->toArray();

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'roles' => $roleNames,
                    'primary_role' => ! empty($roleNames) ? (string) $roleNames[0] : 'medecin',
                    'two_factor_enabled' => $u->two_factor_confirmed_at !== null,
                    'created_at' => $u->created_at?->format('d/m/Y'),
                ];
            });

        $availableRoles = Role::query()->pluck('name')->toArray();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'metrics' => $metrics,
            'availableRoles' => $availableRoles,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter ?: 'all',
            ],
        ]);
    }

    /**
     * Store a newly created medical user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'in:admin,medecin,operateur_saisie'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        $user->email_verified_at = now();
        $user->save();

        $user->assignRole($validated['role']);

        activity('user')
            ->performedOn($user)
            ->causedBy($request->user())
            ->log("Création de l'utilisateur médical {$user->name} ({$user->email}) avec le rôle '{$validated['role']}'");

        return redirect()->route('users.index')
            ->with('success', "Le compte de {$user->name} a été créé avec succès.");
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', 'in:admin,medecin,operateur_saisie'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);
        $user->syncRoles([$validated['role']]);

        activity('user')
            ->performedOn($user)
            ->causedBy($request->user())
            ->log("Mise à jour des informations de l'utilisateur médical {$user->name} (Rôle: '{$validated['role']}')");

        return redirect()->route('users.index')
            ->with('success', "Le profil de {$user->name} a été mis à jour.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()?->id) {
            return redirect()->route('users.index')
                ->with('error', 'Vous ne pouvez pas supprimer votre propre compte administrateur.');
        }

        $userName = $user->name;
        $userEmail = $user->email;

        $user->delete();

        activity('user')
            ->causedBy($request->user())
            ->log("Suppression du compte utilisateur médical {$userName} ({$userEmail})");

        return redirect()->route('users.index')
            ->with('success', "Le compte de {$userName} a été supprimé.");
    }
}
