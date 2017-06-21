<?php
/**
 * Class DataSourceColumnPropertiesTest
 *
 * @author Tarasenko Andrey
 */
Yii::import('application.components.statistics.DataSourceColumn.*');
/**
 * Класс для тестирования объекта DataSourceColumnProperties описания колонок 
 * данных типа DataSource
 *
 * @author Tarasenko Andrey
 */
class DataSourceColumnPropertiesTest extends DbTestCase
{
    /**
     * @var string tmp-переменная языка
     */
    private $tmpLanguage;

    /**
     * Unit-test провайдер.
     * Возвращает массив тестируемых данных.
     * Каждый элемент массива состоит из двух частей:
     *  - тестируемый объект;
     *  - массив проверочных значений.
     * 
     * Каждому тесту соответствует значение определенного ключа.
     * К примеру, в тесте метода getKey сверочное значение находится
     * в ключе 'key'.
     * 
     * @coversNothing
     * @return array
     */
    public function additionProvider()
    {
        return array(
            array(
                (
                    new DataSourceColumnProperties(
                        'testCpm', 
                        array(
                            'name' => 'Тест ЦПМ',
                            'definedValue' => 100.562,
                            'numberFormat' => '0.0',
                            'convertCurrency' => true,
                            'encode' => true,
                            'type' => 'integer',
                            'googleChart' => array(
                                'type' => 'number'
                            ),
                        )
                    )
                ), array(
                    'key' => 'testCpm',
                    'name' => 'Тест ЦПМ',
                    'value' => 100,
                    'formatedValue' => array(
                        'ru' => '100,0', 
                        'en' => '100.0', 
                    ),
                    'currencyConvertedValue' => 5000,
                    'googleChart_type' => 'number',
                )
            ),
            array(
                (
                    new DataSourceColumnProperties(
                        'testCpm2', 
                        array(
                            'name' => array('Тест ', 'ЦПМ2'),
                            'definedValue' => '',
                            'numberFormat' => '0.00',
                            'convertCurrency' => true,
                            'encode' => true,
                            'type' => 'float',
                            'googleChart' => array(
                                'type' => 'string'
                            ),
                        )
                    )
                ), array(
                    'key' => 'testCpm2',
                    'name' => 'Тест ЦПМ2',
                    'value' => 1000.5326987,
                    'formatedValue' => array(
                        'ru' => '1000,53', 
                        'en' => '1000.53', 
                    ),
                    'currencyConvertedValue' => 5000,
                    'googleChart_type' => 'string',
                )
            ),
            array(
                (
                    new DataSourceColumnProperties(
                        'testCpm3', 
                        array(
                            'name' => array('Тест ', 'ЦПМ ', '3'),
                            'definedValue' => 'N/A',
                            'googleChart' => array(
                                'type' => 'string'
                            ),
                        )
                    )
                ), array(
                    'key' => 'testCpm3',
                    'name' => 'Тест ЦПМ 3',
                    'value' => 'N/A',
                    'formatedValue' => array(
                        'ru' => 'N/A', 
                        'en' => 'N/A', 
                    ),
                    'currencyConvertedValue' => 'N/A',
                    'googleChart_type' => 'string',
                )
            ),
            array(
                (
                    new DataSourceColumnProperties(
                        'testCpm4', 
                        array(
                            'name' => array('Тест ', 'ЦПМ ', '4'),
                            'definedValue' => function($row){return $row['testCpm'] * $row['testCpm2'];},
                            'type' => 'float',
                            'numberFormat' => '0.00',
                            'googleChart' => array(
                                'type' => 'number'
                            ),
                        )
                    )
                ), array(
                    'key' => 'testCpm4',
                    'name' => 'Тест ЦПМ 4',
                    'value' => 500266.34935,
                    'formatedValue' => array(
                        'ru' => '500266,35', 
                        'en' => '500266.35', 
                    ),
                    'currencyConvertedValue' => 500266.34935,
                    'googleChart_type' => 'number',
                )
            ),
            array(
                (
                    new DataSourceColumnProperties(
                        'testCpm5', 
                        array(
                            'name' => array('Тест ', 'ЦПМ ', '5'),
                            'googleChart' => array(
                                'type' => 'string'
                            ),
                        )
                    )
                ), array(
                    'key' => 'testCpm5',
                    'name' => 'Тест ЦПМ 5',
                    'value' => '',
                    'formatedValue' => array(
                        'ru' => '&nbsp;', 
                        'en' => '&nbsp;', 
                    ),
                    'currencyConvertedValue' => '',
                    'googleChart_type' => 'string',
                )
            ),
        );
    }

    /**
     * Test DataSourceColumnProperties::getKey()
     * 
     * @author Tarasenko Andrey
     * @param IDataSourceColumn $column
     * @param array $expectedResults
     * @dataProvider additionProvider
     * @covers DataSourceColumnProperties::getKey()
     */
    public function testGetKey(IDataSourceColumn $column, array $expectedResults)
    {
        $this->assertEquals($column->getKey(), $expectedResults['key']);
    }

    /**
     * Тестирует DataSourceColumnProperties::getProperty()
     * 
     * @author Tarasenko Andrey
     * @param IDataSourceColumn $column
     * @param array $expectedResults
     * @dataProvider additionProvider
     * @covers DataSourceColumnProperties::getProperty()
     */
    public function testGetProperty(IDataSourceColumn $column, array $expectedResults)
    {
        $googleChart = $column->getProperty('googleChart');
        $this->assertEquals($googleChart['type'], $expectedResults['googleChart_type']);
    }

    /**
     * Тестирует DataSourceColumnProperties::getName()
     * 
     * @author Tarasenko Andrey
     * @param IDataSourceColumn $column
     * @param array $expectedResults
     * @dataProvider additionProvider
     * @covers DataSourceColumnProperties::getName()
     */
    public function testGetName(IDataSourceColumn $column, array $expectedResults)
    {
        $this->assertEquals($column->getName(), $expectedResults['name']);
    }

    /**
     * Тестирует DataSourceColumnProperties::getValue()
     * 
     * @author Tarasenko Andrey
     * @param IDataSourceColumn $column
     * @param array $expectedResults
     * @dataProvider additionProvider
     * @covers DataSourceColumnProperties::getValue()
     */
    public function testGetValue(IDataSourceColumn $column, array $expectedResults)
    {
        $row = array('testCpm' => 500, 'testCpm2' => 1000.5326987, 'testCpm3' => '', 'testCpm4' => 0, 'testCpm5' => '');
        $this->assertEquals($column->getValue($row), $expectedResults['value']);
    }

    /**
     * Тестирует DataSourceColumnProperties::getFormatedValue()
     * 
     * @author Tarasenko Andrey
     * @param IDataSourceColumn $column
     * @param array $expectedResults
     * @dataProvider additionProvider
     * @covers DataSourceColumnProperties::getFormatedValue()
     */
    public function testGetFormatedValue(IDataSourceColumn $column, array $expectedResults)
    {
        Yii::app()->setLanguage('en');
        $this->assertEquals($column->getFormatedValue($expectedResults['value']), $expectedResults['formatedValue'][Yii::app()->getLanguage()]);
        Yii::app()->setLanguage('ru');
        $this->assertEquals($column->getFormatedValue($expectedResults['value']), $expectedResults['formatedValue'][Yii::app()->getLanguage()]);
    }

    /**
     * Тестирует DataSourceColumnProperties::getCurrencyConvertedValue()
     * 
     * @author Tarasenko Andrey
     * @param IDataSourceColumn $column
     * @param array $expectedResults
     * @dataProvider additionProvider
     * @covers DataSourceColumnProperties::getCurrencyConvertedValue()
     */
    public function testGetCurrencyConvertedValue(IDataSourceColumn $column, array $expectedResults)
    {
        $currencyHelper = $this->getMock('CurrencyHelper', array('convert'));
        $currencyHelper->expects($this->any())->method('convert')->willReturn(5000);
        Yii::app()->setComponent('currency', $currencyHelper);
        
        $this->assertEquals($column->getCurrencyConvertedValue($expectedResults['value'], '2015-02-03'), $expectedResults['currencyConvertedValue']);
    }

    /**
     * Создание тестового окружения.
     */
    public function setUp()
    {
        $this->tmpLanguage = Yii::app()->getLanguage();
    }

    /**
     * Окончание работы тест-метода
     */
    public function tearDown()
    {
        Yii::app()->setLanguage($this->tmpLanguage);
        Yii::app()->setComponent('currency', new CurrencyHelper());
    }
}
