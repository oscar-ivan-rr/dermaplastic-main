<?php error_reporting(0); ?>
<script type="text/javascript">
    function cambiaPago(id){
        var remolque_eliminar = document.getElementById("estado_uuid_rel"+id).value;
        if(remolque_eliminar == 0){
            document.getElementById("estado_uuid_rel"+id).value = 1;
        }else{
            document.getElementById("estado_uuid_rel"+id).value = 0;
        }
    }

    function agregarPago(){
        var id = document.getElementById("lista_pagos").value;

        if(id == -1){
            alert("Debes seleccionar un Pago para agregarlo a CFDI Relacionados");
        }else{            
            var nuevoPago = document.createElement('tr');
            var info = id.split("<>");
            console.log(info);
            var num_pagos = document.getElementById("num_pagos_rel").value;

            if(num_pagos == 0){
                document.getElementById("lista_cfdi_rel").style.display = "block";
            }

            num_pagos = parseInt(num_pagos) + parseInt(1);
            document.getElementById("num_pagos_rel").value = num_pagos;

            nuevoPago.innerHTML = "<tr>";
            nuevoPago.innerHTML += "<td><input type='checkbox' name='btn_del_pago_rel[]' id='btn_del_pago_rel"+num_pagos+"' onchange='cambiaPago("+num_pagos+")'></td>";
            nuevoPago.innerHTML += "<input type='hidden' name='estado_uuid_rel[]' id='estado_uuid_rel"+num_pagos+"' value='0'>";
            nuevoPago.innerHTML += "<input type='hidden' name='fk_pago_rel[]' id='fk_pago_rel"+num_pagos+"' value='"+info[0]+"'>";
            nuevoPago.innerHTML += "<input type='hidden' name='uuid_rel[]' id='uuid_rel"+num_pagos+"' value='"+info[1]+"'>";
            nuevoPago.innerHTML += "<td>"+info[1]+"</td>";
            nuevoPago.innerHTML += "</tr>";

            document.getElementById("lista_cfdi_rel").appendChild(nuevoPago);

            x = document.getElementById("lista_pagos");
            x.remove(x.selectedIndex);
            return false;
        }      
        return false;
    }
</script>