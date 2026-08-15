<?php
require '../../main.inc.php';
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
    <link rel="stylesheet" href="./style.css" />
    <link rel="stylesheet" href="<?= DOL_URL_ROOT . '/includes/jquery/plugins/jnotify/jquery.jnotify.min.js';?>"/>
    <link rel="icon" type="image/x-icon" href="<?= DOL_URL_ROOT ?>/theme/eldy/img/favicon.ico">
    <title>Autofacturación <?= $mysoc->nom ?></title>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-lg  p-3 mb-5 rounded-bottom">
    <a class="navbar-brand" href="<?= DOL_URL_ROOT . '/custom/autofactura/index.php'; ?>">
        <img src="<?= DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/'.$mysoc->logo) ?>" class="d-inline-block align-top rounded" alt="">
    </a>
    <!-- <?= var_dump($mysoc) ?> -->
    <div class="collapse navbar-collapse" id="navbarText">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item active">
                <a class="nav-link" href="<?= DOL_URL_ROOT . '/custom/autofactura/index.php'; ?>"><h4>Autofactura <?= $mysoc->nom ?></h4> <span class="sr-only">(current)</span></a>
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
                    <div class="form-row">
                        <h5>Busca tu factura por timbrar</h5>
                    </div>
                    <br>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="clave_factura">Clave de factura</label>
                            <input type="text" class="form-control" name="clave_factura" id="clave_factura" value="" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="monto">Monto</label>
                            <input type="number" class="form-control" id="monto" name="monto" step="any" required>
                        </div>
                    </div>
                    <div class="row">
                        <label class="col text-danger"><strong id="msg_noresult" ></strong></label>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="busqueda()">Buscar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="container border border-primary rounded"></div>

</body>
<!-- Footer -->
<footer class="page-footer font-small blue">

    <!-- Copyright -->
    <div class="footer-copyright text-center py-3">© <?php echo date('Y'); ?> Copyright</div>
    <!-- Copyright -->

</footer>
<!-- Footer -->
<script type="text/javascript" src="<?= DOL_URL_ROOT . '/includes/jquery/js/jquery.min.js'; ?>"></script>
<script src="<?= DOL_URL_ROOT . '/includes/jquery/plugins/jnotify/jquery.jnotify.min.js';?>"></script>
<script type="text/javascript">
    function busqueda(){
        let url = '<?= DOL_URL_ROOT.'/custom/autofactura/search.php'; ?>';
        let clave_factura = $('#clave_factura').val();
        let monto = $('#monto').val();
        if(monto === '' || clave_factura === ''){//Regresa si faltan valores, para ahorrar tiempo
            let msg = document.getElementById("msg_noresult");
            msg.innerHTML = 'No se encontraron resultados';
            return;
        }
        let data = {
            clave_factura: clave_factura,
            monto : monto
        };
        let contenedor = document.getElementById('resultado');
        $.ajax({
            type:'POST',
            url: url,
            data: data,
            dataType: 'json',
            success:function(data){
                let out = '';
                let msg = document.getElementById("msg_noresult");
                if(data.error === undefined){
                    console.log(data);
                    msg.innerText = '';
                    contenedor.innerHTML = '';
                    let resultado = data[0];
                    let uso_cfdi = data[1];
                    let domicilio = data[3];
                    let estados = data[4];
                    let sel_formpago = data[5][0].formpagcfdi;
                    let sel_usocfdi = data[5][0].usocfdi;
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
                    //--------Limpiamos variables-------------
                    let incompleto = 0;
                    if( ref === undefined || ref === '') {ref = ''; incompleto++;}
                    if( nombre === undefined || nombre === '') {nombre = 'Sin llenar'; incompleto++;}
                    if( sel_formpago === undefined || sel_formpago === '') {sel_formpago = 'Sin llenar'; incompleto++;}
                    if( correo === undefined ) {correo = 'Sin llenar'; incompleto++;}
                    if( sel_usocfdi === undefined || sel_usocfdi === '') {sel_usocfdi = 'Sin llenar'; incompleto++;}
                    if( rfc === undefined || rfc === '') {rfc = 'Sin llenar'; incompleto++;}
                    if( cp === null || cp === '') {cp = 'Sin llenar'; incompleto++;}

                    if( municipio === undefined || municipio === '') {municipio = 'Sin llenar'; incompleto++;}
                    if( colonia === undefined || colonia === '') {colonia = 'Sin llenar'; incompleto++;}
                    if( calle === undefined || calle === '') {calle = 'Sin llenar'; incompleto++;}
                    if( numero_int === undefined ) {numero_int = 'Sin llenar';}
                    if( numero_ext === undefined ) {numero_ext = 'Sin llenar'; incompleto++;}
                    if( fk_estado === undefined || fk_estado === '') {fk_estado = 'Sin llenar'; incompleto++;}
                    out = '<h5>Datos de facturación:</h5><br><div class="container">';
                    out += '<div class="row">';
                    out += '<div class="col"><p>Nombre : <strong>'+nombre+'</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col"><p>Clave de factura : <strong>'+ref+'</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col"><p>Monto : <strong>'+monto+'</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col"><p><strong>Domicilio fiscal</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    let estado_sel = fk_estado;
                    for(let x = 0; x < estados.length; x++){
                        let estado = estados[x];
                        if(fk_estado === estado.rowid){
                            estado_sel =  estado.nom ;
                            break;
                        }
                    }
                    out += '<div class="col-auto"><p>Estado : <strong>'+estado_sel+'</strong></div>';
                    out += '<div class="col-auto"><p>Municipio : <strong>'+municipio+'</strong></div>';
                    out += '<div class="col-auto"><p>Codigo Postal : <strong>'+cp+'</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><p>Colonia : <strong>'+colonia+'</strong></div>';
                    out += '<div class="col-auto"><p>Calle : <strong>'+calle+'</strong></div>';
                    out += '<div class="col-auto"><p>Número int. : <strong>'+numero_int+'</strong></div>';
                    out += '<div class="col-auto"><p>Número ext. : <strong>'+numero_ext+'</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    let metodo_len = metodo_pago.length;
                    let metodo_clave_array = [];
                    let metodo_label_array = [];
                    let bandera = true;
                    let iterador_clave = 0;
                    let iterador_label = 0;
                    let metodo_array;
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
                            out += '<div class="col-auto"><p>Método de pago CFDI : <strong>'+metodo_label_array[x]+'</strong></div>';
                            break;
                        }
                    }
                    let u_len = uso_cfdi.length;
                    for(let u = 0; u < u_len; u++){
                        let opcion = uso_cfdi[u];
                        if(opcion.code === sel_usocfdi){
                            out += '<div class="col-auto"><p>Uso de CFDI : <strong>'+opcion.label+'</strong></div>';
                            break;
                        }
                    }

                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><p>RFC : <strong>'+rfc+'</strong></div>';
                    out += '<div class="col-auto"><p>Correo : <strong>'+correo+'</strong></div>';
                    out += '</div>';
                    out += '<form>';
                    out += '<input type="hidden" name="clave_factura" id="clave_factura" value="'+ref+'">';
                    out += '<input type="hidden" name="monto" id="monto" value="'+monto+'">';
                    out += '<input type="hidden" name="fk_facture" id="fk_facture" value="'+fk_facture+'">';
                    out += '<input type="hidden" name="fk_socid" id="fk_socid" value="'+fk_soc+'">';
                    out += '<input type="hidden" name="email" id="email" value="'+correo+'">';
                    out += '<form>';
                    if(incompleto === 0){
                        out += '<div class="row">';
                        out += '<div class="col-auto"><p><strong>¿Los datos son correctos?</strong></div>';
                        out += '</div>';
                        out += '<div class="row">';
                        out += '<div class="col-auto"><button type="button" class="btn btn-success" onclick="timbrar();">Sí, timbrar</button></div>';
                    }
                    else{
                        out += '<div class="row">';
                        out += '<div class="col-auto"><p><strong>Datos incompletos, favor de completarlos</strong></div>';
                        out += '</div>';
                        out += '<div class="row">';
                    }
                    out += '<div class="col-auto"><button type="button" class="btn btn-warning" onclick="editar_datos();">Editar datos</button></div>';
                    out += '</div>';
                    out += '</div>';

                }
                else if(data.error === 1){
                    msg.innerHTML = 'Fuera de fecha de timbrado';
                }
                else if(data.error === 2){
                    let path = data.ref + '/' + data.uuid;
                    let url_pdf = '<?= DOL_URL_ROOT . "/document.php?modulepart=facture&file="; ?>'+path+'.pdf';
                    let url_xml = '<?= DOL_URL_ROOT . "/document.php?modulepart=facture&file="; ?>'+path+'.xml';
                    contenedor.innerHTML = '';
                    out = '<h5>Factura timbrada previamente</h5>';
                    out += '<br>';
                    out += '<p>A continuacion puedes volver a descargar los archivos de la factura '+data.ref+'</p>';
                    out += '<br>';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><a href="'+url_pdf+'" class="btn btn-success" role="button" target="_blank" download>Descargar PDF</a></div>';
                    out += '<div class="col-auto"><a href="'+url_xml+'" class="btn btn-success" role="button" target="_blank" download>Descargar XML</a></div>';
                    out += '</div>';
                    out += '<br>';
                    out += '<button type="button" class="btn btn-primary" onclick="cancelar()">Salir</button>';
                }
                else{
                    msg.innerHTML = 'No se encontraron resultados';
                }
                contenedor.innerHTML += out;
            },
            error: function ( xhr, errorType, exception ) { //Triggered if an error communicating with server
                var errorMessage = exception || xhr.statusText; //If exception null, then default to xhr.statusText
                console.log(xhr);
                alert( "Error: " + errorMessage );
            }
        });
    }

    function editar_datos(){
        let url = '<?= DOL_URL_ROOT.'/custom/autofactura/search.php'; ?>';
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
                    let sel_formpago = data[5][0].formpagcfdi;
                    let sel_usocfdi = data[5][0].usocfdi;
                    let temp = String(data[2].param);
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
                    let correo = resultado.correo;
                    let cp = resultado.cp;
                    let municipio = domicilio.receptor_delompio;
                    let colonia = domicilio.receptor_colonia;
                    let calle = domicilio.receptor_calle;
                    let numero_int = domicilio.receptor_noint;
                    let numero_ext = domicilio.receptor_noext;
                    //--------Limpiamos variables-------------
                    if( ref === undefined ) ref = '';
                    if( nombre === undefined ) nombre = '';
                    if( m_pago === undefined ) m_pago = '';
                    if( correo === undefined ) correo = '';
                    if( cfdi === undefined ) cfdi = '';
                    if( rfc === undefined ) rfc = '';
                    if( cp === undefined ) cp = '';

                    if( municipio === undefined ) municipio = '';
                    if( colonia === undefined ) colonia = '';
                    if( calle === undefined ) calle = '';
                    if( numero_int === undefined ) numero_int = '';
                    if( numero_ext === undefined ) numero_ext = '';
                    out += '<form name="form_timbra" id="form_timbra" method="POST" >';
                    out += '<br>';
                    out+= '<div class="form-group row">' +
                        '<div class="col-4">' +
                        '<h5>Datos de facturación:</h5>' +
                        '</div>' +
                        '</div>' +
                        '<div class="form-group row">' +
                        '<label for="ref" class="col-sm-2 col-form-label">Clave</label>' +
                        '<div class="col-sm-2">' +
                        '<input type="text" class="form-control" value="'+ref+'" disabled>' +
                        '</div>' +
                        '</div>';
                    out+= '<div class="form-group row">' +
                        '<label for="nombre" class="col-sm-2 col-form-label">Nombre</label>' +
                        '<div class="col-sm-5">' +
                        '<input type="text" class="form-control" id="nombre" name="nombre" value="'+nombre+'" required>' +
                        '<input type="hidden" id="nombre_anterior" name="nombre_anterior" value="'+nombre+'">' +
                        '</div>' +
                        '</div>';
                    out+= '<div class="form-group row">' +
                        '<label for="municipio" class="col-sm-2 col-form-label">Domicilio fiscal</label>' +
                        '</div>';
                    out+= '<div class="form-group row">' +
                        '<label class="col-sm-2 col-form-label"></label>' +
                        '<div class="col-auto">' +
                        '<label for="municipio">Estado</label>' +
                        '<select class="form-control" id="estado" name="estado" required>';
                    for(let x = 0; x < estados.length; x++){
                        let estado = estados[x];
                        if(resultado.fk_estado === estado.rowid)
                            out+= '<option value ="'+estado.rowid+'" selected>'+estado.code_departement+' - '+ estado.nom +'</option>';
                        else
                            out+= '<option value ="'+estado.rowid+'">'+estado.code_departement+' - '+ estado.nom +'</option>';
                    }
                    out+= '</select>' +
                        '</div>' +
                        '</div>';
                    out += '<div class="form-group row">' +
                        '<label class="col-sm-2 col-form-label"></label>' +
                        '<div class="col-auto">' +
                        '<label for="municipio">Municipio</label>' +
                        '<input type="text" class="form-control" id="municipio" name="municipio" value="'+municipio+'" required>' +
                        '</div>' +
                        '</div>' +
                        '<div class="form-group row">' +
                        '<label class="col-sm-2 col-form-label"></label>' +
                        '<div class="col-auto">' +
                        '<label for="calle">Código Postal</label>' +
                        '<input type="number" class="form-control" id="cp" name="cp" value="'+cp+'" required>' +
                        '</div>' +
                        '</div>';
                    out+= '<div class="form-group row">' +
                        '<label class="col-sm-2 col-form-label"></label>' +
                        '<div class="col-auto">' +
                        '<label for="colonia">Colonia</label>' +
                        '<input type="text" class="form-control" id="colonia" name="colonia" value="'+colonia+'" required>' +
                        '</div>' +
                        '<div class="col-auto">' +
                        '<label for="calle">Calle</label>' +
                        '<input type="text" class="form-control" id="calle" name="calle" value="'+calle+'" required>' +
                        '</div>' +
                        '</div>';
                    out+= '<div class="form-group row">' +
                        '<label class="col-sm-2 col-form-label"></label>' +
                        '<div class="col-auto">' +
                        '<label for="numero_int">Número int.</label>' +
                        '<input type="text" class="form-control" id="numero_int" name="numero_int" value="'+numero_int+'">' +
                        '</div>' +
                        '<div class="col-auto">' +
                        '<label for="numero_ext">Número ext.</label>' +
                        '<input type="text" class="form-control" id="numero_ext" name="numero_ext" value="'+numero_ext+'" required>' +
                        '</div>' +
                        '</div>';
                    out+= '<div class="form-group row">' +
                        '<label for="metodo_cfdi" class="col-sm-2 col-form-label">Método de pago CFDI</label>' +
                        '<div class="col-sm-4">' +
                        '<select class="form-control" id="metodo_cfdi" name="metodo_cfdi" required>';
                    let metodo_len = metodo_pago.length;
                    let metodo_clave_array = [];
                    let metodo_label_array = [];
                    let bandera = true;
                    let iterador_clave = 0;
                    let iterador_label = 0;
                    let metodo_array;
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
                        if(metodo_clave_array[x] === sel_formpago)
                            out += '<option value="'+ metodo_clave_array[x] +'" selected>'+ metodo_label_array[x] +'</opcion>';
                        else
                            out += '<option value="'+ metodo_clave_array[x] +'">'+ metodo_label_array[x] +'</opcion>';
                    }
                    out += '</select>' +
                        '</div>' +
                        '</div>';
                    out+= '<div class="form-group row">' +
                        '<label for="uso_cfdi" class="col-sm-2 col-form-label">Uso de CFDI</label>' +
                        '<div class="col-sm-4">' +
                        '<select class="form-control" id="uso_cfdi" name="uso_cfdi" required>';
                    let u_len = uso_cfdi.length;
                    for(let u = 0; u < u_len; u++){
                        let opcion = uso_cfdi[u];
                        if(opcion.code === sel_usocfdi)
                            out += '<option value="'+opcion.code+'" selected>'+opcion.label+'</opcion>';
                        else
                            out += '<option value="'+opcion.code+'">'+opcion.label+'</opcion>';
                    }
                    out += '</select>' +
                        '</div>' +
                        '</div>';
                    out+= '<div class="form-group row">' +
                        '<label for="rfc" class="col-sm-2 col-form-label">R.F.C.</label>' +
                        '<div class="col-sm-2">' +
                        '<input type="text" class="form-control" id="rfc" name="rfc" value="'+rfc+'" required>' +
                        '</div>' +
                        '</div>';
                    out+= '<div class="form-group row">' +
                        '<label for="cp" class="col-sm-2 col-form-label">Correo</label>' +
                        '<div class="col-sm-4">' +
                        '<input type="email" class="form-control" id="correo" name="correo" value="'+correo+'" required>' +
                        '</div>' +
                        '</div>';
                    out+= '<div class="form-row align-items-center">' +
                        '<label for="monto" class="col-sm-2 col-form-label">Monto</label>' +
                        '<div class="col-sm-2">' +
                        '<input type="number" class="form-control" id="monto" name="monto" value="'+monto+'" disabled required>' +
                        '</div>' +
                        '</div>';
                    out += '<br>'
                    out+= '<div class="form-row align-items-center">' +
                        '<div class="col-sm-2">' +
                        '<input type="hidden" id="fk_facture" name = "fk_facture" value="'+fk_facture+'">' +
                        '<input type="hidden" id="ref" name = "ref" value="'+ref+'">' +
                        '<input type="hidden" id="fk_soc" name = "fk_soc" value="'+fk_soc+'">' +
                        '<input type="hidden" id="rfc" name = "rfc" value="'+rfc+'">' +
                        '<input type="hidden" id="monto" name = "monto" value="'+monto+'">' +
                        '</div>' +
                        '<div class="col-auto">' +
                        '<button type="button" class="col-auto btn btn-success" onclick="grabar_datos()">Grabar datos</button>' +
                        '</div>' +
                        '<div class="col-auto">' +
                        '<button type="button" class="col-auto btn btn-danger" onclick="cancelar()">Cancelar</button>' +
                        '</div>' +
                        '</div>';
                    out += '<br>'
                    out += '</form>';


                }
                else{
                }
                contenedor.innerHTML += out;
            },
            error: function ( xhr, errorType, exception ) { //Triggered if an error communicating with server
                var errorMessage = exception || xhr.statusText; //If exception null, then default to xhr.statusText
                console.log(xhr);
                alert( "Error: " + errorMessage );
            }
        });
    }

    function grabar_datos(){
        let url = '<?= DOL_URL_ROOT."/custom/autofactura/grabar_datos.php"; ?>';
        let calle = $('#calle').val();
        let colonia = $('#colonia').val();
        let correo = $('#correo').val();
        let cp = $('#cp').val();
        let estado = $('#estado').val();
        let fk_facture = $('#fk_facture').val();
        let fk_soc = $('#fk_soc').val();
        let metodo_cfdi = $('#metodo_cfdi').val();
        let municipio = $('#municipio').val();
        let nombre = $('#nombre').val();
        let nombre_anterior = $('#nombre_anterior').val();
        let numero_ext = $('#numero_ext').val();
        let numero_int = $('#numero_int').val();
        let ref = $('#ref').val();
        let rfc = $('#rfc').val();
        let rfc_anterior = $('#rfc_anterior').val();
        let uso_cfdi = $('#uso_cfdi').val();
        let monto = $('#monto').val();

        if(nombre === ''){ alert('Hay campos sin rellenar'); return; }
        if(municipio === ''){ alert('Hay campos sin rellenar'); return; }
        if(cp === ''){ alert('Hay campos sin rellenar'); return; }
        if(colonia === ''){ alert('Hay campos sin rellenar'); return; }
        if(calle === ''){ alert('Hay campos sin rellenar'); return; }
        if(rfc === ''){ alert('Hay campos sin rellenar'); return; }
        if(correo === ''){ alert('Hay campos sin rellenar'); return; }
        let data = {
            'calle' : calle,
            'colonia' : colonia,
            'correo' : correo,
            'cp' : cp,
            'estado' : estado,
            'fk_facture' : fk_facture,
            'fk_soc' : fk_soc,
            'metodo_cfdi' : metodo_cfdi,
            'municipio' : municipio,
            'nombre' : nombre,
            'nombre_anterior' : nombre_anterior,
            'numero_ext' : numero_ext,
            'numero_int' : numero_int,
            'ref' : ref,
            'rfc' : rfc,
            'rfc_anterior' : rfc_anterior,
            'uso_cfdi' : uso_cfdi,
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
                let response = data.response; // 0  if OK, < 0 if error
                let post = data.post;
                let params = data.params;
                if(response === 0){
                    // contenedor.innerHTML = '';
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
                    out += '<div class="col-auto"><p>Estado : <strong>'+post.estado+'</strong></div>';
                    out += '<div class="col-auto"><p>Municipio : <strong>'+post.municipio+'</strong></div>';
                    out += '<div class="col-auto"><p>Codigo Postal : <strong>'+post.cp+'</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><p>Colonia : <strong>'+post.colonia+'</strong></div>';
                    out += '<div class="col-auto"><p>Calle : <strong>'+post.calle+'</strong></div>';
                    out += '<div class="col-auto"><p>Número int. : <strong>'+post.numero_int+'</strong></div>';
                    out += '<div class="col-auto"><p>Número ext. : <strong>'+post.numero_ext+'</strong></div>';
                    out += '</div>';
                    out += '<div class="row">';
                    out += '<div class="col-auto"><p>Método de pago CFDI : <strong>'+post.metodo_cfdi+'</strong></div>';
                    out += '<div class="col-auto"><p>Uso de CFDI : <strong>'+post.uso_cfdi+'</strong></div>';
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
                else{
                    alert('Error en la actualizacion de los datos');
                }
            },
            error: function ( xhr, errorType, exception ) { //Triggered if an error communicating with server
                var errorMessage = exception || xhr.statusText; //If exception null, then default to xhr.statusText
                console.log(xhr);
                alert( "Error: " + errorMessage );
            }
        });
    }

    function timbrar(){
        let url = '<?= DOL_URL_ROOT . '/custom/autofactura/genera_cfdi.php';?>';
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
                /*
                * data.error    = 0 if ok ; > 0 if error
                * */
                let out = '';
                if(data.error === 0){
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
                    out = data.msg_email;
                    out += '<br>';
                    out += '<button type="button" class="btn btn-primary" onclick="cancelar()">Salir</button>';
                }

                contenedor.innerHTML = out;
            },
            error: function ( xhr, errorType, exception ) { //Triggered if an error communicating with server
                var errorMessage = exception || xhr.statusText; //If exception null, then default to xhr.statusText
                console.log(xhr);
                alert( "Error: " + errorMessage );
            }
        });
    }

    /* Regresa al index de la pagina
    *
    * */
    function cancelar(){
        window.location = '<?= DOL_URL_ROOT . '/custom/autofactura/index.php'; ?>';
    }
</script>
</html>
