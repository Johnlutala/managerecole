<?php

namespace App\Service;

final class CotationStructure
{
    /**
     * @return array<int, array{nom: string, configuration: string, periodes: array<int, array{libelle: string, nom: string}>, total: string}>
     */
    public function groupes(string $structure): array
    {
        if ($structure === 'semestre') {
            return [
                $this->groupe('1ER SEMESTRE', 'Semestre I', [
                    ['1er P', 'Période 1'],
                    ['2ème P', 'Période 2'],
                    ['Exam', 'Examen 1'],
                ]),
                $this->groupe('SECOND SEMESTRE', 'Semestre II', [
                    ['3ème P', 'Période 3'],
                    ['4ème P', 'Période 4'],
                    ['Exam', 'Examen 2'],
                ]),
            ];
        }

        if ($structure === 'trimestre') {
            return [
                $this->groupe('1ER TRIMESTRE', 'Trimestre I', [
                    ['1er P', 'Période 1'],
                    ['2ème P', 'Période 2'],
                    ['Exam', 'Examen 1'],
                ]),
                $this->groupe('2ÈME TRIMESTRE', 'Trimestre II', [
                    ['3ème P', 'Période 3'],
                    ['4ème P', 'Période 4'],
                    ['Exam', 'Examen 2'],
                ]),
                $this->groupe('3ÈME TRIMESTRE', 'Trimestre III', [
                    ['5ème P', 'Période 5'],
                    ['6ème P', 'Période 6'],
                    ['Exam', 'Examen 3'],
                ]),
            ];
        }

        throw new \InvalidArgumentException(sprintf('Structure de cotation inconnue : %s.', $structure));
    }

    /**
     * @param array<int, array{0: string, 1: string}> $periodes
     * @return array{nom: string, configuration: string, periodes: array<int, array{libelle: string, nom: string}>, total: string}
     */
    private function groupe(string $nom, string $configuration, array $periodes): array
    {
        return [
            'nom' => $nom,
            'configuration' => $configuration,
            'periodes' => array_map(
                static fn (array $periode): array => ['libelle' => $periode[0], 'nom' => $periode[1]],
                $periodes
            ),
            'total' => 'T.S',
        ];
    }
}
