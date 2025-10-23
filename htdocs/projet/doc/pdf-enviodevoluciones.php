<?php
// Archivo principal main
require '../../main.inc.php';
// Libreria TCPDF
require_once TCPDF_PATH.'tcpdf.php';
// ID a buscar por GET
$id = GETPOST('id');
$desde = urldecode(GETPOST('desde'));
$hasta = urldecode(GETPOST('hasta'));

$thirdparty = new Societe($db);
$thirdparty->fetch(1);

// Get current proyect from session
session_start();
$proyecto = $_SESSION['proyecto'];

if (array_key_exists('productos', $proyecto)) {
  foreach ($proyecto['productos'] as $producto => $v) {
    foreach ($v['envios'] as $envio => $valor) {
      $flag = false;
      if($valor['date_devolucion'] != ""){
        $devol_explode = explode(" ", $valor['date_devolucion']);
        $flag = true;
      }

      $desde_rplc = explode('/',$desde);
      $hasta_rplc = explode('/',$hasta);
      $devol_rplc = explode('/',$devol_explode[0]);
      
      if(($devol_rplc[0] >= $desde_rplc[2] && $devol_rplc[0] <= $hasta_rplc[2]//year
          && $devol_rplc[1] >= $desde_rplc[1] && $devol_rplc[1] <= $hasta_rplc[1]//month
            &&  $devol_rplc[2] >= $desde_rplc[0] && $devol_rplc[2] <= $hasta_rplc[0]//day
              && $flag) || ($desde=="" && $hasta == ""))
      { 
        $productos.='
        <tr nobr="true">
          <td  style="overflow: hidden; white-space: nowrap; width: 13%;  text-align:left;" class="ref-col">
            '.$valor['date_devolucion'].'
          </td>
          <td  style="overflow: hidden; white-space: nowrap; width: 10%;  text-align:left;" class="ref-col">
            '.$valor['ref_commande'].'
          </td>
          <td  style="overflow: hidden; white-space: nowrap; width: 10%;  text-align:left;" class="bold col">
            '.$producto.'
          </td>
          <td  style="overflow: hidden; white-space: nowrap; width: 10%;  text-align:left;" class="ref-col">
            '.$envio.'
          </td>
          <td  style="overflow: hidden; white-space: nowrap; width: 37%;  text-align:left;" class="desc-col">
            '.$valor['desc'].'
          </td>
          <td  style="overflow: hidden; white-space: nowrap; width: 10%;  text-align:center;" class="num col">
            '.$valor['qty_sent'].'
          </td>
          <td  style="overflow: hidden; white-space: nowrap; width: 10%;  text-align:center;" class="num col">
            '.$valor['qty_dev'].'
          </td>
        </tr>
        ';
      }
    }
  }
} else {
  $productos.='
  <tr nobr="true">
    <td  style="overflow: hidden; white-space: nowrap; width: 13%;" class="ref-col">
    </td>
    <td  style="overflow: hidden; white-space: nowrap; width: 10%;" class="ref-col">
    </td>  
    <td  style="overflow: hidden; white-space: nowrap; width: 10%;" class="bold col">
    </td>
    <td  style="overflow: hidden; white-space: nowrap; width: 10%;" class="ref-col">
    </td>
    <td  style="overflow: hidden; white-space: nowrap; width: 37%;" class="desc-col">
    </td>
    <td  style="overflow: hidden; white-space: nowrap; width: 10%;" class="num col">
    </td>
    <td  style="overflow: hidden; white-space: nowrap; width: 10%;" class="num col">
    </td>
  </tr>
  <h3 class="sin-envios">No hay envíos con devolución.</h3>
  ';
}


// HTML del pdf
/*──────────────────────────────────────────────────────────────────────*/
$header = '
<header>
  <br>
  <h3 class="proyecto blue">Nota de entrega con Devolución</h3>
  <h5 class="proyecto blue">Desde '.$desde.' - Hasta '.$hasta.'</h4>
  <h5 class="proyecto bold">Proyecto: '.$proyecto['ref_proyecto'].'</h4>
  <h5 class="proyecto blue">Código cliente: '.$proyecto['code_client'].'</h4>
</header>
';

$companycards = '
  <section>
  <table>
    <thead>
      <tr>
        <!-- <th style="width:1px;"></th>
        <th class="header-title">De:</th>
        <th class="spacer"></th>
        <th style="width:1px;"></th> -->
        <th class="header-title">Dirección de envío:</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="gray-spacer"></td>
        <td class="gray-block">
          <h4 class="bold">'.$proyecto['nom'].'</h4>
          <h5>'.$proyecto['address'].'</h5>
          <h5>'.$proyecto['zip'].' '.$proyecto['town'].'</h5>
          <h5>'.$proyecto['phone'].'</h5>
          <h5>Correo: '.$proyecto['email'].'</h5>
          <br>
        </td>
      </tr>
    </tbody>
  </table>
  <br>
  </section>
';

$tablaenvios = '
<body style="font-size: 6px;">
  <table style="table-layout: fixed; width: 100%;"  class="product-table" >
    <thead>
      <tr>
        <th  style="overflow: hidden; white-space: nowrap; width: 13%; text-align:center;" class="ref-col">Fecha de Devolución</th>
        <th  style="overflow: hidden; white-space: nowrap; width: 10%; text-align:center;"  class="ref-col">Ref. de Pedido</th>
        <th  style="overflow: hidden; white-space: nowrap; width: 10%; text-align:center;"  class="bold col">Ref. Envio</th>
        <th  style="overflow: hidden; white-space: nowrap; width: 10%; text-align:center;"  class="ref-col">Referencia</th>
        <th  style="overflow: hidden; white-space: nowrap; width: 37%; text-align:center;"  class="desc-col">Descripción</th>
        <th  style="overflow: hidden; white-space: nowrap; width: 10%; text-align:center;"  class="num col">Cant. Enviada</th>
        <th  style="overflow: hidden; white-space: nowrap; width: 10%; text-align:center;"  class="num col">Cant. Devuelta</th>
      </tr>
    </thead>
    <tbody>
        '.
        $productos
        .'
    </tbody>
  </table>
</body>
';

$estilos = '
<style>
h4,h5 {
  font-weight: regular;
}

.bold {
  font-weight: bold;
}

.blue {
  color: #00002f;
}

.proyecto {
  text-align: right;
  line-height: 5px;
}

.header-title {
  width: 45%;
}

.spacer {
  width: 5%;
}

.gray-spacer {
  background-color: #e7e7e7;
  width: 10px;
}

.gray-block {
  // width: 45%;
  width: auto;
  text-align: left;
  background-color: #e7e7e7;
}

.white-spacer {
  width: 10px;
  border-left: 0.5px solid #cfcfcf;
  border-top: 0.5px solid #cfcfcf;
  border-bottom: 0.5px solid #cfcfcf;
}

.white-block {
  width: auto;
  border-right: 0.5px solid #cfcfcf;
  border-top: 0.5px solid #cfcfcf;
  border-bottom: 0.5px solid #cfcfcf;
}

.product-table {
  border-collapse: collapse;
  border: 0.5px solid #cfcfcf;
}

.product-table th {
  border: 0.5px solid #cfcfcf;
}

.product-table td {
  border-left: 0.5px solid #cfcfcf;
  border-right: 0.5px solid #cfcfcf;
}

.col {
  width: 15%;
}

.num {
  text-align: center;
  width: 10%;
}

.desc-col {
  width: 45%;
}

.ref-col {
  width: 20%;
}

.sin-envios {
  text-align: center;
}
</style>
';
// /*──────────────────────────────────────────────────────────────────────*/

// Crear nuevo documento pdf
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->setFooterMargin(PDF_MARGIN_FOOTER);

// Saltos de página automáticos
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Especificar tipo de fuente
$pdf->SetFont('helvetica', '', 10);

// Añadir página
$pdf->AddPage('P', 'A4');

// Logo
$pdf->Image(DOL_DOCUMENT_ROOT.'/product/custom/Logo_LION-SS.png', 10, 12, 32.00);

// Escribir contenido
$pdf->setCellPaddings(0, 0, 0, 1);
$pdf->writeHTML($estilos.$header, true, false, true, false, '');

$pdf->setCellPaddings(0, 0, 0, 0);
$pdf->writeHTML($estilos.$companycards, true, false, true, false, '');

$pdf->setCellPaddings(0, 1, 0, 1);
$pdf->writeHTML($estilos.$tablaenvios, true, false, true, false, '');


$pdf->lastPage();
// Mostrar contenido del archivo (visible desde navegador)
$pdf->Output('Nota_devolucion.pdf', 'I');

?>
