<?php

namespace App\Services;

use App\Models\FamilyMember;
use Illuminate\Validation\ValidationException;

class RelationshipResolverService
{
    public function __construct(
        private readonly RelationshipTraversalService $traversal,
    ) {}

    /**
     * @return array{relationship: string|null, path: array<int, array<string, mixed>>}
     */
    public function resolve(FamilyMember $source, FamilyMember $target): array
    {
        if ($source->family_id !== $target->family_id) {
            throw ValidationException::withMessages([
                'target_member_id' => ['The selected target member does not belong to the same family.'],
            ]);
        }

        if ($source->id === $target->id) {
            return [
                'relationship' => 'Saya',
                'path' => [],
            ];
        }

        $path = $this->traversal->shortestPath($source, $target);

        return [
            'relationship' => $path === [] ? null : $this->relationshipName($source, $target, $path),
            'path' => $path,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $path
     */
    private function relationshipName(FamilyMember $source, FamilyMember $target, array $path): ?string
    {
        $sequence = array_column($path, 'relationship');

        if ($sequence === ['father']) {
            return 'Ayah';
        }

        if ($sequence === ['mother']) {
            return 'Ibu';
        }

        if ($this->isParentSequence($sequence, 2)) {
            return $target->gender === 'female' ? 'Nenek' : 'Kakek';
        }

        if ($this->isParentSequence($sequence, 3)) {
            return 'Buyut';
        }

        if ($this->isChildSequence($sequence, 3)) {
            return 'Cicit';
        }

        if (count($sequence) === 2 && $this->isParent($sequence[0]) && $sequence[1] === 'child') {
            return $target->gender === 'female' ? 'Saudara Perempuan' : 'Saudara Laki-Laki';
        }

        if ($this->isUncleOrAuntSequence($sequence)) {
            return $this->uncleOrAuntName($path[0]['to_member_id'], $target);
        }

        if ($this->isUncleOrAuntSpouseSequence($sequence)) {
            return $this->uncleOrAuntName($path[0]['to_member_id'], $target, $path[2]['to_member_id']);
        }

        if ($sequence === ['spouse', 'father'] || $sequence === ['spouse', 'mother']) {
            return 'Mertua';
        }

        if ($sequence === ['child', 'spouse']) {
            return 'Menantu';
        }

        if ($sequence === ['father', 'father', 'child', 'child']
            || $sequence === ['father', 'mother', 'child', 'child']
            || $sequence === ['mother', 'father', 'child', 'child']
            || $sequence === ['mother', 'mother', 'child', 'child']) {
            return 'Sepupu';
        }

        if (count($sequence) === 3 && $this->isParent($sequence[0]) && $sequence[1] === 'child' && $sequence[2] === 'child') {
            return 'Keponakan';
        }

        return null;
    }

    /**
     * @param  array<int, string>  $sequence
     */
    private function isParentSequence(array $sequence, int $length): bool
    {
        if (count($sequence) !== $length) {
            return false;
        }

        foreach ($sequence as $relationship) {
            if (! $this->isParent($relationship)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, string>  $sequence
     */
    private function isChildSequence(array $sequence, int $length): bool
    {
        return count($sequence) === $length && array_unique($sequence) === ['child'];
    }

    /**
     * @param  array<int, string>  $sequence
     */
    private function isUncleOrAuntSequence(array $sequence): bool
    {
        return count($sequence) === 3
            && $this->isParent($sequence[0])
            && $this->isParent($sequence[1])
            && $sequence[2] === 'child';
    }

    /**
     * @param  array<int, string>  $sequence
     */
    private function isUncleOrAuntSpouseSequence(array $sequence): bool
    {
        return count($sequence) === 4
            && $this->isParent($sequence[0])
            && $this->isParent($sequence[1])
            && $sequence[2] === 'child'
            && $sequence[3] === 'spouse';
    }

    private function isParent(string $relationship): bool
    {
        return in_array($relationship, ['father', 'mother', 'parent'], true);
    }

    private function uncleOrAuntName(int $parentMemberId, FamilyMember $target, ?int $uncleOrAuntMemberId = null): string
    {
        $comparisonMemberId = $uncleOrAuntMemberId ?? $target->id;
        $parent = FamilyMember::query()->select(['id', 'birth_date'])->find($parentMemberId);
        $comparisonMember = FamilyMember::query()->select(['id', 'birth_date'])->find($comparisonMemberId);
        $olderThanParent = $parent instanceof FamilyMember
            && $comparisonMember instanceof FamilyMember
            && $parent->birth_date !== null
            && $comparisonMember->birth_date !== null
            && $comparisonMember->birth_date->lt($parent->birth_date);

        if ($target->gender === 'female') {
            return $olderThanParent ? 'Bude' : 'Tante';
        }

        return $olderThanParent ? 'Pakde' : 'Om';
    }
}
