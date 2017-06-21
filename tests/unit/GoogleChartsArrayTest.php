<?php
/**
 * Class GoogleChartsArrayTest
 *
 * @author Tarasenko Andrey
 */
Yii::import('application.components.statistics.DataSourceConverter.*');
/**
 * Class for testing object GoogleChartsArray
 *
 * @author Tarasenko Andrey
 */
class GoogleChartsArrayTest extends DbTestCase
{
    /**
     * @var GoogleChartsFormat testing class instance
     */
    private $_obj;

    /**
     * Create testing object instance
     */
    public function setUp()
    {
        parent::setUp();
        $this->_obj = new GoogleChartsArray(array(), array());
    }

    /**
     * Unit-test провайдер данных для метода GoogleChartsArray::getItem()
     * 
     * @coversNothing
     * @return array
     */
    public function googleChartsArrayGetItemProvider()
    {
        return 
            array(
                array(array('k1' => '1', 'k2' => '2.01', 'k3' => '3'), 'k2', array(), '2.01'),
                array(array('k1' => '1', 'k2' => '2.01', 'k3' => '3'), 'k2', array('settype' => 'integer'), 2),
                array(array('k1' => '1', 'k2' => '2.01', 'k3' => '3'), 'newKey', array('definedValue' => function($row){return $row['k2'] * $row['k3'];}), 6.03),
            );
    }

    /**
     * Тестирует GoogleChartsArray::getItem()
     * 
     * @author Tarasenko Andrey
     * @dataProvider googleChartsArrayGetItemProvider
     * @param array $row
     * @param string $columnKey
     * @param array $features
     * @param scalar $expects
     * @covers GoogleChartsArray::getItem()
     */
    public function testGetItem(array $row, $columnKey, array $features, $expects)
    {
        $method = new ReflectionMethod('GoogleChartsArray', 'getItem');
        $method->setAccessible(true);
        $this->assertEquals($expects, $method->invokeArgs($this->_obj, array($row, $columnKey, $features)));
    }

    /**
     * Тестирует GoogleChartsArray::definedValueFeature()
     * 
     * @author Tarasenko Andrey
     * @covers GoogleChartsArray::definedValueFeature()
     */
    public function testDefinedValueFeature()
    {
        $method = new ReflectionMethod('GoogleChartsArray', 'definedValueFeature');
        $method->setAccessible(true);
        $this->assertEquals(false, $method->invokeArgs($this->_obj, array(array('k1' => 1, 'k2' => 2, 'k3' => 3), array())));
        $this->assertEquals(false, $method->invokeArgs($this->_obj, array(array('k1' => 1, 'k2' => 2, 'k3' => 3), array('definedValue' => ''))));
        $this->assertEquals(6, $method->invokeArgs($this->_obj, array(array('k1' => 1, 'k2' => 2, 'k3' => 3), array('definedValue' => function($row){return $row['k2'] * $row['k3'];}))));
    }

    /**
     * Тестирует GoogleChartsArray::setTypeFeature()
     * 
     * @author Tarasenko Andrey
     * @covers GoogleChartsArray::setTypeFeature()
     */
    public function testSetTypeFeature()
    {
        $method = new ReflectionMethod('GoogleChartsArray', 'setTypeFeature');
        $method->setAccessible(true);
        $this->assertEquals(1, $method->invokeArgs($this->_obj, array('1', array('settype' => 'integer'))));
        $this->assertEquals(1.00, $method->invokeArgs($this->_obj, array('1', array('settype' => 'float'))));
        $this->assertEquals('1', $method->invokeArgs($this->_obj, array('1', array())));
    }
}
