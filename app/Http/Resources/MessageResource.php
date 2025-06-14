<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'messageable_type' => $this->messageable_type,
            'messageable_id' => $this->messageable_id,
            'channel' => $this->channel,
            'provider' => $this->provider,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'from' => $this->from,
            'to' => $this->to,
            'content' => $this->content,
            'body' => $this->body,
            'message_type' => $this->message_type,
            'is_template' => (bool) $this->is_template,
            'template_id' => $this->template_id,
            'whatsapp_message_type' => $this->whatsapp_message_type,
            'media_url' => $this->media_url,
            'media_mime_type' => $this->media_mime_type,
            'status' => $this->status,
            'message_status' => $this->message_status,
            'external_message_id' => $this->external_message_id,
            'reply_to_message_id' => $this->reply_to_message_id,
            'error_message' => $this->error_message,
            'timestamp' => optional($this->timestamp)->toDateTimeString(),
            'sent_at' => optional($this->sent_at)->toDateTimeString(),
            'delivered_at' => optional($this->delivered_at)->toDateTimeString(),
            'read_at' => optional($this->read_at)->toDateTimeString(),
            'failed_at' => optional($this->failed_at)->toDateTimeString(),
            'direction' => $this->direction,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
