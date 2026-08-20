<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MemberRelationship;
use App\Models\User;
use App\Services\RelationshipResolverService;
use App\Services\RelationshipTraversalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RelationshipEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_relationship_resolver_calculates_supported_relationship_matrix(): void
    {
        $graph = $this->relationshipGraph();
        $resolver = app(RelationshipResolverService::class);

        $matrix = [
            'Saya' => [$graph['source'], 'Saya'],
            'Ayah' => [$graph['father'], 'Ayah'],
            'Ibu' => [$graph['mother'], 'Ibu'],
            'Kakek' => [$graph['grandFather'], 'Kakek'],
            'Nenek' => [$graph['grandMother'], 'Nenek'],
            'Saudara Laki-Laki' => [$graph['brother'], 'Saudara Laki-Laki'],
            'Saudara Perempuan' => [$graph['sister'], 'Saudara Perempuan'],
            'Pakde' => [$graph['olderUncle'], 'Pakde'],
            'Om' => [$graph['youngerUncle'], 'Om'],
            'Bude' => [$graph['olderAunt'], 'Bude'],
            'Tante' => [$graph['youngerAunt'], 'Tante'],
            'Sepupu' => [$graph['cousin'], 'Sepupu'],
            'Keponakan' => [$graph['nephew'], 'Keponakan'],
            'Mertua Laki-Laki' => [$graph['fatherInLaw'], 'Mertua'],
            'Mertua Perempuan' => [$graph['motherInLaw'], 'Mertua'],
            'Menantu' => [$graph['daughterInLaw'], 'Menantu'],
            'Buyut Laki-Laki' => [$graph['greatGrandFather'], 'Buyut'],
            'Buyut Perempuan' => [$graph['greatGrandMother'], 'Buyut'],
            'Cicit' => [$graph['greatGrandChild'], 'Cicit'],
            'Bude Dari Pasangan Pakde' => [$graph['olderUncleWife'], 'Bude'],
        ];

        foreach ($matrix as $scenario => [$target, $expected]) {
            $result = $resolver->resolve($graph['source'], $target);

            $this->assertSame($expected, $result['relationship'], $scenario);
        }
    }

    public function test_traversal_returns_shortest_path(): void
    {
        $graph = $this->relationshipGraph();

        $path = app(RelationshipTraversalService::class)->shortestPath($graph['source'], $graph['cousin']);

        $this->assertCount(4, $path);
        $this->assertSame(['father', 'father', 'child', 'child'], array_column($path, 'relationship'));
    }

    public function test_traversal_detects_cycles_with_visited_nodes(): void
    {
        $graph = $this->relationshipGraph();

        $this->relationship($graph['cousin'], $graph['source'], 'wife');

        $path = app(RelationshipTraversalService::class)->shortestPath($graph['source'], $graph['cousin']);

        $this->assertCount(1, $path);
        $this->assertSame(['spouse'], array_column($path, 'relationship'));
    }

    public function test_resolver_returns_null_for_disconnected_members(): void
    {
        $graph = $this->relationshipGraph();
        $stranger = $this->member($graph['family'], 'Stranger', 'male', '1990-01-01', $graph['user']);

        $result = app(RelationshipResolverService::class)->resolve($graph['source'], $stranger);

        $this->assertNull($result['relationship']);
        $this->assertSame([], $result['path']);
    }

    public function test_resolver_rejects_members_from_different_families(): void
    {
        $graph = $this->relationshipGraph();
        $other = Family::factory()->create(['created_by' => $graph['user']->id]);
        $stranger = $this->member($other, 'Orang Lain', 'male', '1990-01-01', $graph['user']);

        $this->expectException(ValidationException::class);

        app(RelationshipResolverService::class)->resolve($graph['source'], $stranger);
    }

    public function test_resolver_names_child_and_spouse_gender_variants(): void
    {
        $graph = $this->relationshipGraph();
        $resolver = app(RelationshipResolverService::class);

        $this->assertSame('Anak Perempuan', $resolver->resolve($graph['source'], $graph['daughter'])['relationship']);
        $this->assertSame('Anak Laki-Laki', $resolver->resolve($graph['source'], $graph['son'])['relationship']);
        $this->assertSame('Istri', $resolver->resolve($graph['source'], $graph['spouse'])['relationship']);
        $this->assertSame('Suami', $resolver->resolve($graph['femalePartner'], $graph['maleSpouse'])['relationship']);
        $this->assertSame('Pasangan', $resolver->resolve($graph['source'], $graph['unknownSpouse'])['relationship']);
        $this->assertSame('Cucu Perempuan', $resolver->resolve($graph['source'], $graph['grandChild'])['relationship']);
        $this->assertSame('Cucu Laki-Laki', $resolver->resolve($graph['source'], $graph['maleGrandChild'])['relationship']);
        $this->assertSame('Cucu', $resolver->resolve($graph['source'], $graph['unknownGrandChild'])['relationship']);
    }

    public function test_resolver_returns_null_for_unrecognized_path_sequence(): void
    {
        $graph = $this->relationshipGraph();
        $path = [
            ['from_member_id' => $graph['source']->id, 'to_member_id' => $graph['father']->id, 'relationship' => 'father'],
            ['from_member_id' => $graph['father']->id, 'to_member_id' => $graph['olderUncle']->id, 'relationship' => 'spouse'],
        ];

        $result = app(RelationshipResolverService::class)->nameFromPath($graph['source'], $graph['olderUncle'], $path);

        $this->assertNull($result);
    }

    public function test_traversal_returns_empty_for_cross_family_and_same_member(): void
    {
        $graph = $this->relationshipGraph();
        $other = Family::factory()->create(['created_by' => $graph['user']->id]);
        $stranger = $this->member($other, 'Orang Lain', 'male', '1990-01-01', $graph['user']);
        $traversal = app(RelationshipTraversalService::class);

        $this->assertSame([], $traversal->shortestPath($graph['source'], $stranger));
        $this->assertSame([], $traversal->shortestPath($graph['source'], $graph['source']));
    }

    public function test_traversal_reads_cached_path_on_second_call(): void
    {
        $graph = $this->relationshipGraph();
        $traversal = app(RelationshipTraversalService::class);

        $first = $traversal->shortestPath($graph['source'], $graph['cousin']);
        $second = $traversal->shortestPath($graph['source'], $graph['cousin']);

        $this->assertSame($first, $second);
        $this->assertCount(4, $second);
    }

    public function test_traversal_supports_child_edges_and_parent_gender(): void
    {
        $graph = $this->relationshipGraph();
        $traversal = app(RelationshipTraversalService::class);

        $parentMale = $this->member($graph['family'], 'Bapak', 'male', '1960-01-01', $graph['user']);
        $childA = $this->member($graph['family'], 'Anak A', 'male', '1990-01-01', $graph['user']);
        $this->relationship($childA, $parentMale, 'child');
        $this->assertSame(['father'], array_column($traversal->shortestPath($childA, $parentMale), 'relationship'));

        $parentFemale = $this->member($graph['family'], 'Ibu', 'female', '1962-01-01', $graph['user']);
        $childB = $this->member($graph['family'], 'Anak B', 'male', '1991-01-01', $graph['user']);
        $this->relationship($childB, $parentFemale, 'child');
        $this->assertSame(['mother'], array_column($traversal->shortestPath($childB, $parentFemale), 'relationship'));

        $parentUnknown = $this->member($graph['family'], 'Orang Tua', null, '1963-01-01', $graph['user']);
        $childC = $this->member($graph['family'], 'Anak C', 'male', '1992-01-01', $graph['user']);
        $this->relationship($childC, $parentUnknown, 'child');
        $this->assertSame(['parent'], array_column($traversal->shortestPath($childC, $parentUnknown), 'relationship'));
    }

    public function test_traversal_ignores_soft_deleted_members(): void
    {
        $graph = $this->relationshipGraph();
        $traversal = app(RelationshipTraversalService::class);
        $intermediate = $this->member($graph['family'], 'Penerus', 'male', '1980-01-01', $graph['user']);
        $targetA = $this->member($graph['family'], 'Target A', 'male', '2000-01-01', $graph['user']);
        $targetB = $this->member($graph['family'], 'Target B', 'male', '2001-01-01', $graph['user']);

        $this->relationship($graph['source'], $intermediate, 'father');
        $this->relationship($intermediate, $targetA, 'father');
        $this->relationship($intermediate, $targetB, 'father');

        $this->assertCount(2, $traversal->shortestPath($graph['source'], $targetA));

        $intermediate->delete();

        $this->assertSame([], $traversal->shortestPath($graph['source'], $targetB));
    }

    /**
     * @return array<string, mixed>
     */
    private function relationshipGraph(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['created_by' => $user->id]);

        $greatGrandFather = $this->member($family, 'Buyut Ahmad', 'male', '1920-01-01', $user);
        $greatGrandMother = $this->member($family, 'Buyut Aminah', 'female', '1922-01-01', $user);
        $grandFather = $this->member($family, 'Kakek Budi', 'male', '1945-01-01', $user);
        $grandMother = $this->member($family, 'Nenek Siti', 'female', '1947-01-01', $user);
        $father = $this->member($family, 'Ayah Dedi', 'male', '1970-01-01', $user);
        $mother = $this->member($family, 'Ibu Rini', 'female', '1972-01-01', $user);
        $source = $this->member($family, 'Saya Arif', 'male', '1995-01-01', $user);
        $brother = $this->member($family, 'Kakak Bima', 'male', '1992-01-01', $user);
        $sister = $this->member($family, 'Adik Citra', 'female', '1998-01-01', $user);
        $olderUncle = $this->member($family, 'Pakde Eko', 'male', '1965-01-01', $user);
        $olderUncleWife = $this->member($family, 'Istri Pakde', 'female', '1966-01-01', $user);
        $youngerUncle = $this->member($family, 'Om Fajar', 'male', '1975-01-01', $user);
        $olderAunt = $this->member($family, 'Bude Gita', 'female', '1968-01-01', $user);
        $youngerAunt = $this->member($family, 'Tante Hana', 'female', '1978-01-01', $user);
        $cousin = $this->member($family, 'Sepupu Indra', 'male', '1997-01-01', $user);
        $nephew = $this->member($family, 'Keponakan Jaya', 'male', '2020-01-01', $user);
        $spouse = $this->member($family, 'Istri Kania', 'female', '1996-01-01', $user);
        $fatherInLaw = $this->member($family, 'Mertua Laki', 'male', '1968-01-01', $user);
        $motherInLaw = $this->member($family, 'Mertua Perempuan', 'female', '1969-01-01', $user);
        $son = $this->member($family, 'Anak Laki', 'male', '2018-01-01', $user);
        $daughter = $this->member($family, 'Anak Perempuan', 'female', '2020-01-01', $user);
        $daughterInLaw = $this->member($family, 'Menantu Perempuan', 'female', '2019-01-01', $user);
        $grandChild = $this->member($family, 'Cucu', 'female', '2040-01-01', $user);
        $greatGrandChild = $this->member($family, 'Cicit', 'male', '2060-01-01', $user);
        $maleSpouse = $this->member($family, 'Suami Lain', 'male', '1995-01-01', $user);
        $femalePartner = $this->member($family, 'Pasangan Wanita', 'female', '1997-01-01', $user);
        $unknownSpouse = $this->member($family, 'Pasangan Tanpa Gender', null, '1994-01-01', $user);
        $maleGrandChild = $this->member($family, 'Cucu Laki', 'male', '2041-01-01', $user);
        $unknownGrandChild = $this->member($family, 'Cucu Tanpa Gender', null, '2042-01-01', $user);

        $this->relationship($greatGrandFather, $grandFather, 'father');
        $this->relationship($greatGrandMother, $grandFather, 'mother');
        $this->relationship($grandFather, $father, 'father');
        $this->relationship($grandMother, $father, 'mother');
        $this->relationship($grandFather, $olderUncle, 'father');
        $this->relationship($grandMother, $olderUncle, 'mother');
        $this->relationship($grandFather, $youngerUncle, 'father');
        $this->relationship($grandMother, $youngerUncle, 'mother');
        $this->relationship($grandFather, $olderAunt, 'father');
        $this->relationship($grandMother, $olderAunt, 'mother');
        $this->relationship($grandFather, $youngerAunt, 'father');
        $this->relationship($grandMother, $youngerAunt, 'mother');
        $this->relationship($olderUncle, $cousin, 'father');
        $this->relationship($olderUncle, $olderUncleWife, 'husband');
        $this->relationship($father, $source, 'father');
        $this->relationship($mother, $source, 'mother');
        $this->relationship($father, $brother, 'father');
        $this->relationship($mother, $brother, 'mother');
        $this->relationship($father, $sister, 'father');
        $this->relationship($mother, $sister, 'mother');
        $this->relationship($brother, $nephew, 'father');
        $this->relationship($source, $spouse, 'husband');
        $this->relationship($source, $unknownSpouse, 'husband');
        $this->relationship($femalePartner, $maleSpouse, 'wife');
        $this->relationship($fatherInLaw, $spouse, 'father');
        $this->relationship($motherInLaw, $spouse, 'mother');
        $this->relationship($source, $son, 'father');
        $this->relationship($spouse, $son, 'mother');
        $this->relationship($source, $daughter, 'father');
        $this->relationship($spouse, $daughter, 'mother');
        $this->relationship($son, $daughterInLaw, 'husband');
        $this->relationship($daughter, $grandChild, 'mother');
        $this->relationship($daughter, $maleGrandChild, 'mother');
        $this->relationship($daughter, $unknownGrandChild, 'mother');
        $this->relationship($grandChild, $greatGrandChild, 'mother');

        return compact(
            'user',
            'family',
            'source',
            'father',
            'mother',
            'grandFather',
            'grandMother',
            'greatGrandFather',
            'greatGrandMother',
            'brother',
            'sister',
            'olderUncle',
            'olderUncleWife',
            'youngerUncle',
            'olderAunt',
            'youngerAunt',
            'cousin',
            'nephew',
            'fatherInLaw',
            'motherInLaw',
            'daughterInLaw',
            'greatGrandChild',
            'son',
            'daughter',
            'spouse',
            'grandChild',
            'maleSpouse',
            'femalePartner',
            'unknownSpouse',
            'maleGrandChild',
            'unknownGrandChild',
        );
    }

    private function member(Family $family, string $name, ?string $gender, string $birthDate, User $user): FamilyMember
    {
        return FamilyMember::factory()->create([
            'family_id' => $family->id,
            'full_name' => $name,
            'gender' => $gender,
            'birth_date' => $birthDate,
            'created_by' => $user->id,
        ]);
    }

    private function relationship(FamilyMember $source, FamilyMember $target, string $type): MemberRelationship
    {
        return MemberRelationship::factory()->create([
            'family_id' => $source->family_id,
            'source_member_id' => $source->id,
            'target_member_id' => $target->id,
            'relationship_type' => $type,
        ]);
    }
}
