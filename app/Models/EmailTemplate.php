<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\EmailTemplateFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'email_templates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',       // Template name for identification
        'subject',    // Default subject line
        'body',       // HTML or text body with placeholders
        'placeholders', // Optional: JSON of placeholder fields
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'placeholders' => 'array',
    ];

    /**
     * Apply placeholder replacements to the template.
     */
    public function render(array $data): string
    {
        $rendered = $this->body;
        foreach ($data as $key => $value) {
            $rendered = str_replace('{{' . $key . '}}', $value, $rendered);
        }
        return $rendered;
    }
}
