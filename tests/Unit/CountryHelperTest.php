<?php

namespace Tests\Unit;

use App\Helpers\Services\Localization\Helpers\Country;
use Tests\TestCase;

class CountryHelperTest extends TestCase
{
    public function testLoadDataCldrReturnsData(): void
    {
        $helper = new Country();
        $data = $helper->loadData('php', 'en', 'cldr');

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }
}
