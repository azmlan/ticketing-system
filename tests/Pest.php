<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class)->in('Feature');
uses(RefreshDatabase::class)->in('Feature/Schema', 'Feature/Auth', 'Feature/Profile', 'Feature/Authorization', 'Feature/Promotion', 'Feature/Layout', 'Feature/Phase2', 'Feature/Phase3', 'Feature/Phase4', 'Feature/Phase5', 'Feature/Search');

uses(Tests\TestCase::class, RefreshDatabase::class)->in('Unit/Models', 'Unit/Services', 'Unit/Phase3', 'Unit/Phase4', 'Unit/Phase5', 'Unit/Search');
