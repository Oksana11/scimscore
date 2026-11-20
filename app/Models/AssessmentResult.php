<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'total', 'self_care', 'respiration_sphincter', 'mobility',
    'sciarmi_total', 'sciarmi_self_care', 'sciarmi_respiration_sphincter', 'sciarmi_mobility', 'grade',
])]
class AssessmentResult extends Model
{
    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'self_care' => 'integer',
            'respiration_sphincter' => 'integer',
            'mobility' => 'integer',
            'sciarmi_total' => 'decimal:2',
            'sciarmi_self_care' => 'decimal:2',
            'sciarmi_respiration_sphincter' => 'decimal:2',
            'sciarmi_mobility' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Assessment, $this> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }
}
