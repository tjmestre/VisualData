<?php

require_once("DataVisualizer.php");

/**
 * Description of DataVisualizerDataBase
 *
 * @author wanderer
 */
class DataVisualizerDataBase extends DataVisualizer {

    //database data
    private $HOST = "localhost";
    private $DBUSER = "MossUser";
    private $DBPW = "6GWSKB4jLeDXTwnC";
    private $DBname = "MOSS";
    private $connection;
    private $tableName;

    /**
     *
     * @var <type>
     */
    private $years = array();
    /**
     *
     * @var <type>
     */
    private $countriesValues = array();

    public function __construct($showAverage = false, $tblName="") {
        parent::__construct($showAverage);
        if ($tblName == "") {
            $this->tableName = "ExtremeTemperatureKilled";
        } else {
            $this->tableName = $tblName;
        }

        //does the connection to the db
        $this->initiateDatabase();
        //puts the values from the table in attributes of this class
        $this->getAllDataFromTable();
    }

    /**
     * Connects to the database
     */
    private function initiateDatabase() {
        $this->connection = mysql_connect($this->HOST, $this->DBUSER, $this->DBPW);
        mysql_select_db($this->DBname, $this->connection) or die("Couldn't select database");
    }

    /**
     * Creates a graphic (png image) from the data and parameters chosen chosen
     * @param <String> $file_name the name of the file
     * @param <String> $title the title of the graphic (optional, if null it will get the title from the spreadsheet)
     * @param <String> $yAxisTitle
     * @param <Int> $chartType the type of graphic (by default its 2, i.e., a line graph)
     * @param <String()> $yDataToUse
     * @param <String()> $xDataToUse
     */
    protected function createChart($file_name, $title = "", $yAxisTitle = "", $chartType = 2, $yDataToUse = null, $xDataToUse = null) {
        /* Create the pData object */
        $myData = new pData();

        $this->filterYData($myData, $this->getYAxisLabels(), $yDataToUse);

        //defines the yAxis title (if it isn't passed in the method it uses the name of the default data)
        if ($yAxisTitle == "")
            $myData->setAxisName(0, "Number of Killed by Extreme Temperatures");
        else
            $myData->setAxisName(0, $yAxisTitle);

        //adds the X Data
        $this->filterXData($myData, $this->getXAxisLabels(), $xDataToUse);
        /* Put the timestamp column on the abscissa axis */
        $myData->setAbscissa("Years");
        $myData->setSerieDescription("Years", "Years");

        /*
          //gets the Y labels from the data
          $yAxis = $this->getYAxisLabels();

          //adds the Y Data
          $this->filterYData($myData, $yAxis, $yDataToUse);
          //adds the X Data
          //$myData->addPoints($this->getXAxisLabels(), "Years");
          $this->filterXData($myData, $this->getXAxisLabels(), $xDataToUse);
         */

        /* Create the pChart object */
        $myPicture = new pImage(900, 430, $myData);

        /* Turn off Antialiasing */
        $myPicture->Antialias = FALSE;

        /* Add a border to the picture */
        $myPicture->drawRectangle(0, 0, 899, 429, array("R" => 0, "G" => 0, "B" => 0));

        /* Write the chart title */
        $myPicture->setFontProperties(array("FontName" => "pchart/fonts/Forgotte.ttf", "FontSize" => 11));

        //when the title isn't passed to the method, it will get the title directly from the spreadsheet
        if ($title == "")
            $title = $this->getTitle();

        $myPicture->drawText(150, 35, $title, array("FontSize" => 20, "Align" => TEXT_ALIGN_BOTTOMMIDDLE));

        /* Set the default font */
        $myPicture->setFontProperties(array("FontName" => "pchart/fonts/pf_arma_five.ttf", "FontSize" => 6));

        /* Define the chart area */
        $myPicture->setGraphArea(60, 40, 850, 400);

        /* Draw the scale */
        $scaleSettings = array("XMargin" => 10, "YMargin" => 10, "Floating" => TRUE, "GridR" => 200, "GridG" => 200, "GridB" => 200, "DrawSubTicks" => TRUE, "CycleBackground" => TRUE);
        $myPicture->drawScale($scaleSettings);

        /* Turn on Antialiasing */
        $myPicture->Antialias = TRUE;

        /**
         * Draws the graphic given its type (Note: myPicture is passed by reference here)
         */
        $this->getChart($myPicture, $chartType);

        /* $myPicture->setGraphArea(500, 60, 670, 190);
          $myPicture->drawFilledRectangle(480, 170, 650, 200, array("R" => 255, "G" => 255, "B" => 255, "Surrounding" => -200, "Alpha" => 10));
          $myPicture->drawScale(array("XMargin" => 10, "YMargin" => 10, "Pos" => SCALE_POS_LEFTRIGHT, "DrawSubTicks" => TRUE));
          $myPicture->setShadow(TRUE, array("X" => -1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10));
          $myPicture->drawLineChart();
          $myPicture->setShadow(FALSE); */


        /* Write the chart legend */
        $myPicture->drawLegend(540, 20, array("Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL));

        /* Render the picture (choose the best way) */
        $myPicture->render("pictures/" . $file_name);
    }

    /**
     * This function filters the data to use in the X axis (usually the years)
     * @param <pData()> $data a pData object (passed by reference)
     * @param <String()> $xLabels the complete labels of the xAxis contained in the spreadsheet
     * @param <String()> $filterData the xLabels to be used in the graphic
     */
    protected function filterXData(&$data, $xLabels, $filterData = null) {
        if (empty($filterData)) {
            $data->addPoints($xLabels, "Years");
        } else {
            /*
             * Esta funcao serve para converter o array de strings para inteiros
             * (Isto teve que ser feito por causa que a funcao quando recebe a
             * string da problemas de memoria)
             */
            $filterDataInt = array_map(
                            create_function('$value', 'return (int)$value;'),
                            $filterData
            );

            $data->addPoints($filterDataInt, "Years");
        }
    }

    /**
     * Filters the Y axis data (in the energy case, these are the countries)
     * @param <pData> $data a pData object (passed by reference)
     * @param <String[]> $yLabels
     * @param <String[]> $filterData
     */
    protected function filterYData(&$data, $yLabels, $filterData = null) {
        if (empty($filterData)) {
            $data->addPoints($this->countriesValues[0], $yLabels[0]);
            $data->addPoints($this->countriesValues[1], $yLabels[1]);
            if ($this->showAverage) {
                $average0 = $this->getAverage($this->countriesValues[0]);
                $average1 = $this->getAverage($this->countriesValues[1]);
                for ($i = 0; $i < count($this->countriesValues[0]); $i++) {
                    $data->addPoints($average0, "{$yLabels[0]} (Media)");
                    $data->addPoints($average0, "{$yLabels[0]} (Media)");
                }
            }
        } else {
            /*
             * Esta funcao serve para converter o array de strings para inteiros
             * (Isto teve que ser feito por causa que a funcao quando recebe a
             * string da problemas de memoria)
             */
            $filterDataInt = array_map(
                            create_function('$value', 'return (int)$value;'),
                            $filterData
            );

            //adds the chosen data to the graphic
            for ($i = 0; $i < count($filterDataInt); $i++) {
                echo "array[$i] = " . $filterDataInt[$i] . " ; countriesValue = " . $this->countriesValues[$filterDataInt[$i]] . "<br/>";

                //preciso de usar $yLabels[$filterDataInt[$i]], pois as keys do countryValues são mesmo países, e aquilo q vem no filtro são indices do array, desta forma converto indice para nome de país
                $data->addPoints($this->countriesValues[$yLabels[$filterDataInt[$i]]], $yLabels[$filterDataInt[$i]]);
                if ($this->showAverage) {
                    for ($k = 0; $k < count($this->countriesValues[$yLabels[$filterDataInt[$i]]]); $k++) {
                        $data->addPoints($this->getAverage($this->countriesValues[$yLabels[$filterDataInt[$i]]]), "{$yLabels[$filterDataInt[$i]]} (Media)");
                    }
                }
            }
        }
    }

    /**
     * Gets the Years
     * @return <type>
     */
    protected function getXAxisLabels() {
        $query = "SELECT DISTINCT(Years) FROM ExtremeTemperatureKilled";
        $result = mysql_query($query) or die("Nao deu para executar o query " . $query . " pq " . mysql_error());

        $xLabels = array();
        while ($row = mysql_fetch_array($result)) {
            $xLabels[] = $row['Years'];
        }

        return $xLabels;
    }

    /**
     * Gets the names of the countries
     * @return <type>
     */
    protected function getYAxisLabels() {
        $query = "SHOW COLUMNS FROM $this->tableName";
        $result = mysql_query($query) or die("Nao deu para executar o query " . $query . " pq " . mysql_error());
        $yLabels = array();
        while ($row = mysql_fetch_array($result)) {
            if ($row['Field'] != 'id' && $row['Field'] != 'Years')
                $yLabels[] = $row['Field'];
        }
        return $yLabels;
    }

    /**
     * Fills the attributes of the class from the data in the table chosen
     */
    private function getAllDataFromTable() {
        $query = "SELECT * FROM $this->tableName";
        $result = mysql_query($query) or die("Nao deu para executar o query " . $query . " pq " . mysql_error());

        $i = 0;
        $countries = array();
        while ($row = mysql_fetch_array($result)) {
            /* Push the results of the query in an array */
            $years[] = $row["Years"];


            foreach ($this->getYAxisLabels() as $country) {
                $countries[$country][$i] = $row[$country];
            }


            /* $India[] = $row["India"];
              $Portugal[] = $row["Portugal"];
              $Romania[] = $row["Romania"];
              $USA[] = $row["United States"]; */

            $i++;
        }
        $this->countriesValues = $countries;
        $this->years = $years;
    }

    /**
     * Returns the average of an array
     * @param <String()> $a The array to average
     * @return <Int>  The average of the array
     */
    private function getAverage($a) {
        return array_sum($a) / count($a);
    }

}

?>
