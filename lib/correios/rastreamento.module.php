<?php

/**
 * Classe de rastreamento dos correios. Usar como módulo para Correios
 *
 * Uso:
 * $c = new Correios();
 * $r = $c->Rastreamento($codigo);
 * echo $r;
 * echo $r->codigo;
 * echo $r->origem['sigla'];
 * ...
 *
 * $r->codigo = $novo_codigo;
 * echo $r;
 *
 * @author Gustavo Seganfredo
 * @package Correios
 * @version 1.0
 * @see Correios()
 */
class Rastreamento {

	/**
	 * Código do pacote
	 * @var string
	 */
	private $codigo;
	
	/**
	 * Tipo do serviço
	 * O tipo é representado nos dois primeiros caracteres do código
	 * @var array
	 */
	private $servico;
	
	/**
	 * Origem do pacote
	 * A origem é representada nos dois últimos caracteres do código
	 * @var array
	 */
	private $origem;
	
	/**
	 * Histórico do pacote (rastreio)
	 * @var array
	 */
	private $historico;
	
	/**
	 * URL de rastreamento de pacotes
	 * @var array
	 */
	private $resource = 'http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=';
	
	public function __construct($codigo) {

		// valida o código passado
		$regex = '/(([A-Z]{2})[0-9]{9}([A-Z]{2}))/';
		if (!preg_match($regex, $codigo, $match)) {
			throw new Exception("Código Inválido!");
		}
		
		$servico = $match[2];
		$origem  = $match[3];
		
		// valida o serviço, e a origem do código
		// O serviço é representado pelas duas primeiras letras do código.
		// A origem é reepresentada pelas duas últimas letras do código.
		if (!isset($this->servicos[$servico])) {
			throw new Exception("Código Inválido! Serviço \"{$servico}\" inexistente.");
		} elseif (!isset(Correios::$paises[$origem])) {
			throw new Exception("Código Inválido! Origem \"{$origem}\" inexistente.");
		}
		
		$this->codigo  = $codigo;
		$this->servico = array(
			'sigla' => $servico,
			'nome'  => $this->servicos[$servico],
		);
		$this->origem = array(
			'sigla' => $origem,
			'país' => Correios::$paises[$origem],
		);
		
		$this->fetch();
	}
	
	/**
	 * Função mágica que permite que a classe seja transformada em string
	 * Retorno padrão é na forma de um JSON
	 *
	 * @return string JSON relativo ao pacote
	 */
	public function __toString() {
		return $this->toJSON();
	}
	
	/**
	 * Função mágica para dar acesso de leitura aos atributos do pacote
	 *
	 * @param string $prop Propriedade sendo acessada
	 * @return mixed Valor atual da propriedade
	 */
	public function __get($prop) {
		if (isset($this->$prop)) return $this->$prop;
		return null;
	}
	
	/**
	 * Função mágica que permite que o código seja alterado
	 * Ao alterar o código, ele carrega os dados do novo pacote
	 * Qualquer outra alteração nas propriedades do pacote é bloqueada
	 *
	 * @param string $prop  Propriedade sendo acessada
	 * @param string $value Novo valor para a propriedade
	 * @return 
	 */
	public function __set($prop, $value) {
		if ($prop == 'codigo') 
			$this->__construct($value);
	}
	
	/**
	 * Função que busca dados do pacote no site dos correios
	 */
	private function fetch() {
		$info = file_get_contents($this->resource.$this->codigo);
		
		$regex = '/(rowspan=([0-9])>(.*?)<\/td><td>(.*?)<.*>(.*?)<\/font)|(colspan=([0-9])>(.*?)<)/i';
		preg_match_all($regex, $info, $match);
		
		$historico = array();
		$x = -1;

		for ($i = 0; $i < sizeof($match[0]); $i++) {
			if ($match[4][$i]) { 
				$x++;
				$historico[$x]['data'] = $match[3][$i];
				$historico[$x]['local'] = $match[4][$i];
				$historico[$x]['status'] = $match[5][$i];
			} else {
				$historico[$x]['detalhe'] = $match[8][$i];
			}
		}
		$this->historico = array_reverse($historico);
	}
	
	/**
	 * Função que gera o JSON para o pacote
	 *
	 * @return string JSON relativo ao pacote
	 */
	public function toJSON() {
		$pacote = array(
			'codigo' => $this->codigo,
			'origem' => $this->origem,
			'servico' => $this->servico,
			'historico' => $this->historico,
		);
		return json_encode($pacote);
	}
	
	/**
	 * Lista de serviços dos correios
	 *
	 * http://www.correios.com.br/servicos/rastreamento/internacional/siglas.cfm
	 * Última atualização em 01/06/2011
	 */
	private $servicos = array(
		'AL' => 'AGENTES DE LEITURA',
		'AR' => 'AVISO DE RECEBIMENTO',
		'CA' => 'OBJETO INTERNACIONAL',
		'CC' => 'COLIS POSTAUX',
		'CD' => 'OBJETO INTERNACIONAL',
		'CE' => 'OBJETO INTERNACIONAL',
		'CG' => 'OBJETO INTERNACIONAL',
		'CJ' => 'REGISTRADO INTERNACIONAL',
		'CK' => 'OBJETO INTERNACIONAL',
		'CL' => 'OBJETO INTERNACIONAL',
		'CP' => 'COLIS POSTAUX',
		'CR' => 'CARTA REGISTRADA SEM VALOR DECLARADO',
		'CS' => 'OBJETO INTERNACIONAL',
		'CT' => 'OBJETO INTERNACIONAL',
		'CV' => 'REGISTRADO INTERNACIONAL',
		'CY' => 'OBJETO INTERNACIONAL',
		'DA' => 'REM EXPRES COM AR DIGITAL',
		'DB' => 'REM EXPRES COM AR DIGITAL BRADESCO',
		'DC' => 'REM EXPRESSA CRLV/CRV/CNH e NOTIFICAÇÃO',
		'DD' => 'DEVOLUÇÃO DE DOCUMENTOS',
		'DE' => 'REMESSA EXPRESSA TALÃO E CARTÃO C/ AR',
		'DI' => 'REM EXPRES COM AR DIGITAL ITAU',
		'DP' => 'REM EXPRES COM AR DIGITAL PRF',
		'DS' => 'REM EXPRES COM AR DIGITAL SANTANDER',
		'DT' => 'REMESSA ECON.SEG.TRANSITO C/AR DIGITAL',
		'EA' => 'OBJETO INTERNACIONAL',
		'EB' => 'OBJETO INTERNACIONAL',
		'EC' => 'ENCOMENDA PAC',
		'ED' => 'OBJETO INTERNACIONAL',
		'EE' => 'SEDEX INTERNACIONAL',
		'EF' => 'OBJETO INTERNACIONAL',
		'EG' => 'OBJETO INTERNACIONAL',
		'EH' => 'ENCOMENDA NORMAL COM AR DIGITAL',
		'EI' => 'OBJETO INTERNACIONAL',
		'EJ' => 'ENCOMENDA INTERNACIONAL',
		'EK' => 'OBJETO INTERNACIONAL',
		'EL' => 'OBJETO INTERNACIONAL',
		'EM' => 'OBJETO INTERNACIONAL',
		'EN' => 'ENCOMENDA NORMAL NACIONAL',
		'EP' => 'OBJETO INTERNACIONAL',
		'EQ' => 'ENCOMENDA SERVIÇO NÃO EXPRESSA ECT',
		'ER' => 'REGISTRADO',
		'ES' => 'e-SEDEX',
		'EF' => 'OBJETO INTERNACIONAL',
		'EG' => 'OBJETO INTERNACIONAL',
		'EF' => 'OBJETO INTERNACIONAL',
		'EU' => 'OBJETO INTERNACIONAL',
		'EV' => 'OBJETO INTERNACIONAL',
		'EX' => 'OBJETO INTERNACIONAL',
		'FE' => 'ENCOMENDA FNDE',
		'FF' => 'REGISTRADO DETRAN',
		'FH' => 'REGISTRADO FAC COM AR DIGITAL',
		'FM' => 'REGISTRADO - FAC MONITORADO',
		'FR' => 'REGISTRADO FAC',
		'IA' => 'INTEGRADA AVULSA',
		'IC' => 'INTEGRADA A COBRAR',
		'ID' => 'INTEGRADA DEVOLUCAO DE DOCUMENTO',
		'IE' => 'INTEGRADA ESPECIAL',
		'IF' => 'CPF',
		'II' => 'INTEGRADA INTERNO',
		'IK' => 'INTEGRADA COM COLETA SIMULTANEA',
		'IM' => 'INTEGRADA MEDICAMENTOS',
		'IN' => 'OBJ DE CORRESP E EMS REC EXTERIOR',
		'IP' => 'INTEGRADA PROGRAMADA',
		'IR' => 'IMPRESSO REGISTRADO',
		'IS' => 'INTEGRADA STANDARD',
		'IT' => 'INTEGRADO TERMOLÁBIL',
		'IU' => 'INTEGRADA URGENTE',
		'JA' => 'REMESSA ECONOMICA C/AR DIGITAL',
		'JB' => 'REMESSA ECONOMICA C/AR DIGITAL',
		'JC' => 'REMESSA ECONOMICA C/AR DIGITAL',
		'JJ' => 'REGISTRADO JUSTIÇA',
		'LC' => 'CARTA EXPRESSA',
		'LE' => 'LOGÍSTICA REVERSA ECONOMICA',
		'LF' => 'OBJETO INTERNACIONAL',
		'LI' => 'OBJETO INTERNACIONAL',
		'LJ' => 'OBJETO INTERNACIONAL',
		'LM' => 'OBJETO INTERNACIONAL',
		'LS' => 'LOGISTICA REVERSA SEDEX',
		'LV' => 'LOGISTICA REVERSA EXPRESSA',
		'LX' => 'CARTA EXPRESSA',
		'LY' => 'CARTA EXPRESSA',
		'MA' => 'SERVIÇOS ADICIONAIS',
		'MB' => 'TELEGRAMA DE BALCÃO',
		'MC' => 'MALOTE CORPORATIVO',
		'MD' => 'SEDEX MUNDI - DOCUMENTO INTERNO',
		'ME' => 'TELEGRAMA',
		'MF' => 'TELEGRAMA FONADO',
		'MK' => 'TELEGRAMA CORPORATIVO',
		'MM' => 'TELEGRAMA GRANDES CLIENTES',
		'MP' => 'TELEGRAMA PRÉ-PAGO',
		'MS' => 'ENCOMENDA SAUDE',
		'MT' => 'TELEGRAMA VIA TELEMAIL',
		'MY' => 'TELEGRAMA INTERNACIONAL ENTRANTE',
		'MZ' => 'TELEGRAMA VIA CORREIOS ON LINE',
		'NE' => 'TELE SENA RESGATADA',
		'PA' => 'PASSAPORTE',
		'PB' => 'ENCOMENDA PAC - NÃO URGENTE',
		'PR' => 'REEMBOLSO POSTAL - CLIENTE AVULSO',
		'RA' => 'REGISTRADO PRIORITÁRIO',
		'RB' => 'CARTA REGISTRADA',
		'RC' => 'CARTA REGISTRADA COM VALOR DECLARADO',
		'RD' => 'REMESSA ECONOMICA DETRAN',
		'RE' => 'REGISTRADO ECONÔMICO',
		'RF' => 'OBJETO DA RECEITA FEDERAL',
		'RG' => 'REGISTRADO DO SISTEMA SARA',
		'RH' => 'REGISTRADO COM AR DIGITAL',
		'RI' => 'REGISTRADO',
		'RJ' => 'REGISTRADO AGÊNCIA',
		'RK' => 'REGISTRADO AGÊNCIA',
		'RL' => 'REGISTRADO LÓGICO',
		'RM' => 'REGISTRADO AGÊNCIA',
		'RN' => 'REGISTRADO AGÊNCIA',
		'RO' => 'REGISTRADO AGÊNCIA',
		'RP' => 'REEMBOLSO POSTAL - CLIENTE INSCRITO',
		'RQ' => 'REGISTRADO AGÊNCIA',
		'RR' => 'CARTA REGISTRADA SEM VALOR DECLARADO',
		'RS' => 'REGISTRADO LÓGICO',
		'RT' => 'REM ECON TALAO/CARTAO SEM AR DIGITA',
		'RU' => 'REGISTRADO SERVIÇO ECT',
		'RV' => 'REM ECON CRLV/CRV/CNH COM AR DIGITAL',
		'RW' => 'OBJETO INTERNACIONAL',
		'RX' => 'OBJETO INTERNACIONAL',
		'RY' => 'REM ECON TALAO/CARTAO COM AR DIGITAL',
		'RZ' => 'REGISTRADO',
		'SA' => 'SEDEX ANOREG',
		'SC' => 'SEDEX A COBRAR',
		'SD' => 'REMESSA EXPRESSA DETRAN',
		'SE' => 'ENCOMENDA SEDEX',
		'SF' => 'SEDEX AGÊNCIA',
		'SG' => 'SEDEX DO SISTEMA SARA',
		'RH' => 'REGISTRADO COM AR DIGITAL',
		'SI' => 'SEDEX AGÊNCIA',
		'SJ' => 'SEDEX HOJE',
		'SK' => 'SEDEX AGÊNCIA',
		'SL' => 'SEDEX LÓGICO',
		'SM' => 'SEDEX MESMO DIA',
		'SN' => 'SEDEX COM VALOR DECLARADO',
		'SO' => 'SEDEX AGÊNCIA',
		'SP' => 'SEDEX PRÉ-FRANQUEADO',
		'SQ' => 'SEDEX',
		'SR' => 'SEDEX',
		'SS' => 'SEDEX FÍSICO',
		'ST' => 'REM EXPRES TALAO/CARTAO SEM AR DIGITAL',
		'SU' => 'ENCOMENDA SERVIÇO EXPRESSA ECT',
		'SV' => 'REM EXPRES CRLV/CRV/CNH COM AR DIGITAL',
		'SW' => 'e-SEDEX',
		'SX' => 'SEDEX 10',
		'SY' => 'REM EXPRES TALAO/CARTAO COM AR DIGITAL',
		'SZ' => 'SEDEX AGÊNCIA',
		'TE' => 'TESTE (OBJETO PARA TREINAMENTO)',
		'TS' => 'TESTE (OBJETO PARA TREINAMENTO)',
		'VA' => 'ENCOMENDAS COM VALOR DECLARADO',
		'VC' => 'ENCOMENDAS',
		'VD' => 'ENCOMENDAS COM VALOR DECLARADO',
		'VE' => 'ENCOMENDAS',
		'VF' => 'ENCOMENDAS COM VALOR DECLARADO',
		'VV' => 'OBJETO INTERNACIONAL',
		'XM' => 'SEDEX MUNDI',
		'XR' => 'ENCOMENDA SUR POSTAL EXPRESSO',
		'XX' => 'ENCOMENDA SUR POSTAL 24 HORAS',
	);
}

