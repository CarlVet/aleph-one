<?php

namespace App\Models;

use App\Enums\ParasiteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Parasites extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ParasiteStatus::class,
            'date_identified' => 'date',
        ];
    }

    public function parasite_species()
    {
        return $this->belongsTo(ParasiteSpecies::class);
    }

    public function parasite_samples()
    {
        return $this->hasMany(ParasiteSamples::class);
    }

    public function parasites_origin()
    {
        return $this->morphTo()->morphWith([
            HumanSamples::class => [
                'humans',
                'sample_types',
                'sampling_sites',
                'tubes',
            ],
            AnimalSamples::class => [
                'animals',
                'animals.animal_species',
                'sample_types',
                'sampling_sites',
                'tubes',
            ],
            EnvironmentSamples::class => [
                'environment_sample_types',
                'sampling_sites',
                'tubes',
            ],
        ]);
    }

    public function animal_samples()
    {
        return $this->belongsTo(AnimalSamples::class, 'parasites_origin_id')
            ->where('parasites_origin_type', AnimalSamples::class);
    }

    public function human_samples()
    {
        return $this->belongsTo(HumanSamples::class, 'parasites_origin_id')
            ->where('parasites_origin_type', HumanSamples::class);
    }

    public function environment_samples()
    {
        return $this->belongsTo(EnvironmentSamples::class, 'parasites_origin_id')
            ->where('parasites_origin_type', EnvironmentSamples::class);
    }

    public function locations()
    {
        return $this->belongsTo(Locations::class);
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

    public function experiments()
    {
        return $this->morphMany(Experiments::class, 'experiments_content');
    }

    public function cultures()
    {
        return $this->morphMany(Cultures::class, 'cultures_content');
    }

    public function pools()
    {
        return $this->morphMany(PoolContents::class, 'samples');
    }

    public function tubes()
    {
        return $this->morphMany(Tubes::class, 'tubes_content');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ParasitePhoto::class, 'parasites_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(ParasiteObservation::class, 'parasites_id')
            ->orderByDesc('observed_at')
            ->orderByDesc('id');
    }

    public function latestObservation(): HasOne
    {
        return $this->hasOne(ParasiteObservation::class, 'parasites_id')
            ->latestOfMany('observed_at');
    }

    public function latestPhoto(): HasOne
    {
        return $this->hasOne(ParasitePhoto::class, 'parasites_id')->latestOfMany('id');
    }

    public function syncCoverPhotoPath(): void
    {
        $latest = $this->observations()
            ->with('photo')
            ->orderByDesc('observed_at')
            ->orderByDesc('id')
            ->first();

        $this->update(['photo_path' => $latest?->photo?->photo_path]);
    }

    /**
     * SQL subquery returning alias_code from the parasite's storage tube
     * (a tube whose content is a parasite sample belonging to this parasite).
     */
    public static function storageTubeAliasSubquery(string $parasiteIdColumn = 'parasites.id'): string
    {
        $parasiteSampleType = ParasiteSamples::class;

        return "(SELECT tubes.alias_code
            FROM tubes
            INNER JOIN parasite_samples ON tubes.tubes_content_id = parasite_samples.id
                AND tubes.tubes_content_type = '{$parasiteSampleType}'
            LEFT JOIN parasite_sample_types ON parasite_samples.parasite_sample_types_id = parasite_sample_types.id
            WHERE parasite_samples.parasites_id = {$parasiteIdColumn}
            ORDER BY CASE WHEN LOWER(COALESCE(parasite_sample_types.name, '')) = 'whole parasite' THEN 0 ELSE 1 END, tubes.id ASC
            LIMIT 1)";
    }
}
