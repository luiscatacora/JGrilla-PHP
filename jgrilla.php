<?php if ( ! defined('BASEPATH')) exit('No se permite acceso directo al script');
/**********************************************************************
JGRILLA v0.2
Autor  : Luis Catacora Murillo
e-mail : luiscatacora@live.com
***********************************************************************/
class Jgrilla{
    public $urlGrilla 		= "";
	public $barraFiltro 	= array('stringResult'=>true,'searchOnEnter'=>false);
	public $operacion 		= "";	
	public $nuevoEnLinea 	= false;	
    protected $opcionesGrilla = array(
        "width" => "650",
        "hoverrows" => true, //
        "viewrecords" => true,
		"rownumbers" => true,
        "datatype" => "json",  //xml
        //"jsonReader" => array("repeatitems" => false, "subgrid" => array("repeatitems" => false)),
        //"xmlReader" => array("repeatitems" => false, "subgrid" => array("repeatitems" => false)),
        //"gridview" => true,// */       
        "rowNum" => 10,
		"autowidth"	=> true,
        "rowList" => array(10, 30, 60, 120, 240, 480, 960),
        "sortorder" => "desc",
        "postData" => array("oper" => "grid"),
        "prmNames" => array("page" => "page", "rows" => "rows", "sort" => "sidx", "order" => "sord", "search" => "_search", "nd" => "nd", "id" => "id", "filter" => "filters", "searchField" => "searchField", "searchOper" => "searchOper", "searchString" => "searchString", "oper" => "oper", "query" => "grid", "addoper" => "add", "editoper" => "edit", "deloper" => "del", "excel" => "excel", "subgrid" => "subgrid", "totalrows" => "totalrows", "autocomplete" => "autocmpl"),
		"loadComplete"=>"js:function() {
						var table = this;
						setTimeout(function(){
							styleCheckbox(table);							
							updateActionIcons(table);
							updatePagerIcons(table);
							enableTooltips(table);
						}, 0);
					}",			

    );
    protected $columnaGrilla = array();
    protected $accionGrilla = array();
    protected $opcionBarra = array(
		"cloneToTop"=>true, 
		"excel" => true, 
		"pdf" => true,
        "edit" => true,
		"edittext"=>"",
		"editicon"=>'ace-icon fa fa-pencil blue', //ace
        "add" => true,
		"addtext"=>"",
		"addicon"=> 'ace-icon fa fa-plus-circle purple',//ace
        "del" => true,
		"deltext"=>"",
		"delicon"=> 'ace-icon fa fa-trash-o red',
        "search" => true,
		"searchtext"=>"",
		"searchicon"=> 'ace-icon fa fa-search orange',//ace
        "view" => true,
		"viewtext"=>"",
		"viewicon"=>'ace-icon fa fa-search-plus grey',//ace
        "refresh" => true,
		"refreshtext"=>"",
		"refreshicon"=> 'ace-icon fa fa-refresh green');//ace
    protected $opcionEdit = array("width"=>450/* "closeAfterEdit" => true, "reloadAfterSubmit" => true */);
    protected $opcionAdd = array("width"=>450/* "reloadAfterSubmit" => true, "closeAfterAdd" => true */); //"height"=>280,"reloadAfterSubmit"=>false
    protected $opcionDel = array();
    protected $opcionSearch = array("multipleSearch"=>true/* "closeAfterSearch" => true */);
    protected $opcionView = array("width"=>450);
    protected $cagarGrilla = true;
	protected $grupoTitulo = array();
    protected $metodoGrilla = array();
	protected $jsCodigo = "";
	protected $nuevoEnLineaOpcion = array("addParams" => array(), "editParams" => array());
	protected $modeloNombre = "";
	protected $modeloCRUD 	= true;
	protected $modeloFuncion = array(
		"insert" => "insert",
		"update" => "update",
		"delete" => "delete",
		"select" => "select",
		"total"  => "total");	
	protected $filtro 		= "";
	protected $ci;
	protected $campos;
	protected $tabla;	
	private $whereFiltro	= "";
	
    function __construct() {		
       if (isset($_REQUEST['oper'])) {
            $this->cagarGrilla = false;            
            $this->operacion = $_REQUEST['oper'];
			$this->ci =& get_instance();   
        } else {
            $dato = $this->operacion;
            $this->cagarGrilla = true;
        }
    }
	function esOperacion(){
		if (isset($_REQUEST['oper']))
			return true;
		return false;
	}
	
	function extenderArray($actual, $dato) {
        foreach ($dato as $i => $valor) {
            if (is_array($valor)) {
                if (!isset($actual[$i])) {
                    $actual[$i] = $valor;
                } else {
                    $actual[$i] = $this->extenderArray($actual[$i], $valor);
                }
            } else {
                $actual[$i] = $valor;
            }
        } 
        return $actual;
    }
	function convertirArray($dato) {
        $resultado = "";
        if (is_string($dato)) {
			if (strpos($dato, 'js:') === 0){
                $resultado = substr($dato, 3);
			}else{
            	$resultado = '"' . $dato . '"';
			}
        } elseif (is_int($dato) or is_float($dato)) {
            $resultado = $dato;
        } elseif (is_bool($dato)) {
            if ($dato) {
                $resultado = "true";
            } else {
                $resultado = "false";
            }
        } elseif (is_array($dato)) {
            $resp = array();
            $ban = false;
            foreach ($dato as $i => $valor) {				
                if (is_int($i)) {
					/*if(isset($valor["edittype"]) && $valor["edittype"]=="select" ){
						print_r($this->convertirValue($valor["editoptions"]["value"]));
						exit;
					}*/					
                    $resp[] = $this->convertirArray($valor);
                    $ban = true;
                } else {
                    $resp[] = $i . ': ' . $this->convertirArray($valor);
                    $ban = false;
                }
            }
            if ($ban) {
                $resultado = '[' . implode(', ', $resp) . ']';
            } else {
                $resultado = '{' . implode(', ', $resp) . '}';
            }
        }
        return $resultado;
    }

	public function convertirValue($value){
		$data=array();
		if (is_array($value) && count($value) > 0) {
            //$data = array_map(create_function('$i, $v','return $i.":".$v;'), array_keys($value), array_values($value));
			foreach($value as $v){
				$data[]=$v['id'].":".$v['nombre'];
			}
            $data = implode(';',$data);
        }elseif(is_object($value)){
            return false;
		}
		return $data;		
	}

    public function agregaColumna(array $propiedad, $posicion = 'last') {
        if (!$this->cagarGrilla)
            return false; 
		if (is_array($propiedad) && count($propiedad) > 0) {
            $numcolu = count($this->columnaGrilla);
            if ($numcolu > 0) {
                if (strtolower($posicion) === 'first') {
                    array_unshift($this->columnaGrilla, $propiedad);
                } else if (strtolower($posicion) === 'last') {
                    array_push($this->columnaGrilla, $propiedad);
                } else if ((int) $posicion >= 0 && (int) $posicion <= $numcolu - 1) {
                    $a = array_slice($this->columnaGrilla, 0, $posicion + 1);
                    $b = array_slice($this->columnaGrilla, $posicion + 1);
                    array_push($a, $propiedad);
                    $this->columnaGrilla = array();
                    foreach ($b as $cm) {
                        $a[] = $cm;
                    } 
					$this->columnaGrilla = $a;
                } 
				$propiedad = null;
                return true;
            }
        } 
		return false;
    } 
	public function operacionLinea($nombre = "Operación", $posicion = 'last', $ancho = 80){ 
		$this->agregaColumna(array(
			"name"			=> $nombre,
			"formatter"		=> "actions",
			"editable"		=> false,
			"sortable"		=> false,
			"resizable"		=> false,
			"fixed"			=> true,
			"search"		=> false,
			"viewable" 		=> false,			
			"width"			=> $ancho,
			"formatoptions" => array("keys" => true)
		), $posicion);
	}
	private function setExtenderOpcion($opcion, $nomOpcion){
		if ($this->cagarGrilla) {
			if (is_array($opcion)) {
				$this->{$nomOpcion} = $this->extenderArray($this->{$nomOpcion}, $opcion);
			}
		} else {
            return false;
        }
	}
    public function setOpcionGrilla($opciones) {
        if ($this->cagarGrilla) {
            if (is_array($opciones)) {
                $this->opcionesGrilla = $this->extenderArray($this->opcionesGrilla, $opciones);
            }
        } else {
            return false;
        }
    }
	public function setBarraFiltro($opciones){
		$this->setExtenderOpcion($opciones, 'barraFiltro');    		
	}
    public function setColumnaGrilla($nombre, array $propiedades) {
        $result = false;
        if ($this->cagarGrilla) {
            if (!is_array($propiedades))
                return $result;
            if (count($this->columnaGrilla) > 0) {
                if (is_int($nombre)) {
                    $this->columnaGrilla[$nombre] = $this->extenderArray($this->columnaGrilla[$nombre], $propiedades);
                    $result = true;
                } else {
                    foreach ($this->columnaGrilla as $i => $valor) {
                        if ($valor['name'] == trim($nombre)) {
                            $this->columnaGrilla[$i] = $this->extenderArray($this->columnaGrilla[$i], $propiedades);
                            $result = true;
                            break;
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function cargarDatosGrilla($tabla = "", array $campo) {
        $this->tabla	= $tabla;
		$this->campos	= $campo;
		if ($this->cagarGrilla) {
			if($this->setColumna($campo))
				return true;
        }
        return false;
    }	
	public function setColumna($campo){
		if(is_array($campo)){				
			foreach ($campo as $i => $valor) {
				$this->columnaGrilla[] = array('name' => $valor, 'index' => $valor);					
			}
			return true;
		} 
		return false;		
	}
    public function setAccionGrilla($operacion, $sql, $param = null) {
        $this->accionGrilla = array("accion" => $operacion, "sql" => $sql, "param" => $param);
    }

    public function setOpcionBarra($opcion, $tipo = "bar") {
		$this->opcionBarra = array_merge($this->opcionBarra, $opcion);
        switch ($tipo) {
            case 'bar':
                $this->opcionBarra = array_merge($this->opcionBarra, $opcion);
                break;
            case 'edit':
                $this->opcionEdit = array_merge($this->opcionEdit, $opcion);
                break;
            case 'add':
                $this->opcionAdd = array_merge($this->opcionAdd, $opcion);
                break;
            case 'del':
                $this->opcionDel = array_merge($this->opcionDel, $opcion);
                break;
            case 'search':
                $this->opcionSearch = array_merge($this->opcionSearch, $opcion);
                break;
            case 'view':
                $this->opcionView = array_merge($this->opcionView, $opcion);
        }
    }

    public function setUrlGrilla($url) {
        if ($this->cagarGrilla) {
            $this->urlGrilla = $url;
            $this->setOpcionGrilla(array("url" => $url, "editurl" => $url, 'cellurl' => $url));	
            return true;
        } else {
            return false;
        }
    }
	
	//////////////////////////////////////
    public function setSubGrilla($url = '', $titulo = false, $ancho = false, $alinea = false, $parametro = false) {
        if ($this->cagarGrilla){
			if ($titulo && is_array($titulo)) {
				$total = count($titulo);
				for ($i = 0; $i < $total; $i++) {
					if (!isset($ancho[$i]))
						$ancho[$i] = 100; 
					if (!isset($alinea[$i]))
						$alinea[$i] = 'center';
				} 
				$this->setOpcionGrilla(array("gridview" => false, "subGrid" => true, "subGridUrl" => $url, 
				"subGridModel" => array(array("name" => $titulo, "width" => $ancho, "align" => $alinea, "params" => $parametro))));
				return true;
			} 
		}
		return false;
    }
	
	////////////////////////////////////////	
    public function setSubGrillaFull($urlSub, $subgridnames=null) {
        if ($this->cagarGrilla){
			$this->setOpcionGrilla(array("subGrid" => true, "gridview" => false));
			$setval = (is_array($subgridnames) && count($subgridnames) > 0 ) ? 'true' : 'false';
			if ($setval == 'true') {
				$anames = implode(",", $subgridnames);
			} else {
				$anames = '';
			} 
			$codigo = 'function(subgridid,id){
				var data = {subgrid:subgridid, rowid:id};
				if("'.$setval.'" == "true") {
					var anm= "'.$anames.'";
					anm = anm.split(",");
					var rd = jQuery(this).jqGrid("getRowData", id);
					if(rd) {
						for(var i=0; i<anm.length; i++) {
							if(rd[anm[i]]) {
								data[anm[i]] = rd[anm[i]];
							}
						}
					}
				}
				$("#"+jQuery.jgrid.jqID(subgridid)).load("'.$urlSub.'",data);
			}';
			$this->setEventoGrilla('subGridRowExpanded', $codigo);
			return true;
		}else{
			return false;	
		}
    }
	
	public function setEventoGrilla($evento, $codigo) {
        if ($this->cagarGrilla) {
			$this->opcionesGrilla[$evento] = "js:" . $codigo;
			return true;
		}else{
			return false;	
		}
    }
	/////////////////////////////////
	public function setGrupoTitulo($opciones){
		if (is_array($opciones)) {
        	array_push($this->grupoTitulo, $opciones);
        }		
	}
	
	public function getGrupoTitulo($listar){
		$result = "";
		if(count($this->grupoTitulo) > 0){
			$result = "jQuery('#".$listar."').jqGrid('setGroupHeaders', {
				  useColSpanStyle: true, 
				  groupHeaders:";
			$result .= $this->convertirArray($this->grupoTitulo)."});";
		}			
		return $result;
	}
	
    public function setMetodoGrilla($grid, $method, array $aoptions = null) {
        if ($this->cagarGrilla) {
            $prm = '';
            if (is_array($aoptions) && count($aoptions) > 0) {
                $prm = $this->convertirArray($aoptions);
                $prm = substr($prm, 1);
                $prm = substr($prm, 0, -1);
                $prm = "," . $prm;
            } if (strpos($grid, "#") === false || strpos($grid, "#") > 0) {
                $grid = "#" . $grid;
            } 
			$this->metodoGrilla[] = "jQuery('" . $grid . "').jqGrid('" . $method . "'" . $prm . ");";
        }
    }
		
	public function setJsCodigo($cod){
		if ($this->cagarGrilla) {
			$this->jsCodigo = "js:".$cod;
		}
	}
	
	public function setNuevoEnLineaOpcion($modulo, $opcion) {
        if (!$this->cagarGrilla)
            return false; 
		switch ($modulo) {
            case 'navigator': 
				$this->nuevoEnLineaOpcion = array_merge($this->nuevoEnLineaOpcion, $opcion);
                break;
            case 'add': 
				$this->nuevoEnLineaOpcion['addParams'] = array_merge($this->nuevoEnLineaOpcion['addParams'], $opcion);
                break;
            case 'edit': 
				$this->nuevoEnLineaOpcion['editParams'] = array_merge($this->nuevoEnLineaOpcion['editParams'], $opcion);
                break;
        } 
		return true;
    }	
	
    public function generaGrilla($listar='', $pager='') {
        if ($this->cagarGrilla) {
            $resultado = "";
            $resultado .= "<table id='" . $listar . "'></table>";
            $resultado .= "<div id='" . $pager . "'></div>";
			$this->opcionesGrilla['colModel'] = $this->columnaGrilla;
			//print_r($this->opcionesGrilla['colModel']); exit;
            $this->opcionesGrilla['pager'] = "#" . $pager;
            $resultado .= "<script type='text/javascript'>";
            $resultado .= "jQuery(document).ready(function() {";
            $resultado .= "jQuery('#" . $listar . "').jqGrid(" . $this->convertirArray($this->opcionesGrilla) . ");";
            $resultado .= "jQuery('#" . $listar . "').jqGrid('navGrid','#" . $pager . "',";
            
			$resultado .= $this->convertirArray($this->opcionBarra) . ',';
            $resultado .= $this->convertirArray($this->opcionEdit) . ',';
            $resultado .= $this->convertirArray($this->opcionAdd) . ',';
            $resultado .= $this->convertirArray($this->opcionDel) . ',';
            $resultado .= $this->convertirArray($this->opcionSearch) . ',';
            $resultado .= $this->convertirArray($this->opcionView).");";
			$resultado .= $this->getGrupoTitulo($listar);
			
			if($this->opcionBarra["excel"] == true) {
             	$resultado .= "jQuery('#".$listar."').jqGrid('navButtonAdd','#".$pager."', {id: '".$listar."_xls',caption: '', buttonicon: 'ace-icon fa fa-file-excel-o green', title: 'Exportar a excel', onClickButton:  function(e){
					try {
						jQuery('#".$listar."').jqGrid('excelExport',{tag:'excel', url:'".$this->urlGrilla."'});
					} catch (e) {
						window.location = '".$this->urlGrilla."?oper=excel';
					}
				}});";
            } 
			if($this->opcionBarra["pdf"] == true) {
             	$resultado .= "jQuery('#".$listar."').jqGrid('navButtonAdd','#".$pager."', {id: '".$listar."_pdf',caption: '', buttonicon: 'ace-icon fa fa-file-pdf-o red', title: 'Exportar a PDF', onClickButton:  function(e){
					try {
						jQuery('#".$listar."').jqGrid('excelExport',{tag:'pdf', url:'".$this->urlGrilla."'});
					} catch (e) {
						window.location = '".$this->urlGrilla."?oper=pdf';
					}
				}});";
            } 			
				
				
			
			if ($this->nuevoEnLinea && strlen($pager) > 0) {
				$this->setMetodoGrilla($listar, 'setFrozenColumns');
                $funcionLinea = 'function (id, res){
					res = res.responseText.split("#");
					try {
						$(this).jqGrid("setCell", id, res[0], res[1]);
						$("#"+id, "#"+this.p.id).removeClass("jqgrid-new-row").attr("id",res[1] );
						$(this)[0].p.selrow = res[1];											
					} catch (asr) {}
				}';
                $this->nuevoEnLineaOpcion['addParams'] = $this->extenderArray($this->nuevoEnLineaOpcion['addParams'], array("aftersavefunc" => "js:" . $funcionLinea));
                $this->nuevoEnLineaOpcion['editParams'] = $this->extenderArray($this->nuevoEnLineaOpcion['editParams'], array("aftersavefunc" => "js:" . $funcionLinea));
                $resultado .= "jQuery('#".$listar."').jqGrid('inlineNav','".$pager."',".$this->convertirArray($this->nuevoEnLineaOpcion) .");\n";
            }			
			
			
			
			if($this->barraFiltro){	
				//$resultado .= "jQuery('#".$listar."').jqGrid('filterToolbar',{stringResult: true,searchOnEnter: false});";
				$resultado .= "jQuery('#".$listar."').jqGrid('filterToolbar', ".$this->convertirArray($this->barraFiltro).");";
			}
			if(count($this->metodoGrilla) > 0){
				foreach($this->metodoGrilla as $dato){
					$resultado .= $dato."\n";
				}
			}
			if(strlen($this->jsCodigo) > 0)
                $resultado .= $this->convertirArray($this->jsCodigo); 
			
			$resultado .= "
			function beforeDeleteCallback(e) {
				var form = $(e[0]);
				if(form.data('styled')) return false;
				form.closest('.ui-jqdialog').find('.ui-jqdialog-titlebar').wrapInner('<div class=\"widget-header\" />')
				style_delete_form(form);
				form.data('styled', true);
			}
			function beforeEditCallback(e) {
					var form = $(e[0]);
					form.closest('.ui-jqdialog').find('.ui-jqdialog-titlebar').wrapInner('<div class=\"widget-header\" />')
					style_edit_form(form);
			}
			function styleCheckbox(table) {
				/**
					$(table).find('input:checkbox').addClass('ace')
					.wrap('<label />')
					.after('<span class=\"lbl align-top\" />')
					$('.ui-jqgrid-labels th[id*=\"_cb\"]:first-child')
					.find('input.cbox[type=checkbox]').addClass('ace')
					.wrap('<label />').after('<span class=\"lbl align-top\" />');
				*/
				}
				function updateActionIcons(table) {
					/**
					var replacement = 
					{
						'ui-ace-icon fa fa-pencil' : 'ace-icon fa fa-pencil blue',
						'ui-ace-icon fa fa-trash-o' : 'ace-icon fa fa-trash-o red',
						'ui-icon-disk' : 'ace-icon fa fa-check green',
						'ui-icon-cancel' : 'ace-icon fa fa-times red'
					};
					$(table).find('.ui-pg-div span.ui-icon').each(function(){
						var icon = $(this);
						var $"."class = $.trim(icon.attr('class').replace('ui-icon', ''));
						if($"."class in replacement) icon.attr('class', 'ui-icon '+replacement[$"."class]);
					})
					*/
				}
				function updatePagerIcons(table) {
					var replacement = 
					{
						'ui-icon-seek-first' : 'ace-icon fa fa-angle-double-left bigger-140',
						'ui-icon-seek-prev' : 'ace-icon fa fa-angle-left bigger-140',
						'ui-icon-seek-next' : 'ace-icon fa fa-angle-right bigger-140',
						'ui-icon-seek-end' : 'ace-icon fa fa-angle-double-right bigger-140'
					};
					$('.ui-pg-table:not(.navtable) > tbody > tr > .ui-pg-button > .ui-icon').each(function(){
						var icon = $(this);
						var $"."class = $.trim(icon.attr('class').replace('ui-icon', ''));
						
						if($"."class in replacement) icon.attr('class', 'ui-icon '+replacement[$"."class]);
					})
				}
			
				function enableTooltips(table) {
					$('.navtable .ui-pg-button').tooltip({container:'body'});
					$(table).find('.ui-pg-div').tooltip({container:'body'});
				}";
			
			$resultado .= "}); </script>";
			return array("grilla" => $resultado);
        } else {
			//print_r($_POST);
			$camp	= "";
			$tabla	= $this->tabla;
			$data	= $this->campos;
			$where	= "";
			if(isset($_POST['oper'])){
				$camp = $_POST;
				unset($camp['oper']);
			}
			$crudfn = "";
			if(!$this->modeloCRUD){
				$this->ci->load->model($this->modeloNombre,"",true);
			}	
			switch($this->operacion){
				case 'edit':
					$where	= array($this->campos['id'] => $camp['id']);					
					unset($camp['id']);	
					$data 	= $camp;					
					if($this->modeloCRUD){
						//$this->update($where, $data);
					}else{
						$this->ci->{$this->modeloNombre}->{$this->modeloFuncion['update']}($tabla,$where,$data);
					}
				break;
				case 'add':
					unset($camp['oper']);
					unset($camp['id']);
					$data = $camp;
					if($this->modeloCRUD){
						//$this->insert($data);
					}else{
						$this->ci->{$this->modeloNombre}->{$this->modeloFuncion['insert']}($tabla,$data);
					}				
				break;
				case 'del':
					$where	= array($this->campos['id'] => $camp['id']);
					if($this->modeloCRUD){
						//$this->delete($where);
					}else{
						$this->ci->{$this->modeloNombre}->{$this->modeloFuncion['delete']}($tabla,$where);
					}				
				break;
				case 'pdf':
					$this->exportaPDF();
					return true;
				break;
				case 'excel':
					$this->exportaExcel();
					return true;
				break;
				default:
					if($this->modeloCRUD){
						/*$this->total();
						$this->getSelect();*/
					}else{
						$this->getConsulta();
					}
				break;	
			}					
			return $this->operacion;		      
        }
    }
	private $nroRegistro = 0;
	private function getConsulta(){
		$this->nroRegistro = $this->ci->{$this->modeloNombre}->{$this->modeloFuncion['total']}($this->tabla, $this->getFiltro());
		$this->getFiltro($this->nroRegistro);
		return $this->ci->{$this->modeloNombre}->{$this->modeloFuncion['select']}($this->tabla);		
	}
	/*private function update($where, $data){
		$this->ci->db->where($where);
		$this->ci->db->update($this->tabla, $data);		
	}
	
	private function insert($data){
		$this->ci->db->insert($this->tabla, $data);	
	}
	
	private function delete($where){
		$this->ci->db->delete($this->tabla,$where);
	}
	private function total(){
		$this->ci->db->select("COUNT(*) as total");
		$this->ci->db->from($this->tabla);
		$res = $this->getData($this->ci->db, false);		
		return $res->row()->total;		
	}
	private function getSelect(){
		$this->ci->db->select(implode(", ", $this->campos));
		$this->ci->db->from($this->tabla);
		echo $this->getData($this->ci->db);		
	}*/
	public function setModeloCRUD($modelo = "", $funciones = array()){
		if($modelo != ""){			
			$this->modeloNombre = $modelo;
			if(is_array($funciones))
				$this->modeloFuncion = array_merge($this->modeloFuncion, $funciones);			
			$this->modeloCRUD = false;
		}
	}	

	public function setFiltro($filtro){
		$this->filtro = $filtro;
	}
	
	protected function getFiltro($total=0){
		$resp = new stdClass();
		$page	= $_GET['page'];
		$limit 	= $_GET['rows'];
		$sidx 	= $_GET['sidx'];
		$sord 	= $_GET['sord'];
		$s 	= "";		
		if(!$sidx) 
			$sidx = 1;
		$count = $total;
		if( $count > 0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$total_pages = 1;
		}
		if ($page > $total_pages) 
			$page = $total_pages;
		$start = ($limit*$page) - $limit;				
		if($_GET['_search'] == 'true'){		
			$filtro	= $_GET['filters'];
			$filtro = json_decode($filtro);
			$regla	= $filtro->rules;
			$opera	= $filtro->groupOp;
			$i_ 	= " ";
			$sopt 	= array('eq' => "=", 'ne' => "<>", 'lt' => "<", 'le' => "<=", 'gt' => ">", 'ge' => ">=", 'bw' => " {$i_}LIKE ", 'bn' => " NOT {$i_}LIKE ", 'in' => ' IN ', 'ni' => ' NOT IN', 'ew' => " {$i_}LIKE ", 'en' => " NOT {$i_}LIKE ", 'cn' => " {$i_}LIKE ", 'nc' => " NOT {$i_}LIKE ", 'nu' => 'IS NULL', 'nn' => 'IS NOT NULL');
			$temp 	= array();
			foreach($regla as $indice=>$valor){
				$campo 	= $valor->field;
				$op 	= $valor->op;
				$dato 	= $valor->data;
				if($s != "")			
					$s 	.=  $opera.' ';
				switch ($op) {
					case 'bw': 
					case 'bn': 
						$s .= $campo . ' ' . $sopt[$op] . " '".$dato."%' ";
						break;
					case 'ew': 
					case 'en': 
						$s .= $campo . ' ' . $sopt[$op] . " '%".$dato."' ";
						break;
					case 'cn': 
					case 'nc': 
						$s .= $campo . ' ' . $sopt[$op] . " '%".$dato."%' ";
						break;
					case 'in': 
					case 'ni': 
						$s .= $campo . ' ' . $sopt[$op] . " ( '".$dato."') ";
						break;
					case 'nu': 
					case 'nn': 
						$s .= $campo . ' ' . $sopt[$op] . " ";
						break;
					default : 
						/*if(is_numeric($dato))
							$s .= $campo . ' ' . $sopt[$op] . " $dato ";
						else*/
							$s .= $campo . ' ' . $sopt[$op] . " '".$dato."' ";
						break;
				}			
			}
		}
		if($s != ""){
			if($this->filtro != "")
				$resp->filtro = $this->filtro.' AND ('.$s.')';
			else
				$resp->filtro = $s;
		}else{
			$resp->filtro = $this->filtro;
		}
		$resp->norden		= $sidx;
		$resp->torden		= $sord;
		$resp->nlimite		= (int)$limit;
		$resp->ilimite		= (int)$start;
		$resp->page 		= $page;
		$resp->total 		= $total_pages;
		$resp->records 		= $count;
		$this->whereFiltro	= $resp;
		return $resp;
	}
	
	public function getData($objeto, $json = true){
		if($this->whereFiltro->filtro != "" )
			$objeto->where($this->whereFiltro->filtro);
		$objeto->order_by($this->whereFiltro->norden,$this->whereFiltro->torden);
		$objeto->limit($this->whereFiltro->nlimite,$this->whereFiltro->ilimite);

		if($this->operacion == 'excel' || $this->operacion == 'pdf'){
			$res = $objeto->get()->result_array();
			return $res;
		}		
		//print_r($this->db);	
		if($json)
			$res = $objeto->get()->result();
		else{
			$res = $objeto->get();
			return $res;	
		}
		$responce 			= new stdClass();
		$responce->page 	= $this->whereFiltro->page;
		$responce->total 	= $this->whereFiltro->total;
		$responce->records 	= $this->whereFiltro->records;
		//print_r($responce);
		//exit();
		$i					= 0;
		$ide				= $this->campos['id'];			
		foreach($res as $in=>$val){
			$responce->rows[$i]['id'] 	= $val->$ide;
			$j							= 0;
			$dato						= array();
			foreach($this->campos as $valor){
				$dato[$j] = $val->$valor;
				$j++;
			}
			$responce->rows[$i]['cell'] = $dato;
			$i++;				
		}
		header("Content-type: application/json");
		echo json_encode($responce);
		return true;				
	}
	/*exportacion a excel*/
	public static function fechaExcel($fecha) {
        $php = array('A', 'y', 'Y', 'M', 'F', 'i', 'm', 'n', 'l', 'D', 'd', 'j', 's');
        $excel = array('am/pm', 'yy', 'yyyy', 'mmm', 'mmmm', 'MM', 'mm', 'm', 'dddd', 'ddd', 'dd', 'd', 'ss');
        return str_replace($php, $excel, $fecha);
    }
    protected $propExcel = array(
		"file_type" => "xml", 
		"file" => "", 
		"start_cell" => "A1", 
		"creator" => "Luis Catacora", 
		"author" => "Luis Catacora", 
		"title" => "Excel exportado con JGrilla", 
		"subject" => "Office 2007 XLSX Document", 
		"description" => "Documento creado por Luis Catacora", 
		"keywords" => "Excel, JGrilla", 
		"font" => "Arial", 
		"font_size" => 11, 
		"header_logo" => "", 
		"header_logo_width" => 0, 
		"header_title" => "", 
		"protect" => false, 
		"password" => "ExcelGrilla");
	public $archivoExcel='ArchivoExportado';	
	public function setPropExcel($prop){
		if(is_array($prop)){
			$this->propExcel=$this->extenderArray($this->propExcel,$prop);
		}
	}
    protected function getExcel($objPHPExcel) {
        $es = $this->propExcel;
        $fn = $es['file'] !== '' ? $es['file'] : $this->archivoExcel;
		$ext = pathinfo($fn, PATHINFO_EXTENSION);
		if ($ext != 'xlsx') {
			$fn .= '.xlsx';
		} 
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename=' . $fn);
		header('Cache-Control: max-age=0');
		header('Cache-Control: max-age=1');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: cache, must-revalidate');
		header('Pragma: public');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
    }	
	public function exportaExcel($colmodel = false){
		$rs		= $this->getConsulta();
		$rows	= $this->nroRegistro;		
		$es 	= $this->propExcel;
	  	try {
            $objPHPExcel = new PHPExcel();
            $objWorksheet = $objPHPExcel->getActiveSheet();
        } catch (Exception $e) {
            echo $e->getMessage();
            exit();
        } 
		$typearr = array();
        $ncols = count($this->campos);
        $nmodel = is_array($colmodel) ? count($colmodel) : -1;
        if ($nmodel > 0) {
            for ($i = 0; $i < $nmodel; $i++) {
                if ($colmodel[$i]['name'] == 'actions') {
                    array_splice($colmodel, $i, 1);
                    $nmodel--;
                    break;
                }
            }
        } 
		//switch ($this->dbtype) {
       //     case 'oci8': case 'db2': case 'sqlsrv': case 'odbcsqlsrv': $nmodel++;
       //         break;
        //} 
		$model = false;
        if ($colmodel && $nmodel == $ncols) {
            $model = true;
        } 
		$aSum = array();
        $aFormula = array();
        $ahidden = array();
        $aselect = array();
        $hiddencount = 0;
        $fmtstr = array();
        $fnmkeys = array();
        list ($startColumn, $startRow) = PHPExcel_Cell::coordinateFromString($es['start_cell']);
        $currentColumn = $startColumn;
        if ($es['header_title']) {
            $objWorksheet->setCellValue($currentColumn . $startRow, $es['header_title']);
            ++$startRow;
        } 
		for ($i = 0; $i < $ncols; $i++) {
            $field = array();
            $fnmkeys[$i] = "";
            if ($model && isset($colmodel[$i])) {
                $fname = isset($colmodel[$i]["label"]) ? $colmodel[$i]["label"] : $colmodel[$i]["name"];
                $field["name"] = $colmodel[$i]["name"];
                $typearr[$i] = isset($colmodel[$i]["sorttype"]) ? $colmodel[$i]["sorttype"] : '';
            } else {
               // $field = jqGridDB::getColumnMeta($i, $rs);
               // $fname = $field["name"];
                //$typearr[$i] = jqGridDB::MetaType($field, $this->dbtype);
            } 
			$ahidden[$i] = ($model && isset($colmodel[$i]["hidden"])) ? $colmodel[$i]["hidden"] : false;
            $aselect[$i] = false;
            if ($model && isset($colmodel[$i]["formatter"])) {
                $cfmt = $colmodel[$i]["formatter"];
                $asl = isset($colmodel[$i]["formatoptions"]) ? $colmodel[$i]["formatoptions"] : $colmodel[$i]["editoptions"];
                switch ($cfmt) {
                    case "select": if (isset($asl["value"])) {
                            $sep = isset($asl["separator"]) ? $asl["separator"] : ":";
                            $delim = isset($asl["delimiter"]) ? $asl["delimiter"] : ";";
                            $list = explode($delim, $asl["value"]);
                            foreach ($list as $key => $val) {
                                $items = explode($sep, $val);
                                $aselect[$i][$items[0]] = $items[1];
                            }
                        } 
						break;
                    case "date" : if (isset($asl['newformat']) && $asl['newformat'] !== '') {
                            $fmtstr[$i] = $asl['newformat'];
                        } else {
                            if ($typearr[$i] == 'date') {
                                $fmtstr[$i] = $this->getUserDate();
                            } else {
                                $fmtstr[$i] = $this->getUserTime();
                            }
                        } 
						break;
                }
            } 
			if ($field["name"] == "jqgrid_row") {
                $ahidden[$i] = true;
            } 
			if ($ahidden[$i]) {
                $hiddencount++;
                continue;
            } 
			$fnmkeys[$i] = $model ? $colmodel[$i]["name"] : $fname[$i];
            $objWorksheet->setCellValue($currentColumn . $startRow, $fname);
            $objWorksheet->getColumnDimension($currentColumn)->setAutoSize(true);
            ++$currentColumn;
        } 
		$objWorksheet->getStyle($es['start_cell'] . ':' . $currentColumn . $startRow)->getFont()->setBold(true);
        ++$startRow;
		/*
 		foreach($rs as $r){
		//while ($r = jqGridDB::fetch_num($rs, $this->pdo)) {
            $currentColumn = $startColumn;
            //if ($this->dbtype == 'mysqli') {$r = $res_arr;} 
			for ($i = 0; $i < $ncols; $i++) {
                if (isset($ahidden[$i]) && $ahidden[$i]) {
                    continue;
                } 
				$v = $r[$i];
                if (is_array($aselect[$i])) {
                    if (isset($aselect[$i][$v])) {
                        $v1 = $aselect[$i][$v];
                        if ($v1) {
                            $v = $v1;
                        }
                    } $typearr[$i] = 'string';
                } 
				switch ($typearr[$i]) {
                    case 'date': 
					case 'datetime': 
						if (substr($v, 0, 4) == '0000' || empty($v) || $v == 'NULL') {
                            $v = '1970-01-01';
                        } 
						$objWorksheet->setCellValue($currentColumn . $startRow, PHPExcel_Shared_Date::stringToExcel($v));
                        $objWorksheet->getStyle($currentColumn . $startRow)->getNumberFormat()->setFormatCode(jqGridUtils::phpToExcelDate($fmtstr[$i]));
                        break;
                    case 'int': 
					case 'numeric': 
						$objWorksheet->setCellValueExplicit($currentColumn . $startRow, stripslashes((trim($v))), PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        break;
                    default: $objWorksheet->setCellValue($currentColumn . $startRow, $v);
                } 
				++$currentColumn;
            } 
			++$startRow;
            $rows += 1;
            if ($rows >= $gSQLMaxRows) {
                break;
            }
        } 
		$currentColumn = $startColumn;
        if ($this->tmpvar) {
            for ($i = 0; $i < $ncols; $i++) {
                if (isset($ahidden[$i]) && $ahidden[$i]) {
                    continue;
                } 
				$vv = '';
                foreach ($this->tmpvar as $key => $v) {
                    if ($fnmkeys[$i] == $key) {
                        $vv = $v;
                        break;
                    }
                } 
				$objWorksheet->setCellValue($currentColumn . $startRow, $vv);
                ++$currentColumn;
            }
        } 
		$this->setExcelOptions(array("end_cell" => $currentColumn . $startRow));
        $this->setExcelProp($objPHPExcel);
        if ($this->excelClass) {
            try {
                $objPHPExcel = call_user_func(array($this->excelClass, $this->excelFunc), $objPHPExcel);
            } catch (Exception $e) {
                echo "Can not call the method class - " . $e->getMessage();
            }
        } else if (function_exists($this->excelFunc)) {
            $objPHPExcel = call_user_func($this->excelFunc, $objPHPExcel);
        } 
		$this->getExcel($objPHPExcel);*/
        return true;		
	}
	public function exportaPDF(){
		print_r($this->getConsulta());
		echo "pdf";	
	}
}
?>