<?php

namespace App\Models;

use App\Enums\ExperimentPurpose;
use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\HasSubProjectFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experiments extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use HasSubProjectFlag;

    protected $guarded = [];

    protected $casts = [
        'outcome_quant' => 'decimal:2',
        'outcome_binary' => 'boolean',
        'date_tested' => 'date',
        'is_private' => 'boolean',
        'purpose' => ExperimentPurpose::class,
    ];

    public function protocols()
    {
        return $this->belongsTo(Protocols::class);
    }

    public function pathogens()
    {
        return $this->belongsTo(Pathogens::class);
    }

    public function projects()
    {
        return $this->belongsTo(Projects::class);
    }

    public function people()
    {
        return $this->belongsTo(People::class);
    }

    public function laboratories()
    {
        return $this->belongsTo(Laboratories::class);
    }

    public function nucleic_acids()
    {
        return $this->morphMany(NucleicAcids::class, 'nucleic_content');
    }

    public function experiments_content()
    {
        return $this->morphTo('experiments_content');
    }
}
