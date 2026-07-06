<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CountriesFactory extends Factory
{
    public function definition(): array
    {

        $countries = [
            // Africa
            'Kenya', 'Tanzania', 'Uganda', 'Zimbabwe', 'Botswana', 'Namibia',
            'Mozambique', 'Zambia', 'Malawi', 'Angola', 'Democratic Republic of Congo',
            'Ethiopia', 'Sudan', 'South Sudan', 'Nigeria', 'Ghana', 'Senegal', 'Morocco',
            'Egypt', 'Algeria', 'Tunisia', 'Libya', 'Chad', 'Niger', 'Mali', 'Burkina Faso',
            'Ivory Coast', 'Liberia', 'Sierra Leone', 'Guinea', 'Guinea-Bissau', 'Gambia',
            'Mauritania', 'Cameroon', 'Central African Republic', 'Gabon', 'Congo',
            'Equatorial Guinea', 'São Tomé and Príncipe', 'Rwanda', 'Burundi', 'Comoros',
            'Madagascar', 'Mauritius', 'Seychelles', 'Mayotte', 'Réunion', 'Lesotho',
            'Eswatini', 'Djibouti', 'Eritrea', 'Somalia', 'Somaliland', 'Cape Verde',

            // Europe
            'United Kingdom', 'Germany', 'France', 'Spain', 'Portugal', 'Netherlands',
            'Belgium', 'Switzerland', 'Austria', 'Sweden', 'Norway', 'Denmark', 'Finland',
            'Poland', 'Czech Republic', 'Hungary', 'Romania', 'Bulgaria', 'Greece', 'Croatia',
            'Slovenia', 'Slovakia', 'Lithuania', 'Latvia', 'Estonia', 'Ireland', 'Iceland',
            'Luxembourg', 'Malta', 'Cyprus', 'Albania', 'North Macedonia', 'Serbia',
            'Montenegro', 'Bosnia and Herzegovina', 'Kosovo', 'Moldova', 'Ukraine',
            'Belarus', 'Russia', 'Georgia', 'Armenia', 'Azerbaijan', 'Turkey',

            // North America
            'Canada', 'Mexico', 'Guatemala', 'Belize', 'El Salvador',
            'Honduras', 'Nicaragua', 'Costa Rica', 'Panama', 'Cuba', 'Jamaica', 'Haiti',
            'Dominican Republic', 'Puerto Rico', 'Bahamas', 'Barbados', 'Trinidad and Tobago',

            // South America
            'Brazil', 'Argentina', 'Chile', 'Peru', 'Colombia', 'Venezuela', 'Ecuador',
            'Bolivia', 'Paraguay', 'Uruguay', 'Guyana', 'Suriname', 'French Guiana',

            // Asia
            'Japan', 'China', 'India', 'South Korea', 'North Korea', 'Vietnam', 'Thailand',
            'Cambodia', 'Laos', 'Myanmar', 'Malaysia', 'Singapore', 'Indonesia', 'Philippines',
            'Taiwan', 'Mongolia', 'Kazakhstan', 'Uzbekistan', 'Kyrgyzstan', 'Tajikistan',
            'Turkmenistan', 'Afghanistan', 'Pakistan', 'Bangladesh', 'Sri Lanka', 'Nepal',
            'Bhutan', 'Maldives', 'Iran', 'Iraq', 'Syria', 'Lebanon', 'Jordan', 'Israel',
            'Palestine', 'Saudi Arabia', 'Yemen', 'Oman', 'United Arab Emirates', 'Qatar',
            'Kuwait', 'Bahrain',

            // Oceania
            'Australia', 'New Zealand', 'Papua New Guinea', 'Fiji', 'Solomon Islands',
            'Vanuatu', 'New Caledonia', 'French Polynesia', 'Samoa', 'Tonga', 'Micronesia',

            // Antarctica
            'Antarctica',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($countries),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
