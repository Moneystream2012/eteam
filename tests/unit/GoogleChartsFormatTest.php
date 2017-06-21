<?php
/**
 * Class GoogleChartsFormatTest
 *
 * @author Tarasenko Andrey
 */
Yii::import('application.components.statistics.DataSourceConverter.*');
/**
 * Class for testing object GoogleChartsFormat
 *
 * @author Tarasenko Andrey
 */
class GoogleChartsFormatTest extends DbTestCase
{
    /**
     * @var GoogleChartsFormat testing class instanc
     */
    private $_obj;

    /**
     * Create testing object instance
     */
    public function setUp()
    {
        parent::setUp();
        $this->_obj = new GoogleChartsFormat();
    }

    /**
     * Unit-test provider "raw" data for forming data set of column in GoogleCharts format.
     * 
     * @coversNothing
     * @return array
     */
    public function googleChartsFormatColumnsProvider()
    {
        return 
            array(
                array(array('columnKey'), array('columnKey' => '{"label":"columnKey","id":"columnKey","type":"string"}')),
                array(array('columnKey' => array('label' => 'Column 1', 'id' => 'cNo1')), array('columnKey' => '{"label":"Column 1","id":"cNo1","type":"string"}')),
                array(array('columnKey' => array('role' => 'annotation', 'pattern' => '/pattern/')), array('columnKey' => '{"label":"columnKey","id":"columnKey","role":"annotation","type":"string","pattern":"\/pattern\/"}')),
            );
    }

    /**
     * Testing GoogleChartsFormat::testGetColumns()
     * 
     * @author Tarasenko Andrey
     * @dataProvider googleChartsFormatColumnsProvider
     * @param string $rawColumns Set of "raw" data in GoogleCharts fornat.
     * @param string $expects Expected result
     * @covers GoogleChartsFormat::getColumns()
     */
    public function testGetColumns($rawColumns, $expects)
    {
        $this->assertEquals($expects, $this->_obj->getColumns($rawColumns));
    }

    /**
     * Unit-test provider GoogleCharts formats of data.
     * 
     * @coversNothing
     * @return array
     */
    public function googleChartsFormatColumnTypeProvider()
    {
        return array(
            array('number', false, 'number'),
            array('date', false, 'date'),
            array('datetime', false, 'datetime'),
            array('timeofday', false, 'timeofday'),
            array('boolean', false, 'boolean'),
            array('boolean', true, 'string'),
            array('fake', false, 'number'),
        );
    }

    /**
     * Testing GoogleChartsFormat::getColumnType()
     * 
     * @author Tarasenko Andrey
     * @param string $type Data type of GoogleCharts column.
     * @param string $discreteColumn Does column data type is discrete (scalar).
     * @param string $expects Expected result
     * @dataProvider googleChartsFormatColumnTypeProvider
     * @covers GoogleChartsFormat::getColumnType()
     */
    public function testGetColumnType($type, $discreteColumn, $expects)
    {
        $method = new ReflectionMethod('GoogleChartsFormat', 'getColumnType');
        $method->setAccessible(true);
        $this->assertEquals($expects, $method->invokeArgs($this->_obj, array($type, $discreteColumn)));
    }

    /**
     * Unit-test provider GoogleCharts data rule formats.
     * 
     * @coversNothing
     * @return array
     */
    public function googleChartsFormatRuleProvider()
    {
        return array(
            array('annotation', true),
            array('annotationText', true),
            array('style', true),
            array('tooltip', true),
            array('domain', true),
            array('certainty', true),
            array('emphasis', true),
            array('scope', true),
            array('data', true),
            array('interval', true),
            array('fake', false),
        );
    }

    /**
     * Testing GoogleChartsFormat::isRole()
     * 
     * @author Tarasenko Andrey
     * @param string $role GoogleCharts формат вывода данных.
     * @param string $expects Expected result
     * @dataProvider googleChartsFormatRuleProvider
     * @covers GoogleChartsFormat::isRole()
     */
    public function testIsRole($role, $expects)
    {
        $method = new ReflectionMethod('GoogleChartsFormat', 'isRole');
        $method->setAccessible(true);
        $this->assertEquals($expects, $method->invokeArgs($this->_obj, array($role)));
    }

    /**
     * Testing GoogleChartsFormat::getColumnKey()
     * 
     * @author Tarasenko Andrey
     * @covers GoogleChartsFormat::getColumnKey()
     */
    public function testGetColumnKey()
    {
        $method = new ReflectionMethod('GoogleChartsFormat', 'getColumnKey');
        $method->setAccessible(true);
        $this->assertEquals('key', $method->invokeArgs($this->_obj, array(0, 'key')));
        $this->assertEquals('key', $method->invokeArgs($this->_obj, array('key', 'value')));
        $this->assertEquals('key', $method->invokeArgs($this->_obj, array('key', array('value'))));
    }
}
