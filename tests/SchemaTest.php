<?php

declare(strict_types=1);

namespace MongoDB\Laravel\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

use function count;

class SchemaTest extends TestCase
{
    public function tearDown(): void
    {
        Schema::drop('newcollection');
        Schema::drop('newcollection_two');
    }

    public function testCreate(): void
    {
        Schema::create('newcollection');
        $this->assertTrue(Schema::hasCollection('newcollection'));
        $this->assertTrue(Schema::hasTable('newcollection'));
    }

    public function testCreateWithCallback(): void
    {
        $instance = $this;

        Schema::create('newcollection', function ($collection) use ($instance) {
            $instance->assertInstanceOf(Blueprint::class, $collection);
        });

        $this->assertTrue(Schema::hasCollection('newcollection'));
    }

    public function testCreateWithOptions(): void
    {
        Schema::create('newcollection_two', null, ['capped' => true, 'size' => 1024]);
        $this->assertTrue(Schema::hasCollection('newcollection_two'));
        $this->assertTrue(Schema::hasTable('newcollection_two'));

        $collection = Schema::getCollection('newcollection_two');
        $this->assertTrue($collection['options']['capped']);
        $this->assertEquals(1024, $collection['options']['size']);
    }

    public function testDrop(): void
    {
        Schema::create('newcollection');
        Schema::drop('newcollection');
        $this->assertFalse(Schema::hasCollection('newcollection'));
    }

    public function testBluePrint(): void
    {
        $instance = $this;

        Schema::collection('newcollection', function ($collection) use ($instance) {
            $instance->assertInstanceOf(Blueprint::class, $collection);
        });

        Schema::table('newcollection', function ($collection) use ($instance) {
            $instance->assertInstanceOf(Blueprint::class, $collection);
        });
    }

    public function testIndex(): void
    {
        Schema::collection('newcollection', function ($collection) {
            $collection->index('mykey1');
        });

        $index = $this->getIndex('newcollection', 'mykey1');
        $this->assertEquals(1, $index['key']['mykey1']);

        Schema::collection('newcollection', function ($collection) {
            $collection->index(['mykey2']);
        });

        $index = $this->getIndex('newcollection', 'mykey2');
        $this->assertEquals(1, $index['key']['mykey2']);

        Schema::collection('newcollection', function ($collection) {
            $collection->string('mykey3')->index();
        });

        $index = $this->getIndex('newcollection', 'mykey3');
        $this->assertEquals(1, $index['key']['mykey3']);
    }

    public function testPrimary(): void
    {
        Schema::collection('newcollection', function ($collection) {
            $collection->string('mykey', 100)->primary();
        });

        $index = $this->getIndex('newcollection', 'mykey');
        $this->assertEquals(1, $index['unique']);
    }

    public function testUnique(): void
    {
        Schema::collection('newcollection', function ($collection) {
            $collection->unique('uniquekey');
        });

        $index = $this->getIndex('newcollection', 'uniquekey');
        $this->assertEquals(1, $index['unique']);
    }

    public function testDropIndex(): void
    {
        Schema::collection('newcollection', function ($collection) {
            $collection->unique('uniquekey');
            $collection->dropIndex('uniquekey_1');
        });

        $index = $this->getIndex('newcollection', 'uniquekey');
        $this->assertEquals(null, $index);

        Schema::collection('newcollection', function ($collection) {
            $collection->unique('uniquekey');
            $collection->dropIndex(['uniquekey']);
        });

        $index = $this->getIndex('newcollection', 'uniquekey');
        $this->assertEquals(null, $index);

        Schema::collection('newcollection', function ($collection) {
            $collection->index(['field_a', 'field_b']);
        });

        $index = $this->getIndex('newcollection', 'field_a_1_field_b_1');
        $this->assertNotNull($index);

        Schema::collection('newcollection', function ($collection) {
            $collection->dropIndex(['field_a', 'field_b']);
        });

        $index = $this->getIndex('newcollection', 'field_a_1_field_b_1');
        $this->assertFalse($index);

        Schema::collection('newcollection', function ($collection) {
            $collection->index(['field_a' => -1, 'field_b' => 1]);
        });

        $index = $this->getIndex('newcollection', 'field_a_-1_field_b_1');
        $this->assertNotNull($index);

        Schema::collection('newcollection', function ($collection) {
            $collection->dropIndex(['field_a' => -1, 'field_b' => 1]);
        });

        $index = $this->getIndex('newcollection', 'field_a_-1_field_b_1');
        $this->assertFalse($index);

        Schema::collection('newcollection', function ($collection) {
            $collection->index(['field_a', 'field_b'], 'custom_index_name');
        });

        $index = $this->getIndex('newcollection', 'custom_index_name');
        $this->assertNotNull($index);

        Schema::collection('newcollection', function ($collection) {
            $collection->dropIndex('custom_index_name');
        });

        $index = $this->getIndex('newcollection', 'custom_index_name');
        $this->assertFalse($index);
    }

    public function testDropIndexIfExists(): void
    {
        Schema::collection('newcollection', function (Blueprint $collection) {
            $collection->unique('uniquekey');
            $collection->dropIndexIfExists('uniquekey_1');
        });

        $index = $this->getIndex('newcollection', 'uniquekey');
        $this->assertEquals(null, $index);

        Schema::collection('newcollection', function (Blueprint $collection) {
            $collection->unique('uniquekey');
            $collection->dropIndexIfExists(['uniquekey']);
        });

        $index = $this->getIndex('newcollection', 'uniquekey');
        $this->assertEquals(null, $index);

        Schema::collection('newcollection', function (Blueprint $collection) {
            $collection->index(['field_a', 'field_b']);
        });

        $index = $this->getIndex('newcollection', 'field_a_1_field_b_1');
        $this->assertNotNull($index);

        Schema::collection('newcollection', function (Blueprint $collection) {
            $collection->dropIndexIfExists(['field_a', 'field_b']);
        });

        $index = $this->getIndex('newcollection', 'field_a_1_field_b_1');
        $this->assertFalse($index);

        Schema::collection('newcollection', function (Blueprint $collection) {
            $collection->index(['field_a', 'field_b'], 'custom_index_name');
        });

        $index = $this->getIndex('newcollection', 'custom_index_name');
        $this->assertNotNull($index);

        Schema::collection('newcollection', function (Blueprint $collection) {
            $collection->dropIndexIfExists('custom_index_name');
        });

        $index = $this->getIndex('newcollection', 'custom_index_name');
        $this->assertFalse($index);
    }

    public function testHasIndex(): void
    {
        $instance = $this;

        Schema::collection('newcollection', function (Blueprint $collection) use ($instance) {
            $collection->index('myhaskey1');
            $instance->assertTrue($collection->hasIndex('myhaskey1_1'));
            $instance->assertFalse($collection->hasIndex('myhaskey1'));
        });

        Schema::collection('newcollection', function (Blueprint $collection) use ($instance) {
            $collection->index('myhaskey2');
            $instance->assertTrue($collection->hasIndex(['myhaskey2']));
            $instance->assertFalse($collection->hasIndex(['myhaskey2_1']));
        });

        Schema::collection('newcollection', function (Blueprint $collection) use ($instance) {
            $collection->index(['field_a', 'field_b']);
            $instance->assertTrue($collection->hasIndex(['field_a_1_field_b']));
            $instance->assertFalse($collection->hasIndex(['field_a_1_field_b_1']));
        });
    }

    public function testBackground(): void
    {
        Schema::collection('newcollection', function ($collection) {
            $collection->background('backgroundkey');
        });

        $index = $this->getIndex('newcollection', 'backgroundkey');
        $this->assertEquals(1, $index['background']);
    }

    public function testSparse(): void
    {
        Schema::collection('newcollection', function ($collection) {
            $collection->sparse('sparsekey');
        });

        $index = $this->getIndex('newcollection', 'sparsekey');
        $this->assertEquals(1, $index['sparse']);
    }

    public function testExpire(): void
    {
        Schema::collection('newcollection', function ($collection) {
            $collection->expire('expirekey', 60);
        });

        $index = $this->getIndex('newcollection', 'expirekey');
        $this->assertEquals(60, $index['expireAfterSeconds']);
    }

    public function testSoftDeletes(): void
    {
        Schema::collection('newcollection', function ($collection) {
            $collection->softDeletes();
        });

        Schema::collection('newcollection', function ($collection) {
            $collection->string('email')->nullable()->index();
        });

        $index = $this->getIndex('newcollection', 'email');
        $this->assertEquals(1, $index['key']['email']);
    }

    public function testFluent(): void
    {
        Schema::collection('newcollection', function ($collection) {
            $collection->string('email')->index();
            $collection->string('token')->index();
            $collection->timestamp('created_at');
        });

        $index = $this->getIndex('newcollection', 'email');
        $this->assertEquals(1, $index['key']['email']);

        $index = $this->getIndex('newcollection', 'token');
        $this->assertEquals(1, $index['key']['token']);
    }

    public function testGeospatial(): void
    {
        Schema::collection('newcollection', function ($collection) {
            $collection->geospatial('point');
            $collection->geospatial('area', '2d');
            $collection->geospatial('continent', '2dsphere');
        });

        $index = $this->getIndex('newcollection', 'point');
        $this->assertEquals('2d', $index['key']['point']);

        $index = $this->getIndex('newcollection', 'area');
        $this->assertEquals('2d', $index['key']['area']);

        $index = $this->getIndex('newcollection', 'continent');
        $this->assertEquals('2dsphere', $index['key']['continent']);
    }

    public function testDummies(): void
    {
        Schema::collection('newcollection', function ($collection) {
            $collection->boolean('activated')->default(0);
            $collection->integer('user_id')->unsigned();
        });
        $this->expectNotToPerformAssertions();
    }

    public function testSparseUnique(): void
    {
        Schema::collection('newcollection', function ($collection) {
            $collection->sparse_and_unique('sparseuniquekey');
        });

        $index = $this->getIndex('newcollection', 'sparseuniquekey');
        $this->assertEquals(1, $index['sparse']);
        $this->assertEquals(1, $index['unique']);
    }

    public function testRenameColumn(): void
    {
        DB::connection()->collection('newcollection')->insert(['test' => 'value']);
        DB::connection()->collection('newcollection')->insert(['test' => 'value 2']);
        DB::connection()->collection('newcollection')->insert(['column' => 'column value']);

        $check = DB::connection()->collection('newcollection')->get();
        $this->assertCount(3, $check);

        $this->assertArrayHasKey('test', $check[0]);
        $this->assertArrayNotHasKey('newtest', $check[0]);

        $this->assertArrayHasKey('test', $check[1]);
        $this->assertArrayNotHasKey('newtest', $check[1]);

        $this->assertArrayHasKey('column', $check[2]);
        $this->assertArrayNotHasKey('test', $check[2]);
        $this->assertArrayNotHasKey('newtest', $check[2]);

        Schema::collection('newcollection', function (Blueprint $collection) {
            $collection->renameColumn('test', 'newtest');
        });

        $check2 = DB::connection()->collection('newcollection')->get();
        $this->assertCount(3, $check2);

        $this->assertArrayHasKey('newtest', $check2[0]);
        $this->assertArrayNotHasKey('test', $check2[0]);
        $this->assertSame($check[0]['test'], $check2[0]['newtest']);

        $this->assertArrayHasKey('newtest', $check2[1]);
        $this->assertArrayNotHasKey('test', $check2[1]);
        $this->assertSame($check[1]['test'], $check2[1]['newtest']);

        $this->assertArrayHasKey('column', $check2[2]);
        $this->assertArrayNotHasKey('test', $check2[2]);
        $this->assertArrayNotHasKey('newtest', $check2[2]);
        $this->assertSame($check[2]['column'], $check2[2]['column']);
    }

    public function testGetTables()
    {
        DB::connection('mongodb')->collection('newcollection')->insert(['test' => 'value']);
        DB::connection('mongodb')->collection('newcollection_two')->insert(['test' => 'value']);

        $tables = Schema::getTables();
        $this->assertIsArray($tables);
        $this->assertGreaterThanOrEqual(2, count($tables));
        $found = false;
        foreach ($tables as $table) {
            $this->assertArrayHasKey('name', $table);
            $this->assertArrayHasKey('size', $table);

            if ($table['name'] === 'newcollection') {
                $this->assertEquals(8192, $table['size']);
                $found = true;
            }
        }

        if (! $found) {
            $this->fail('Collection "newcollection" not found');
        }
    }

    public function testGetTableListing()
    {
        DB::connection('mongodb')->collection('newcollection')->insert(['test' => 'value']);
        DB::connection('mongodb')->collection('newcollection_two')->insert(['test' => 'value']);

        $tables = Schema::getTableListing();

        $this->assertIsArray($tables);
        $this->assertGreaterThanOrEqual(2, count($tables));
        $this->assertContains('newcollection', $tables);
        $this->assertContains('newcollection_two', $tables);
    }

    protected function getIndex(string $collection, string $name)
    {
        $collection = DB::getCollection($collection);

        foreach ($collection->listIndexes() as $index) {
            if (isset($index['key'][$name])) {
                return $index;
            }
        }

        return false;
    }
}
