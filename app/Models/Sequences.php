<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\HasSubProjectFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sequences extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use HasSubProjectFlag;

    protected $guarded = [];

    protected $casts = [
        'date_sequenced' => 'date',
        'is_private' => 'boolean',
    ];

    public function nucleic_acids()
    {
        return $this->belongsTo(NucleicAcids::class);
    }

    public function people()
    {
        return $this->belongsTo(People::class);
    }

    public function laboratories()
    {
        return $this->belongsTo(Laboratories::class);
    }

    public function projects()
    {
        return $this->belongsTo(Projects::class);
    }
}
