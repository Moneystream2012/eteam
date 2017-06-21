<?php

/* @var $cs CClientScript  */

class GooglePieWidget extends CWidget {

    public $title = 'Pie Widget';
    public $url;
    public $data = array('Something' => 33, 'Else' => 66);
    public $width = 400;
    public $height;

    public function run() {

        $cs = Yii::app()->clientScript;
        $cs->registerCoreScript('jquery')
                ->registerScriptFile('https://www.google.com/jsapi')
                ->registerScript('google_load_corechart', 'google.load("visualization", "1", {packages:["corechart"]});', CClientScript::POS_HEAD);

        if (empty($this->height)) {
            $this->height = $this->width * 0.62;
        }

        if (empty($this->url)) { // static data mode
            $google_psihodelic_data_format = array(array('Label', 'Value'));

            foreach ($this->data as $label => $value)
                $google_psihodelic_data_format[] = array($label, (float) $value);

            $cs->registerScript(__CLASS__ . $this->id, '
                    google.setOnLoadCallback(drawChart' . $this->id . ');
                    function drawChart' . $this->id . '(){
                        data = google.visualization.arrayToDataTable(' . json_encode($google_psihodelic_data_format) . ');
                        var options = {
                            title: "' . $this->title . '",
                            width: ' . $this->width . ', height: ' . $this->height . ',
                        };
                        var chart = new google.visualization.PieChart(document.getElementById("pie_' . $this->id . '"));
                        chart.draw(data, options);
                    }
                    ', CClientScript::POS_HEAD);
        } else { //Has url. Ajax mode
            $cs->registerScript(__CLASS__ . $this->id, '
                    google.setOnLoadCallback(drawChart' . $this->id . ');
                    function drawChart' . $this->id . '(){
                        jQuery.get("url",function(data){
                            data = google.visualization.arrayToDataTable(data);
                            var options = {
                                title: "' . $this->title . '",
                                width: ' . $this->width . ', height: ' . $this->height . ',
                            };
                            var chart = new google.visualization.PieChart(document.getElementById("gauge_' . $this->id . '"));
                            chart.draw(data, options);
                        }
                    },"json");
                    ', CClientScript::POS_HEAD);
        }


        echo CHtml::tag('div', array('id' => 'pie_' . $this->id, 'style' => 'width: ' . $this->width . 'px; height: ' . $this->height . 'px;'), __CLASS__ . ' placeholder');
    }

}