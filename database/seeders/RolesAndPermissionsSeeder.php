<?php

namespace Database\Seeders;

use App\Models\Administrateur;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ── Modules et permissions ──────────────────────────────────────────────
        $modules = [
            'dashboard' => [
                'label' => 'Tableau de bord',
                'permissions' => [
                    'view' => 'Consulter le tableau de bord',
                ],
            ],

            'utilisateurs' => [
                'label' => 'Gestion des utilisateurs',
                'permissions' => [
                    'view'    => 'Consulter les utilisateurs',
                    'update'  => 'Modifier un utilisateur',
                    'delete'  => 'Supprimer un utilisateur',
                    'archive' => 'Archiver un utilisateur',
                    'restore' => 'Restaurer un utilisateur',
                    'export'  => 'Exporter les utilisateurs',
                ],
            ],

            'kyc' => [
                'label' => 'Vérification KYC',
                'permissions' => [
                    'view'     => 'Consulter les dossiers KYC',
                    'validate' => 'Valider un document KYC',
                    'reject'   => 'Rejeter un document KYC',
                    'update'   => 'Mettre à jour un document KYC',
                    'export'   => 'Exporter les données KYC',
                ],
            ],

            'cotisations' => [
                'label' => 'Cotisations',
                'permissions' => [
                    'view'   => 'Consulter les cotisations',
                    'export' => 'Exporter les cotisations',
                ],
            ],

            'types-cotisation' => [
                'label' => 'Types de cotisation',
                'permissions' => [
                    'view'   => 'Consulter les types de cotisation',
                    'create' => 'Créer un type de cotisation',
                    'update' => 'Modifier un type de cotisation',
                    'delete' => 'Supprimer un type de cotisation',
                ],
            ],

            'epargne' => [
                'label' => 'Épargne',
                'permissions' => [
                    'view'   => "Consulter les données d'épargne",
                    'update' => "Modifier une épargne",
                    'export' => "Exporter les épargnes",
                ],
            ],

            'transactions' => [
                'label' => 'Transactions',
                'permissions' => [
                    'view'   => 'Consulter les transactions',
                    'export' => 'Exporter les transactions',
                ],
            ],

            'paiements' => [
                'label' => 'Paiements entrants',
                'permissions' => [
                    'view'   => 'Consulter les paiements',
                    'cancel' => 'Annuler un paiement',
                    'export' => 'Exporter les paiements',
                ],
            ],

            'reversements' => [
                'label' => 'Reversements',
                'permissions' => [
                    'view'     => 'Consulter les reversements',
                    'create'   => 'Créer un reversement',
                    'update'   => 'Modifier un reversement',
                    'validate' => 'Valider un reversement',
                    'reject'   => 'Rejeter un reversement',
                    'cancel'   => 'Annuler un reversement',
                    'export'   => 'Exporter les reversements',
                ],
            ],

            'repartitions' => [
                'label' => 'Répartitions automatiques',
                'permissions' => [
                    'view'   => 'Consulter les répartitions',
                    'export' => 'Exporter les répartitions',
                ],
            ],

            'mobile-money' => [
                'label' => 'Mobile Money',
                'permissions' => [
                    'view'     => 'Consulter les comptes Mobile Money',
                    'update'   => 'Modifier un compte Mobile Money',
                    'validate' => 'Valider un compte Mobile Money',
                    'reject'   => 'Rejeter un compte Mobile Money',
                    'export'   => 'Exporter les comptes Mobile Money',
                ],
            ],

            'partenaires-financiers' => [
                'label' => 'Partenaires financiers',
                'permissions' => [
                    'view'    => 'Consulter les partenaires financiers',
                    'create'  => 'Créer un partenaire financier',
                    'update'  => 'Modifier un partenaire financier',
                    'delete'  => 'Supprimer un partenaire financier',
                    'archive' => 'Archiver un partenaire financier',
                    'restore' => 'Restaurer un partenaire financier',
                ],
            ],

            'moyens-paiement' => [
                'label' => 'Moyens de paiement',
                'permissions' => [
                    'view'   => 'Consulter les moyens de paiement',
                    'create' => 'Créer un moyen de paiement',
                    'update' => 'Modifier un moyen de paiement',
                    'delete' => 'Supprimer un moyen de paiement',
                ],
            ],

            'configurations-api' => [
                'label' => 'Configurations API opérateurs',
                'permissions' => [
                    'view'      => 'Consulter les configurations API',
                    'create'    => 'Créer une configuration API',
                    'update'    => 'Modifier une configuration API',
                    'delete'    => 'Supprimer une configuration API',
                    'configure' => 'Configurer les paramètres API opérateur',
                ],
            ],

            'seuils-prelevement' => [
                'label' => 'Seuils de prélèvement',
                'permissions' => [
                    'view'      => 'Consulter les seuils de prélèvement',
                    'update'    => 'Modifier un seuil de prélèvement',
                    'configure' => 'Configurer les règles de prélèvement',
                ],
            ],

            'gestion-admins' => [
                'label' => 'Gestion des administrateurs',
                'permissions' => [
                    'view'    => 'Consulter les administrateurs',
                    'create'  => 'Créer un administrateur',
                    'update'  => 'Modifier un administrateur',
                    'delete'  => 'Supprimer un administrateur',
                    'archive' => 'Archiver un administrateur',
                    'restore' => 'Restaurer un administrateur',
                    'assign'  => 'Assigner des rôles à un administrateur',
                ],
            ],

            'roles' => [
                'label' => 'Gestion des rôles RBAC',
                'permissions' => [
                    'view'    => 'Consulter les rôles',
                    'create'  => 'Créer un rôle',
                    'update'  => 'Modifier un rôle',
                    'delete'  => 'Supprimer un rôle',
                    'archive' => 'Archiver un rôle',
                    'restore' => 'Restaurer un rôle',
                    'assign'  => 'Assigner des permissions à un rôle',
                ],
            ],

            'permissions' => [
                'label' => 'Gestion des permissions RBAC',
                'permissions' => [
                    'view'   => 'Consulter les permissions',
                    'create' => 'Créer une permission',
                    'update' => 'Modifier une permission',
                    'delete' => 'Supprimer une permission',
                    'assign' => 'Assigner une permission à un rôle',
                ],
            ],

            'logs-audit' => [
                'label' => "Logs d'audit",
                'permissions' => [
                    'view'   => "Consulter les logs d'audit",
                    'delete' => "Supprimer des logs d'audit",
                    'export' => "Exporter les logs d'audit",
                ],
            ],

            'alertes' => [
                'label' => 'Alertes système',
                'permissions' => [
                    'view'   => 'Consulter les alertes système',
                    'update' => 'Modifier une alerte',
                    'delete' => 'Supprimer une alerte',
                ],
            ],

            'notifications-config' => [
                'label' => 'Configuration des notifications',
                'permissions' => [
                    'view'   => 'Consulter la configuration des notifications',
                    'create' => 'Créer un canal de notification',
                    'update' => 'Modifier une configuration de notification',
                    'delete' => 'Supprimer une configuration de notification',
                ],
            ],

            'parametres-globaux' => [
                'label' => 'Paramètres globaux',
                'permissions' => [
                    'view'   => 'Consulter les paramètres globaux',
                    'create' => 'Créer un paramètre global',
                    'update' => 'Modifier un paramètre global',
                    'delete' => 'Supprimer un paramètre global',
                ],
            ],

            'parametres-generaux' => [
                'label' => 'Paramètres généraux de la plateforme',
                'permissions' => [
                    'view'   => 'Consulter les paramètres généraux',
                    'update' => 'Modifier les paramètres généraux',
                ],
            ],

            'pages' => [
                'label' => 'Gestion des pages CMS',
                'permissions' => [
                    'view'    => 'Consulter les pages',
                    'create'  => 'Créer une page',
                    'update'  => 'Modifier une page',
                    'delete'  => 'Supprimer une page',
                    'archive' => 'Archiver une page',
                    'restore' => 'Restaurer une page',
                ],
            ],

            'systeme' => [
                'label' => 'Système (SuperAdmin uniquement)',
                'permissions' => [
                    'view'    => 'Consulter les logs système',
                    'backup'  => 'Effectuer une sauvegarde système',
                    'restore' => 'Restaurer une sauvegarde système',
                    'delete'  => 'Supprimer des logs ou sauvegardes système',
                ],
            ],
        ];

        // ── Création idempotente de toutes les permissions ──────────────────────
        $allPermissionNames = [];
        foreach ($modules as $moduleKey => $module) {
            foreach ($module['permissions'] as $action => $displayName) {
                $permName = "{$moduleKey}.{$action}";
                Permission::firstOrCreate(
                    ['name' => $permName, 'guard_name' => 'admin'],
                    [
                        'display_name' => $displayName,
                        'module'       => $moduleKey,
                        'description'  => "{$displayName} — Module : {$module['label']}",
                    ]
                );
                $allPermissionNames[] = $permName;
            }
        }

        // ── Définition des rôles par défaut ─────────────────────────────────────
        $roles = [

            // Accès total — réservé au fondateur / hébergeur
            'super-admin' => [
                'display_name' => 'Super Administrateur',
                'description'  => 'Accès complet et illimité à toutes les fonctionnalités de la plateforme',
                'permissions'  => $allPermissionNames,
            ],

            // Gestion opérationnelle complète, sans RBAC ni système
            'admin' => [
                'display_name' => 'Administrateur',
                'description'  => 'Gestion opérationnelle complète, hors système et RBAC avancé',
                'permissions'  => $this->excludeModules($allPermissionNames, [
                    'systeme', 'roles', 'permissions', 'gestion-admins',
                ]),
            ],

            // Finance : cotisations, épargne, reversements, mobile money
            'gestionnaire-financier' => [
                'display_name' => 'Gestionnaire Financier',
                'description'  => 'Supervision et traitement des opérations financières',
                'permissions'  => $this->filterModules($allPermissionNames, [
                    'dashboard', 'utilisateurs',
                    'kyc', 'cotisations', 'types-cotisation',
                    'epargne', 'transactions', 'paiements',
                    'reversements', 'repartitions', 'mobile-money',
                ]),
            ],

            // Vérification des documents et identités utilisateurs
            'agent-kyc' => [
                'display_name' => 'Agent KYC',
                'description'  => "Vérification et validation des documents d'identité",
                'permissions'  => [
                    'dashboard.view',
                    'utilisateurs.view',
                    'kyc.view', 'kyc.validate', 'kyc.reject', 'kyc.update', 'kyc.export',
                ],
            ],

            // Lecture seule + export sur tous les modules
            'auditeur' => [
                'display_name' => 'Auditeur',
                'description'  => "Accès en lecture seule et export sur l'ensemble des données",
                'permissions'  => $this->filterActions($allPermissionNames, ['view', 'export']),
            ],

            // Assistance utilisateurs, pas d'actions sensibles
            'support' => [
                'display_name' => 'Support Client',
                'description'  => 'Consultation des données courantes pour assistance aux utilisateurs',
                'permissions'  => [
                    'dashboard.view',
                    'utilisateurs.view',
                    'kyc.view',
                    'cotisations.view',
                    'epargne.view',
                    'transactions.view',
                    'paiements.view',
                    'mobile-money.view',
                    'alertes.view', 'alertes.update',
                    'notifications-config.view',
                ],
            ],
        ];

        // ── Création et synchronisation des rôles ───────────────────────────────
        foreach ($roles as $roleName => $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'admin'],
                [
                    'display_name' => $roleData['display_name'],
                    'description'  => $roleData['description'],
                    'is_archived'  => false,
                ]
            );
            $role->syncPermissions($roleData['permissions']);
        }

        // ── Assignation super-admin au premier administrateur ────────────────────
        $firstAdmin = Administrateur::query()->first();
        if ($firstAdmin && !$firstAdmin->hasRole('super-admin')) {
            $firstAdmin->assignRole('super-admin');
        }

        $this->command->info('✓ ' . count($allPermissionNames) . ' permissions enregistrées dans ' . count($modules) . ' modules.');
        $this->command->info('✓ ' . count($roles) . ' rôles créés et configurés.');
    }

    /** Garde uniquement les permissions des modules listés */
    private function filterModules(array $permissions, array $modules): array
    {
        return array_values(array_filter($permissions, function (string $perm) use ($modules): bool {
            return in_array(explode('.', $perm)[0], $modules, true);
        }));
    }

    /** Exclut les permissions des modules listés */
    private function excludeModules(array $permissions, array $modules): array
    {
        return array_values(array_filter($permissions, function (string $perm) use ($modules): bool {
            return !in_array(explode('.', $perm)[0], $modules, true);
        }));
    }

    /** Garde uniquement les permissions dont l'action est dans la liste */
    private function filterActions(array $permissions, array $actions): array
    {
        return array_values(array_filter($permissions, function (string $perm) use ($actions): bool {
            $parts = explode('.', $perm, 2);
            return isset($parts[1]) && in_array($parts[1], $actions, true);
        }));
    }
}
