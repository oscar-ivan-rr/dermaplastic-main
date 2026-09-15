<?php
require '../../master.inc.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="./style.css" />
    <link rel="stylesheet" href="<?= DOL_URL_ROOT . '/includes/jquery/plugins/jnotify/jquery.jnotify.min.js';?>"/>
    <link rel="icon" type="image/x-icon" href="<?= DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/'.$mysoc->logo) ?>">
    <title>Autofacturación</title>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-lg  p-3 mb-5 rounded-bottom">
    <a class="navbar-brand" href="<?= DOL_URL_ROOT . '/custom/autofactura_ticket/index.php'; ?>">
        <img style="width:80px;" src="<?= DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/'.$mysoc->logo) ?>" class="d-inline-block align-top rounded" alt="">
    </a>
    <!-- <?= var_dump($mysoc) ?> -->
    <div class="collapse navbar-collapse" id="navbarText">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item active">
                <a class="nav-link" href="<?= DOL_URL_ROOT . '/custom/autofactura_ticket/index.php'; ?>"><h5>Autofactura</h5> <span class="sr-only">(current)</span></a>
            </li>
        </ul>
    </div>
</nav>
<!--/.Navbar-->
<br>
<div class="container shadow-lg p-3 mb-5 bg-white rounded" name="resultado" id="resultado">
    <div class="row">
        <div class="col-12">
            <form name="form_busqueda" id="form_busqueda">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0"><i class="fa fa-search"></i> Buscar Ticket para Facturar</h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6 mx-auto">
                                <div class="form-group">
                                    <label for="folio_ticket" class="font-weight-bold"><i class="fa fa-ticket-alt"></i> Folio del Ticket</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-file-invoice"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="folio_ticket" id="folio_ticket" value="" placeholder="Ingrese el folio" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 mx-auto">
                                <div class="form-group">
                                    <label for="monto" class="font-weight-bold"><i class="fa fa-dollar-sign"></i> Monto</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" class="form-control" name="monto" id="monto" value="" placeholder="Ingrese el monto exacto" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row justify-content-center mb-4">
                            <div class="col-md-6">
                                <div id="msg_noresult" class="text-danger text-center font-weight-bold"></div>
                            </div>
                        </div>
                        
                        <div class="row justify-content-center">
                            <div class="col-md-6 text-center">
                                <button type="button" class="btn btn-primary btn-lg px-5" onclick="busquedaTicket()">
                                    <i class="fa fa-search mr-2"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="container border border-primary rounded"></div>

</body>
<!-- Footer -->
<footer class="page-footer font-small blue">
    <div class="text-center py-3">
        <span>Para mas información, contacta al equipo de Dermaglobal al <i><b>449-720-6639</b></i></span>
    </div>

</footer>
<!-- Footer -->

<!-- Styles -->
<style>
    #search_button{
        cursor: pointer;
    }

</style>

<script type="text/javascript" src="<?= DOL_URL_ROOT . '/includes/jquery/js/jquery.min.js'; ?>"></script>
<script src="<?= DOL_URL_ROOT . '/includes/jquery/plugins/jnotify/jquery.jnotify.min.js';?>"></script>
<script type="text/javascript">

    function busquedaTicket(){
        let url = '<?= DOL_URL_ROOT.'/custom/autofactura_ticket/searchTicket.php'; ?>';
        let folio_ticket = $('#folio_ticket').val();
        let monto = $('#monto').val();
        if (folio_ticket === ''){
            let msg = document.getElementById('msg_noresult');
            if(msg) {
                msg.innerHTML = '<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Por favor ingrese el folio del ticket</div>';
            }
            return;
        }
        if (monto === '' || monto === '0' || parseFloat(monto) <= 0){
            let msg = document.getElementById('msg_noresult');
            if(msg) {
                msg.innerHTML = '<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Por favor ingrese un monto válido</div>';
            }
            return;
        }
        
        let data = {
            folio_ticket: folio_ticket,
            monto : monto
        }
        
        let contenedor = document.getElementById('resultado');
        let msg = document.getElementById('msg_noresult');
        
        if(!msg && contenedor) {
            contenedor.innerHTML += '<div id="msg_noresult" class="d-none"></div>';
            msg = document.getElementById('msg_noresult');
        }
        
        contenedor.innerHTML = `
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center py-5">
                    <div class="spinner-grow text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <div class="mb-1">
                        <h5 class="font-weight-normal text-muted">Buscando Ticket ${folio_ticket}</h5>
                    </div>
                    <p class="text-muted mb-0">Por favor espere mientras procesamos su solicitud...</p>
                    <div id="msg_noresult" class="d-none"></div>
                </div>
            </div>
        `;
        
        $.ajax({
            type: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function(response) {
                if(response.resultado === '1'){
                    let out = `
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-success text-white py-3">
                            <h4 class="mb-0"><i class="fa fa-check-circle"></i> Ticket Encontrado</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 border-left border-success">
                                        <div class="card-body">
                                            <h5 class="card-title text-success"><i class="fa fa-ticket-alt"></i> Información del Ticket</h5>
                                            <div class="form-group">
                                                <label class="font-weight-bold">Folio de Ticket</label>
                                                <input type="text" class="form-control" value="${response.folio_factura}" disabled/>
                                            </div>
                                            <div class="form-group mb-0">
                                                <label class="font-weight-bold">Monto</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                    <input type="number" class="form-control" name="monto" id="monto" value="${monto}" disabled>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 border-left border-primary">
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title text-primary"><i class="fa fa-file-invoice"></i> Facturación</h5>
                                            <p class="text-muted mb-3">Puede editar los datos para facturar este ticket.</p>
                                            <div id="msg_noresult" class="text-danger mb-3"></div>
                                            <div class="mt-auto text-center">
                                                <button type="button" class="btn btn-primary btn-lg" onclick="busquedaFactura()">
                                                    <i class="fa fa-edit mr-2"></i> Editar Datos de Facturación
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="clave_factura" id="clave_factura" value="${response.folio_factura}"/>
                        </div>
                    </div>`;
                    
                    contenedor.innerHTML = out;
                }
                else if (response.resultado === '2'){
                    let out = `
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-info text-white py-3">
                            <h4 class="mb-0"><i class="fa fa-info-circle"></i> Ticket Encontrado - Sin Factura Asociada</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 border-left border-info">
                                        <div class="card-body">
                                            <h5 class="card-title text-info"><i class="fa fa-ticket-alt"></i> Información del Ticket</h5>
                                            <div class="form-group">
                                                <label class="font-weight-bold">Folio de Ticket</label>
                                                <input type="text" class="form-control" value="${response.folio_ticket}" disabled/>
                                            </div>
                                            <div class="form-group mb-0">
                                                <label class="font-weight-bold">Monto</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                    <input type="number" class="form-control" value="${monto}" disabled>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 border-left border-primary">
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title text-primary"><i class="fa fa-file-invoice"></i> Facturación</h5>
                                            <p class="alert alert-info">
                                                <i class="fa fa-info-circle"></i> Este ticket no tiene factura asociada. Puede generar una ahora.
                                            </p>
                                            <div class="mt-auto text-center">
                                                <button type="button" class="btn btn-primary btn-lg" onclick="facturarTicket()">
                                                    <i class="fa fa-file-invoice-dollar mr-2"></i> Facturar Ticket
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="folio_ticket" id="folio_ticket" value="${response.folio_ticket}" />
                            <input type="hidden" name="monto" id="monto" value="${monto}" />
                        </div>
                    </div>`;
                        
                    contenedor.innerHTML = out;
                }
                else if (response.resultado === '3'){
                    if(msg) {
                        msg.innerHTML = '';
                    }
                    
                    contenedor.innerHTML = `
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-danger text-white py-3">
                            <h4 class="mb-0"><i class="fa fa-times-circle"></i> No se puede facturar</h4>
                        </div>
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="fa fa-exclamation-triangle fa-4x text-danger"></i>
                            </div>
                            <h5 class="mb-3">El ticket no ha sido pagado</h5>
                            <p class="text-muted mb-4">Este ticket no puede ser facturado porque no ha sido pagado.</p>
                            <button type="button" class="btn btn-outline-primary" onclick="cancelar()">
                                <i class="fa fa-arrow-left mr-2"></i> Volver
                            </button>
                        </div>
                    </div>`;
                }
                else if (response.resultado === '4'){
                    if(msg) {
                        msg.innerHTML = '';
                    }
                    
                    contenedor.innerHTML = `
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-danger text-white py-3">
                            <h4 class="mb-0"><i class="fa fa-calendar-times"></i> Fuera de Fecha</h4>
                        </div>
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="fa fa-clock fa-4x text-danger"></i>
                            </div>
                            <h5 class="mb-3">Fuera de fecha de timbrado</h5>
                            <p class="text-muted mb-4">Solo se puede facturar durante el mes de la venta. Si la venta fue el último día del mes, debe facturarse ese mismo día.</p>
                            ${response.fecha_limite ? `<p class="text-muted mb-4"><strong>Fecha límite:</strong> ${response.fecha_limite}</p>` : ''}
                            <button type="button" class="btn btn-outline-primary" onclick="cancelar()">
                                <i class="fa fa-arrow-left mr-2"></i> Volver
                            </button>
                        </div>
                    </div>`;
                }
                else{
                    if(msg) {
                        msg.innerHTML = '';
                    }
                    
                    contenedor.innerHTML = `
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-warning text-white py-3">
                            <h4 class="mb-0"><i class="fa fa-search"></i> No Encontrado</h4>
                        </div>
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="fa fa-search fa-4x text-warning"></i>
                            </div>
                            <h5 class="mb-3">No se encontraron coincidencias</h5>
                            <p class="text-muted mb-4">Verifique que el folio y monto sean correctos.</p>
                            <button type="button" class="btn btn-outline-primary" onclick="cancelar()">
                                <i class="fa fa-arrow-left mr-2"></i> Volver
                            </button>
                        </div>
                    </div>`;
                }
            }
        });
    }

    function facturarTicket() {
        let url = '<?= DOL_URL_ROOT.'/custom/autofactura_ticket/facturar_ticket.php'; ?>';
        let folio_ticket = $('#folio_ticket').val();
        let monto = $('#monto').val();
        if (folio_ticket === ''){
            let msg = document.getElementById('msg_noresult');
            if(msg) {
                msg.innerHTML = '<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Por favor ingrese el folio del ticket</div>';
            } else {
                contenedor.innerHTML += '<div id="msg_noresult" class="d-none"></div>';
                msg = document.getElementById('msg_noresult');
            }
            return;
        }
        let data = {
            folio_ticket: folio_ticket,
        }
        let contenedor = document.getElementById('resultado');
        let msg = document.getElementById('msg_noresult');
        
        if(!msg && contenedor) {
            contenedor.innerHTML += '<div id="msg_noresult" class="d-none"></div>';
            msg = document.getElementById('msg_noresult');
        }
        
        contenedor.innerHTML = `
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center py-5">
                <div class="spinner-grow text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="sr-only">Procesando...</span>
                </div>
                <div class="mb-1">
                    <h5 class="font-weight-normal text-muted">Generando factura para ticket ${folio_ticket}</h5>
                </div>
                <p class="text-muted mb-0">Por favor espere mientras procesamos su solicitud...</p>
                <div id="msg_noresult" class="d-none"></div>
            </div>
        </div>
        `;
        
        $.ajax({
            type: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function (response) {
                if(response.resultado === '1'){
                    let out = `
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-success text-white py-3">
                            <h4 class="mb-0"><i class="fa fa-check-circle"></i> Factura Generada Correctamente</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 border-left border-success">
                                        <div class="card-body">
                                            <h5 class="card-title text-success"><i class="fa fa-ticket-alt"></i> Información del Ticket</h5>
                                            <div class="form-group">
                                                <label class="font-weight-bold">Folio de Ticket</label>
                                                <input type="text" class="form-control" value="${response.folio_ticket}" disabled/>
                                            </div>
                                            <div class="form-group mb-0">
                                                <label class="font-weight-bold">Folio de Factura</label>
                                                <input type="text" class="form-control" value="${response.folio_factura}" disabled/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 border-left border-primary">
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title text-primary"><i class="fa fa-file-invoice"></i> Facturación</h5>
                                            <p class="alert alert-success">
                                                <i class="fa fa-check-circle"></i> Se ha generado correctamente la factura. Puede verificar los datos ahora.
                                            </p>
                                            <div id="msg_noresult" class="text-danger mb-3"></div>
                                            <div class="mt-auto text-center">
                                                <button type="button" class="btn btn-primary btn-lg" onclick="busquedaFactura()">
                                                    <i class="fa fa-clipboard-check mr-2"></i> Verificar Datos
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="clave_factura" id="clave_factura" value="${response.folio_factura}"/>
                            <input type="hidden" name="monto" id="monto" value="${monto}"/>
                        </div>
                    </div>`;
                    
                    contenedor.innerHTML = out;
                }
                else if (response.resultado === '4') {
                    contenedor.innerHTML = `
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-danger text-white py-3">
                            <h4 class="mb-0"><i class="fa fa-calendar-times"></i> Fuera de Fecha</h4>
                        </div>
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="fa fa-clock fa-4x text-danger"></i>
                            </div>
                            <h5 class="mb-3">Fuera de fecha de timbrado</h5>
                            <p class="text-muted mb-4">Solo se puede facturar durante el mes de la venta. Si la venta fue el último día del mes, debe facturarse ese mismo día.</p>
                            ${response.fecha_limite ? `<p class="text-muted mb-4"><strong>Fecha límite:</strong> ${response.fecha_limite}</p>` : ''}
                            <button type="button" class="btn btn-outline-primary" onclick="cancelar()">
                                <i class="fa fa-arrow-left mr-2"></i> Volver
                            </button>
                        </div>
                    </div>`;
                }
                else if (response.resultado === '-1') {
                    contenedor.innerHTML = `
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-danger text-white py-3">
                            <h4 class="mb-0"><i class="fa fa-times-circle"></i> Error en la Facturación</h4>
                        </div>
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="fa fa-exclamation-triangle fa-4x text-danger"></i>
                            </div>
                            <h5 class="mb-3">No se pudo generar la factura</h5>
                            <p class="text-muted mb-4">Ocurrió un error al intentar generar la factura. Por favor intente nuevamente.</p>
                            <button type="button" class="btn btn-outline-primary" onclick="cancelar()">
                                <i class="fa fa-arrow-left mr-2"></i> Volver
                            </button>
                        </div>
                    </div>`;
                }
            },
            error: function (error) {
                console.log(error);
                contenedor.innerHTML = `
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-danger text-white py-3">
                        <h4 class="mb-0"><i class="fa fa-exclamation-triangle"></i> Error de Comunicación</h4>
                    </div>
                    <div class="card-body text-center py-4">
                        <div class="mb-3">
                            <i class="fa fa-server fa-4x text-danger"></i>
                        </div>
                        <h5 class="mb-3">Error en la comunicación con el servidor</h5>
                        <p class="text-muted mb-4">No se pudo establecer comunicación con el servidor. Por favor intente nuevamente más tarde.</p>
                        <button type="button" class="btn btn-outline-primary" onclick="cancelar()">
                            <i class="fa fa-arrow-left mr-2"></i> Volver
                        </button>
                    </div>
                </div>`;
            }
        });
    }

    function busquedaFactura(){
        let url = '<?= DOL_URL_ROOT.'/custom/autofactura_ticket/search.php'; ?>';
        let clave_factura = $('#clave_factura').val();
        let monto = $('#monto').val();
        if(monto === '' || clave_factura === ''){//Regresa si faltan valores, para ahorrar tiempo
            let msg = document.getElementById("msg_noresult");
            if(msg) {
                msg.innerHTML = 'No se encontraron resultados';
            }
            return;
        }
        let data = {
            clave_factura: clave_factura,
            monto : monto
        };
        let contenedor = document.getElementById('resultado');
        
        contenedor.innerHTML = `
            <div class="d-flex justify-content-center my-5">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
            </div>
            <div class="text-center mb-5">
                <p class="lead">Buscando información de factura...</p>
            </div>
            <div id="msg_noresult" class="d-none"></div>
        `;
        
        $.ajax({
            type:'POST',
            url: url,
            data: data,
            dataType: 'json',
            success:function(data){
                let out = '';
                let msg = document.getElementById("msg_noresult");
                if(data.error === undefined){
                    if(msg) {
                        msg.innerText = '';
                    }
                    contenedor.innerHTML = '';
                    contenedor.innerHTML += '<div id="msg_noresult" class="d-none"></div>';
                    
                    let resultado = data[0];
                    let uso_cfdi = data[1];
                    let domicilio = data[3];
                    let estados = data[4];
                    let sel_formpago = (data[5].length == 0 )? '' : data[5][0].formpagcfdi;
                    let sel_usocfdi = (data[5].length == 0)? '' : data[5][0].usocfdi;
                    let temp = String(data[2].param);
                    let metodo_pago = temp.split('"');
                    let monto = resultado.monto.toString();
                    let regex=/(\d*.\d{0,2})/;
                    monto = monto.match(regex)[0];
                    let fk_facture = resultado.rowid;
                    let ref = resultado.ref;
                    let fk_soc = resultado.fk_socid;
                    let nombre = resultado.nombre;
                    let rfc = resultado.rfc;
                    let correo = resultado.correo;
                    let cp = resultado.cp;
                    let fk_estado = resultado.fk_estado;
                    let municipio = domicilio.receptor_delompio;
                    let colonia = domicilio.receptor_colonia;
                    let calle = domicilio.receptor_calle;
                    let numero_int = domicilio.receptor_noint;
                    let numero_ext = domicilio.receptor_noext;
                    let regimen_fiscal = domicilio.regimen_f;
                    let regimen_label = domicilio.regimen_label;
                    let direccion = domicilio.tpdomicilio;
                    //--------Limpiamos variables-------------
                    let incompleto = 0;
                    if( ref === undefined || ref === '') {ref = ''; incompleto++;}
                    if( nombre === undefined || nombre === '') {nombre = 'Sin llenar'; incompleto++;}
                    if( sel_formpago === undefined || sel_formpago === '') {sel_formpago = 'Sin llenar'; incompleto++;}
                    if( correo === undefined ) {correo = 'Sin llenar'; incompleto++;}
                    if( sel_usocfdi === undefined || sel_usocfdi === '') {sel_usocfdi = 'Sin llenar'; incompleto++;}
                    if( regimen_fiscal === undefined || regimen_fiscal === '') {regimen_fiscal = 'Sin llenar'; incompleto++;}
                    if( regimen_label === undefined || regimen_label === '') {regimen_label = ''; incompleto++;}
                    if( rfc === undefined || rfc === '') {rfc = 'Sin llenar'; incompleto++;}
                    if( cp === null || cp === '') {cp = 'Sin llenar'; incompleto++;}

                    if( municipio === undefined || municipio === '') {municipio = 'Sin llenar'; incompleto++;}
                    if( colonia === undefined || colonia === '') {colonia = 'Sin llenar'; incompleto++;}
                    if( calle === undefined || calle === '') {calle = 'Sin llenar'; incompleto++;}
                    if( numero_int === undefined ) {numero_int = 'Sin llenar';}
                    if( numero_ext === undefined ) {numero_ext = 'Sin llenar'; incompleto++;}
                    if( fk_estado === undefined || fk_estado === '') {fk_estado = 'Sin llenar'; incompleto++;}
                    
                    // Buscar el nombre del estado
                    let estado_nombre = 'Sin llenar';
                    if(fk_estado != 0){
                        for(let x = 0; x < estados.length; x++){
                            let estado = estados[x];
                            if(fk_estado === estado.rowid){
                                estado_nombre = estado.nom;
                                break;
                            }
                        }
                    }
                    
                    // Encontrar la etiqueta del método de pago
                    let metodo_pago_label = 'Sin llenar';
                    let metodo_len = metodo_pago.length;
                    let metodo_clave_array = [];
                    let metodo_label_array = [];
                    let bandera = true;
                    let iterador_clave = 0;
                    let iterador_label = 0;
                    
                    for(let x = 3; x < metodo_len; x++){
                        let opcion = metodo_pago[x];
                        if(x % 2 !== 0 && bandera){
                            metodo_clave_array[iterador_clave] = opcion;
                            bandera = false;
                            iterador_clave++;
                        }else if(x % 2 !== 0 && !bandera){
                            metodo_label_array[iterador_label] = opcion;
                            bandera = true;
                            iterador_label++;
                        }
                    }
                    
                    let total = metodo_label_array.length;
                    for(let x = 0; x < total; x++){
                        if(metodo_clave_array[x] === sel_formpago){
                            metodo_pago_label = metodo_label_array[x];
                            break;
                        }
                    }
                    
                    // Encontrar etiqueta de uso CFDI
                    let uso_cfdi_label = 'Sin llenar';
                    let u_len = uso_cfdi.length;
                    for(let u = 0; u < u_len; u++){
                        let opcion = uso_cfdi[u];
                        if(opcion.code === sel_usocfdi){
                            uso_cfdi_label = opcion.code + ' - ' + opcion.label;
                            break;
                        }
                    }
                    
                    out = `
                    <div class="container mb-4">
                        <div class="card shadow-lg border-0">
                            <div class="card-header bg-primary text-white py-3">
                                <h4 class="mb-0">
                                    <i class="fa fa-file-invoice"></i> Información de Factura
                                    <span class="badge badge-light float-right">${ref}</span>
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-4 h-100 border-left border-primary">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0"><i class="fa fa-user"></i> Datos del Cliente</h5>
                                            </div>
                                            <div class="card-body">
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><strong>Nombre:</strong></span>
                                                        <span class="text-primary">${nombre}</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><strong>RFC:</strong></span>
                                                        <span class="text-primary">${rfc}</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><strong>Correo:</strong></span>
                                                        <span class="text-primary">${correo}</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><strong>Régimen Fiscal:</strong></span>
                                                        <span class="text-primary">${regimen_fiscal} ${regimen_label ? '- ' + regimen_label : ''}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="card mb-4 h-100 border-left border-primary">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0"><i class="fa fa-map-marker-alt"></i> Domicilio Fiscal</h5>
                                            </div>
                                            <div class="card-body">
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item">
                                                        <strong>Dirección:</strong><br>
                                                        <span class="text-primary">${direccion}</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><strong>Estado:</strong></span>
                                                        <span class="text-primary">${estado_nombre}</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><strong>Municipio:</strong></span>
                                                        <span class="text-primary">${municipio}</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><strong>C.P.:</strong></span>
                                                        <span class="text-primary">${cp}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="card border-left border-primary">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0"><i class="fa fa-money-check-alt"></i> Datos de Facturación</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="alert alert-info">
                                                            <h6 class="alert-heading"><i class="fa fa-dollar-sign"></i> Monto</h6>
                                                            <h4 class="text-center font-weight-bold">$${monto}</h4>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label class="font-weight-bold">Método de pago CFDI:</label>
                                                            <p class="form-control-static text-primary">${metodo_pago_label}</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label class="font-weight-bold">Uso de CFDI:</label>
                                                            <p class="form-control-static text-primary">${uso_cfdi_label}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <form class="mt-4">
                                    <input type="hidden" name="clave_factura" id="clave_factura" value="${ref}">
                                    <input type="hidden" name="monto" id="monto" value="${monto}">
                                    <input type="hidden" name="fk_facture" id="fk_facture" value="${fk_facture}">
                                    <input type="hidden" name="fk_socid" id="fk_socid" value="${fk_soc}">
                                    <input type="hidden" name="email" id="email" value="${correo}">
                                </form>
                                
                                <div class="row mt-4">
                                    <div class="col-12 text-center">
                                        ${incompleto === 0 ? 
                                            `<div class="alert alert-success mb-4">
                                                <h6 class="alert-heading"><i class="fa fa-check-circle"></i> Datos completados correctamente</h6>
                                                <p class="mb-0">¿Desea continuar con el proceso de facturación?</p>
                                            </div>
                                            <button type="button" class="btn btn-lg btn-success mr-3" onclick="timbrar();">
                                                <i class="fa fa-check-circle"></i> Sí, Timbrar Factura
                                            </button>` 
                                            : 
                                            `<div class="alert alert-warning mb-4">
                                                <h6 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> Información incompleta</h6>
                                                <p class="mb-0">Es necesario completar todos los datos fiscales antes de continuar.</p>
                                            </div>`
                                        }
                                        <button type="button" class="btn btn-lg btn-primary" onclick="editar_datos();">
                                            <i class="fa fa-edit"></i> Editar Datos
                                        </button>
                                        <button type="button" class="btn btn-lg btn-danger ml-3" onclick="cancelar();">
                                            <i class="fa fa-times"></i> Cancelar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    `;
                }
                else if(data.error === 1){
                    let limiteTxt = data.date_limit ? ` Fecha límite: ${data.date_limit}.` : '';
                    let fueraMsg = 'Fuera de fecha de timbrado. Solo se puede facturar durante el mes de la venta.' + limiteTxt;
                    if(msg) {
                        msg.innerHTML = fueraMsg;
                    } else {
                        contenedor.innerHTML = '<div class="alert alert-danger">' + fueraMsg + '</div>';
                    }
                }
                else if(data.error === 2){
                    let path = data.ref + '/' + data.uuid;
                    let url_pdf = '<?= DOL_URL_ROOT . "/document.php?modulepart=facture&file="; ?>'+path+'.pdf';
                    let url_xml = '<?= DOL_URL_ROOT . "/document.php?modulepart=facture&file="; ?>'+path+'.xml';
                    contenedor.innerHTML = '';
                    contenedor.innerHTML += '<div id="msg_noresult" class="d-none"></div>';
                    out = `
                    <div class="card shadow-lg border-0 mb-4">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0"><i class="fa fa-check-circle"></i> Factura Timbrada Previamente</h4>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-4">
                                <img src="img/invoice-check.svg" alt="Factura Timbrada" style="max-width: 150px;">
                            </div>
                            <h5 class="card-title">La factura ${data.ref} ya ha sido timbrada</h5>
                            <p class="card-text mb-4">A continuación puedes descargar los archivos correspondientes</p>
                            
                            <div class="row justify-content-center">
                                <div class="col-md-4 mb-3">
                                    <a href="${url_pdf}" class="btn btn-primary btn-lg btn-block" target="_blank" download>
                                        <i class="fa fa-file-pdf fa-lg mr-2"></i> Descargar PDF
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="${url_xml}" class="btn btn-secondary btn-lg btn-block" target="_blank" download>
                                        <i class="fa fa-file-code fa-lg mr-2"></i> Descargar XML
                                    </a>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-light btn-lg mt-3" onclick="cancelar()">
                                <i class="fa fa-arrow-left"></i> Regresar
                            </button>
                        </div>
                    </div>
                    `;
                }
                else{
                    if(msg) {
                        msg.innerHTML = 'No se encontraron resultados';
                    } else {
                        contenedor.innerHTML = '<div class="alert alert-danger">No se encontraron resultados</div>';
                    }
                }
                contenedor.innerHTML += out;
            },
            error: function ( xhr, errorType, exception ) {
                var errorMessage = exception || xhr.statusText;
                console.log(xhr);
                alert( "Error: " + errorMessage );
            }
        });
    }

    function editar_datos(){
        let url = '<?= DOL_URL_ROOT.'/custom/autofactura_ticket/search.php'; ?>';
        let clave_factura = $('#clave_factura').val();
        let monto = $('#monto').val();
        let data = {
            'clave_factura' : clave_factura,
            'monto' : monto
        };
        let contenedor = document.getElementById('resultado');
        let loading = '';
        loading += '<div class ="row">';
        loading += '<div class ="col center-block" align="center">';
        loading += '<img src="img/loading.gif" alt="loading" />';
        loading += '</div>';
        loading += '</div>';
        contenedor.innerHTML = loading;
        $.ajax({
            type:'POST',
            url: url,
            data: data,
            dataType: 'json',
            success:function(data){
                let out = '';
                if(data.length !== 0){
                    contenedor.innerHTML = '';
                    let resultado = data[0];
                    let uso_cfdi = data[1];
                    let domicilio = data[3];
                    let estados = data[4];
                    let sel_usocfdi = (data[5].length == 0)? '' : data[5][0].usocfdi;
                    let temp = String(data[2].param);
                    let regimen = data[7];
                    let metodo_pago = temp.split('"');
                    let monto = resultado.monto.toString();
                    let regex=/(\d*.\d{0,2})/;
                    monto = monto.match(regex)[0];
                    let fk_facture = resultado.rowid;
                    let ref = resultado.ref;
                    let fk_soc = resultado.fk_socid;
                    let nombre = resultado.nombre;
                    let m_pago = resultado.m_pago;
                    let cfdi = resultado.cfdi;
                    let rfc = resultado.rfc;
                    let phone = resultado.phone;
                    let correo = resultado.correo;
                    let cp = resultado.cp;
                    let municipio = domicilio.receptor_delompio;
                    let regimen_fiscal = domicilio.regimen_f;
                    let direccion = domicilio.tpdomicilio;
                    
                    let es_publico_general = nombre && (
                        nombre.toLowerCase().indexOf('publico general') !== -1 || 
                        nombre.toLowerCase().indexOf('público general') !== -1
                    );
                    
                    //--------Limpiamos variables-------------
                    if( ref === undefined ) ref = '';
                    if( nombre === undefined ) nombre = '';
                    if( m_pago === undefined ) m_pago = '';
                    if( correo === undefined ) correo = '';
                    if( cfdi === undefined ) cfdi = '';
                    if( rfc === undefined ) rfc = '';
                    if( phone === undefined ) phone = '';
                    if( cp === undefined ) cp = '';

                    if( municipio === undefined ) municipio = '';
                    if(direccion === undefined) direccion = '';
                    if( regimen_fiscal === undefined ) regimen_fiscal = '';
                    if( correo === undefined ) correo = '';
                    out += '<form name="form_timbra" id="form_timbra" method="POST" >';
                    out += '<br>';
                    
                    if(es_publico_general) {
                        out += '<div class="alert alert-warning" role="alert">';
                        out += '<strong><i class="fa fa-exclamation-triangle"></i> La información del cliente \'' + nombre + '\' no puede ser modificada directamente.</strong>';
                        out += '<p class="mb-0 mt-2">Para cambiar a un cliente diferente, utilice la búsqueda por número de teléfono.</p>';
                        out += '</div>';
                    }
                    
                    out += '<div class="card shadow-sm mb-4">';
                    out += '<div class="card-header bg-primary text-white">';
                    out += '<h5 class="mb-0"><i class="fa fa-user-circle"></i> Datos de facturación</h5>';
                    out += '</div>';
                    out += '<div class="card-body">';
                    
                    out += '<div class="row mb-4">';
                    out += '<div class="col-12">';
                    out += '<div class="form-group">';
                    out += '<h6 class="mb-3"><i class="fa fa-search"></i> Buscar cliente existente</h6>';
                    out += '</div>';
                    out += '</div>';
                    out += '</div>';
                    
                    out +=
                        `<div class="form-group row">
                            <label for="phone" class="col-sm-2 col-form-label">Número de Teléfono</label>
                            <div class="col-sm-5">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                    </div>
                                    <input type="number" class="form-control" id="phone" name="phone" value="${phone}" placeholder="10 dígitos" maxlength="10" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary" onclick="buscarCliente()">
                                            <i class="fa fa-search"></i> Buscar
                                        </button>
                            </div> 
                                </div>
                                <span id="phone_error" class="text-danger"></span>
                            </div>
                        </div>`;
                        
                        
                    out += '<div class="form-group row"><div class="col-12"><div id="alerta_busqueda"></div></div></div>';
                    
                    out += '<div class="form-group row"><div class="col-12"><div id="resultadosClientes"></div></div></div>';
                    
                    out += '<div class="form-group row"><div class="col-12"><div id="datos_empresa" class="text-danger"></div></div></div>';
                    
                    out += '<hr class="my-4">';
                    
                    out += '<div class="row mb-3">';
                    out += '<div class="col-12">';
                    out += '<h6><i class="fa fa-address-card"></i> Información del cliente</h6>';
                    out += '</div>';
                    out += '</div>';
                    
                    out+= 
                        `<div class="form-group row"> 
                            <label for="nombre" class="col-sm-2 col-form-label">Nombre</label> 
                            <div class="col-sm-8"> 
                                <input type="text" class="form-control" id="nombre" name="nombre" value="${nombre}" required ${es_publico_general ? 'disabled' : ''}> 
                                <input type="hidden" id="nombre_anterior" name="nombre_anterior" value="${nombre}"> 
                            </div> 
                        </div>`;

                    out+= 
                        `<div class="form-group row">
                            <label for="municipio" class="col-sm-2 col-form-label">Domicilio fiscal</label>
                        </div>`;

                    out += '<div class="row">';
                    
                    out += '<div class="col-md-4 mb-3">';
                    out += '<label for="estado">Estado</label>';
                    out += '<select class="form-control" id="estado" name="estado" required ' + (es_publico_general ? 'disabled' : '') + '>';

                    for(let x = 0; x < estados.length; x++){
                        let estado = estados[x];
                        if(resultado.fk_estado === estado.rowid)
                            out+= `<option value ="${estado.rowid}" selected>${estado.code_departement} - ${estado.nom} </option>`;
                        else
                            out+= '<option value ="'+estado.rowid+'">'+estado.code_departement+' - '+ estado.nom +'</option>';
                    }
                    out += '</select>';
                    out += '</div>';
                    
                    out += '<div class="col-md-4 mb-3">';
                    out += '<label for="municipio">Municipio</label>';
                    out += '<input type="text" class="form-control" id="municipio" name="municipio" value="' + municipio + '" required ' + (es_publico_general ? 'disabled' : '') + '>';
                    out += '<input type="hidden" id="municipio_original" name="municipio_original" value="' + municipio + '">';
                    out += '</div>';
                    
                    out += '<div class="col-md-4 mb-3">';
                    out += '<label for="cp">Código Postal</label>';
                    out += '<input type="number" class="form-control" id="cp" name="cp" value="' + cp + '" required ' + (es_publico_general ? 'disabled' : '') + '>';
                    out += '<input type="hidden" id="cp_original" name="cp_original" value="' + cp + '">';
                    out += '</div>';
                    
                    out += '</div>'; // Cierre de row
                    
                    out += '<div class="form-group row mb-4">';
                    out += '<div class="col-md-12">';
                    out += '<label for="direccion">Dirección</label>';
                    out += '<textarea rows="2" class="form-control" id="direccion" name="direccion" placeholder="Ingrese colonia, calle, numero interior, numero exterior" required ' + (es_publico_general ? 'disabled' : '') + '>' + direccion + '</textarea>';
                    out += '<input type="hidden" id="direccion_original" name="direccion_original" value="' + direccion + '">';
                    out += '</div>';
                    out += '</div>';
                    
                    out += '<hr class="my-4">';
                    
                    out += '<div class="row mb-3">';
                    out += '<div class="col-12">';
                    out += '<h6><i class="fa fa-file-invoice-dollar"></i> Información fiscal</h6>';
                    out += '</div>';
                    out += '</div>';
                    
                    out += '<div class="row">';
                    
                    out += '<div class="col-md-6 mb-3">';
                    out += '<label for="regimen_fiscal">Régimen fiscal</label>';
                    out += '<select class="form-control" id="regimen_fiscal" name="regimen_fiscal" required ' + (es_publico_general ? 'disabled' : '') + '>';

                    for(let x = 0; x < regimen.length; x++){
                        let regimen_f = regimen[x];
                        if(regimen_f.code === regimen_fiscal)
                            out += `<option value ="${regimen_f.code}" selected>${regimen_f.code} - ${regimen_f.label}</option>`;
                        else
                            out += `<option value ="${regimen_f.code}">${regimen_f.code} - ${regimen_f.label}</option>`;
                    }
                    out += '</select>';
                    out += '<input type="hidden" id="regimen_fiscal_original" name="regimen_fiscal_original" value="' + regimen_fiscal + '">';
                    out += '</div>';
                    
                    out += '<div class="col-md-6 mb-3">';
                    out += '<label for="rfc">R.F.C.</label>';
                    out += '<input type="text" class="form-control" id="rfc" name="rfc" value="' + rfc + '" required ' + (es_publico_general ? 'disabled' : '') + '>';
                    out += '</div>';
                    
                    out += '</div>'; // Cierre de row
                    
                    out += '<div class="row">';
                    
                    out += '<div class="col-md-6 mb-3">';
                    out += '<label for="correo">Correo electrónico</label>';
                    out += '<input type="email" class="form-control" id="correo" name="correo" value="' + correo + '" required ' + (es_publico_general ? 'disabled' : '') + '>';
                    out += '<input type="hidden" id="correo_original" name="correo_original" value="' + correo + '">';
                    out += '</div>';
                    
                    out += '<div class="col-md-6 mb-3">';
                    out += '<label for="metodo_cfdi">Método de pago CFDI</label>';
                    out += '<select class="form-control" id="metodo_cfdi" name="metodo_cfdi" required ' + (es_publico_general ? 'disabled' : '') + '>';
                    out += '<option value="PUE" selected>PUE - Pago en una sola exhibición</option>';
                    out += '</select>';
                    out += '</div>';
                    
                    out += '</div>'; // Cierre de row
                    
                    out += '<div class="row">';
                    
                    out += '<div class="col-md-6 mb-3">';
                    out += '<label for="uso_cfdi">Uso de CFDI</label>';
                    out += '<select class="form-control" id="uso_cfdi" name="uso_cfdi" required ' + (es_publico_general ? 'disabled' : '') + '>';
                    
                    options = {
                        "G01": "G01 - Adquisición de mercancías",
                        "G03": "G03 - Gastos en general",
                        "D02": "D02 - Gastos médicos",
                        "S01": "S01 - Sin efectos fiscales",
                        "CP01": "CP01 - Pagos",
                        //? TODO: Agregar un nuevo uso de CFDI
                    };

                    let arrOptions = Object.keys(options);
                    arrOptions.forEach(function(key) {
                        if (key === sel_usocfdi) {
                            out += `<option value="${key}" selected>${options[key]}</option>`;
                        } else {
                            out += `<option value="${key}">${options[key]}</option>`;
                        }
                    });
                    
                    out += '</select>';
                    out += '</div>';
                    
                    out += '<div class="col-md-6 mb-3">';
                    out += '<label for="monto">Monto</label>';
                    out += '<input type="number" class="form-control" id="monto" name="monto" value="' + monto + '" disabled required>';
                    out += '</div>';
                    
                    out += '</div>'; // Cierre de row
                    
                    out += '<input type="hidden" id="fk_facture" name="fk_facture" value="' + fk_facture + '">';
                    out += '<input type="hidden" id="ref" name="ref" value="' + ref + '">';
                    out += '<input type="hidden" id="fk_soc" name="fk_soc" value="' + fk_soc + '">';
                    out += '<input type="hidden" id="fk_soc_original" name="fk_soc_original" value="' + fk_soc + '">';
                    out += '<input type="hidden" id="rfc_anterior" name="rfc_anterior" value="' + rfc + '">';
                    
                    out += '<div class="form-group row mt-4">';
                    out += '<div class="col-12 text-center">';
                    out += '<button type="button" class="btn btn-lg btn-success mr-2" onclick="grabar_datos()"><i class="fa fa-save"></i> Guardar datos</button>';
                    out += '<button type="button" class="btn btn-lg btn-danger" onclick="cancelar()"><i class="fa fa-times"></i> Cancelar</button>';
                    out += '</div>';
                    out += '</div>';
                    
                    out += '</div>'; // Cierre de card-body
                    out += '</div>'; // Cierre de card
                    
                    out += '</form>';
                }
                else{
                    mostrarAlerta('alerta_busqueda', 'Error en la actualización de los datos', 'danger');
                }
                contenedor.innerHTML += out;
            },
            error: function(xhr, errorType, exception) {
                let errorMessage = exception || xhr.statusText;
                mostrarAlerta('alerta_busqueda', 'Error: ' + errorMessage, 'danger');
            }
        });
    }

    function buscarCliente(){
        let url = '<?= DOL_URL_ROOT.'/custom/autofactura_ticket/search.php'; ?>';
        let phone = $('#phone').val();

        // Validar que el número de teléfono tenga exactamente 10 dígitos y solo números
        if(phone.length !== 10 || !/^\d+$/.test(phone)){
            mostrarAlerta('phone_error', 'El número de teléfono debe tener exactamente 10 dígitos', 'danger');
            return;
        }

        let resultadosClientes = document.getElementById('resultadosClientes');

        let regexPhone = /^\d{10}$/;
        if(phone === ''){
            mostrarAlerta('phone_error', 'Ingrese un número de teléfono para buscar al cliente', 'danger');
            return;
        }
        
        if(!regexPhone.test(phone)){
            mostrarAlerta('phone_error', 'El número de teléfono debe tener exactamente 10 dígitos', 'danger');
            return;
        }

        let data = {
            'action' : 'buscarClientes',
            'phone' : phone
        };

        resultadosClientes.innerHTML = '<div class="text-center mt-3 mb-3"><div class="spinner-border text-primary" role="status"><span class="sr-only">Buscando...</span></div><p class="mt-2">Buscando clientes...</p></div>';
        
        $.ajax({
            type:'POST',
            url: url,
            data: data,
            dataType: 'json',
            success:function(data){
                if(data.clientes && data.clientes.length > 0) {
                    mostrarClientes(data.clientes);
                    mostrarAlerta('alerta_busqueda', 'Se encontraron ' + data.clientes.length + ' cliente(s)', 'success');
                } else {
                    resultadosClientes.innerHTML = '<div class="alert alert-info mt-3"><i class="fa fa-info-circle"></i> No se encontraron clientes con ese número de teléfono. Puede crear un nuevo cliente completando el formulario.</div>';
                    
                    limpiarCamposCliente();
                    habilitarCamposCliente();
                    mostrarAlerta('alerta_busqueda', 'No se encontraron clientes con ese número de teléfono', 'warning');
                }
            },
            error: function(xhr, errorType, exception) {
                resultadosClientes.innerHTML = '';
                mostrarAlerta('alerta_busqueda', 'Error en la búsqueda: ' + exception, 'danger');
                console.log(xhr);
            }
        });
    }

    function mostrarClientes(clientes) {
        let resultadosClientes = document.getElementById('resultadosClientes');
        
        let nombre_actual = document.getElementById('nombre').value;
        let es_publico_general_actual = nombre_actual && (
            nombre_actual.toLowerCase().indexOf('publico general') !== -1 || 
            nombre_actual.toLowerCase().indexOf('público general') !== -1
        );
        
        if (es_publico_general_actual) {
            let infoBox = '<div class="alert alert-info mb-3">';
            infoBox += '<i class="fa fa-info-circle"></i> <strong>¿Desea cambiar de "Público general" a un cliente específico?</strong>';
            infoBox += '<p class="mb-0 mt-1">Seleccione el cliente deseado de la tabla siguiente.</p>';
            infoBox += '</div>';
            resultadosClientes.innerHTML = infoBox;
        }
        
        let tabla = '<div class="mb-3 text-right">';
        tabla += '</div>';
        
        tabla += '<div class="table-responsive">';
        tabla += '<table class="table table-hover table-bordered">';
        tabla += '<thead class="thead-dark">';
        tabla += '<tr>';
        tabla += '<th>Nombre</th>';
        tabla += '<th>Teléfono</th>';
        tabla += '<th>RFC</th>';
        tabla += '<th>Correo</th>';
        tabla += '<th>Acción</th>';
        tabla += '</tr>';
        tabla += '</thead>';
        tabla += '<tbody>';

        clientes.forEach(function(cliente) {
            let esPublicoGeneral = (cliente.nombre && (
                cliente.nombre.toLowerCase().indexOf('publico general') !== -1 || 
                cliente.nombre.toLowerCase().indexOf('público general') !== -1
            ));

            tabla += '<tr' + (esPublicoGeneral ? ' class="table-warning"' : '') + '>';
            tabla += '<td>' + (cliente.nombre || '') + '</td>';
            tabla += '<td>' + (cliente.phone || '') + '</td>';
            tabla += '<td>' + (cliente.rfc || '') + '</td>';
            tabla += '<td>' + (cliente.correo || '') + '</td>';
            tabla += '<td><button type="button" class="btn btn-sm ' + (esPublicoGeneral ? 'btn-warning' : 'btn-primary') + '" onclick="seleccionarCliente(' + cliente.rowid + ')">';
            tabla += esPublicoGeneral ? '<i class="fa fa-eye"></i> Ver' : '<i class="fa fa-check"></i> Seleccionar';
            tabla += '</button></td>';
            tabla += '</tr>';
        });

        tabla += '</tbody>';
        tabla += '</table>';
        tabla += '</div>';

        if (es_publico_general_actual) {
            resultadosClientes.innerHTML += tabla;
        } else {
            resultadosClientes.innerHTML = tabla;
        }
    }
    
    function prepararNuevoCliente() {
        limpiarCamposCliente();
        habilitarCamposCliente();
        let phone = document.getElementById('phone').value;
        mostrarAlerta('alerta_busqueda', 'Complete el formulario para crear un nuevo cliente', 'info');
        document.getElementById('resultadosClientes').innerHTML = '';
    }

    function seleccionarCliente(clienteId) {
        let url = '<?= DOL_URL_ROOT.'/custom/autofactura_ticket/search.php'; ?>';
        let data = {
            'action': 'obtenerCliente',
            'rowid': clienteId
        };

        mostrarAlerta('alerta_busqueda', 'Cargando datos del cliente...', 'info');

        $.ajax({
            type: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function(data) {
                if(data.cliente_encontrado) {
                    let cliente = data;
                    let { domicilio } = data;
                    
                    let nombre_original = document.getElementById('nombre').value;
                    let fk_soc_original = document.getElementById('fk_soc').value;
                    
                    if(!document.getElementById('fk_soc_original').value) {
                        document.getElementById('fk_soc_original').value = fk_soc_original;
                    }
                    
                    let es_publico_general_original = (nombre_original && (
                        nombre_original.toLowerCase().indexOf('publico general') !== -1 || 
                        nombre_original.toLowerCase().indexOf('público general') !== -1
                    ));
                    
                    document.getElementById('nombre').value = cliente.nombre;
                    document.getElementById('phone').value = cliente.phone;
                    
                    document.getElementById('fk_soc').value = cliente.rowid;
                    
                    document.getElementById('nombre_anterior').value = nombre_original;
                    
                    if(es_publico_general_original && !data.es_publico_general) {
                        mostrarAlerta('alerta_busqueda', '✅ Se ha cambiado el cliente de "' + nombre_original + '" a "' + cliente.nombre + '". Los datos se actualizarán al guardar cambios.', 'success');
                    } else if(data.es_publico_general) {
                        mostrarAlerta('alerta_busqueda', 'Ha seleccionado "' + cliente.nombre + '". Recuerde que la información de este cliente no puede ser modificada.', 'warning');
                    } else {
                        mostrarAlerta('alerta_busqueda', 'Cliente cargado correctamente', 'success');
                    }
                    
                    if(domicilio.rowid_estado) {
                        document.getElementById('estado').value = domicilio.rowid_estado;
                    }
                    document.getElementById('municipio').value = domicilio.municipio || '';
                    document.getElementById('cp').value = cliente.cp || '';
                    document.getElementById('direccion').value = domicilio.tpdomicilio || '';
                    
                    document.getElementById('municipio_original').value = domicilio.municipio || '';
                    document.getElementById('cp_original').value = cliente.cp || '';
                    document.getElementById('direccion_original').value = domicilio.tpdomicilio || '';
                    
                    if(domicilio.regimenfiscal) {
                        document.getElementById('regimen_fiscal').value = domicilio.regimenfiscal;
                        document.getElementById('regimen_fiscal_original').value = domicilio.regimenfiscal;
                    }
                    document.getElementById('rfc').value = cliente.rfc || '';
                    document.getElementById('rfc_anterior').value = cliente.rfc || '';
                    document.getElementById('correo').value = cliente.correo || '';
                    document.getElementById('correo_original').value = cliente.correo || '';

                    if(data.es_publico_general) {
                        mostrarAlerta('datos_empresa', 'La información del cliente \'' + cliente.nombre + '\' no puede ser modificada. Por favor ingrese los datos de Número de teléfono y Nombre correctamente.', 'warning');
                        deshabilitarCamposCliente();
                    } else {
                        document.getElementById('datos_empresa').innerHTML = '';
                        habilitarCamposCliente();
                    }
                    
                    document.getElementById('resultadosClientes').innerHTML = '';
                } else {
                    mostrarAlerta('alerta_busqueda', 'No se pudo cargar la información del cliente', 'danger');
                }
            },
            error: function(xhr, errorType, exception) {
                mostrarAlerta('alerta_busqueda', 'Error al cargar cliente: ' + exception, 'danger');
                console.log(xhr);
            }
        });
    }

    function mostrarAlerta(elementId, mensaje, tipo) {
        let elemento = document.getElementById(elementId);
        if(elemento) {
            elemento.innerHTML = '<div class="alert alert-' + tipo + '">' + mensaje + '</div>';
            
            // Auto-ocultar después de 5 segundos para alertas de éxito
            if(tipo === 'success') {
                setTimeout(function() {
                    elemento.innerHTML = '';
                }, 5000);
            }
        }
    }

    function limpiarCamposCliente() {
        document.getElementById('nombre').value = '';
        document.getElementById('estado').value = '';
        document.getElementById('municipio').value = '';
        document.getElementById('cp').value = '';
        document.getElementById('direccion').value = '';
        document.getElementById('regimen_fiscal').value = '';
        document.getElementById('rfc').value = '';
        document.getElementById('correo').value = '';
    }

    function habilitarCamposCliente() {
        document.getElementById('nombre').disabled = false;
        document.getElementById('estado').disabled = false;
        document.getElementById('municipio').disabled = false;
        document.getElementById('cp').disabled = false;
        document.getElementById('direccion').disabled = false;
        document.getElementById('regimen_fiscal').disabled = false;
        document.getElementById('rfc').disabled = false;
        document.getElementById('correo').disabled = false;
        document.getElementById('metodo_cfdi').disabled = false;
        document.getElementById('uso_cfdi').disabled = false;
    }

    function deshabilitarCamposCliente() {
        document.getElementById('nombre').disabled = true;
        document.getElementById('estado').disabled = true;
        document.getElementById('municipio').disabled = true;
        document.getElementById('cp').disabled = true;
        document.getElementById('direccion').disabled = true;
        document.getElementById('regimen_fiscal').disabled = true;
        document.getElementById('rfc').disabled = true;
        document.getElementById('correo').disabled = true;
        document.getElementById('metodo_cfdi').disabled = true;
        document.getElementById('uso_cfdi').disabled = true;
    }

    function grabar_datos(){
        let url = '<?= DOL_URL_ROOT."/custom/autofactura_ticket/grabar_datos.php"; ?>';
        let direccion = $('#direccion').val();
        let correo = $('#correo').val();
        let cp = $('#cp').val();
        let estado = $('#estado').val();
        let fk_facture = $('#fk_facture').val();
        let fk_soc = $('#fk_soc').val();
        let metodo_cfdi = $('#metodo_cfdi').val();
        let municipio = $('#municipio').val();
        let nombre = $('#nombre').val();
        let nombre_anterior = $('#nombre_anterior').val();
        let regimen_fiscal = $('#regimen_fiscal').val();
        let ref = $('#ref').val();
        let rfc = $('#rfc').val();
        let rfc_anterior = $('#rfc_anterior').val();
        let uso_cfdi = $('#uso_cfdi').val();
        let monto = $('#monto').val();
        let phone = $('#phone').val();

        // Validar que el número de teléfono tenga exactamente 10 dígitos y solo números
        if(phone.length !== 10 || !/^\d+$/.test(phone)){
            mostrarAlerta('phone_error', 'El número de teléfono debe tener exactamente 10 dígitos', 'danger');
            return;
        }
        
        let es_publico_general = nombre && (
            nombre.toLowerCase().indexOf('publico general') !== -1 || 
            nombre.toLowerCase().indexOf('público general') !== -1
        );
        
        let fk_soc_original = $('#fk_soc_original').val();
        let cambiando_desde_publico_general = false;
        
        if(es_publico_general && fk_soc === fk_soc_original) {
            mostrarAlerta('alerta_busqueda', 'La información del cliente \'' + nombre + '\' no puede ser modificada. Por favor seleccione un cliente específico.', 'danger');
            return;
        }
        
        let direccion_original = $('#direccion_original').val();
        let correo_original = $('#correo_original').val();
        let cp_original = $('#cp_original').val();
        let municipio_original = $('#municipio_original').val();
        let regimen_fiscal_original = $('#regimen_fiscal_original').val();

        if(nombre === ''){ 
            mostrarAlerta('alerta_busqueda', 'Hay campos sin rellenar: Nombre', 'danger'); 
            return; 
        }
        if(municipio === ''){ 
            mostrarAlerta('alerta_busqueda', 'Hay campos sin rellenar: Municipio', 'danger'); 
            return; 
        }
        if(cp === ''){ 
            mostrarAlerta('alerta_busqueda', 'Hay campos sin rellenar: Código Postal', 'danger'); 
            return; 
        }
        if(direccion === ''){ 
            mostrarAlerta('alerta_busqueda', 'Hay campos sin rellenar: Dirección', 'danger'); 
            return; 
        }
        if(rfc === ''){ 
            mostrarAlerta('alerta_busqueda', 'Hay campos sin rellenar: RFC', 'danger'); 
            return; 
        }
        if(correo === ''){ 
            mostrarAlerta('alerta_busqueda', 'Hay campos sin rellenar: Correo', 'danger'); 
            return; 
        }
        let data = {
            'direccion' : direccion,
            'direccion_original' : direccion_original,
            'correo' : correo,
            'correo_original' : correo_original,
            'cp' : cp,
            'cp_original' : cp_original,
            'estado' : estado,
            'fk_facture' : fk_facture,
            'fk_soc' : fk_soc,
            'fk_soc_original': fk_soc_original,
            'metodo_cfdi' : metodo_cfdi,
            'municipio' : municipio,
            'municipio_original' : municipio_original,
            'nombre' : nombre,
            'nombre_anterior' : nombre_anterior,
            'regimen_fiscal' : regimen_fiscal,
            'regimen_fiscal_original' : regimen_fiscal_original,
            'ref' : ref,
            'rfc' : rfc,
            'rfc_anterior' : rfc_anterior,
            'uso_cfdi' : uso_cfdi,
            'monto' : monto,
            'phone' : phone
        };
        let contenedor = document.getElementById('resultado');
        let loading = '';
        loading += '<div class ="row">';
        loading += '<div class ="col center-block" align="center">';
        loading += '<img src="img/loading.gif" alt="loading" />';
        loading += '</div>';
        loading += '</div>';
        contenedor.innerHTML = loading;
        $.ajax({
            type:'POST',
            url: url,
            data: data,
            dataType: 'json',
            success:function(data){

                let response = data.response; // 0  if OK, < 0 if error
                let post = data.post;
                let params = data.params;
                if(response === 0){
                    mostrarAlerta('alerta_busqueda', 'Datos guardados correctamente', 'success');
                    
                    let out = '<h5>Datos actualizados con éxito:</h5><br><div class="container">';
                    out += '<div class="row">';
                    out += '<div class="col"><p>Nombre : <strong>'+post.nombre+'</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col"><p>Clave de factura : <strong>'+post.ref+'</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col"><p>Monto : <strong>'+post.monto+'</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col"><p><strong>Domicilio fiscal</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><p>Estado : <strong>'+params.estado+'</strong></div>';
                    out += '<div class="col-auto"><p>Municipio : <strong>'+post.municipio+'</strong></div>';
                    out += '<div class="col-auto"><p>Codigo Postal : <strong>'+post.cp+'</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><p>Dirección : <strong>'+post.direccion+'</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><p>Método de pago CFDI : <strong>'+post.metodo_cfdi+' - '+params.metodo_pago+'</strong></div>';
                    out += '<div class="col-auto"><p>Uso de CFDI : <strong>'+post.uso_cfdi+' - '+params.uso_cfdi+'</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><p>Régimen fiscal : <strong>'+post.regimen_fiscal+' - '+params.regimen+'</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><p>RFC : <strong>'+post.rfc+'</strong></div>';
                    out += '<div class="col-auto"><p>Correo : <strong>'+post.correo+'</strong></div>';
                    out += '</div>';
                    out += '<input type="hidden" name="fk_socid" id="fk_socid" value="'+params.fk_socid+'">';
                    out += '<input type="hidden" name="fk_facture" id="fk_facture" value="'+params.fk_facture+'">';
                    out += '<input type="hidden" name="clave_factura" id="clave_factura" value="'+params.ref+'">';
                    out += '<input type="hidden" name="monto" id="monto" value="'+params.monto+'">';
                    out += '<input type="hidden" name="email" id="email" value="'+params.email+'">';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><button class="btn btn-success" onclick="timbrar();">Timbrar</button></div>';
                    out += '<div class="col-auto"><button class="btn btn-warning" onclick="editar_datos();">Regresar a editar</button></div>';
                    out += '</div>';
                    out += '</div>';
                    contenedor.innerHTML = out;
                }
                else if(response === -2){
                    let errorMsg = data.error_message || 'La información del cliente \'Público general CN\' no puede ser modificada. Por favor ingrese los datos de Número de teléfono y Nombre correctamente.';
                    
                    let out = '<div class="alert alert-danger" role="alert">';
                    out += '<h5>Error:</h5>';
                    out += '<p>' + errorMsg + '</p>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><button class="btn btn-primary" onclick="editar_datos();">Regresar</button></div>';
                    out += '</div>';
                    
                    contenedor.innerHTML = out;
                }
                else{
                    mostrarAlerta('alerta_busqueda', 'Error en la actualización de los datos', 'danger');
                }
            },
            error: function ( xhr, errorType, exception ) {
                var errorMessage = exception || xhr.statusText;
                console.log(xhr);
                mostrarAlerta('alerta_busqueda', 'Error: ' + errorMessage, 'danger');
            }
        });
    }

    function timbrar(){
        let url = '<?= DOL_URL_ROOT . '/custom/autofactura_ticket/genera_cfdi.php';?>';
        let fk_socid = $('#fk_socid').val();
        let fk_facture = $('#fk_facture').val();
        let email = $('#email').val();
        let data = {
            'fk_socid' : fk_socid,
            'fk_facture' : fk_facture,
            'email' : email
        };
        let contenedor = document.getElementById('resultado');
        let loading = '';
        loading += '<div class ="row">';
        loading += '<div class ="col center-block" align="center">';
        loading += '<img src="img/loading.gif" alt="loading" />';
        loading += '</div>';
        loading += '</div>';
        contenedor.innerHTML = loading;
        $.ajax({
            type:'POST',
            url: url,
            data: data,
            dataType: 'json',
            success : function(data){
                let out = '';
                if(data.error === 0 && data.cfdi_code >= 0){
                    let url_pdf = '<?= DOL_URL_ROOT . "/document.php?modulepart=facture&file="; ?>'+data.path_pdf;
                    let url_xml = '<?= DOL_URL_ROOT . "/document.php?modulepart=facture&file="; ?>'+data.path_xml;
                    out = '<h5>Factura timbrada con exito</h5>';
                    out += '<br>';
                    out += data.msg_email;
                    out += '<br>';
                    out += '<br>';
                    out += '<div class="row">';
                    out += '<div class="col-3"><a href="'+url_pdf+'" class="btn btn-success" role="button" target="_blank" download>Descargar PDF</a></div>';
                    out += '<div class="col-3"><a href="'+url_xml+'" class="btn btn-success" role="button" target="_blank" download>Descargar XML</a></div>';
                    out += '</div>';
                    out += '<br>';
                    out += '<button type="button" class="btn btn-primary" onclick="cancelar()">Salir</button>';
                }
                else{
                    out = '<div class="container">';
                    out += '<div class="row">';
                    out += '<div class="col-auto error_title"><h5>Error al timbrar la factura</h5></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><p>' + data.msg + '</p></div>';
                    out += '</div>';
                    out += '<br>';
                    out += '<br>';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><p><b><i><h3>Comuníquese con DermaGlobal </h3></i></b></p></div>';
                    out += '</div>';
                    out += '<br>';
                    out += '<br>';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><button type="button" class="btn btn-primary" onclick="cancelar()">Salir</button></div>';
                    out += '</div>';
                    out += '</div>';

                    out = '<style>' +
                        '.container{display: flex; flex-direction: column; align-items: center; justify-content: center;}' +
                        '.error_title{color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 10px; margin: 10px;}' +
                        'h5{font-size: 1.25rem; margin-top: 0; margin-bottom: 0;}' +
                        'p{font-size: 1rem; margin-top: 0; margin-bottom: 0;}' +
                        '.row{margin: 10px;}' +
                        '</style>' + out;
                }

                contenedor.innerHTML = out;
            },
            error: function ( xhr, errorType, exception ) {
                var errorMessage = exception || xhr.statusText;
                console.log(xhr);
                mostrarAlerta('alerta_busqueda', 'Error: ' + errorMessage, 'danger');
            }
        });
    }

    /* Regresa al index de la pagina
    *
    * */
    function cancelar(){
        window.location = '<?= DOL_URL_ROOT . '/custom/autofactura_ticket/index.php'; ?>';
    }
</script>
</html>
