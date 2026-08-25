<?php

namespace Tests\Unit\Deployment;

use PHPUnit\Framework\TestCase;

final class DeploymentStateTransitionContractTest extends TestCase
{
    private string $class =
        \App\Services\Deployment\DeploymentStateTransitionPolicy::class;

    public function test_transition_policy_exists(): void
    {
        $this->assertTrue(
            class_exists($this->class),
            'DeploymentStateTransitionPolicy is not implemented.'
        );
    }

    public function test_policy_exposes_can_transition(): void
    {
        if (!class_exists($this->class)) {
            $this->fail(
                'DeploymentStateTransitionPolicy is not implemented.'
            );
        }

        $this->assertTrue(
            method_exists($this->class, 'canTransition'),
            'DeploymentStateTransitionPolicy::canTransition() is required.'
        );
    }

    /**
     * @dataProvider legalTransitions
     */
    public function test_legal_transitions_are_allowed(
        string $from,
        string $to
    ): void {
        if (!class_exists($this->class)) {
            $this->fail(
                'DeploymentStateTransitionPolicy is not implemented.'
            );
        }

        $policy = new $this->class();

        $this->assertTrue(
            $policy->canTransition($from, $to),
            "{$from} -> {$to} must be allowed."
        );
    }

    /**
     * @dataProvider illegalTransitions
     */
    public function test_illegal_transitions_are_rejected(
        string $from,
        string $to
    ): void {
        if (!class_exists($this->class)) {
            $this->fail(
                'DeploymentStateTransitionPolicy is not implemented.'
            );
        }

        $policy = new $this->class();

        $this->assertFalse(
            $policy->canTransition($from, $to),
            "{$from} -> {$to} must be rejected."
        );
    }

    public static function legalTransitions(): array
    {
        return [
            'uploaded_to_validated' => [
                'uploaded',
                'validated',
            ],

            'uploaded_to_failed' => [
                'uploaded',
                'failed',
            ],

            'validated_to_installed' => [
                'validated',
                'installed',
            ],

            'validated_to_failed' => [
                'validated',
                'failed',
            ],

            'installed_to_rolled_back' => [
                'installed',
                'rolled_back',
            ],

            'installed_to_superseded' => [
                'installed',
                'superseded',
            ],
        ];
    }

    public static function illegalTransitions(): array
    {
        return [
            'uploaded_directly_to_installed' => [
                'uploaded',
                'installed',
            ],

            'uploaded_directly_to_rolled_back' => [
                'uploaded',
                'rolled_back',
            ],

            'uploaded_directly_to_superseded' => [
                'uploaded',
                'superseded',
            ],

            'validated_back_to_uploaded' => [
                'validated',
                'uploaded',
            ],

            'validated_directly_to_rolled_back' => [
                'validated',
                'rolled_back',
            ],

            'installed_back_to_validated' => [
                'installed',
                'validated',
            ],

            'installed_to_failed' => [
                'installed',
                'failed',
            ],

            'failed_to_installed' => [
                'failed',
                'installed',
            ],

            'failed_to_validated' => [
                'failed',
                'validated',
            ],

            'rolled_back_to_installed' => [
                'rolled_back',
                'installed',
            ],

            'superseded_to_installed' => [
                'superseded',
                'installed',
            ],

            'same_state_uploaded' => [
                'uploaded',
                'uploaded',
            ],

            'same_state_validated' => [
                'validated',
                'validated',
            ],

            'same_state_installed' => [
                'installed',
                'installed',
            ],

            'unknown_source_state' => [
                'unknown',
                'validated',
            ],

            'unknown_target_state' => [
                'uploaded',
                'unknown',
            ],
        ];
    }
}
