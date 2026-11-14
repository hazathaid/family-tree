<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\MemberRelationship;

class MemberSpouseService
{
    public function __construct(private readonly RelationshipTraversalService $traversal) {}

    /**
     * @return array<int, array{
     *     relationship_uuid: string,
     *     spouse_uuid: string,
     *     spouse_name: string,
     *     gender: string|null,
     *     label: string,
     *     start_date: string|null,
     *     end_date: string|null,
     *     end_reason: string|null,
     *     is_ended: bool
     * }>
     */
    public function summaries(FamilyMember $member): array
    {
        return $this->traversal->spouses($member)
            ->map(function (array $row): array {
                /** @var MemberRelationship $relationship */
                $relationship = $row['relationship'];
                /** @var FamilyMember $spouse */
                $spouse = $row['spouse'];
                $ended = $relationship->end_date !== null;
                $widowed = $relationship->end_reason === MemberRelationship::END_REASON_WIDOWED;

                return [
                    'relationship_uuid' => $relationship->uuid,
                    'spouse_uuid' => $spouse->uuid,
                    'spouse_name' => $spouse->full_name,
                    'gender' => $spouse->gender,
                    'label' => $this->label($spouse->gender, $ended, $widowed),
                    'start_date' => $relationship->start_date?->toDateString(),
                    'end_date' => $relationship->end_date?->toDateString(),
                    'end_reason' => $relationship->end_reason,
                    'is_ended' => $ended,
                ];
            })
            ->values()
            ->all();
    }

    private function label(?string $gender, bool $ended, bool $widowed): string
    {
        if ($ended && ! $widowed) {
            return match ($gender) {
                'male' => 'Mantan Suami',
                'female' => 'Mantan Istri',
                default => 'Mantan Pasangan',
            };
        }

        return match ($gender) {
            'male' => 'Suami',
            'female' => 'Istri',
            default => 'Pasangan',
        };
    }
}
