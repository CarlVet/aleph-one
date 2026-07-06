<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TeamPageData
{
    /**
     * @return array{
     *     permissionStyles: array<string, array<string, string>>,
     *     allMembers: Collection,
     *     permissionFolders: Collection,
     *     roleFolders: Collection
     * }
     */
    public static function forProject(Collection $people, ?int $currentPeopleId = null): array
    {
        $sortMembers = static fn (Collection $members) => self::sortMembers($members, $currentPeopleId);

        $permissionStyles = [
            'admin' => [
                'label' => 'Admins',
                'icon' => 'fas fa-user-shield',
                'badge' => 'from-rose-500 to-pink-600',
                'soft' => 'from-rose-50 via-pink-50 to-fuchsia-50',
                'ring' => 'border-rose-100',
                'icon_wrap' => 'bg-rose-100 text-rose-600',
            ],
            'editor' => [
                'label' => 'Editors',
                'icon' => 'fas fa-user-pen',
                'badge' => 'from-amber-500 to-orange-600',
                'soft' => 'from-amber-50 via-orange-50 to-yellow-50',
                'ring' => 'border-amber-100',
                'icon_wrap' => 'bg-amber-100 text-amber-600',
            ],
            'viewer' => [
                'label' => 'Viewers',
                'icon' => 'fas fa-user',
                'badge' => 'from-sky-500 to-blue-600',
                'soft' => 'from-sky-50 via-blue-50 to-indigo-50',
                'ring' => 'border-sky-100',
                'icon_wrap' => 'bg-sky-100 text-sky-600',
            ],
        ];

        $roleStylePresets = [
            ['badge' => 'from-violet-500 to-purple-600', 'soft' => 'from-violet-50 via-purple-50 to-fuchsia-50', 'ring' => 'border-violet-100', 'icon_wrap' => 'bg-violet-100 text-violet-600', 'icon' => 'fas fa-user-tie'],
            ['badge' => 'from-teal-500 to-cyan-600', 'soft' => 'from-teal-50 via-cyan-50 to-sky-50', 'ring' => 'border-teal-100', 'icon_wrap' => 'bg-teal-100 text-teal-700', 'icon' => 'fas fa-user-graduate'],
            ['badge' => 'from-lime-500 to-emerald-600', 'soft' => 'from-lime-50 via-emerald-50 to-green-50', 'ring' => 'border-lime-100', 'icon_wrap' => 'bg-lime-100 text-lime-700', 'icon' => 'fas fa-user-nurse'],
            ['badge' => 'from-fuchsia-500 to-pink-600', 'soft' => 'from-fuchsia-50 via-pink-50 to-rose-50', 'ring' => 'border-fuchsia-100', 'icon_wrap' => 'bg-fuchsia-100 text-fuchsia-600', 'icon' => 'fas fa-user-doctor'],
            ['badge' => 'from-orange-500 to-amber-600', 'soft' => 'from-orange-50 via-amber-50 to-yellow-50', 'ring' => 'border-orange-100', 'icon_wrap' => 'bg-orange-100 text-orange-600', 'icon' => 'fas fa-user-gear'],
        ];

        $permissionFolders = collect(['admin', 'editor', 'viewer'])->map(function ($permission) use ($people, $permissionStyles, $sortMembers) {
            $members = $sortMembers(
                $people->filter(fn ($member) => strtolower((string) ($member->pivot->permission ?? 'viewer')) === $permission)
            );

            return [
                'key' => $permission,
                'label' => $permissionStyles[$permission]['label'],
                'style' => $permissionStyles[$permission],
                'members' => $members,
                'count' => $members->count(),
            ];
        })->filter(fn ($folder) => $folder['count'] > 0)->values();

        $roleIndex = 0;
        $roleFolders = $people
            ->groupBy(fn ($member) => trim((string) ($member->pivot->role ?? 'Team member')) ?: 'Team member')
            ->map(function ($members, $role) use ($roleStylePresets, $sortMembers, &$roleIndex) {
                $style = $roleStylePresets[$roleIndex % count($roleStylePresets)];
                $roleIndex++;
                $members = $sortMembers($members);

                return [
                    'key' => Str::slug($role),
                    'label' => $role,
                    'style' => array_merge($style, ['label' => $role]),
                    'members' => $members,
                    'count' => $members->count(),
                ];
            })
            ->sortBy('label')
            ->values();

        return [
            'permissionStyles' => $permissionStyles,
            'allMembers' => $sortMembers($people),
            'permissionFolders' => $permissionFolders,
            'roleFolders' => $roleFolders,
        ];
    }

    private static function sortMembers(Collection $members, ?int $currentPeopleId = null): Collection
    {
        return $members->sortBy([
            fn ($a, $b) => self::currentUserSortKey($a, $currentPeopleId) <=> self::currentUserSortKey($b, $currentPeopleId),
            fn ($a, $b) => strcasecmp((string) $a->last_name, (string) $b->last_name),
            fn ($a, $b) => strcasecmp((string) $a->first_name, (string) $b->first_name),
        ])->values();
    }

    private static function currentUserSortKey(object $member, ?int $currentPeopleId): int
    {
        return $currentPeopleId !== null && (int) $member->id === $currentPeopleId ? 0 : 1;
    }
}
