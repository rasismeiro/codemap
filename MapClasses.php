<?php

/*
 * @package MapClasses
 * @version 1.0.0
 * @author Ricardo Sismeiro <ricardo@sismeiro.com>
 * @copyright 2012 Ricardo Sismeiro
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/
 * @filesource
 */

class MapClasses
{

  public function __construct()
  {
    ignore_user_abort(true);
    set_time_limit(0);
    $this->_sendHeaders();
  }

  private function _sendHeaders()
  {
    header("HTTP/1.1 200 OK", true, 200);
  }

  public function run()
  {

    $directory = isset($_GET['dir']) ? $_GET['dir'] : '';
    $directoryHtml = htmlentities($directory);
    if (empty($directory)) {
      exit;
    }

    $this->_outputHeader();
    $this->_outputFlush();

    $this->_ouputBody($directory);
    $this->_outputFlush();

    $this->_outputFooter();
    $this->_outputFlush();
  }

  private function _outputFooter()
  {
    $result = '</body>' . PHP_EOL . '</html>';
    echo $result;
  }

  private function _outputFlush()
  {
    ob_flush();
    flush();
  }

  private function _ouputBody($directory)
  {

    $result = '<body>' . PHP_EOL;
    echo $result;
    $data = MapClassesSearch::classesCalls($directory);
    $this->_outputScript($data);
  }

  private function _formatGraphData($a)
  {
    $r = '';
    $_classes = array();
    foreach ($a as $k => $v) {
      foreach ($v as $i => $s) {
        if (!isset($_classes[$i])) {
          $_classes[$i] = 0;
        }
        $_classes[$i]++;
      }
    }

    foreach ($a as $k => $v) {
      $t = substr(md5($k), -6) . ' : ' . basename($k);
      $_c = count($v);
      foreach ($v as $i => $s) {
        $r .= '{source: "' . $i . '", target: "' . $t . '", type: "suit" , group : "' . $_c . '" , ccall : "' . $_classes[$i] . '"},';
      }
    }
    if (!empty($r)) {
      $r = substr($r, 0, -1);
    }

    $r = '[' . $r . ']';
    return $r;
  }

  private function _outputScript($data)
  {
    $result = '
    <script type="text/javascript">
      var links = ' . $this->_formatGraphData($data) . ';' . PHP_EOL;

    $result .=<<<OUTPUT
      var nodes = {};

      // Compute the distinct nodes from the links.
      links.forEach(function(link) {
        link.source = nodes[link.source] || (nodes[link.source] = {name: link.source, tipo : 0, ccall : link.ccall});
        link.target = nodes[link.target] || (nodes[link.target] = {name: link.target, tipo : link.group , ccall : 0});
      });

      var w = 2600,
      h = 2600,
      fill = d3.scale.category20();

      var classSize = function (i){
        var f = [2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22];
        if (i>20) {i = 20;}
        return f[i];
      };

      var force = d3.layout.force()
      .nodes(d3.values(nodes))
      .links(links)
      .size([w, h])
      .linkDistance(100)
      .charge(-500)
      .on("tick", tick)
      .start();

      var svg = d3.select("body").append("svg")
      .attr("width", w)
      .attr("height", h);

      // Per-type markers, as they don't inherit styles.
      svg.append("defs").selectAll("marker")
      .data(["suit", "licensing", "resolved"])
      .enter().append("marker")
      .attr("id", String)
      .attr("viewBox", "0 -5 10 10")
      .attr("refX", 15)
      .attr("refY", -1.5)
      .attr("markerWidth", 6)
      .attr("markerHeight", 6)
      .attr("orient", "auto")
      .append("path")
      .attr("d", "M0,-5L10,0L0,5");


      var path = svg.append("g").selectAll("path")
      .data(force.links())
      .enter().append("path")
      .attr("class", function(d) { return "link " + d.type; })
      .attr("marker-end", function(d) { return "url(#" + d.type + ")"; });

      var circle = svg.append("g").selectAll("circle")
      .data(force.nodes())
      .enter().append("circle")
      .attr("r", function(d){ if (d.ccall > 0) return classSize(d.ccall); else return 6;})
      .attr("fill", function(d) { if (d.tipo > 0) return fill(d.tipo); else return '#ccdd11'; })
      .call(force.drag);

      var text = svg.append("g").selectAll("g")
      .data(force.nodes())
      .enter().append("g");

      // A copy of the text with a thick white stroke for legibility.
      text.append("text")
      .attr("x", 8)
      .attr("y", ".31em")
      .attr("class", "shadow")
      .text(function(d) { return d.name; });

      text.append("text")
      .attr("x", 8)
      .attr("y", ".31em")
      .text(function(d) { return d.name; });

      // Use elliptical arc path segments to doubly-encode directionality.
      function tick() {
        path.attr("d", function(d) {
          var dx = d.target.x - d.source.x,
          dy = d.target.y - d.source.y,
          dr = Math.sqrt(dx * dx + dy * dy);
          return "M" + d.source.x + "," + d.source.y + "A" + dr + "," + dr + " 0 0,1 " + d.target.x + "," + d.target.y;
        });

        circle.attr("transform", function(d) {
          return "translate(" + d.x + "," + d.y + ")";
        });

        text.attr("transform", function(d) {
          return "translate(" + d.x + "," + d.y + ")";
        });
      }

    </script>

OUTPUT;

    echo $result;
  }

  private function _outputHeader()
  {
    $result = <<<OUTPUT
<!DOCTYPE html>
<html>
  <head>
    <title>MapClasses</title>
    <meta charset="UTF-8" />
    <!-- for more info http://mbostock.github.com/d3/ -->
    <script type="text/javascript" src="http://sismeiro.com/d3/d3.js"></script>
    <script type="text/javascript" src="http://sismeiro.com/d3/d3.geom.js"></script>
    <script type="text/javascript" src="http://sismeiro.com/d3/d3.layout.js"></script>
    <style type="text/css">

      path.link {
        fill: none;
        stroke: #666;
        stroke-width: 1.5px;
      }

      marker#licensing {
        fill: green;
      }

      path.link.licensing {
        stroke: green;
      }

      path.link.resolved {
        stroke-dasharray: 0,2 1;
      }

      circle {
        stroke: #333;
        stroke-width: 1.5px;
      }

      text {
        font: 9px sans-serif;
        pointer-events: none;
      }

      text.shadow {
        stroke: #fff;
        stroke-width: 3px;
        stroke-opacity: .8;
      }

    </style>
  </head>
OUTPUT;

    echo $result;
  }

}
