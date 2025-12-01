<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentPerformanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    // public function toArray(Request $request): array
    // {
    //     return parent::toArray($request);
    // }




    public function toArray($request)
    {
        return [
            'name' => $this->name ?? 'Unknown Agent',
            'confirmationRate' => round($this->BuyoutRate ?? 0),
            'deliveryRate' => round($this->DeliveryRate ?? 0),
            'confirmationTrend' => $this->confirmationTrend ?? 0,
            'deliveryTrend' => $this->deliveryTrend ?? 0,
            'totalCalls' => $this->totalCalls ?? 0,
            'avgCallTime' => $this->avgCallTime ?? 0,
            'rating' => $this->rating ?? 0,
            'shift' => $this->shift ?? 'N/A',
            'experience' => $this->experience ?? 'N/A',
        ];
    }
}
