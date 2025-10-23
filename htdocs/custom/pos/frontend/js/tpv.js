var decimals = 4;
var round_factor = 10**decimals;
console.log(round_factor);
var discount_percent_limit = 0;
var round_factor = 10000;
var Ticket = jQuery.Class({

	init: function() 
	{
		this.id = 0;
		this.payment_type = 0;
		this.type = 0;
		this.discount_percent = 0;
		this.discount_qty = 0;
		this.lines = new Array();
		this.oldproducts = new Array();
		this.total = 0;
		this.customerpay = 0;
		this.difpayment = 0;
		this.customerId = 0;
		this.proyectId = 0;
		this.employeeId = 0;
		this.idsource = 0;
		this.state = 1; // 0=Draft, 1=To Invoice , 2=Invoiced, 3=No invoiceble
		this.id_place = 0;
		this.note = "";
		this.mode=0;
		this.points=0;
		this.idCoupon = 0;
		this.ret_points = 0;
		this.id_temporal = 1000000;
		this.cont = 1;
		this.customerpay1 = 0;
		this.customerpay2 = 0;
		this.customerpay3 = 0;
		this.customerpay4 = 0;
		this.customerpay5 = 0;
		this.NoCredit = 0;
		this.auxPaymentcus=0;
		this.newByDifference=0;
		
		this.rc_products = {};
		this.infoCustomer_ref = '';

		//this.isPropal=0;
	},
	agregarProspecto: function(){

		var line = new TicketLine();

		// Linea original: line.setLineByIdProducts(idProduct);
		//line.idProduct = this.id_temporal++;
		//line.ref = 'PROSPECTO' + this.cont++;
		line.label = $("#producto_etiqueta").val();
		/*line.description = "PROS: " + $("#producto_etiqueta").val();
		/line.localtax1_tx = 0;
		/line.localtax2_tx = 0;
		line.tva_tx = 16;
		line.idTicket = _TPV.ticket.id;
		line.price_min_ttc =0;
		line.price_base_type = 'TTC';
		line.fk_product_type = 0;
		line.remise_percent_global = 0;
		line.diff_price = 0;*/

		if(typeof($("#producto_cantidad").val()) == 'undefined' || $("#producto_cantidad").val() == '' || $("#producto_cantidad").val() <= 0)
			line.cant = 1;
		else line.cant = $("#producto_cantidad").val();

		/*if(typeof($("#producto_precio").val()) == 'undefined' || $("#producto_precio").val() == '' || $("#producto_precio").val() <= 0)
			line.price_ttc = 0;
		else line.price_ttc = $("#producto_precio").val();

		line.price = line.price_ttc / ( 1 + line.tva_tx / 100);
		line.total = line.price_ttc * line.cant;
		line.total_ttc = line.price_ttc * line.cant;
		line.total_ttc_without_discount = line.price_ttc * line.cant;*/
		/*var prospecto=new Object();
		prospecto.qty=$("#producto_cantidad").val();
		prospecto.label=$("#producto_etiqueta").val();console.log(prospecto);*/
		//Se cea en la tabla prospectos
		var resultado = ajaxDataSend('asignarLabel',line);

		/*this.lines.push(line);

		this.total = parseFloat(this.total) + parseFloat(line.total_ttc);

		$('#totalTicket').html(displayPrice(this.total));
		$('#totalTicketinv').html(displayPrice(this.total));

		// Linea original: this.setLine(idProduct,line);
		if(this.lines.length==1)
			this.setButtonState(true);

		$('#tablaTicket > tbody:last').prepend(line.getHtml());*/

		//Reestrablecer los valores del formulario
		document.getElementById("producto_etiqueta").value= '';
		//document.getElementById("producto_precio").value = '';
		document.getElementById("producto_cantidad").value = '';
		$("#boton-prospecto").hide();
		/////
	},
	setButtonState:function(hasTicket)
	{
		$('#ls_tpv_checkall').attr("checked", 0);
		ls_tpv_switch_all_checkboxes();
		if(!hasTicket)
		{
			$('#btnReturnTicket').hide();
			$('#btnTicketRef').hide();$('#btnTicketRef').html('');
			$('#btnSaveTicket').hide();
			$('#btnFreight').hide();
			$('#btnAddDiscount').hide();
			$('#btnOkTicket').hide();
			$('#btnTicketNote').hide();
			$('#alertfaclim').hide();

			$('#btnTicketHomeDelivery').hide();
			$('#btnTicketApartado').hide();$('#btnTicketApartado').css('background-color','inherit');
			$('#btnTicketSendFront').hide();$('#btnTicketSendFront').css('background-color','inherit');
			$('#btnReloadTickett').hide();
			$('#btnTicketAskTransfer').hide();
			$('#btnTicketAskAbroad').hide();
		}
		else
		{
			//if(_TPV.ticket.customerId != "8" && _TPV.ticket.state == "2")
			if (
				   parseInt(_TPV.ticket.state) == 1 
				|| _TPV.ticket.esCredito() 
				|| parseFloat(_TPV.ticket.difpayment)==0 
				|| (parseFloat(_TPV.ticket.difpayment) > 0 && parseFloat(_TPV.ticket.difpayment) != parseFloat(_TPV.ticket.total)))			
				$('#btnReturnTicket').show();
			else
				$('#btnReturnTicket').hide();
			//$('#btnTicketRef').hide();$('#btnTicketRef').css('background-color','inherit');
			if(_TPV.ticketState==0 && _TPV.ticket.type!=1)
			{
				$('#btnSaveTicket').show();
				$('#btnFreight').show();
				$('#btnAddDiscount').show();
			}
			if(_TPV.ticket.NoCredit == 0)
				$('#btnOkTicket').show();
			$('#btnTicketNote').show()
			$('#btnReloadTickett').show();
			if (_TPV.ticket.state == 0 || _TPV.ticket.id==0) // Borrador o No Guardado
			{
				$('#btnTicketHomeDelivery').hide();
				$('#btnTicketAskTransfer').hide();
				$('#btnTicketSendFront').show();$('#btnTicketSendFront').css('background-color','inherit');
    	  		$('#btnTicketApartado').hide();$('#btnTicketApartado').css('background-color','inherit');
    	  		$('#btnTicketAskAbroad').hide();
			}
			else if (_TPV.ticket.state == 2) //Procesado
			{
				$('#btnTicketSendFront').show();$('#btnTicketSendFront').css('background-color','inherit');
    	  		if (_TPV.ticket.customerId != '8' || (parseFloat(_TPV.ticket.difpayment) == 0 && _TPV.ticket.type != 1))
    	  		{
					$('#btnTicketAskTransfer').show();
	    	  		$('#btnTicketApartado').show();$('#btnTicketApartado').css('background-color','inherit');
	    	  		$('#btnTicketAskAbroad').show();
	    	  		$('#btnTicketHomeDelivery').show();
    	  		}
    	  		// console.log(_TPV.ticket);
			}
			else // Cerrado(?)
			{
				//$('#btnTicketHomeDelivery').show();
				$('#btnTicketAskTransfer').show();
				$('#btnTicketSendFront').hide();$('#btnTicketSendFront').css('background-color','inherit');
    	  		$('#btnTicketApartado').show();$('#btnTicketApartado').css('background-color','inherit');
    	  		//$('#btnTicketAskAbroad').show();
			}
			
		}
	},
	checkApplyQuantity:function(idProduct,cant){
		var lineproduct = null;
		if(typeof _TPV.ticket.oldproducts!='undefined' && _TPV.ticket.oldproducts.length>0)
		{
			for(var i=0;i<_TPV.ticket.oldproducts.length;i++)
			{
				if(_TPV.ticket.oldproducts[i]['idProduct']==idProduct){
					lineproduct = _TPV.ticket.oldproducts[i];
					break;
				}
			}
			if(cant>=lineproduct.cant)
				return false;
		}
		return true;
	},
	checkExistReturnProduct:function(idProduct){
		
		if(typeof _TPV.ticket.oldproducts!='undefined' && _TPV.ticket.oldproducts.length>0)
		{
			for(var i=0;i<_TPV.ticket.oldproducts.length;i++)
			{
				if(_TPV.ticket.oldproducts[i]['idProduct']==idProduct){
					return true;
				}
			}
		}
		return false;
	},
	newTicket: function(){
		this.init();
		this.customerId = _TPV.customerId;
		this.cashId = _TPV.cashId;
		if(_TPV.defaultConfig.customer.remise != "")
			this.discount_percent = _TPV.defaultConfig.customer.remise;
		else
			this.discount_percent = 0;
		_TPV.ticketState=0;
		$('#Customer_remise').html(_TPV.defaultConfig.customer.remise+'%');
		$('#infoCustomer_ref').val("");
		if(_TPV.defaultConfig.customer.proyectos.length == 1 && _TPV.defaultConfig.customer.proyectos[0].proyect == '&nbsp'){
			$('#infoProyectCustomer_').html(_TPV.defaultConfig.customer.proyectos[0].proyect);
			if(_TPV.defaultConfig.customer.limite == null) {
				_TPV.limite_de_credito = 0;
				_TPV.discount = 0;
			}
			else {
				_TPV.limite_de_credito = _TPV.defaultConfig.customer.limite;
				_TPV.discount = _TPV.defaultConfig.customer.remise;
			}
			if(_TPV.defaultConfig.customer.por_pagar == null)
				_TPV.restar_por_pagar = 0;
			else
				_TPV.restar_por_pagar = _TPV.defaultConfig.customer.por_pagar;
			$('#limite_c').html("$"+PriceCommas(parseInt(_TPV.limite_de_credito)));
			var resta = parseInt(_TPV.limite_de_credito)-parseInt(_TPV.restar_por_pagar);
						
			if (_TPV.ticket.customerId == 8)
			{
				resta = 0;
							
			}						
			if(resta == null)
				resta=0;
			$('#disponible_c').html("$"+PriceCommas(resta));
			if(resta >= 0) {
				$('#disponible_c').css("color", "rgb(34, 200, 34)");
				_TPV.ticket.NoCredit = 0;
			}
			else {
				$('#disponible_c').css("color", "#f44");
				_TPV.ticket.NoCredit = 1;
			}
			_TPV.ticket.proyectId=0;
		}
		_TPV.getDataCategories(0);
		this.setButtonState(false);
		
		$('#tablaTicket tbody tr').remove();
		$('#totalDiscount').html(displayPrice(0));
		$('#totalWdiscount').html(displayPrice(0));
		$('#totalTicket').html(displayPrice(0));
		$('#totalTicketinv').html(displayPrice(0));
		$('#totalPlace').html('');
		var result = ajaxDataSend('getNotes',0);
		if(result)
		{
			$('#totalNote_').html(result);
		}
		else{
			$('#totalNote_').html(0);
		}
		if(typeof _TPV.defaultConfig['customer']['name']!='undefined'){
			$('#infoCustomer').html(_TPV.defaultConfig['customer']['name']);
			$('#infoCustomer_').html(_TPV.defaultConfig['customer']['name']);
		}
		_TPV.points = _TPV.defaultConfig['customer']['points'];
		_TPV.coupon = _TPV.defaultConfig['customer']['coupon'];
		_TPV.activeIdProduct = 0;
		$('#info_product').hide();
		$('#payment_points').hide();
		$("#containerRestToPay").css("display", "none");
		hideLeftContent();
		if(_TPV.defaultConfig['terminal']['barcode'] == 1){
			$('#id_product_search').focus();
		}
		if (rc_openTicketsAlert)
		{
			var tres = JSON.parse(ajaxSend('getOpenTickets'));
			//console.log(tres);
			//console.log(tres['result']['anteriores']);
			//rc_alert_pending_tickets
			if (tres['result']['anteriores'] > 0 || tres['result']['hoy'] > 0)
			{
				var msg = 'AVISO: Tiene  ';
				if (tres['result']['hoy'] > 0)
				{
					msg += tres['result']['hoy']+' tickets SIN cerrar de hoy'
					if (tres['result']['anteriores'] > 0)
					{
						msg += ' y '
					}
					else
					{
						msg += '.'
					}
				}
				if (tres['result']['anteriores'] > 0)
				{
					msg += tres['result']['anteriores']+' tickets SIN cerrar de días anteriores.'
				}
				$('#rc_alert_pending_tickets_button').unbind();
				$('#rc_alert_pending_tickets_button').click(function(){$('#rc_alert_pending_tickets').dialog("close");return false;});
				$('#rc_alert_pending_tickets_p').html(msg);
				//$('#').show();
				$('#rc_alert_pending_tickets').dialog({ modal: true });
				$('#rc_alert_pending_tickets').dialog({width:440});

			}
		}
	},
	newTicketPlace: function(id_place){
		this.newTicket();
		$('#totalPlace').html(_TPV.places[id_place]);
		this.id_place = id_place;
		 showTicketContent();
		
	},
	setLine : function(idProduct, line){
		this.lines.push(line);
		if(this.lines.length==1)
			this.setButtonState(true);
	},
	setPlace : function(idPlace){
		this.id_place = idPlace;
	},
	getLine : function(idProduct){
		for (var i in this.lines)
		{
			if(this.lines[i]['idProduct']==idProduct)
				return this.lines[i];
		}
		return null;
	},
	getTotal : function(){
		
		return this.total.toFixed(decimals);;
	},
	calculeDiscountTotal: function(total_lines)
	{
		discount = total_lines - this.total;
		var pricediscount = new Number(discount);
		pricediscount = pricediscount.toFixed(decimals);
		$('#totalDiscount').html(displayPrice(pricediscount));
		var total = new Number(this.total);
		total = total.toFixed(decimals);
		$('#totalTicket').html(displayPrice(total));
		$('#totalTicketinv').html(displayPrice(total));
	},
	calculeTotal : function(){
		var sum = 0;
		var sum2 = 0;
		discount = 0;
				
		if(this.discount_percent!=null)
		{
			discount = this.discount_percent;
		}
		for (var i in this.lines)
		{
			var line = this.lines[i];
			if(this.type == 0 && parseFloat(line["remise_percent_global"]) != parseFloat(discount))
				{
					line["remise_percent_global"]=discount;
					if(!line["price_base_type"])
						line["price_base_type"] = "TTC";
					var result = ajaxDataSend('calculePrice',line);
					this.lines[i].total = result["total_ttc"];
					this.lines[i].total_ttc_without_discount = result["total_ttc_without_discount"];
					sum = parseFloat(sum) + Math.round(parseFloat(result["total_ttc"])*round_factor)/round_factor;
					sum2 = parseFloat(sum2) + Math.round(parseFloat(result["total_ttc_without_discount"])*round_factor)/round_factor;
				}
			else
				{
					sum = parseFloat(sum) + Math.round(parseFloat(this.lines[i].total)*round_factor)/round_factor;
					sum2 = parseFloat(sum2) + Math.round(parseFloat(this.lines[i].total_ttc_without_discount)*round_factor)/round_factor;
				}
		}
		
		this.total=Math.round(sum*round_factor)/round_factor ;
		sum2 = Math.round(sum2*round_factor)/round_factor;
			
		var pricediscount = new Number(discount);
		pricediscount = sum2 - this.total;
		pricediscount = Math.round(pricediscount*round_factor)/round_factor;
		$('#totalDiscount').html(displayPrice(pricediscount));
		$('#totalWdiscount').html(displayPrice(sum2));
		console.log('T.Desc. ' + sum2);
		var total = new Number(this.total);
		total = total.toFixed(decimals);
		$('#totalTicket').html(displayPrice(total));
		$('#totalTicketinv').html(displayPrice(total));
		var limfac = new Number(_TPV.faclimit);
		if(total >= limfac){
			$('#alertfaclim').show();
		}
		else{
			$('#alertfaclim').hide();
		}
		console.log('Total '+_TPV.ticket.total);
		console.log('Pagos '+_TPV.ticket.customerpay);
		console.log('Aux '+_TPV.ticket.auxPaymentcus);
		_TPV.ticket.difpayment = _TPV.ticket.total-_TPV.ticket.customerpay;
		$("#totalRestToPay").html(displayPrice(parseFloat(_TPV.ticket.difpayment)));

	},
	addProductLine: function()
	{
		if(!this.getLine(_TPV.activeIdProduct))
		{
			this.addLine(_TPV.activeIdProduct, true);
		}
		var cant = parseFloat($('#id_product_quantity').val());
		if(cant>1)
		{
			cant = cant-1;
			this.getLine(_TPV.activeIdProduct).cant = this.getLine(_TPV.activeIdProduct).cant + cant;
			this.getLine(_TPV.activeIdProduct).setQuantity(this.getLine(_TPV.activeIdProduct).cant);
			this.getLine(_TPV.activeIdProduct).qty_ent = this.getLine(_TPV.activeIdProduct).cant;
			this.getLine(_TPV.activeIdProduct).setQtyEnt(this.getLine(_TPV.activeIdProduct).qty_ent); 
			this.getLine(_TPV.activeIdProduct).showTotal();	
		}
		showTicketContent();
	},
	addManualProduct: function(id,qty,disc,pri,note,ls_warehouse_status,qty_ent,ls_stock_mv_code)
	{
		if (isNaN(qty_ent))
		{
			qty_ent = qty;
		}
		if (isNaN(disc))
		{
			disc = 0;
		}
		if(typeof id!= 'undefined' && id!=0)
		{
			_TPV.activeIdProduct = id;
			_TPV.ticket.addLine(id,undefined,ls_warehouse_status,qty_ent,ls_stock_mv_code);
			if(pri != this.getLine(_TPV.activeIdProduct).price)
			{
				this.getLine(_TPV.activeIdProduct).setPrice(pri*1.16);
				this.getLine(_TPV.activeIdProduct).showTotal();
			}
			if(ls_warehouse_status)
			{
				this.getLine(_TPV.activeIdProduct).ls_warehouse_status=ls_warehouse_status;
			}
			this.getLine(_TPV.activeIdProduct).qty_ent = qty_ent;
			var flag = 0;

			if(typeof qty!= 'undefined' && qty!=1)
			{
				
				cant = qty-1;
				this.getLine(_TPV.activeIdProduct).cant = this.getLine(_TPV.activeIdProduct).cant + cant;
				this.getLine(_TPV.activeIdProduct).setQuantity(this.getLine(_TPV.activeIdProduct).cant);
				flag = 1;
				
			}
			
			if(typeof disc!= 'undefined' && disc!=0){
				this.getLine(_TPV.activeIdProduct).setDiscount(disc);
				//this.getLine(_TPV.activeIdProduct).price = this.getLine(_TPV.activeIdProduct).price / (1-disc/100);
				flag=1;
			}
			/*if(typeof pri!= 'undefined' && pri!=0){console.log(pri);
				this.getLine(_TPV.activeIdProduct).setPrice(pri);
				this.getLine(_TPV.activeIdProduct).showTotal();
			}*/
			if(note){
				this.getLine(_TPV.activeIdProduct).setNote(note);
			}
			if(flag){
				this.getLine(_TPV.activeIdProduct).showTotal();
			}
		}
	},
	addReturnProduct: function(idProduct)
	{
		if(!this.checkExistReturnProduct(idProduct))
			return;
		if(this.getLine(idProduct)!=undefined)
		{
			var line = this.getLine(idProduct);
			var quantity = line.cant;
			line.qty_ent = line.cant;
			
			if(!this.checkApplyQuantity(idProduct,quantity++))
				return;
			line.setQuantity(quantity++);
			line.showTotal();
		}
		else
		{
			var line = new TicketLine();
			line.setLineByIdLine(idProduct);
			if(line.discount!=0)
				line.setDiscount(line.discount);
			//this.total = this.total + line.price_ttc;
			//$('#totalTicket').html(displayPrice(this.total));
			line.qty_ent = line.cant;
			this.setLine(idProduct,line);
			$('#tablaTicket > tbody:last').prepend(line.getHtml());
			line.showTotal();
			//this.calculeDiscountTotal();
			
				
		}
		
		
	},
	addLine: function(idProduct, add,ls_warehouse_status,qty_ent,ls_stock_mv_code)
	{
		if(_TPV.ticketState==1)
			return;
		if(_TPV.infoProduct==0 || typeof add!='undefined')
		{
			showTicketContent();
		
			if(_TPV.ticket.idsource!=0)
			{
				this.addReturnProduct(idProduct);
				return;
			}
			if(this.getLine(idProduct)!=undefined)
			{
				//if(_TPV.products[idProduct]["stock"] > this.getLine(idProduct).cant || _TPV.products[idProduct]["stock"] == "all"){
		if (typeof qty_ent == 'undefined')
		{
			qty_ent = this.getLine(idProduct).cant;
		}
					this.getLine(idProduct).cant = this.getLine(idProduct).cant + 1;
					this.getLine(idProduct).setQuantity(this.getLine(idProduct).cant);
					this.getLine(idProduct).setQtyEnt( this.getLine(idProduct).cant);
					this.getLine(idProduct).showTotal();
				/*}
				else{
					//Muestro un error diciendo que no hay stock ni se le espera...
					var txt=ajaxDataSend('Translate','NoStockEnough');
					_TPV.showError(txt);
				}*/
			}
			else
			{
		if (typeof qty_ent == 'undefined')
		{
			qty_ent = 1;
		}
				var line = new TicketLine();
				line.setLineByIdProducts(idProduct);
				line.ls_warehouse_status=ls_warehouse_status;
				line.qty_ent = qty_ent;
				line.discount = _TPV.discount;
				line.ls_stock_mv_code = ls_stock_mv_code;
				//if(_TPV.products[idProduct]["stock"] >= line.cant || _TPV.products[idProduct]["stock"] == "all"){
					this.total = this.total + line.total;
					$('#totalTicket').html(displayPrice(this.total));
					$('#totalTicketinv').html(displayPrice(this.total));
					this.setLine(idProduct,line);
					$('#tablaTicket > tbody:last').prepend(line.getHtml());
				//}
			}
		} 
		else
		{
			showInfoProduct();
		}
		_TPV.ticket.calculeTotal();
		_TPV.addInfoProduct(idProduct);
		//line.showTotal();
		if(_TPV.defaultConfig['terminal']['barcode'] == 1){
			$('#id_product_search').focus();
		}
		
	},
	addTicketCoupon: function(amount, id)
	{
		amount = parseFloat(amount);
		if(parseFloat(amount) > parseFloat(this.difpayment))
		{
			amount = parseFloat(this.difpayment);
		}
		$('#pay_client_4').val(amount);
		_TPV.payClient();
		/*
		this.total = this.total-amount;
		this.difpayment = this.difpayment-amount
		if(_TPV.ticket.difpayment > 0)
		$('.payment_return').addClass('negat');
		else
		$('.payment_return').removeClass('negat');
		$('.payment_options .payment_return').html(displayPrice(this.total)); 
		//var txt=ajaxDataSend('Translate','CouponAdded');
		*/
		this.idCoupon = id;
		$('#payment_coupon').hide();
		_TPV.showInfo('Ticket de Regalo Agregado');
	},
	searchSustitutes : function(idProduct)
	{
		$('#sustituteslist').html('');
		$("#sustitutes").toggle();
		var res = ajaxDataSend('getAllSustites', idProduct);
		if(res != 0)
		{
			$.each(res, function(id, item) {
				$('#sustituteslist').append('<tr ondblclick="_TPV.ticket.addLine('+item['id']+');" style="background-color: white;"><td style="width:25%;">'+item['ref']+'</td><td style="width:75%;">'+item['label']+'</td></tr>');
			});
		}
	},
	searchComplements : function(idProduct)
	{
		$('#complementslist').html('');
		$("#complements").toggle();
		var res = ajaxDataSend('getAllComplements', idProduct);
		if(res != 0)
		{
			$.each(res, function(id, item) {
				$('#complementslist').append('<tr ondblclick="_TPV.ticket.addLine('+item['id']+');_TPV.ticket.addCant('+item['id']+','+item['qty']+');" style="background-color: white;"><td style="width:15%;">'+item['ref']+'</td><td style="width:80%;">'+item['label']+'</td><td style="width:5%;">'+item['qty']+'</td></tr>');
			});
		}
	},
	addCant : function(idProduct,cant)
	{
		this.getLine(idProduct).cant = cant;
		this.getLine(idProduct).setQuantity(this.getLine(idProduct).cant);
		this.getLine(idProduct).showTotal();
	},
	editTicketLine : function(idProduct)
	{
		var qe = _TPV.ticket.getLine(idProduct).cant;
		if (_TPV.ticket.getLine(idProduct).qty_ent > 0)
		{
			qe = _TPV.ticket.getLine(idProduct).qty_ent;
		}
		$('#line_quantity').val(_TPV.ticket.getLine(idProduct).cant);
		$('#line_qty_ent').val(qe);
		$('#line_discount').val(_TPV.ticket.getLine(idProduct).discount);
		$('#line_price').val(Math.round(_TPV.ticket.getLine(idProduct).price_ttc*round_factor)/round_factor);
		$('#line_note').val(_TPV.ticket.getLine(idProduct).note);
		//$('#idTicketLine').dialog({width: 400});
		showLeftContent('#idTicketLine');
		$('#line_quantity').focus();
		$('#id_btn_editTicketline').unbind('click');
		$('#id_btn_editTicketline').click(function(){
			if(isNaN(parseInt($("#line_discount").val())))
			{
				$("#line_discount").val('0');
			}
			if(isNaN(parseFloat($("#line_quantity").val())))
			{
				$("#line_quantity").val('1');
				$("#line_qty_ent").val('1');
			}
			if(isNaN(parseFloat($("#line_qty_ent").val())))
			{
				$("#line_qty_ent").val($("#line_quantity").val());
			}
			if(isNaN(parseFloat($("#line_price").val())))
			{
				$("#line_price").val('9999999');
			}
			if(_TPV.discount==0 && _TPV.discount != undefined) {
//				if(parseInt($("#line_discount").val()) == 0)
//				{
//					_TPV.ticket.updateTicketLine(idProduct);
//				}

			var stock_movs = ajaxDataSend('can_delete_line',{['ticket']:_TPV.ticket.id,['product']:idProduct});
			if (parseFloat(stock_movs) > parseFloat($("#line_quantity").val()))
			{
				alert('El producto tiene movimientos de stock superiores a la cantidad ingresada.')
				return;
			}
			


if(parseInt($("#line_discount").val()) == 0 || $("#line_discount").val() == '')
{

   $("#line_discount").val('0');
   _TPV.ticket.updateTicketLine(idProduct);
}
				else {
					if(discount_percent_limit < parseInt($("#line_discount").val()))
					{
						$("#form-validar-contrasena").show();

						$("#validar-contrasena").click(function(){
							var passwd = $("#contrasena_descuento").val();
							$("#form-validar-contrasena").hide();
							var res = ajaxDataSend('validate_pass', passwd);
							if(res["allow"] && parseInt(res['desc'])>parseInt($("#line_discount").val()) && res['error'] == 0) {
								//if(parseInt(res["desc"]) >= parseInt($("#line_discount").val()))
								_TPV.ticket.updateTicketLine(idProduct);
							}
							else{
								if(res['error'] > 0)
									alert(res['mensaje']);
								else
									alert("El descuento no puede ser mayor a " + res['desc'] + "% para el administrador que lo aplico");
							}
							document.getElementById("contrasena_descuento").value = '';
							$("#form-validar-contrasena").hide();
						});
					}
					else {
						_TPV.ticket.updateTicketLine(idProduct);
					}

				}
			}
			else if(parseInt($("#line_discount").val()) != 0 && parseInt($("#line_discount").val())>_TPV.discount)
			{
				alert("El cliente ya tiene un descuento");
			}
			else
			{
				_TPV.ticket.updateTicketLine(idProduct);
			}
		});		
	},
	updateTicketLine: function(idProduct)
	{
		var line = _TPV.ticket.getLine(idProduct);
		console.log('ent'+parseFloat($('#line_qty_ent').val()));
		console.log('qty'+parseFloat($('#line_quantity').val()));
		if (parseFloat($('#line_qty_ent').val()) > parseFloat($('#line_quantity').val()))
		{
			$('#line_qty_ent').val($('#line_quantity').val());
		}
		if(_TPV.ticket.checkApplyQuantity(idProduct,$('#line_quantity').val()))
		{
			line.setQuantity($('#line_quantity').val());
			line.setDiscount($('#line_discount').val());
			line.setPrice($('#line_price').val());
			line.setNote($('#line_note').val());
			line.setQtyEnt($('#line_qty_ent').val());
		}
		else
		{
			line.setQtyEnt($('#line_qty_ent').val());
		}
		line.showTotal();
		hideLeftContent();
	},
	deleteLine: function(idProduct)
	{
		var can_be_deleted = ajaxDataSend('can_delete_line',{['ticket']:_TPV.ticket.id,['product']:idProduct});
		switch (parseInt(can_be_deleted))
		{
			case 0:
				// sin movimientos registrados
				break;
			case -1:
				// error en la consulta
				alert('No fue posible validar los movimientos de stock. Intente de nuevo más tarde.')
				return;
				break;
			default :
				alert('Producto con movimientos de stock pendientes. No puede ser eliminado');
				return;
				break;
		}
		var data = [];
		var row,checked_items=0;;
		$('.ls_tpv_line_checkbox').each(function(i,e){
			if ($(e).is(":checked"))
			{
				row={'prod_id':$(e).attr('id').replace('ls_tpv_line_chkbox_','')
				};
				checked_items+=1;
				data.push(row);
			}
		});
		if(checked_items > 0){
			$.each(data,function(i,e){
				var success = ajaxSend('deleteLine');
				$('#ticketLine' + e["prod_id"]).remove();
				_TPV.ticket.total = _TPV.ticket.total - _TPV.ticket.getLine(parseInt(e["prod_id"])).total;
				$('#totalTicket').html(displayPrice(_TPV.ticket.total));
				$('#totalTicketinv').html(displayPrice(_TPV.ticket.total));
				_TPV.ticket.lines = removeKey(_TPV.ticket.lines, parseInt(e["prod_id"]));
			});
			if (this.length == 0) {
				this.setButtonState(false);
			}
			this.calculeTotal();
			$('#ticketOptions').html('').hide();
		}else {
			var success = ajaxSend('deleteLine');
			$('#ticketLine' + idProduct).remove();
			this.total = this.total - this.getLine(idProduct).total;
			$('#totalTicket').html(displayPrice(this.total));
			$('#totalTicketinv').html(displayPrice(this.total));
			this.lines = removeKey(this.lines, idProduct);
			if (this.lines.length == 0) {
				this.setButtonState(false);

			}
			this.calculeTotal();
			$('#ticketOptions').html('').hide();
		}
	},
	cancelTicket: function()
	{
		var success = ajaxSend('cancelTicket');
		$('#tablaTicket tbody tr').remove();
	},
	saveTicket: function(p=0)
	{
		var tempId = _TPV.ticket.proyectId;
		
		// Set State to draftcustomerTable_
		_TPV.ticket.mode=0;
		if (p==0)
		{
			if(parseInt(_TPV.ticket.state) != 2) {
				_TPV.ticket.state = 0;
				_TPV.ticket.difpayment = _TPV.ticket.total;
			}
		}
		_TPV.ticket.employeeId=_TPV.employeeId;
		_TPV.ticket.infoCustomer_ref = $('#infoCustomer_ref').val();
		var result = ajaxDataSend('saveTicket',_TPV.ticket);
		//var result = ajaxDataSend('saveTicket',_TPV.ticket);
		$('#tablaTicket tbody tr').remove();
		//_TPV.ticket.newTicket();
		_TPV.getTicket(result,true);
		_TPV.ticket.proyectId=tempId;
		return result;
	},
	
	esCredito: function(){
		
		var rc_limit = parseInt($('#disponible_c').text().replace('$','').replace(',','').replace(',','').replace(',',''));
		console.log('Disponible: '+rc_limit+', por pagar: '+this.difpayment);
		if (rc_limit<=0 || (this.difpayment > rc_limit && this.state == 0))
		{
			return false;
		}
		return true;
	},
	
	okTicket: function()
	{
		if (_TPV.ticket.rc_isOrder())
		{
			_TPV.ticket.lines = _TPV.ticket.oldproducts;
			_TPV.ticket.sendTicket();
			_TPV.ticket.newTicket();
			return;
		}
		
		// $('#id_btn_add_ticket').hide();
		$('#payment_points').hide();
		$('#info_product').hide();
		_TPV.ticket.employeeId=_TPV.employeeId;
		_TPV.ticket.convertDis = false;
				var rc_cred_limit = $('#disponible_c').text().replace('$','').replace(',','');
				if (rc_cred_limit.length == 0)
				{
					rc_cred_limit = '0';
				}
				rc_cred_limit = parseFloat(rc_cred_limit);
				if (isNaN(rc_cred_limit))
				{
					rc_cred_limit = 0;
				}
				var toP = _TPV.ticket.difpayment;
				toP = parseFloat(toP);
				if(toP > rc_cred_limit || parseInt(_TPV.ticket.customerId) == 8)
				{
					$('#solo_contado').show();
				}
				else
				{
					$('#solo_contado').hide();
				}
				
		if(_TPV.ticket.type==1)
		{
			$('#pay_client_ret_0').val('');
			$('#pay_client_ret_1').val('');
			$('#pay_client_ret_2').val('');
			$('.payment_options .payment_return_ret').html('');
			if(this.ret_points > 0)
				this.difpayment = Math.min(this.total, this.ret_points);
			else
				this.difpayment = this.total;

			if(_TPV.ticket.difpayment > 0)
				$('.payment_return_ret').addClass('negat');
			else
				$('.payment_return_ret').removeClass('negat');
			
			
			//$('.payment_options .payment_total').html(this.total);
			$('.payment_options .payment_return_ret').html(displayPrice(this.difpayment));
			$('#payment_options').hide();
			
			//la opcion para elegir ticket, facsim o factura
			/*if(_TPV.defaultConfig['module']['ticket'] == 1 && _TPV.defaultConfig['module']['facture'] == 1){
				showLeftContent('#idReturnMode');		
			}*/
			//else if(_TPV.defaultConfig['module']['ticket'] == 1){
			if(_TPV.ticket.mode == 0){
				showLeftContent('#payTypeRet');
				$('#convert_coupon').hide();
				$('#payment_total_ret').show();
				$('#payment_total_points_ret').show();
				_TPV.ticket.mode=0;
			}
			else if(_TPV.ticket.total < _TPV.faclimit){
				showLeftContent('#payTypeRet');
				_TPV.ticket.showTotalBlockRet();
				$('#convert_coupon').show();
				_TPV.ticket.mode=1;
			}
			else{
				showLeftContent('#payTypeRet');
				_TPV.ticket.showTotalBlockRet();
				$('#convert_coupon').show();
				_TPV.ticket.mode=2;
			}
			/*$('#id_btn_ticketRet').click(function(){
				$('#id_btn_ticketRet').unbind('click');
				showLeftContent('#payTypeRet');		
				$('#payment_total_ret').show();
				if(_TPV.ticket.mode == 0)
					$('#convert_coupon').hide();
				$('#id_btn_add_ticket_ret').show();
				_TPV.ticket.mode=0;
			});
			$('#id_btn_facsimRet').click(function(){
				$('#id_btn_facsimRet').unbind('click');
				_TPV.ticket.mode=1;
				showLeftContent('#payTypeRet');
				_TPV.ticket.showTotalBlockRet();
			});
			$('#id_btn_factureRet').click(function(){
				$('#id_btn_factureRet').unbind('click');
				_TPV.ticket.mode=2;
				showLeftContent('#payTypeRet');
				_TPV.ticket.showTotalBlockRet();
			});*/
		}
		else {	
		if (_TPV.ticket.rc_higher_than_stock()){
			if(_TPV.ticket.state == 1){
				if (!confirm("Las unidades entregadas exceden el stock. \r\n¿Está seguro de guardar esta venta?")){
					return;
				}
			}
		}
		if (	/* rc_canReceivePayments
			&& */ _TPV.ticket.rc_deliveryDiff() 
			&& confirm("Existen diferencias entre las unidades pedidas y entregadas. \r\n¿Quire generar un ticket complementario?"))
		{
			_TPV.ticket.newByDifference = 1;
		}
		
		
		
		
			$('#pay_client_0').val('');
			$('#pay_client_1').val('');
			$('#pay_client_2').val('');
			$('#pay_client_3').val('');
			$('#pay_client_4').val('');
			$('#points_client_id').val('');
			$('.payment_options .payment_return').html('');

			if(this.state != "2")
				this.difpayment = this.total;
			
			if(_TPV.ticket.difpayment > 0)
				$('.payment_return').addClass('negat');
			else
				$('.payment_return').removeClass('negat');
			
			//$('.payment_options .payment_total').html(this.total);
			$('.payment_options .payment_return').html(displayPrice(this.difpayment));
			$('#payment_options').hide();
			
			//la opcion para elegir ticket, facsim o factura
			
			if(_TPV.defaultConfig['module']['ticket'] == 1 && _TPV.defaultConfig['module']['facture'] == 1 && rc_canReceivePayments)
			{
				$('#id_btn_coupon').show();
				_TPV.ticket.showTotalBlock();				
				showLeftContent('#idFactureMode');
			}
			else if(!rc_canReceivePayments)
			{
				//_TPV.ticket.mode=0;
				$('#id_btn_coupon').hide();
				var rc_cred_limit = $('#disponible_c').text().replace('$','').replace(',','');
				if (rc_cred_limit.length == 0)
				{
					rc_cred_limit = '0';
				}
				rc_cred_limit = parseFloat(rc_cred_limit);
				if (isNaN(rc_cred_limit))
				{
					rc_cred_limit = 0;
				}
				var toP = _TPV.ticket.difpayment;
				toP = parseFloat(toP);
				if(toP > rc_cred_limit)
				{
					var msg = '';
					if (rc_cred_limit < 0)
					{
						rc_cred_limit = Math.abs(rc_cred_limit);
						rc_cred_limit = rc_cred_limit.toFixed(decimals);
						msg =	'Límite de Crédito Excedido por $'+PriceCommas(rc_cred_limit)+
								"\r\n\r\n¿Desea proceder?"
								;
					}
					else
					{
						toP = toP.toFixed(decimals);
						rc_cred_limit = rc_cred_limit.toFixed(decimals);
						msg =	'La cantidad por pagar de $'+
								PriceCommas(toP)+
								', excede el Crédito Disponible de $'+
								PriceCommas(rc_cred_limit)+"\r\n\r\n¿Desea proceder?"
								;
					}
					//if(!confirm(msg))
					//{
					//	return false;
					//}
					_TPV.ticket.state=2;
				}
				else
				{
					_TPV.ticket.state=1;
				}
				$('#pay_client_0').val('');
				$('#pay_client_1').val('');
				$('#pay_client_2').val('');
				$('#pay_client_3').val('');
				$('#pay_client_4').val('');

				$('#payType div.options').hide();
				$('#label_add_ticket').hide();
				if (toP > 0)
				{
					showLeftContent('#payType');
					_TPV.ticket.showTotalBlock();
				}
				else
				{
					
				}
			}
			else if(_TPV.defaultConfig['module']['ticket'] == null){
				_TPV.ticket.mode=0;
				showLeftContent('#payType');
				$('#payment_coupon').hide();
				$('#payment_total_points').show();
			}
			
			else if(_TPV.ticket.total < _TPV.faclimit){
				_TPV.ticket.mode=1;
				showLeftContent('#payType');
				_TPV.ticket.showTotalBlock();
			}
			else{
				_TPV.ticket.mode=2;
				showLeftContent('#payType');
				_TPV.ticket.showTotalBlock();
			}
			$('#id_btn_ticketPay').click(function(){
				$('#id_btn_ticketPay').unbind('click');
				_TPV.ticket.mode=0;
				showLeftContent('#payType');
				//$('#payment_coupon').hide();
				
				$('#payment_total_points').show();
				$('#id_btn_add_ticket').show();
			});
			$('#id_btn_facsimPay').click(function(){
				$('#id_btn_facsimPay').unbind('click');
				_TPV.ticket.mode=1;
				showLeftContent('#payType');
				_TPV.ticket.showTotalBlock();
			});
			$('#id_btn_facturePay').click(function(){
				$('#id_btn_facturePay').unbind('click');
				_TPV.ticket.mode=2;
				showLeftContent('#payType');
				_TPV.ticket.showTotalBlock();
			});	
		}
		
		$('#id_btn_add_ticket').unbind('click');
		$('#id_btn_add_ticket_ret').unbind('click');
		$('#id_btn_add_ticket_desc').unbind('click');
		
		$('#id_btn_add_ticket').click(function(){
		// Mostrador
		let automaticInvoicing = ajaxDataSend('getAutomaticInvoicing',_TPV.ticket.customerId);
		if(automaticInvoicing == 1)
		{
			$('#div_aviso_factura_automática').show();
		}
		else
		{
			$('#div_aviso_factura_automática').hide();
		}
		if (!rc_canReceivePayments)
		{
			// PubGral
			if(_TPV.ticket.customerId == 8)
			{
				_TPV.ticket.sendTicket();
			}
			// Contado
			else if (!_TPV.ticket.esCredito())
			{
				_TPV.ticket.sendTicket();
			}
			// Credito
			else
			{
				_TPV.ticket.askInvoicing(); // We ask if they need invoicing
			}
		}
		else
		{
			// PubGral
			if(_TPV.ticket.customerId == 8)
			{
				_TPV.ticket.sendTicket();
			}
			// Contado
			else if (!_TPV.ticket.esCredito())
			{
				_TPV.ticket.sendTicket();
			}
			// Credito
			else
			{
				_TPV.ticket.askInvoicing(); // We ask if they need invoicing
			}
		}
		});

		$('#id_btn_add_ticket_ret').click(function(){
			_TPV.ticket.sendTicket();
		});
		$('#id_btn_add_ticket_desc').click(function(){
			_TPV.ticket.convertDis = true;
			_TPV.ticket.sendTicket();
		});
		
		// Eliminar paso de botón de "Venta"
		$('#id_btn_ticketPay').click();
		
		// Si no puede recibir pagos
		if(!rc_canReceivePayments)
		{
				$('#id_btn_add_ticket').click();
		}
		else
		{
			if (parseFloat(this.difpayment) == 0)
			{
				//$('#id_btn_add_ticket').click();
			}
		}
		

	},
	askInvoicing: function(){//
		$res = 0;
		if (_TPV.ticket.id > 0)
		{
			$res = parseInt(ajaxDataSend('get_invoice_id',{'ticket_id':_TPV.ticket.id})) ;
		}
		if ($res > 0)
		{
			_TPV.ticket.sendTicket();
		}
		else
		{
			$('#form-invoicing').show();
		}
	},
	askInvoicingFromList: function(id){
		
		$res = 0;
		if (id > 0)
		{
			$res = parseInt(ajaxDataSend('get_invoice_id',{'ticket_id':id})) ;
		}
		console.log ($res);
		if ($res == 999999999999)
		{
			alert('El ticket de venta original no ha sido facturado.')
			return;
		}
		else if ($res > 0)
		{
			alert('El ticket ya fue facturado.');
			return;
			
		}

		if(id > 0) $("#ticketToFactureID").val(id);
		let socid = _TPV.ticket.customerId;
		let selectTipoPago = document.getElementById("selectTipoPago");
		let selectMetodoCFDI = document.getElementById("selectMetodoCFDI");
		let selectUsoCFDI = document.getElementById("selectUsoCFDI");
		let emailLabel = document.getElementById("form-confirm-customer-email");
		if(id > 0){
			var data = {
				'action' : 'getRequiredFieldsCFDI',
				'ticketId' : id
			}
		}
		else{
			var data = {
				'action' : 'getRequiredFieldsCFDI',
				'socid' : socid
			}
		}

		$.ajax({
			type: "POST",
			url: './ajax_pos.php',
			data: data,
			async : false,
			dataType : 'json',
			success: function(response)
			{
				let optionsTipoPago = '';
				response.data.metodoPago.forEach(element => {
					if(element.label == element.labelConfirm){
						optionsTipoPago += '<option value="'+element.id+'" selected>'+element.label+'</option>';
					}else{
						optionsTipoPago += '<option value="'+element.id+'">'+element.label+'</option>';
					}
				});
				selectTipoPago.innerHTML = optionsTipoPago;

				let optionsUsoCFDI = '';
				response.data.usoCFDI.forEach(element => {
					if(element.code == element.labelConfirm){
						optionsUsoCFDI += '<option value="'+element.code+'" selected>'+element.label+'</option>';
					}else{
						optionsUsoCFDI += '<option value="'+element.code+'">'+element.label+'</option>';
					}
					//optionsUsoCFDI += '<option value="'+element.code+'">'+element.label+'</option>';
				});
				selectUsoCFDI.innerHTML = optionsUsoCFDI;

				let optionsMetodoCFDI = '';
				response.data.metodoPagoCFDI.forEach(element => {
					if(element.code == element.labelConfirm){
						optionsMetodoCFDI += '<option value="'+element.code+'" selected>'+element.label+'</option>';
					}else{
						optionsMetodoCFDI += '<option value="'+element.code+'">'+element.label+'</option>';
					}
					//optionsMetodoCFDI += '<option value="'+element.code+'">'+element.label+'</option>';
				});
				selectMetodoCFDI.innerHTML = optionsMetodoCFDI;
				

				$("#inputRFC").val(response.data.clientInfo.rfc);
				$("#inputTelefono").val(response.data.clientInfo.phone);
				$("#inputCP").val(response.data.clientInfo.zip);
				emailLabel.innerHTML = response.data.clientInfo.email;
			},
			error: function(err)
			{
				console.log(err);
			}
		});
		$("#form-confirm-invoicing").show();
	},
	invoicingTicketCreated: function(ticketId, tipoPago, usoCFDI, metodoCFDI, email, rfc, zipCode, phone){
		
		let data = {
			'action' : 'factureTicket',
			'ticketId' : ticketId,
			'tipoPago': tipoPago,
			'usoCFDI' : usoCFDI,
			'metodoCFDI' : metodoCFDI,
			'rfc' : rfc,
			'zipCode' : zipCode,
			'phone' : phone
		}
		$.ajax({
			type: "POST",
			url: './ajax_pos.php',
			data: data,
			async : false,
			dataType : 'json',
			success: function(response)
			{
				//console.log(response.data);
				if (parseInt(response.error.value) > 0)
				{
					alert(response.error.desc)
				}
				else
				{
					if (response.data !== null)
					{
						_TPV.ticket.timbraFactura(response.data.facid, email);
					}
				}
			},
			error: function(err)
			{
				console.log(err);
			}
		});
	},
	invoicingTicket: function(tipoPago, usoCFDI, metodoCFDI, email, rfc, zipCode, phone){
		_TPV.ticket.state=1;
		if(_TPV.ticket.difpayment > 0)
			_TPV.ticket.state = 2;
		_TPV.ticket.cashId = _TPV.cashId;
		var sendTicket = _TPV.ticket;
		var tempId = _TPV.ticket.proyectId;
		var result = ajaxDataSend('saveTicket',sendTicket);
		hideLeftContent();
		if(!result)
			return;
		if(_TPV.defaultConfig['module']['print']>0 ){
			if(_TPV.ticket.mode==0){
				_TPV.printing('ticket',result);
			}
			else{
				_TPV.printing('facture',result);
			}
			_TPV.ticket.newTicket();
			_TPV.ticket.proyectId=tempId;
		}
		else{
			_TPV.ticket.newTicket();
			_TPV.ticket.proyectId=tempId;
		}
		let data = {
			'action' : 'factureTicket',
			'ticketId' : result,
			'tipoPago': tipoPago,
			'usoCFDI' : usoCFDI,
			'metodoCFDI' : metodoCFDI,
			'rfc' : rfc,
			'zipCode' : zipCode,
			'phone' : phone
		}
		$.ajax({
			type: "POST",
			url: './ajax_pos.php',
			data: data,
			async : false,
			dataType : 'json',
			success: function(response)
			{
				//console.log(response.data);
				
				if (parseInt(response.error.value) > 0)
				{
					alert(response.error.desc)
				}
				else
				{
					_TPV.ticket.timbraFactura(response.data.facid, email);
				}
			},
			error: function(err)
			{
				console.log(err);
			}
		});

	},
	timbraFactura: function(facid, email){
		var data = {
			'action' : 'timbraFactura',
			'facid' : facid,
			'socid' : _TPV.ticket.customerId,
			'email' : email
		}
		$.ajax({
			type: "POST",
			url: './ajax_pos.php',
			data: data,
			async : false,
			dataType : 'json',
			success: function(response)
			{
				console.log(response);
				document.getElementById("form-invoice-title").innerHTML = 'Factura realizada con éxito.';
				document.getElementById("form-invoice-subtitle").innerHTML = response.fac_reference; //Change to ref of facture
				document.getElementById("form-invoice-text").innerHTML = 'Timbrado: ' + response.msg + '<br>' + 'Correo: ' + response.msg_email;
				$("#form-invoice-cfdi").show();
			},
			error: function(err)
			{
				console.log(err);
			}
		});
	},
	sendTicket: function(){
		_TPV.ticket.state=1;
		if(_TPV.ticket.difpayment > 0)
			_TPV.ticket.state = 2;
		_TPV.ticket.cashId = _TPV.cashId;
		_TPV.ticket.infoCustomer_ref = $('#infoCustomer_ref').val();
		var sendTicket = _TPV.ticket;
		
		console.log(sendTicket);
		var tempId = _TPV.ticket.proyectId;
		var result = ajaxDataSend('saveTicket',sendTicket);
		hideLeftContent();
		if(!result)
			return;
		if(_TPV.defaultConfig['module']['print']>0 ){
			if(_TPV.ticket.mode==0 && _TPV.ticket.type == 1)
			{
				
			}
			if(_TPV.ticket.mode==0)
			{
				if (!rc_canReceivePayments)
				{
					if (_TPV.ticket.difpayment > 0)
					{
						_TPV.printing('ticket',result);
					}
				}
				else
				{
					if (_TPV.ticket.customerpay5 != 0 || _TPV.ticket.customerpay1 != 0 || _TPV.ticket.customerpay2 != 0 || _TPV.ticket.customerpay3 != 0 || _TPV.ticket.customerpay4 != 0 || this.esCredito())
					{
						_TPV.printing('ticket',result);
					}
					else
					{
						//console.log
					}
				}
			}
			else{
				_TPV.printing('facture',result);
			}	
			//_TPV.ticket.newTicket();
			_TPV.getTicket(result,true);
			_TPV.ticket.proyectId=tempId;
			$('#btnReloadTicket').click();
		}
		else{
			//_TPV.ticket.newTicket();
			_TPV.getTicket(result,true);
			_TPV.ticket.proyectId=tempId;
			$('#btnReloadTicket').click();
		}
	},
	AbandonarTicket: function()
	{
		var sendTicket = _TPV.ticket;
		var result = ajaxDataSend('abandonarTicket',sendTicket);
		if (parseInt(result) == -99)
		{
			alert('No tien permisos para cancelar una venta con movimientos de stock activos.')
		}
		else if (parseInt(result) == -100)
		{
			alert('No tiene permisos para cancelar una venta con pagos activos.')
		}
		else
		{
			_TPV.printing('ticket',_TPV.ticket.id)
			_TPV.ticket.newTicket();
		}
	},
	showAddCustomer: function(customer)
	{
		$('#idClient').dialog({ modal: true });
		$('#idClient').dialog({width:440});
		$('#id_btn_add_customer').unbind('click');
		$('#id_btn_add_customer').click(function(){
			var customer = new Customer();
			customer.nom = $('#id_customer_name').val();
			customer.prenom = $('#id_customer_lastname').val();
			customer.address = $('#id_customer_address').val();
			customer.town = $('#id_customer_town').val();
			customer.zip = $('#id_customer_zip').val();
			customer.idprof1 = $('#id_customer_cif').val();
			customer.tel = $('#id_customer_phone').val();
			customer.email = $('#id_customer_email').val();
			
			customer.outnum = $('#id_customer_outnum').val();
			customer.innum = $('#id_customer_innum').val();
			customer.neigh = $('#id_customer_neigh').val();
			customer.county = $('#id_customer_county').val();
			
			var result = ajaxDataSend('addCustomer',customer);
			if (parseInt(result) > 0)
			{
				$('#idClient :input[type=text]').each(function(i,e){
					$(e).val('');
				});
				$('#idClient').dialog('close');
			}
			//if(result.length>0)
				//_TPV.ticket.id= result[0];
		});
		
	},
	addTicketCustomer: function(idcustomer,siren,name,remise,coupon,points,idproyect,proyect,mode,pos=0)
	{
		_TPV.ticket.customerId = idcustomer;
		_TPV.ticket.proyectId = idproyect;
		_TPV.ticket.discount_percent = remise;
		_TPV.discount = remise;
		_TPV.points = points;
		_TPV.coupon = coupon;
		$('#infoCustomer').html(name);
		$('#infoCustomer_').html(name);
		$('#infoProyectCustomer_').html(proyect);
		$('#Customer_remise').html(remise+"%");
		if(idcustomer != 8) {
            $('#infoCustomer_rfc').html("RFC: " + siren);
        }else{
            $('#infoCustomer_rfc').html("RFC: ");
        }
		if(mode == 1){
			//if(_TPV.temporalpro[idproyect]["presupuesto"] != null && _TPV.temporalpro[idproyect]["presupuesto"] != undefined)
			if(parseInt(_TPV.ticket.proyectId) != 0 && _TPV.temporalpro[idproyect]["presupuesto"] != null && _TPV.temporalpro[idproyect]["presupuesto"] != undefined)
			{
				if(_TPV.temporalpro[idproyect]["presupuesto"] == null)
					_TPV.temporalpro[idproyect]["presupuesto"] = 0;
				$('#limite_c').html("$"+PriceCommas(_TPV.temporalpro[idproyect]["presupuesto"]));
				var resta=parseInt(_TPV.temporalpro[idproyect]["presupuesto"])-(parseInt(_TPV.temporalpro[idproyect]["cuentas"],10)+parseInt(_TPV.temporalpro[idproyect]["gastos"])+parseInt(_TPV.temporalpro[idproyect]["por_pagar"]));
				if(resta == null)
					resta=0;
				$('#disponible_c').html("$"+PriceCommas(resta));
				if(resta > 0) {
					$('#disponible_c').css("color", "rgb(34, 200, 34)");
					_TPV.ticket.NoCredit = 0;
				}
				else {
					$('#disponible_c').css("color", "#f44");
					_TPV.ticket.NoCredit = 1;
				}
			}
			else {
				if(_TPV.limite_de_credito == null)
					_TPV.limite_de_credito = 0;
				$('#limite_c').html("$"+PriceCommas(parseInt(_TPV.limite_de_credito)));
				var resta = parseInt(_TPV.limite_de_credito)-parseInt(_TPV.restar_por_pagar);
				if(resta == null)
					resta=0;
				$('#disponible_c').html("$"+PriceCommas(resta));
				if(resta >= 0) {
					$('#disponible_c').css("color", "rgb(34, 200, 34)");
					_TPV.ticket.NoCredit = 0;
				}
				else {
					$('#disponible_c').css("color", "#f44");
					_TPV.ticket.NoCredit = 1;
				}
			}
		}else if(mode ==2)
		{
			//if(_TPV.temporalpro[pos]["proyectos"][idproyect]["presupuesto"] != null && _TPV.temporalpro[pos]["proyectos"][idproyect]["presupuesto"] != undefined)
			if(parseInt(_TPV.ticket.proyectId) != 0 && _TPV.temporalpro[pos]["proyectos"][idproyect]["presupuesto"] != null && _TPV.temporalpro[pos]["proyectos"][idproyect]["presupuesto"] != undefined)
			{
				if(_TPV.temporalpro[pos]["proyectos"][idproyect]["presupuesto"] == null)
					_TPV.temporalpro[pos]["proyectos"][idproyect]["presupuesto"] = 0;
				$('#limite_c').html("$"+PriceCommas(parseInt(_TPV.temporalpro[pos]["proyectos"][idproyect]["presupuesto"])));
				var resta=parseInt(_TPV.temporalpro[pos]["proyectos"][idproyect]["presupuesto"])-(parseInt(_TPV.temporalpro[pos]["proyectos"][idproyect]["cuentas"],10)+parseInt(_TPV.temporalpro[pos]["proyectos"][idproyect]["gastos"])+parseInt(_TPV.temporalpro[pos]["proyectos"][idproyect]["por_pagar"]));
				if(resta == null)
					resta=0;
				$('#disponible_c').html("$"+PriceCommas(resta));
				if(resta >= 0) {
					$('#disponible_c').css("color", "rgb(34, 200, 34)");
					_TPV.ticket.NoCredit = 0;
				}
				else {
					$('#disponible_c').css("color", "#f44");
					_TPV.ticket.NoCredit = 1;
				}
			}
			else {
				if(_TPV.temporalpro[pos]["limite"] == null)
					_TPV.temporalpro[pos]["limite"]=0;
				$('#limite_c').html("$"+PriceCommas(parseInt(_TPV.temporalpro[pos]["limite"])));
				var por_pagar=parseInt(_TPV.temporalpro[pos]["por_pagar"]);
				if(parseInt(_TPV.temporalpro[pos]["por_pagar"]) < 0)
					por_pagar=parseInt(_TPV.temporalpro[pos]["por_pagar"])*-1
				var resta = parseInt(_TPV.temporalpro[pos]["limite"])-por_pagar;
				if(resta == null)
					resta=0;
				$('#disponible_c').html("$"+PriceCommas(resta));
				if(resta > 0) {
					$('#disponible_c').css("color", "rgb(34, 200, 34)");
					_TPV.ticket.NoCredit = 0;
				}
				else {
					$('#disponible_c').css("color", "#f44");
					_TPV.ticket.NoCredit = 1;
				}
			}
		}
		if(parseInt(_TPV.ticket.state) == 2 && parseInt(_TPV.ticket.id)>0)
		{
			var result = ajaxDataSend('saveTicket',_TPV.ticket);
			_TPV.getTicket(_TPV.ticket.id,true);
		}
		showTicketContent();
		_TPV.ticket.calculeTotal();console.log("Descuentos");
		//Recuperar descuentos
		_TPV.ticket.lines.forEach(function(line){console.log(line.discount);
			if(line.discount == 0){
				line.setDiscount(0);
				line.setDiscount(_TPV.discount);
				line.showTotal();
			}
		});
	},
	showAddProduct: function(customer)
	{
		var product = new Product();
		$('#id_product_name').val('');
		$('#id_product_ref').val('');
		$('#id_product_price').val('');
		$('#idPanelProduct').dialog({ modal: true });
		$('#idPanelProduct').dialog({height:450,width:440});
		$('.tax_types').removeClass('btnon');
		$('.tax_types').unbind('click');
		$('.tax_types').click(function(){
			$('.tax_types').removeClass('btnon');
			$(this).addClass('btnon');
			product.tax = $(this).find('a:first').attr('id').substring(7);
		})
		$('#id_btn_add_product').unbind('click');
		$('#id_btn_add_product').click(function(){
			
			product.label = $('#id_product_name').val();
			product.ref = $('#id_product_ref').val();
			product.price_ttc = $('#id_product_price').val();
			var result = ajaxDataSend('addNewProduct',product);
			$('#idPanelProduct').dialog('close');
			if(result)
				_TPV.getDataCategories(0);
			
		});
		
	},
	addDiscount: function()
	{
		$('#ticket_discount_perc').val('');
		$('#ticket_discount_qty').val('');
	//	$('#idDiscount').show();
		//$('#products').hide();
		showLeftContent('#idDiscount');
		$('#id_btn_add_discount').unbind('click');
		$('#id_btn_add_discount').click(function(){
			var data = [];
			var row,checked_items=0;;
			$('.ls_tpv_line_checkbox').each(function(i,e){
				if ($(e).is(":checked"))
				{
					row={'prod_id':$(e).attr('id').replace('ls_tpv_line_chkbox_','')
					};
					checked_items+=1;
					data.push(row);
				}
			});
			if(checked_items > 0){
				if(_TPV.discount==0 && _TPV.discount != undefined) {
					if(parseInt($("#ticket_discount_perc").val()) != 0)
					{
						if(discount_percent_limit < parseInt($("#ticket_discount_perc").val()))
						{
							$("#form-validar-contrasena").show();

							$("#validar-contrasena").click(function(){
								var passwd = $("#contrasena_descuento").val();
								$("#form-validar-contrasena").hide();
								var res = ajaxDataSend('validate_pass', passwd);
								if(res["allow"] && parseInt(res['desc'])>parseInt($("#ticket_discount_perc").val()) && res['error'] == 0) {
									//if(parseInt(res["desc"]) >= parseInt($("#line_discount").val()))
									$.each(data,function(i,e){
										_TPV.ticket.getLine(parseInt(e["prod_id"])).setDiscount($('#ticket_discount_perc').val());
										_TPV.ticket.getLine(parseInt(e["prod_id"])).showTotal();
									});
									hideLeftContent();
								}
								else {
									if(res['error'] > 0)
										alert(res['mensaje']);
									else
										alert("El descuento no puede ser mayor a " + res['desc'] + "% para el administrador que lo aplico");
								}
								document.getElementById("contrasena_descuento").value = '';
								$("#form-validar-contrasena").hide();
							});
						}
						else {
							$.each(data,function(i,e){
								_TPV.ticket.getLine(parseInt(e["prod_id"])).setDiscount($('#ticket_discount_perc').val());
								_TPV.ticket.getLine(parseInt(e["prod_id"])).showTotal();
							});
							hideLeftContent();
						}

					}
				}
				else if(parseInt($("#line_discount").val()) != 0)
				{
					alert("El cliente ya tiene un descuento");
				}
				else
				{
					$.each(data,function(i,e){
						_TPV.ticket.getLine(parseInt(e["prod_id"])).setDiscount($('#ticket_discount_perc').val());
						_TPV.ticket.getLine(parseInt(e["prod_id"])).showTotal();
					});
					hideLeftContent();
				}
			}
			else {
				if (discount_percent_limit < parseInt($("#ticket_discount_perc").val())) {
					$("#form-validar-contrasena").show();

					$("#validar-contrasena").click(function () {
						var passwd = $("#contrasena_descuento").val();
						$("#form-validar-contrasena").hide();
						var res = ajaxDataSend('validate_pass', passwd);
						if (res["allow"] && parseInt(res['desc']) > parseInt($("#ticket_discount_perc").val()) && res['error'] == 0) {
							_TPV.ticket.updateDiscount();
						} else {
							if(res['error'] > 0)
								alert(res['mensaje']);
							else
								alert("El descuento no puede ser mayor a " + res['desc'] + "% para el administrador que lo aplico");
						}
						document.getElementById("contrasena_descuento").value = '';
						$("#form-validar-contrasena").hide();
					});
				} else {
					_TPV.ticket.updateDiscount();
				}
			}
		});			
	},
	updateDiscount: function(){
		var data = [];
		var row,checked_items=0;;
		$('.ls_tpv_line_checkbox').each(function(i,e){
			row={'prod_id':$(e).attr('id').replace('ls_tpv_line_chkbox_','')
			};
			data.push(row);
		});
		$.each(data,function(i,e){
			_TPV.ticket.getLine(parseInt(e["prod_id"])).setDiscount($('#ticket_discount_perc').val());
			_TPV.ticket.getLine(parseInt(e["prod_id"])).showTotal();
		});
		/*_TPV.ticket.discount_percent = $('#ticket_discount_perc').val();
		_TPV.ticket.discount_qty = $('#ticket_discount_qty').val();*/
		_TPV.ticket.calculeTotal();
		//$('#idDiscount').hide();
		//$('#products').show();
		hideLeftContent();
	},
	addTicketNote: function()
	{
		$('#ticket_note').val(_TPV.ticket.note);
		$('#ticketNote').dialog({ modal: true });
		$('#ticketNote').dialog({width:450});
		$('#id_btn_ticket_note').unbind('click');
		$('#id_btn_ticket_note').click(function(){
			if($('#ticket_note').val() != '') {
				_TPV.ticket.note = $('#ticket_note').val();
				var numNotes = parseInt($("#totalNote_").html());
				if(numNotes == 0)
					numNotes+=1;
				$("#totalNote_").html(numNotes);
				$('#ticketNote').dialog("close");
			}
		});
		
	},
	showCoupon: function()
	{
		if(_TPV.ticket.customerId == 0){
			_TPV.ticket.customerId = _TPV.customerId;
		}
		var data = {'customer':_TPV.ticket.customerId,'amount':_TPV.ticket.difpayment};
		var result = ajaxDataSend('searchCoupon',data);
		$('#idCoupon').dialog({ modal: true });
		$('#idCoupon').dialog({height:450,width: 600});
    	$("#couponTable_ tr.data").remove();
    	var win = "$('#idCoupon').dialog('close')";
    	$.each(result, function(id, item) {
    	    $('#couponTable_').append('<tr class="data"><td class="itemId" style="display:none">'+item['id']+'</td><td class="itemReason">'+item['description']+'</td><td class="itemAmount">'+displayPrice(item['amount_ttc'])+'</td><td class="action add"><a class="action addcoupon" onclick="_TPV.ticket.addTicketCoupon('+item['amount_ttc']+','+item['id']+');'+win+';"></a></td></tr>');
    	});
		
	},
	showTotalBlock : function(){
		if(_TPV.coupon <= 0 || _TPV.ticket.idCoupon > 0){
			$('#payment_coupon').hide();
		}
		else{
			$('#payment_coupon').show();
		}
		if(_TPV.points != null && _TPV.ticket.mode!=0){
			$('#payment_points').show();
			$('#payment_total_points').hide();
		}
		else{
			$('#payment_total_points').show();
		}
		$('#id_btn_add_ticket').show();
		//Initialize Values
		$('.points_total').html(_TPV.points);
		$('.points_money').html(_TPV.defaultConfig['module']['points']*_TPV.points+' ');
		//$('.payment_total').html(displayPrice(_TPV.ticket.total));
	},
	showTotalBlockRet : function(){
		$('#payment_total_ret').show();
		
		$('#id_btn_add_ticket_ret').show();
		$('#convert_coupon').hide();
		
		//Initialize Values
		//$('.payment_total').html(displayPrice(_TPV.ticket.total));
	},
	showZoomProducts:function()
	{
		$('#idProducts').append($('#products').html());
		$('#idProducts').dialog({ modal: true });
		$('#idProducts').dialog({width:640});
	},
	sendToWarehouse:function()
	{
		var hasToSave = true;
		var actualState = _TPV.ticket.state;
		if (_TPV.ticket.id == 0)
		{
			actualState = 0;
		}
		var data = {
					'ticket':_TPV.ticket.id,
					'ticket_status':actualState,
					'warehouse':_TPV.warehouseId,
					'lines':[],
					};
		var row;
		$('#listado_productos_ticket tr').each(function(i,e){
			var lpid = $(e).attr('id').replace('ticketLine','');
			if ($('#ls_tpv_line_chkbox_'+lpid).is(":checked"))
			{
				row={'prod_id':lpid,
					 'checked':'1',
					 'qty':$('#ticketLine'+lpid+' td:nth-child(8)').text()
				};
				data.lines.push(row);
			}
			
		});
		if (data.ticket == 0)
		{
			data.ticket = _TPV.ticket.saveTicket();
			hasToSave = false;
		}
		if(_TPV.ticket.rc_isOrder())
		{
			hasToSave = false;
		}
		if(parseInt(_TPV.ticket.state) > 0)
		{
			hasToSave = false;
		}
		if (hasToSave)
		{
			_TPV.ticket.saveTicket(1);
		}
		var result = ajaxDataSend('askToWarehouse',data);
		if (result > 0)
		{
			_TPV.ticket.newTicket();
			_TPV.getTicket(data.ticket,!_TPV.ticket.rc_isOrder());
			$('#btnTicketSendFront').css('background-color','green');
		}
	},
	rc_save_delivery_address:function()
	{
		if($('#rc_delivery_name').val().length <= 0)
		{
			alert('El campo "Nombre" es requerido.');
		}
		else if($('#rc_delivery_address').val().length <= 0)
		{
			alert('El campo "Dirección" es requerido.');
		}
		else if($('#rc_delivery_city').val().length <= 0)
		{
			alert('El campo "Ciudad" es requerido.');
		}
		else if($('#rc_delivery_state').val().length <= 0)
		{
			alert('El campo "Estado/Provincio" es requerido.');
		}
		else if($('#rc_delivery_country').val().length <= 0)
		{
			alert('El campo "País" es requerido.');
		}
		else if($('#rc_delivery_zip').val().length <= 0)
		{
			alert('El campo "Código Postal" es requerido.');
		}
		else if($('#rc_delivery_phone').val().length <= 0)
		{
			alert('El campo "Teléfono" es requerido.');
		}
		else
		{
			$('.rc_hd_input').each(function(i,e){
				var pid = e.id.replace('rc_hd_input_','')
				_TPV.ticket.lines.forEach(function(se,si){
					console.log(_TPV.ticket.lines);
					console.log(si);
					console.log(se)
					if(_TPV.ticket.lines[si]['idProduct'] == pid)
					{
						_TPV.ticket.lines[si]['qty_ent'] = parseFloat(_TPV.ticket.lines[si]['cant'])- parseFloat($(e).val()); 
					}
				});
				_TPV.ticket.oldproducts.forEach(function(se,si){
					if(_TPV.ticket.oldproducts[si]['idProduct'] == pid)
					{
						_TPV.ticket.oldproducts[si]['qty_ent'] = parseFloat(_TPV.ticket.oldproducts[si]['cant'])- parseFloat($(e).val()); 
					}
				});
			});
			var data = {
				'ticket':_TPV.ticket.id,
				'name':$('#rc_delivery_name').val(),
				'address':$('#rc_delivery_address').val(),
				'city':$('#rc_delivery_city').val(),
				'state':$('#rc_delivery_state').val(),
				'country':$('#rc_delivery_country').val(),
				'zip':$('#rc_delivery_zip').val(),
				'phone':$('#rc_delivery_phone').val(),
				'customer':_TPV.ticket.customerId,
				'project':_TPV.ticket.proyectId,
				'raw':_TPV.ticket
				};
			var result = ajaxDataSend('createDeliveryOrder',data);
			$('#rc_dialog-home-delivery').hide();
		}
	},
	showDeliveryDialog:function()
	{
		var result = ajaxDataSend('getDeliveryData',_TPV.ticket.customerId);
		$('#rc_delivery_name').val(result['name']);
		$('#rc_delivery_address').val(result['address']);
		$('#rc_delivery_city').val(result['town']);
		$('#rc_delivery_state').val(result['state']);
		$('#rc_delivery_country').val(result['country']);
		$('#rc_delivery_zip').val(result['zip']);
		$('#rc_delivery_phone').val(result['phone']);
		$('#rc_dialog-home-delivery').show();
		$('#rc_homedeliv_product_list').html('');
		$('#listado_productos_ticket').find('tr').each(function(i,e){
			var p_id = $(e).find('.idCol').text();
			var p_lb = $(e).find('.description').text();
			var p_qt = $(e).find('.cant').text();
			$('#rc_homedeliv_product_list').append(
			'<tr style="">'+
			'<td style="color:#ccc;">'+
			p_lb+
			'</td>'+
			'<td>'+
			'<input type="text" value="'+p_qt+'" class="rc_hd_input" id="rc_hd_input_'+p_id+'" />'+
			'</td>'+
			'<tr>'
			);
		});
	},
	dm_set_freight_cost: function() {
		let error = 0, distance = $('#dm_delivery_distance').val(), shipment = $('#selectdm_delivery_shipment').val();


		/**
		 * Check parameters
		 */
		if(distance.length <= 0) {
			$.jnotify('El campo "Distancia" es requerido.', 'error');
			error++;
		}
		if(isNaN(distance)) {
			$.jnotify('El campo "Distancia" debe ser numérico.', 'error');
			error++;
		}
		if (shipment == -1) {
			$.jnotify('El campo "Método de envío" es requerido.', 'error');
			error++;
		}

		// Set freight
		if (error == 0) {
			// Get cost
			var freight = ajaxDataSend('getFreight', { 'ticket':_TPV.ticket.id, 'distance': distance, 'shipment': shipment });

			if (freight.ok && freight.service > 0) {
				var idProduct = freight.service;
				
				// Check if service exist in ticket
				var lservice = this.getLine(idProduct);
				
				// Update price and totals if exist
				if (lservice != undefined) {
					lservice.setPrice(freight.cost);
					lservice.showTotal();
				}
				// Otherwise add service
				else {
					var info = new Object();
					info['product'] = idProduct;
					if(_TPV.ticket.customerId != 0){
						info['customer'] = _TPV.ticket.customerId;
					}
					else
					{
						info['customer'] = _TPV.customerId;
					}
	
					// Get datra of freight service
					
					var result = getCacheProduct(info['customer'],idProduct);
	
					_TPV.products[idProduct] = result[0];
					
					var product = _TPV.products[idProduct];
					
					var data = new Object();
					data['customer'] = info['customer'];
					data['tva'] = product.tva_tx;
					var localtax = {"1":0,"2":0}; //ajaxDataSend('getLocalTax',data);
	
					product["cant"]=1;
					product["remise_percent_global"]=0;
					product["localtax1_tx"] = localtax['1'];
					product["localtax2_tx"] = localtax['2'];
					product["price_base_type"] = 'TTC';
					product["price_ttc"] = freight.cost;
	
					// Line to add
					var line = new TicketLine();
	
					if(localtax['1'] != 0 || localtax['2'] != 0){
						var result = ajaxDataSend('calculePrice',product);
						line.price = result["pu_ht"];
						line.price_ttc = parseFloat(result["pu_ht"])+parseFloat(result["pu_tva"]);
						line.total = result["total_ttc"];
						line.total_ttc = parseFloat(result["total_ht"])+parseFloat(result["total_tva"]);
						line.total_ttc_without_discount = result["total_ttc_without_discount"];
					}
					else{
						line.price = product.price;
						line.price_ttc = product.price_ttc;
						line.total = product.price_ttc;
						line.total_ttc = product.price_ttc;
						line.total_ttc_without_discount = product.price_ttc;
					}
					line.ref = product.ref;
					line.idProduct = idProduct;
					line.stock = product.stock;
					line.label = product.label;
					line.description = product.description;
					line.ls_warehouse_status = product.ls_warehouse_status;
					line.localtax1_tx = localtax['1'];
					line.localtax2_tx = localtax['2'];
					line.tva_tx = product.tva_tx;
					line.idTicket = _TPV.ticket.id;
					line.price_min_ttc = product.price_min_ttc;
					line.price_base_type = product.price_base_type;
					line.fk_product_type = product.fk_product_type;
					line.remise_percent_global = 0;
					line.diff_price = product.diff_price;
					line.flag= parseInt(product.flag);
					line.dias_entrega= parseInt(product.delivery_time_days);
					line.qty_ent = '';
	
					// Update ticket totals
					this.total = parseFloat(this.total) + line.total;
					$('#totalTicket').html(displayPrice(this.total));
					$('#totalTicketinv').html(displayPrice(this.total));
					this.setLine(idProduct,line);
					$('#tablaTicket > tbody:last').prepend(line.getHtml());
				}
				
			}

			// Clean
			$('#dm_dialog_demoFreight').fadeOut();
			$('#dm_delivery_distance').val('');
			$('#selectdm_delivery_shipment').val(-1);
		}
	},
	showDemoFreight: function() {
		$('#dm_dialog_demoFreight').show();
	},
	setToApartado:function()
	{
		var data = {
					'ticket':_TPV.ticket.id,
					'ticket_status':_TPV.ticket.state,
					'warehouse':_TPV.warehouseId,
					'lines':[],
					};
		var row;
      	$('.ls_tpv_line_checkbox').each(function(i,e){
      		if ($(e).is(":checked"))
			{
				row={'prod_id':$(e).attr('id').replace('ls_tpv_line_chkbox_',''),
					 'ticket_id':_TPV.ticket.id,
					 'checked':'1'
					};
			}
			else
			{
				row={'prod_id':$(e).attr('id').replace('ls_tpv_line_chkbox_',''),
					 'ticket_id':_TPV.ticket.id,
					 'checked':'0'
					};
			}
			data.lines.push(row);
      	});
		var result = ajaxDataSend('setToApartado',data);
		if (result.status == 'ERROR')
		{
			alert('Se produjo el siguiente error: '+"\r\n\r\n"+result.errors[0]);
		}
		else
		{
			$('#btnTicketApartado').css('background-color','green');
		}
	},
	showManualProducts:function()
	{
		$('#idManualProducts').dialog({ modal: true });
		$('#idManualProducts').dialog({width:640});
	},
	showTicketOptions:function(idProduct){
		$('.leftBlock').hide();
		$('#products').show();
		$('#ticketOptions').html($('#ticketLine'+idProduct).find('.colActions').html()).show();
		_TPV.addInfoProduct(idProduct);
				
		$('#tablaTicket tr').removeClass('lineSelected');
		$('#ticketLine'+idProduct).addClass('lineSelected');
	},
	hideTicketOptions:function(idProduct){

		$('#ticketOptions').html($('#ticketLine'+idProduct).find('.colActions').html()).hide();

	},
	showHistoryOptions:function(idTicket){

		$('#historyOptions .colActions').html($('#historyTicket'+idTicket).find('.colActions').html()).show();

	
		$('#historyOptions').show();
		$('#historyTable tr').removeClass('lineSelected');
		$('#historyTicket'+idTicket).addClass('lineSelected');
	},
	hideHistoryOptions:function(idTicket){

		$('#historyOptions .colActions').html($('#historyTicket'+idTicket).find('.colActions').html()).hide();

	
		$('#historyOptions').hide();

	},
	showHistoryFacOptions:function(idTicket){

		$('#historyFacOptions .colActions').html($('#historyFacTicket'+idTicket).find('.colActions').html()).show();

	
		$('#historyFacOptions').show();
		$('#historyFacTable tr').removeClass('lineSelected');
		$('#historyFacTicket'+idTicket).addClass('lineSelected');
	},
	hideHistoryFacOptions:function(idTicket){

		$('#historyFacOptions .colActions').html($('#historyFacTicket'+idTicket).find('.colActions').html()).hide();

	
		$('#historyFacOptions').hide();

	},
	showStockOptions:function(idProduct,idWarehouse){

		$('#stockOptions .colActions').html($('#stock'+idProduct+'_'+idWarehouse).find('.colActions').html()).show();

	
		$('#stockOptions').show();
		$('#storeTable tr').removeClass('lineSelected');
		$('#stock'+idProduct+'_'+idWarehouse).addClass('lineSelected');
		var result = ajaxDataSend('getNumberofSustitute',idProduct);
		$("#numbersustitute").html(result);
		$("#sustituteProd").css("display","block");
		$("#sustituteProd").click(function(){_TPV.searchByStock(-8,idProduct)});
		//Complementos
		var result = ajaxDataSend('getNumberofComplement',idProduct);
		$("#numbercomplementos").html(result);
		$("#ComplementProd").css("display","block");
		$("#ComplementProd").click(function(){_TPV.searchByStock(-9,idProduct)});
	},
	hideStockOptions:function(idProduct,idWarehouse){

		$('#stockOptions .colActions').html($('#stock'+idProduct+'_'+idWarehouse).find('.colActions').html()).hide();

	
		$('#stockOptions').hide();

	},
	rc_getCheckedProducts:function()
	{
		var rc_pid = [];
		var rc_rid = 0;
      	$('.ls_tpv_line_checkbox').each(function(i,e){
      		rc_rid = 0;
      		if ($(e).is(":checked"))
			{
				rc_rid = parseInt($(e).attr('id').replace('ls_tpv_line_chkbox_',''));
				if(rc_rid > 0)
				{
					rc_pid.push(rc_rid);
				}
			}
      	});
      	return rc_pid;
	},
	rc_askTransfer:function(rc_p)
	{
		$('#rc_confirm_warehouse_transfer').dialog('close');
		var data = {
					'ticket_id':_TPV.ticket,
					'ticket_ref':'',
					'products':rc_p,
					'target':_TPV.warehouseId,
					'source': null //$('#rc_transfer_warehouse_id').val()
					};
		var result = ajaxDataSend('rc_askForWarehouseTransfer',data);
		if (result=='1')
		{
			window.open(rc_url_root + "/product/stock/massstockmove.php?mainmenu=products&leftmenu=&draft=1");
		}
		return false;
	},
	rc_isOrder:function(tr='')
	{
		if (tr.length == 0)
		{
			tr = $('#btnTicketRef').text();
		}
		if (tr.length > 8 && tr.substr(0,8).indexOf('-') == -1 && tr.indexOf('(PROV') == -1)
		{
			return true;
		}
		else
		{
			return false;
		}
	},
	rc_higher_than_stock:function()
	{
		var dif = false;
		
		if(parseInt(_TPV.ticket.type) == 0)
		{
			this.lines.forEach(function(e,i){
				if (parseFloat(e.qty_ent) > parseFloat(e.stock))
				{
					dif = true;
				}
			});
		}
		return dif;
	},
	rc_deliveryDiff:function()
	{
		var dif = false;
		var some = false;
		if(parseInt(_TPV.ticket.type) == 0)
		{
			$('#listado_productos_ticket tr').each(function(i,e){
				// Verificar si existen diferencias entre pedido y entregado
				if (parseFloat($(e).find('.cant').text()) != parseFloat($(e).find('.qty_ent').text()))
				{
					dif = true;
				}
				// Verificar si se va a entregar algo
				if (parseFloat($(e).find('.qty_ent').text()) != 0)
				{
					some = true;
				}
		});
		}
		return (dif && some);
	}
});

// CLASS TICKET LINE **********************************************************************
var TicketLine = jQuery.Class({
	init: function()
	{
		this.id = 0;
		this.idProduct = 0;
		this.ref = 0;
		this.label = '';
		this.description = '';
		this.discount = 0;
		this.cant = 1;
		this.idTicket = 0;
		this.localtax1_tx = 0;
		this.localtax2_tx = 0;
		this.tva_tx = 0;
		this.price = 0;//pu_ht
		this.price_ttc = 0;//pu_ht+pu_tva
		this.total = 0;//total_ht+total_tva+total_localtax1+total_localtax2
		this.price_min_ttc = 0;
		this.price_base_type = '';
		this.fk_product_type = 0;
		this.total_ttc = 0;//total_ht+total_tva
        this.total_ttc_without_discount = 0;
        this.diff_price = 0;
        this.ls_stock_mv_code = null;
        this.ls_warehouse_status = null;
        this.ls_warehouse_status_by = null;
        this.ls_warehouse_status_date = null;
        this.qty_ent = null;
		this.flag = 0;
		this.stock = 0;
		this.dias_entrega=0;
	},
	getHtml:function(){
		var hide = "$('#info_product').toggle()";
    	var chkd = '';

    	var estado_v = '<td>&nbsp;</td>';
    	if (parseInt(this.ls_warehouse_status) in rc_stLbl)
    	{
			estado_v= '<td style="background-color:'+rc_stBak[parseInt(this.ls_warehouse_status)]+
						  ';color:'+rc_stClr[parseInt(this.ls_warehouse_status)]+'">'
						  ;
  			if (parseInt(this.ls_warehouse_status) == 3)
  			{
  				estado_v += '<span title="Código de Inventario: '+this.ls_stock_mv_code+'">'+
	  		 				rc_stLbl[parseInt(this.ls_warehouse_status)]+
							'</span>'
							;
  			}
  			else
  			{
  				estado_v += rc_stLbl[parseInt(this.ls_warehouse_status)];
  			}
  			estado_v += '</td>';
    	}
    	
		var warningSixmonth='';
		if (typeof this.qty_ent == 'undefined')
		{
			this.qty_ent = this.cant;
		}
    	if(this.flag == 1)
		{
			warningSixmonth+='<img src="'+imgwarning+'">';
		}
    	if (this.ls_warehouse_status == '7')
    	{
	   		$('#btnTicketApartado').css('background-color','green');
	   	}
       	if (this.ls_warehouse_status >= '1' && this.ls_warehouse_status != '10')
       	{
       		$('#btnTicketSendFront').css('background-color','green');
       	}
		var chkbx = '<input type="checkbox" class="ls_tpv_line_checkbox" onclick="ls_tpv_switch_line_checkbox();" id="ls_tpv_line_chkbox_'+this.idProduct+'"'+chkd+'> ';
		if(this.diff_price == 0){
			if(parseInt(this.stock) < 0)
				return	'<tr id="ticketLine'+this.idProduct+'">'+
						'<td class="idCol">'+this.idProduct+'</td>'+
						'<td style="text-align: center;" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+warningSixmonth+this.ref+'</td>'+
						'<td style="text-align: center;color: red;" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+this.stock+'</td>'+
						'<td class="description" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+chkbx+this.label+'</td onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+
						estado_v+
						'<td class="price" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+displayPrice(this.total/this.cant)+'</td>'+
						'<td class="discount" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+this.discount+'%</td>'+
						'<td class="price_d" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+displayPrice(this.total/this.cant)+'</td>'+
						'<td class="cant">'+this.cant+'<a onclick="_TPV.ticket.editTicketLine('+this.idProduct+');"><span class="fas fa-pencil-alt marginleftonly" style=" color: #444;" title="Editar cantidad"></span></a></td>'+
						'<td class="qty_ent">'+this.qty_ent+'</td>'+
						'<td class="total" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+displayPrice(this.total)+'</td>'+
						'<td class="colActions"><a class="action edit" onclick="_TPV.ticket.editTicketLine('+this.idProduct+');"></a><a class="action" onclick="_TPV.ticket.searchSustitutes('+this.idProduct+');"><img title="Sustitutos" src="./img/sustitutes.png" style="width: 50px;height: 50px;"></a><a class="action" onclick="_TPV.ticket.searchComplements('+this.idProduct+');"><img title="Complementos" src="./img/complement.png" style="width: 50px;height: 50px;"></a><a class="action delete" onclick="_TPV.ticket.deleteLine('+this.idProduct+');"></a><a class="action info" onclick="'+hide+'"></a><a class="action close" onclick="_TPV.ticket.hideTicketOptions('+this.idProduct+')"></a></td></tr>';
			else
				return	'<tr id="ticketLine'+this.idProduct+'">'+
						'<td class="idCol">'+this.idProduct+'</td>'+
						'<td style="text-align: center;" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+warningSixmonth+this.ref+'</td>'+
						'<td style="text-align: center;" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+this.stock+'</td>'+
						'<td class="description" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+chkbx+this.label+'</td>'+
						estado_v+
						'<td class="price" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+displayPrice(this.total/this.cant)+'</td>'+
						'<td class="discount" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+this.discount+'%</td>'+
						'<td class="price_d" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+displayPrice(this.total/this.cant)+'</td>'+
						'<td class="cant">'+this.cant+'<a onclick="_TPV.ticket.editTicketLine('+this.idProduct+');"><span class="fas fa-pencil-alt marginleftonly" style=" color: #444;" title="Editar cantidad"></span></a></td>'+
						'<td class="qty_ent">'+this.qty_ent+'</td>'+
						'<td class="total" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+displayPrice(this.total)+'</td>'+
						'<td class="colActions"><a class="action edit" onclick="_TPV.ticket.editTicketLine('+this.idProduct+');"></a><a class="action" onclick="_TPV.ticket.searchSustitutes('+this.idProduct+');"><img title="Sustitutos" src="./img/sustitutes.png" style="width: 50px;height: 50px;"></a><a class="action" onclick="_TPV.ticket.searchComplements('+this.idProduct+');"><img title="Complementos" src="./img/complement.png" style="width: 50px;height: 50px;"></a><a class="action delete" onclick="_TPV.ticket.deleteLine('+this.idProduct+');"></a><a class="action info" onclick="'+hide+'"></a><a class="action close" onclick="_TPV.ticket.hideTicketOptions('+this.idProduct+')"></a></td></tr>';
		}
		else{
			var txt = ajaxDataSend('Translate','DiffPrice');
			if(parseInt(this.stock) < 0)
				return	'<tr id="ticketLine'+this.idProduct+'">'+
						'<td class="idCol">'+this.idProduct+'</td>'+
						'<td style="text-align: center;" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+warningSixmonth+chkbx+this.ref+'</td>'+
						'<td style="text-align: center;color: red;" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+this.stock+'</td>'+
						'<td class="description" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+chkbx+this.label+'</td>'+
						'<td class="price_d" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')"><img style="float: left; margin: 3% 0px 0px 26%;" src="img/alert.png" title="'+txt+'">  '+displayPrice(this.total/this.cant)+'</td>'+
						'<td class="discount" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+this.discount+'%</td>'+
						'<td class="price" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')"><img style="float: left; margin: 3% 0px 0px 26%;" src="img/alert.png" title="'+txt+'">  '+displayPrice(this.total/this.cant)+'</td>'+
						'<td class="cant">'+this.cant+'<a onclick="_TPV.ticket.editTicketLine('+this.idProduct+');"><span class="fas fa-pencil-alt marginleftonly" style=" color: #444;" title="Editar cantidad"></span></a></td>'+
						'<td class="total" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+displayPrice(this.total)+'</td>'+
						'<td class="colActions"><a class="action edit" onclick="_TPV.ticket.editTicketLine('+this.idProduct+');"></a><a class="action" onclick="_TPV.ticket.searchSustitutes('+this.idProduct+');"><img title="Sustitutos" src="./img/sustitutes.png" style="width: 50px;height: 50px;"></a><a class="action" onclick="_TPV.ticket.searchComplements('+this.idProduct+');"><img title="Complementos" src="./img/complement.png" style="width: 50px;height: 50px;"></a><a class="action delete" onclick="_TPV.ticket.deleteLine('+this.idProduct+');"></a><a class="action info" onclick="'+hide+'"></a><a class="action close" onclick="_TPV.ticket.hideTicketOptions('+this.idProduct+')"></a></td></tr>';
			else
				return	'<tr id="ticketLine'+this.idProduct+'">'+
						'<td class="idCol">'+this.idProduct+'</td>'+
						'<td style="text-align: center;" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+warningSixmonth+chkbx+this.ref+'</td>'+
						'<td style="text-align: center;" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+this.stock+'</td>'+
						'<td class="description" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+chkbx+this.label+'</td>'+
						'<td class="price_d" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')"><img style="float: left; margin: 3% 0px 0px 26%;" src="img/alert.png" title="'+txt+'">  '+displayPrice(this.total/this.cant)+'</td>'+
						'<td class="discount" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+this.discount+'%</td>'+
						'<td class="price" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')"><img style="float: left; margin: 3% 0px 0px 26%;" src="img/alert.png" title="'+txt+'">  '+displayPrice(this.total/this.cant)+'</td>'+
						'<td class="cant">'+this.cant+'<a onclick="_TPV.ticket.editTicketLine('+this.idProduct+');"><span class="fas fa-pencil-alt marginleftonly" style=" color: #444;" title="Editar cantidad"></span></a></td>'+
						'<td class="cant">'+this.qty_ent+'</td>'+
						'<td class="total" onclick="_TPV.ticket.showTicketOptions('+this.idProduct+')">'+displayPrice(this.total)+'</td>'+
						'<td class="colActions"><a class="action edit" onclick="_TPV.ticket.editTicketLine('+this.idProduct+');"></a><a class="action" onclick="_TPV.ticket.searchSustitutes('+this.idProduct+');"><img title="Sustitutos" src="./img/sustitutes.png" style="width: 50px;height: 50px;"></a><a class="action" onclick="_TPV.ticket.searchComplements('+this.idProduct+');"><img title="Complementos" src="./img/complement.png" style="width: 50px;height: 50px;"></a><a class="action delete" onclick="_TPV.ticket.deleteLine('+this.idProduct+');"></a><a class="action info" onclick="'+hide+'"></a><a class="action close" onclick="_TPV.ticket.hideTicketOptions('+this.idProduct+')"></a></td></tr>';
		}
	},
	setLineByIdProducts:function(idProduct){
		var info = new Object();
		info['product']=idProduct;
		if(_TPV.ticket.customerId != 0){
			info['customer'] = _TPV.ticket.customerId;
		}
		else
		{
			info['customer'] = _TPV.customerId;
		}
		//console.log('setLineByIdProducts');
		
		var result = getCacheProduct(info['customer'],idProduct);

		if(result.length>0){
			//if(result[0]["stock"] == "all" || result[0]["stock"] > 0){
				_TPV.products[idProduct]= result[0];
				
				//cada vez que se elige un producto se carga de base de datos	
				var product = _TPV.products[idProduct];
				
				var data = new Object();
				data['customer'] = info['customer'];
				data['tva'] = product.tva_tx;
				var localtax = {"1":0,"2":0}; //ajaxDataSend('getLocalTax',data);
						
				product["cant"]=1;
				product["remise_percent_global"]=0;
				product["localtax1_tx"] = localtax['1'];
				product["localtax2_tx"] = localtax['2'];
				if((_TPV.discount < result[0]["discount_percent"]) || (_TPV.discount == 0 && result[0]["discount_percent"]) > 0){
					this.setDiscount(result[0]["discount_percent"]);
				}
				if(localtax['1'] != 0 || localtax['2'] != 0){
					var result = ajaxDataSend('calculePrice',product);
					this.price = result["pu_ht"];
					this.price_ttc = parseFloat(result["pu_ht"])+parseFloat(result["pu_tva"]);
					this.total = result["total_ttc"];
					this.total_ttc = parseFloat(result["total_ht"])+parseFloat(result["total_tva"]);
			        this.total_ttc_without_discount = result["total_ttc_without_discount"];
				}
				else{
					this.price = product.price;
					this.price_ttc = product.price_ttc;
					this.total = product.price_ttc;
					this.total_ttc = product.price_ttc;
			        this.total_ttc_without_discount = product.price_ttc;
				}
				this.idProduct = idProduct;
				this.ref = product.ref;
				this.stock = product.stock;
				this.label = product.label;
				this.description = product.description;
				this.ls_warehouse_status = product.ls_warehouse_status;
				this.localtax1_tx = localtax['1'];
				this.localtax2_tx = localtax['2'];
				this.tva_tx = product.tva_tx;
				this.idTicket = _TPV.ticket.id;
				this.price_min_ttc = product.price_min_ttc;
				this.price_base_type = product.price_base_type;
				this.fk_product_type = product.fk_product_type;
				this.remise_percent_global = 0;
				this.diff_price = product.diff_price;
				this.flag= parseInt(product.flag);
				this.dias_entrega= parseInt(product.delivery_time_days);
				this.qty_ent = product.qty_ent
			/*}
			else{
				//Muestro un error diciendo que no hay stock ni se le espera...
				var txt=ajaxDataSend('Translate','NoStockEnough');
				_TPV.showError(txt);
			}*/
		}
		
		
	},
	setLineByIdLine:function(idProduct){
		if(typeof _TPV.ticket.oldproducts=='undefined'){
			return;	
		}
		var lines = _TPV.ticket.oldproducts;
		var line = null;
		for(var i=0;i<lines.length;i++)
		{
			if(lines[i]['idProduct']==idProduct){
				line = lines[i];
				break;
			}
		}
		if(!line)
			return 1;
		this.idProduct = idProduct;
		this.ref = product.ref;
		this.stock = product.stock;
		this.label = line.label;
		this.discount = 0;
		this.description = line.description;
		this.localtax1_tx = line.localtax1_tx;
		this.localtax2_tx = line.localtax2_tx;
		this.tva_tx = line.tva_tx;
		this.price = line.price;///(1-line.discount/100);
		this.cant = line.cant;
		this.price_ttc = line.price_ttc;
		this.total = line.total_ttc;
		this.price_min_ttc = line.price_min_ttc;
		this.price_base_type = line.price_base_type;
		this.fk_product_type = line.fk_product_type;
		this.total_ttc = line.total_ttc;
		this.ls_warehouse_status = line.ls_warehouse_status;
		this.qty_ent = line.qty_ent;
		this.price_ttc = line.total_ttc/line.cant;
		if(_TPV.ticket.discount_percent)
			this.remise_percent_global = _TPV.ticket.discount_percent;
		else
			this.remise_percent_global = 0;
        this.total_ttc_without_discount = line.total_ttc;
		
		return 0;
	},
	setQuantity : function(cant){
		number = parseFloat(cant);
		// Add Quantity
		this.cant = number;
	},
	setQtyEnt : function (qty_ent){
		number = parseFloat(qty_ent);
		this.qty_ent = number;
	},
	setDiscount : function(discount){
		quantitydiscount = parseFloat(discount); 
		if(quantitydiscount > 100 || quantitydiscount < 0)
			quantitydiscount=0;
		// Add Discount
		this.discount = quantitydiscount;
	},
	setPrice : function(new_price){
		price = parseFloat(new_price);
		price_old = Math.round(this.price_ttc*round_factor)/round_factor;
		if(price == price_old)
			return;
		// Add New Price
		if(price < this.price_min_ttc){
			if(_TPV.isPropal == 0){
				console.log(price);
				console.log(this.price_min_ttc);
				var txt=ajaxDataSend('Translate','PriceMinError');
				_TPV.showError(txt);
			}else{
				tva = parseFloat(this.tva_tx);
				this.price_ttc = price;
				this.price_base_type = "TTC";
			}
		}
		else{
			tva = parseFloat(this.tva_tx);
			this.price_ttc = price;
			this.price_base_type = "TTC";
		}	
	},
	setNote : function(note){
		// Add Note
		this.note = note;
				
	},
	setTotal : function(total){
		// Add Total
		this.total = total;
		$('#ticketLine'+this.idProduct).find('.total').html(displayPrice(total));
	},
	showTotal : function(){
		//ajaxDataSend('Translate','shoeTotal');
		var line = this;
		if(_TPV.ticket.type == 0){
			line["remise_percent_global"] = 0;
			if(!line["price_base_type"])
				line["price_base_type"] = "TTC";
					
			var result = ajaxDataSend('calculePrice',line);
			if (result['total_ttc'] < this.cant*this.price_min_ttc)
			{
				if(_TPV.isPropal == 0){
					var txt=ajaxDataSend('Translate','PriceMinError');
					_TPV.showError(txt);
					this.discount = 0;
				}else{
					this.price = result["pu_ht"];
					this.price_ttc = parseFloat(result["pu_ht"])+parseFloat(result["pu_tva"]);
					this.total = result["total_ttc"];
					this.total_ttc = parseFloat(result["total_ht"])+parseFloat(result["total_tva"]);
					this.total_ttc_without_discount = result["total_ttc_without_discount"];
				}
			}else{
				this.price = result["pu_ht"];
				this.price_ttc = parseFloat(result["pu_ht"])+parseFloat(result["pu_tva"]);
				this.total = result["total_ttc"];
				this.total_ttc = parseFloat(result["total_ht"])+parseFloat(result["total_tva"]);
				this.total_ttc_without_discount = result["total_ttc_without_discount"];
			}

			$('#ticketLine'+this.idProduct).find('.cant').html(this.cant+'<a onclick="_TPV.ticket.editTicketLine('+this.idProduct+');"><span class="fas fa-pencil-alt marginleftonly" style=" color: #444;" title="Editar cantidad"></span></a>');
			$('#ticketLine'+this.idProduct).find('.qty_ent').html(this.qty_ent);
			$('#ticketLine'+this.idProduct).find('.discount').html(this.discount+'%');
			if (this.cant != this.qty_ent)
			{
				var bgc = '#deb306';
			}
			else
			{
				var bgc = '#1DB100';
			}
			if ($('#ticketLine'+this.idProduct+' td:nth-child(5)').text()=='Surtido')
			{
				$('#ticketLine'+this.idProduct+' td:nth-child(5)').css('background',bgc);
			}
			if(line.diff_price==0)
				$('#ticketLine'+this.idProduct).find('.price').html(displayPrice(result["pu_ttc"]));
			else{
				var txt = ajaxDataSend('Translate','DiffPrice');
				$('#ticketLine'+this.idProduct).find('.price').html('<img style="float: left; margin: 3% 0px 0px 26%;" src="img/alert.png" title="'+txt+'"> '+displayPrice(result["pu_ttc"])+'');
			}
			$('#ticketLine'+this.idProduct).find('.price_d').html(displayPrice(this.total/this.cant));
			$('#ticketLine'+this.idProduct).find('.total').html(displayPrice(this.total));
		}
		else{
			if(this.cant<0 && this.price<0){
				this.cant=this.cant*-1;
				this.price=this.price*-1;
			}
			line["remise_percent_global"] = 0;
			if(!line["price_base_type"])
				line["price_base_type"] = "TTC";
					
			var result = ajaxDataSend('calculePrice',line);
			if (result['total_ttc'] < this.cant*this.price_min_ttc)
			{
				if(_TPV.isPropal == 0){
					var txt=ajaxDataSend('Translate','PriceMinError');
					_TPV.showError(txt);
					this.discount = 0;
				}
				else{
					this.price = result["pu_ht"];
					this.price_ttc = parseFloat(result["pu_ht"])+parseFloat(result["pu_tva"]);
					this.total = result["total_ttc"];
					this.total_ttc = parseFloat(result["total_ht"])+parseFloat(result["total_tva"]);
					this.total_ttc_without_discount = result["total_ttc_without_discount"];
				}
			}
			else{
				this.price = result["pu_ht"];
				this.price_ttc = parseFloat(result["pu_ht"])+parseFloat(result["pu_tva"]);
				this.total = result["total_ttc"];
				this.total_ttc = parseFloat(result["total_ht"])+parseFloat(result["total_tva"]);
			    this.total_ttc_without_discount = result["total_ttc_without_discount"];
			}
			$('#ticketLine'+this.idProduct).find('.cant').html(this.cant);	
			$('#ticketLine'+this.idProduct).find('.qty_ent').html(this.qty_ent);
			$('#ticketLine'+this.idProduct).find('.discount').html(this.discount+'%');
			if(line.diff_price==0)
				$('#ticketLine'+this.idProduct).find('.price').html(displayPrice(result["pu_ttc"]));
			else{
				var txt = ajaxDataSend('Translate','DiffPrice');
				$('#ticketLine'+this.idProduct).find('.price').html('<img style="float: left; margin: 3% 0px 0px 26%;" src="img/alert.png" title="'+txt+'"> '+displayPrice(result["pu_ttc"])+'');
			}
			$('#ticketLine'+this.idProduct).find('.total').html(displayPrice(this.total));
			$('#ticketLine'+this.idProduct).find('.price_d').html(displayPrice(this.total/this.cant));
			//$('#ticketLine'+this.idProduct).find('.total').html(displayPrice(this.total_ttc));
		}
		_TPV.ticket.calculeTotal();
	}
});


// CLASS CUSTOMER *******************************************************************
var Customer = jQuery.Class({
	init: function()
	{
		this.id = 0;
		this.nom = '';
		this.prenom = '';
		this.idprof1 = '';
		this.address = '';
		this.cp = '';
		this.ville = '';	
		this.tel = '';
		this.email = '';
	}
	
});

// CLASS PRODUCT ********************************************************
var Product = jQuery.Class({
	init: function()
	{
		this.id = 0;
		this.label = '';
		this.price_ttc = 0;
		this.ref = '';
		this.tax = 0;
		this.price_min_ttc = 0;
		
	}
	
});
//CLASS CASH ************************************************************
var Cash = jQuery.Class({
	init: function()
	{
		this.moneyincash = 0;
		this.type = 1;
		this.printer = 1;
		this.employeeId = 0;
		this.mail = 1;
		this.d_1000 = 0;
		this.d_500 = 0;
		this.d_200 = 0;
		this.d_100 = 0;
		this.d_50 = 0;
		this.d_20 = 0;
		this.d_10 = 0;
		this.d_5 = 0;
		this.d_2 = 0;
		this.d_1 = 0;
		this.d_05 = 0;
		this.quantity_delivery = 0;
	}
	
});


// CLASS TPV  ***********************************************************
var TPV = jQuery.Class({
	
	init: function()
	{
		this.categories = new Array();
		this.products = new Array();
		this.places = new Array();
		this.ticket = new Ticket();
		this.activeIdProduct = 0;
		this.temporalpro = new Array();
		this.restar_por_pagar = 0;
		this.limite_de_credito = 0;
		this.gastos = 0;
		this.employeeId = 0;
		this.barcode = 0;
		this.infoProduct = 0;
		this.defaultConfig = new Array();
		this.ticketState = 0; // 0 => Normal, 1 => Blocked to add products, 2 => Return products  
		this.cash = new Cash();
		this.cashId = 0;
		this.warehouseId = 0;
		this.fullscreen = 0;
		this.faclimit = 0;
		this.discount;
		this.points = 0;
		this.coupon = 0;
		this.showingProd = 0;
	},
	
	setButtonEvents:function()
	{
		$('#btnNewTicket').click(function() {
			_TPV.ticket.newTicket();
		});
		
		$('#btnOkTicket').click(function() {
			if(confirm("\r\nATENCIÓN: Revisar unidades entregadas.\r\n\r\n¿ESTÁS SEGURO DE GENERAR EL TICKET?")){
				_TPV.ticket.okTicket();
			}
		});
		$('#btnHistory').click(function() {
			_TPV.getHistory();
		});
		$('#btnSaveTicket').click(function() {
			if(confirm("¿Estas seguro de generar un provisional?")){
				_TPV.ticket.saveTicket();
			}
		});
		$('#btnCancelTicket').click(function() {
			_TPV.ticket.cancelTicket();
		});
		$('#btnReturnTicket').click(function() {
			var data = [];
			var cnt = 0;
			$('.ls_tpv_line_checkbox').each(function(i,e){
				if ($(e).is(":checked"))
				{
					row={'prod_id':$(e).attr('id').replace('ls_tpv_line_chkbox_',''),
						'ticket_id':_TPV.ticket.id,
						'checked':'1'
					};
					cnt ++;
				}
				else{
					row={'prod_id':$(e).attr('id').replace('ls_tpv_line_chkbox_',''),
						'ticket_id':_TPV.ticket.id,
						'checked':'0'
					};
				}
				data.push(row);
			});
			if (cnt <= 0)
			{
				alert('Seleccione al menos un producto a devolver.');
				return;
				
			}
			if(parseInt(_TPV.ticket.state) != 2) {
				_TPV.ticketState = 2;
				_TPV.ticket.setButtonState(false);
				var id = _TPV.ticket.idsource;
				var discount_percent = _TPV.ticket.discount_percent;
				var discount_qty = _TPV.ticket.discount_qty;
				var lines = _TPV.ticket.oldproducts;
				var ret_points = _TPV.ticket.ret_points;
				var customerid = _TPV.ticket.customerId;
				var tempId = _TPV.ticket.proyectId;
				var customername = $('#infoCustomer_').text();
				var mode = _TPV.ticket.mode;
				_TPV.ticket.newTicket();
				_TPV.ticket.idsource = id;
				_TPV.ticket.ret_points = parseInt(ret_points);
				_TPV.ticket.discount_percent = discount_percent;
				_TPV.ticket.discount_qty = discount_qty;
				_TPV.ticket.type = 1;
				_TPV.ticket.customerId = customerid;
				_TPV.ticket.mode = mode;
				_TPV.ticket.proyectId = tempId;
				$.each(lines, function (id, item) {
					$.each(data, function (rowid, dat) {
						var line = new TicketLine();
						if (item["idProduct"] == dat["prod_id"] && dat["checked"] == "1") {
							line.setLineByIdProducts(item["idProduct"]);
							line.setDiscount(parseInt(item["discount"]));
							line.setQtyEnt(item['cant']);
							if(_TPV.ticket.type != 0)
							{
								var rc_price = parseFloat(item["total_ttc"]) / parseFloat(item['cant']);
								line.setPrice(rc_price);
							}
							_TPV.ticket.total = _TPV.ticket.total + line.total_ttc;
							_TPV.ticket.setLine(item["idProduct"], line);
							$('#tablaTicket').append(line.getHtml());
							_TPV.ticket.addCant(item['idProduct'], item['cant']);
						}
					});
				});

				$('#infoCustomer').html(customername);
				$('#infoCustomer_').html(customername);
				$('#Customer_remise').html(_TPV.ticket.discount_percent+"%");
				$('#btnTicketRef').show();
				if (_TPV.ticket.NoCredit == 0)
					$('#btnOkTicket').show();
			}
			else {
				/*$.each(data, function (rowid, dat) {//x
					if(dat["checked"] == "1") {
						_TPV.ticket.getLine(dat["prod_id"]).setQuantity(0);
						_TPV.ticket.getLine(dat["prod_id"]).showTotal();
						hideLeftContent();
						$("#ticketLine" + dat["prod_id"]).find("td").css("color", "red");
					}
				});
				_TPV.ticket.difpayment = _TPV.ticket.total-_TPV.ticket.customerpay;
				$("#totalRestToPay").html(displayPrice(parseFloat(_TPV.ticket.difpayment)));*/
				_TPV.ticketState = 2;
				_TPV.ticket.setButtonState(false);
				var id = _TPV.ticket.idsource;
				var discount_percent = _TPV.ticket.discount_percent;
				var discount_qty = _TPV.ticket.discount_qty;
				var lines = _TPV.ticket.lines;
				var ret_points = _TPV.ticket.ret_points;
				var customerid = _TPV.ticket.customerId;
				var tempId = _TPV.ticket.proyectId;
				var customername = $('#infoCustomer_').text();
				var mode = _TPV.ticket.mode;
				_TPV.ticket.newTicket();
				_TPV.ticket.idsource = id;
				_TPV.ticket.ret_points = parseInt(ret_points);
				_TPV.ticket.discount_percent = discount_percent;
				_TPV.ticket.discount_qty = discount_qty;
				_TPV.ticket.type = 1;
				_TPV.ticket.customerId = customerid;
				_TPV.ticket.mode = mode;
				_TPV.ticket.proyectId = tempId;
				$.each(lines, function (id, item) {
					$.each(data, function (rowid, dat) {
						var line = new TicketLine();
						if (item["idProduct"] == dat["prod_id"] && dat["checked"] == "1") {
							line.setLineByIdProducts(item["idProduct"]);
							line.setDiscount(parseInt(item["discount"]));
							//line.setPrice(parseInt(item["price_ttc"]));console.log(line);
							_TPV.ticket.total = _TPV.ticket.total + line.total_ttc;
							_TPV.ticket.setLine(item["idProduct"], line);
							$('#tablaTicket').append(line.getHtml());
							_TPV.ticket.addCant(item['idProduct'], item['cant']);
						}
					});
				});

				$('#infoCustomer').html(customername);
				$('#infoCustomer_').html(customername);
				$('#Customer_remise').html(_TPV.ticket.discount_percent+"%");
				$('#btnTicketRef').show();
				if (_TPV.ticket.NoCredit == 0)
					$('#btnOkTicket').show();
			}
		});
		$('#btnViewTicket').click(function() {
			_TPV.ticket.viewTicket();
		});
		
		$('#btnAddCustomer').click(function() {
			_TPV.ticket.showAddCustomer();
		});
		$('#btnNewCustomer').click(function() {
			_TPV.ticket.showAddCustomer();
		});
		$('#btnAddDiscount').click(function() {
			_TPV.ticket.addDiscount();
		});
		$('#btnAddProduct').click(function() {
			_TPV.ticket.showAddProduct();
		});
		$('#btnTicketNote').click(function() {
			_TPV.ticket.addTicketNote();
		});
		$('#btnShowManualProducts').click(function() {
			_TPV.ticket.showManualProducts();
		});
		$('#btnTicketSendFront').click(function() {
			var ct = 0;
      		$('.ls_tpv_line_checkbox').each(function(i,e){
      			var prid = $(e).attr('id').replace('ls_tpv_line_chkbox_','');
				if(prid>0 && $(e).is(":checked"))
				{
					ct++;
				}
      		});
  			if (ct > 0)
  			{
				_TPV.ticket.sendToWarehouse();
  			}
  			else
  			{
  				alert('Seleccione al menos un producto a solictar.');
  			}
		});
		$('#btnTicketApartado').click(function() {
			var ct = 0;
      		$('.ls_tpv_line_checkbox').each(function(i,e){
      			var prid = $(e).attr('id').replace('ls_tpv_line_chkbox_','');
				if(prid>0 && $(e).is(":checked"))
				{
					ct++;
				}
      		});
  			if (ct > 0)
  			{
				_TPV.ticket.setToApartado();
  			}
  			else
  			{
  				alert('Seleccione al menos un producto a Apartar.');
  			}
		});
		$('#btnTicketHomeDelivery').click(function(){
			_TPV.ticket.showDeliveryDialog();
		});
		$('#btnFreight').click(function() {
			_TPV.ticket.showDemoFreight();
		});
		$('#btnLogout').click(function() {
			window.location.href = "./disconect.php";
		});
		$('#btnZoomCategories').click(function() {
			_TPV.ticket.showZoomProducts();
		});	
		$('#btnAddProductCart').click(function() {
			_TPV.ticket.addProductLine();
		});
		$('#btnHideInfo').click(function() {
			$('#short_description_content').toggle();
		});
		$('#btnHideInfoSt').click(function() {
			$('#short_description_content_st').toggle();
		});
		
		// Filter Product Search Events
		$('#id_product_search').live("keypress", function(e) {
	        if (e.keyCode == 13 || e.which == 13) {
	        	_TPV.searchProduct();
	        }
	        if(_TPV.defaultConfig['terminal']['barcode'] == 1){
	        	$('#id_product_search').focus();
	        }
	    });
		$('#img_product_search').click(function(){
			_TPV.searchProduct();
		});
		// Filter Sotck products Search Events
		$('#id_stock_search').live("keypress", function(e) {
	        if (e.keyCode == 13 || e.which == 13) {
	        	_TPV.searchByStock(1,_TPV.warehouseId);
	        }
	    });
		$('#img_stock_search').click(function(){
			_TPV.searchByStock(1,_TPV.warehouseId);
		});
		// Filter Cusotmer Search
		$('#id_customer_search_').live("keypress", function(e) {
	        if (e.keyCode == 13 || e.which == 13) {
	        	_TPV.searchCustomer();
	        }
	    });
		$('#img_customer_search').click(function(){
			_TPV.searchCustomer();
		});
		$('#tabStock').click(function(){
			$('#info_product_st').hide();
			_TPV.countByStock();
			//_TPV.searchByStock();
		});		
		$('#tabPlaces').click(function(){
			_TPV.searchByPlace();	
		});
		$('#id_place_search').live("keypress", function(e) {
	        if (e.keyCode == 13 || e.which == 13) {
	        	_TPV.searchByPlace();
	        }
	    });		
		$('#tabHistory').click(function(){
			_TPV.searchByRef(-1);
			_TPV.countByRef();
		});
		$('#tabHistoryFac').click(function(){
			_TPV.searchByRefFac(-1);
			_TPV.countByRefFac();
		});
		
		// Filter Reference Search 
		$('#id_ref_search').live("keypress", function(e) {
	        if (e.keyCode == 13 || e.which == 13) {
	        	_TPV.searchByRef(-1);
	        }
	    });
		$('#img_ref_search').click(function(){
			_TPV.searchByRef(-1);
		});
		$('#id_ref_fac_search').live("keypress", function(e) {
	        if (e.keyCode == 13 || e.which == 13) {
	        	_TPV.searchByRefFac(-1);
	        }
	    });
		$('#img_ref_fac_search').click(function(){
			_TPV.searchByRefFac(-1);
		});
		var productid = 0;
		$("#id_product_search").keypress(function(){
			$('#divSelectProducts').hide();
		});
		$('#closedropdown').click(function() {
			$('#divSelectProducts').hide();
			$('#id_product_search').val('');
		});
		$('#id_selectProduct').click(function() {
			productid = $(this).val()[0];
			if(productid!=0){
				_TPV.ticket.addLine(productid);
			}
		});
		/*$('#id_selectProduct').change(function() {
			if($(this).val()!=0){
				_TPV.ticket.addLine($(this).val());
				$('#divSelectProducts').hide();
				$('#id_product_search').val('');
			}	
		});*/
		/*$('.payment_types').each(function(){
				$(this).click(function() {
					$('#payment_coupon').hide();
					if(_TPV.points != null && _TPV.ticket.mode!=0){
						$('#payment_points').show();
						$('#payment_total_points').hide();
					}
					else
						{$('#payment_total_points').show();}
					$('#id_btn_add_ticket').show();
					$('.payment_types').removeClass('btnon');
					$(this).addClass('btnon');
					_TPV.ticket.setPaymentType($(this).find('a:first').attr('id').substring(7));
				});
		});*/
		$('#id_btn_coupon').click(function(){
			_TPV.ticket.showCoupon();
		});
		$('#line_discount').keyup(function(e){//normal mode
			if (e.keyCode == 13 || e.which == 13) {
				$('#id_btn_editTicketline').click();
			}
		});
		$('#line_price').keyup(function(e){//normal mode
			if (e.keyCode == 13 || e.which == 13) {
				$('#id_btn_editTicketline').click();
			}
		});
		$('#line_note').keyup(function(e){//normal mode
			if (e.keyCode == 13 || e.which == 13) {
				$('#id_btn_editTicketline').click();
			}
		});
		$('#line_quantity').keyup(function(e){//normal mode
			if (e.keyCode == 13 || e.which == 13) {
				$('#id_btn_editTicketline').click();
			}
			//_TPV.checkStock();
		});
		$('#line_qty_ent').keyup(function(e){//normal mode
			if (e.keyCode == 13 || e.which == 13) {
				$('#id_btn_editTicketline').click();
			}
			//_TPV.checkStock();
		});
		$('#line_quantity').blur(function(){//tactil mode
			_TPV.checkStock();
		});
		$('#points_client_id').keyup(function(){//normal mode
			_TPV.pointsClient();
		});
		$('#points_client_id').blur(function(){//tactil mode
			_TPV.pointsClient();
		});
		$('#pay_client_0').keyup(function(){//normal mode
			_TPV.payClient();
		});
		$('#pay_client_0').blur(function(){//tactil mode
			_TPV.payClient();
		});
		$('#pay_client_1').keyup(function(){//normal mode
			_TPV.payClient();
		});
		$('#pay_client_1').blur(function(){//tactil mode
			_TPV.payClient();
		});
		$('#pay_client_2').keyup(function(){//normal mode
			_TPV.payClient();
		});
		$('#pay_client_2').blur(function(){//tactil mode
			_TPV.payClient();
		});
		$('#pay_client_3').keyup(function(){//normal mode
			_TPV.payClient();
		});
		$('#pay_client_3').blur(function(){//tactil mode
			_TPV.payClient();
		});
		$('#pay_client_ret_0').keyup(function(){//normal mode
			_TPV.payClientRet();
		});
		$('#pay_client_ret_0').blur(function(){//tactil mode
			_TPV.payClientRet();
		});
		$('#pay_client_ret_1').keyup(function(){//normal mode
			_TPV.payClientRet();
		});
		$('#pay_client_ret_1').blur(function(){//tactil mode
			_TPV.payClientRet();
		});
		$('#pay_client_ret_2').keyup(function(){//normal mode
			_TPV.payClientRet();
		});
		$('#pay_client_ret_2').blur(function(){//tactil mode
			_TPV.payClientRet();
		});
		$('#pay_client_ret_3').keyup(function(){//normal mode
			_TPV.payClientRet();
		});
		$('#pay_client_ret_3').blur(function(){//tactil mode
			_TPV.payClientRet();
		});
		$('#ticket_discount_perc').keyup(function(e){//normal mode
			if (e.keyCode == 13 || e.which == 13) {
				$('#id_btn_add_discount').click();
			}
		});
		$('#pay_all_0').click(function(){//tactil mode
			if($('#pay_client_0').val() != "")
				var prev = $('#pay_client_0').val();
			else
				var prev = 0;
			$('#pay_client_0').val((parseFloat(_TPV.ticket.difpayment)  + parseFloat(prev)).toFixed(decimals));
			_TPV.payClient();
		});
		$('#pay_all_1').click(function(){//tactil mode
			if($('#pay_client_1').val() != "")
				var prev = $('#pay_client_1').val();
			else
				var prev = 0;
			$('#pay_client_1').val((parseFloat(_TPV.ticket.difpayment)  + parseFloat(prev)).toFixed(decimals));
			_TPV.payClient();
		});
		$('#pay_all_2').click(function(){//tactil mode
			if($('#pay_client_2').val() != "")
				var prev = $('#pay_client_2').val();
			else
				var prev = 0;
			$('#pay_client_2').val((parseFloat(_TPV.ticket.difpayment)  + parseFloat(prev)).toFixed(decimals));
			_TPV.payClient();
		});
		$('#pay_all_3').click(function(){//tactil mode
			if($('#pay_client_3').val() != "")
				var prev = $('#pay_client_3').val();
			else
				var prev = 0;
			$('#pay_client_3').val((parseFloat(_TPV.ticket.difpayment)  + parseFloat(prev)).toFixed(decimals));
			_TPV.payClient();
		});
		$('#pay_all_ret_0').click(function(){//tactil mode
			if($('#pay_client_ret_0').val() != "")
				var prev = $('#pay_client_ret_0').val();
			else
				var prev = 0;
			$('#pay_client_ret_0').val((parseFloat(_TPV.ticket.difpayment)  + parseFloat(prev)).toFixed(decimals));
			_TPV.payClientRet();
		});
		$('#pay_all_ret_1').click(function(){//tactil mode
			if($('#pay_client_ret_1').val() != "")
				var prev = $('#pay_client_ret_1').val();
			else
				var prev = 0;
			$('#pay_client_ret_1').val((parseFloat(_TPV.ticket.difpayment)  + parseFloat(prev)).toFixed(decimals));
			_TPV.payClientRet();
		});
		$('#pay_all_ret_2').click(function(){//tactil mode
			if($('#pay_client_ret_2').val() != "")
				var prev = $('#pay_client_ret_2').val();
			else
				var prev = 0;
			$('#pay_client_ret_2').val((parseFloat(_TPV.ticket.difpayment)  + parseFloat(prev)).toFixed(decimals));
			_TPV.payClientRet();
		});
		$('#pay_all_ret_3').click(function(){//tactil mode
			if($('#pay_client_ret_3').val() != "")
				var prev = $('#pay_client_ret_3').val();
			else
				var prev = 0;
			$('#pay_client_ret_3').val((parseFloat(_TPV.ticket.difpayment)  + parseFloat(prev)).toFixed(decimals));
			_TPV.payClientRet();
		});
		
		$('#id_btn_tpvtactil').click(function(){
			if($(this).hasClass('on')){
				$(this).removeClass('on');
				$(this).addClass('off');
				_TPV.tpvTactil(false);
			}
			else{
				$(this).addClass('on');
				$(this).removeClass('off');
				_TPV.tpvTactil(true);
			}
		});
		$('#id_btn_barcode').click(function(){
			if($(this).hasClass('on')){
				$(this).removeClass('on');
				$(this).addClass('off');
				_TPV.barcode=0;
			}
			else if($(this).hasClass('off')){
				$(this).addClass('on');
				$(this).removeClass('off');
				_TPV.barcode=1;
			}
		});
		
		
		$('#id_btn_infoproduct').click(function(){
			if($(this).hasClass('on')){
				$(this).removeClass('on');
				$(this).addClass('off');
				_TPV.showInfoProduct(false);
			}
			else{
				$(this).addClass('on');
				$(this).removeClass('off');
				_TPV.showInfoProduct(true);
			}
		});
		$('#id_btn_closeproduct').click(function(){
			$('#products').toggle();
			//$('#productSearch').toggle();
		});
		$('#id_btn_fullscreen').click(function(){

			if(_TPV.fullscreen == 0){
				var docElm = document.documentElement;
				if (docElm.requestFullscreen) {
				    docElm.requestFullscreen();
				}
				else if (docElm.mozRequestFullScreen) {
				    docElm.mozRequestFullScreen();
				}
				else if (docElm.webkitRequestFullScreen) {
				    docElm.webkitRequestFullScreen(docElm.ALLOW_KEYBOARD_INPUT);
				}
				_TPV.fullscreen = 1;
			}
			else{
				if (document.exitFullscreen) {
				    document.exitFullscreen();
				}
				else if (document.mozCancelFullScreen) {
				    document.mozCancelFullScreen();
				}
				else if (document.webkitCancelFullScreen) {
				    document.webkitCancelFullScreen();
				}
				_TPV.fullscreen = 0;
			}

		});
		$('#closedialogpaymentschange').click(function(){
			$('#idChangeTypePaiement').dialog('close');
		});
		$('#confirmpaymentschanges').click(function(){
			var info = [];
			$('.changetypep').each(function(){
				if($(this).val() != -1) {
					id = $(this).attr('id').split("_");// 1 => FROM, 2 => ROWID
					from = id [1];
					rowid = id[2];
					to = $(this).val();
					var pago = {"id":rowid,"from":from,"to":to};
					info.push(pago);
				}
			});
			var result = ajaxDataSend('changePayments',info);
			if(result == 0){
				$('#idChangeTypePaiement').dialog('close');
			}
		});
		$('#id_btn_closecash').click(function(){
			alert("Revisa que no falten gastos por cerrar antes de realizar el corte");
			var money = ajaxDataSend('getMoneyCash',null);
			$('#id_terminal_cash').val(displayPrice(money));
			$('#id_money_cash').val('');
			$('#idCloseCash').dialog({ modal: true });
			$('#idCloseCash').dialog({width:440});
			var paiements = ajaxDataSend('returnPaiementToChange',null);
			$(".payments_changes").html('');
			paiements.forEach(function(reg, index) {
				reg.forEach(function(reg2, index2) {
					var html = "";
					if(reg2[1].html != 'Sin registros') {
						html += "<tr>";
						html += "<td>" + reg2[1].ticketnumber + "</td><td>" + reg2[1].ref + "</td>";
						//html+="<td><input type=\"checkbox\" name=\"change_"+reg2[0]+"\" class=\"changep\" value=\""+reg2[1].rowid+"\"/></td>";
						html += "<td>" + reg2[1].html + "</td>";
						html += "</tr>";
					}else{
						html += "<tr>";
						html += "<td colspan='3'>" + reg2[1].html + "</td></tr>";
					}
					$("#payments_"+reg2[0]).html($("#payments_"+reg2[0]).html()+html);
				});
			});
			$('#idChangeTypePaiement').dialog({ modal: true });
			$('#idChangeTypePaiement').dialog({width:440});
			$('#id_btn_close_cash').unbind('click');
			$('#id_btn_close_cash').dblclick(function(e) {
				e.preventDefault();
			});
			
			$('#id_btn_close_cash').click(function(){
				if($("#cantidad_entregar").val() != '') {
					if (clickeado == false) {
						if ($('#id_money_cash').val())
							_TPV.cash.moneyincash = $('#id_money_cash').val();
						_TPV.cash.employeeId = _TPV.employeeId;
						if ($("#cantidad_entregar").val() < 0) {
							alert("Ingrese un valor positivo")
						} else {
							_TPV.cash.quantity_delivery = $("#cantidad_entregar").val();
							var result = ajaxDataSend('closeCash', _TPV.cash);
							$('#idCloseCash').dialog('close');
							if (!result)
								return;
							if (_TPV.cash.type == 1) {
								if (_TPV.defaultConfig['module']['print'] > 0 || _TPV.defaultConfig['module']['mail'] > 0) {
									if($("#cantidad_entregar").val() != 0){
										window.open(result.pdf, '_blank');
									}
									$('#idCashMode').dialog({modal: true});
									$('#idCashMode').dialog({width: 400});

									$('#id_btn_cashPrint').click(function () {
										$('#id_btn_cashPrint').unbind('click');
										_TPV.printing('closecash', result.id);
										$('#idCashMode').dialog("close");
										//if (_TPV.defaultConfig['module']['print_mode'] == 0)
										//$('#btnLogout').click();
									});

									$('#id_btn_cashMail').click(function () {
										$('#id_btn_cashMail').unbind('click');
										_TPV.mailCash(result.id, _TPV.defaultConfig['terminal']['id'])
										$('#idCashMode').dialog("close");

									});
								} else {
									//$('#btnLogout').click();
								}
							}
							clickeado = true;
						}
					} else {
						return false;
					}
				}else{
					alert('El campo "Cantidad a entregar" no debe estar vacío');
				}
			});
			function getMoney(){
				var mil=$("#mil").val() * 1000;
				var quin=$("#quin").val() * 500;
				var dosc=$("#dosc").val() * 200;
				var cien=$("#cien").val() * 100;
				var cinc=$("#cinc").val() * 50;
				var vein=$("#vein").val() * 20;
				var diez=$("#diez").val() * 10;
				var cinco=$("#cinco").val() * 5;
				var dos=$("#dos").val() * 2;
				var uno=$("#uno").val() * 1;
				var centavos=$("#centavos").val() * 0.5;
				var money =mil+quin+dosc+cien+cinc+vein+diez+cinco+dos+uno+centavos;
				return money;
			}
			$("#id_money_cash").bind("input",function(){
				var mon=getMoney();
				$(this).val(mon);
			});
			$("#mil").bind("input", function() {
				var mon=getMoney();
				_TPV.cash.d_1000=$(this).val();
				$("#id_money_cash").val(mon);
			});
			$("#quin").bind("input", function() {
				var mon=getMoney();
				_TPV.cash.d_500=$(this).val();
				$("#id_money_cash").val(mon);
			});
			$("#dosc").bind("input", function() {
				var mon=getMoney();
				_TPV.cash.d_200=$(this).val();
				$("#id_money_cash").val(mon);
			});
			$("#cien").bind("input", function() {
				var mon=getMoney();
				_TPV.cash.d_100=$(this).val();
				$("#id_money_cash").val(mon);
			});
			$("#cinc").bind("input", function() {
				var mon=getMoney();
				_TPV.cash.d_50=$(this).val();
				$("#id_money_cash").val(mon);
			});
			$("#vein").bind("input", function() {
				var mon=getMoney();
				_TPV.cash.d_20=$(this).val();
				$("#id_money_cash").val(mon);
			});
			$("#diez").bind("input", function() {
				var mon=getMoney();
				_TPV.cash.d_10=$(this).val();
				$("#id_money_cash").val(mon);
			});
			$("#cinco").bind("input", function() {
				var mon=getMoney();
				_TPV.cash.d_5=$(this).val();
				$("#id_money_cash").val(mon);
			});
			$("#dos").bind("input", function() {
				var mon=getMoney();
				_TPV.cash.d_2=$(this).val();
				$("#id_money_cash").val(mon);
			});
			$("#uno").bind("input", function() {
				var mon=getMoney();
				_TPV.cash.d_1=$(this).val();
				$("#id_money_cash").val(mon);
			});
			$("#centavos").bind("input", function() {
				var mon=getMoney();
				_TPV.cash.d_05=$(this).val();
				$("#id_money_cash").val(mon);
			});
		});
		$('#btnTotalNote').click(function(){
			_TPV.showNotes();
		});
		$('#btnChangeCustomer').click(function(){
			_TPV.changeCustomer();
		});
		$('#btnChangePlace').click(function(){
			_TPV.searchByPlace();
		});
		$('.close_types').click(function(){
			$('.close_types').removeClass('btnon');
			$(this).addClass('btnon');
			_TPV.cash.type = $(this).find('a:first').attr('id').substring(9);
		});
		$('.print_close_types').click(function(){
			$('.print_close_types').removeClass('btnon');
			$(this).addClass('btnon');
			_TPV.cash.printer = $(this).find('a:first').attr('id').substring(14);
		});
		$('.mail_close_types').click(function(){
			$('.mail_close_types').removeClass('btnon');
			$(this).addClass('btnon');
			_TPV.cash.mail = $(this).find('a:first').attr('id').substring(14);
		});
		$('.type_discount').click(function(){
			$('.type_discount').removeClass('btnon');
			$(this).addClass('btnon');
			if($(this).find('a:first').attr('id')=='btnTypeDiscount0')
			{
				$('#typeDiscount0').show();
				$('#typeDiscount1').hide();
				$('#typeDiscount1').val(0);
			}
			else
			{
				$('#typeDiscount1').show();
				$('#typeDiscount0').hide();
				$('#typeDiscount0').val(0);
			}
		});
		
		$('#id_btn_employee').click(function(){
			
			
			$('#idEmployee').dialog({width:400});
			$('#idEmployee a').unbind('click');
			
			$('#idEmployee a').click(function(){
								
				var login = $(this).attr('login');
				var userid = $(this).attr('id').substring(12);
				var username = $(this).html();
				var photo = $(this).attr('photo');
				
				$('#idEmpPass').dialog({ modal: true });
				$('#idEmpPass').dialog({width:400});
				
				$('#id_btn_empPass').unbind('click');
				$('#id_btn_empPass').click(function(){
					var pass = new Object();	
					pass.pass = $('#password').val();
					pass.login = login;
					 
					var result = ajaxDataSend('checkPassword',pass);
					
					if(result > 0){
						_TPV.employeeId = userid;
						$('#id_user_name').html(username);
						$('#id_image').attr("src",photo);
					}
					$('#password').val('');
					$('#idEmpPass').dialog('close');
					$('#idEmployee').dialog('close');
				
				});
			});
		});
		
		$('#id_btn_opendrawer').click(function(){
			ajaxDataSend('addPrint',"D");
		});
		$('#btnReloadTicket').click(function(){
			if(_TPV.ticket.id > 0)
			{
				if(_TPV.ticket.state==0 || _TPV.ticket.state==2)
				{
					_TPV.getTicket(_TPV.ticket.id,true);
				}
				else
				{
					_TPV.getTicket(_TPV.ticket.id,false);
				}
			}
			else
			{
				$('#btnReloadTickett').hide();
			}
		});
		
		$('#btnTicketAskTransfer').click(function(){
			var rc_prods	= _TPV.ticket.rc_getCheckedProducts();
			var rc_html		= '';
			var rc_ajxpr	= [];
			if (rc_prods.length <= 0)
			{
				alert('Por favor, seleccione al menos un producto a solicitar.');
			}
			else
			{
				rc_prods.forEach(function(e,i)
				{
					var rc_cant = $('#ticketLine'+e+' td:nth-child(8)').text();
					var rc_entr = $('#ticketLine'+e+' td:nth-child(9)').text();
					rc_ajxpr.push({'prod_id':e,'qty':rc_cant,'ent':rc_entr});
					
				});
				$('#rc_confirm_warehouse_transfer_button').unbind();
				$('#rc_confirm_warehouse_transfer_button').click(function(){_TPV.ticket.rc_askTransfer(rc_ajxpr);return false;});
				//$('#').show();
				$('#rc_confirm_warehouse_transfer').dialog({ modal: true });
				$('#rc_confirm_warehouse_transfer').dialog({width:440});
			}
		});
		
		$('#btnTicketAskAbroad').click(function(){
			var rwCt = 0;
			var trs  = {};
			$('#rc_confirm_ask_abroad_tbody').html('');
			$('#listado_productos_ticket tr').each(function(i,e){
				var cant = parseFloat($(e).find('.cant').text());
				var entr = parseFloat($(e).find('.qty_ent').text());
				var prDs = $(e).find('.description').text();
				var prId = $(e).find('.idCol').text();
				var tr   = '';
				if (isNaN(cant))
				{
					cant = 0;
				}
				if (isNaN(entr))
				{
					entr = 0;
				}
				if ((cant-entr)>0)
				{
					$('#rc_confirm_ask_abroad_tbody').append(
							'<tr>'+
								'<td style="color:#ddd;">'+
								prDs+
								'</td>'+
								'<td>'+
									'<input '+
										'type="number" '+
										'value="'+(cant-entr)+'" '+
										'min="0"'+
										'max="'+(cant-entr)+'" '+
										'name="rc_confirm_ask_abroad_input_'+prId+'" '+
										'id="rc_confirm_ask_abroad_input_'+prId+'" '+
										'style="width:60px;" '+
									'/>'+
								'</td>'+
							'</tr>'
					);
					rwCt++;
				}
			});
			if (rwCt>0)
			{
				$('#rc_confirm_ask_abroad_button').unbind();
				$('#rc_confirm_ask_abroad_button').click(function(){
					$('#rc_confirm_ask_abroad_tbody input').each(function(i,e){
						trs[$(e).attr('name').replace('rc_confirm_ask_abroad_input_','')] = $(e).val(); 
					});
					$('#rc_confirm_ask_abroad').dialog('close');
					var result = ajaxDataSend('createDeliveryTicket'
						,{
							'lines':trs,
							'ticket_id':_TPV.ticket.id,
							'client_id':_TPV.ticket.customerId,
							'ticket_ref':$('#btnTicketRef').text(),
							
							
						}
					);
					if (result > 0)
					{
						if(_TPV.defaultConfig['module']['print']>0 ){
							if(_TPV.ticket.mode==0){
								_TPV.printing('ticket',result);
							}
							else{
								_TPV.printing('facture',result);
							}	
						}
						$('#btnReloadTicket').click();
					}
					return false;
				});
				$('#rc_confirm_ask_abroad').dialog({width:540});
				$('#rc_confirm_ask_abroad').dialog({ modal: true });
			}
			else
			{
				alert('No quedan productos por entregar');
			}
		});
		
	},
	checkStock: function(){
		cant = $('#line_quantity').val();
		/*if(_TPV.products[this.activeIdProduct]["stock"] != "all"){
			if(parseFloat(cant) > parseFloat(_TPV.products[_TPV.activeIdProduct]["stock"])){
				$('#line_quantity').val(_TPV.products[_TPV.activeIdProduct]["stock"]);
			}	
		}*/
		
	},
	pointsClient: function(){
		_TPV.ticket.points = $('#points_client_id').val();
		if(parseFloat(_TPV.ticket.points) > parseFloat(_TPV.points))
		{
			_TPV.ticket.points = _TPV.points;
		}
		if(parseFloat(_TPV.ticket.points) * parseFloat(_TPV.defaultConfig['module']['points']) > parseFloat(_TPV.ticket.total))
		{
			_TPV.ticket.points = parseFloat(_TPV.ticket.total) / parseFloat(_TPV.defaultConfig['module']['points']);
		}
		$('#points_client_id').val(_TPV.ticket.points);
		discount = _TPV.ticket.points * _TPV.defaultConfig['module']['points'];
		_TPV.ticket.total_with_points = _TPV.ticket.total-discount;
		
		$('.payment_total').html(displayPrice(_TPV.ticket.total_with_points));
		if(_TPV.ticket.points > 0)
		{
			_TPV.ticket.difpayment = _TPV.ticket.total_with_points-_TPV.ticket.customerpay;
		}
		else
		{
			_TPV.ticket.difpayment = _TPV.ticket.total-_TPV.ticket.customerpay;
		}
		if(_TPV.ticket.difpayment > 0)
			$('.payment_return').addClass('negat');
		else
			$('.payment_return').removeClass('negat');
		$('.payment_return').html(displayPrice(_TPV.ticket.difpayment));
		_TPV.ticketState.customerpay = _TPV.ticket.total_with_points;
	},
	payClient: function(){
//		if($('#pay_client_0').val() != "")
//			var mode1 = $('#pay_client_0').val();
//		else
//			var mode1 = 0;



		if($('#pay_client_0').val() != "")
			var mode1 = $('#pay_client_0').val();
		else
			var mode1 = 0;
		if($('#pay_client_1').val() != "")
			var mode2 = $('#pay_client_1').val();
		else
			var mode2 = 0;
		if($('#pay_client_2').val() != "")
			var mode3 = $('#pay_client_2').val();
		else
			var mode3 = 0;
		if($('#pay_client_3').val() != "")
			var mode4 = $('#pay_client_3').val();
		else
			var mode4 = 0;

		// Tickets de regalo
		if($('#pay_client_4').val() != "")
			var mode99 = $('#pay_client_4').val();
		else
			var mode99 = 0;
			
		if(typeof mode2 == 'undefined') mode2 = 0;
		if(typeof mode3 == 'undefined') mode3 = 0;
		if(typeof mode4 == 'undefined') mode4 = 0;
		if(typeof mode99 == 'undefined') mode99 = 0;
				
		_TPV.ticket.customerpay = parseFloat(mode1) + parseFloat(mode2) + parseFloat(mode3)+ parseFloat(mode4)+ parseFloat(mode99);
		console.log(_TPV.ticket.customerpay);
		_TPV.ticket.customerpay1 = parseFloat(mode1);
		_TPV.ticket.customerpay2 = parseFloat(mode2);
		_TPV.ticket.customerpay3 = parseFloat(mode3);
		_TPV.ticket.customerpay4 = parseFloat(mode4);
		_TPV.ticket.customerpay5 = parseFloat(mode99);
		if(_TPV.ticket.points > 0)
		{
			_TPV.ticket.difpayment = _TPV.ticket.total_with_points-_TPV.ticket.customerpay;
		}
		else if(_TPV.ticket.state != "2")
		{
			_TPV.ticket.difpayment = _TPV.ticket.total-_TPV.ticket.customerpay;
		}
		else {
			_TPV.ticket.difpayment = _TPV.ticket.total-(_TPV.ticket.customerpay+_TPV.ticket.auxPaymentcus);
		}
		console.log('Total '+_TPV.ticket.total);
		console.log('Pagos '+_TPV.ticket.customerpay);
		console.log('Aux '+_TPV.ticket.auxPaymentcus);
		if(_TPV.ticket.difpayment > 0)
			$('.payment_return').addClass('negat');
		else
			$('.payment_return').removeClass('negat');
		$('.payment_return').html(displayPrice(_TPV.ticket.difpayment));
	},
	
	payClientRet: function(){
		if($('#pay_client_ret_0').val() != "")
			var mode1 = $('#pay_client_ret_0').val();
		else
			var mode1 = 0;
		if($('#pay_client_ret_1').val() != "")
			var mode2 = $('#pay_client_ret_1').val();
		else
			var mode2 = 0;
		if($('#pay_client_ret_2').val() != "")
			var mode3 = $('#pay_client_ret_2').val();
		else
			var mode3 = 0;
		if($('#pay_client_ret_3').val() != "")
			var mode4 = $('#pay_client_ret_3').val();
		else
			var mode4 = 0;
		if(typeof mode2 == 'undefined') mode2 = 0;
		if(typeof mode3 == 'undefined') mode3 = 0;
		if(typeof mode4 == 'undefined') mode4 = 0;
				
		_TPV.ticket.customerpay = parseFloat(mode1) + parseFloat(mode2) + parseFloat(mode3);
		if(_TPV.ticket.customerpay > Math.min(_TPV.ticket.total, _TPV.ticket.ret_points) && _TPV.ticket.type!=1){
			$('#pay_client_ret_0').val(0);
			$('#pay_client_ret_1').val(0);
			$('#pay_client_ret_2').val(0);
			$('#pay_client_ret_3').val(0);
			_TPV.ticket.customerpay = 0;
			_TPV.ticket.customerpay1 = 0;
			_TPV.ticket.customerpay2 = 0;
			_TPV.ticket.customerpay3 = 0;
			_TPV.ticket.customerpay4 = 0;
			_TPV.ticket.customerpay5 = 0;
		}
		
		_TPV.ticket.customerpay1 = parseFloat(mode1);
		_TPV.ticket.customerpay2 = parseFloat(mode2);
		_TPV.ticket.customerpay3 = parseFloat(mode3);
		_TPV.ticket.customerpay4 = parseFloat(mode4);
		_TPV.ticket.customerpay5 = 0.00 ; //parseFloat(mode99);
		if(_TPV.ticket.type!=1)
			_TPV.ticket.difpayment = Math.min(_TPV.ticket.total,  _TPV.ticket.ret_points) -_TPV.ticket.customerpay;
		else {
			if(_TPV.ticket.ret_points > 0)
				_TPV.ticket.difpayment = Math.min(_TPV.ticket.total, _TPV.ticket.ret_points) - _TPV.ticket.customerpay;
			else
				_TPV.ticket.difpayment = _TPV.ticket.total - _TPV.ticket.customerpay;
		}

		if(_TPV.ticket.difpayment > 0)
			$('.payment_return_ret').addClass('negat');
		else
			$('.payment_return_ret').removeClass('negat');
		$('.payment_return_ret').html(displayPrice(_TPV.ticket.difpayment));
	},
	
	getTicket: function(idTicket,edit)
	{
		_TPV.ticket.rc_products = {};
		_TPV.rc_products = {};
		var rc_ref = '';
		//$('#btnTicketSendFront').hide();$('#btnTicketSendFront').css('background-color','inherit');
		$('#btnTicketApartado').hide();$('#btnTicketApartado').css('background-color','inherit');
  		$('#btnTicketHomeDelivery').hide();
		$('#btnTicketAskTransfer').hide();
		$('#payType').hide();
		$('#payTypeRet').hide();
		$('#info_product').hide();
		

		
		$('#ls_tpv_checkall').attr("checked", 0);
		ls_tpv_switch_all_checkboxes();
		if(edit)
		{
			$('#idTotalNote').dialog( "close" );
			_TPV.ticketState=0;
			$('#btnReturnTicket').hide();
			$('#btnTicketRef').show();
			$('#btnSaveTicket').show();
			$('#btnFreight').show();
			$('#btnAddDiscount').show();
			$('#btnOkTicket').show();
			$('#btnTicketNote').show();
			$('#btnReloadTickett').show();

		}
		else                 
		{
			$('#btnTicketRef').show();
			$('#btnSaveTicket').hide();
			$('#btnFreight').hide();
			$('#btnAddDiscount').hide();
			$('#btnOkTicket').hide();
			$('#btnTicketNote').hide();
			_TPV.ticketState=1;
			$('#btnReloadTickett').show();
			
			//$('#btnTicketHomeDelivery').show();			
			
		}
		$('#btnTicketPrintFront').show();
		$('#btnTicketPrintFront').unbind('click');
		$('#btnTicketPrintFront').click(function(){
			_TPV.printing('ticket',idTicket);
		});
		if(typeof idTicket!='undefined')
		{
			var result = ajaxDataSend('getTicket',idTicket);
			$.each(result, function(id, item) {
        	    _TPV.ticket.init();

				//VARABLE PARA VER SI VIENE O NO DE PROPAL (0->NO...1->SI)
				_TPV.isPropal = item['isPropal'];
				
        	    _TPV.ticket.id = item['id'];
        	    $('#btnTicketRef').html(item['ref']);
        	    rc_ref = item['ref'];
        	    $('#infoProyectCustomer_').html(item['proyect_name']);
        	    _TPV.ticket.payment_type = item['payment_type'];
        	    _TPV.ticket.type = item['type'];
	   			_TPV.ticket.rc_products = item['rc_products'];
				$('#Customer_remise').html(item['remise_percentCustom']+"%");
				$('#infoCustomer_rfc').html("RFC: " + item['siren']);

        	    
        	    if(typeof item['discount_percent']!='undefined')
        	    	_TPV.ticket.discount_percent = item['discount_percent'];
        	    else
        	    	_TPV.ticket.discount_percent = 0;
        	    if(typeof item['discount_qty']!='undefined')
               	    _TPV.ticket.discount_qty =item['discount_qty'];
        	    else
        	    	_TPV.ticket.discount_qty =0;
        	    _TPV.ticket.customerpay = item['customerpay'];
        	    _TPV.ticket.auxPaymentcus = parseFloat(item['customerpay']);
        	    _TPV.ticket.difpayment = item['difpayment'];
				_TPV.ticket.state = item['state'];
        	    _TPV.ticket.customerId = item['customerId'];//AQUI
		    	$('#infoCustomer').html(item['customerName']);
		    	$('#infoCustomer_').html(item['customerName']);
		    	$('#limite_c').html('$'+PriceCommas(item['lim_cred']));
				console.log(item['infoCustomer_ref']);
				$("#infoCustomer_ref").val(item['infoCustomer_ref']);

			if(item['dis_cred'] >= 0) {
				$('#disponible_c').css("color", "rgb(34, 200, 34)");
				_TPV.ticket.NoCredit = 0;
			}
			else {
				$('#disponible_c').css("color", "#f44");
				_TPV.ticket.NoCredit = 1;
			}


		    	$('#disponible_c').html('$'+PriceCommas(item['dis_cred']));





		    	if(_TPV.ticket.discount_percent == null)
					$('#Customer_remise').html("0%");
		    	else
					$('#Customer_remise').html(_TPV.ticket.discount_percent+"%");
		    	_TPV.points = item['points'];
		    	_TPV.coupon = item['coupon'];
        	    //_TPV.ticket.total = item['total_ttc'];
        	    _TPV.ticket.id_place = item['id_place'];
        	    _TPV.ticket.note = item['note'];
				_TPV.ticket.proyectId = item['projetId'];
				if(parseFloat(_TPV.ticket.difpayment) > 0) {
					$("#totalRestToPay").html(displayPrice(parseFloat(_TPV.ticket.difpayment)));
					$("#containerRestToPay").css("display", "block");
				}
				else{
					$("#containerRestToPay").css("display", "none");
				}
        	    if(!edit)
        	    {
        	    	_TPV.ticket.idsource = idTicket;
        	    	_TPV.ticket.oldproducts = item['lines'];
        	    	_TPV.ticket.ret_points = item['ret_points'];
					_TPV.ticket.total = item['total_ttc'];
        	    }
        	    
        	    $('#tablaTicket > tbody tr').remove();
        	    var total_dis = 0;
        	    
        	    
        	    $.each(item['lines'], function(idline, line) {
       	    		if (line['ls_warehouse_status'] >= '1' && line['ls_warehouse_status'] != '10')
       	    		{
       	    			$('#btnTicketSendFront').css('background-color','green');
       	    		}
			    	if (this.ls_warehouse_status == '7')
			    	{
	   					$('#btnTicketApartado').css('background-color','green');
				   	}
        	    	
        	    	if(!edit)
        	    	{
	        	    	var totalLine = 0;
	        	    	var discount = 1;
	        	    	if(line['discount']!=0)
	        	    		discount = 1-line['discount']/100;
	        	    	//line['price_ttx']  = line['price_ttx']*(1+discount);
	        	    	total_dis= total_dis + parseFloat(line['remise']) * line['cant'];// * ((parseFloat(line['tva_tx']) + parseFloat(line['localtax1_tx']) + parseFloat(line['localtax2_tx']))/100+1)
	        	    	var chkd = '';
	        	    	if (line['ls_warehouse_status'] >= '1')
	        	    	{
	        	    		//chkd = ' checked="checked"'
	        	    	}

						var estado_v = '<td>&nbsp;</td>';
						if (parseInt(this.ls_warehouse_status) in rc_stLbl)
						{
							estado_v= '<td style="background-color:'+rc_stBak[parseInt(this.ls_warehouse_status)]+
										';color:'+rc_stClr[parseInt(this.ls_warehouse_status)]+'">'
										;
							if (parseInt(this.ls_warehouse_status) == 3)
							{
								estado_v += '<span title="Código de Inventario: '+this.ls_stock_mv_code+'">'+
											rc_stLbl[parseInt(this.ls_warehouse_status)]+
											'</span>'
											;
							}
							else
							{
								estado_v += rc_stLbl[parseInt(this.ls_warehouse_status)];
							}
							estado_v += '</td>';
						}

						var chkbx = '<input type="checkbox" class="ls_tpv_line_checkbox" onclick="ls_tpv_switch_line_checkbox();" id="ls_tpv_line_chkbox_'+this.idProduct+'"'+chkd+'> ';
						var tr = 
						'<tr id="ticketLine'+line['idProduct']+'" onclick="_TPV.ticket.showTicketOptions('+line['idProduct']+')">'+
						'<td class="idCol" >'+line['idProduct']+'</td>'+
						'<td style="text-align: center;">'+line['ref']+'</td>'+
						'<td style="text-align: center;">'+line['stock']+'</td>'+
						'<td class="description">'+chkbx+line['label']+'</td>'+
						estado_v+
						'<td class="price">'+displayPrice(line['total_ttc']/line['cant']/((100-parseFloat(line['discount']))/100))+'</td>'+
						'<td class="discount">'+line['discount']+'%</td>'+
						'<td class="price_d">'+displayPrice(line['total_ttc']/line['cant'])+'</td>'+
						'<td class="cant">'+line['cant']+'</td>'+
						'<td class="qty_ent">'+line['qty_ent']+'</td>'+
						'<td class="total">'+displayPrice(line['total_ttc'])+'</td>';
						tr = tr + '</tr>';
												
						$('#tablaTicket > tbody:last').prepend(tr);
        	    	}
        	    	else
        	    	{
						if(line['discount'] == 0){
        	    			line['discount'] = line['discount']-_TPV.ticket.discount_percent;
						}
        	    		//_TPV.ticket.addManualProduct(line['idProduct'],line['cant'],line['discount'],line['total_ttc']);
        	    		console.log("discount after:"+line['discount']);_TPV.ticket.addManualProduct(line['idProduct'],line['cant'],line['discount'],line['price'],line['note'],line["ls_warehouse_status"],line['qty_ent'],line['ls_stock_mv_code']);
						if(line['discount'] >= 0)
								total_dis += parseFloat(line['remise']) * line['cant'];
						else {
							total_dis = ((_TPV.ticket.discount_percent / 100) * _TPV.ticket.total) / (1 - (_TPV.ticket.discount_percent / 100));
						}
        	    	}
        	    });
				//Recuperar descuentos
				console.log(_TPV.ticket.lines);
				console.log("Descuentos");
				_TPV.ticket.lines.forEach(function(line){console.log(line.discount)
					if(line.discount == 0){
						line.setDiscount(0);
						line.setDiscount(item['discount_percent']);
						line.showTotal();
					}
				});
        	    if(edit)
        	    {
        	    	if(parseFloat(item['total_ttc']) < parseFloat(item['difpayment']))
        	    		_TPV.ticket.total = item['difpayment'];
        	    	else
        	    		_TPV.ticket.total = item['total_ttc'];
        	    	$('#totalDiscount').html(displayPrice(total_dis));
        	    	$('#totalTicket').html(displayPrice(_TPV.ticket.total));
        	    	$('#totalTicketinv').html(displayPrice(_TPV.ticket.total));
        	    	//_TPV.ticket.calculeDiscountTotal(total);
        	    }
        	    else{
					$('#totalDiscount').html(displayPrice(total_dis));
					$('#totalTicket').html(displayPrice(_TPV.ticket.total));
					$('#totalWdiscount').html(displayPrice(parseFloat(_TPV.ticket.total) + total_dis));
					console.log(_TPV.ticket);
				}
        	    if(item['id_place']){
        	    	$('#totalPlace').html(_TPV.places[item['id_place']]);
        	    }
        	    else
        	    	{
        	    	$('#totalPlace').html('');
        	    	}
        	    showTicketContent();
        	    if(item['type']!='0')
        	    {
					$('#btnTicketSendFront').hide();$('#btnTicketSendFront').css('background-color','inherit');
      				$('#btnTicketApartado').hide();$('#btnTicketApartado').css('background-color','inherit');
		   	  		$('#btnTicketHomeDelivery').hide();
					$('#btnTicketAskTransfer').hide();
					$('#btnTicketAskAbroad').hide();
        	    }
        	});
        	
			if((	_TPV.ticket.state==1 
				//|| 	(	_TPV.ticket.customerId != "8" 
				//	 && parseInt(_TPV.ticket.state) == 2)
				) && _TPV.ticket.type==0)
			{
				$('#btnTicketAskTransfer').show();
	   		  	$('#btnTicketHomeDelivery').show();
		      	$('#btnTicketApartado').show();
				$('#btnTicketSendFront').hide();
				$('#btnTicketAskAbroad').show();
			}
        	if((_TPV.ticket.state == 2 || parseInt(_TPV.ticket.state) == 2)&& parseInt(_TPV.ticket.type) == 0)
			{
				//Mostramos el cuadro para abandonar la venta
				$("#CancelSquare").show();
				//Evento click para el boton
				$('#id_btn_cancel_ticket').unbind('click');
				$('#id_btn_cancel_ticket').click(function(){
					if (confirm('¿Desea cancelar la venta?'))
					{
					_TPV.ticket.AbandonarTicket();
					}
				});
			}
        	else
				$("#CancelSquare").hide();
				
		}
        	    if (_TPV.ticket.rc_isOrder(rc_ref))
        	    {
        	    	$('#btnReturnTicket').hide();
        	    	$('#btnTicketApartado').hide();
        	    	$('#btnTicketAskTransfer').hide();
        	    	$('#btnTicketAskAbroad').hide();
        	    	$('#btnTicketHomeDelivery').hide();
        	    	$('#btnOkTicket').show();
        	    }
			if(_TPV.ticket.type == 0 && _TPV.ticket.state == 1)
			{
				$('#btnReturnTicket').show();
			}
			else
			{
				$('#btnReturnTicket').hide();
			}

		ls_tpv_switch_line_checkbox();
	},


	getFacture: function(idTicket,edit)
	{
		if(edit)
		{
			_TPV.ticketState=0;
			$('#btnReturnTicket').hide();
			$('#btnTicketRef').hide();$('#btnTicketRef').html('');
			$('#btnSaveTicket').show();
			$('#btnFreight').show();
			$('#btnAddDiscount').show();
			$('#btnOkTicket').show();
			$('#btnTicketNote').show();
//      		$('#btnTicketApartado').hide();
//      		$('#btnTicketHomeDelivery').hide();$('#btnTicketHomeDelivery').css('background-color','inherit');
		}
		else
		{
			if(_TPV.ticketState!=1 && !_TPV.ticket.rc_isOrder())
			{
				$('#btnReturnTicket').show();
			}
			$('#btnTicketRef').show();
			$('#btnSaveTicket').hide();
			$('#btnFreight').hide();
			$('#btnAddDiscount').hide();
			$('#btnOkTicket').hide();
			$('#btnTicketNote').hide();
			_TPV.ticketState=1;
			
		}
		
		if(typeof idTicket!='undefined')
		{
			var result = ajaxDataSend('getFacture',idTicket);
			$.each(result, function(id, item) {
        	    _TPV.ticket.init();
        	    _TPV.ticket.id = item['id'];
        	    $('#btnTicketRef').html(item['ref']);
        	    _TPV.ticket.payment_type = item['payment_type'];
        	    _TPV.ticket.type = item['type'];
        	    if(typeof item['discount_percent']!='undefined')
        	    	_TPV.ticket.discount_percent = item['discount_percent'];
        	    else
        	    	_TPV.ticket.discount_percent = 0;
        	    if(typeof item['discount_qty']!='undefined')
               	    _TPV.ticket.discount_qty =item['discount_qty'];
        	    else
        	    	_TPV.ticket.discount_qty =0;
        	   _TPV.ticket.customerId = item['customerId'];
        	   _TPV.ticket.mode = 1;//Para diferencia de los tickets a la hora de hacer devoluciones
		   		$('#infoCustomer').html(item['customerName']);
		   		$('#infoCustomer_').html(item['customerName']);
		   	    _TPV.ticket.state = item['state'];
        	    if(!edit)
        	    {
        	    	_TPV.ticket.idsource = idTicket;
        	    	_TPV.ticket.oldproducts = item['lines'];
        	    	_TPV.ticket.ret_points = item['ret_points'];
        	    }
        	    $('#tablaTicket > tbody tr').remove();
        	    var total = 0;
        	    $.each(item['lines'], function(idline, line) {
        	    	if(!edit)
        	    	{
	        	    	var totalLine = 0;
	        	    	var discount = 1;
	        	    	_TPV.ticket.discount_percent = 0;
	        	    	//line['discount']=line['discount']-_TPV.ticket.discount_percent;
	        	    	if(line['discount']!=0)
	        	    		discount = 1-line['discount']/100;
	        	    	totalLine = parseFloat(line['total_ttc']);
	        	    	total += totalLine;
	        	    	totalLine = displayPrice(totalLine);
	        	    	var tr = '<tr id="ticketLine'+line['idProduct']+'"><td class="idCol" >'+line['idProduct']+'</td><td class="description">'+line['label']+'</td><td class="discount">'+line['discount']+'%</td><td class="price">'+displayPrice((line['total_ttc']/line['cant'])/discount)+'</td><td class="cant">'+line['cant']+'</td><td class="qty_ent">'+line['qty_ent']+'</td><td class="total">'+totalLine+'</td>';
	        	    	tr = tr + '</tr>';
	        	    		        	    	
	        	    	$('#tablaTicket > tbody:last').prepend(tr);
        	    	}
        	    	else
        	    	{
        	    		_TPV.ticket.addManualProduct(line['idProduct'],line['cant'],line['discount'])
        	    	}
        	    });
        	    if(!edit)
        	    {
        	    	_TPV.ticket.total = item['total_ttc'];
        	    	_TPV.ticket.calculeDiscountTotal(total);
        	    	//$('#totalTicket').html(displayPrice(total));
        	    }
        	    showTicketContent();
        	});
		}	
	},
	
	countByStock: function(){
		
		var result = ajaxDataSend('countProduct',_TPV.warehouseId);
				
		$('#stockNoSell').html(result["no_sell"]);
	
		$('#stockSell').html(result["sell"]);
	
		$('#stockWith').html(result["stock"]);
	
		$('#stockWithout').html(result["no_stock"]);
	
		$('#stockBest').html(result["best_sell"]);
	
		$('#stockWorst').html(result["worst_sell"]);
			
	},
	sortTable: function(n,type) {
		var table, rows, switching=true, i, x, y, shouldSwitch, dir, switchcount = 0;

		table = document.getElementById("storeTable");
		switching = true;
		//Set the sorting direction to ascending:
		dir = "asc";

		/*Make a loop that will continue until no switching has been done:*/
		while (switching) {
			//start by saying: no switching is done:
			switching = false;
			rows = table.rows;
			/*Loop through all table rows (except the first, which contains table headers):*/
			for (i = 1; i < (rows.length - 1); i++) {
				//start by saying there should be no switching:
				shouldSwitch = false;
				/*Get the two elements you want to compare, one from current row and one from the next:*/
				x = rows[i].getElementsByTagName("TD")[n];
				y = rows[i + 1].getElementsByTagName("TD")[n];
				/*check if the two rows should switch place, based on the direction, asc or desc:*/
				if (dir == "asc") {
					if(n == 3)
					{
						$("#downM").css('display','none');
						$("#upM").css('display','block');
					}else {
						$("#downG").css('display','none');
						$("#upG").css('display','block');
					}
					if ((type=="str" && x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) || (type=="int" && parseFloat(x.innerHTML) > parseFloat(y.innerHTML))) {
						//if so, mark as a switch and break the loop:
						shouldSwitch= true;
						break;
					}
				} else if (dir == "desc") {
					if(n == 3)
					{
						$("#upM").css('display','none');
						$("#downM").css('display','block');
					}else {
						$("#upG").css('display','none');
						$("#downG").css('display','block');
					}
					if ((type=="str" && x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) || (type=="int" && parseFloat(x.innerHTML) < parseFloat(y.innerHTML))) {
						//if so, mark as a switch and break the loop:
						shouldSwitch = true;
						break;
					}
				}
			}
			if (shouldSwitch) {
				/*If a switch has been marked, make the switch and mark that a switch has been done:*/
				rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
				switching = true;
				//Each time a switch is done, increase this count by 1:
				switchcount ++;
			} else {
				/*If no switching has been done AND the direction is "asc", set the direction to "desc" and run the while loop again.*/
				if (switchcount == 0 && dir == "asc") {
					dir = "desc";
					switching = true;
				}
			}
		}
	},
	searchByStock: function(mode,warehouse){
		var filter = new Object();	
		filter.search = $('#id_stock_search').val();
		filter.mode = mode;
		filter.warehouse = warehouse;
		var result = ajaxDataSend('searchStocks',filter);
		var actionsHtml = '';
    	$("#storeTable tr.data").remove();
	    if(result != null){	
	    	$.each(result, function(id, item) {
	    		actionsHtml = '';
	    		if(item['warehouseId'] == _TPV.warehouseId)
	    		{
	    			if (item['flag'] == 1 || item['stock'] > 0)
	    				{
	    				actionsHtml = '<a class="accion addline" onclick="_TPV.ticket.addLine('+item['id']+');"></a>';
	    				}
	    		}
	    		var hide = "$('#info_product_st').toggle()";
	    		actionsHtml += '<a class="accion info" onclick="'+hide+'"></a><a class="action close" onclick="_TPV.ticket.hideStockOptions('+item['id']+'_'+item['warehouseId']+')"></a>';
				var backM='',backG='';
				if(parseInt(item['matriz']) < 0)
					backM='style="color:red;"';
				if(parseInt(item['gpe']) < 0)
					backG='style="color:red;"';
	    		if(item['id'] != null) {
					if(mode == -9)
						$('#storeTable').append('<tr id="stock' + item['id'] + '_' + item['warehouseId'] + '" onclick="_TPV.ticket.showStockOptions(' + item['id'] + ',' + item['warehouseId'] + ');_TPV.addInfoProductSt(' + item['id'] + ');" ondblclick="_TPV.ticket.addLine(' + item['id'] + ');_TPV.ticket.addCant('+item['id']+','+item['qty']+');" class="data"><td>' + item['id'] + '</td><td>' + item['ref'] + '</td><td>' + item['label'] + '</td><td '+backM+'>' + item['matriz'] + '</td><td '+backG+'>' + item['gpe'] + '</td><td>' + item['supplier'] + '</td><td class="colActions"  style="text-align:center">' + actionsHtml + '</tr>');
					else
						$('#storeTable').append('<tr id="stock' + item['id'] + '_' + item['warehouseId'] + '" onclick="_TPV.ticket.showStockOptions(' + item['id'] + ',' + item['warehouseId'] + ');_TPV.addInfoProductSt(' + item['id'] + ');" ondblclick="_TPV.ticket.addLine(' + item['id'] + ');" class="data"><td>' + item['id'] + '</td><td>' + item['ref'] + '</td><td>' + item['label'] + '</td><td '+backM+'>' + item['matriz'] + '</td><td '+backG+'>' + item['gpe'] + '</td><td>' + item['supplier'] + '</td><td class="colActions"  style="text-align:center">' + actionsHtml + '</tr>');
				}//$('#historyTable').append('<tr id="historyTicket'+item['id']+'" onclick="_TPV.ticket.showHistoryOptions('+item['id']+')" class="data"><td><a class="icontype state'+item['statut']+' type'+item['type']+'"></a>'+item['ticketnumber']+'</td><td>'+date+'</td><td>'+item['terminal']+'</td><td>'+item['seller']+'</td><td>'+item['client']+'</td><td style="text-align:right;">'+displayPrice(item['amount'])+'</td><td class="colActions"  style="text-align:center">'+actionsHtml+'</tr>');
	    	});	
    	}	
	},
	
	searchProduct: function(){
		var data = new Object;
    	data['search'] = $('#id_product_search').val();
    	data['warehouse'] = _TPV.warehouseId;
		data['ticketstate'] = _TPV.ticket.type;
		data['customer'] = _TPV.ticket.customerId;
    	var result = ajaxDataSend('searchProducts',data);
    	if(result != null) {
			$("#boton-prospecto").css("display","none");
			//$("#id_selectProduct option").remove();
			$("#table_selectProduct>tbody>tr").remove();
			$.each(result, function(id, item) {
				if(item['id'] != null) {
					/*$('#id_selectProduct>optgroup').append(
						$('<option></option>').val(item['id']).html(item['ref'] + ' > ' + item['label'] + ' > $' + displayPrice(item['price_ttc']) + ' > MATRIZ > ' + item['matriz'] + ' > GPE > ' + item['gpe'])
					);*/
					if(item['ref'].length >12)
						item['ref']=item['ref'].substring(0,12);
					if(item['label'].length >59)
						item['label']=item['label'].substring(0,59);
					if(item['matriz'] != null && item['matriz'].lenght >5)
						item['matriz']=item['matriz'].substring(item['matriz'].lenght-1,item['matriz'].length-6);
					if(item['gpe'] != null && item['gpe'].lenght >5)
						item['gpe']=item['gpe'].substring(item['gpe'].lenght-1,item['gpe'].length-6);
					$('#table_selectProduct').append('<tr onclick="_TPV.addInfoProduct('+item['id']+');" ondblclick="_TPV.ticket.addLine('+item['id']+');"><td style="width:20%;">' + item['ref'] + '</td><td style="width:50%;">' + item['label'] + '</td><td style="width:10%;">$' + displayPrice(item['price_ttc']) + '</td><td style="width:10%;">'+item['matriz']+'</td><td style="width:10%;">'+item['gpe']+'</td></tr>');
				}
			});
			if(_TPV.barcode==1)
			{
				$('#id_product_search').val('');
				$('#divSelectProducts').show();

			}
			else if(result.length==1)
			{
				_TPV.ticket.addLine(result[0]['id']);

				$('#divSelectProducts').hide();
				if(_TPV.defaultConfig['terminal']['barcode'] == 1){
					$('#id_product_search').focus();
				}
				$('#id_product_search').val('');

			}
			else {
				$('#divSelectProducts').show();
			}
		}else{
    		$("#boton-prospecto").css("display","block");
    		$("#producto_etiqueta").val(data['search']);
		}
    },
    
    searchCustomer: function(){
    	var result = ajaxDataSend('searchCustomer',$('#id_customer_search_').val());
    	$("#customerTable_ tr.data").remove();
    	var win = "$('#idChangeCustomer').dialog('close')";
		_TPV.temporalpro=result;
    	$.each(result, function(id, item) {
			var iterator = 0;
			var select_p='<select id="p_client'+item['id']+'">';
			select_p += '<option value="0" selected>&nbsp;</option>';
			$.each(item['proyectos'],function (row,proyecto) {
				if(proyecto['proyect'] == "&nbsp")
					select_p="&nbsp";
				else {
					select_p += '<option value="' + proyecto["proyect_id"] + '">' + proyecto["proyect"] + '</option>';
					iterator++;
				}
			});
			if(iterator > 0) {
				select_p += "</select>";
				$('#customerTable_').append('<tr class="data"><td class="itemId" style="display:none">' + item['id'] + '</td><td class="itemDni">' + item['profid1'] + '</td><td class="itemName">' + item['nom'] + '</td><td class="itemProyect">' + select_p + '</td><td class="action add"><a class="action addcustomer" onclick="_TPV.ticket.addTicketCustomer(' + item['id'] + ',\'' + item['profid1'] + '\',\'' + item['nom'] + '\',' + item['remise'] + ',' + item['coupon'] + ',' + item['points'] + ',$(\'#p_client' + item['id'] + '\').val(),$(\'#p_client' + item['id'] + ' option:selected\').text(),2,' + id + ');' + win + ';"></a></td></tr>');
			}
			else
			{
				$('#customerTable_').append('<tr class="data"><td class="itemId" style="display:none">' + item['id'] + '</td><td class="itemDni">' + item['profid1'] + '</td><td class="itemName">' + item['nom'] + '</td><td class="itemProyect">' + select_p + '</td><td class="action add"><a class="action addcustomer" onclick="_TPV.ticket.addTicketCustomer(' + item['id'] + ',\'' + item['profid1'] + '\',\'' + item['nom'] + '\',' + item['remise'] + ',' + item['coupon'] + ',' + item['points'] + ',0, \'&nbsp\',2,' + id + ');' + win + ';"></a></td></tr>');
			}
    	});
    },
	getAllCustomers: function(){
		var result = ajaxDataSend('getAllCustomers','');
		$("#customerTable_ tr.data").remove();
		var win = "$('#idChangeCustomer').dialog('close')";
		_TPV.temporalpro=result;
		$.each(result, function(id, item) {
			var iterator = 0;
			var select_p='<select id="p_client'+item['id']+'">';
			select_p += '<option value="0" selected>&nbsp;</option>';
			$.each(item['proyectos'],function (row,proyecto) {
				if(proyecto['proyect'] == "&nbsp")
					select_p="&nbsp";
				else {
					select_p += '<option value="' + proyecto["proyect_id"] + '">' + proyecto["proyect"] + '</option>';
					iterator++;
				}
			});
			if(iterator > 0) {
				select_p += "</select>";
				$('#customerTable_').append('<tr class="data"><td class="itemId" style="display:none">' + item['id'] + '</td><td class="itemDni">' + item['profid1'] + '</td><td class="itemName">' + item['nom'] + '</td><td class="itemProyect">' + select_p + '</td><td class="action add"><a class="action addcustomer" onclick="_TPV.ticket.addTicketCustomer(' + item['id'] + ',\'' + item['profid1'] + '\',\'' + item['nom'] + '\',' + item['remise'] + ',' + item['coupon'] + ',' + item['points'] + ',$(\'#p_client' + item['id'] + '\').val(),$(\'#p_client' + item['id'] + ' option:selected\').text(),2,' + id + ');' + win + ';"></a></td></tr>');
			}
			else
			{
				$('#customerTable_').append('<tr class="data"><td class="itemId" style="display:none">' + item['id'] + '</td><td class="itemDni">' + item['profid1'] + '</td><td class="itemName">' + item['nom'] + '</td><td class="itemProyect">' + select_p + '</td><td class="action add"><a class="action addcustomer" onclick="_TPV.ticket.addTicketCustomer(' + item['id'] + ',\'' + item['profid1'] + '\',\'' + item['nom'] + '\',' + item['remise'] + ',' + item['coupon'] + ',' + item['points'] + ',0,\'&nbsp\',2,' + id + ');' + win + ';"></a></td></tr>');
			}
		});
	},
	searchByPlace: function(){
		var result = ajaxDataSend('getPlaces');
    	$("#placeTable_ div").remove();
    	
    	
    	$.each(result, function(id, item) {
    		
    		if(item['fk_ticket'] > 0)
    		{
    			_TPV.places[item['id']]= item['name'];
    			$('#placeTable_').append('<div class="placeDiv placeDivFree" onclick="_TPV.getTicket('+item['fk_ticket']+',true); ">'+item['name']+'</div>');
        	}
    		else
    		{
    			_TPV.places[item['id']]= item['name'];
    			$('#placeTable_').append('<div class="placeDiv" onclick="_TPV.ticket.newTicketPlace('+item['id']+'); ">'+item['name']+'</div>');
    		}
    		});
    	$('#idChangePlace').dialog({ modal: true });
    	$('#idChangePlace').dialog({width: 600});
    	
    	$('#idChangePlace').unbind('click');
		
		$('#idChangePlace').click(function(){
			
			$('#idChangePlace').dialog('close');
		
		});
    	    	
	},
	countByRef: function(){
		
		var result = ajaxDataSend('countHistory','');
				
		$('#histToday').html(result["today"]);
	
		$('#histYesterday').html(result["yesterday"]);
	
		$('#histThisWeek').html(result["thisweek"]);
	
		$('#histLastWeek').html(result["lastweek"]);
	
		$('#histTwoWeeks').html(result["twoweek"]);
	
		$('#histThreeWeeks').html(result["threeweek"]);
	
		$('#histThisMonth').html(result["thismonth"]);
	
		$('#histOneMonth').html(result["monthago"]);
	
		$('#histLastMonth').html(result["lastmonth"]);
			
	},
	
	countByRefFac: function(){
		
		var result = ajaxDataSend('countHistoryFac','');
				
		$('#histFacToday').html(result["today"]);
	
		$('#histFacYesterday').html(result["yesterday"]);
	
		$('#histFacThisWeek').html(result["thisweek"]);
	
		$('#histFacLastWeek').html(result["lastweek"]);
	
		$('#histFacTwoWeeks').html(result["twoweek"]);
	
		$('#histFacThreeWeeks').html(result["threeweek"]);
	
		$('#histFacThisMonth').html(result["thismonth"]);
	
		$('#histFacOneMonth').html(result["monthago"]);
	
		$('#histFacLastMonth').html(result["lastmonth"]);
			
	},

	searchByRef: function(stat){
		var filter = new Object();	
		filter.search = $('#id_ref_search').val();
		filter.stat = stat;
		/*filter.search=$("#type_search").val();
		var result = ajaxDataSend('getHistory',filter);
    	$("#historyTable tr.data").remove();*/
		if (document.getElementById("type_searchbyref").value == "terminal") {
			var result = ajaxDataSend('getHistoryByTerminal',filter);
		} else if (document.getElementById("type_searchbyref").value == "seller") {
			var result = ajaxDataSend('getHistoryByUser',filter);
		}else if (document.getElementById("type_searchbyref").value == "client") {
			var result = ajaxDataSend('getHistoryByClient',filter);
		} else {
			var result = ajaxDataSend('getHistory',filter);
		}
		$("#historyTable tr.data").remove();
    	$.each(result, function(id, item) {
    		var edit = false;
    		var delet = false;
    		var actionsHtml = '';
    		var strticket = "'ticket'";
    		if(item['statut']==0 || (item['statut']==2 && item['type'] != 1)){
        		edit = true;
        		delet = true;
    		}
    		strticketgift = "'giftticket'";
    		var date = '-';
    		if(item['date_close'].length>0 && item['date_close']!='')
    			date = item['date_close'];
    		else if(item['date_creation'].length>0 && item['date_creation']!='')
    			date = item['date_creation'];
    		var blocked = '';
    		var strticket = "'ticket'";
    		if(item['type']==1)
    			blocked = '_TPV.ticketState=1;';
    		
    		
    		actionsHtml += '<a class="action edit" onclick="'+blocked+'_TPV.getTicket('+item['id']+','+edit+');"></a>';
    		actionsHtml += '<a class="action view" onclick="dol_ticket('+item['id']+');"></a>';
    		if(delet){
    			actionsHtml += '<a class="action delete" onclick="_TPV.deletTicket('+item['id']+');"></a>';
    		}
    		if(_TPV.defaultConfig['module']['print']>0){
    			actionsHtml += '<a class="action print" onclick="_TPV.printing('+strticket+','+item['id']+');"></a>';
    		}
    		if(parseInt(item['type']) == 1)
				delet = false;
    		//if(_TPV.defaultConfig['module']['print']>0 && !delet && (parseInt(item['amount']) - parseInt(item['customer_pay'])) > 0 && parseInt(item['type']) == 1){
    		if(_TPV.defaultConfig['module']['print']>0 && !delet && parseInt(item['diffpayment']) > 0 && parseInt(item['type']) == 1){
    			actionsHtml += '<a class="action printgift" onclick="_TPV.printing('+strticketgift+','+item['id']+');"></a>';
    		}
    		if(_TPV.defaultConfig['module']['mail']>0){
    			actionsHtml += '<a class="action mail" onclick="_TPV.mailTicket('+item['id']+');"></a>';
    		}
			//## Boton para Facturar ##
			actionsHtml += '<a style="background-image: url(\'img/invoice.png\'); " onclick="_TPV.ticket.askInvoicingFromList('+item['id']+');"></a>';

			//Boton PDF
			if(parseInt(item['statut']) == 0){ //Checar si es Borrador
				actionsHtml += '<a style="background-image: url(\'img/pdf.png\'); " onclick="_TPV.pdfTicket('+item['id']+');"></a>';
			}

			//Boton de Clonar
			actionsHtml += '<a title="Clonar Ticket" style="background-image: url(\'img/copy.png\');background-repeat: no-repeat;" onclick="_TPV.cloneTikcet('+item['id']+');"></a>';


    		actionsHtml += '<a class="action close" onclick="_TPV.ticket.hideHistoryOptions('+item['id']+')"></a>';
    		var chkd='';
    		if(parseInt(item['total_surtir']) > 0)
    			chkd="checked";
			var chkbx = '<input type="checkbox" class="ls_ticket" '+chkd+'> ';
			var autoChange = '';
			if (parseInt(item['hasAutoChangesByStock']) >0 )
			{
				autoChange = ' <span class="icontype state6" title="Listo para entregar"></span>';
			}
			
    	    $('#historyTable').append('<tr id="historyTicket'+item['id']+'" onclick="_TPV.ticket.showHistoryOptions('+item['id']+')" class="data"><td style="width:35px;">'+autoChange+'</td><td><a class="icontype state'+item['statut']+' type'+item['type']+'">'+chkbx+'</a>'+item['ticketnumber']+'</td><td>'+date+'</td><td>'+item['terminal']+'</td><td>'+item['seller']+'</td><td>'+item['client']+'</td><td style="text-align:right;">'+displayPrice(item['amount'])+'</td><td class="colActions"  style="text-align:center">'+actionsHtml+'</tr>');
    	});
	},

	pdfTicket : function(idTicket)
	{
		var DTO = {'data': idTicket};
		var data = JSON.stringify(DTO);
		var result;
		$.ajax({
			type: "POST",
			url: './ajax_pos.php?action=filePDF',
			contentType: "application/json;charset=utf-8",
			dataType: "json", 
			async: false,
			processData:false,
			data: data,
			success: function(msg)
			{
				console.log(msg.url);
				var link = document.createElement("a");
				link.href = msg.url;
				link.target = '_blank';
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
				delete link;
			},
			error: function()
			{
				result = {'error':{'value':'','desc':''},'data':''}
			}
		});
	},

	cloneTikcet : function(idTicket)
	{
		if(confirm("Deseas clonar el ticket?")){
			var DTO = {'data': idTicket};
			var data = JSON.stringify(DTO);
			var result;
			$.ajax({
				type: "POST",
				url: './ajax_pos.php?action=cloneTicket',
				contentType: "application/json;charset=utf-8",
				dataType: "json",
				async: false,
				processData:false,
				data: data,
				success: function(msg)
				{
					console.log(msg);
					if(msg.result > 0){
						_TPV.searchByRef(-1);
						_TPV.countByRef();
					}
				},
				error: function()
				{
					result = {'error':{'value':'','desc':''},'data':''}
				}
			});
		}
	},
	
	searchByRefFac: function(stat){
		var filter = new Object();	
		filter.search = $('#id_ref_fac_search').val();
		filter.stat = stat;
		var result = ajaxDataSend('getHistoryFac',filter);
    	$("#historyFacTable tr.data").remove();
    	
    	
    	$.each(result, function(id, item) {
    		var edit = false;
    		var delet = false;
    		var actionsHtml = '';
    		var strticket = "'facture'";
    		if(item['statut']==0){
        		edit = true;
        		delet = true;
        		strticket = "'ticket'";
    		}
    		strticketgift = "'giftfacture'";
    		var date = '-';
    		if(item['date_close'].length>0 && item['date_close']!='')
    			date = item['date_close'];
    		else if(item['date_creation'].length>0 && item['date_creation']!='')
    			date = item['date_creation'];
    		var blocked = '';
    		if(item['type']==1)
    			blocked = '_TPV.ticketState=1;';
    		
    		if(delet)
    			actionsHtml += '<a class="action edit" onclick="'+blocked+'_TPV.getTicket('+item['id']+','+edit+');"></a>';
    		else
    			actionsHtml += '<a class="action edit" onclick="'+blocked+'_TPV.getFacture('+item['id']+','+edit+');"></a>';
    		
    		if(delet){
    			actionsHtml += '<a class="action delete" onclick="_TPV.deletTicket('+item['id']+');"></a>';
    		}
    		if(_TPV.defaultConfig['module']['print']>0){
    			actionsHtml += '<a class="action print" onclick="_TPV.printing('+strticket+','+item['id']+');"></a>';
    		}
    		if(_TPV.defaultConfig['module']['print']>0 && !delet ){
    			actionsHtml += '<a class="action printgift" onclick="_TPV.printing('+strticketgift+','+item['id']+');"></a>';
    		}
    		if(_TPV.defaultConfig['module']['mail']>0 && !delet){
    			actionsHtml += '<a class="action mail" onclick="_TPV.mailFacture('+item['id']+');"></a>';
    		}
    		else if (_TPV.defaultConfig['module']['mail']>0 && delet){
    			actionsHtml += '<a class="action mail" onclick="_TPV.mailTicket('+item['id']+');"></a>';
    		}
    		//## Boton para Facturar ##
			actionsHtml += '<a class="action invoice" onclick="_TPV.askInvoicing();"></a>';
    		actionsHtml += '<a class="action close" onclick="_TPV.ticket.hideHistoryFacOptions('+item['id']+')"></a>';
    	    $('#historyFacTable').append('<tr id="historyFacTicket'+item['id']+'" onclick="_TPV.ticket.showHistoryFacOptions('+item['id']+')" class="data"><td><a class="icontype state'+item['statut']+' type'+item['type']+'"></a>'+item['ticketnumber']+'</td><td>'+date+'</td><td>'+item['terminal']+'</td><td>'+item['seller']+'</td><td>'+item['client']+'</td><td style="text-align:right;">'+displayPrice(item['amount'])+'</td><td class="colActions"  style="text-align:center">'+actionsHtml+'</tr>');
    	});
	},
	
	showNotes: function()
	{
		var result = ajaxDataSend('getNotes',1);
		$("#noteTable tr.data").remove();
		var blocked=' ';
		var idtick = 0;
    	$.each(result, function(id, item) {
    		if(item['ticketid'] != idtick){
    			idtick = item['ticketid'];
    			$('#noteTable').append('<tr class="data"><td class="itemId" style="display:none">'+item['id']+'</td><td width=10px; id="noteCabe">'+item['ticketnumber']+'</td><td colspan=2 id="noteCabe">'+item['note']+'</td><td width=10px; id="noteCabe"><a class="action addNote" onclick="'+blocked+'_TPV.getTicket('+item['ticketid']+',true);" ></a></td></tr>');
    		}
    		else{
    			$('#noteTable').append('<tr class="data"><td class="itemId" style="display:none">'+item['id']+'</td><td colspan=2>'+item['description']+'</td><td colspan=2>'+item['note']+'</td></tr>');
    		}
    		});
    	$('#idTotalNote').dialog({ modal: true });
    	$('#idTotalNote').dialog({height:450,width: 600});
    },
    changeCustomer: function()
	{
    	$('#idChangeCustomer').dialog({ modal: true });
		$('#idChangeCustomer').dialog({height:450,width: 800});
    },
    
	getDataCategories: function(category)
	{
		$('#products').html('');
		//this.getCategories(category);	
	},
	getCategories: function(category)
	{
		$('#products').html('');
		
		var categories = this.categories;
		$.getJSON('./ajax_pos.php?action=getCategories&parentcategory='+category, function(data){
		
			if(category!=0)
			{
				$('#products').append('<div align="center" onclick="_TPV.getDataCategories('+categories[category]['parent']+')" id="category_'+categories[category]['parent']+'" title="Up" class="botonCategoria">'
						+'<div align="center"></div>UP</div>');
			}			
		
			$.each(data, function(key, val) 
			{
				if(categories[val.id]==undefined)
				{
					categories[val.id]= val;
					categories[val.id]['parent'] = category;
				}
				$('#products').append('<div align="center" onclick="_TPV.getDataCategories('+val.id+')" id="category_'+val.id+'" title="'+val.label+'" class="botonCategoria">'
							+'<div align="center"><img border="0" alt="" src="'+val.image+'"></div>'+val.label+'</div>');
				
			},_TPV.getProducts(category));
			
						
		});
	},
	loadMoreProducts: function(category,pag){
		//_TPV.showingProd = 0;
		$('#btnLoadMore').detach();
		var products = this.products;
		var categories = this.categories;
		var addProducts = true;
		
		$.getJSON('./ajax_pos.php?action=getMoreProducts&category='+ category+'&pag='+pag+'&ticketstate='+_TPV.ticket.type, function(data) {
			$.each(data, function(key, val) {
				if(products[val.id]==undefined)
					products[val.id]= val;
				if(addProducts && categories[category]!= undefined){
					var arrayItem = categories[category]['products'].length;
					categories[category]['products'][arrayItem] = val.id;
				}
				$('#products').append('<div onclick="_TPV.ticket.addLine('+ val.id +');_TPV.go_up();" align="center" id="produc_'+ val.id +'"  class="botonProducto">'
				+ '<div align="center"><a ><img border="0"  src="'+val.thumb+'"></a></div>'+ val.label + '</div>');
				_TPV.showingProd++;
			});
			if(_TPV.showingProd % 10 == 0 && _TPV.showingProd > 0){
				var txt=ajaxDataSend('Translate','More');
				$('#products').append('<div class="butProd" id="btnLoadMore" onclick="_TPV.loadMoreProducts('+category+','+_TPV.showingProd+')">'+txt+'</div>');
			}
		});
	},
	getProducts: function(category)
	{
		_TPV.showingProd = 0;
		var products = this.products;
		var categories = this.categories;
		if(typeof category!='undefined')
		{
			var addProducts = false;
			if(categories[category]!=undefined)
			{
				if(categories[category]['products']!=undefined)
				{
					categoryProducts = this.categories[category]['products'];
				
					$.each(categoryProducts, function(key, val) {
						product = products[val];
						$('#products').append('<div onclick="_TPV.ticket.addLine('+ product.id +');_TPV.go_up();" align="center" id="produc_'+ product.id +'" class="botonProducto">'
								+ '<div align="center"><a ><img border="0"  src="'+ product.thumb +'"></a></div>'+ product.label + '</div>');
						_TPV.showingProd++;
					});
					if(_TPV.showingProd % 10 == 0 && _TPV.showingProd > 0){
						var txt=ajaxDataSend('Translate','More');
						$('#products').append('<div class="butProd" id="btnLoadMore" onclick="_TPV.loadMoreProducts('+category+','+_TPV.showingProd+')">'+txt+'</div>');
					}
					return;
				} 
				else 
				{
					addProducts = true;
					categories[category]['products'] = new Array();
				}
			}
			
			$.getJSON('./ajax_pos.php?action=getProducts&category='+ category+'&ticketstate='+_TPV.ticket.type, function(data) {
												$.each(data, function(key, val) {
													if(products[val.id]==undefined)
														products[val.id]= val;
													if(addProducts){
														var arrayItem = categories[category]['products'].length;
														categories[category]['products'][arrayItem] = val.id;
													}
													$('#products').append('<div onclick="_TPV.ticket.addLine('+ val.id +');_TPV.go_up();" align="center" id="produc_'+ val.id +'"  class="botonProducto">'
													+ '<div align="center"><a ><img border="0"  src="'+val.thumb+'"></a></div>'+ val.label + '</div>');
													_TPV.showingProd++;
												});
												if( _TPV.showingProd % 10 == 0 && _TPV.showingProd > 0){
													var txt=ajaxDataSend('Translate','More');
													$('#products').append('<div class="butProd" id="btnLoadMore" onclick="_TPV.loadMoreProducts('+category+','+_TPV.showingProd+')">'+txt+' </div>');
												}
											});
		} 
		
	},
	
	go_up : function() {
		
		$("div.ticket_content").animate({ scrollTop: 0 }, "slow");
			return false;
	},
	
	printing: function(type,id)
	{
		//$(".btnPrint").printPage();
		switch(type)
		{
			case 'ticket':
				if(_TPV.defaultConfig['module']['print_mode'] == 0)
				{
					_TPV.getTicket(id,false);
					if (
							rc_canReceivePayments 
						||	_TPV.ticket.esCredito() 
						||	parseInt(_TPV.ticket.state) == 0
						|| $('#btnTicketRef').text().substring(0,4) == 'MGTK'
						|| $('#btnTicketRef').text().substring(0,4) == 'GMTK' 
						)
					{
						$(".btnPrint").attr('href','tpl/doc.tpl.php?id='+id);
					}
					else
					{
						$(".btnPrint").attr('href','tpl/ticket.tpl.php?id='+id);
					}
				}	
				else
				{
					ajaxDataSend('addPrint',"T"+id);
				}
				break;
			case 'facture':
				if(_TPV.defaultConfig['module']['print_mode'] == 0)
					$(".btnPrint").attr('href','tpl/facture.tpl.php?id='+id);
				else
					ajaxDataSend('addPrint',"F"+id);
				break;	
			case 'giftticket':
				if(_TPV.defaultConfig['module']['print_mode'] == 0)
					$(".btnPrint").attr('href','tpl/giftticket.tpl.php?id='+id);
				else
					ajaxDataSend('addPrint',"G"+id);
				break;
			case 'giftfacture':
				if(_TPV.defaultConfig['module']['print_mode'] == 0)
					$(".btnPrint").attr('href','tpl/giftfacture.tpl.php?id='+id);
				else
					ajaxDataSend('addPrint',"J"+id);
				break;		
			case 'closecash':
				if(_TPV.defaultConfig['module']['print_mode'] == 0)
					$(".btnPrint").attr('href','tpl/closecash.tpl.php?id='+id);
				else
					ajaxDataSend('addPrint',"C"+id);
				break;
		
		}
		 
		if(_TPV.defaultConfig['module']['print_mode'] == 0){
		var windowSizeArray = [ "width=0,height=0",
		                            "width=0,height=0,scrollbars=no" ];
		 
			$('.btnPrint').unbind('click');
			$('.btnPrint').click(function (event){
			console.log(rc_canReceivePayments);
			console.log(_TPV.ticket.esCredito());
			console.log(_TPV.ticket.difpayment);
			if (							
				rc_canReceivePayments 
			&& !_TPV.ticket.esCredito() 
			&& _TPV.ticket.difpayment >= 1
			&& _TPV.ticket.state == 2
			&& parseInt(_TPV.ticket.type) == 0 
			)
			{
				alert ('Ticket de contado sin pagar.');
				return false;
			}
             var url = $(this).attr("href");
             var windowName = "_blank";//$(this).attr("name");popup
             var windowSize = windowSizeArray[0];

             window.open(url, windowName, windowSize);

             event.preventDefault();

         });
		$(".btnPrint").click();
	}
	},
	
	validateMail : function(valor) {
		
		if (/^[0-9a-z_\-\.]+@[0-9a-z\-\.]+\.[a-z]{2,4}$/i.test(valor))
		{
			return true;
		} 
		else 
		{
			var txt=ajaxDataSend('Translate','MailError');
			_TPV.showError(txt);
			return false;
		}
	},
	
	
	
	mailTicket : function(idTicket)
	{
		$('#mail_to').val("");
		$('#idSendMail').dialog({ modal: true });
		$('#idSendMail').dialog({width: 400});
		$('#id_btn_ticketLine').unbind('click');
		$('#id_btn_ticketLine').click(function(){
		var email = new Object();	
		email.idTicket = idTicket;
		email.mail_to = $('#mail_to').val();
		
		if(_TPV.validateMail($('#mail_to').val()))
		{
			var result = ajaxDataSend('SendMail',email);
		}					
		$('#idSendMail').dialog("close");
		});		
	},
	mailFacture : function(idFacture)
	{
		$('#mail_to').val("");
		$('#idSendMail').dialog({ modal: true });
		$('#idSendMail').dialog({width: 400});
		$('#id_btn_ticketLine').unbind('click');
		$('#id_btn_ticketLine').click(function(){
		var email = new Object();	
		email.idFacture = idFacture;
		email.mail_to = $('#mail_to').val();
		
		if(_TPV.validateMail($('#mail_to').val()))
		{
			var result = ajaxDataSend('SendMail',email);
		}					
		$('#idSendMail').dialog("close");
		});		
	},
	
	mailCash : function(idCloseCash)
	{
		$('#mail_to').val("");
		$('#idSendMail').dialog({ modal: true });
		$('#idSendMail').dialog({width: 400});
		$('#id_btn_ticketLine').unbind('click');
		$('#id_btn_ticketLine').click(function(){
		var email = new Object();
		email.idCloseCash = idCloseCash;
		email.mail_to = $('#mail_to').val();
		
		if(_TPV.validateMail($('#mail_to').val()))
		{
			var result = ajaxDataSend('SendMail',email);
		}
					
		$('#idSendMail').dialog("close");
		$('#btnLogout').click();
		});		
	},
	
	deletTicket : function(idTicket)
	{
		$('#delete').val("");
		$('#idTicketDelet').dialog({ modal: true });
		$('#idTicketDelet').dialog({width: 400});
		$('#id_btn_ticketYes').click(function(){
			$('#id_btn_ticketYes').unbind('click');
			var result = ajaxDataSend('deleteTicket',idTicket);
			_TPV.searchByRef(-1);	
			_TPV.searchByRefFac(-1);
			
			$('#idTicketDelet').dialog("close");
		});	
		
		$('#id_btn_ticketNo').click(function(){
			$('#id_btn_ticketNo').unbind('click');
									
			$('#idTicketDelet').dialog("close");
			});
	},
	
	showInfo: function(error)
	{
		$('#infoText').html(error);
		console.log(error.length);
		$('#idPanelInfo').dialog({ modal: true });
		$('#idPanelInfo').dialog({width:500,height:200});
		//if (error.length)
		setTimeout(function(){$('#idPanelInfo').dialog("close")},24000);
	},
	showError: function(error)
	{
		$('#errorText').html(error);
		$('#idPanelError').dialog({ modal: true });
		$('#idPanelError').dialog({width:500,height:200});
		setTimeout(function(){$('#idPanelError').dialog("close")},24000);
	},
	addInfoProduct: function(idProduct)
	{
		$('#short_description_content').hide();
		$('#info_product').show();
		this.activeIdProduct = idProduct;
		if(typeof this.products[idProduct] == 'undefined'){
			var info = new Object();
			info['product']=idProduct;
			if(_TPV.ticket.customerId != 0){
				info['customer'] = _TPV.ticket.customerId;
			}
			else
				{
				info['customer'] = _TPV.customerId;
				}
			
			var result = getCacheProduct(info['customer'],idProduct);
			
			if(result)
				this.products[idProduct]= result[0];
		}
		product = this.products[idProduct];
		
		var prodPdfLink = '';
		if (product['pdf_file'].length > 0)
		{
			prodPdfLink = 	 ' <a '
							+'   href="#" '
							+'   title="Ver más detalles..." '
							+'   onclick="rc_showProductPdf(\''+product['pdf_file']+'\');return false;"'
							+'>'
							+'<img src="'+rc_url_root+'/theme/eldy/img/pdf3.png" style="width:15px;height:auto;" /> '
							+'</a>'
							;
		}
		 
		$('#info_product').find('#our_label_display').html(product.label + prodPdfLink);
		$('#info_product').find('#delivery_days_display').html("Días de Entrega: "+product.delivery_time_days);
		$('#info_product').find('#short_description_content').html(product.description);
		if(product.description){$('#btnHideInfo').show();}
		else{$('#btnHideInfo').hide();}
		var price = new Number(product.price_ttc);
		price = price.toFixed(decimals);
		var price_min = new Number(product.price_min_ttc);
		price_min = price_min.toFixed(decimals);
		$('#info_product').find('#our_price_display').html(price);	
		if(price_min>0){$('#info_product').find('#our_price_min_display').html(price_min);$('#our_price_min').show();}
		else{$('#our_price_min').hide();}
		$('#info_product').find('#bigpic').attr({src:product.image[0]});
		this.loadImages("#imageStorage", product);
		$('#info_product').find('#hiddenIdProduct').val(idProduct);
	},
	addInfoProductSt: function(idProduct)
	{
		$('#short_description_content_st').hide();
		$('#info_product_st').show();
		this.activeIdProduct = idProduct;
		if(typeof this.products[idProduct] == 'undefined'){
			var info = new Object();
			info['product']=idProduct;
			if(_TPV.ticket.customerId != 0){
				info['customer'] = _TPV.ticket.customerId;
			}
			else
				{
				info['customer'] = _TPV.customerId;
				}
			
			var result = getCacheProduct(info['customer'],idProduct);
			
			if(result)
				this.products[idProduct]= result[0];
		}
		product = this.products[idProduct];
		var prodPdfLink = '';
		if (product['pdf_file'].length > 0)
		{
			prodPdfLink = 	 ' <a '
							+'   href="#" '
							+'   title="Ver más detalles..." '
							+'   onclick="rc_showProductPdf(\''+product['pdf_file']+'\');return false;"'
							+'>'
							+'<img src="'+rc_url_root+'/theme/eldy/img/pdf3.png" style="width:15px;height:auto;" /> '
							+'</a>'
							;
		}
		 
		$('#info_product_st').find('#our_label_display_st').html(product.label + prodPdfLink);
		//$('#info_product_st').find('#our_label_display_st').html(product.label);
		$('#info_product_st').find('#short_description_content_st').html(product.description);
		if(product.description){$('#btnHideInfoSt').show();}
		else{$('#btnHideInfoSt').hide();}
		var price = new Number(product.price_ttc);
		price = price.toFixed(decimals);
		var price_min = new Number(product.price_min_ttc);
		price_min = price_min.toFixed(decimals);
		$('#info_product_st').find('#our_price_display_st').html(price);	
		if(price_min>0){$('#info_product_st').find('#our_price_min_display_st').html(price_min);$('#our_price_min_st').show();}
		else{$('#our_price_min_st').hide();}
		$('#info_product_st').find('#bigpic').attr({src:product.image[0]});
		this.loadImages("#imageStorage", product);
		$('#info_product_st').find('#hiddenIdProduct').val(idProduct);
	},
	loadImages: function(divId, product){
		$(".mySlides").remove();
		var image_html = "";
		if (Array.isArray(product.image)) {
			product.image.forEach(function( element, index ){
				image_html = '<img class="mySlides" src="'+element+'" style="width:100%">';
				$(divId).after( image_html );
			});
		}else if(product.image){
			image_html = '<img class="mySlides" src="'+product.image+'" style="width:100%">';
			$(divId).after( image_html );
		}
	},
	loadConfig: function(){
		
		var result = ajaxDataSend('getPlaces');
		
		if(result)
		{
			_TPV.places[null]= '';
			$.each(result, function(id, item) {
	    		_TPV.places[item['id']]= item['name'];
	    	});
		}	
		var result = ajaxDataSend('getConfig',null);
		if(result)
		{
			this.defaultConfig = result;
			$('#id_user_name').html(result['user']['name']);
			$('#id_user_terminal').html(result['terminal']['name']);
			$('#infoCustomer').html(result['customer']['name']);
			$('#infoCustomer_').html(result['customer']['name']);
			$('#Customer_remise').html(result['customer']['remise']+'%');
			_TPV.temporalpro=result['customer']['proyectos'];
			_TPV.restar_por_pagar=result['customer']['por_pagar'];
			_TPV.limite_de_credito=result['customer']['limite'];
			$('#id_image').attr("src",result['user']['photo']);
			_TPV.customerId = result['customer']['id'];
			_TPV.employeeId = result['user']['id'];
			_TPV.warehouseId = result['terminal']['warehouse'];
			_TPV.faclimit = result['terminal']['faclimit'];
			_TPV.discount = result['customer']['remise'];
			_TPV.points = result['customer']['points'];
			_TPV.coupon = result['customer']['coupon'];
			_TPV.cashId = result['terminal']['id'];
			discount_percent_limit = result['user']['posdesc'];

			if(result['terminal']['tactil'] == 1){
				_TPV.tpvTactil(true);
			}
			else{
				_TPV.tpvTactil(false);
			}
			if(result['terminal']['barcode'] == 1){
				$('#id_product_search').focus();
			}

			var iterator = 0;
			var select_p='<select id="p_client'+result['customer']['id']+'">';
			select_p += '<option value="0" selected>&nbsp;</option>';
			var button_action = "&nbsp";
			$.each(result['customer']['proyectos'],function (row,proyecto) {
				if(proyecto['proyect'] == "&nbsp")
					select_p="&nbsp";
				else {
					select_p += '<option value="' + proyecto["proyectid"] + '">' + proyecto["proyect"] + '</option>';
					iterator++;
				}
			});
			if(iterator > 0) {
				select_p += "</select>";
				button_action='<a class="icontype" style="cursor:pointer" onclick="_TPV.ticket.addTicketCustomer('+result['customer']['id']+',\''+result['customer']['name']+'\','+result['customer']['remise']+','+result['customer']['coupon']+','+result['customer']['points']+',$(\'#p_client'+result['customer']['id']+'\').val(),$(\'#p_client'+result['customer']['id']+' option:selected\').text(),1);">Elegir</a>';
				/*script = '<script>$("#p_client'+result['customer']['id']+'").change(function(){' +
					'_TPV.ticket.addTicketCustomer('+result['customer']['id']+',\''+result['customer']['name']+'\','+result['customer']['remise']+','+result['customer']['coupon']+','+result['customer']['points']+',$(\'#p_client'+result['customer']['id']+'\').val(),$(\'#p_client'+result['customer']['id']+' option:selected\').text());});</script>';*/
			}
			else{
				//button_action='<a class="icontype" style="cursor:pointer" onclick="_TPV.ticket.addTicketCustomer('+result['customer']['id']+',\''+result['customer']['name']+'\','+result['customer']['remise']+','+result['customer']['coupon']+','+result['customer']['points']+',0,\'&nbsp\',1);">Elegir</a>';
				_TPV.ticket.addTicketCustomer(result['customer']['id'],'',result['customer']['name'],result['customer']['remise'],result['customer']['coupon'],result['customer']['points'],0,'&nbsp',1);
			}
			$('#infoProyectCustomer_').html(select_p+button_action);
		}
		var result = ajaxDataSend('getNotes',0);
		if(result)
		{
			$('#totalNote_').html(result);
		}
		
		return;
	},
	showInfoProduct: function(on){
		
		if(!on){
			_TPV.infoProduct = 0;
			
		} else {
			_TPV.infoProduct = 1;
		}
		return;
	},
	tpvTactil: function(on){
		
		if(!on){
			//$('.quertyKeyboard').keyboard('option', 'openOn', '');
			$('[type=text]').each(function(){
				$(this).getkeyboard().destroy();
			});
			return;
		}
		else
		{
			$('[type=text]:not(.numKeyboard)').keyboard({
				layout:'qwerty',
				usePreview:false , 
				autoAccept : true,
				accepted : function(e, keyboard, el){
				
			  }	
			});
			$('.numKeyboard').keyboard({ 
				//layout:'num',
				layout: 'custom',
				usePreview:false,
				autoAccept : true,
				customLayout: {
					'default' : [
						
						'7 8 9',
						'4 5 6',
						'1 2 3',
						'0 . {sign}',
						'{bksp} {a} {c}'
					]
				},
				accepted : function(e, el){ 
					
				}
			});
			return;
		}
		
	}
});

$(function(){
	
			_TPV.setButtonEvents();
			
			_TPV.tpvTactil(true);
			$.keyboard.keyaction.enter = function(base){
				  if (base.el.tagName === "INPUT") {
				    //base.accept();      // accept the content
				    var e = $.Event('keypress');
				    e.which = 13; 
				    base.close(true);
				    $(base.el).trigger(e);
				    // same as base.accept();
				    return false;  
				 
				  } else {
				    base.insertText('\r\n'); // textarea
				  }
			};
		});
$(document).ready(function() {
	_TPV.loadConfig();
	_TPV.ticket.newTicket();
	_TPV.ticket.setButtonState(false);
		$(".numKeyboard").keypress(function(e) {
		  
		 	
			if(window.event){ // IE
				var charCode = e.keyCode;
			} else if (e.which) { // Safari 4, Firefox 3.0.4
				var charCode = e.which
			}
			if (charCode!=8 && charCode!=0 && ((charCode<48 && charCode!=46 && charCode!=44) || charCode>57))
				
			return false;
			return true;
		});

	});
var _TPV = new TPV();
_TPV.getDataCategories(0);

function getCacheProduct(customer,product)
{
	var result;
	if (	parseInt(_TPV.ticket.rc_products.ticketId) == _TPV.ticket.id
		&&	_TPV.ticket.customerId == _TPV.ticket.rc_products.customerId
		&&	typeof _TPV.ticket.rc_products.products[product] != 'undefined'
		// && false
		)
	{
		result = _TPV.ticket.rc_products.products[product]['data'];
	}
	else
	{
		var result = ajaxDataSend('getProduct',{'product':product,'customer':customer});
	}
	return result;
		
}

function removeKey(arrayName,key)
{
 var x;
 var tmpArray = new Array();
 for (var i=0;i<arrayName.length;i++)
 {
	 if(arrayName[i]['idProduct']!=key) { tmpArray.push(arrayName[i]); }
 }
 return tmpArray;
}

function ajaxSend(action)
{
	var result;
	$.ajax({
			type: "POST",
			url: './ajax_pos.php',
			data: 'action='+ action,
			async : false,
			success: function(msg)
			{
				result = msg;
			}
		});
	return result;
}
function displayPrice(pr)
{
	//return (Math.round(pr*100/5)*5/100).toFixed(2);
	if(typeof _TPV.defaultConfig['decrange']['tot']!='undefined')
			precision = _TPV.defaultConfig['decrange']['tot'];
			
	var precision = 2;
	if (isNaN(pr))
	{
		return parseFloat('0.0000').toFixed(precision);
	}
	if (isNaN(parseFloat(pr).toFixed(precision)))
	{
		return parseFloat('0.0000').toFixed(precision)
	}
	else
	{
		return PriceCommas(parseFloat(pr).toFixed(precision));
	}
	
}
function PriceCommas(n) {
	n = n.toString()
	while (true) {
		var n2 = n.replace(/(\d)(\d{3})($|,|\.)/g, '$1,$2$3')
		if (n == n2) break
		n = n2
	}
	return n
}
function showLeftContent(divcontent)
{
	$('.leftBlock').each(function(){
		$(this).hide();
	});
	$(divcontent).show();
}
function hideLeftContent()
{
	$('.leftBlock').each(function(){
		$(this).hide();
	});
	$('#products').show();
}
function ajaxDataSend(action,data)
{
	var result;
	var DTO = {'data': data};

	var data = JSON.stringify(DTO);
	$.ajax({
		type: "POST",
	  traditional: true,
	  cache:false,
		url: './ajax_pos.php?action='+action,
		contentType: "application/json;charset=utf-8",
		dataType: "json", 
		async: false,
		processData:false,
		data: data,
		success: function(msg)
		{
			result = msg;
		},
		error: function()
		{
			result = {'error':{'value':'','desc':''},'data':''}
		}
		
	});
	
	if(result!=null && typeof result!='undefined'){
		if(typeof result['error']!='undefined' && typeof result['error']['desc']!='undefined' && result['error']['desc']!='') // desc,value
		{
			if(result['error']['value']==0){
				_TPV.showInfo(result['error']['desc']);
			}
			else if(result['error']['value']== 99){
				_TPV.showError(result['error']['desc']);
				window.location.href = "./disconect.php";
			}
			else {
				_TPV.showError(result['error']['desc']);
			}
			if(typeof result['error']['value']!='undefined' && parseInt(result['error']['value'])==1)
				return false;
		}
		if(typeof result['error']!='undefined' && typeof result['error']['value']!='undefined' && result['error']['value']==0) // desc,value
		{
			if(action == 'closeCash'){
				return JSON.parse('{"id":'+result['data']+',"pdf":"'+result['error']['pdf']+'"}');
			}else{
				if(typeof result['data']!='undefined')
					return result['data']; 
			}
		}
		//_TPV.showInfo('Error de ejecucion del codigo javascript');
	}
	return result;
}
