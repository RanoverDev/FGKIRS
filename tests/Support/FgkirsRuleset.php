<?php

namespace Tests\Support;

use Domain\Championship\AgeDivision;
use Domain\Championship\BeltDivision;
use Domain\Championship\Discipline;
use Domain\Championship\Ruleset;
use Domain\Championship\WeightClass;

/** Espelho em PHP do seed 004_seed_ruleset_fgkirs.sql. */
final class FgkirsRuleset
{
    public static function build(): Ruleset
    {
        $named = [
            new AgeDivision(1, 'MIR', 'Mirim', 7, 10, 1),
            new AgeDivision(2, 'INF', 'Infantil', 11, 12, 2),
            new AgeDivision(3, 'IJU', 'Infanto Juvenil', 13, 14, 3),
            new AgeDivision(4, 'JUV', 'Juvenil', 15, 17, 4),
            new AgeDivision(5, 'ADU', 'Adulto', 18, 35, 5),
            new AgeDivision(6, 'MAS', 'Master', 36, null, 6),
        ];
        $team = [
            new AgeDivision(7, 'EQ-14', 'Até 14 anos', null, 14, 7),
            new AgeDivision(8, 'EQ-15', '15 anos acima', 15, null, 8),
        ];

        $weights = [];
        $id = 0;
        foreach ($named as $age) {
            foreach (['M', 'F'] as $gender) {
                $weights[] = new WeightClass(++$id, $age->id, $gender, 'até 50kg (provisório)', null, 50.0, 1);
                $weights[] = new WeightClass(++$id, $age->id, $gender, 'acima de 50kg (provisório)', 50.0, null, 2);
            }
        }

        $namedIds = array_map(static fn (AgeDivision $a) => $a->id, $named);
        $teamIds  = array_map(static fn (AgeDivision $a) => $a->id, $team);

        return new Ruleset(
            id: 1,
            name: 'Regulamento FGKIRS (atual)',
            agePolicy: 'event_date',
            ageDivisions: [...$named, ...$team],
            beltDivisions: [
                new BeltDivision(1, 'COL', 'Faixa Colorida', 1, 10, 1),
                new BeltDivision(2, 'PRE', 'Faixa Preta', 11, 20, 2),
            ],
            weightClasses: $weights,
            disciplines: [
                new Discipline(1, 'KTI', 'Kata Individual', 'kata', 'individual', true, true, false, null, null, $namedIds, 1),
                new Discipline(2, 'KMI', 'Kumite Individual', 'kumite', 'individual', true, true, true, null, null, $namedIds, 2),
                new Discipline(3, 'KTE', 'Kata Equipe', 'kata', 'team', true, false, false, 3, null, $teamIds, 3),
                new Discipline(4, 'KME', 'Kumite Equipe (Revezamento)', 'kumite', 'team', true, false, false, 3, null, $namedIds, 4),
            ],
        );
    }
}
