<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\GraphQL\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

abstract class CrudBuilder implements MappingInterface
{
    protected $types = [
        'User'   => [],
        'Flight' => [
            'orderBy' => 'name',
        ],
        'LauncherCategory'             => [],
        'LauncherComponent'            => [],
        'LauncherType'                 => [],
        'LauncherVersion'              => [],
        'DueType'                      => [],
        'OperationalSteeringCommittee' => [],
        'TechnicalCommittee'           => ['mutation' => false],
        'TechnicalCommitteeAriane'     => [],
        'TechnicalCommitteeAtb'        => [],
        'TechnicalCommitteeCdc'        => [],
        'TechnicalCommitteeSoyuz'      => [],
        'TechnicalCommitteeVega'       => [],
        'Measure'                      => [],
        'MeasureSet'                   => [],
        'MeasureSubset'                => [],
        'Unit'                         => [],
        'Anomaly'                      => [],
        'AnomalyClass'                 => [],
        'Action'                       => [],
        'AnomalyAnnexe'                => [],
        'Progress'                     => [],
    ];

    protected function getEntityIdType(string $type): string
    {
        return sprintf('%sId', $type);
    }
}
