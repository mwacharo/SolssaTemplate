<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusTimestamp;

class StatusTransitionService
{
    /**
     * BUSINESS RULES BASED ON STATUS NAME (NOT ID)
     */
    const ALLOWED_TRANSITIONS = [
        'New' => ['Scheduled', 'Pending', 'Cancelled'],

        'Pending' => ['Scheduled', 'Cancelled', 'Pending'],

        'Scheduled' => ['Awaiting Dispatch', 'Cancelled', 'Duplicate', 'Pending'],

        'Awaiting Dispatch' => ['Dispatched'],

        'Dispatched' => ['In transit', 'Undispatched'],

        'In transit' => ['Delivered', 'Return'],



        'Delivered' => ['Awaiting Return'],

        'Awaiting Return' => ['Return Received'],

        'Undispatched' => ['Cancelled', 'Scheduled'],
        'Cancelled' => ['Scheduled', 'Pending'],
    ];

    const ROLE_GATED_TRANSITIONS = [
        'Cancelled' => [
            'from' => ['Dispatched', 'In transit'],
            'roles' => ['ops_manager', 'admin'],
        ],
    ];

    // ─────────────────────────────
    // GET CURRENT STATUS (SOURCE OF TRUTH)
    // ─────────────────────────────

    public function getCurrentStatusName(Order $order): ?string
    {
        return $order->latestStatus?->status?->name;
    }
    // ─────────────────────────────
    // APPLY TRANSITION
    // ─────────────────────────────

    public function apply(Order $order, string $newStatusName, ?string $notes = null): OrderStatusTimestamp
    {
        $currentStatusName = $this->getCurrentStatusName($order);

        $this->validateTransition($currentStatusName, $newStatusName);
        $this->validateRole($currentStatusName, $newStatusName);

        $status = \App\Models\Status::where('name', $newStatusName)->firstOrFail();

        return OrderStatusTimestamp::create([
            'order_id'     => $order->id,
            'status_id'    => $status->id,   // ← resolved FK
            'status_notes' => $notes,
        ]);
    }

    // ─────────────────────────────
    // VALIDATION
    // ─────────────────────────────

    // private function validateTransition(?string $current, string $new): void
    // {

    //     // ✅ Allow ONLY Pending → Pending



    //     if ($current === null) {
    //         if ($new !== 'New') {
    //             throw new \RuntimeException("Order must start with New");
    //         }
    //         return;
    //     }

    //     $allowed = self::ALLOWED_TRANSITIONS[$current] ?? [];

    //     if (!in_array($new, $allowed, true)) {
    //         throw new \RuntimeException(
    //             "Invalid transition: {$current} → {$new}"
    //         );
    //     }
    // }



    private function validateTransition(?string $current, string $new): void
    {
        // ✅ Allow Pending → Pending (self-transition)
        if ($current === 'Pending' && $new === 'Pending') {
            return;
        }

        if ($current === null) {
            if ($new !== 'New') {
                throw new \RuntimeException("Order must start with New");
            }
            return;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$current] ?? [];

        if (!in_array($new, $allowed, true)) {
            throw new \RuntimeException(
                "Invalid transition: {$current} → {$new}"
            );
        }
    }

    private function validateRole(?string $current, string $new): void
    {
        $gate = self::ROLE_GATED_TRANSITIONS[$new] ?? null;

        if (!$gate) return;

        if (!in_array($current, $gate['from'], true)) return;

        $user = auth()->user();

        if (!$user || !$user->hasAnyRole($gate['roles'])) {
            throw new \RuntimeException(
                "Transition requires roles: " . implode(', ', $gate['roles'])
            );
        }
    }

    // ─────────────────────────────
    // CHECK
    // ─────────────────────────────

    public function canTransition(Order $order, string $newStatus): bool
    {
        $current = $this->getCurrentStatusName($order);

        if ($current === null) {
            return $newStatus === 'New';
        }

        return in_array($newStatus, self::ALLOWED_TRANSITIONS[$current] ?? [], true);
    }
}
