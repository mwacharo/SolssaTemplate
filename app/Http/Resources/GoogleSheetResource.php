<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoogleSheetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sheet_name' => $this->sheet_name,
            'post_spreadsheet_id' => $this->post_spreadsheet_id,
            'active' => $this->active,
            'auto_sync' => $this->auto_sync,
            'sync_all' => $this->sync_all,
            'sync_interval' => $this->sync_interval,
            'last_order_synced' => $this->last_order_synced,
            'last_order_upload' => $this->last_order_upload,
            'last_product_synced' => $this->last_product_synced,
            'is_current' => $this->is_current,
            'order_prefix' => $this->order_prefix,
            'vendor_id' => $this->vendor_id,
            'lastUpdatedOrderNumber' => $this->lastUpdatedOrderNumber,
            'ou_id' => $this->ou_id,
        ];
    }
}
