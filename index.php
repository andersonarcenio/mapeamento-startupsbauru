<?php
if(!file_exists('include/db.php')) require_once('installer.php');
include_once "header.php";
?>
 
<!DOCTYPE html>
<html>
  <head>
    <!--
    This site was based on the Represent.LA project by:
    - Alex Benzer (@abenzer)
    - Tara Tiger Brown (@tara)
    - Sean Bonner (@seanbonner)

    Create a map for your startup community!
    https://github.com/abenzer/represent-map
    -->
    <title><?= $title_tag ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta charset="UTF-8">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans+Condensed:700|Open+Sans:400,700' rel='stylesheet' type='text/css'>
    <link href="./bootstrap/css/bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="./bootstrap/css/bootstrap-responsive.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="map.css?nocache=289671982568" type="text/css" />
    <link rel="stylesheet" media="only screen and (max-device-width: 480px)" href="mobile.css" type="text/css" />
    <script src="./scripts/jquery-1.7.1.js" type="text/javascript" charset="utf-8"></script>
    <script src="./bootstrap/js/bootstrap.js" type="text/javascript" charset="utf-8"></script>
    <script src="./bootstrap/js/bootstrap-typeahead.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
    <script type="text/javascript" src="./scripts/label.js"></script>

    <link rel="apple-touch-icon" sizes="57x57" href="./images/fav/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="./images/fav/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="./images/fav/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="./images/fav/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="./images/fav/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="./images/fav/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="./images/fav/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="./images/fav/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="./images/fav/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="./images/fav/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="./images/fav/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="./images/fav/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./images/fav/favicon-16x16.png">
    <link rel="manifest" href="./images/fav/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="./images/fav/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">

    <script type="text/javascript">
      var map;
      var infowindow = null;
      var gmarkers = [];
      var markerTitles =[];
      var highestZIndex = 0;
      var agent = "default";
      var zoomControl = true;


      // detect browser agent
      $(document).ready(function(){
        if(navigator.userAgent.toLowerCase().indexOf("iphone") > -1 || navigator.userAgent.toLowerCase().indexOf("ipod") > -1) {
          agent = "iphone";
          zoomControl = false;
        }
        if(navigator.userAgent.toLowerCase().indexOf("ipad") > -1) {
          agent = "ipad";
          zoomControl = false;
        }
      });


      // resize marker list onload/resize
      $(document).ready(function(){
        resizeList()
      });
      $(window).resize(function() {
        resizeList();
      });

      // resize marker list to fit window
      function resizeList() {
        newHeight = $('html').height() - $('#topbar').height();
        $('#list').css('height', newHeight + "px");
        $('#menu').css('margin-top', $('#topbar').height());
      }


      // initialize map
      function initialize() {
        // set map styles
        var mapStyles = [
         {
            featureType: "road",
            elementType: "geometry",
            stylers: [
              { hue: "#8800ff" },
              { lightness: 100 }
            ]
          },{
            featureType: "road",
            stylers: [
              { visibility: "on" },
              { hue: "#91ff00" },
              { saturation: -62 },
              { gamma: 1.98 },
              { lightness: 45 }
            ]
          },{
            featureType: "water",
            stylers: [
              { hue: "#005eff" },
              { gamma: 0.72 },
              { lightness: 42 }
            ]
          },{
            featureType: "transit.line",
            stylers: [
              { visibility: "off" }
            ]
          },{
            featureType: "administrative.locality",
            stylers: [
              { visibility: "on" }
            ]
          },{
            featureType: "administrative.neighborhood",
            elementType: "geometry",
            stylers: [
              { visibility: "simplified" }
            ]
          },{
            featureType: "landscape",
            stylers: [
              { visibility: "on" },
              { gamma: 0.41 },
              { lightness: 46 }
            ]
          },{
            featureType: "administrative.neighborhood",
            elementType: "labels.text",
            stylers: [
              { visibility: "on" },
              { saturation: 33 },
              { lightness: 20 }
            ]
          }
        ];

        // set map options
        var myOptions = {
          zoom: 13,
          //minZoom: 10,
          center: new google.maps.LatLng(<?= $lat_lng ?>),
          mapTypeId: google.maps.MapTypeId.ROADMAP,
          streetViewControl: false,
          mapTypeControl: false,
          panControl: false,
          zoomControl: zoomControl,
          styles: mapStyles,
          zoomControlOptions: {
            style: google.maps.ZoomControlStyle.SMALL,
            position: google.maps.ControlPosition.LEFT_CENTER
          }
        };
        map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
        zoomLevel = map.getZoom();

        // prepare infowindow
        infowindow = new google.maps.InfoWindow({
          content: "holding..."
        });

        // only show marker labels if zoomed in
        google.maps.event.addListener(map, 'zoom_changed', function() {
          zoomLevel = map.getZoom();
          if(zoomLevel <= 15) {
            $(".marker_label").css("display", "none");
          } else {
            $(".marker_label").css("display", "inline");
          }
        });

        // markers array: name, type (icon), lat, long, description, uri, address
        markers = new Array();
        <?php
          $types = Array(
              Array('startup', 'Startups'),
              Array('accelerator','Aceleradoras'),
              Array('incubator', 'Incubadoras'),
              Array('coworking', 'Coworking'),
              Array('investor', 'Invetidores'),
              Array('service', 'Consultores'),
              Array('hackerspace', 'Startup Studio'),
              Array('event', 'Eventos'),
              );
          $marker_id = 0;
          foreach($types as $type) {
            $places = mysql_query("SELECT * FROM places WHERE approved='1' AND type='$type[0]' ORDER BY title");
            $places_total = mysql_num_rows($places);
            while($place = mysql_fetch_assoc($places)) {
              $place[title] = htmlspecialchars_decode(addslashes(htmlspecialchars($place[title])));
              $place[description] = str_replace(array("\n", "\t", "\r"), "", htmlspecialchars_decode(addslashes(htmlspecialchars($place[description]))));
              $place[uri] = addslashes(htmlspecialchars($place[uri]));
              $place[address] = htmlspecialchars_decode(addslashes(htmlspecialchars($place[address])));
              echo "
                markers.push(['".$place[title]."', '".$place[type]."', '".$place[lat]."', '".$place[lng]."', '".$place[description]."', '".$place[uri]."', '".$place[address]."']);
                markerTitles[".$marker_id."] = '".$place[title]."';
              ";
              $count[$place[type]]++;
              $marker_id++;
            }
          }
          if($show_events == true) {
            $place[type] = "event";
            $events = mysql_query("SELECT * FROM events WHERE start_date > ".time()." AND start_date < ".(time()+9676800)." ORDER BY id DESC");
            $events_total = mysql_num_rows($events);
            while($event = mysql_fetch_assoc($events)) {
              $event[title] = htmlspecialchars_decode(addslashes(htmlspecialchars($event[title])));
              $event[description] = htmlspecialchars_decode(addslashes(htmlspecialchars($event[description])));
              $event[uri] = addslashes(htmlspecialchars($event[uri]));
              $event[address] = htmlspecialchars_decode(addslashes(htmlspecialchars($event[address])));
              $event[start_date] = date("D, M j @ g:ia", $event[start_date]);
              echo "
                markers.push(['".$event[title]."', 'event', '".$event[lat]."', '".$event[lng]."', '".$event[start_date]."', '".$event[uri]."', '".$event[address]."']);
                markerTitles[".$marker_id."] = '".$event[title]."';
              ";
              $count[$place[type]]++;
              $marker_id++;
            }
          }
        ?>

        // add markers
        jQuery.each(markers, function(i, val) {
          infowindow = new google.maps.InfoWindow({
            content: ""
          });

          // offset latlong ever so slightly to prevent marker overlap
          rand_x = Math.random();
          rand_y = Math.random();
          val[2] = parseFloat(val[2]) + parseFloat(parseFloat(rand_x) / 6000);
          val[3] = parseFloat(val[3]) + parseFloat(parseFloat(rand_y) / 6000);

          // show smaller marker icons on mobile
          if(agent == "iphone") {
            var iconSize = new google.maps.Size(16,19);
          } else {
            iconSize = null;
          }

          // build this marker
          var markerImage = new google.maps.MarkerImage("./images/icons/"+val[1]+".png", null, null, null, iconSize);
          var marker = new google.maps.Marker({
            position: new google.maps.LatLng(val[2],val[3]),
            map: map,
            title: '',
            clickable: true,
            infoWindowHtml: '',
            zIndex: 10 + i,
            icon: markerImage
          });
          marker.type = val[1];
          gmarkers.push(marker);

          // add marker hover events (if not viewing on mobile)
          if(agent == "default") {
            google.maps.event.addListener(marker, "mouseover", function() {
              this.old_ZIndex = this.getZIndex();
              this.setZIndex(9999);
              $("#marker"+i).css("display", "inline");
              $("#marker"+i).css("z-index", "99999");
            });
            google.maps.event.addListener(marker, "mouseout", function() {
              if (this.old_ZIndex && zoomLevel <= 15) {
                this.setZIndex(this.old_ZIndex);
                $("#marker"+i).css("display", "none");
              }
            });
          }

          // format marker URI for display and linking
          var markerURI = val[5];
          if(markerURI.substr(0,7) != "http://") {
            markerURI = "http://" + markerURI;
          }
          var markerURI_short = markerURI.replace("http://", "");
          var markerURI_short = markerURI_short.replace("www.", "");

          // add marker click effects (open infowindow)
          google.maps.event.addListener(marker, 'click', function () {
            infowindow.setContent(
              "<div class='marker_title'>"+val[0]+"</div>"
              + "<div class='marker_uri'><a target='_blank' href='"+markerURI+"'>"+markerURI_short+"</a></div>"
              + "<div class='marker_desc'>"+val[4]+"</div>"
              + "<div class='marker_address'>"+val[6]+"</div>"
            );
            infowindow.open(map, this);
          });

          // add marker label
          var latLng = new google.maps.LatLng(val[2], val[3]);
          var label = new Label({
            map: map,
            id: i
          });
          label.bindTo('position', marker);
          label.set("text", val[0]);
          label.bindTo('visible', marker);
          label.bindTo('clickable', marker);
          label.bindTo('zIndex', marker);
        });


        // zoom to marker if selected in search typeahead list
        $('#search').typeahead({
          source: markerTitles,
          onselect: function(obj) {
            marker_id = jQuery.inArray(obj, markerTitles);
            if(marker_id > -1) {
              map.panTo(gmarkers[marker_id].getPosition());
              map.setZoom(15);
              google.maps.event.trigger(gmarkers[marker_id], 'click');
            }
            $("#search").val("");
          }
        });
      }


      // zoom to specific marker
      function goToMarker(marker_id) {
        if(marker_id) {
          map.panTo(gmarkers[marker_id].getPosition());
          map.setZoom(15);
          google.maps.event.trigger(gmarkers[marker_id], 'click');
        }
      }

      // toggle (hide/show) markers of a given type (on the map)
      function toggle(type) {
        if($('#filter_'+type).is('.inactive')) {
          show(type);
        } else {
          hide(type);
        }
      }

      // hide all markers of a given type
      function hide(type) {
        for (var i=0; i<gmarkers.length; i++) {
          if (gmarkers[i].type == type) {
            gmarkers[i].setVisible(false);
          }
        }
        $("#filter_"+type).addClass("inactive");
      }

      // show all markers of a given type
      function show(type) {
        for (var i=0; i<gmarkers.length; i++) {
          if (gmarkers[i].type == type) {
            gmarkers[i].setVisible(true);
          }
        }
        $("#filter_"+type).removeClass("inactive");
      }

      // toggle (hide/show) marker list of a given type
      function toggleList(type) {
        $("#list .list-"+type).toggle();
      }


      // hover on list item
      function markerListMouseOver(marker_id) {
        $("#marker"+marker_id).css("display", "inline");
      }
      function markerListMouseOut(marker_id) {
        $("#marker"+marker_id).css("display", "none");
      }

      google.maps.event.addDomListener(window, 'load', initialize);
    </script>

    <? echo $head_html; ?>
  </head>
  <body>

    <!-- display error overlay if something went wrong -->
    <?php echo $error; ?>

    <!-- facebook like button code -->
    <div id="fb-root"></div>
	<script>(function(d, s, id) {
	  var js, fjs = d.getElementsByTagName(s)[0];
	  if (d.getElementById(id)) return;
	  js = d.createElement(s); js.id = id;
	  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.5&appId=819613408060975";
	  fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));</script>

    <!-- google map -->
    <div id="map_canvas"></div>

    <!-- topbar -->
    <div class="topbar" id="topbar">
      <div class="wrapper">
        <div class="right">
          <div class="share">
          <a href="https://twitter.com/share" class="twitter-share-button" data-url="<?= $domain ?>" data-text="<?= $twitter['share_text'] ?>" data-via="<?= $twitter['username'] ?>" data-count="none">Tweet</a>
            <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
            <div class="fb-like" data-href="https://www.facebook.com/startupsbauru" data-layout="button_count" data-action="like" data-show-faces="false" data-share="true"></div>
          </div>
        </div>
        <div class="left">
          <div class="logo">
            <a href="./">
              <img src="images/logo.png" width="202" alt="" />
            </a>
          </div>
          <div class="buttons">
            <a href="#modal_info" class="btn btn-large btn-info" data-toggle="modal"><i class="icon-info-sign icon-white"></i>Sobre este mapa</a>
            <?php if($sg_enabled) { ?>
              <a href="#modal_add_choose" class="btn btn-large btn-success" data-toggle="modal"><i class="icon-plus-sign icon-white"></i>Adicione aqui</a>
            <? } else { ?>
              <a href="#modal_add" class="btn btn-large btn-success" data-toggle="modal"><i class="icon-plus-sign icon-white"></i>Adicione aqui</a>
            <? } ?>
          </div>
          <div class="search">
            <input type="text" name="search" id="search" placeholder="Buscar por empresas..." data-provide="typeahead" autocomplete="off" />
          </div>
        </div>
      </div>
    </div>

    <!-- right-side gutter -->
    <div class="menu" id="menu">
      <ul class="list" id="list">
        <?php
          $types = Array(
              Array('startup', 'Startups'),
              Array('accelerator','Aceleradoras'),
              Array('incubator', 'Incubadoras'),
              Array('coworking', 'Coworking'),
              Array('investor', 'Investidores'),
              Array('service', 'Consultores'),
              Array('hackerspace', 'Startup Studio')
              );
          if($show_events == true) {
            // $types[] = Array('event', 'Events');
          }
          $marker_id = 0;
          foreach($types as $type) {
            if($type[0] != "event") {
              $markers = mysql_query("SELECT * FROM places WHERE approved='1' AND type='$type[0]' ORDER BY title");
            } else {
              $markers = mysql_query("SELECT * FROM events WHERE start_date > ".time()." AND start_date < ".(time()+4838400)." ORDER BY id DESC");
            }
            $markers_total = mysql_num_rows($markers);
            echo "
              <li class='category'>
                <div class='category_item'>
                  <div class='category_toggle' onClick=\"toggle('$type[0]')\" id='filter_$type[0]'></div>
                  <a href='#' onClick=\"toggleList('$type[0]');\" class='category_info'><img src='./images/icons/$type[0].png' alt='' />$type[1]<span class='total'> ($markers_total)</span></a>
                </div>
                <ul class='list-items list-$type[0]'>
            ";
            while($marker = mysql_fetch_assoc($markers)) {
              echo "
                  <li class='".$marker[type]."'>
                    <a href='#' onMouseOver=\"markerListMouseOver('".$marker_id."')\" onMouseOut=\"markerListMouseOut('".$marker_id."')\" onClick=\"goToMarker('".$marker_id."');\">".$marker[title]."</a>
                  </li>
              ";
              $marker_id++;
            }
            echo "
                </ul>
              </li>
            ";
          }
        ?>
        <li class="blurb"><?= $blurb ?></li>
        <li class="attribution">
          <!-- per our license, you may not remove this line -->
          <?=$attribution?>
        </li>
      </ul>
    </div>

    <!-- more info modal -->
    <div class="modal hide" id="modal_info">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Sobre este mapa</h3>
      </div>
      <div class="modal-body">
        <p>
          Nós construímos este mapa para se conectar e promover a comunidade startup de tecnologia de Bauru e Região. Necessitamos de sua ajuda para manter o mapa atualizado. Se você não ver a sua empresa, por favor 
          
          <?php if($sg_enabled) { ?>
            <a href="#modal_add_choose" data-toggle="modal" data-dismiss="modal">adicione aqui</a>.
          <?php } else { ?>
            <a href="#modal_add" data-toggle="modal" data-dismiss="modal">adicione aqui</a>.
          <?php } ?>
          Vamos lá galera !
        </p>
        <!-- <p>
        Dúvidas? Feedback? Conecte-se conosco: <a href="http://www.twitter.com/<?= $twitter['username'] ?>" target="_blank">@<?= $twitter['username'] ?></a>
        </p> -->
      </div>
      <div class="modal-footer">
        <a href="#" class="btn" data-dismiss="modal" style="float: right;">Close</a>
      </div>
    </div>


    <!-- add something modal -->
    <div class="modal hide" id="modal_add">
      <form action="add.php" id="modal_addform" class="form-horizontal">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">×</button>
          <h3>Adicione aqui!</h3>
        </div>
        <div class="modal-body">
          <div id="result"></div>
          <fieldset>
            <div class="control-group">
              <label class="control-label" for="add_owner_name">Seu nome</label>
              <div class="controls">
                <input type="text" class="input-xlarge" name="owner_name" id="add_owner_name" maxlength="100">
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_owner_email">Seu email</label>
              <div class="controls">
                <input type="text" class="input-xlarge" name="owner_email" id="add_owner_email" maxlength="100">
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_title">Empresa</label>
              <div class="controls">
                <input type="text" class="input-xlarge" name="title" id="add_title" maxlength="100" autocomplete="off">
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="input01">Categoria</label>
              <div class="controls">
                <select name="type" id="add_type" class="input-xlarge">
                  <option value="startup">Startup</option>
                  <option value="accelerator">Aceleradora</option>
                  <option value="incubator">Incubadora</option>
                  <option value="coworking">Coworking</option>
                  <option value="investor">Investidor</option>
                  <option value="service">Consultor</option>
                  <option value="hackerspace">Startup Studio </option>
                </select>
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_address">Endereço</label>
              <div class="controls">
                <input type="text" class="input-xlarge" name="address" id="add_address">
                <p class="help-block">
                  Necessário ser seu <b>endereço completo (incluindo cidade e CEP)</b>.
                  Funciona através do Google Maps
                </p>
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_uri">URL Site</label>
              <div class="controls">
                <input type="text" class="input-xlarge" id="add_uri" name="uri" placeholder="http://">
                <p class="help-block">
                  Deve ser sua URL completa sem a barra final, e.x. "http://www.startupsbauru.com.br"
                </p>
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_description">Descrição</label>
              <div class="controls">
                <input type="text" class="input-xlarge" id="add_description" name="description" maxlength="150">
                <p class="help-block">
                  Breve, breve descrição. Qual é o seu produto? Que problema você resolve? Max 150 caracteres.
                </p>
              </div>
            </div>
          </fieldset>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Enviar para aprovação</button>
          <a href="#" class="btn" data-dismiss="modal" style="float: right;">Fechar</a>
        </div>
      </form>
    </div>
    <script>
      // add modal form submit
      $("#modal_addform").submit(function(event) {
        event.preventDefault();
        // get values
        var $form = $( this ),
            owner_name = $form.find( '#add_owner_name' ).val(),
            owner_email = $form.find( '#add_owner_email' ).val(),
            title = $form.find( '#add_title' ).val(),
            type = $form.find( '#add_type' ).val(),
            address = $form.find( '#add_address' ).val(),
            uri = $form.find( '#add_uri' ).val(),
            description = $form.find( '#add_description' ).val(),
            url = $form.attr( 'action' );

        // send data and get results
        $.post( url, { owner_name: owner_name, owner_email: owner_email, title: title, type: type, address: address, uri: uri, description: description },
          function( data ) {
            var content = $( data ).find( '#content' );

            // if submission was successful, show info alert
            if(data == "success") {
              $("#modal_addform #result").html("Nós recebemos seu envio e ele será colocado para aprovação em breve. Obrigado");
              $("#modal_addform #result").addClass("alert alert-info");
              $("#modal_addform p").css("display", "none");
              $("#modal_addform fieldset").css("display", "none");
              $("#modal_addform .btn-primary").css("display", "none");

            // if submission failed, show error
            } else {
              $("#modal_addform #result").html(data);
              $("#modal_addform #result").addClass("alert alert-danger");
            }
          }
        );
      });
    </script>
  </body>
</html>
