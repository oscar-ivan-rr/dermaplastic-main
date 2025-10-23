<?php
error_reporting(0);
/* Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}
?>

<script type="text/javascript">
    function cambiarFiltro(valor){
        document.getElementById("ban_filtro").value = valor;
    }

    function cambiaEstadoProd(id){
        if(document.getElementById("rowid_prod_del"+id).value == 0){
            document.getElementById("rowid_prod_del"+id).value = 1;
        }else{
            document.getElementById("rowid_prod_del"+id).value = 0;
        }
    }

    function agregarLista(){
        var id_producto    = document.getElementById("productos_sel").value;
        var label_producto = document.getElementById('productos_sel').options[document.getElementById('productos_sel').selectedIndex].dataset.label;
        var num_productos = document.getElementById("num_productos").value;
        var existe = 0;

        if(num_productos > 0){
            for(i = 0; i < num_productos; i++){
                console.log("rowid_prod :: "+document.getElementById("rowid_prod"+i).value);
                if(document.getElementById("rowid_prod"+i).value == id_producto){
                    existe = 1;
                    alert("El Producto ya esta en la lista.");
                    break;
                }
            }
        }

        if(existe == 0){
            if(num_productos == 0){
                document.getElementById("list_prod_add").style.display = "block";
            }

            var prodAdd = document.createElement('tr');

            prodAdd.innerHTML = "<tr>";
            prodAdd.innerHTML += "<td><input type='checkbox' name='btn_del_prod[]' id='btn_del_prod"+num_productos+"' onchange='cambiaEstadoProd("+num_productos+")'></td>";
            prodAdd.innerHTML += "<input type='hidden' name='rowid_prod_del[]' id='rowid_prod_del"+num_productos+"' value='0'>";
            prodAdd.innerHTML += "<input type='hidden' name='rowid_prod[]' id='rowid_prod"+num_productos+"' value='"+id_producto+"'>";
            prodAdd.innerHTML += "<td>"+label_producto+"</td>";
            prodAdd.innerHTML += "</tr>";

            num_productos = parseInt(num_productos) + parseInt(1);
            document.getElementById("num_productos").value = num_productos;

            document.getElementById("list_prod_add").appendChild(prodAdd);
        }

        return false;
    }

    jQuery(document).ready(function() {


        jQuery("#catalogo_sel").change(function() {
            datos =
                {
                    action: "update_info_doli",
                    catalogo: $("#catalogo_sel").val(),
                    token: $("#token").val()
                };

            $("#nuevo_valor_noide").hide("fast");

            if($("#catalogo_sel").val() != 3){
                $("#lista_valores").show("fast");

                $.ajax({
                    async: true,
                    type: "POST",
                    dataType: "html",
                    contentType: "application/x-www-form-urlencoded",
                    url:"../js/obtener_informacion.php",
                    data:datos,
                    success:function (data){
                        console.log(data);

                        if(data != null){
                            var obj = JSON.parse(data);
                            console.log(obj);

                            if(obj != null){
                                $("#nuevo_valor").empty();
                                $("#nuevo_valor").append("<option value='-1'>Seleccione Nuevo Valor</option>");

                                for (x of obj) {
                                    if(x.clave != "" && x.etiqueta != ""){
                                        $("#nuevo_valor").append("<option value='" + x.clave + "'>" +  x.clave + " - " + x.etiqueta + "</option>");
                                    }
                                }
                            }else{
                                $("#nuevo_valor").empty();
                            }
                        }else{
                            $("#nuevo_valor").empty();
                        }
                    },
                    error:function (data){
                        alert("error "+data);
                    }
                });
            }else{
                $("#lista_valores").hide("fast");
                $("#nuevo_valor_noide").show("fast");
            }

            return false;
        });
    });
</script>