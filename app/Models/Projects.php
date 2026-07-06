<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\TracksChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Projects extends Model
{
    use HasFactory, HasPublicUuid, TracksChanges;

    protected $guarded = [];

    public function human_samples()
    {
        return $this->hasMany(HumanSamples::class);
    }

    public function cultures()
    {
        return $this->hasMany(Cultures::class);
    }

    public function pools()
    {
        return $this->hasMany(Pools::class);
    }

    public function environment_samples()
    {
        return $this->hasMany(EnvironmentSamples::class);
    }

    public function animals()
    {
        return $this->hasMany(AnimalSamples::class);
    }

    public function animal_samples()
    {
        return $this->hasMany(AnimalSamples::class);
    }

    public function parasites()
    {
        return $this->hasMany(Parasites::class);
    }

    public function parasite_samples()
    {
        return $this->hasMany(ParasiteSamples::class);
    }

    public function nucleic_acids()
    {
        return $this->hasMany(NucleicAcids::class);
    }

    public function microplastics()
    {
        return $this->hasMany(Microplastics::class);
    }

    public function sequences()
    {
        return $this->hasMany(Sequences::class);
    }

    public function experiments()
    {
        return $this->hasMany(Experiments::class);
    }

    public function people()
    {
        return $this->belongsToMany(People::class, 'projects_people')
            ->withPivot('role', 'date_joined', 'permission', 'module_permissions')
            ->withTimestamps();
    }

    public function tubes()
    {
        return $this->hasMany(Tubes::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function meta_animals()
    {
        return $this->hasMany(MetaAnimal::class);
    }

    public function meta_humans()
    {
        return $this->hasMany(MetaHuman::class);
    }

    public function meta_environments()
    {
        return $this->hasMany(MetaEnvironment::class);
    }

    public function meta_parasites()
    {
        return $this->hasMany(MetaParasite::class);
    }

    public function documents()
    {
        return $this->hasMany(Documents::class, 'projects_id');
    }

    public function fundings()
    {
        return $this->belongsToMany(Fundings::class, 'projects_fundings')->withTimestamps();
    }

    public function subProjects()
    {
        return $this->hasMany(SubProject::class, 'project_id');
    }
}
