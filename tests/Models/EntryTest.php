<?php

namespace Adldap\Tests\Models;

use Adldap\Models\Entry;
use Adldap\Tests\UnitTestCase;

class EntryTest extends UnitTestCase
{
    protected function newConnectionMock()
    {
        return $this->mock('Adldap\Connections\Ldap');
    }

    public function testConstruct()
    {
        $attributes = [
            'cn' => 'Common Name',
            'samaccountname' => 'Account Name',
        ];

        $entry = new Entry($attributes, $this->newConnectionMock());

        $this->assertEquals($attributes, $entry->getAttributes());
    }

    public function testSetRawAttributes()
    {
        $attributes = [
            'cn' => ['Common Name'],
            'samaccountname' => ['Account Name'],
        ];

        $entry = new Entry([], $this->newConnectionMock());

        $entry->setRawAttributes($attributes);

        $this->assertTrue($entry->exists);
        $this->assertEquals($attributes, $entry->getAttributes());
    }

    public function testModifications()
    {
        $attributes = [
            'cn' => ['Common Name'],
            'samaccountname' => ['Account Name'],
        ];

        $entry = new Entry([], $this->newConnectionMock());

        $entry->setRawAttributes($attributes);

        $entry->cn = null;
        $entry->samaccountname = 'Changed';
        $entry->test = 'New Attribute';

        $modifications = $entry->getModifications();

        // Removed 'cn' attribute
        $this->assertEquals('cn', $modifications[0]['attrib']);
        $this->assertEquals([null], $modifications[0]['values']);
        $this->assertEquals(2, $modifications[0]['modtype']);

        // Modified 'samaccountname' attribute
        $this->assertEquals('samaccountname', $modifications[1]['attrib']);
        $this->assertEquals(['Changed'], $modifications[1]['values']);
        $this->assertEquals(3, $modifications[1]['modtype']);

        // New 'test' attribute
        $this->assertEquals('test', $modifications[2]['attrib']);
        $this->assertEquals(['New Attribute'], $modifications[2]['values']);
        $this->assertEquals(1, $modifications[2]['modtype']);
    }

    public function testCreate()
    {
        $connection = $this->newConnectionMock();

        $connection->shouldReceive('add')->once()->withArgs([null, ['testing']])->andReturn(true);

        $entry = new Entry(['testing'], $connection);

        $this->assertTrue($entry->save());
    }

    public function testUpdate()
    {
        $connection = $this->newConnectionMock();

        $dn = 'cn=Testing,ou=Accounting,dc=corp,dc=org';

        $connection->shouldReceive('modifyBatch')->once()->withArgs([$dn, []])->andReturn(true);

        $entry = new Entry([], $connection);

        $entry->setRawAttributes(['dn' => $dn]);

        $this->assertTrue($entry->update());
    }
}