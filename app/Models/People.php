<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class People extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date_birth' => 'date',
    ];

    public function getNameAttribute(): string
    {
        return trim((string) ($this->first_name ?? '').' '.(string) ($this->last_name ?? ''));
    }

    public function contactEmail(): ?string
    {
        $userEmail = $this->users?->email;

        return filled($userEmail) ? $userEmail : $this->email;
    }

    public function organizations()
    {
        return $this->belongsTo(Organizations::class);
    }

    public function departments()
    {
        return $this->belongsTo(Departments::class);
    }

    public function human_samples()
    {
        return $this->hasMany(HumanSamples::class);
    }

    public function animal_samples()
    {
        return $this->hasMany(AnimalSamples::class);
    }

    public function animal_medications()
    {
        return $this->hasMany(AnimalMedication::class);
    }

    public function animal_vaccinations()
    {
        return $this->hasMany(AnimalVaccination::class);
    }

    public function environment_samples()
    {
        return $this->hasMany(EnvironmentSamples::class);
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

    public function cultures()
    {
        return $this->hasMany(Cultures::class);
    }

    public function pools()
    {
        return $this->hasMany(Pools::class);
    }

    public function users()
    {
        return $this->hasOne(User::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Projects::class, 'projects_people')
            ->withPivot('role', 'date_joined', 'permission', 'module_permissions')
            ->withTimestamps();
    }

    public function fundings()
    {
        return $this->hasMany(Fundings::class, 'recipient_id');
    }

    public function subProjects()
    {
        return $this->belongsToMany(SubProject::class, 'sub_project_people')
            ->withTimestamps();
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
}
