<?php

namespace Database\Seeders;

use App\Models\Lab;
use Illuminate\Database\Seeder;

class LabSeeder extends Seeder
{
    public function run(): void
    {
        $labs = [
            ['code' => 'ГМЗ-1', 'ru' => 'Гидрометаллургический завод №1', 'uz' => '№1 Gidrometallurgiya zavodi', 'en' => 'Hydrometallurgical Plant No.1', 'status' => 'soon', 'url' => null],
            ['code' => 'ГМЗ-2', 'ru' => 'Гидрометаллургический завод №2', 'uz' => '№2 Gidrometallurgiya zavodi', 'en' => 'Hydrometallurgical Plant No.2', 'status' => 'soon', 'url' => null],
            ['code' => 'ГМЗ-3', 'ru' => 'Гидрометаллургический завод №3', 'uz' => '№3 Gidrometallurgiya zavodi', 'en' => 'Hydrometallurgical Plant No.3', 'status' => 'live', 'url' => null],
            ['code' => 'ГМЗ-4', 'ru' => 'Гидрометаллургический завод №4', 'uz' => '№4 Gidrometallurgiya zavodi', 'en' => 'Hydrometallurgical Plant No.4', 'status' => 'soon', 'url' => null],
            ['code' => 'ГМЗ-5', 'ru' => 'Гидрометаллургический завод №5', 'uz' => '№5 Gidrometallurgiya zavodi', 'en' => 'Hydrometallurgical Plant No.5', 'status' => 'live', 'url' => 'http://192.168.26.8:3010'],
            ['code' => 'ГМЗ-6', 'ru' => 'Гидрометаллургический завод №6', 'uz' => '№6 Gidrometallurgiya zavodi', 'en' => 'Hydrometallurgical Plant No.6', 'status' => 'soon', 'url' => null],
            ['code' => 'ГМЗ-7', 'ru' => 'Гидрометаллургический завод №7', 'uz' => '№7 Gidrometallurgiya zavodi', 'en' => 'Hydrometallurgical Plant No.7', 'status' => 'soon', 'url' => null],
            ['code' => 'ЦНИЛ', 'ru' => 'Центральная научно-исследовательская лаборатория', 'uz' => 'Markaziy ilmiy-tadqiqot laboratoriyasi', 'en' => 'Central Research Laboratory', 'status' => 'live', 'url' => null],
        ];

        foreach ($labs as $i => $lab) {
            Lab::updateOrCreate(
                ['code' => $lab['code']],
                [
                    'name_ru' => $lab['ru'],
                    'name_uz' => $lab['uz'],
                    'name_en' => $lab['en'],
                    'status' => $lab['status'],
                    'url' => $lab['url'],
                    'sort_order' => $i + 1,
                ],
            );
        }
    }
}
