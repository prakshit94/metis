<?php

namespace Database\Seeders;

use App\Modules\Users\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $year = (int) date('Y');

        $holidays = [
            // National Holidays (Mandatory)
            ['name' => 'Republic Day', 'date' => "$year-01-26", 'type' => 'National'],
            ['name' => 'Independence Day', 'date' => "$year-08-15", 'type' => 'National'],
            ['name' => 'Mahatma Gandhi Jayanti', 'date' => "$year-10-02", 'type' => 'National'],

            // Major Festivals (Often treated as National or Company Holidays)
            ['name' => 'Makar Sankranti / Pongal', 'date' => "$year-01-14", 'type' => 'Company'],
            ['name' => 'Holi', 'date' => "$year-03-04", 'type' => 'Company'], // Note: Dates vary by lunar calendar, using approximation
            ['name' => 'Good Friday', 'date' => "$year-04-03", 'type' => 'Company'], // Varies
            ['name' => 'Eid-ul-Fitr', 'date' => "$year-03-20", 'type' => 'Company'], // Varies
            ['name' => 'Raksha Bandhan', 'date' => "$year-08-28", 'type' => 'Company'], // Varies
            ['name' => 'Diwali / Deepavali', 'date' => "$year-11-08", 'type' => 'Company'], // Varies
            ['name' => 'Christmas', 'date' => "$year-12-25", 'type' => 'Company'],

            // Optional / Restricted Holidays
            ['name' => 'Maha Shivaratri', 'date' => "$year-02-14", 'type' => 'Optional'], // Varies
            ['name' => 'Ram Navami', 'date' => "$year-03-28", 'type' => 'Optional'], // Varies
            ['name' => 'Mahavir Jayanti', 'date' => "$year-04-01", 'type' => 'Optional'], // Varies
            ['name' => 'Buddha Purnima', 'date' => "$year-05-01", 'type' => 'Optional'], // Varies
            ['name' => 'Eid-ul-Zuha (Bakrid)', 'date' => "$year-05-27", 'type' => 'Optional'], // Varies
            ['name' => 'Muharram', 'date' => "$year-06-26", 'type' => 'Optional'], // Varies
            ['name' => 'Janmashtami', 'date' => "$year-09-04", 'type' => 'Optional'], // Varies
            ['name' => 'Ganesh Chaturthi', 'date' => "$year-09-15", 'type' => 'Optional'], // Varies
            ['name' => 'Dussehra (Maha Navami)', 'date' => "$year-10-20", 'type' => 'Optional'], // Varies
            ['name' => 'Karva Chauth', 'date' => "$year-10-31", 'type' => 'Optional'], // Varies
            ['name' => 'Guru Nanak Jayanti', 'date' => "$year-11-24", 'type' => 'Optional'], // Varies
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['name' => $holiday['name'], 'date' => $holiday['date']],
                ['type' => $holiday['type']]
            );
        }
    }
}
