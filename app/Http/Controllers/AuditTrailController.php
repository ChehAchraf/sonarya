<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditTrailController extends Controller
{
    /**
     * Display the hospital clinical audit trail and CNDP traceability log.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $event = $request->string('event')->trim()->value();
        $logName = $request->string('log_name')->trim()->value();
        $userId = $request->string('user_id')->trim()->value();
        $dateFrom = $request->string('date_from')->trim()->value();
        $dateTo = $request->string('date_to')->trim()->value();

        $query = Activity::query()
            ->with('causer:id,name,email')
            ->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('log_name', 'like', "%{$search}%")
                    ->orWhereHasMorph('causer', [User::class], function ($uq) use ($search): void {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($event !== '' && $event !== 'all') {
            $query->where('event', $event);
        }

        if ($logName !== '' && $logName !== 'all') {
            $query->where('log_name', $logName);
        }

        if ($userId !== '' && $userId !== 'all') {
            $query->where('causer_id', (int) $userId)
                ->where('causer_type', User::class);
        }

        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Compute metrics
        $metrics = [
            'total_activities' => Activity::count(),
            'exports_count' => Activity::where('log_name', 'export')->orWhere('event', 'export')->count(),
            'updates_count' => Activity::where('event', 'updated')->count(),
            'active_users_count' => Activity::distinct('causer_id')->whereNotNull('causer_id')->count('causer_id'),
        ];

        $activities = $query
            ->paginate(20)
            ->withQueryString()
            ->through(function (Activity $act): array {
                $causer = $act->causer;
                $causerName = $causer instanceof User ? $causer->name : 'Système';
                $causerEmail = $causer instanceof User ? $causer->email : '—';
                $causerRole = $causer instanceof User ? $causer->getRoleNames()->first() : null;

                $event = $act->event ?? 'action';
                $eventLabel = match ($event) {
                    'created' => 'Création',
                    'updated' => 'Modification',
                    'deleted' => 'Suppression',
                    'export' => 'Exportation CNDP',
                    'consultation_dossier' => 'Consultation Dossier',
                    default => ucfirst($event),
                };

                $subjectLabel = match ($act->log_name) {
                    'patient' => 'Dossier Patient',
                    'disease_profile' => 'Profil Maladie',
                    'treatment' => 'Traitement DMT',
                    'mri_exam' => 'Examen IRM',
                    'clinical_exam' => 'Examen Clinique / EDSS',
                    'consultation' => 'Consultation de Suivi',
                    'complementary_exam' => 'Examens Complémentaires / PL',
                    'relapse' => 'Poussée / Rechute',
                    'export' => 'Export Recherche / Cohorte',
                    'user' => 'Utilisateur / Accès',
                    default => ucfirst($act->log_name ?? 'Module'),
                };

                return [
                    'id' => $act->id,
                    'log_name' => $act->log_name,
                    'subject_label' => $subjectLabel,
                    'subject_id' => $act->subject_id,
                    'description' => $act->description,
                    'event' => $event,
                    'event_label' => $eventLabel,
                    'causer' => [
                        'id' => $causer instanceof User ? $causer->id : null,
                        'name' => $causerName,
                        'email' => $causerEmail,
                        'role' => $causerRole,
                    ],
                    'properties' => $act->properties?->toArray() ?? [],
                    'created_at' => $act->created_at?->format('d/m/Y H:i:s'),
                    'created_at_relative' => $act->created_at?->diffForHumans(),
                ];
            });

        $users = User::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('AuditTrail/Index', [
            'activities' => $activities,
            'metrics' => $metrics,
            'users' => $users,
            'filters' => [
                'search' => $search,
                'event' => $event ?: 'all',
                'log_name' => $logName ?: 'all',
                'user_id' => $userId ?: 'all',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }
}
