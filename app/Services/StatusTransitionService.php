<?php

namespace App\Services;

use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Order;
use App\Models\OrderStatusTimestamp;
use Illuminate\Support\Facades\Log;

class StatusTransitionService
{
    /**
     * Defines every valid from → [to] transition.
     * Key = current status ID, value = array of allowed next status IDs.
     */


    // show status with names instead of ids
    const STATUS_NAMES = [
        1 => 'New',
        2 => 'Scheduled',
        3 => 'Awaiting Dispatch',
        4 => 'Dispatched',
        5 => 'In transit',
        6 => 'Delivered',
        7 => 'Awaiting Return',
        8 => 'Returned',
        9 => 'Return Received',
        10 => 'Cancelled',
        11 => 'Pending',
        12 => 'Out of Stock',
        13 => 'Duplicate',
        14 => 'Undispatched',
    ];


    const ALLOWED_TRANSITIONS = [
        1  => [2, 11, 10],       // New       → Scheduled, Pending, Cancelled
        11 => [2, 10],           // Pending   → Scheduled, Cancelled
        2  => [3, 10, 13],       // Scheduled → Awaiting Dispatch, Cancelled, Duplicate
        3  => [4],           // Awaiting Dispatch → Dispatched, 
        4  => [5],       // Dispatched → In Transit,
        14 => [10],               // Undispatched → Cancelled 
        14 => [2],               // Undispatched → scheduled 
        5  => [6],           // In Transit → Delivered, 
        6  => [8],               // Delivered → Return
        7  => [9],               // Awaiting Return → Returned Received
    ];

    /**
     * Transitions that require elevated roles.
     * Key = to_status_id, value = required role(s).
     */
    const ROLE_GATED_TRANSITIONS = [
        // Cancellation after dispatch needs ops_manager or admin
        10 => [
            'from' => [4, 5],   // Dispatched or In Transit → Cancelled
            'roles' => ['ops_manager', 'admin'],
        ],
    ];

    /**
     * Validate and apply a status transition.
     * Creates the OrderStatusTimestamp — does NOT touch stock directly.
     * Stock changes are handled by OrderStatusTimestampObserver → StockService.
     */
    public function apply(Order $order, int $newStatusId, ?string $notes = null): OrderStatusTimestamp
    {
        $currentStatusId = $this->getCurrentStatusId($order);

        $this->validateTransition($order, $currentStatusId, $newStatusId);
        $this->validateRole($currentStatusId, $newStatusId);

        $timestamp = OrderStatusTimestamp::create([
            'order_id'   => $order->id,
            'status_id'  => $newStatusId,
            'status_notes' => $notes,
            'timestamp'  => now(),
        ]);

        Log::info('StatusTransition: applied', [
            'order_id'    => $order->id,
            'from_status' => $currentStatusId,
            'to_status'   => $newStatusId,
            'user_id'     => auth()->id(),
        ]);

        return $timestamp;
    }

    /**
     * Check if a transition is valid without applying it.
     */
    public function canTransition(Order $order, int $newStatusId): bool
    {
        $currentStatusId = $this->getCurrentStatusId($order);

        if ($currentStatusId === null) {
            return $newStatusId === 1; // Must start at New
        }

        return in_array($newStatusId, self::ALLOWED_TRANSITIONS[$currentStatusId] ?? []);
    }

    /**
     * Get the current status ID from the latest OrderStatusTimestamp.
     */
    public function getCurrentStatusId(Order $order): ?int
    {
        return $order->orderStatusTimestamps()
            ->latest('timestamp')
            ->value('status_id');
    }

    /**
     * Check whether this order was ever Scheduled (committed stock).
     * Used by StockService before calling releaseCommittedStock.
     */
    public function wasEverScheduled(Order $order): bool
    {
        return $order->orderStatusTimestamps()
            ->where('status_id', 2)
            ->exists();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private guards
    // ──────────────────────────────────────────────────────────────────────────

    private function validateTransition(?int $currentStatusId, int $newStatusId): void
    {
        // First status must be New (id=1)
        if ($currentStatusId === null) {
            if ($newStatusId !== 1) {
                throw new InvalidStatusTransitionException(
                    "Orders must start with status New (id=1). Attempted: {$newStatusId}"
                );
            }
            return;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$currentStatusId] ?? [];

        if (!in_array($newStatusId, $allowed)) {
            throw new InvalidStatusTransitionException(
                "Invalid transition: status {$currentStatusId} → {$newStatusId}. " .
                    "Allowed: [" . implode(', ', $allowed) . "]"
            );
        }
    }

    private function validateRole(Order $order, ?int $currentStatusId, int $newStatusId): void
    {
        $gate = self::ROLE_GATED_TRANSITIONS[$newStatusId] ?? null;

        if (!$gate) {
            return; // No role restriction for this target status
        }

        // Only restricted when coming from specific statuses
        if (!in_array($currentStatusId, $gate['from'])) {
            return;
        }

        $user = auth()->user();

        if (!$user || !$user->hasAnyRole($gate['roles'])) {
            throw new InvalidStatusTransitionException(
                "Transition to status {$newStatusId} from {$currentStatusId} " .
                    "requires one of these roles: " . implode(', ', $gate['roles'])
            );
        }
    }
}
