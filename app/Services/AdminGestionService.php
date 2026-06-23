<?php

namespace App\Services;

use App\Http\Resources\AdminGestionResource;
use App\Models\Administrateur;
use App\Models\Role;

class AdminGestionService
{
    public function dashboard(): array
    {
        try {
            $total    = Administrateur::count();
            $actifs   = Administrateur::where('statut_compte', 'ACTIF')->count();
            $inactifs = Administrateur::where('statut_compte', 'INACTIF')->count();
            $archives = Administrateur::onlyTrashed()->count();
            $superAdmins = Administrateur::whereHas('roles', fn($q) => $q->where('name', 'super-admin'))->count();
            $ceMois   = Administrateur::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            return [
                'success' => true,
                'message' => 'Tableau de bord administrateurs',
                'data'    => compact('total', 'actifs', 'inactifs', 'archives', 'superAdmins', 'ceMois'),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function lister(array $params): array
    {
        try {
            $query = Administrateur::with(['roles']);

            if (!empty($params['avec_archives'])) {
                $query->withTrashed();
            } elseif (!empty($params['seulement_archives'])) {
                $query->onlyTrashed();
            }

            if (!empty($params['search'])) {
                $q = $params['search'];
                $query->where(function ($qb) use ($q) {
                    $qb->where('nom', 'like', "%{$q}%")
                       ->orWhere('prenom', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%")
                       ->orWhere('telephone', 'like', "%{$q}%");
                });
            }

            if (!empty($params['statut'])) {
                $query->where('statut_compte', $params['statut']);
            }

            if (!empty($params['role'])) {
                $query->whereHas('roles', fn($r) => $r->where('name', $params['role']));
            }

            $perPage   = min((int) ($params['per_page'] ?? 20), 100);
            $page      = (int) ($params['page'] ?? 1);
            $paginated = $query->orderBy('nom')->orderBy('prenom')->paginate($perPage, ['*'], 'page', $page);

            return [
                'success' => true,
                'message' => 'Liste des administrateurs',
                'data'    => AdminGestionResource::collection($paginated->getCollection()),
                'meta'    => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function afficher(Administrateur $admin): array
    {
        try {
            $admin->load(['roles.permissions', 'permissions']);
            return [
                'success' => true,
                'message' => 'Détail de l\'administrateur',
                'data'    => new AdminGestionResource($admin),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function creer(array $data): array
    {
        try {
            $existe = Administrateur::where('email', $data['email'])->exists();
            if ($existe) {
                return ['success' => false, 'message' => 'Un administrateur avec cet email existe déjà.'];
            }

            if (!empty($data['telephone'])) {
                $telExiste = Administrateur::where('telephone', $data['telephone'])->exists();
                if ($telExiste) {
                    return ['success' => false, 'message' => 'Ce numéro de téléphone est déjà utilisé.'];
                }
            }

            // Conserver le mot de passe en clair pour l'email avant que le cast le hache
            $plainPassword = $data['password'];

            $admin = Administrateur::create([
                'nom'           => $data['nom'],
                'prenom'        => $data['prenom'] ?? null,
                'email'         => $data['email'],
                'password'      => $plainPassword,
                'telephone'     => $data['telephone'] ?? null,
                'ville'         => $data['ville'] ?? null,
                'adresse'       => $data['adresse'] ?? null,
                'statut_compte' => $data['statut_compte'] ?? 'INACTIF',
            ]);

            if (!empty($data['role_id'])) {
                $role = Role::where('guard_name', 'admin')->find($data['role_id']);
                if ($role) {
                    $admin->syncRoles([$role]);
                }
            }

            $admin->load(['roles']);

            // Envoyer l'email d'invitation avec les informations de connexion
            $emailResult = app(AdminNotificationService::class)->envoyerInvitation($admin, $plainPassword);

            return [
                'success'       => true,
                'message'       => "Administrateur {$admin->prenom} {$admin->nom} créé avec succès.",
                'data'          => new AdminGestionResource($admin),
                'email_envoye'  => $emailResult['envoye'],
                'email_raison'  => $emailResult['raison'] ?? ($emailResult['erreur'] ?? null),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function renvoyerInvitation(Administrateur $admin): array
    {
        try {
            $admin->load(['roles']);

            $result = app(AdminNotificationService::class)->renvoyerInvitation($admin);

            if (!$result['envoye']) {
                return [
                    'success' => false,
                    'message' => $result['raison'] ?? ($result['erreur'] ?? "Échec de l'envoi de l'invitation."),
                ];
            }

            return [
                'success' => true,
                'message' => "Email d'invitation renvoyé à {$admin->email} avec un nouveau mot de passe temporaire.",
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function modifier(Administrateur $admin, array $data): array
    {
        try {
            if (isset($data['email']) && $data['email'] !== $admin->email) {
                $existe = Administrateur::where('email', $data['email'])->exists();
                if ($existe) {
                    return ['success' => false, 'message' => 'Un administrateur avec cet email existe déjà.'];
                }
            }

            if (!empty($data['telephone']) && $data['telephone'] !== $admin->telephone) {
                $telExiste = Administrateur::where('telephone', $data['telephone'])->exists();
                if ($telExiste) {
                    return ['success' => false, 'message' => 'Ce numéro de téléphone est déjà utilisé.'];
                }
            }

            $champs = array_filter([
                'nom'           => $data['nom']           ?? null,
                'prenom'        => $data['prenom']         ?? null,
                'email'         => $data['email']          ?? null,
                'telephone'     => $data['telephone']      ?? null,
                'ville'         => $data['ville']          ?? null,
                'adresse'       => $data['adresse']        ?? null,
                'statut_compte' => $data['statut_compte']  ?? null,
            ], fn($v) => $v !== null);

            if (!empty($data['password'])) {
                $champs['password'] = $data['password'];
            }

            if (!empty($champs)) {
                $admin->update($champs);
            }

            $admin->load(['roles']);

            return [
                'success' => true,
                'message' => 'Administrateur modifié avec succès.',
                'data'    => new AdminGestionResource($admin),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function changerStatut(Administrateur $admin, string $statut): array
    {
        try {
            if ($admin->isSuperAdmin()) {
                return ['success' => false, 'message' => 'Le statut du super-admin ne peut pas être modifié.'];
            }
            $admin->update(['statut_compte' => $statut]);

            // Révoquer tous les tokens Sanctum lors de la désactivation
            if ($statut === 'INACTIF') {
                $admin->tokens()->delete();
            }

            $admin->load(['roles']);
            $libelle = $statut === 'ACTIF' ? 'activé' : 'désactivé';
            return [
                'success' => true,
                'message' => "Compte {$libelle}.",
                'data'    => new AdminGestionResource($admin),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function archiver(Administrateur $admin): array
    {
        try {
            if ($admin->isSuperAdmin()) {
                return ['success' => false, 'message' => 'Le super-admin ne peut pas être archivé.'];
            }
            $admin->delete();
            return ['success' => true, 'message' => 'Administrateur archivé.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function restaurer(string $adminId): array
    {
        try {
            $admin = Administrateur::withTrashed()->findOrFail($adminId);
            if (!$admin->trashed()) {
                return ['success' => false, 'message' => 'Cet administrateur n\'est pas archivé.'];
            }
            $admin->restore();
            $admin->load(['roles']);
            return [
                'success' => true,
                'message' => 'Administrateur restauré.',
                'data'    => new AdminGestionResource($admin),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
