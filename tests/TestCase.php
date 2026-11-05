<?php
namespace ItmoSoft\Returns\Tests;
use Orchestra\Testbench\TestCase as Orchestra;
use ItmoSoft\Returns\ReturnsServiceProvider;
abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array { return [ReturnsServiceProvider::class]; }
    protected function defineEnvironment($app): void { $app['config']->set('database.default', 'testing'); $app['config']->set('database.connections.testing', ['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true]); }
}
