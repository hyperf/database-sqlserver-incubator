<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace HyperfTest\Database\Sqlsrv;

use Hyperf\Database\Query\Builder;
use Hyperf\Database\Sqlsrv\Query\Grammars\SqlServerGrammar;
use Hyperf\DbConnection\Connection;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @internal
 * @coversNothing
 */
class DatabaseSqlServerQueryGrammarTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testToRawSql()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('escape')->with('foo', false)->andReturn("'foo'");
        $grammar = new SqlServerGrammar();

        $bindings = array_map(fn ($value) => $connection->escape($value, false), ['foo']);

        $query = $grammar->substituteBindingsIntoRawSql(
            "select * from [users] where 'Hello''World?' IS NOT NULL AND [email] = ?",
            $bindings,
        );

        $this->assertSame("select * from [users] where 'Hello''World?' IS NOT NULL AND [email] = 'foo'", $query);
    }

    public function testCompileTruncate()
    {
        $reflection = new ReflectionClass(SqlServerGrammar::class);
        $instance = m::mock(SqlServerGrammar::class);
        $method = $reflection->getMethod('compileTruncate');
        $instance->allows('wrapTable')->andReturnUsing(fn ($value) => $value);
        $query = m::mock(Builder::class);
        $query->from = 'users';
        $result = $method->invoke($instance, $query);
        $this->assertIsArray($result);
        $this->assertSame('truncate table users', array_keys($result)[0]);
        $this->assertSame([], array_values($result)[0]);
    }
}
