<?php

namespace App\Services\Deployment;

final class DeploymentStateTransitionPolicy
{
    private const TRANSITIONS = [
        'uploaded' => [
            'validated',
            'failed',
        ],

        'validated' => [
            'installed',
            'failed',
        ],

        'installed' => [
            'rolled_back',
            'superseded',
        ],

        'failed' => [],

        'rolled_back' => [],

        'superseded' => [],
    ];

    public function canTransition(
        string $from,
        string $to
    ): bool {
        if (!array_key_exists($from, self::TRANSITIONS)) {
            return false;
        }

        if (!array_key_exists($to, self::TRANSITIONS)) {
            return false;
        }

        return in_array(
            $to,
            self::TRANSITIONS[$from],
            true
        );
    }
}