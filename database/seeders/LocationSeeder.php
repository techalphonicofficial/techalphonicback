<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $india = Location::updateOrCreate(['slug' => 'india'], [
            'name' => 'India', 'type' => 'country', 'country' => 'India', 'sort_order' => 0,
            'latitude' => 20.5937, 'longitude' => 78.9629,
            'description' => 'We work with businesses across India — from startups to established brands.',
        ]);

        // [slug, name, state, lat, lng, local facts]
        $cities = [
            ['noida', 'Noida', 'Uttar Pradesh', 28.5355, 77.3910, [
                'Our office is in Sector 63, Noida — meet the team in person.',
                'Noida’s IT/ITES corridor (Sectors 62, 63, 125–135) is one of NCR’s most competitive B2B markets.',
                'Local search demand is strong for real estate, education, healthcare and IT services.',
            ]],
            ['delhi', 'Delhi', 'Delhi', 28.6139, 77.2090, [
                'Delhi’s retail, hospitality and professional-services markets are highly competitive online.',
                'Hyper-local targeting (e.g. South Delhi, Dwarka, Rohini) often converts better than city-wide campaigns.',
            ]],
            ['greater-noida', 'Greater Noida', 'Uttar Pradesh', 28.4744, 77.5040, [
                'A fast-growing hub for education, manufacturing and residential real estate.',
            ]],
            ['ghaziabad', 'Ghaziabad', 'Uttar Pradesh', 28.6692, 77.4538, [
                'Strong demand from local services, retail and manufacturing businesses in Indirapuram, Vaishali and Raj Nagar.',
            ]],
            ['gurgaon', 'Gurgaon', 'Haryana', 28.4595, 77.0266, [
                'Gurugram is home to many corporate HQs and startups — B2B and SaaS searches are highly competitive.',
            ]],
            ['faridabad', 'Faridabad', 'Haryana', 28.4089, 77.3178, [
                'An industrial and manufacturing centre where B2B lead generation and local SEO deliver strong ROI.',
            ]],
            ['mumbai', 'Mumbai', 'Maharashtra', 19.0760, 72.8777, []],
            ['bangalore', 'Bangalore', 'Karnataka', 12.9716, 77.5946, []],
            ['pune', 'Pune', 'Maharashtra', 18.5204, 73.8567, []],
            ['hyderabad', 'Hyderabad', 'Telangana', 17.3850, 78.4867, []],
            ['chennai', 'Chennai', 'Tamil Nadu', 13.0827, 80.2707, []],
            ['kolkata', 'Kolkata', 'West Bengal', 22.5726, 88.3639, []],
            ['ahmedabad', 'Ahmedabad', 'Gujarat', 23.0225, 72.5714, []],
            ['jaipur', 'Jaipur', 'Rajasthan', 26.9124, 75.7873, []],
            ['lucknow', 'Lucknow', 'Uttar Pradesh', 26.8467, 80.9462, []],
            ['chandigarh', 'Chandigarh', 'Chandigarh', 30.7333, 76.7794, []],
        ];

        foreach ($cities as $i => [$slug, $name, $state, $lat, $lng, $facts]) {
            Location::updateOrCreate(['slug' => $slug], [
                'parent_id' => $india->id, 'name' => $name, 'type' => 'city', 'state' => $state, 'country' => 'India',
                'latitude' => $lat, 'longitude' => $lng, 'local_facts' => $facts ?: null, 'sort_order' => $i + 1,
                'status' => 'published',
            ]);
        }
    }
}
