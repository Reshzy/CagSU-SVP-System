<?php

namespace App\Services;

use App\Models\BacSignatory;
use Illuminate\Support\Facades\Log;

class SignatoryLoaderService
{
    /**
     * Load active signatories from bac_signatories table
     *
     * @param  array  $requiredPositions  Array of required position keys
     * @param  bool  $throwOnMissing  Whether to throw exception if positions are missing
     * @return array Formatted signatory data
     *
     * @throws \Exception if required positions are not configured and $throwOnMissing is true
     */
    public function loadActiveSignatories(array $requiredPositions, bool $throwOnMissing = false): array
    {
        // Load all active signatories
        $signatories = BacSignatory::with('user')
            ->active()
            ->orderBy('updated_at', 'desc')
            ->get();

        if ($signatories->isEmpty()) {
            if ($throwOnMissing) {
                throw new \Exception('No BAC signatories configured. Please set up signatories first.');
            }

            return [];
        }

        // Group by position and take the most recently updated one for each
        $groupedSignatories = $signatories->groupBy('position');

        $result = [];

        foreach ($requiredPositions as $position) {
            // Handle special position mappings
            $searchPosition = $this->mapPositionName($position);

            // Handle BAC members specially (need 3 members)
            if (in_array($position, ['bac_member_1', 'bac_member_2', 'bac_member_3'])) {
                $members = $groupedSignatories->get('bac_member', collect());
                $memberIndex = (int) substr($position, -1) - 1; // Extract index (0, 1, 2)

                if (isset($members[$memberIndex])) {
                    $result[$position] = $this->formatSignatoryData($members[$memberIndex]);
                } elseif ($throwOnMissing) {
                    throw new \Exception('BAC Member '.($memberIndex + 1).' is not configured.');
                }
            } else {
                // Regular position lookup
                if ($groupedSignatories->has($searchPosition)) {
                    // Take the first (most recently updated) signatory for this position
                    $signatory = $groupedSignatories->get($searchPosition)->first();

                    $result[$position] = $this->formatSignatoryData($signatory);
                } elseif ($throwOnMissing) {
                    $positionName = $this->getPositionDisplayName($position);
                    throw new \Exception("{$positionName} is not configured in BAC Signatories.");
                }
            }
        }

        Log::info('Loaded signatories from BAC Signatories setup', [
            'requested_positions' => $requiredPositions,
            'found_positions' => array_keys($result),
        ]);

        return $result;
    }

    /**
     * Format signatory data for document services
     */
    public function formatSignatoryData(BacSignatory $signatory): array
    {
        return [
            'name' => $signatory->display_name,
            'prefix' => $signatory->prefix,
            'suffix' => $signatory->suffix,
        ];
    }

    /**
     * Validate that all required signatories are configured
     *
     * @throws \Exception if validation fails
     */
    public function validateSignatorySetup(array $requiredPositions): void
    {
        $this->loadActiveSignatories($requiredPositions, true);
    }

    /**
     * Get missing signatory positions
     *
     * @return array Array of missing position names
     */
    public function getMissingPositions(array $requiredPositions): array
    {
        $configured = $this->loadActiveSignatories($requiredPositions, false);
        $missing = [];

        foreach ($requiredPositions as $position) {
            if (! isset($configured[$position])) {
                $missing[] = $this->getPositionDisplayName($position);
            }
        }

        return $missing;
    }

    /**
     * Get all configured signatory positions with their status
     */
    public function getSignatoryStatus(): array
    {
        $allPositions = [
            'bac_chairman' => 'BAC Chairman',
            'bac_vice_chairman' => 'BAC Vice Chairman',
            'bac_member' => 'BAC Members (need 3)',
            'head_bac_secretariat' => 'Head, BAC Secretariat',
            'ceo' => 'CEO',
            'canvassing_officer' => 'Canvassing Officer',
        ];

        $signatories = BacSignatory::with('user')
            ->active()
            ->get()
            ->groupBy('position');

        $status = [];

        foreach ($allPositions as $position => $displayName) {
            $configured = $signatories->get($position, collect());

            if ($position === 'bac_member') {
                $status[$position] = [
                    'display_name' => $displayName,
                    'is_configured' => $configured->count() >= 3,
                    'count' => $configured->count(),
                    'required_count' => 3,
                    'signatories' => $configured->map(fn ($s) => $s->full_name)->toArray(),
                ];
            } else {
                $status[$position] = [
                    'display_name' => $displayName,
                    'is_configured' => $configured->isNotEmpty(),
                    'count' => $configured->count(),
                    'required_count' => 1,
                    'signatories' => $configured->map(fn ($s) => $s->full_name)->toArray(),
                ];
            }
        }

        return $status;
    }

    /**
     * Map position names between different document types
     */
    private function mapPositionName(string $position): string
    {
        return match ($position) {
            'bac_chairperson' => 'bac_chairman',
            'bac_member_1', 'bac_member_2', 'bac_member_3' => 'bac_member',
            default => $position,
        };
    }

    /**
     * Get human-readable position display name
     */
    private function getPositionDisplayName(string $position): string
    {
        return match ($position) {
            'bac_chairman', 'bac_chairperson' => 'BAC Chairman',
            'bac_vice_chairman' => 'BAC Vice Chairman',
            'bac_member_1' => 'BAC Member 1',
            'bac_member_2' => 'BAC Member 2',
            'bac_member_3' => 'BAC Member 3',
            'head_bac_secretariat' => 'Head, BAC Secretariat',
            'ceo' => 'CEO',
            'canvassing_officer' => 'Canvassing Officer',
            default => ucwords(str_replace('_', ' ', $position)),
        };
    }
}
