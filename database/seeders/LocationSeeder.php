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
            'status' => 'published',
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
                'Hyper-local targeting (e.g. South Delhi, Dwarka, Rohini, Okhla) often converts better than city-wide campaigns.',
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
            ['mumbai', 'Mumbai', 'Maharashtra', 19.0760, 72.8777, [
                'India’s financial and commercial capital with high search demand across fintech, retail, entertainment, and D2C.',
            ]],
            ['bangalore', 'Bangalore', 'Karnataka', 12.9716, 77.5946, [
                'India’s Silicon Valley — a premier hub for SaaS startups, IT enterprises, EdTech, and B2B technology brands.',
            ]],
            ['pune', 'Pune', 'Maharashtra', 18.5204, 73.8567, [
                'A major IT, automotive, manufacturing, and education hub in Western India.',
            ]],
            ['hyderabad', 'Hyderabad', 'Telangana', 17.3850, 78.4867, [
                'HITEC City and Financial District drive strong B2B, pharma, healthcare, and enterprise IT search demand.',
            ]],
            ['chennai', 'Chennai', 'Tamil Nadu', 13.0827, 80.2707, [
                'A leading hub for SaaS, automotive, healthcare, and industrial manufacturing in South India.',
            ]],
            ['kolkata', 'Kolkata', 'West Bengal', 22.5726, 88.3639, [
                'Eastern India’s primary commercial, retail, healthcare, and hospitality gateway.',
            ]],
            ['ahmedabad', 'Ahmedabad', 'Gujarat', 23.0225, 72.5714, [
                'A fast-scaling commercial hub for textiles, pharma, e-commerce, and industrial enterprises.',
            ]],
            ['jaipur', 'Jaipur', 'Rajasthan', 26.9124, 75.7873, [
                'A thriving market for hospitality, tourism, handicrafts/D2C e-commerce, jewelry, and real estate.',
            ]],
            ['lucknow', 'Lucknow', 'Uttar Pradesh', 26.8467, 80.9462, [
                'A rapidly expanding North Indian hub for retail, real estate, healthcare, education, and local services.',
            ]],
            ['chandigarh', 'Chandigarh', 'Chandigarh', 30.7333, 76.7794, [
                'The commercial and IT gateway for Punjab, Haryana, and Himachal Pradesh.',
            ]],
        ];

        foreach ($cities as $i => [$slug, $name, $state, $lat, $lng, $facts]) {
            Location::updateOrCreate(['slug' => $slug], [
                'parent_id' => $india->id, 'name' => $name, 'type' => 'city', 'state' => $state, 'country' => 'India',
                'latitude' => $lat, 'longitude' => $lng, 'local_facts' => $facts ?: null, 'sort_order' => $i + 1,
                'status' => 'published',
            ]);
        }

        // International hubs matching LocationDirectory.tsx & old website location pages
        $international = [
            ['los-angeles', 'Los Angeles', 'city', 'California', 'United States', 34.0522, -118.2437, [
                'West Coast hub for entertainment, e-commerce, D2C brands, healthcare, and technology startups.',
            ]],
            ['san-diego', 'San Diego', 'city', 'California', 'United States', 32.7157, -117.1611, [
                'Southern California innovation hub for biotech, SaaS, hospitality, and professional services.',
            ]],
            ['chicago', 'Chicago', 'city', 'Illinois', 'United States', 41.8781, -87.6298, [
                'Midwest commercial and logistics capital with high B2B, finance, retail, and healthcare search volume.',
            ]],
            ['dallas', 'Dallas', 'city', 'Texas', 'United States', 32.7767, -96.7970, [
                'One of North America’s fastest-growing corporate, technology, real estate, and enterprise hubs.',
            ]],
            ['australia', 'Australia', 'country', 'Australia', 'Australia', -25.2744, 133.7751, [
                'High-growth Asia-Pacific digital market spanning Sydney, Melbourne, Brisbane, and Perth.',
            ]],
        ];

        foreach ($international as $j => [$slug, $name, $type, $state, $country, $lat, $lng, $facts]) {
            Location::updateOrCreate(['slug' => $slug], [
                'parent_id' => null, 'name' => $name, 'type' => $type, 'state' => $state, 'country' => $country,
                'latitude' => $lat, 'longitude' => $lng, 'local_facts' => $facts, 'sort_order' => 20 + $j,
                'status' => 'published',
            ]);
        }
    }
}
