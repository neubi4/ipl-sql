<?php

namespace ipl\Tests\Sql;

use ipl\Sql\Expression;
use ipl\Sql\QueryBuilder;
use ipl\Sql\Select;
use ipl\Sql\Sql;
use ipl\Test\BaseTestCase;

class SelectTest extends BaseTestCase
{
    /**
     * The SELECT query to test
     *
     * @var Select
     */
    protected $query;

    /**
     * The SQL query builder
     *
     * @var QueryBuilder
     */
    protected $queryBuilder;

    public function setUp()
    {
        $this->query = new Select();
        $this->queryBuilder = new QueryBuilder();
    }

    public function testDistinct()
    {
        $this->query
            ->distinct()
            ->columns('1');

        $this->assertSame(true, $this->query->getDistinct());
        $this->assertCorrectStatementAndValues('SELECT DISTINCT 1', []);
    }

    public function testColumns()
    {
        $this->query->columns('1');

        $this->assertSame(['1'], $this->query->getColumns());
        $this->assertCorrectStatementAndValues('SELECT 1', []);
    }

    public function testColumnsWithAlias()
    {
        $this->query->columns('1 AS one');

        $this->assertSame(['1 AS one'], $this->query->getColumns());
        $this->assertCorrectStatementAndValues('SELECT 1 AS one', []);
    }

    public function testColumnsWithArray()
    {
        $this->query->columns(['1', '2']);

        $this->assertSame(['1', '2'], $this->query->getColumns());
        $this->assertCorrectStatementAndValues('SELECT 1, 2', []);
    }

    public function testColumnsWithArrayAndAlias()
    {
        $this->query->columns(['one' => '1', '2']);

        $this->assertSame(['one' => '1', '2'], $this->query->getColumns());
        $this->assertCorrectStatementAndValues('SELECT 1 AS one, 2', []);
    }

    public function testColumnsWithExpression()
    {
        $columns = ['three' => new Expression('? + ?', 1, 2)];
        $this->query->columns($columns);

        $this->assertSame($columns, $this->query->getColumns());
        $this->assertCorrectStatementAndValues('SELECT (? + ?) AS three', [1, 2]);
    }

    public function testColumnsWithSelect()
    {
        $columns = [
            'customers' => (new Select())
                ->columns('COUNT(*)')
                ->from('customers')
                ->where(['ctime > ?' => 1234567890])
        ];

        $this->query->columns($columns);

        $this->assertSame($columns, $this->query->getColumns());
        $this->assertCorrectStatementAndValues(
            'SELECT (SELECT COUNT(*) FROM customers WHERE ctime > ?) AS customers',
            [1234567890]
        );
    }

    public function testFrom()
    {
        $this->query->from('table');

        $this->assertSame(['table'], $this->query->getFrom());
        $this->assertCorrectStatementAndValues('FROM table', []);
    }

    public function testFromWithAlias()
    {
        $this->query->from('table t1');

        $this->assertSame(['table t1'], $this->query->getFrom());
        $this->assertCorrectStatementAndValues('FROM table t1', []);
    }

    public function testFromWithArray()
    {
        $this->query->from(['t1' => 'table']);

        $this->assertSame(['t1' => 'table'], $this->query->getFrom());
        $this->assertCorrectStatementAndValues('FROM table t1', []);
    }

    public function testInnerJoin()
    {
        $this->query->join('table2', 'table2.table1_id = table1.id');

        $this->assertSame([['INNER', 'table2', [Sql::ALL, 'table2.table1_id = table1.id']]], $this->query->getJoin());
        $this->assertCorrectStatementAndValues('INNER JOIN table2 ON table2.table1_id = table1.id', []);
    }

    public function testInnerJoinWithAlias()
    {
        $this->query->join('table2 t2', 't2.table1_id = t1.id');

        $this->assertSame([['INNER', 'table2 t2', [Sql::ALL, 't2.table1_id = t1.id']]], $this->query->getJoin());
        $this->assertCorrectStatementAndValues('INNER JOIN table2 t2 ON t2.table1_id = t1.id', []);
    }

    public function testInnerJoinWithArray()
    {
        $this->query->join(['t2' => 'table2'], 't2.table1_id = t1.id');

        $this->assertSame([['INNER', ['t2' => 'table2'], [Sql::ALL, 't2.table1_id = t1.id']]], $this->query->getJoin());
        $this->assertCorrectStatementAndValues('INNER JOIN table2 t2 ON t2.table1_id = t1.id', []);
    }

    public function testInnerJoinWithComplexCondition()
    {
        $this->query->join('table2', ['table2.table1_id = table1.id', 'table2.table3_id = 42']);

        $this->assertSame(
            [['INNER', 'table2', [Sql::ALL, 'table2.table1_id = table1.id', 'table2.table3_id = 42']]],
            $this->query->getJoin()
        );

        $this->assertCorrectStatementAndValues(
            'INNER JOIN table2 ON (table2.table1_id = table1.id) AND (table2.table3_id = 42)',
            []
        );
    }

    public function testInnerJoinWithOperatorAll()
    {
        $this->query->join('table2', ['table2.table1_id = table1.id', 'table2.table3_id = 42'], Sql::ALL);

        $this->assertSame(
            [['INNER', 'table2', [Sql::ALL, 'table2.table1_id = table1.id', 'table2.table3_id = 42']]],
            $this->query->getJoin()
        );

        $this->assertCorrectStatementAndValues(
            'INNER JOIN table2 ON (table2.table1_id = table1.id) AND (table2.table3_id = 42)',
            []
        );
    }

    public function testInnerJoinWithOperatorAny()
    {
        $this->query->join('table2', ['table2.table1_id = table1.id', 'table2.table3_id = 42'], Sql::ANY);

        $this->assertSame(
            [['INNER', 'table2', [Sql::ANY, 'table2.table1_id = table1.id', 'table2.table3_id = 42']]],
            $this->query->getJoin()
        );

        $this->assertCorrectStatementAndValues(
            'INNER JOIN table2 ON (table2.table1_id = table1.id) OR (table2.table3_id = 42)',
            []
        );
    }

    public function testInnerJoinWithParametrizedCondition()
    {
        $this->query->join('table2', ['table2.table1_id = table1.id', 'table2.table3_id = ?' => 42]);

        $this->assertSame(
            [['INNER', 'table2', [Sql::ALL, 'table2.table1_id = table1.id', 'table2.table3_id = ?' => 42]]],
            $this->query->getJoin()
        );

        $this->assertCorrectStatementAndValues(
            'INNER JOIN table2 ON (table2.table1_id = table1.id) AND (table2.table3_id = ?)',
            [42]
        );
    }

    public function testLeftJoin()
    {
        $this->query->joinLeft('table2', 'table2.table1_id = table1.id');

        $this->assertSame([['LEFT', 'table2', [Sql::ALL, 'table2.table1_id = table1.id']]], $this->query->getJoin());
        $this->assertCorrectStatementAndValues('LEFT JOIN table2 ON table2.table1_id = table1.id', []);
    }

    public function testLeftJoinWithAlias()
    {
        $this->query->joinLeft('table2 t2', 't2.table1_id = t1.id');

        $this->assertSame([['LEFT', 'table2 t2', [Sql::ALL, 't2.table1_id = t1.id']]], $this->query->getJoin());
        $this->assertCorrectStatementAndValues('LEFT JOIN table2 t2 ON t2.table1_id = t1.id', []);
    }

    public function testLeftJoinWithArray()
    {
        $this->query->joinLeft(['t2' => 'table2'], 't2.table1_id = t1.id');

        $this->assertSame([['LEFT', ['t2' => 'table2'], [Sql::ALL, 't2.table1_id = t1.id']]], $this->query->getJoin());
        $this->assertCorrectStatementAndValues('LEFT JOIN table2 t2 ON t2.table1_id = t1.id', []);
    }

    public function testLeftJoinWithComplexCondition()
    {
        $this->query->joinLeft('table2', ['table2.table1_id = table1.id', 'table2.table3_id = 42']);

        $this->assertSame(
            [['LEFT', 'table2', [Sql::ALL, 'table2.table1_id = table1.id', 'table2.table3_id = 42']]],
            $this->query->getJoin()
        );

        $this->assertCorrectStatementAndValues(
            'LEFT JOIN table2 ON (table2.table1_id = table1.id) AND (table2.table3_id = 42)',
            []
        );
    }

    public function testLeftJoinWithOperatorAll()
    {
        $this->query->joinLeft('table2', ['table2.table1_id = table1.id', 'table2.table3_id = 42'], Sql::ALL);

        $this->assertSame(
            [['LEFT', 'table2', [Sql::ALL, 'table2.table1_id = table1.id', 'table2.table3_id = 42']]],
            $this->query->getJoin()
        );

        $this->assertCorrectStatementAndValues(
            'LEFT JOIN table2 ON (table2.table1_id = table1.id) AND (table2.table3_id = 42)',
            []
        );
    }

    public function testLeftJoinWithOperatorAny()
    {
        $this->query->joinLeft('table2', ['table2.table1_id = table1.id', 'table2.table3_id = 42'], Sql::ANY);

        $this->assertSame(
            [['LEFT', 'table2', [Sql::ANY, 'table2.table1_id = table1.id', 'table2.table3_id = 42']]],
            $this->query->getJoin()
        );

        $this->assertCorrectStatementAndValues(
            'LEFT JOIN table2 ON (table2.table1_id = table1.id) OR (table2.table3_id = 42)',
            []
        );
    }

    public function testLeftJoinWithParametrizedCondition()
    {
        $this->query->joinLeft('table2', ['table2.table1_id = table1.id', 'table2.table3_id = ?' => 42]);

        $this->assertSame(
            [['LEFT', 'table2', [Sql::ALL, 'table2.table1_id = table1.id', 'table2.table3_id = ?' => 42]]],
            $this->query->getJoin()
        );

        $this->assertCorrectStatementAndValues(
            'LEFT JOIN table2 ON (table2.table1_id = table1.id) AND (table2.table3_id = ?)',
            [42]
        );
    }

    public function testRightJoin()
    {
        $this->query->joinRight('table2', 'table2.table1_id = table1.id');

        $this->assertSame([['RIGHT', 'table2', [Sql::ALL, 'table2.table1_id = table1.id']]], $this->query->getJoin());
        $this->assertCorrectStatementAndValues('RIGHT JOIN table2 ON table2.table1_id = table1.id', []);
    }

    public function testRightJoinWithAlias()
    {
        $this->query->joinRight('table2 t2', 't2.table1_id = t1.id');

        $this->assertSame([['RIGHT', 'table2 t2', [Sql::ALL, 't2.table1_id = t1.id']]], $this->query->getJoin());
        $this->assertCorrectStatementAndValues('RIGHT JOIN table2 t2 ON t2.table1_id = t1.id', []);
    }

    public function testRightJoinWithArray()
    {
        $this->query->joinRight(['t2' => 'table2'], 't2.table1_id = t1.id');

        $this->assertSame([['RIGHT', ['t2' => 'table2'], [Sql::ALL, 't2.table1_id = t1.id']]], $this->query->getJoin());
        $this->assertCorrectStatementAndValues('RIGHT JOIN table2 t2 ON t2.table1_id = t1.id', []);
    }

    public function testRightJoinWithComplexCondition()
    {
        $this->query->joinRight('table2', ['table2.table1_id = table1.id', 'table2.table3_id = 42']);

        $this->assertSame(
            [['RIGHT', 'table2', [Sql::ALL, 'table2.table1_id = table1.id', 'table2.table3_id = 42']]],
            $this->query->getJoin()
        );

        $this->assertCorrectStatementAndValues(
            'RIGHT JOIN table2 ON (table2.table1_id = table1.id) AND (table2.table3_id = 42)',
            []
        );
    }

    public function testRightJoinWithOperatorAll()
    {
        $this->query->joinRight('table2', ['table2.table1_id = table1.id', 'table2.table3_id = 42'], Sql::ALL);

        $this->assertSame(
            [['RIGHT', 'table2', [Sql::ALL, 'table2.table1_id = table1.id', 'table2.table3_id = 42']]],
            $this->query->getJoin()
        );

        $this->assertCorrectStatementAndValues(
            'RIGHT JOIN table2 ON (table2.table1_id = table1.id) AND (table2.table3_id = 42)',
            []
        );
    }

    public function testRightJoinWithOperatorAny()
    {
        $this->query->joinRight('table2', ['table2.table1_id = table1.id', 'table2.table3_id = 42'], Sql::ANY);

        $this->assertSame(
            [['RIGHT', 'table2', [Sql::ANY, 'table2.table1_id = table1.id', 'table2.table3_id = 42']]],
            $this->query->getJoin()
        );

        $this->assertCorrectStatementAndValues(
            'RIGHT JOIN table2 ON (table2.table1_id = table1.id) OR (table2.table3_id = 42)',
            []
        );
    }

    public function testRightJoinWithParametrizedCondition()
    {
        $this->query->joinRight('table2', ['table2.table1_id = table1.id', 'table2.table3_id = ?' => 42]);

        $this->assertSame(
            [['RIGHT', 'table2', [Sql::ALL, 'table2.table1_id = table1.id', 'table2.table3_id = ?' => 42]]],
            $this->query->getJoin()
        );

        $this->assertCorrectStatementAndValues(
            'RIGHT JOIN table2 ON (table2.table1_id = table1.id) AND (table2.table3_id = ?)',
            [42]
        );
    }

    public function testGroupBy()
    {
        $this->query->groupBy(['a', 'b']);

        $this->assertSame(['a', 'b'], $this->query->getGroupBy());
        $this->assertCorrectStatementAndValues('GROUP BY a, b', []);
    }

    public function testGroupByWithAlias()
    {
        $this->query->groupBy(['t.a', 't.b']);

        $this->assertSame(['t.a', 't.b'], $this->query->getGroupBy());
        $this->assertCorrectStatementAndValues('GROUP BY t.a, t.b', []);
    }

    public function testUnion()
    {
        $unionQuery = (new Select())
            ->columns('a')
            ->from('table2')
            ->where(['b < ?' => 2]);

        $this->query
            ->columns('a')
            ->from('table1')
            ->where(['b > ?' => 1])
            ->union($unionQuery);

        $this->assertSame([[$unionQuery, false]], $this->query->getUnion());
        $this->assertCorrectStatementAndValues(
            '(SELECT a FROM table1 WHERE b > ?) UNION (SELECT a FROM table2 WHERE b < ?)',
            [1, 2]
        );
    }

    public function testUnionAll()
    {
        $unionQuery = (new Select())
            ->columns('a')
            ->from('table2')
            ->where(['b < ?' => 2]);

        $this->query
            ->columns('a')
            ->from('table1')
            ->where(['b > ?' => 1])
            ->unionAll($unionQuery);

        $this->assertSame([[$unionQuery, true]], $this->query->getUnion());
        $this->assertCorrectStatementAndValues(
            '(SELECT a FROM table1 WHERE b > ?) UNION ALL (SELECT a FROM table2 WHERE b < ?)',
            [1, 2]
        );
    }

    public function testElementOrder()
    {
        $this->query
            ->distinct()
            ->columns(['c.id', 'c.name', 'orders' => 'COUNT(o.customer)'])
            ->from(['c' => 'customer'])
            ->joinLeft('order o', 'o.customer = c.id')
            ->where(['c.name LIKE ?' => '%Doe%'])
            ->orWhere(['c.name LIKE ?' => '%Deo%'])
            ->groupBy(['c.id'])
            ->having(['COUNT(o.customer) >= ?' => 42])
            ->orHaving(['COUNT(o.customer) <= ?' => 3])
            ->orderBy(['COUNT(o.customer)', 'c.name'])
            ->offset(75)
            ->limit(25)
            ->unionAll((new Select())->columns(['id' => -1, 'name' => "''", 'orders' => -1]));

        $this->assertCorrectStatementAndValues(
            "(SELECT DISTINCT c.id, c.name, COUNT(o.customer) AS orders"
                . " FROM customer c LEFT JOIN order o ON o.customer = c.id"
                . " WHERE (c.name LIKE ?) OR (c.name LIKE ?)"
                . " GROUP BY c.id HAVING (COUNT(o.customer) >= ?) OR (COUNT(o.customer) <= ?)"
                . " ORDER BY COUNT(o.customer), c.name LIMIT 25 OFFSET 75)"
                . " UNION ALL (SELECT -1 AS id, '' AS name, -1 AS orders)",
            ['%Doe%', '%Deo%', 42, 3]
        );
    }

    public function testRollupMysql()
    {
        $this->query
            ->columns([
                'division' => 'di.name',
                'department' => 'de.name',
                'employees' => 'COUNT(e.id)'
            ])
            ->from(['e' => 'employee'])
            ->joinRight('department de', 'de.id = e.department')
            ->joinRight('division di', 'di.id = de.division')
            ->groupBy(['di.id', 'de.id WITH ROLLUP']);

        $this->assertCorrectStatementAndValues(
            'SELECT di.name AS division, de.name AS department, COUNT(e.id) AS employees'
                . ' FROM employee e'
                . ' RIGHT JOIN department de ON de.id = e.department'
                . ' RIGHT JOIN division di ON di.id = de.division'
                . ' GROUP BY di.id, de.id WITH ROLLUP',
            []
        );
    }

    public function testRollupPostgresql()
    {
        $this->query
            ->columns([
                'division' => 'di.name',
                'department' => 'de.name',
                'employees' => 'COUNT(e.id)'
            ])
            ->from(['e' => 'employee'])
            ->joinRight('department de', 'de.id = e.department')
            ->joinRight('division di', 'di.id = de.division')
            ->groupBy(['ROLLUP (di.id, de.id)']);

        $this->assertCorrectStatementAndValues(
            'SELECT di.name AS division, de.name AS department, COUNT(e.id) AS employees'
                . ' FROM employee e'
                . ' RIGHT JOIN department de ON de.id = e.department'
                . ' RIGHT JOIN division di ON di.id = de.division'
                . ' GROUP BY ROLLUP (di.id, de.id)',
            []
        );
    }

    protected function assertCorrectStatementAndValues($statement, $values)
    {
        list($actualStatement, $actualValues) = $this->queryBuilder->assembleSelect($this->query);

        $this->assertSame($statement, $actualStatement);
        $this->assertSame($values, $actualValues);
    }
}
