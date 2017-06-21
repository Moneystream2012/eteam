<?php

/* @var $cs CClientScript  */

class GoogleLineWidget extends CWidget {

    /**
     * Ссылка на clientScript
     * 
     * @access private
     * @var clientScript 
     */
    private $_clientScript;

    /**
     * Название графика
     * 
     * @var string
     */
    public $title = 'Line Widget';

    /**
     * Url-путь к данным. Используется если данные должны быть получены
     * по средством AJAX-метода
     * 
     * @var string
     */
    public $url;

    /**
     * Объект преобразования данных типа DataSource.
     * Используется если данные переданы объектом DataSource
     * 
     * @var DataSourceConverter 
     */
    public $dataSource = null;

    /**
     * Массив. Используется если данные переданы классическим образом.
     * 
     * @var array
     */
    public $data = array(
        'AAA' => array('oil' => 300, 'gas' => 200),
        'BBB' => array('oil' => 320, 'gas' => 210),
        'CCC' => array('oil' => 260, 'gas' => 140),
    );

    /**
     * Ширина рабочей области виджета.
     * 
     * @var integer
     */
    public $width = 400;

    /**
     * Высота рабочей области виджета.
     * 
     * @var integer
     */
    public $height = 300;

    /**
     * Настройки оси-Y
     * 
     * @var array
     */
    public $vAxis = array();

    /**
     * Настройки оси-X
     * 
     * @var array
     */
    public $hAxis = array();

    /**
     * Инициализация
     * 
     * @return void
     */
    public function init()
    {
        $this->_clientScript = Yii::app()->clientScript;
    }

    /**
     * Выводит заголовок
     * 
     * @return void
     */
    protected function renderTitle()
    {
        echo CHtml::tag('h3', array(), $this->title);
    }

    /**
     * Выводит placeholder
     * 
     * @return void
     */
    protected function renderPlaceholder()
    {
        echo CHtml::tag('div', array('id' => 'map_' . $this->id), __CLASS__ . ' placeholder');
    }

    /**
     * Запуск виджета
     */
    public function run() {
        $this->renderTitle();
        if ($this->data || $this->url || $this->dataSource) {
            $this->loadGoogleJs();
            $this->_clientScript
                ->registerScript(
                    __CLASS__ . $this->id, 
                    $this->getOnLoadCallbackJsString(), 
                    CClientScript::POS_END
                );
            $this->renderPlaceholder();
        } else {
            echo CHtml::tag('i', array(), 'No data');
        }
    }

    /**
     * Регистрирует Callback графика.
     * 
     * @access private
     * @return string
     */
    private function getOnLoadCallbackJsString()
    {
        return
            implode("\n", array(
                'google.setOnLoadCallback(drawChart' . $this->id . ');',
                'function drawChart' . $this->id . '() {',
                    $this->getDrawChart(),
                '}'
            ));
    }

    /**
     * Определяет тип входящих данных.
     * 
     * @access private
     * @return string
     */
    private function getDrawChart()
    {
        $jsString = '';
        if ($this->dataSource) {
            $jsString = $this->getDataSourceDrawChart();
        } elseif ($this->data) {
            $jsString = $this->getDataDrawChart();
        } elseif ($this->url) {
            $jsString = $this->getAjaxDrawChart();
        }
        return $jsString;
    }

    /**
     * Возвращает JS-строку, команд:
     * - создание таблицы данных;
     * - указания колонок;
     * - установка данных
     * 
     * Источник данных - объект DataSource
     * 
     * @access private
     * @return string
     */
    private function getDataSourceDrawChart()
    {
        return
            implode("\n", array(
                'var data = new google.visualization.DataTable();',
                $this->getAddColumnJsString(),
                'data.addRows(' . CJSON::encode($this->dataSource->getRows()) . ');',
                $this->getChartDrawJsString(),
            ));
    }

    /**
     * Возвращает JS-строку, команд:
     * - создание таблицы данных;
     * - указания колонок;
     * - установка данных
     * 
     * Источник данных - массив
     * 
     * @access private
     * @return string
     */
    private function getDataDrawChart()
    {
        $google_psihodelic_data_format = array();
        $i = 0;
        foreach ($this->data as $label => $values) {
            if ($i++ == 0) { // firest iteration
                $google_psihodelic_data_format[] = array_merge(array($this->vAxis), array_keys($values));
            }
            $google_psihodelic_data_format[] = array_merge(array($label), array_values($values));
        }
        
        return
            implode("\n", array(
                'data = google.visualization.arrayToDataTable(' . json_encode($google_psihodelic_data_format) . ');',
                $this->getChartDrawJsString()
            ));
    }

    /**
     * Возвращает JS-строку, команд:
     * - создание таблицы данных;
     * - указания колонок;
     * - установка данных
     * 
     * Источник данных - результат AJAX-запроса
     * 
     * @access private
     * @return string
     */
    private function getAjaxDrawChart()
    {
        return
            implode("\n", array(
                'jQuery.get("' . $this->url . '", function(data){',
                    'data = google.visualization.arrayToDataTable(data);',
                    $this->getChartDrawJsString(),
                '}, "json");'
            ));
    }

    /**
     * Возвращает JS-строку, команду создания объекта графика и его прорисовки.
     * 
     * @access private
     * @return string
     */
    private function getChartDrawJsString()
    {
        return
            implode("\n", array(
                $this->getFormatterColumns(),
                'var options = ' . CJSON::encode($this->getOptions()) . ';',
                'var chart = new google.visualization.LineChart(document.getElementById("map_' . $this->id . '"));',
                'chart.draw(data, options);'
            ));
    }

    /**
     * Возвращает JS-строку, команду форматирования чисел колонки.
     * 
     * @access private
     * @return string
     */
    private function getFormatterColumns()
    {
        $formattersJsString = array();
        $formatterTemplate = 
            'var formatter = new google.visualization.NumberFormat({formatType: "%s"});' .
            'formatter.format(data, %d);';
        
        $columnIndex = 0;
        foreach ($this->dataSource->getDataSourceColumns() as $key => $cParams) {
            if ($numberFormat = $this->dataSource->getColumnParam($cParams, 'numberFormat')) {
                $formattersJsString[] = sprintf($formatterTemplate, $numberFormat, $columnIndex);
            }
            $columnIndex++;
        }
        return implode("\n", $formattersJsString);
    }

    /**
     * Метод формирует JS-строку добавления колонок для GoogleCharts
     * @return string
     */
    private function getAddColumnJsString()
    {
        
        $arr = array(); 
        foreach($this->dataSource->getColumns() as $c){
            $arr[]= 'data.addColumn(' . $c . ');'; 
        }
        return implode("\n", $arr);
    }

    /**
     * Формирует строку настройки X-оси
     * 
     * @return string
     */
    private function getHAxis()
    {
        return array_merge(array('title' => ""), $this->hAxis);
    }

    /**
     * Формирует строку настройки Y-оси
     * 
     * @return string
     */
    private function getVAxis()
    {
        return array_merge(array('title' => "", 'format' => '0.00'), $this->vAxis);
    }

    /**
     * Настройки графика
     * 
     * @access private
     * @return array
     */
    private function getOptions()
    {
        return 
            array(
                'width' => $this->width,
                'height' => $this->height,
                'hAxis' => $this->getHAxis(),
                'vAxis' => $this->getVAxis(),
            );
    }

    /**
     * Подгружает необходимые библиотеки.
     * 
     * @access private
     * @return void
     */
    private function loadGoogleJs()
    {
        $this->_clientScript->registerCoreScript('jquery')
            ->registerScriptFile('https://www.google.com/jsapi')
            ->registerScript('google_load_corechart', 'google.load("visualization", "1", {packages:["corechart", "line"], language: "' . Yii::app()->language . '"});', CClientScript::POS_HEAD);
    }
}
