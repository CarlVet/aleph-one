<?php

namespace App\Models;

use App\Models\Concerns\HasSubProjectFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaParasite extends Model
{
    use HasFactory;
    use HasSubProjectFlag;

    protected $guarded = [];

    public function countries()
    {
        return $this->belongsTo(Countries::class);
    }

    public function risk_factors()
    {
        return $this->morphToMany(RiskFactors::class, 'meta', 'risk_factors_meta', 'meta_id', 'risk_factors_id');
    }

    public function studies()
    {
        return $this->belongsTo(Studies::class);
    }

    public function parasite_species()
    {
        return $this->belongsTo(ParasiteSpecies::class);
    }

    public function parasite_sample_types()
    {
        return $this->belongsTo(ParasiteSampleTypes::class);
    }

    public function pathogens()
    {
        return $this->belongsTo(Pathogens::class);
    }

    public function techniques()
    {
        return $this->belongsTo(Techniques::class);
    }

    public function projects()
    {
        return $this->belongsTo(Projects::class);
    }

    public function people()
    {
        return $this->belongsTo(People::class);
    }
}
