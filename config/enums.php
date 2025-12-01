<?php

namespace App\config\Enums;

class CityEnum
{
    public const DAMASCUS   = 'Damascus';
    public const ALEPPO     = 'Aleppo';
    public const HOMS       = 'Homs';
    public const LATAKIA    = 'Latakia';
    public const HAMA       = 'Hama';
    public const IDLIB      = 'Idlib';
    public const TARTUS     = 'Tartus';
    public const RAQQA      = 'Raqqa';
    public const DEIR_EZZOR = 'Deir ez-Zor';
    public const HASAKAH    = 'Hasakah';
    public const SUWAYDA    = 'As-Suwayda';
    public const DARAA      = 'Daraa';
    public const QUNEITRA   = 'Quneitra';
    public const RIF_DIMASHQ = 'Rif Dimashq'; // ريف دمشق

    public static function values(): array
    {
        return [
            self::DAMASCUS,
            self::ALEPPO,
            self::HOMS,
            self::LATAKIA,
            self::HAMA,
            self::IDLIB,
            self::TARTUS,
            self::RAQQA,
            self::DEIR_EZZOR,
            self::HASAKAH,
            self::SUWAYDA,
            self::DARAA,
            self::QUNEITRA,
            self::RIF_DIMASHQ,
        ];
    }

    public static function places(): array
    {
        return [
            self::DAMASCUS => [
                'Kafr Sousa', 'Mezzeh', 'Midan', 'Rukn al-Din', 'Barzeh',
                'Qaboun', 'Jobar', 'Bab Touma', 'Bab Sharqi', 'Shaghour',
                'Sarouja', 'Mazraa', 'Malki', 'Abu Rummaneh', 'Tijara',
                'Dummar', 'Qudsaya', 'Yarmouk', 'Tadamon'
            ],
            self::ALEPPO => [
                'Al-Jdeideh', 'Salah al-Din', 'Bustan al-Qasr', 'Aziziya',
                'Seif al-Dawla', 'Al-Shaar', 'Hanano', 'Al-Midan',
                'Al-Sabil', 'Al-Hamdaniya', 'Al-Mashhad', 'Al-Khalidiya'
            ],
            self::HOMS => [
                'Al-Hamidiya', 'Inshaat', 'Khaldiya', 'Bayada',
                'Bab al-Dreib', 'Bab Hud', 'Al-Waer', 'Al-Qusour'
            ],
            self::LATAKIA => [
                'Al-Qalaa', 'Corniche', 'Al-Raml al-Janoubi',
                'Al-Raml al-Shamali', 'Al-Slaybiyeh', 'Al-Sheikh Daher',
                'Al-Amerkan', 'Al-Ziraa'
            ],
            self::HAMA => [
                'Al-Hader', 'Al-Tawun', 'Al-Kazaz', 'Al-Sabouniya',
                'Al-Mashaa', 'Al-Baroudiya', 'Al-Hamidiya'
            ],
            self::IDLIB => [
                'City Center', 'Al-Dabit', 'Al-Jalaa', 'Al-Qusour',
                'Al-Kournish', 'Al-Midan'
            ],
            self::TARTUS => [
                'Al-Qadmous', 'Safita', 'Banyas', 'Dreikish',
                'Sheikh Badr', 'Al-Hamidiya'
            ],
            self::RAQQA => [
                'Al-Mashlab', 'Al-Rashid', 'Al-Dariya', 'Al-Sinaa',
                'Al-Mansour', 'Al-Firdous'
            ],
            self::DEIR_EZZOR => [
                'Al-Joura', 'Al-Qusour', 'Al-Hamidiya', 'Al-Ardi',
                'Al-Rashidiya', 'Al-Huwaiqa'
            ],
            self::HASAKAH => [
                'Al-Nashwa', 'Al-Mufti', 'Al-Salhiya', 'Al-Ghweiran',
                'Al-Kallasa', 'Al-Masakin'
            ],
            self::SUWAYDA => [
                'City Center', 'Al-Qalaa', 'Al-Mashnaqa', 'Al-Mazraa',
                'Al-Shaab', 'Al-Kafr'
            ],
            self::DARAA => [
                'Al-Mahatta', 'Al-Balad', 'Tariq al-Sadd',
                'Al-Manshiya', 'Al-Kashef'
            ],
            self::QUNEITRA => [
                'City Center', 'Khan Arnabeh', 'Al-Baath City',
                'Al-Masakin', 'Al-Midan'
            ],
            self::RIF_DIMASHQ => [
                'Douma', 'Harasta', 'Zamalka', 'Arbin', 'Saqba',
                'Jaramana', 'Qudsaya', 'Darayya', 'Moadamiyah',
                'Al-Tall', 'Rankous', 'Yabroud', 'Al-Nabak'
            ],
        ];
    }

    
}