<?php

namespace Tests\Feature;

use App\Services\UaeFinanceConversion;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UaeFinanceCommandMemoryTest extends TestCase
{
    public function test_plesk_option_raises_memory_before_preview_and_preserves_dry_run(): void
    {
        $original = ini_get('memory_limit');
        $path = sys_get_temp_dir() . '/uae-memory-' . uniqid() . '.json';
        try {
            ini_set('memory_limit', '128M');
            $service = \Mockery::mock(UaeFinanceConversion::class);
            $service->shouldReceive('report')->once()->with(null)->andReturnUsing(function () {
                $this->assertSame('512M', ini_get('memory_limit'));
                return ['changes' => [], 'conflicts' => [], 'signature' => 'test-only'];
            });
            $service->shouldNotReceive('apply');
            $this->app->instance(UaeFinanceConversion::class, $service);
            $this->assertSame(0, Artisan::call('finance:convert-uae-to-aed', ['--memory' => '512', '--output' => $path]));
            $output = Artisan::output();
            $this->assertStringContainsString('PHP memory limit for this process: 512M', $output);
            $this->assertStringContainsString('Preview only:', $output);
            $this->assertFileExists($path);
        } finally {
            ini_set('memory_limit', $original);
            if (is_file($path)) unlink($path);
        }
    }

    public function test_invalid_or_unbounded_limits_fail_before_reading_financial_data(): void
    {
        $service = \Mockery::mock(UaeFinanceConversion::class);
        $service->shouldNotReceive('report');
        $service->shouldNotReceive('apply');
        $this->app->instance(UaeFinanceConversion::class, $service);
        $original = ini_get('memory_limit');
        foreach (['-1', '512M', '0', '64', '4096', '512.5', ''] as $invalid) {
            $this->assertSame(1, Artisan::call('finance:convert-uae-to-aed', ['--memory' => $invalid]));
            $this->assertStringContainsString('--memory must be', Artisan::output());
            $this->assertSame($original, ini_get('memory_limit'));
        }
    }

    public function test_memory_option_does_not_lower_an_existing_higher_limit(): void
    {
        $original = ini_get('memory_limit');
        try {
            ini_set('memory_limit', '1G');
            $this->assertSame(1, Artisan::call('finance:convert-uae-to-aed', ['--memory' => '512', '--source' => '/missing-snapshot.sql']));
            $this->assertSame('1G', ini_get('memory_limit'));
            $this->assertStringContainsString('Source file is missing', Artisan::output());
        } finally {
            ini_set('memory_limit', $original);
        }
    }
}
