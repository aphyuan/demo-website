<?php
	// Start session.
	session_start();
	
	// Set a key, checked in mailer, prevents against spammers trying to hijack the mailer.
	$security_token = $_SESSION['security_token'] = uniqid(rand());
	
	if ( ! isset($_SESSION['formMessage'])) {
		$_SESSION['formMessage'] = 'Fill in the form below to send me an email.';	
	}
	
	if ( ! isset($_SESSION['formFooter'])) {
		$_SESSION['formFooter'] = ' ';
	}
	
	if ( ! isset($_SESSION['form'])) {
		$_SESSION['form'] = array();
	}
	
	function check($field, $type = '', $value = '') {
		$string = "";
		if (isset($_SESSION['form'][$field])) {
			switch($type) {
				case 'checkbox':
					$string = 'checked="checked"';
					break;
				case 'radio':
					if($_SESSION['form'][$field] === $value) {
						$string = 'checked="checked"';
					}
					break;
				case 'select':
					if($_SESSION['form'][$field] === $value) {
						$string = 'selected="selected"';
					}
					break;
				default:
					$string = $_SESSION['form'][$field];
			}
		}
		return $string;
	}
?><!DOCTYPE html><!-- Forward 1.1.6 --><html><head>
<script type="text/javascript" src="//d3js.org/d3.v3.js?rwcache=522861671"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script src="http://underscorejs.org/underscore-min.js"></script>
<script>
	(function() {
  function cartesianProduct(arr) {
    return _.reduce(arr, function(a, b) {
      return _.flatten(_.map(a, function(x) {
        return _.map(b, function(y) {
          return x.concat([y])
        })
      }), true)
    }, [
      []
    ])
  }
  d3.bayesianNetwork = function(idOrNode, style, width, height) {
    var bbox = $(idOrNode)[0].getBoundingClientRect()
    width = typeof width !== 'undefined' ? width : bbox.width
    height = typeof height !== 'undefined' ? height : 1000
    var nodes = []
    var links = []

    function tabulate(node, columns, data) {
      var foreignObject = node.append("foreignObject")
      var table = foreignObject.append("xhtml:body").style("position", "relative").append("table").style("position", "relative"),
        thead = table.append("thead").data(data),
        tbody = table.append("tbody").data(data)
        // append the header row
      var headerRows = thead.selectAll("tr").data(function(n) {
        var numStates = n.states.length
        var numCols = n.cpt.probabilities.length / numStates
        var parentStates = n.cpt.parents.map(function(p, i) {
          var cols = p.states.map(function(s) {
            var result = {
              text: (p.name + "=" + s)
            }
            if ('currentState' in p) result.active = s == p.currentState
            return result
          })
          return cols
        })
        var matrix = _.zip.apply(_, cartesianProduct(parentStates))
        var columnsActive = null
        matrix.forEach(function(row, rowIndex) {
          columnsActive = columnsActive || new Array(row.length)
          row.forEach(function(col, colIndex) {
            if ('active' in col) {
              if (typeof columnsActive[colIndex] == 'undefined') columnsActive[colIndex] = col.active
              else columnsActive[colIndex] = columnsActive[colIndex] && col.active
            }
          })
        })
        var matrixWithCorrectActiveFlags = matrix.map(function(row) {
          return row.map(function(col, colIndex) {
            if (typeof columnsActive[colIndex] == 'undefined') return col
            else return _.extend({}, col, {
              active: columnsActive[colIndex]
            })
          })
        })
        return matrixWithCorrectActiveFlags.map(function(row) {
          return [{
            text: ""
          }].concat(row)
        }).concat([
          [{
            text: n.name
          }].concat(Array.apply(null, new Array(numCols)).map(function() {
            return {
              text: "prob."
            }
          })).concat([{
            text: "inferred"
          }])
        ])
      }).enter().append("tr")
      headerRows.selectAll("th").data(function(arr) {
          return arr;
        }).enter().append("th").text(function(column) {
          return column.text;
        }).style("color", function(column) {
          return ('active' in column) ? (column.active ? "Green" : "LightGray") : "Black"
        }).attr("colspan", function(column) {
          return column.colspan;
        }).attr("align", "center")
        // create a row for each object in the data
      var rows = tbody.selectAll("tr").data(function(nod) {
          var numStates = nod.states.length
          var sliceLength = nod.cpt.probabilities.length / numStates
          return nod.states.map(function(s, i) {
            var mrg = nod.marginalized.probabilities[i]
            var result = {
              rowData: [s].concat(nod.cpt.probabilities.slice(i * sliceLength, (i + 1) * sliceLength)).concat([mrg])
            }
            if ('currentState' in nod) result.active = s == nod.currentState
            return result
          })
        }).enter().append("tr").style("color", function(column) {
          return ('active' in column) ? (column.active ? "Green" : "LightGray") : "Black"
        })
        // create a cell in each row for each column
      var cells = rows.selectAll("td").data(function(row) {
          return row.rowData
        }).enter().append("td").attr("style", "font-family: Courier") // sets the font style
        .html(function(d) {
          if (isNaN(d)) {
            return d
          } else {
            return Math.round(d * 1000) / 1000
          }
        })
      foreignObject.each(function(d) {
        var tableRect = $(this).find("table")[0].getBoundingClientRect()
        var width = tableRect.width + 4
        var height = tableRect.height + 4
        d3.select(this).attr({
          width: width,
          height: height,
          x: "-" + (width / 2),
          y: "30px"
        })
      })
      return table
    }

    function barChartify(node, data) {
      var barChart = node.append("g").data(data)
      // var container = barChart.append("g").selectAll("rect.container")
      //   .data(function(nod) {
      //     return [nod]
      //   })
      //   .enter()
      //   .append("rect")
      //   .attr("class", "container")
      //   .attr("x", -47)
      //   .attr("y", 15)
      //   .attr("width", 94)
      //   .attr("height", function(n) {
      //     return 8 + n.states.length * 15
      //   })
      //   .attr("fill", "rgb(200, 200, 256)")
      //   .attr("stroke-width", 2)
      //   .attr("stroke", "rgb(100,100,128)")
      var bars = barChart.selectAll("rect.bar")
        .data(function(nod) {
          return nod.marginalized.probabilities
        })
        .enter()
        .append("rect")
        .attr("class", "bar")
        .attr("x", -20)
        .attr("y", function(d, i) {
          return 20 + i * 15
        })
        .attr("width", function(d, i) {
          return +d * 40
        })
        .attr("height", 12)
        .attr("fill", function(d, i) {
          return "rgb(255, 255, " + (i * 10) + ")"
        })

      var texts = barChart.selectAll("text")
        .data(function(nod) {
          return nod.states
        })
        .enter()
        .append("text")
        .text(function(d) {
          return d
        })
        .attr("text-anchor", "middle")
        .attr("x", function(d, i) {
          return 0
        })
        .attr("y", function(d, i) {
          return i * 15 + 30
        })
        .attr("font-family", "sans-serif")
        .attr("font-size", "11px")
        .attr("fill", "black")
      return barChart
    }
    var color = d3.scale.category20()
    var force = d3.layout.force().charge(-10000).linkDistance(300).size([width, height])
    force.nodes(nodes).links(links).start()
    var svg = d3.select(idOrNode).append("svg").attr("width", width).attr("height", height)
      // build the arrow.
    svg.append("svg:defs").selectAll("marker").data(["end"]) // Different link/path types can be defined here
      .enter().append("svg:marker") // This section adds in the arrows
      .attr("id", "markerArrow")
      .attr("viewBox", "0 -5 10 10")
      .attr("refX", 10)
      .attr("refY", 0)
      .attr("markerWidth", 5)
      .attr("markerHeight", 5)
      .attr("orient", "auto")
      .append("svg:path")
      .attr("d", "M0,-5L10,0L0,5")
    var circleSize = 100
    var textColor = "black"
    var edge = svg.selectAll(".edge")
    var node = svg.selectAll(".node")

    function render(network) {
      console.log("Rendering the following nodes and edges", network.nodes, network.edges)
      network.nodes.forEach(function(node) {
        var existingNode = _.findWhere(nodes, {
          name: node.name
        })
        if (existingNode) {
          _.extend(node, _.pick(existingNode, 'x', 'y', 'px', 'py', 'fixed'))
        }
      })
      nodes.splice(0, nodes.length)
      links.splice(0, links.length)
      network.nodes.forEach(function(n) {
        nodes.push(n)
      })
      network.edges.forEach(function(e) {
        links.push(e)
      })
      edge = edge.data(force.links())
      edge.enter()
        .append("svg:path")
        .attr("class", "link")
        .attr("marker-end", "url(#markerArrow)")
        .style("stroke", "#999")
        .style("stroke-opacity", ".6")
        .style("stroke-width", function(d) {
        return Math.sqrt(16);
      })
      edge.exit().remove()
      var node_drag = d3.behavior.drag().on("dragstart", dragstart).on("drag", dragmove).on("dragend", dragend);

      function dragstart(d, i) {
        force.stop() // stops the force auto positioning before you start dragging
      }

      function dragmove(d, i) {
        d.px += d3.event.dx;
        d.py += d3.event.dy;
        d.x += d3.event.dx;
        d.y += d3.event.dy;
        tick(); // this is the key to make it work together with updating both px,py,x,y on d !
      }

      function dragend(d, i) {
        d.fixed = true; // of course set the node to fixed so the force doesn't include the node in its auto positioning stuff
        tick();
        force.resume();
      }
      node = node.data(force.nodes(), function(d) {
        return d.name;
      })
      node.enter().append("g").attr("class", "node").call(node_drag)
      node.exit().remove()
      node.append("circle").attr("r", circleSize).style("fill", function(d) {
        return color(d.group);
      })
      node.append("text")
        .attr("text-anchor", "middle")
        .attr("dy", "7px")
        .attr("font-size", "20px")
        .attr("font-family", "sans-serif")
        .attr("fill", textColor).attr("stroke", textColor).text(function(d) {
          return d.name;
        })
      node.append("title").text(function(d) {
        return d.name;
      })
      if (style == "cpt") {
        tabulate(node, ["name", "probability"], nodes)
      } else {
        barChartify(node, nodes)
      }


      function tick() {
        edge.attr("d", function(d) {
          var dx = d.target.x - d.source.x,
            dy = d.target.y - d.source.y
          var lineAngle = Math.atan2(dy, dx)
          return "M" + (d.source.x + Math.cos(lineAngle) * circleSize) + ","
            + (d.source.y + Math.sin(lineAngle) * circleSize)
            + "L" + (d.target.x - Math.cos(lineAngle) * circleSize) + ","
            + (d.target.y - Math.sin(lineAngle) * circleSize)
        })
        node.attr("transform", function(d) {
          return "translate(" + d.x + "," + d.y + ")";
        })
      }
      force.on("tick", tick)
      force.start()
    }
    return render
  };
})();


</script>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="robots" content="index, follow" />
		<meta name="generator" content="RapidWeaver" />
		<meta content="width=device-width, initial-scale=1" name="viewport"><title>contact | Zoom Social</title><style type="text/css"><ul class="disc">,ul.circle,ul.square,ol.arabic-numbers,ol.upper-alpha,ol.lower-alpha,ol.upper-roman,ol.lower-roman{margin:0 0 18px 0;padding:0;padding-left:20px;list-style-position:outside}ul.disc{list-style-type:disc}ul.circle{list-style-type:circle}ul.square{list-style-type:square}ol.arabic-numbers{list-style-type:decimal}ol.upper-alpha{list-style-type:upper-alpha}ol.lower-alpha{list-style-type:lower-alpha}ol.upper-roman{list-style-type:upper-roman}ol.lower-roman{list-style-type:lower-roman}b,strong{font-weight:bolder}em{font-style:italic}a{text-decoration:none}hr.t{height:1px;font-size:0;width:100%;border:none}img[alt~="edge"]{width:100%;height:auto;line-height:0}.cf:after{content:"";display:table;clear:both}.image-left img{float:left;margin:5px 20px 15px 5px}.image-right img{float:right;margin:5px 5px 15px 20px}.image-left img[alt~="edge"]{max-width:50%}.image-right img[alt~="edge"]{max-width:50%}#nav-config,#layout-config{position:absolute;visibility:hidden}@-webkit-keyframes ncdFadeIn{from{opacity:0}to{opacity:1}}html,body{padding:0;margin:0;max-width:100% !important;overflow-x:hidden !important}html{font-size:17px}body{font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;-webkit-animation:ncdFadeIn .4s ease-out both .2s}body.ready #site-feature #site-logo{left:0;top:50%;width:40%;padding:0 30%;position:absolute}body.ready.feature #site-feature{max-width:1090px}@media screen and (min-width: 850px){body.ready.pull section.theme,body.ready.feature.pull section.theme,body.ready.feature.logo.pull section.theme,body.ready.sf-ec.pull section.theme,body.ready.sf-ec.logo.pull section.theme{margin-top:60px}}body.ready.pull #site-feature,body.ready.feature.pull #site-feature,body.ready.feature.logo.pull #site-feature,body.ready.sf-ec.pull #site-feature,body.ready.sf-ec.logo.pull #site-feature{position:absolute;width:100%;max-width:100%}body.ready.pull #site-feature #site-logo img,body.ready.feature.pull #site-feature #site-logo img,body.ready.feature.logo.pull #site-feature #site-logo img,body.ready.sf-ec.pull #site-feature #site-logo img,body.ready.sf-ec.logo.pull #site-feature #site-logo img{display:none}body.sf-ec #site-feature .inner-wrapper{z-index:10;position:relative;max-width:100% !important;width:auto;margin-left:0;left:0}body.sf-ec #site-feature #site-logo{z-index:30;position:absolute;padding:0;margin-right:30%;margin-left:30%}@media screen and (min-width: 850px){body.feature section.theme,body.logo section.theme,body.feature.logo section.theme,body.sf-ec section.theme,body.sf-ec.logo section.theme{margin-top:-120px}}@media screen and (max-width: 850px){body.feature section.theme,body.logo section.theme,body.feature.logo section.theme,body.sf-ec section.theme,body.sf-ec.logo section.theme{padding:7%;margin-top:0;margin-bottom:0}}@media screen and (min-width: 850px){body .inner-wrapper{width:850px;position:relative}body .inner-wrapper #site-logo img{width:100%;height:auto}}body.no-title nav.theme{top:0 !important}body.no-title #gradient-effect{top:46px;margin-top:0}body.no-title #site-info{display:none}body.no-title.feature #gradient-effect{top:auto}body.no-title.full #gradient-effect{top:auto}.wrapper{max-width:850px;position:relative;margin:0 auto}.wrapper#container{z-index:20}header.theme{clear:both;position:relative}@media screen and (max-width: 850px){header.theme{top:0 !important}}#site-info{padding:20px;z-index:10;text-align:center;background:#fff}#site-info h1{font-size:33px;line-height:normal;font-weight:400;margin:0}#site-info span{font-size:15px;display:block;color:#AAA}#site-info h1+span{padding-top:0.8%}#site-feature{margin:0 auto;width:auto;position:relative;max-width:100%;background-size:cover;line-height:0}#site-feature.ratio>#gradient-effect{top:0;margin-top:0;bottom:auto}#site-feature.ratio .inner-wrapper{position:absolute;top:0;width:100%;height:100%}#site-feature .inner-wrapper{width:100%;margin:auto}#site-feature .middle{position:absolute;text-align:center;z-index:2;transform:translate3d(0, -50%, 0);margin-top:-60px}#site-feature #site-logo{text-align:center;padding:40px;position:relative;z-index:10}#site-feature #site-logo img{max-width:100%;height:auto;max-height:90%;width:auto;margin-top:5%;margin-bottom:5%}@media screen and (max-width: 850px){#site-feature #site-logo img{opacity:1 !important}}#site-feature #site-logo.middle{padding:0;left:-9999px;top:-9999px;width:0}@media screen and (max-width: 850px){#site-feature #site-logo.middle{margin-top:0;max-height:100%}}#site-feature #thisImg{width:100%;top:0;position:absolute}#site-feature #thisImg p,#site-feature #thisImg br{display:none}#site-feature #thisImg>img{width:100%;max-width:100%;height:auto}body.ready.feature #site-feature #thisImg{position:static;visibility:visible}body.feature #gradient-effect,body.sf-ec #gradient-effect{height:170px;bottom:-1px;top:auto}@media screen and (max-width: 850px){body.feature #gradient-effect,body.sf-ec #gradient-effect{display:none}}#gradient-effect{position:absolute;height:450px;width:100%;left:0;top:0}#gradient-effect.ncd-hide{display:none}li#plusNav>div:before{content:'More'}li#plusNav>div:after{right:10px}nav.theme a{color:#999}nav.theme>ul:before{background:#333}nav.theme{position:relative;overflow:hidden;z-index:1000;top:0;left:0;text-align:center;width:100%;font-weight:400;font-size:15px;height:46px;line-height:46px;user-select:none;transition:transform 0.25s}nav.theme.overflow{overflow:visible}nav.theme.open li{font-smoothing:subpixel-antialiased}nav.theme.m ul.l1{display:block;width:2000px}nav.theme.m ul.l1>li.move{visibility:hidden}nav.theme.m li#plusNav{float:none;right:0;top:0}nav.theme.m li#plusNav.open>ul.l2{right:0;opacity:1}nav.theme.m li#plusNav>ul.l2{right:-9999px;left:auto;opacity:0;transition:opacity .25s, transform .25s, right 50ms 0.25s, top 50ms 0.25s, left 50ms 0.25s}nav.theme.m li#plusNav>ul.l2>li{display:none}nav.theme.m li#plusNav>ul.l2>li.move{display:block;visibility:visible}nav.theme.m ul.l1>li#plusNav.open>ul.l2,nav.theme.m li#plusNav ul.l2>li.open>ul.l3{transition:opacity .25s, transform .25s}nav.theme>ul{height:46px;line-height:46px;display:inline-block}nav.theme>ul>li>a{position:relative;z-index:10;white-space:nowrap}nav.theme>ul>li.open>ul.l2{left:0;top:100%;opacity:1;transform:translateY(0);padding-bottom:46px;transition:opacity 0.25s, transform .25s}nav.theme>ul:before{width:100%;height:46px;position:absolute;left:0;top:0;content:'';display:block;z-index:5}nav.theme ul{list-style:none;padding:0;margin:0}nav.theme ul li{float:left;position:relative;z-index:900}nav.theme ul li.open{z-index:1000}nav.theme ul li#plusNav{position:absolute;top:-9999px}nav.theme ul li#plusNav div[role="show-more-navigation"]{padding:0 25px;background:#000;line-height:46px;display:block;cursor:pointer;z-index:10;position:relative;transition:background 0.15s, color 0.15s}nav.theme ul li.hasChild a{padding-right:30px}nav.theme ul li.hasChild>a:after,nav.theme ul li div[role="show-more-navigation"]:after{content:'+';font-size:16px;top:-2px;right:15px;position:absolute;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;transition:transform .3s;transform-origin:50% 55%;z-index:1000}nav.theme ul li a{padding:0 20px;line-height:46px;display:block;text-decoration:none;transition:color 0.20s}nav.theme ul ul{width:250px;text-align:left;opacity:0;z-index:1000;position:absolute;left:-9999px;top:-9999px}nav.theme ul ul li{float:none}nav.theme ul ul li.hasChild>a:after{font-size:22px;top:4px}nav.theme ul ul li a{padding-top:10px;padding-bottom:10px;padding-left:25px;padding-right:20px;line-height:normal}nav.theme ul ul li ul{background:transparent}nav.theme ul ul li ul li a{padding-left:35px}nav.theme ul ul li ul ul li a{padding-left:45px}nav.theme ul ul li ul ul ul li a{padding-left:55px}nav.theme ul ul li ul ul ul ul li a{padding-left:65px}nav.theme ul ul li ul ul ul ul ul li a{padding-left:75px}nav.theme li.open>a:after,nav.theme li.open div[role="show-more-navigation"]:after{transform:rotate(45deg)}nav.theme ul.l2{transform:translateY(-5px);transition:opacity 0.25s, transform .25s, top 0s .25s, left 0s .25s}nav.theme ul.l2>li:last-child{padding-bottom:10px}nav.theme ul.l2 li.open>ul.l3,nav.theme ul.l2 li.open>ul.l4,nav.theme ul.l2 li.open>ul.l5,nav.theme ul.l2 li.open>ul.l6,nav.theme ul.l2 li.open>ul.l7{position:relative;left:0;top:0;opacity:1}nav.theme ul.l3{padding-bottom:10px}nav.theme ul.l3,nav.theme ul.l4,nav.theme ul.l5,nav.theme ul.l6,nav.theme ul.l7{transition:opacity 0.5s}body.hasScrolled header.theme{padding-top:46px}body.hasScrolled nav.theme{position:fixed;top:0 !important;height:auto;backface-visibilty:hidden;margin-top:-46px !important;transform:translateY(46px) !important;transition:transform 0.25s, height 0 .25s}body.hasScrolled nav.theme.overflow{overflow:hidden;overflow-y:visible}body.hasScrolled nav.theme.open{bottom:0 !important;transition:transform 0.25s, height 0}body.hasScrolled nav.theme.ncd-hide{transform:translateY(0) !important}body.hasScrolled nav.theme.stay{position:fixed;top:0 !important;margin-top:-46px !important;transform:translateY(46px) !important;transition:transform 0.25s, height 0 .25s}body.hasScrolled nav.theme.stay.ncd-hide{transform:translateY(46px) !important}#page-breadcrumb{line-height:normal;font-size:14px;width:100%;list-style:none !important;margin:0;padding:0;display:none;padding:0 0 3% 0;font-style:normal}#page-breadcrumb.visible{display:block}#page-breadcrumb a{text-decoration:none}#page-breadcrumb li{float:left;margin:0 15px 10px 0}#page-breadcrumb li+li+li>a{width:15px;display:inline-block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}#page-breadcrumb li+li:before{border-style:solid;border-width:2px 2px 0 0;content:"";font-weight:400;display:inline-block;height:0.35em;position:relative;top:0.37em;vertical-align:top;width:0.35em;left:-9px;transform:rotate(45deg)}#page-breadcrumb li:last-child a{width:auto;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}section.theme{padding:7% 12%;position:relative;line-height:160%;background:#fff;min-height:100px}@media screen and (min-width: 850px){section.theme{margin-top:60px}}section.theme h1,section.theme h2,section.theme h3,section.theme h4,section.theme h5,section.theme h6{margin:0 0 3.5% 0 !important;font-weight:400}section.theme h6{font-weight:600}section.theme a[class^='social-']{font-size:1.6em;transition:color 300ms}section.theme blockquote{margin:0;padding:0 0 0 4%}section.theme blockquote p{display:inline}section.theme code{font-family:monospace, monospace;vertical-align:top;line-height:80%;font-size:0.8125em}section.theme .image-left{float:left;margin-right:20px}section.theme .image-right{float:right;margin-left:20px}section.theme img{max-width:100%;height:auto}section.theme img[alt*="site-feature"],section.theme img[alt*="page-background"]{display:none}footer.theme{font-size:0.95em;text-align:center;clear:both;padding:7% 12%;position:relative;line-height:160%}@media screen and (min-width: 850px){footer.theme{margin-bottom:7%}}footer.theme p{margin:0}footer.theme #myExtraContent2{padding-bottom:10px;text-align:left}footer.theme .copy{display:block;text-align:center}footer.theme #rw_email_contact:before{content:'';vertical-align:middle;display:inline-block;position:relative;height:5px;width:5px;border-radius:5px;margin:0 10px}footer.theme a[class^='social-']{font-size:30px;padding:12px;display:inline-block}.blog-entry{padding:0;border-bottom-style:solid;border-bottom-width:1px}.blog-entry+.blog-entry{padding:30px 0 0}.blog-entry h1.blog-entry-title{font-size:1.35em !important;line-height:normal}.blog-entry .blog-entry-date{padding-bottom:5%}.blog-entry .blog-entry-date a{padding:0;font-weight:bolder}.blog-entry .blog-entry-body{padding:0 0 30px}.blog-entry .blog-entry-body .blog-read-more{display:block;font-size:1em;padding:15px 0 0;font-style:italic;font-weight:bolder}.blog-entry .blog-entry-body a.blog-comment-link{padding:15px 0;display:block;clear:both}.blog-entry .blog-entry-body .blog-entry-tags{clear:both;word-wrap:break-word;margin-top:10px;padding-top:10px}.blog-entry .blog-entry-body .blog-entry-tags a{padding:0 3px}#blog-categories,#blog-archives,.blog-tag-cloud,#blog-rss-feeds{list-style:none;font-size:0.9em;padding:30px 0 !important;display:block;position:relative;border-bottom-style:solid;border-bottom-width:1px}#blog-categories:before,#blog-archives:before,.blog-tag-cloud:before,#blog-rss-feeds:before{font-size:1.1em;font-weight:bolder;display:block;padding-bottom:10px}#blog-categories a,#blog-archives a,.blog-tag-cloud a,#blog-rss-feeds a{padding:2px 0;display:block}#blog-rss-feeds{border-bottom:none}#blog-categories:before{content:"Categories"}#blog-archives:before{content:"Archives"}.blog-tag-cloud:before{content:"Tags"}#blog-rss-feeds:before{content:"Feeds"}.blog-category-link-enabled,.blog-archive-link-enabled,.blog-rss-link{display:block}#blog-categories br,#blog-archives br,#blog-rss-feeds br,.blog-category-link-disabled,.blog-archive-link-disabled,.blog-archive-month,.blog-archive-link{display:none}form>div>label{font-weight:normal;line-height:24px;font-size:0.85em;padding-bottom:4px}form>div .form-input-field{font-size:14px;padding:1.7%;display:inline-block;margin:0;width:96.6%;border-style:solid;border-width:2px;border-radius:3px;background:transparent;-webkit-appearance:none}form>div input.form-input-button[type="reset"],form>div input.form-input-button[type="submit"]{font-weight:normal;font-weight:bolder;font-size:0.9em;display:inline-block;padding:1.7% 3%;margin:0 20px 0 0;background:transparent;cursor:pointer;-webkit-appearance:none;border-radius:3px;border-style:solid;border-width:2px}form>div .form-input-field:focus{box-shadow:none;outline:none}@media screen and (850px){form>div .form-input-field{padding:3%;width:94%}form>div input.form-input-button[type="reset"],form>div input.form-input-button[type="submit"]{padding:3%}}.album-title{padding:0 !important}.album-wrapper ul li p{font-size:0.8em !important}.movie-page-title{font-size:1.6em;font-weight:normal;line-height:18px;margin-left:3%;margin-right:3%;padding-bottom:1.5%}.movie-page-description{padding:10px 0 35px;margin-left:3%;margin-right:3%}.movie-thumbnail-frame{display:block;margin:0 5px 60px 0;float:left}.movie-thumbnail-frame{display:inline-block;text-align:center;position:relative;margin:0 8px 60px;width:100%;height:auto}.movie-thumbnail-frame img{margin:0;position:relative;z-index:10;box-shadow:0px 1px 4px #999;width:100%;height:100%;display:inline;perspective:1000;backface-visibility:hidden;transition:all 600ms cubic-bezier(0.215, 0.61, 0.355, 1)}.movie-thumbnail-frame .movie-thumbnail-caption{padding-top:3px;font-size:1em;float:left}.movie-background{background-image:none}.movie-background .movie-title{margin:0 30px;padding-top:20px;letter-spacing:1px;font-size:12px}.movie-background .movie-frame{text-align:center;padding-top:20px}.filesharing-item{padding-bottom:30px}.filesharing-item+.filesharing-item{padding-top:30px;border-top-width:1px;border-top-style:solid}.filesharing-item .filesharing-item-title{margin:0 0 15px}.filesharing-item .filesharing-item-title a{font-size:1.05em;font-weight:bolder;border:none;display:block;background:transparent}.filesharing-item .filesharing-item-title a:hover{box-shadow:none;text-decoration:none !important}.filesharing-item .filesharing-item-title .filesharing-item-description{padding-bottom:10px}ul.tree,ul.tree ul{padding:0 0 0 20px}ul.tree li,ul.tree ul li{padding:7px 0 0 0}</ul></style></head><body><header class="theme"><div id="site-info"><div class="wrapper cf"><h1>Zoom Social</h1><span>World's Fastest Location-Based Big Data Engine</span></div></div><nav class="theme cf"><ul><li><a href="../" rel="">home</a></li><li class="current"><a href="./" rel="">contact</a></li></ul></nav><div id="site-feature"><div class="inner-wrapper"><div id="site-logo"><a href=""></a></div><div id="thisImg"><img src="../rw_common/images/locus-social-logo-big.png"><div id="gradient-effect"></div></div></div></div></header><div class="wrapper" id="container"><section class="theme main cf">
<div class="message-text"><?php echo $_SESSION['formMessage']; unset($_SESSION['formMessage']); ?></div><br />

<form class="rw-contact-form" action="./files/mailer.php" method="post" enctype="multipart/form-data">
	 <div>
		<label>Your Name</label> *<br />
		<input class="form-input-field" type="text" value="<?php echo check('element0'); ?>" name="form[element0]" size="40"/><br /><br />

		<label>Your Email</label> *<br />
		<input class="form-input-field" type="text" value="<?php echo check('element1'); ?>" name="form[element1]" size="40"/><br /><br />

		<label>Subject</label> *<br />
		<input class="form-input-field" type="text" value="<?php echo check('element2'); ?>" name="form[element2]" size="40"/><br /><br />

		<label>Message</label> *<br />
		<textarea class="form-input-field" name="form[element3]" rows="8" cols="38"><?php echo check('element3'); ?></textarea><br /><br />

		<div style="display: none;">
			<label>Spam Protection: Please don't fill this in:</label>
			<textarea name="comment" rows="1" cols="1"></textarea>
		</div>
		<input type="hidden" name="form_token" value="<?php echo $security_token; ?>" />
		
		<input class="form-input-button" type="submit" name="submitButton" value="Submit" />
	</div>
</form>

<br />
<div class="form-footer"><?php echo $_SESSION['formFooter']; unset($_SESSION['formFooter']); ?></div><br />

<?php unset($_SESSION['form']); ?>
</section><footer class="theme"><div id="extraContainer2"></div><div class="copy">&copy; 2017 Locus Social</div></footer></div><div id="nav-config"></div><div id="layout-config"></div>
  <link rel="stylesheet" type="text/css" media="all" href="../rw_common/themes/forward/consolidated-1.css?rwcache=522861671" />
		
  <link rel="stylesheet" href="https://d1azc1qln24ryf.cloudfront.net/47089/SocialIconsNCD/style-cf.css?0">
  <!--[if IE 9]><style type="text/css">#gradient-effect{display: none}</style><!-- <![endif]-->
  
  <script>window.jQuery || document.write('<script src="../rw_common/themes/forward/assets/js/jquery-2.1.1.min.js">\x3C/script>')</script>
<script defer>
      var ncdVars = function (){ 
          pullup = $('#layout-config').css('padding-top') == '1px';
          navVisible = $('#nav-config').css('padding-right') == '1px';
          navMouseClose = $('#nav-config').css('padding-bottom') == '1px';
          navSubParentDisable = $('#nav-config').css('padding-left') == '1px';
      };
  </script>
  <script src="../rw_common/themes/forward/assets/js/javascript.js" defer></script></body></html>
