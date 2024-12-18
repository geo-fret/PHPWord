<?php
/**
 * This file is part of PHPWord - A pure PHP library for reading and writing
 * word processing documents.
 *
 * PHPWord is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/PHPOffice/PHPWord/contributors.
 *
 * @see         https://github.com/PHPOffice/PHPWord
 *
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\PhpWord\Writer\Word2007\Part;

use PhpOffice\PhpWord\Element\Chart as ChartElement;
use PhpOffice\PhpWord\Shared\XMLWriter;

/**
 * Word2007 chart part writer: word/charts/chartx.xml.
 *
 * @since 0.12.0
 * @see  http://www.datypic.com/sc/ooxml/e-draw-chart_chartSpace.html
 */
class Chart extends AbstractPart
{
    /**
     * Chart element.
     *
     * @var \PhpOffice\PhpWord\Element\Chart
     */
    private $element;

    /**
     * Type definition.
     *
     * @var array
     */
    private $types = [
        'pie' => ['type' => 'pie', 'colors' => 1],
        'doughnut' => ['type' => 'doughnut', 'colors' => 1, 'hole' => 75, 'no3d' => true],
        'bar' => ['type' => 'bar', 'colors' => 0, 'axes' => true, 'bar' => 'bar', 'grouping' => 'clustered'],
        'stacked_bar' => ['type' => 'bar', 'colors' => 0, 'axes' => true, 'bar' => 'bar', 'grouping' => 'stacked'],
        'percent_stacked_bar' => ['type' => 'bar', 'colors' => 0, 'axes' => true, 'bar' => 'bar', 'grouping' => 'percentStacked'],
        'column' => ['type' => 'bar', 'colors' => 0, 'axes' => true, 'bar' => 'col', 'grouping' => 'clustered'],
        'stacked_column' => ['type' => 'bar', 'colors' => 0, 'axes' => true, 'bar' => 'col', 'grouping' => 'stacked'],
        'percent_stacked_column' => ['type' => 'bar', 'colors' => 0, 'axes' => true, 'bar' => 'col', 'grouping' => 'percentStacked'],
        'line' => ['type' => 'line', 'colors' => 0, 'axes' => true],
        'area' => ['type' => 'area', 'colors' => 0, 'axes' => true],
        'radar' => ['type' => 'radar', 'colors' => 0, 'axes' => true, 'radar' => 'standard', 'no3d' => true],
        'scatter' => ['type' => 'scatter', 'colors' => 0, 'axes' => true, 'scatter' => 'marker', 'no3d' => true],
    ];

    /**
     * Chart options.
     *
     * @var array
     */
    private $options = [];

    /**
     * Set chart element.
     */
    public function setElement(ChartElement $element): void
    {
        $this->element = $element;
    }

    /**
     * Write part.
     *
     * @return string
     */
    public function write()
    {
        $xmlWriter = $this->getXmlWriter();

        $xmlWriter->startDocument('1.0', 'UTF-8', 'yes');
        $xmlWriter->startElement('c:chartSpace');
        $xmlWriter->writeAttribute('xmlns:c', 'http://schemas.openxmlformats.org/drawingml/2006/chart');
        $xmlWriter->writeAttribute('xmlns:a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $xmlWriter->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $this->writeChart($xmlWriter);
        $this->writeShape($xmlWriter);

        $xmlWriter->endElement(); // c:chartSpace

        return $xmlWriter->getData();
    }

    /**
     * Write chart.
     *
     * @see  http://www.datypic.com/sc/ooxml/t-draw-chart_CT_Chart.html
     * @see  http://www.datypic.com/sc/ooxml/t-draw-chart_ST_DispBlanksAs.html
     */
    private function writeChart(XMLWriter $xmlWriter): void
    {
        $style = $this->element->getStyle();

        $xmlWriter->startElement('c:chart');

        $this->writePlotArea($xmlWriter);

        $xmlWriter->writeElementBlock('c:dispBlanksAs', 'val', $style->getDisplayBlanksAs());

        $xmlWriter->endElement(); // c:chart
    }

    /**
     * Write plot area.
     *
     * @see  http://www.datypic.com/sc/ooxml/t-draw-chart_CT_PlotArea.html
     * @see  http://www.datypic.com/sc/ooxml/t-draw-chart_CT_PieChart.html
     * @see  http://www.datypic.com/sc/ooxml/t-draw-chart_CT_DoughnutChart.html
     * @see  http://www.datypic.com/sc/ooxml/t-draw-chart_CT_BarChart.html
     * @see  http://www.datypic.com/sc/ooxml/t-draw-chart_CT_LineChart.html
     * @see  http://www.datypic.com/sc/ooxml/t-draw-chart_CT_AreaChart.html
     * @see  http://www.datypic.com/sc/ooxml/t-draw-chart_CT_RadarChart.html
     * @see  http://www.datypic.com/sc/ooxml/t-draw-chart_CT_ScatterChart.html
     */
    private function writePlotArea(XMLWriter $xmlWriter): void
    {
        $type = $this->element->getType();
        $style = $this->element->getStyle();
        if (isset($this->element->options)) {
            $this->options = array_merge($this->types[$type], $this->element->options);
        } else {
            $this->options = $this->types[$type];
        }

        $title = $style->getTitle();
        $showLegend = $style->isShowLegend();
        $legendPosition = $style->getLegendPosition();
        $legendOverlay = $style->getLegendOverlay() ? 0 : 1;
        $legendStyle = $this->options['legendStyles'] ?? null;

        //Chart title
        if ($title) {
            $xmlWriter->startElement('c:title');
            $xmlWriter->startElement('c:tx');
            $xmlWriter->startElement('c:rich');
            $xmlWriter->writeRaw('
                <a:bodyPr/>
                <a:lstStyle/>
                <a:p>
                <a:pPr>
                <a:defRPr/></a:pPr><a:r><a:rPr/><a:t>' . $title . '</a:t></a:r>
                <a:endParaRPr/>
                </a:p>');
            $xmlWriter->endElement(); // c:rich
            $xmlWriter->endElement(); // c:tx
            $xmlWriter->endElement(); // c:title
        } else {
            $xmlWriter->writeElementBlock('c:autoTitleDeleted', 'val', 1);
        }

        //Chart legend
        if ($showLegend) {
            $xmlWriter->startElement('c:legend');
            $xmlWriter->writeElementBlock('c:legendPos', 'val', $legendPosition);
            $xmlWriter->writeElementBlock('c:overlay', 'val', $legendOverlay);

            $index = 0;
            foreach ($this->element->getSeries() as $seriesItem) {
                if (isset($seriesItem['styles']['legendEntry'])) {
                    $delete = $seriesItem['styles']['legendEntry'] ? 0 : 1;
                    $xmlWriter->startElement('c:legendEntry');
                    $xmlWriter->writeElementBlock('c:idx', 'val', $index);
                    $xmlWriter->writeElementBlock('c:delete', 'val', $delete);
                    $xmlWriter->endElement(); // c:legendEntry
                }
                ++$index;
            }
            if ($legendStyle) {
                $this->writeTextStyle($xmlWriter, $legendStyle);
            }
            $xmlWriter->endElement(); // c:legend
        }

        $xmlWriter->startElement('c:plotArea');
        $xmlWriter->writeElement('c:layout');

        // Chart
        $chartType = $this->options['type'];
        $chartType .= $style->is3d() && !isset($this->options['no3d']) ? '3D' : '';
        $chartType .= 'Chart';
        $xmlWriter->startElement("c:{$chartType}");

        $xmlWriter->writeElementBlock('c:varyColors', 'val', $this->options['colors']);
        if ($type == 'area') {
            $xmlWriter->writeElementBlock('c:grouping', 'val', 'standard');
        }
        if (isset($this->options['hole'])) {
            $xmlWriter->writeElementBlock('c:holeSize', 'val', $this->options['hole']);
        }
        if (isset($this->options['bar'])) {
            $xmlWriter->writeElementBlock('c:barDir', 'val', $this->options['bar']); // bar|col
            $xmlWriter->writeElementBlock('c:grouping', 'val', $this->options['grouping']); // 3d; standard = percentStacked
        }
        if (isset($this->options['radar'])) {
            $xmlWriter->writeElementBlock('c:radarStyle', 'val', $this->options['radar']);
        }
        if (isset($this->options['scatter'])) {
            $xmlWriter->writeElementBlock('c:scatterStyle', 'val', $this->options['scatter']);
        }

        // Series
        $this->writeSeries($xmlWriter, isset($this->options['scatter']));

        // don't overlap if grouping is 'clustered'
        if (!isset($this->options['grouping']) || $this->options['grouping'] != 'clustered') {
            $xmlWriter->writeElementBlock('c:overlap', 'val', '100');
        }

        // Axes
        if (isset($this->options['axes'])) {
            $xmlWriter->writeElementBlock('c:axId', 'val', 1);
            $xmlWriter->writeElementBlock('c:axId', 'val', 2);
        }

        $xmlWriter->endElement(); // chart type

        // Axes
        if (isset($this->options['axes'])) {
            if ($style->areCategoriesNumeric()) {
                $minCategory = $this->options['minCategory'] ?? null;
                $maxCategory = $this->options['maxCategory'] ?? null;
                $minValue = $this->options['minValue'] ?? null;
                $maxValue = $this->options['maxValue'] ?? null;
                $formatCategory = $this->options['categoryFormat'] ?? null;
                $formatValue = $this->options['valueFormat'] ?? null;
                $stylesCategory = $this->options['categoryStyles'] ?? null;
                $stylesValue = $this->options['valueStyles'] ?? null;
                $this->writeAxis($xmlWriter, 'cat', 'c:valAx', $minCategory, $maxCategory, $formatCategory, $stylesCategory);
                $this->writeAxis($xmlWriter, 'val', 'c:valAx', $minValue, $maxValue, $formatValue, $stylesValue);
            } else {
                $this->writeAxis($xmlWriter, 'cat');
                $this->writeAxis($xmlWriter, 'val');
            }
        }

        $xmlWriter->endElement(); // c:plotArea
    }

    /**
     * Write series.
     *
     * @param bool $scatter
     */
    private function writeSeries(XMLWriter $xmlWriter, $scatter = false): void
    {
        $series = $this->element->getSeries();
        $style = $this->element->getStyle();
        $colors = $style->getColors();

        $index = 0;
        $colorIndex = 0;
        foreach ($series as $seriesItem) {
            $categories = $seriesItem['categories'];
            $values = $seriesItem['values'];
            $seriesStyle = $seriesItem['styles'] ?? null;

            $xmlWriter->startElement('c:ser');

            $xmlWriter->writeElementBlock('c:idx', 'val', $index);
            $xmlWriter->writeElementBlock('c:order', 'val', $index);

            if (null !== $seriesItem['name'] && $seriesItem['name'] != '') {
                $xmlWriter->startElement('c:tx');
                $xmlWriter->startElement('c:strRef');
                $xmlWriter->startElement('c:strCache');
                $xmlWriter->writeElementBlock('c:ptCount', 'val', 1);
                $xmlWriter->startElement('c:pt');
                $xmlWriter->writeAttribute('idx', 0);
                $xmlWriter->startElement('c:v');
                $xmlWriter->writeRaw($seriesItem['name']);
                $xmlWriter->endElement(); // c:v
                $xmlWriter->endElement(); // c:pt
                $xmlWriter->endElement(); // c:strCache
                $xmlWriter->endElement(); // c:strRef
                $xmlWriter->endElement(); // c:tx
            }

            // The c:dLbls was added to make word charts look more like the reports in SurveyGizmo
            // This section needs to be made configurable before a pull request is made
            $xmlWriter->startElement('c:dLbls');

            foreach ($style->getDataLabelOptions() as $option => $val) {
                $xmlWriter->writeElementBlock("c:{$option}", 'val', (int) $val);
            }

            $xmlWriter->endElement(); // c:dLbls

            if ($scatter === true) {
                if (isset($seriesStyle['color'])) {
                    $curColor = $seriesStyle['color'];
                } elseif ($colors) {
                    $curColor = $colors[$colorIndex++ % count($colors)];
                } else {
                    $curColor = null;
                }
                $dashStyle = $seriesStyle['dashStyle'] ?? null;
                $drawLine = $seriesStyle['line'] ?? false;
                $this->writeShape($xmlWriter, $drawLine, $curColor, $dashStyle);

                if (isset($seriesStyle['marker'])) {
                    $markerSymbol = $seriesStyle['marker']['symbol'] ?? 'none';
                    $markerSize = $seriesStyle['marker']['size'] ?? null;
                    $fillColor = $seriesStyle['marker']['fillColor'] ?? null;
                    $lineColor = $seriesStyle['marker']['lineColor'] ?? null;
                    $this->writeMarkerOptions($xmlWriter, $markerSymbol, $markerSize, $fillColor, $lineColor);
                }

                if ($style->areCategoriesNumeric()) {
                    $this->writeSeriesItem($xmlWriter, 'xNum', $categories);
                    $this->writeSeriesItem($xmlWriter, 'yNum', $values);
                } else {
                    $this->writeSeriesItem($xmlWriter, 'xVal', $categories);
                    $this->writeSeriesItem($xmlWriter, 'yVal', $values);
                }
            } else {
                $this->writeSeriesItem($xmlWriter, 'cat', $categories);
                $this->writeSeriesItem($xmlWriter, 'val', $values);

                // check that there are colors
                if (is_array($colors) && count($colors) > 0) {
                    // assign a color to each value
                    $valueIndex = 0;
                    for ($i = 0; $i < count($values); ++$i) {
                        // check that there are still enought colors
                        $xmlWriter->startElement('c:dPt');
                        $xmlWriter->writeElementBlock('c:idx', 'val', $valueIndex);
                        $xmlWriter->startElement('c:spPr');
                        $xmlWriter->startElement('a:solidFill');
                        $xmlWriter->writeElementBlock('a:srgbClr', 'val', $colors[$colorIndex++ % count($colors)]);
                        $xmlWriter->endElement(); // a:solidFill
                        $xmlWriter->endElement(); // c:spPr
                        $xmlWriter->endElement(); // c:dPt
                        ++$valueIndex;
                    }
                }
            }

            $smooth = $seriesStyle['smooth'] ?? true;
            $smooth = $smooth ? 1 : 0;
            $xmlWriter->writeElementBlock('c:smooth', 'val', $smooth);

            $xmlWriter->endElement(); // c:ser
            ++$index;
        }
    }

    /**
     * Write series items.
     *
     * @param string $type
     * @param array $values
     */
    private function writeSeriesItem(XMLWriter $xmlWriter, $type, $values): void
    {
        $types = [
            'cat' => ['c:cat', 'c:strLit'],
            'val' => ['c:val', 'c:numLit'],
            'xVal' => ['c:xVal', 'c:strLit'],
            'yVal' => ['c:yVal', 'c:numLit'],
            'xNum' => ['c:xVal', 'c:numLit'],
            'yNum' => ['c:yVal', 'c:numLit'],
        ];
        [$itemType, $itemLit] = $types[$type];

        $xmlWriter->startElement($itemType);
        $xmlWriter->startElement($itemLit);
        $xmlWriter->writeElementBlock('c:ptCount', 'val', count($values));

        $index = 0;
        foreach ($values as $value) {
            if ($value !== null) {
                $xmlWriter->startElement('c:pt');
                $xmlWriter->writeAttribute('idx', $index);
                if (\PhpOffice\PhpWord\Settings::isOutputEscapingEnabled()) {
                    $xmlWriter->writeElement('c:v', $value);
                } else {
                    $xmlWriter->startElement('c:v');
                    $xmlWriter->writeRaw($value);
                    $xmlWriter->endElement(); // c:v
                }
                $xmlWriter->endElement(); // c:pt
            }
            ++$index;
        }

        $xmlWriter->endElement(); // $itemLit
        $xmlWriter->endElement(); // $itemType
    }

    /**
     * Write axis.
     *
     * @see  http://www.datypic.com/sc/ooxml/t-draw-chart_CT_CatAx.html
     *
     * @param string $type
     * @param null|string $typeText
     * @param null|mixed $minValue
     * @param null|mixed $maxValue
     * @param null|string $format
     * @param null|array $textStyle
     */
    private function writeAxis(XMLWriter $xmlWriter, $type, $typeText = null, $minValue = null, $maxValue = null, $format = null, $textStyle = null): void
    {
        $style = $this->element->getStyle();
        $labelStyle = $textStyle['labels'] ?? null;
        $titleStyle = $textStyle['title'] ?? null;

        $types = [
            'cat' => ['c:catAx', 1, 'b', 2],
            'val' => ['c:valAx', 2, 'l', 1],
        ];
        [$axisType, $axisId, $axisPos, $axisCross] = $types[$type];
        if ($typeText !== null) {
            $axisType = $typeText;
        }

        $xmlWriter->startElement($axisType);

        $xmlWriter->writeElementBlock('c:axId', 'val', $axisId);
        $xmlWriter->writeElementBlock('c:axPos', 'val', $axisPos);

        $categoryAxisTitle = $style->getCategoryAxisTitle();
        $valueAxisTitle = $style->getValueAxisTitle();

        if ($type == 'cat') {
            if (null !== $categoryAxisTitle) {
                $this->writeAxisTitle($xmlWriter, $categoryAxisTitle, $titleStyle);
            }
        } elseif ($type == 'val') {
            if (null !== $valueAxisTitle) {
                $this->writeAxisTitle($xmlWriter, $valueAxisTitle, $titleStyle);
            }
        }

        $xmlWriter->writeElementBlock('c:crossAx', 'val', $axisCross);
        $xmlWriter->writeElementBlock('c:auto', 'val', 1);

        if (isset($this->options['axes'])) {
            $xmlWriter->writeElementBlock('c:delete', 'val', 0);
            $xmlWriter->writeElementBlock('c:majorTickMark', 'val', $style->getMajorTickPosition());
            $xmlWriter->writeElementBlock('c:minorTickMark', 'val', 'none');
            if ($style->showAxisLabels()) {
                if ($axisType == 'c:catAx') {
                    $xmlWriter->writeElementBlock('c:tickLblPos', 'val', $style->getCategoryLabelPosition());
                } else {
                    $xmlWriter->writeElementBlock('c:tickLblPos', 'val', $style->getValueLabelPosition());
                }
            } else {
                $xmlWriter->writeElementBlock('c:tickLblPos', 'val', 'none');
            }
            $xmlWriter->writeElementBlock('c:crosses', 'val', 'autoZero');
        }
        if (isset($this->options['radar']) || ($type == 'cat' && $style->showGridX()) || ($type == 'val' && $style->showGridY())) {
            $xmlWriter->writeElement('c:majorGridlines');
        }

        if ($format !== null) {
            $xmlWriter->writeElementBlock('c:numFmt', ['formatCode' => $format, 'sourceLinked' => 0]);
        }
        if ($labelStyle) {
            $this->writeTextStyle($xmlWriter, $labelStyle);
        }

        $xmlWriter->startElement('c:scaling');
        $xmlWriter->writeElementBlock('c:orientation', 'val', 'minMax');
        if ($maxValue !== null) {
            $xmlWriter->writeElementBlock('c:max', 'val', $maxValue);
        }
        if ($minValue !== null) {
            $xmlWriter->writeElementBlock('c:min', 'val', $minValue);
        }
        $xmlWriter->endElement(); // c:scaling

        $this->writeShape($xmlWriter, true);

        $xmlWriter->endElement(); // $axisType
    }

    /**
     * Write shape.
     *
     * @see  http://www.datypic.com/sc/ooxml/t-a_CT_ShapeProperties.html
     *
     * @param bool $line
     * @param null|string $color
     * @param string $dashStyle
     */
    private function writeShape(XMLWriter $xmlWriter, $line = false, $color = null, $dashStyle = false): void
    {
        $xmlWriter->startElement('c:spPr');
        $xmlWriter->startElement('a:ln');
        if ($line === true) {
            if ($color === null) {
                $xmlWriter->writeElement('a:solidFill');
            } else {
                $xmlWriter->startElement('a:solidFill');
                $xmlWriter->writeElementBlock('a:srgbClr', 'val', $color);
                $xmlWriter->endElement(); // a:solidFill
            }
            if ($dashStyle) {
                $xmlWriter->writeElementBlock('a:prstDash', 'val', $dashStyle);
            }
        } else {
            $xmlWriter->writeElement('a:noFill');
        }
        $xmlWriter->endElement(); // a:ln
        $xmlWriter->endElement(); // c:spPr
    }

    /**
     * Write axis title.
     * 
     * @see  http://www.datypic.com/sc/ooxml/e-draw-chart_title-1.html
     * 
     * @param string $title
     * @param null|array $textStyle
     */
    private function writeAxisTitle(XMLWriter $xmlWriter, $title, $textStyle = null): void
    {
        $rotate = $textStyle['rotate'] ?? 0;
        $rotate = (int) round($rotate * 60 * 1000);
        $size = $textStyle['size'] ?? 10;
        $size = (int) round($size * 100);
        $bold = $textStyle['bold'] ?? false;
        $bold = $bold ? 1 : 0;
        $italic = $style['italic'] ?? false;
        $italic = $italic ? 1 : 0;

        $xmlWriter->startElement('c:title'); //start c:title
        $xmlWriter->startElement('c:tx'); //start c:tx
        $xmlWriter->startElement('c:rich'); // start c:rich
        $xmlWriter->writeElementBlock('a:bodyPr', 'rot', $rotate);
        $xmlWriter->writeElement('a:lstStyle');
        $xmlWriter->startElement('a:p');
        $xmlWriter->startElement('a:pPr');
        $xmlWriter->writeElementBlock('a:defRPr', ['sz' => $size, 'b' => $bold, 'i' => $italic]);
        $xmlWriter->endElement(); // end a:pPr
        $xmlWriter->startElement('a:r');

        $xmlWriter->startElement('a:t');
        $xmlWriter->writeRaw($title);
        $xmlWriter->endElement(); //end a:t

        $xmlWriter->endElement(); // end a:r
        $xmlWriter->endElement(); //end a:p
        $xmlWriter->endElement(); //end c:rich
        $xmlWriter->endElement(); // end c:tx
        $xmlWriter->writeElementBlock('c:overlay', 'val', '0');
        $xmlWriter->endElement(); // end c:title
    }

    /**
     * Write marker options.
     * 
     * @see  http://www.datypic.com/sc/ooxml/e-draw-chart_title-1.html
     * 
     * @param string $markerSymbol
     * @param null|int $markerSize
     * @param null|string $fillColor
     * @param null|string $lineColor
     */
    private function writeMarkerOptions(XMLWriter $xmlWriter, $markerSymbol = 'none', $markerSize = null, $fillColor = null, $lineColor = null): void
    {

    }

    /**
     * Write text style.
     * 
     * @param array $style
     */
    private function writeTextStyle(XMLWriter $xmlWriter, array $style): void
    {
        $rotate = $style['rotate'] ?? 0;
        $rotate = (int) round($rotate * 60 * 1000);
        $size = $style['size'] ?? 10;
        $size = (int) round($size * 100);
        $bold = $style['bold'] ?? false;
        $bold = $bold ? 1 : 0;
        $italic = $style['italic'] ?? false;
        $italic = $italic ? 1 : 0;

        $xmlWriter->startElement('c:txPr');
        $xmlWriter->writeElementBlock('a:bodyPr', 'rot', $rotate);
        $xmlWriter->startElement('a:p');
        $xmlWriter->startElement('a:pPr');
        $xmlWriter->writeElementBlock('a:defRPr', ['sz' => $size, 'b' => $bold, 'i' => $italic]);
        $xmlWriter->endElement(); // a:pPr
        $xmlWriter->endElement(); // a:p
        $xmlWriter->endElement(); // c:txPr
    }
}
