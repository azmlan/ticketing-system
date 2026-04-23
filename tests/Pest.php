<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class)->in('Feature');
uses(RefreshDatabase::class)->in('Feature/Schema', 'Feature/Auth', 'Feature/Profile', 'Feature/Authorization', 'Feature/Promotion', 'Feature/Layout', 'Feature/Phase2');

uses(Tests\TestCase::class, RefreshDatabase::class)->in('Unit/Models', 'Unit/Services');
